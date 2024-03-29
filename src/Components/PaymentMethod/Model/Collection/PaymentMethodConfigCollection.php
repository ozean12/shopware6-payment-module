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
 * @extends EntityCollection<PaymentMethodConfigEntity>
 */
class PaymentMethodConfigCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PaymentMethodConfigEntity::class;
    }
}
