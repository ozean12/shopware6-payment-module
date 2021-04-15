<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\PaymentMethod\Model\Definition;

use Billie\BilliePayment\Components\PaymentMethod\Model\Collection\PaymentMethodConfigCollection;
use Billie\BilliePayment\Components\PaymentMethod\Model\PaymentMethodConfigEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class PaymentMethodConfigDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'billie_payment_config';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return PaymentMethodConfigEntity::class;
    }

    public function getCollectionClass(): string
    {
        return PaymentMethodConfigCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField(
                'payment_method_id',
                PaymentMethodConfigEntity::FIELD_ID
            ))->addFlags(new Required(), new PrimaryKey()),

            (new IntField(
                'duration',
                PaymentMethodConfigEntity::FIELD_DURATION
            ))->addFlags(new Required()),
        ]);
    }

    protected function defaultFields(): array
    {
        return [];
    }
}
