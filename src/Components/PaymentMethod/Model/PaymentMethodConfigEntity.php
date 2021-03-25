<?php declare(strict_types=1);

namespace Billie\BilliePayment\Components\PaymentMethod\Model;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class PaymentMethodConfigEntity extends Entity
{
    public const FIELD_ID = 'id';

    public const FIELD_DURATION = 'duration';

    use EntityIdTrait;

    /**
     * @var integer
     */
    protected $duration;

    /**
     * @return int
     */
    public function getDuration(): int
    {
        return $this->duration;
    }

}
