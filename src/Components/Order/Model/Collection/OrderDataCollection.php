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
 * @method void                 add(OrderDataEntity $entity)
 * @method void                 set(string $key, OrderDataEntity $entity)
 * @method OrderDataEntity[]    getIterator()
 * @method OrderDataEntity[]    getElements()
 * @method OrderDataEntity|null get(string $key)
 * @method OrderDataEntity|null first()
 * @method OrderDataEntity|null last()
 */
class OrderDataCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return OrderDataEntity::class;
    }
}
