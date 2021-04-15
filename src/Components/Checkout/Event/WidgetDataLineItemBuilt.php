<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\Checkout\Event;

use Billie\Sdk\Model\LineItem;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;

class WidgetDataLineItemBuilt
{
    /**
     * @var LineItem
     */
    private $billieLineItem;

    /**
     * @var \Shopware\Core\Checkout\Cart\LineItem\LineItem|OrderLineItemEntity
     */
    private $shopwareLineItem;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var ProductEntity|null
     */
    private $product;

    /**
     * WidgetDataLineItemBuilt constructor.
     *
     * @param \Shopware\Core\Checkout\Cart\LineItem\LineItem|OrderLineItemEntity $shopwareLineItem
     */
    public function __construct(LineItem $billieLineItem, $shopwareLineItem, Context $context, ?ProductEntity $product)
    {
        $this->billieLineItem = $billieLineItem;
        $this->shopwareLineItem = $shopwareLineItem;
        $this->context = $context;
        $this->product = $product;
    }

    public function getBillieLineItem(): LineItem
    {
        return $this->billieLineItem;
    }

    public function setBillieLineItem(LineItem $billieLineItem): void
    {
        $this->billieLineItem = $billieLineItem;
    }

    /**
     * @return \Shopware\Core\Checkout\Cart\LineItem\LineItem|OrderLineItemEntity
     */
    public function getShopwareLineItem()
    {
        return $this->shopwareLineItem;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getProduct(): ?ProductEntity
    {
        return $this->product;
    }
}
