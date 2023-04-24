<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\PaymentMethod\Model\Extension;

use Billie\BilliePayment\Components\PaymentMethod\Model\Definition\PaymentMethodConfigDefinition;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\RestrictDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class PaymentMethodExtension extends EntityExtension
{
    /**
     * @var string
     */
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
