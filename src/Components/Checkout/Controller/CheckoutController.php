<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\Checkout\Controller;

use Billie\Sdk\Model\Address;
use Billie\Sdk\Model\Person;
use Billie\Sdk\Util\ArrayHelper;
use ReflectionClass;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressCollection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountService;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\NoContentResponse;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/billie-payment", defaults={"_routeScope"={"storefront"}})
 */
class CheckoutController extends StorefrontController
{
    /**
     * @var EntityRepository<CustomerAddressCollection>
     * the interface has been deprecated, but shopware is using the Interface in a decorator for the repository.
     * so it will crash, if we are only using EntityRepository, cause an object of the decorator got injected into the constructor.
     * After Shopware has removed the decorator, we can replace this by a normal definition
     * TODO remove comment on Shopware Version 6.5.0.0 & readd type hint & change constructor argument type
     */
    private object $customerAddressRepository;

    /**
     * @var EntityRepository<OrderAddressCollection>
     * the interface has been deprecated, but shopware is using the Interface in a decorator for the repository.
     * so it will crash, if we are only using EntityRepository, cause an object of the decorator got injected into the constructor.
     * After Shopware has removed the decorator, we can replace this by a normal definition
     * TODO remove comment on Shopware Version 6.5.0.0 & readd type hint & change constructor argument type
     */
    private object $orderAddressRepository;

    /**
     * @var EntityRepository<OrderCollection>
     * the interface has been deprecated, but shopware is using the Interface in a decorator for the repository.
     * so it will crash, if we are only using EntityRepository, cause an object of the decorator got injected into the constructor.
     * After Shopware has removed the decorator, we can replace this by a normal definition
     * TODO remove comment on Shopware Version 6.5.0.0 & readd type hint & change constructor argument type
     */
    private object $orderRepository;

    /**
     * @var EntityRepository<OrderDeliveryCollection>
     * the interface has been deprecated, but shopware is using the Interface in a decorator for the repository.
     * so it will crash, if we are only using EntityRepository, cause an object of the decorator got injected into the constructor.
     * After Shopware has removed the decorator, we can replace this by a normal definition
     * TODO remove comment on Shopware Version 6.5.0.0 & readd type hint & change constructor argument type
     */
    private object $orderDeliveryRepository;

    private AccountService $accountService;

    public function __construct(
        object $addressRepository,
        object $orderRepository,
        object $orderAddressRepository,
        object $orderDeliveryRepository,
        AccountService $accountService
    ) {
        $this->customerAddressRepository = $addressRepository;
        $this->orderAddressRepository = $orderAddressRepository;
        $this->orderRepository = $orderRepository;
        $this->orderDeliveryRepository = $orderDeliveryRepository;
        $this->accountService = $accountService;
    }

    /**
     * @Route(path="/update-addresses/{orderId}", name="billie-payment.checkout.update-addresses", methods={"POST"}, defaults={"XmlHttpRequest"=true})
     * @noinspection NullPointerExceptionInspection
     *
     * @return NoContentResponse|NotFoundHttpException
     */
    public function updateCustomerAddress(Request $request, SalesChannelContext $salesChannelContext, string $orderId = null)
    {
        // ###############################################################################################################
        // ## PLEASE NOTE ################################################################################################
        // ## This action will update the address according the widget response.
        // ## Currently the billie payment methods are not available if the payment has been failed,
        // ## cause the customer can not change his address after the order has been placed. This is a concept of Shopware.
        // ## but this action has been already implemented the function to replace the addresses on a *placed* order.
        // ## currently the implementation (for the placed order) is not used, but has been fully tested.
        // ###############################################################################################################

        if ($orderId === null) {
            $this->updateAddress(
                $salesChannelContext->getCustomer()->getActiveBillingAddress()->getId(),
                $salesChannelContext->getCustomer()->getActiveShippingAddress()->getId(),
                $request->request->all(),
                $this->customerAddressRepository,
                $salesChannelContext->getCustomer(),
                $salesChannelContext
            );
        } else {
            $criteria = (new Criteria([$orderId]))
                ->addAssociation('deliveries');

            /** @var OrderEntity|null $orderEntity */
            $orderEntity = $this->orderRepository->search($criteria, $salesChannelContext->getContext())->first();
            if (!$orderEntity instanceof OrderEntity) {
                return $this->createNotFoundException('order with id ' . $orderId . ' was not found');
            }

            $this->updateAddress(
                $orderEntity->getBillingAddressId(),
                $orderEntity->getDeliveries()->first()->getShippingOrderAddressId(),
                $request->request->all(),
                $this->orderAddressRepository,
                $orderEntity,
                $salesChannelContext
            );
        }

        return new NoContentResponse();
    }

    /**
     * the interface has been deprecated, but shopware is using the Interface in a decorator for the repository.
     * so it will crash, if we are only using EntityRepository, cause an object of the decorator got injected into the constructor.
     * After Shopware has removed the decorator, we can replace this by a normal definition
     * TODO remove comment on Shopware Version 6.5.0.0 & readd type hint & change constructor argument type
     * @param EntityRepository<OrderAddressCollection>|EntityRepository<CustomerAddressCollection> $addressRepository
     */
    protected function updateAddress(
        string $shopwareBillingAddressId,
        string $shopwareShippingAddressId,
        array $requestParams,
        object $addressRepository,
        Entity $referencedEntity,
        SalesChannelContext $salesChannelContext
    ): void {
        $context = $salesChannelContext->getContext();

        $billieDebtorCompanyName = $requestParams['debtor_company']['name'];
        $billieBillingAddress = new Address(ArrayHelper::removePrefixFromKeys($requestParams['debtor_company'], 'address_'), true);
        $billieDebtorPerson = new Person($requestParams['debtor_person']);
        $billieShippingAddress = new Address($requestParams['delivery_address']);

        // update billing address
        $addressRepository->update([[
            'id' => $shopwareBillingAddressId,
            'company' => $billieDebtorCompanyName,
            'firstName' => $billieDebtorPerson->getFirstname(),
            'lastName' => $billieDebtorPerson->getLastname(),
            'street' => $billieBillingAddress->getStreet() . ' ' . $billieBillingAddress->getHouseNumber(),
            'zipcode' => $billieBillingAddress->getPostalCode(),
            'city' => $billieBillingAddress->getCity(),
        ]], $context);

        if (!$this->compareArrays($billieBillingAddress->toArray(), $billieShippingAddress->toArray())) {
            $isNewAddress = $shopwareBillingAddressId === $shopwareShippingAddressId;
            $shippingAddressData = [];
            if ($isNewAddress) {
                /** @var CustomerAddressEntity|OrderAddressEntity $billingAddressEntity */
                $billingAddressEntity = $addressRepository->search(new Criteria([$shopwareBillingAddressId]), $context)->first();

                // copy current address data and remove specific values
                $shippingAddressData = array_diff_key($billingAddressEntity->jsonSerialize(), array_flip([
                    '_uniqueIdentifier', 'createdAt', 'updatedAt', 'order', 'customer', 'extensions', 'versionId', 'id', 'country', 'countryState', 'salutation', 'orderDeliveries',
                ]));
                $shippingAddressData['id'] = Uuid::randomHex();
            } else {
                $shippingAddressData['id'] = $shopwareShippingAddressId;
            }

            $shippingAddressData = array_merge($shippingAddressData, [
                'street' => $billieShippingAddress->getStreet() . ' ' . $billieShippingAddress->getHouseNumber(),
                'zipcode' => $billieShippingAddress->getPostalCode(),
                'city' => $billieShippingAddress->getCity(),
            ]);
            $addressRepository->upsert([$shippingAddressData], $context);

            if ($isNewAddress) {
                if ($referencedEntity instanceof CustomerEntity) {
                    $refAccountService = new ReflectionClass($this->accountService);
                    $arguments = [$shippingAddressData['id'], $salesChannelContext];

                    if ($refAccountService->getMethod('setDefaultShippingAddress')->getNumberOfParameters() === 3) {
                        $arguments[] = $referencedEntity;
                    }

                    /** @phpstan-ignore-next-line */
                    $this->accountService->setDefaultShippingAddress(...$arguments);
                } elseif ($referencedEntity instanceof OrderEntity) {
                    /** @var OrderDeliveryEntity $delivery */
                    $delivery = $referencedEntity->getDeliveries()->first();
                    $this->orderDeliveryRepository->upsert([[
                        'id' => $delivery->getId(),
                        'shippingOrderAddressId' => $shippingAddressData['id'],
                    ]], $context);
                }
            }
        }
    }

    private function compareArrays(array $array1, array $array2): bool
    {
        if (count($array1) !== count($array2)) {
            return false;
        }

        $keys = array_unique([...array_keys($array1), ...array_keys($array2)]);

        foreach ($keys as $key) {
            if (($array1[$key] ?? null) !== ($array2[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }
}
