<?php declare(strict_types=1);

namespace Billie\BilliePayment\Components\Checkout\Service;

use Billie\BilliePayment\Components\PaymentMethod\Util\MethodHelper;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Payment\SalesChannel\AbstractPaymentMethodRoute;
use Shopware\Core\Checkout\Payment\SalesChannel\PaymentMethodRouteResponse;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PaymentMethodRoute extends AbstractPaymentMethodRoute
{
    /**
     * @var AbstractPaymentMethodRoute
     */
    private $innerService;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var EntityRepositoryInterface
     */
    private $countryRepository;

    public function __construct(
        AbstractPaymentMethodRoute $innerService,
        RequestStack $requestStack,
        EntityRepositoryInterface $orderRepository,
        EntityRepositoryInterface $countryRepository
    )
    {
        $this->innerService = $innerService;
        $this->requestStack = $requestStack;
        $this->orderRepository = $orderRepository;
        $this->countryRepository = $countryRepository;
    }

    public function getDecorated(): AbstractPaymentMethodRoute
    {
        return $this;
    }

    public function load(Request $request, SalesChannelContext $salesChannelContext, ?Criteria $criteria = null): PaymentMethodRouteResponse
    {
        $response = $this->innerService->load($request, $salesChannelContext, $criteria);

        $currentRequest = $this->requestStack->getCurrentRequest();
        if (!$currentRequest) {
            return $response;
        }

        // if the order id is set, the oder has been already placed, and the customer may tries to change/edit
        // the payment method. - e.g. in case of a failed payment
        $orderId = $currentRequest->get('orderId');
        $order = null;
        if ($orderId) {
            $criteria = (new Criteria([$orderId]))
                ->addAssociation('billingAddress');
            /** @var OrderEntity $order */
            $order = $this->orderRepository->search($criteria, $salesChannelContext->getContext())->first();
            $billingAddress = $order->getBillingAddress();
        } else {
            $customer = $salesChannelContext->getCustomer();
            $billingAddress = $customer ? $customer->getActiveBillingAddress() : null;
        }

        if ($order || $request->query->getBoolean('onlyAvailable', false)) {

            $me = $this;
            $paymentMethods = $response->getPaymentMethods()->filter(static function (PaymentMethodEntity $paymentMethod) use ($me, $billingAddress) {
                return ($billingAddress && MethodHelper::isBilliePayment($paymentMethod) === false) ||
                    (
                        !empty($billingAddress->getCompany()) &&
                        $me->getCountryIso($billingAddress) === 'DE'
                    );
            });
            return new PaymentMethodRouteResponse($paymentMethods);
        }

        return $response;
    }

    /**
     * @param CustomerAddressEntity|OrderAddressEntity $address
     * @return string|null
     */
    private function getCountryIso($address): string
    {
        // the whole function does not make any sense.
        // on the checkout-confirm page, the country-assoc is loaded.
        // but the payment methods will be also requested on the success/finish page. On this page, the country-assoc isn't loaded.
        // there is no reason to load the payment methods on the finish page. - so this is just a fix, during the methods will be loaded on the success-page.

        if ($address->getCountry() === null) {
            $country = $this->countryRepository->search(new Criteria([$address->getCountryId()]), Context::createDefaultContext())->first();
        } else {
            $country = $address->getCountry();
        }
        return $country->getIso();
    }
}
