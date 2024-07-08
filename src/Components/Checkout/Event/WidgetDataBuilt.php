<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\Checkout\Event;

use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class WidgetDataBuilt
{
    public function __construct(
        private ArrayStruct $widgetData,
        private readonly CustomerEntity|OrderCustomerEntity $customer,
        private readonly CustomerAddressEntity|OrderAddressEntity $billingAddress,
        private readonly CustomerAddressEntity|OrderAddressEntity $shippingAddress,
        private readonly CartPrice $price,
        private readonly LineItemCollection|OrderLineItemCollection $lineItems,
        private readonly SalesChannelContext $salesChannelContext
    ) {
    }

    public function getWidgetData(): ArrayStruct
    {
        return $this->widgetData;
    }

    public function setWidgetData(ArrayStruct $widgetData): void
    {
        $this->widgetData = $widgetData;
    }

    public function getCustomer(): CustomerEntity|OrderCustomerEntity
    {
        return $this->customer;
    }

    public function getBillingAddress(): CustomerAddressEntity|OrderAddressEntity
    {
        return $this->billingAddress;
    }

    public function getShippingAddress(): CustomerAddressEntity|OrderAddressEntity
    {
        return $this->shippingAddress;
    }

    public function getPrice(): CartPrice
    {
        return $this->price;
    }

    public function getLineItems(): LineItemCollection|OrderLineItemCollection
    {
        return $this->lineItems;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }
}
