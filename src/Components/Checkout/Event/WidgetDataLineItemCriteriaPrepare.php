<?php declare(strict_types=1);


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
     * @param Criteria $criteria
     * @param LineItem|OrderLineItemEntity $lineItem
     * @param Context $context
     */
    public function __construct(Criteria $criteria, $lineItem, Context $context)
    {
        $this->criteria = $criteria;
        $this->lineItem = $lineItem;
        $this->context = $context;
    }

    /**
     * @return Criteria
     */
    public function getCriteria(): Criteria
    {
        return $this->criteria;
    }

    /**
     * @param Criteria $criteria
     */
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

    /**
     * @return Context
     */
    public function getContext(): Context
    {
        return $this->context;
    }

}
