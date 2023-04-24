<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\BillieApi\Util;

use Billie\Sdk\Model\Address;
use Billie\Sdk\Model\DebtorCompany;
use Billie\Sdk\Util\AddressHelper as SdkHelper;
use InvalidArgumentException;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;

class AddressHelper
{
    /**
     * @param OrderAddressEntity|CustomerAddressEntity $addressEntity
     */
    public static function createDebtorCompany($addressEntity): DebtorCompany
    {
        self::validateParam($addressEntity);

        return (new DebtorCompany())
            ->setValidateOnSet(false)
            ->setAddress(self::createAddress($addressEntity))
            ->setName($addressEntity->getCompany());
    }

    /**
     * @param OrderAddressEntity|CustomerAddressEntity $addressEntity
     */
    public static function createAddress($addressEntity): Address
    {
        self::validateParam($addressEntity);

        $addressModel = (new Address())
            ->setValidateOnSet(false)
            ->setStreet(SdkHelper::getStreetName($addressEntity->getStreet()))
            ->setHouseNumber(SdkHelper::getHouseNumber($addressEntity->getStreet()))
            ->setPostalCode($addressEntity->getZipcode())
            ->setCity($addressEntity->getCity())
            ->setCountryCode($addressEntity->getCountry()->getIso());

        if ($addressEntity->getAdditionalAddressLine1() &&
            !empty($addition1 = trim($addressEntity->getAdditionalAddressLine1()))
        ) {
            $addressModel->setAddition($addition1);
        }

        if ($addressEntity->getAdditionalAddressLine2() &&
            !empty($addition2 = trim($addressEntity->getAdditionalAddressLine2()))
        ) {
            $addressModel->setAddition(
                (!empty($addition1) ? $addition1 . ', ' : null) . $addition2
            );
        }

        return $addressModel;
    }

    /**
     * @param OrderAddressEntity|CustomerAddressEntity $address
     */
    private static function validateParam($address): void
    {
        if (!$address instanceof OrderAddressEntity && !$address instanceof CustomerAddressEntity) {
            throw new InvalidArgumentException('the param `address` must be type of ' . OrderAddressEntity::class . ' or ' . CustomerAddressEntity::class . '. Given type: ' . get_class($address));
        }
    }
}
