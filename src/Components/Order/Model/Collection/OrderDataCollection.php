<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\Order\Model\Collection;

use Billie\BilliePayment\Components\Order\Model\OrderDataEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<OrderDataEntity>
 */
class OrderDataCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return OrderDataEntity::class;
    }
}
