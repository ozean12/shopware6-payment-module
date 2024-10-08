<?php

declare(strict_types=1);
/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\StateMachine\Subscriber;

use Billie\BilliePayment\Components\StateMachine\Event\BillieStateChangedEvent;
use Billie\Sdk\Model\Order;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\Exception\IllegalTransitionException;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BillieStateSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly StateMachineRegistry $stateMachine
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BillieStateChangedEvent::class => 'onStateChanged',
        ];
    }

    public function onStateChanged(BillieStateChangedEvent $event): void
    {
        $action = match ($event->getBillieState()) {
            Order::STATE_AUTHORIZED => StateMachineTransitionActions::ACTION_AUTHORIZE,
            Order::STATE_SHIPPED => StateMachineTransitionActions::ACTION_PAID,
            Order::STATE_CANCELLED => $event->getOrderTransaction()->getStateMachineState()->getTechnicalName() === OrderTransactionStates::STATE_AUTHORIZED ? StateMachineTransitionActions::ACTION_CANCEL : StateMachineTransitionActions::ACTION_REFUND,
            default => null,
        };

        if ($action === null) {
            return;
        }

        try {
            $availableTransitions = $this->stateMachine->getAvailableTransitions(
                OrderTransactionDefinition::ENTITY_NAME,
                $event->getOrderTransaction()->getId(),
                'stateId',
                $event->getContext()
            );

            foreach ($availableTransitions as $transition) {
                if ($transition->getActionName() === $action) {
                    $this->stateMachine->transition(new Transition(
                        OrderTransactionDefinition::ENTITY_NAME,
                        $event->getOrderTransaction()->getId(),
                        $transition->getActionName(),
                        'stateId'
                    ), $event->getContext());
                    break;
                }
            }
        } catch (IllegalTransitionException) {
            // ignore
        }
    }
}
