<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\PaymentMethod\Model\Collection;

use Billie\BilliePayment\Components\PaymentMethod\Model\PaymentMethodConfigEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                           add(PaymentMethodConfigEntity $entity)
 * @method void                           set(string $key, PaymentMethodConfigEntity $entity)
 * @method PaymentMethodConfigEntity[]    getIterator()
 * @method PaymentMethodConfigEntity[]    getElements()
 * @method PaymentMethodConfigEntity|null get(string $key)
 * @method PaymentMethodConfigEntity|null first()
 * @method PaymentMethodConfigEntity|null last()
 */
class PaymentMethodConfigCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PaymentMethodConfigEntity::class;
    }
}
