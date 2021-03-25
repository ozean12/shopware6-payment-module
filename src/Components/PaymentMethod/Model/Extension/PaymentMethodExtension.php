<?php declare(strict_types=1);

namespace Billie\BilliePayment\Components\PaymentMethod\Model\Extension;

use Billie\BilliePayment\Components\PaymentMethod\Model\Definition\PaymentMethodConfigDefinition;
use Billie\BilliePayment\Components\PaymentMethod\Model\PaymentMethodConfigEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\RestrictDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class PaymentMethodExtension extends EntityExtension
{
    public const EXTENSION_NAME = 'billieConfig';

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToOneAssociationField(
                self::EXTENSION_NAME,
                'id',
                'payment_method_id',
                PaymentMethodConfigDefinition::class,
                true
            ))->addFlags(new RestrictDelete())
        );
    }

    public function getDefinitionClass(): string
    {
        return PaymentMethodDefinition::class;
    }
}
