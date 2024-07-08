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
use \Shopware\Core\Checkout\Cart\LineItem\LineItem as CartLineItem;

class WidgetDataLineItemBuilt
{
    public function __construct(
        private LineItem $billieLineItem,
        private readonly CartLineItem|OrderLineItemEntity $shopwareLineItem,
        private readonly Context $context,
        private readonly ?ProductEntity $product
    ) {
    }

    public function getBillieLineItem(): LineItem
    {
        return $this->billieLineItem;
    }

    public function setBillieLineItem(LineItem $billieLineItem): void
    {
        $this->billieLineItem = $billieLineItem;
    }

    public function getShopwareLineItem(): CartLineItem|OrderLineItemEntity
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
