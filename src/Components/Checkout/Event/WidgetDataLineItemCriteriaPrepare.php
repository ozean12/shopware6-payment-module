<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\Checkout\Event;

use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class WidgetDataLineItemCriteriaPrepare
{
    /**
     * @var Criteria
     */
    private $criteria;

    /**
     * @var LineItem|OrderLineItemEntity
     */
    private $lineItem;

    /**
     * @var Context
     */
    private $context;

    /**
     * WidgetDataLineItemCriteriaPrepare constructor.
     *
     * @param LineItem|OrderLineItemEntity $lineItem
     */
    public function __construct(Criteria $criteria, $lineItem, Context $context)
    {
        $this->criteria = $criteria;
        $this->lineItem = $lineItem;
        $this->context = $context;
    }

    public function getCriteria(): Criteria
    {
        return $this->criteria;
    }

    public function setCriteria(Criteria $criteria): void
    {
        $this->criteria = $criteria;
    }

    /**
     * @return LineItem|OrderLineItemEntity
     */
    public function getLineItem()
    {
        return $this->lineItem;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
