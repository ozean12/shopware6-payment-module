<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

    public function getModel(): CheckoutSessionConfirmRequestModel
    {
        return $this->model;
    }

    public function setModel(CheckoutSessionConfirmRequestModel $model): void
    {
        $this->model = $model;
    }

    public function getOrder(): OrderEntity
    {
        return $this->order;
    }
}
