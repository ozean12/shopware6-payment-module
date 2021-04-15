<?php declare(strict_types=1);

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
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\StateMachine\Event\StateMachineTransitionEvent;
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
     * @var ShipOrderRequest
     */
    private $shipOrderRequest;
    /**
     * @var CancelOrderRequest
     */
    private $cancelOrderRequest;
    /**
     * @var DocumentUrlHelper
     */
    private $documentUrlHelper;

    public function __construct(
        EntityRepositoryInterface $orderDeliveryRepository,
        EntityRepositoryInterface $orderRepository,
        ShipOrderRequest $shipOrderRequest,
        CancelOrderRequest $cancelOrderRequest,
        ConfigService $configService,
        DocumentUrlHelper $documentUrlHelper,
        Logger $logger
    )
    {
        $this->orderDeliveryRepository = $orderDeliveryRepository;
        $this->orderRepository = $orderRepository;
        $this->configService = $configService;
        $this->logger = $logger;
        $this->shipOrderRequest = $shipOrderRequest;
        $this->cancelOrderRequest = $cancelOrderRequest;
        $this->documentUrlHelper = $documentUrlHelper;
    }

    public static function getSubscribedEvents()
    {
        return [
            StateMachineTransitionEvent::class => 'onTransition',
        ];
    }

    public function onTransition(StateMachineTransitionEvent $event)
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

            switch ($event->getToPlace()->getTechnicalName()) {
                case $this->configService->getStateForShip():
                    $invoiceNumber = $billieData->getExternalInvoiceNumber();
                    $invoiceUrl = $billieData->getExternalInvoiceUrl();
                    $shippingUrl = $billieData->getExternalDeliveryNoteUrl();

                    if (!$invoiceNumber) {
                        foreach ($order->getDocuments() as $document) {

                            if ($invoiceNumber === null && $document->getDocumentType()->getTechnicalName() === 'invoice') {
                                $config = $document->getConfig();
                                $invoiceNumber = isset($config['custom']['invoiceNumber']) ? $config['custom']['invoiceNumber'] : null;
                                $invoiceUrl = $this->documentUrlHelper->generateRouteForDocument($document);
                            }

                            if ($shippingUrl === null && $document->getDocumentType()->getTechnicalName() === 'delivery_note') {
                                $shippingUrl = $this->documentUrlHelper->generateRouteForDocument($document);
                            }
                        }
                    }

                    $data = new ShipOrderRequestModel($billieData->getReferenceId());
                    $data->setExternalOrderId($order->getOrderNumber())
                        ->setInvoiceNumber($invoiceNumber)
                        ->setInvoiceUrl($invoiceUrl ?? '.')
                        ->setShippingDocumentUrl($shippingUrl);

                    try {
                        $this->shipOrderRequest->execute($data);
                    } catch (BillieException $e) {
                        $this->logError($e, $order, $billieData);
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
                        $this->cancelOrderRequest->execute(new OrderRequestModel($billieData->getReferenceId()));
                    } catch (BillieException $e) {
                        $this->logError($e, $order, $billieData);
                    }
                    break;
            }
            return;
        }
    }

    protected function getOrder(string $orderId, Context $context): OrderEntity
    {
        $criteria = CriteriaHelper::getCriteriaForOrder($orderId);
        $criteria->addAssociation('documents.documentType');
        return $this->orderRepository->search($criteria, $context)->first();
    }

    private function logError(BillieException $e, OrderEntity $order, OrderDataEntity $billieData)
    {
        $this->logger->addCritical('Exception during ship. (Exception: ' . $e->getMessage() . ')', [
            'error' => $e->getBillieCode(),
            'order' => $order->getId(),
            'billie-reference-id' => $billieData->getReferenceId(),
        ]);
    }
}
