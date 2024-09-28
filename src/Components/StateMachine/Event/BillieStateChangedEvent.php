<?php

declare(strict_types=1);
/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\StateMachine\Event;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

class BillieStateChangedEvent
{
    public function __construct(
        private readonly OrderEntity $order,
        private readonly OrderTransactionEntity $orderTransaction,
        private readonly string $billieState,
        private readonly Context $context,
    ) {
    }

    public function getOrder(): OrderEntity
    {
        return $this->order;
    }

    public function getOrderTransaction(): OrderTransactionEntity
    {
        return $this->orderTransaction;
    }

    public function getBillieState(): string
    {
        return $this->billieState;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
