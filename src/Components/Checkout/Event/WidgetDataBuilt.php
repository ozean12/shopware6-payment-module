<?php declare(strict_types=1);


namespace Billie\BilliePayment\Components\Checkout\Event;


use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class WidgetDataBuilt
{

    /**
     * @var ArrayStruct
     */
    private $widgetData;

    /**
     * @var PaymentMethodEntity
     */
    private $paymentMethodEntity;

    /**
     * @var CustomerEntity|OrderCustomerEntity
     */
    private $customer;

    /**
     * @var CustomerAddressEntity|OrderAddressEntity
     */
    private $billingAddress;

    /**
     * @var CustomerAddressEntity|OrderAddressEntity
     */
    private $shippingAddress;

    /**
     * @var CartPrice
     */
    private $price;

    /**
     * @var LineItemCollection|OrderLineItemCollection
     */
    private $lineItems;

    /**
     * @var Context
     */
    private $salesChannelContext;

    /**
     * @param ArrayStruct $widgetData
     * @param CustomerEntity|OrderCustomerEntity $customer
     * @param CustomerAddressEntity|OrderAddressEntity $billingAddress
     * @param CustomerAddressEntity|OrderAddressEntity $shippingAddress
     * @param CartPrice $price
     * @param LineItemCollection|OrderLineItemCollection $lineItems
     * @param SalesChannelContext $salesChannelContext
     */
    public function __construct(
        ArrayStruct $widgetData,
        $customer,
        $billingAddress,
        $shippingAddress,
        CartPrice $price,
        $lineItems,
        SalesChannelContext $salesChannelContext
    )
    {
        $this->widgetData = $widgetData;
        $this->customer = $customer;
        $this->billingAddress = $billingAddress;
        $this->shippingAddress = $shippingAddress;
        $this->price = $price;
        $this->lineItems = $lineItems;
        $this->salesChannelContext = $salesChannelContext;
    }

    /**
     * @return ArrayStruct
     */
    public function getWidgetData(): ArrayStruct
    {
        return $this->widgetData;
    }

    /**
     * @param ArrayStruct $widgetData
     */
    public function setWidgetData(ArrayStruct $widgetData): void
    {
        $this->widgetData = $widgetData;
    }

    /**
     * @return PaymentMethodEntity
     */
    public function getPaymentMethodEntity(): PaymentMethodEntity
    {
        return $this->paymentMethodEntity;
    }

    /**
     * @return CustomerEntity|OrderCustomerEntity
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @return CustomerAddressEntity|OrderAddressEntity
     */
    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

    /**
     * @return CustomerAddressEntity|OrderAddressEntity
     */
    public function getShippingAddress()
    {
        return $this->shippingAddress;
    }

    /**
     * @return CartPrice
     */
    public function getPrice(): CartPrice
    {
        return $this->price;
    }

    /**
     * @return LineItemCollection|OrderLineItemCollection
     */
    public function getLineItems()
    {
        return $this->lineItems;
    }

    /**
     * @return Context
     */
    public function getSalesChannelContext(): Context
    {
        return $this->salesChannelContext;
    }
}
