<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Util;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class CriteriaHelper
{
    public static function getCriteriaForOrder(string $orderId): Criteria
    {
        $criteria = (new Criteria([$orderId]))
            ->addAssociation('addresses.country')
            ->addAssociation('addresses.salutation')
            ->addAssociation('deliveries')
            ->addAssociation('lineItems')
            ->addAssociation('transactions.paymentMethod')
            ->addAssociation('transactions.stateMachineState');

        // sort by latest transactions to get the current transaction
        $criteria->getAssociation('transactions')->addSorting(new FieldSorting('createdAt'));

        return $criteria;
    }
}
