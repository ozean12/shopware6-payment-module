<?php declare(strict_types=1);

namespace Billie\BilliePayment\Components\StateMachine\Service;

use Billie\BilliePayment\Components\Order\Model\Extension\OrderExtension;
use Billie\BilliePayment\Components\Order\Model\OrderDataEntity;
use Billie\BilliePayment\Components\PluginConfig\Service\ConfigService;
use Billie\BilliePayment\Util\CriteriaHelper;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateCollection;
use Shopware\Core\System\StateMachine\Exception\IllegalTransitionException;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class StateMachineRegistryDecorator extends StateMachineRegistry
{
    /**
     * @var ConfigService
     */
    protected $configService;

    /**
     * @var EntityRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var EntityRepositoryInterface
     */
    protected $orderDeliveryRepository;

    public function __construct(
        EntityRepositoryInterface $stateMachineRepository,
        EntityRepositoryInterface $stateMachineStateRepository,
        EntityRepositoryInterface $stateMachineHistoryRepository,
        EventDispatcherInterface $eventDispatcher,
        DefinitionInstanceRegistry $definitionRegistry,
        ConfigService $configService,
        EntityRepositoryInterface $orderRepository,
        EntityRepositoryInterface $orderDeliveryRepository
    ) {
        parent::__construct(
            $stateMachineRepository,
            $stateMachineStateRepository,
            $stateMachineHistoryRepository,
            $eventDispatcher,
            $definitionRegistry
        );

        $this->configService = $configService;
        $this->orderRepository = $orderRepository;
        $this->orderDeliveryRepository = $orderDeliveryRepository;
    }


    public function transition(Transition $transition, Context $context): StateMachineStateCollection
    {
        if ($this->configService->isStateWatchingEnabled()) {
            if ($this->configService->getStateForShip() && $transition->getEntityName() === OrderDeliveryDefinition::ENTITY_NAME) {
                /** @var OrderDeliveryEntity $orderDelivery */
                $orderDelivery = $this->orderDeliveryRepository->search(new Criteria([$transition->getEntityId()]), $context)->first();
                $order = $this->getOrder($orderDelivery->getOrderId(), $context);

                if ($order && !$this->orderHasBillieInvoiceNumber($order)) {
                    // ToDo: Own exception
                    throw new IllegalTransitionException('1', '2', [3]);
                }
            }
        }

        return parent::transition($transition, $context);
    }

    protected function orderHasBillieInvoiceNumber(OrderEntity $order): bool
    {
        /** @var OrderDataEntity $billieData */
        $billieData = $order->getExtension(OrderExtension::EXTENSION_NAME);

        $invoiceNumber = $billieData->getExternalInvoiceNumber();
        if (!$invoiceNumber) {
            foreach ($order->getDocuments() as $document) {
                if ($document->getDocumentType()->getTechnicalName() === 'invoice') {
                    $config = $document->getConfig();
                    $invoiceNumber = $config['custom']['invoiceNumber'] ?? null;
                    break;
                }
            }
        }

        return (bool) $invoiceNumber;
    }

    protected function getOrder(string $orderId, Context $context): OrderEntity
    {
        $criteria = CriteriaHelper::getCriteriaForOrder($orderId);
        $criteria->addAssociation('documents.documentType');

        return $this->orderRepository->search($criteria, $context)->first();
    }
}
