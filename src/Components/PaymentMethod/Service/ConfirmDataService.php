<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\PaymentMethod\Service;

use Billie\BilliePayment\Components\BillieApi\Util\AddressHelper;
use Billie\BilliePayment\Components\PaymentMethod\Event\ConfirmModelBuilt;
use Billie\BilliePayment\Components\PaymentMethod\Model\Extension\PaymentMethodExtension;
use Billie\BilliePayment\Components\PaymentMethod\Model\PaymentMethodConfigEntity;
use Billie\BilliePayment\Util\CriteriaHelper;
use Billie\Sdk\Model\Amount;
use Billie\Sdk\Model\Request\CheckoutSession\CheckoutSessionConfirmRequestModel;
use Billie\Sdk\Model\Request\CheckoutSession\Confirm\Debtor;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ConfirmDataService
{
    /**
     * @param EntityRepository<OrderCollection> $orderRepository
     */
    public function __construct(
        private readonly EntityRepository $orderRepository,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function getConfirmModel(string $sessionUuid, OrderEntity $orderEntity): CheckoutSessionConfirmRequestModel
    {
        $criteria = CriteriaHelper::getCriteriaForOrder($orderEntity->getId());
        /** @var OrderEntity $orderEntity */
        $orderEntity = $this->orderRepository->search($criteria, Context::createDefaultContext())->first();

        /** @var PaymentMethodConfigEntity $paymentConfig */
        $paymentConfig = $orderEntity->getTransactions()->first()->getPaymentMethod()->getExtension(PaymentMethodExtension::EXTENSION_NAME);

        $billingAddressId = $orderEntity->getBillingAddressId();
        $billingAddress = $orderEntity->getAddresses()->get($billingAddressId);
        $shippingAddressId = $orderEntity->getDeliveries()->first()->getShippingOrderAddressId();

        $model = (new CheckoutSessionConfirmRequestModel())
            ->setSessionUuid($sessionUuid)
            ->setExternalCode($orderEntity->getOrderNumber())
            ->setDebtor(
                (new Debtor())
                    ->setName($billingAddress->getCompany())
                    ->setAddress(AddressHelper::createAddress($billingAddress))
            )
            ->setDeliveryAddress(AddressHelper::createAddress($orderEntity->getAddresses()->get($shippingAddressId)))
            ->setDuration($paymentConfig->getDuration())
            ->setAmount(
                (new Amount())
                    ->setGross($orderEntity->getAmountTotal())
                    ->setNet($orderEntity->getAmountNet())
                    ->setTax($orderEntity->getPrice()->getCalculatedTaxes()->getAmount())
            );

        /** @var ConfirmModelBuilt $event */
        $event = $this->eventDispatcher->dispatch(new ConfirmModelBuilt($model, $orderEntity));

        return $event->getModel();
    }
}
