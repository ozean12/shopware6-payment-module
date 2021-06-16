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
use Billie\Sdk\Model\DebtorCompany;
use Billie\Sdk\Model\Person;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\NoContentResponse;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/billie-payment")
 * @RouteScope(scopes={"storefront"})
 */
class CheckoutController extends StorefrontController
{
    /**
     * @var EntityRepositoryInterface
     */
    private $customerAddressRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderAddressRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderRepository;

    public function __construct(
        EntityRepositoryInterface $addressRepository,
        EntityRepositoryInterface $orderRepository,
        EntityRepositoryInterface $orderAddressRepository
    ) {
        $this->customerAddressRepository = $addressRepository;
        $this->orderAddressRepository = $orderAddressRepository;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @Route(path="/update-addresses/{orderId}", name="billie-payment.checkout.update-addresses", methods={"POST"}, defaults={"XmlHttpRequest"=true})
     * @noinspection NullPointerExceptionInspection
     *
     * @return \Shopware\Core\System\SalesChannel\NoContentResponse|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function updateCustomerAddress(Request $request, SalesChannelContext $salesChannelContext, string $orderId = null)
    {
        if ($orderId === null) {
            $this->updateAddress(
                $salesChannelContext->getCustomer()->getActiveBillingAddress()->getId(),
                $salesChannelContext->getCustomer()->getActiveShippingAddress()->getId(),
                $request->request->all(),
                $this->customerAddressRepository,
                $salesChannelContext->getContext()
            );
        } else {
            $criteria = (new Criteria([$orderId]))
                ->addAssociation('deliveries');

            /** @var OrderEntity|null $orderEntity */
            $orderEntity = $this->orderRepository->search($criteria, $salesChannelContext->getContext())->first();
            if ($orderEntity === null) {
                return $this->createNotFoundException('order with id ' . $orderId . ' was not found');
            }

            $this->updateAddress(
                $orderEntity->getBillingAddressId(),
                $orderEntity->getDeliveries()->first()->getShippingOrderAddressId(),
                $request->request->all(),
                $this->orderAddressRepository,
                $salesChannelContext->getContext()
            );
        }

        return new NoContentResponse();
    }

    protected function updateAddress(
        string $shopwareBillingAddressId,
        string $shopwareShippingAddressId,
        array $requestParams,
        EntityRepositoryInterface $repository,
        Context $context
    ): void {
        $billieDebtorCompany = new DebtorCompany($requestParams['debtor_company']);
        $billieDebtorPerson = new Person($requestParams['debtor_person']);
        $billieShippingAddress = new Address($requestParams['delivery_address']);

        // update billing address
        $repository->update([[
            'id' => $shopwareBillingAddressId,
            'company' => $billieDebtorCompany->getName(),
            'firstName' => $billieDebtorPerson->getFirstname(),
            'lastName' => $billieDebtorPerson->getLastname(),
            'street' => $billieDebtorCompany->getAddress()->getStreet() . ' ' . $billieDebtorCompany->getAddress()->getHouseNumber(),
            'zipcode' => $billieDebtorCompany->getAddress()->getPostalCode(),
            'city' => $billieDebtorCompany->getAddress()->getCity(),
        ]], $context);

        if ($shopwareBillingAddressId !== $shopwareShippingAddressId) {
            // update shipping address if the ids are not identical
            $repository->update([[
                'id' => $shopwareShippingAddressId,
                'street' => $billieShippingAddress->getStreet() . ' ' . $billieShippingAddress->getHouseNumber(),
                'zipcode' => $billieShippingAddress->getPostalCode(),
                'city' => $billieShippingAddress->getCity(),
            ]], $context);
        }
    }
}
