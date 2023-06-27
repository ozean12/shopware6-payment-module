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
use Billie\Sdk\Util\AddressHelper as SdkHelper;
use InvalidArgumentException;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;

class AddressHelper
{
    /**
     * @param CustomerEntity|OrderCustomerEntity $customer
     */
    public static function getCustomerNumber($customer): string
    {
        $merchantNumber = $customer->getCustomerNumber();
        if ($merchantNumber === null || $merchantNumber === '') {
            return $customer instanceof OrderCustomerEntity ? $customer->getCustomerId() : $customer->getId();
        }

        return $merchantNumber;
    }

    /**
     * @param OrderAddressEntity|CustomerAddressEntity $addressEntity
     */
    public static function createAddress(object $addressEntity): Address
    {
        self::validateParam($addressEntity);

        return (new Address())
            ->setValidateOnSet(false)
            ->setStreet(SdkHelper::getStreetName($addressEntity->getStreet()))
            ->setHouseNumber(SdkHelper::getHouseNumber($addressEntity->getStreet()))
            ->setPostalCode($addressEntity->getZipcode())
            ->setCity($addressEntity->getCity())
            ->setCountryCode($addressEntity->getCountry()->getIso());
    }

    private static function validateParam(object $address = null): void
    {
        if (!$address instanceof OrderAddressEntity && !$address instanceof CustomerAddressEntity) {
            throw new InvalidArgumentException('the param `address` must be type of ' . OrderAddressEntity::class . ' or ' . CustomerAddressEntity::class . '. Given type: ' . ($address !== null ? get_class($address) : 'null'));
        }
    }
}
