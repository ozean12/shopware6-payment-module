<?php


namespace Billie\BilliePayment\Components\Order\Model\Extension;


use Billie\BilliePayment\Components\Order\Model\Definition\OrderDataDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\RestrictDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class OrderExtension extends EntityExtension
{
    public const EXTENSION_NAME = 'billieData';

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToOneAssociationField(
                self::EXTENSION_NAME,
                'id',
                'order_id',
                OrderDataDefinition::class,
                true
            ))->addFlags(new RestrictDelete())
        );
    }

    public function getDefinitionClass(): string
    {
        return OrderDefinition::class;
    }
}
