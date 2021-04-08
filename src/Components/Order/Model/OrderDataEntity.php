<?php declare(strict_types=1);

namespace Billie\BilliePayment\Components\Order\Model;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class OrderDataEntity extends Entity
{
    public const FIELD_ID = 'id';
    public const FIELD_ORDER_ID = 'orderId';
    public const FIELD_ORDER_VERSION_ID = 'orderVersionId';
    public const FIELD_REFERENCE_ID = 'referenceId';
    public const FIELD_IS_SUCCESSFUL = 'successful';
    public const FIELD_EXTERNAL_INVOICE_NUMBER = 'externalInvoiceNumber';
    public const FIELD_EXTERNAL_INVOICE_URL = 'externalInvoiceUrl';
    public const FIELD_EXTERNAL_DELIVERY_NOTE_URL = 'externalDeliveryNoteUrl';

    use EntityIdTrait;

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

    /**
     * @return string
     */
    public function getOrderId(): string
    {
        return $this->orderId;
    }

    /**
     * @return string
     */
    public function getOrderVersionId(): string
    {
        return $this->orderVersionId;
    }

    /**
     * @return OrderEntity
     */
    public function getOrder(): OrderEntity
    {
        return $this->order;
    }

    /**
     * @return string
     */
    public function getReferenceId(): string
    {
        return $this->referenceId;
    }

    /**
     * @return string|null
     */
    public function getExternalInvoiceNumber(): ?string
    {
        return $this->externalInvoiceNumber;
    }

    /**
     * @return string|null
     */
    public function getExternalInvoiceUrl(): ?string
    {
        return $this->externalInvoiceUrl;
    }

    /**
     * @return string|null
     */
    public function getExternalDeliveryNoteUrl(): ?string
    {
        return $this->externalDeliveryNoteUrl;
    }

    /**
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->successful;
    }

}
