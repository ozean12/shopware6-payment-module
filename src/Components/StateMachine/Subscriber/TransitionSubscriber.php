<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\StateMachine\Subscriber;

use Billie\BilliePayment\Components\BillieApi\Service\OperationService;
use Billie\BilliePayment\Components\Order\Model\Extension\OrderExtension;
use Billie\BilliePayment\Components\Order\Model\OrderDataEntity;
use Billie\BilliePayment\Components\PluginConfig\Service\ConfigService;
use Billie\BilliePayment\Util\CriteriaHelper;
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
     * @var \Billie\BilliePayment\Components\BillieApi\Service\OperationService
     */
    private $operationService;

    public function __construct(
        EntityRepositoryInterface $orderDeliveryRepository,
        EntityRepositoryInterface $orderRepository,
        ConfigService $configService,
        OperationService $operationService
    ) {
        $this->orderDeliveryRepository = $orderDeliveryRepository;
        $this->orderRepository = $orderRepository;
        $this->configService = $configService;
        $this->operationService = $operationService;
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
        } elseif ($event->getEntityName() === OrderDefinition::ENTITY_NAME) {
            $order = $this->getOrder($event->getEntityId(), $event->getContext());
        } else {
            return;
        }

        /** @var OrderDataEntity|null $billieData */
        $billieData = $order->getExtension(OrderExtension::EXTENSION_NAME);
        if ($billieData === null) {
            // this is not a billie order - or if it is, the order data is broken
            return;
        }

        switch ($event->getToPlace()->getTechnicalName()) {
            case $this->configService->getStateForShip():
                $this->operationService->ship($order);
                break;
            case $this->configService->getStateCancel():
                $this->operationService->cancel($order);
                break;
        }
    }

    protected function getOrder(string $orderId, Context $context): OrderEntity
    {
        $criteria = CriteriaHelper::getCriteriaForOrder($orderId);
        $criteria->addAssociation('documents.documentType');

        return $this->orderRepository->search($criteria, $context)->first();
    }
}
