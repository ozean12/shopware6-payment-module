<?php


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
     * @param LineItem $billieLineItem
     * @param \Shopware\Core\Checkout\Cart\LineItem\LineItem|OrderLineItemEntity $shopwareLineItem
     * @param Context $context
     * @param ProductEntity|null $product
     */
    public function __construct(LineItem $billieLineItem, $shopwareLineItem, Context $context, ?ProductEntity $product)
    {
        $this->billieLineItem = $billieLineItem;
        $this->shopwareLineItem = $shopwareLineItem;
        $this->context = $context;
        $this->product = $product;
    }

    /**
     * @return LineItem
     */
    public function getBillieLineItem(): LineItem
    {
        return $this->billieLineItem;
    }

    /**
     * @param LineItem $billieLineItem
     */
    public function setBillieLineItem(LineItem $billieLineItem): void
    {
        $this->billieLineItem = $billieLineItem;
    }

    /**
     * @return  \Shopware\Core\Checkout\Cart\LineItem\LineItem|OrderLineItemEntity
     */
    public function getShopwareLineItem()
    {
        return $this->shopwareLineItem;
    }

    /**
     * @return Context
     */
    public function getContext(): Context
    {
        return $this->context;
    }

    /**
     * @return ProductEntity|null
     */
    public function getProduct(): ?ProductEntity
    {
        return $this->product;
    }

}
