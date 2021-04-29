<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\StateMachine\Subscriber;

use Billie\BilliePayment\Components\Order\Model\Extension\OrderExtension;
use Billie\BilliePayment\Components\Order\Model\OrderDataEntity;
use Billie\BilliePayment\Components\Order\Util\DocumentUrlHelper;
use Billie\BilliePayment\Components\PluginConfig\Service\ConfigService;
use Billie\BilliePayment\Util\CriteriaHelper;
use Billie\Sdk\Exception\BillieException;
use Billie\Sdk\Model\Request\OrderRequestModel;
use Billie\Sdk\Model\Request\ShipOrderRequestModel;
use Billie\Sdk\Service\Request\CancelOrderRequest;
use Billie\Sdk\Service\Request\ShipOrderRequest;
use Monolog\Logger;
use Shopware\Core\Checkout\Document\DocumentGenerator\DeliveryNoteGenerator;
use Shopware\Core\Checkout\Document\DocumentGenerator\InvoiceGenerator;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\StateMachine\Event\StateMachineTransitionEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TransitionSubscriber implements EventSubscriberInterface
{
    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderDeliveryRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var DocumentUrlHelper
     */
    private $documentUrlHelper;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(
        ContainerInterface $container,
        EntityRepositoryInterface $orderDeliveryRepository,
        EntityRepositoryInterface $orderRepository,
        ConfigService $configService,
        DocumentUrlHelper $documentUrlHelper,
        Logger $logger
    ) {
        $this->container = $container;
        $this->orderDeliveryRepository = $orderDeliveryRepository;
        $this->orderRepository = $orderRepository;
        $this->configService = $configService;
        $this->logger = $logger;
        $this->documentUrlHelper = $documentUrlHelper;
    }

    public static function getSubscribedEvents()
    {
        return [
            StateMachineTransitionEvent::class => 'onTransition',
        ];
    }

    public function onTransition(StateMachineTransitionEvent $event): void
    {
        if ($this->configService->isStateWatchingEnabled() === false) {
            return;
        }

        if ($event->getEntityName() === OrderDeliveryDefinition::ENTITY_NAME) {
            /** @var OrderDeliveryEntity $orderDelivery */
            $orderDelivery = $this->orderDeliveryRepository->search(new Criteria([$event->getEntityId()]), $event->getContext())->first();
            $order = $this->getOrder($orderDelivery->getOrderId(), $event->getContext());

            /** @var OrderDataEntity $billieData */
            $billieData = $order->getExtension(OrderExtension::EXTENSION_NAME);
            if ($billieData === null) {
                // this is not a billie order - or if it is, we can not process it, without the order-data extension
                return;
            }

            switch ($event->getToPlace()->getTechnicalName()) {
                case $this->configService->getStateForShip():
                    $invoiceNumber = $billieData->getExternalInvoiceNumber();
                    $invoiceUrl = $billieData->getExternalInvoiceUrl();
                    $shippingUrl = $billieData->getExternalDeliveryNoteUrl();

                    if (!$invoiceNumber || !$shippingUrl) {
                        foreach ($order->getDocuments() as $document) {
                            if ($invoiceNumber === null &&
                                $document->getDocumentType()->getTechnicalName() === InvoiceGenerator::INVOICE
                            ) {
                                $config = $document->getConfig();
                                $invoiceNumber = isset($config['custom']['invoiceNumber']) ? $config['custom']['invoiceNumber'] : null;
                                $invoiceUrl = $this->documentUrlHelper->generateRouteForDocument($document);
                            }

                            if ($shippingUrl === null &&
                                $document->getDocumentType()->getTechnicalName() === DeliveryNoteGenerator::DELIVERY_NOTE
                            ) {
                                $shippingUrl = $this->documentUrlHelper->generateRouteForDocument($document);
                            }
                        }
                    }

                    $data = new ShipOrderRequestModel($billieData->getReferenceId());
                    $data->setInvoiceNumber($invoiceNumber)
                        ->setInvoiceUrl($invoiceUrl ?? '.')
                        ->setShippingDocumentUrl($shippingUrl);

                    try {
                        /* @noinspection NullPointerExceptionInspection */
                        $this->container->get(ShipOrderRequest::class)->execute($data);
                    } catch (BillieException $e) {
                        $this->logger->addCritical(
                            'Exception during shipment. (Exception: ' . $e->getMessage() . ')',
                            [
                                'error' => $e->getBillieCode(),
                                'order' => $order->getId(),
                                'billie-reference-id' => $billieData->getReferenceId(),
                            ]
                        );
                    }
                    break;
            }

            return;
        }

        if ($event->getEntityName() === OrderDefinition::ENTITY_NAME) {
            $order = $this->getOrder($event->getEntityId(), $event->getContext());
            /** @var OrderDataEntity $billieData */
            $billieData = $order->getExtension(OrderExtension::EXTENSION_NAME);

            switch ($event->getToPlace()->getTechnicalName()) {
                case $this->configService->getStateCancel():
                    try {
                        /* @noinspection NullPointerExceptionInspection */
                        $this->container->get(CancelOrderRequest::class)
                            ->execute(new OrderRequestModel($billieData->getReferenceId()));
                    } catch (BillieException $e) {
                        $this->logger->addCritical(
                            'Exception during cancellation. (Exception: ' . $e->getMessage() . ')',
                            [
                                'error' => $e->getBillieCode(),
                                'order' => $order->getId(),
                                'billie-reference-id' => $billieData->getReferenceId(),
                            ]
                        );
                    }
                    break;
            }
        }
    }

    protected function getOrder(string $orderId, Context $context): OrderEntity
    {
        $criteria = CriteriaHelper::getCriteriaForOrder($orderId);
        $criteria->addAssociation('documents.documentType');

        return $this->orderRepository->search($criteria, $context)->first();
    }
}
