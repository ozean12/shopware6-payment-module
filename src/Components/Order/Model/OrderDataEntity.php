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

    /**
     * @var string
     */
    public const FIELD_ID = 'id';

    /**
     * @var string
     */
    public const FIELD_ORDER_ID = 'orderId';

    /**
     * @var string
     */
    public const FIELD_ORDER_VERSION_ID = 'orderVersionId';

    /**
     * @var string
     */
    public const FIELD_ORDER_STATE = 'orderState';

    /**
     * @var string
     */
    public const FIELD_REFERENCE_ID = 'referenceId';

    /**
     * @var string
     */
    public const FIELD_IS_SUCCESSFUL = 'successful';

    /**
     * @var string
     */
    public const FIELD_EXTERNAL_INVOICE_NUMBER = 'externalInvoiceNumber';

    /**
     * @var string
     */
    public const FIELD_EXTERNAL_INVOICE_URL = 'externalInvoiceUrl';

    /**
     * @var string
     */
    public const FIELD_EXTERNAL_DELIVERY_NOTE_URL = 'externalDeliveryNoteUrl';

    /**
     * @var string
     */
    public const FIELD_BANK_IBAN = 'bankIban';

    /**
     * @var string
     */
    public const FIELD_BANK_BIC = 'bankBic';

    /**
     * @var string
     */
    public const FIELD_BANK_NAME = 'bankName';

    /**
     * @var string
     */
    public const FIELD_DURATION = 'duration';

    /**
     * @var string
     */
    public const FIELD_INVOICE_UUID = 'invoiceUuid';

    protected string $orderId;

    protected string $orderVersionId;

    protected ?OrderEntity $order = null;

    protected string $orderState;

    protected string $referenceId;

    protected ?string $invoiceUuid = null;

    protected ?string $externalInvoiceNumber = null;

    protected ?string $externalInvoiceUrl = null;

    protected ?string $externalDeliveryNoteUrl = null;

    protected ?string $bankIban = null;

    protected ?string $bankBic = null;

    protected ?string $bankName = null;

    protected ?int $duration = null;

    protected bool $successful;

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getOrderVersionId(): string
    {
        return $this->orderVersionId;
    }

    public function getOrder(): ?OrderEntity
    {
        return $this->order;
    }

    public function getOrderState(): string
    {
        return $this->orderState;
    }

    public function getReferenceId(): string
    {
        return $this->referenceId;
    }

    public function getInvoiceUuid(): ?string
    {
        return $this->invoiceUuid;
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

    public function getBankIban(): ?string
    {
        return $this->bankIban;
    }

    public function getBankBic(): ?string
    {
        return $this->bankBic;
    }

    public function getBankName(): ?string
    {
        return $this->bankName;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }
}
