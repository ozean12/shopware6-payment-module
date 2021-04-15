<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\Order\Model;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class OrderDataEntity extends Entity
{
    use EntityIdTrait;

    public const FIELD_ID = 'id';

    public const FIELD_ORDER_ID = 'orderId';

    public const FIELD_ORDER_VERSION_ID = 'orderVersionId';

    public const FIELD_REFERENCE_ID = 'referenceId';

    public const FIELD_IS_SUCCESSFUL = 'successful';

    public const FIELD_EXTERNAL_INVOICE_NUMBER = 'externalInvoiceNumber';

    public const FIELD_EXTERNAL_INVOICE_URL = 'externalInvoiceUrl';

    public const FIELD_EXTERNAL_DELIVERY_NOTE_URL = 'externalDeliveryNoteUrl';

    /**
     * @var string
     */
    protected $orderId;

    /**
     * @var string
     */
    protected $orderVersionId;

    /**
     * @var OrderEntity
     */
    protected $order;

    /**
     * @var string
     */
    protected $referenceId;

    /**
     * @var string|null
     */
    protected $externalInvoiceNumber;

    /**
     * @var string|null
     */
    protected $externalInvoiceUrl;

    /**
     * @var string|null
     */
    protected $externalDeliveryNoteUrl;

    /**
     * @var bool
     */
    protected $successful;

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getOrderVersionId(): string
    {
        return $this->orderVersionId;
    }

    public function getOrder(): OrderEntity
    {
        return $this->order;
    }

    public function getReferenceId(): string
    {
        return $this->referenceId;
    }

    public function getExternalInvoiceNumber(): ?string
    {
        return $this->externalInvoiceNumber;
    }

    public function getExternalInvoiceUrl(): ?string
    {
        return $this->externalInvoiceUrl;
    }

    public function getExternalDeliveryNoteUrl(): ?string
    {
        return $this->externalDeliveryNoteUrl;
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }
}
