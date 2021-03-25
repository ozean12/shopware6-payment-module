<?php

namespace Billie\BilliePayment\Components\RedirectException\Subscriber;

use Billie\BilliePayment\Components\RedirectException\Exception\ForwardException;
use Billie\BilliePayment\Components\RedirectException\Exception\RedirectException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\RequestTransformerInterface;
use Shopware\Storefront\Framework\Routing\Router;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RedirectExceptionListener implements EventSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var OrderTransactionStateHandler
     */
    private $orderTransactionStateHandler;

    public function __construct(
        ContainerInterface $container,
        OrderTransactionStateHandler $orderTransactionStateHandler
    ) {
        $this->container = $container;
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 9000],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        $prevThrowable = $throwable->getPrevious();
        if ($prevThrowable instanceof SyncPaymentProcessException) {
            // set the transaction to failed. Without this, the customer will not be able to try a repayment.
            $this->orderTransactionStateHandler->fail($prevThrowable->getOrderTransactionId(), Context::createDefaultContext());
        }

        if ($throwable instanceof RedirectException) {
            $event->setResponse($throwable->getRedirectResponse());
        } elseif ($throwable instanceof ForwardException) {
            /** @var UrlGeneratorInterface $router */
            $router = $this->container->get('router');
            $url = $router->generate(
                $throwable->getRoute(),
                $throwable->getRouteParams(),
                Router::PATH_INFO
            );

            // for the route matching the request method is set to "GET" because
            // this method is not ought to be used as a post passthrough
            // rather it shall return templates or redirects to display results of the request ahead
            $method = $router->getContext()->getMethod();
            $router->getContext()->setMethod(Request::METHOD_GET);

            $route = $router->match($url);
            $router->getContext()->setMethod($method); // reset method

            $attributes = array_merge(
                $this->container->get(RequestTransformerInterface::class)->extractInheritableAttributes($event->getRequest()),
                $route,
                $throwable->getQueryParams(),
                ['_route_params' => $throwable->getRouteParams()]
            );
            $subRequest = $event->getRequest()->duplicate($route, null, $attributes);

            $response = $this->container->get('http_kernel')
                ->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
            $event->setResponse($response);
            $event->allowCustomResponseCode();
        }
    }
}
