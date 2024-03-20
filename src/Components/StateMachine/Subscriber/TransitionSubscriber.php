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
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\StateMachine\Event\StateMachineTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TransitionSubscriber implements EventSubscriberInterface
{
    private ConfigService $configService;

    /**
     * @var EntityRepository<OrderDeliveryCollection>
     * the interface has been deprecated, but shopware is using the Interface in a decorator for the repository.
     * so it will crash, if we are only using EntityRepository, cause an object of the decorator got injected into the constructor.
     * After Shopware has removed the decorator, we can replace this by a normal definition
     * TODO remove comment on Shopware Version 6.5.0.0 & readd type hint & change constructor argument type
     */
    private object $orderDeliveryRepository;

    /**
     * @var EntityRepository<OrderCollection>
     * the interface has been deprecated, but shopware is using the Interface in a decorator for the repository.
     * so it will crash, if we are only using EntityRepository, cause an object of the decorator got injected into the constructor.
     * After Shopware has removed the decorator, we can replace this by a normal definition
     * TODO remove comment on Shopware Version 6.5.0.0 & readd type hint & change constructor argument type
     */
    private object $orderRepository;

    private OperationService $operationService;

    public function __construct(
        object $orderDeliveryRepository,
        object $orderRepository,
        ConfigService $configService,
        OperationService $operationService
    ) {
        $this->orderDeliveryRepository = $orderDeliveryRepository;
        $this->orderRepository = $orderRepository;
        $this->configService = $configService;
        $this->operationService = $operationService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            StateMachineTransitionEvent::class => 'onTransition',
        ];
    }

    public function onTransition(StateMachineTransitionEvent $event): void
    {
        if (!$this->configService->isStateWatchingEnabled()) {
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
        if (!$billieData instanceof OrderDataEntity) {
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
            case $this->configService->getStateReturn():
                $this->operationService->return($order);
                break;
        }
    }

    protected function getOrder(string $orderId, Context $context): OrderEntity
    {
        $criteria = CriteriaHelper::getCriteriaForOrder($orderId);
        $criteria->addAssociation('documents.documentType');

        /** @var OrderEntity $orderEntity */
        $orderEntity = $this->orderRepository->search($criteria, $context)->first();

        return $orderEntity;
    }
}
