<?php


namespace Billie\BilliePayment\Components\PaymentMethod\Event;


use Billie\Sdk\Model\Request\CheckoutSessionConfirmRequestModel;
use Shopware\Core\Checkout\Order\OrderEntity;

class ConfirmModelBuilt
{

    /**
     * @var CheckoutSessionConfirmRequestModel
     */
    private $model;
    /**
     * @var OrderEntity
     */
    private $order;

    public function __construct(CheckoutSessionConfirmRequestModel $model, OrderEntity $order)
    {
        $this->model = $model;
        $this->order = $order;
    }

    /**
     * @return CheckoutSessionConfirmRequestModel
     */
    public function getModel(): CheckoutSessionConfirmRequestModel
    {
        return $this->model;
    }

    /**
     * @param CheckoutSessionConfirmRequestModel $model
     */
    public function setModel(CheckoutSessionConfirmRequestModel $model): void
    {
        $this->model = $model;
    }

    /**
     * @return OrderEntity
     */
    public function getOrder(): OrderEntity
    {
        return $this->order;
    }

}
