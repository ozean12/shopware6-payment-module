<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\StateMachine\Service;

use Billie\BilliePayment\Components\Order\Model\Extension\OrderExtension;
use Billie\BilliePayment\Components\Order\Model\OrderDataEntity;
use Billie\BilliePayment\Components\PaymentMethod\Util\MethodHelper;
use Billie\BilliePayment\Components\PluginConfig\Service\ConfigService;
use Billie\BilliePayment\Components\StateMachine\Exception\InvoiceNumberMissingException;
use Billie\BilliePayment\Util\CriteriaHelper;
use Shopware\Core\Checkout\Document\Renderer\InvoiceRenderer;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateCollection;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\StateMachineEntity;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;

class StateMachineRegistryDecorator extends StateMachineRegistry // we must extend it, cause there is no interface
{
    /**
     * @param EntityRepository<OrderCollection> $orderRepository
     * @param EntityRepository<OrderDeliveryCollection> $orderDeliveryRepository
     * @noinspection MagicMethodsValidityInspection
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(
        private readonly StateMachineRegistry $innerService,
        private readonly ConfigService $configService,
        private readonly EntityRepository $orderRepository,
        private readonly EntityRepository $orderDeliveryRepository
    ) {
    }

    public function transition(Transition $transition, Context $context): StateMachineStateCollection
    {
        if ($this->configService->isStateWatchingEnabled()
            && $transition->getEntityName() === OrderDeliveryDefinition::ENTITY_NAME
            && $transition->getTransitionName() === StateMachineTransitionActions::ACTION_SHIP
        ) {
            /** @var OrderDeliveryEntity $orderDelivery */
            $orderDelivery = $this->orderDeliveryRepository->search(new Criteria([$transition->getEntityId()]), $context)->first();
            $order = $this->getOrder($orderDelivery->getOrderId(), $context);

            $transaction = $order instanceof OrderEntity ? $order->getTransactions()->first() : null;
            $paymentMethod = $transaction instanceof OrderTransactionEntity ? $transaction->getPaymentMethod() : null;
            if ($paymentMethod &&
                MethodHelper::isBilliePayment($paymentMethod) &&
                !$this->orderHasBillieInvoiceNumber($order)
            ) {
                throw new InvoiceNumberMissingException();
            }
        }

        return $this->innerService->transition($transition, $context);
    }

    // not changed methods

    /**
     * @deprecated method has been removed from shopware core
     */
    public function getInitialState(string $stateMachineName, Context $context): StateMachineStateEntity
    {
        /** @phpstan-ignore-next-line */
        return $this->innerService->getInitialState($stateMachineName, $context);
    }

    public function getAvailableTransitions(string $entityName, string $entityId, string $stateFieldName, Context $context): array
    {
        return $this->innerService->getAvailableTransitions($entityName, $entityId, $stateFieldName, $context);
    }

    public function getStateMachine(string $name, Context $context): StateMachineEntity
    {
        return $this->innerService->getStateMachine($name, $context);
    }

    protected function orderHasBillieInvoiceNumber(OrderEntity $order): bool
    {
        /** @var OrderDataEntity $billieData */
        $billieData = $order->getExtension(OrderExtension::EXTENSION_NAME);

        $invoiceNumber = $billieData->getExternalInvoiceNumber();
        if (!$invoiceNumber) {
            foreach ($order->getDocuments() as $document) {
                if ($document->getDocumentType()->getTechnicalName() === InvoiceRenderer::TYPE) {
                    $config = $document->getConfig();

                    return isset($config['custom']['invoiceNumber']);
                }
            }

            return false;
        }

        return true;
    }

    protected function getOrder(string $orderId, Context $context): ?OrderEntity
    {
        $criteria = CriteriaHelper::getCriteriaForOrder($orderId);
        $criteria->addAssociation('documents.documentType');

        /** @var OrderEntity $orderEntity */
        $orderEntity = $this->orderRepository->search($criteria, $context)->first();

        return $orderEntity;
    }
}
