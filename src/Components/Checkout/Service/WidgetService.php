<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\Checkout\Service;

use Billie\BilliePayment\Components\BillieApi\Util\AddressHelper;
use Billie\BilliePayment\Components\Checkout\Event\WidgetDataBuilt;
use Billie\BilliePayment\Components\Checkout\Event\WidgetDataLineItemBuilt;
use Billie\BilliePayment\Components\Checkout\Event\WidgetDataLineItemCriteriaPrepare;
use Billie\BilliePayment\Components\PaymentMethod\Model\Extension\PaymentMethodExtension;
use Billie\BilliePayment\Components\PaymentMethod\Model\PaymentMethodConfigEntity;
use Billie\BilliePayment\Components\PluginConfig\Service\ConfigService;
use Billie\BilliePayment\Util\CriteriaHelper;
use Billie\Sdk\Exception\BillieException;
use Billie\Sdk\Model\Amount;
use Billie\Sdk\Model\LineItem;
use Billie\Sdk\Model\Person;
use Billie\Sdk\Model\Request\CreateSessionRequestModel;
use Billie\Sdk\Service\Request\CreateSessionRequest;
use Billie\Sdk\Util\WidgetHelper;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class WidgetService
{
    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var EntityRepositoryInterface
     */
    private $productRepository;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var EntityRepositoryInterface
     */
    private $salutationRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(
        ContainerInterface        $container,
        EventDispatcherInterface  $eventDispatcher,
        EntityRepositoryInterface $productRepository,
        EntityRepositoryInterface $orderRepository,
        EntityRepositoryInterface $salutationRepository,
        CartService               $cartService,
        ConfigService             $configService
    )
    {
        $this->container = $container;
        $this->eventDispatcher = $eventDispatcher;
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
        $this->salutationRepository = $salutationRepository;
        $this->configService = $configService;
        $this->cartService = $cartService;
    }

    public function getWidgetDataByOrder(OrderEntity $orderEntity, SalesChannelContext $salesChannelContext): ArrayStruct
    {
        $criteria = CriteriaHelper::getCriteriaForOrder($orderEntity->getId());

        /** @var OrderEntity $orderEntity */
        $orderEntity = $this->orderRepository->search($criteria, $salesChannelContext->getContext())->first();

        $billingAddress = $orderEntity->getAddresses()->get($orderEntity->getBillingAddressId());
        $shippingAddress = $orderEntity->getAddresses()->get($orderEntity->getDeliveries()->first()->getShippingOrderAddressId());

        return $this->getBaseData(
            $orderEntity->getOrderCustomer(),
            $billingAddress,
            $shippingAddress,
            $orderEntity->getPrice(),
            $orderEntity->getLineItems(),
            $salesChannelContext
        );
    }

    /** @noinspection NullPointerExceptionInspection */
    public function getWidgetDataBySalesChannelContext(SalesChannelContext $salesChannelContext): ?ArrayStruct
    {
        $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);

        return $this->getBaseData(
            $salesChannelContext->getCustomer(),
            $salesChannelContext->getCustomer()->getActiveBillingAddress(),
            $salesChannelContext->getCustomer()->getActiveShippingAddress(),
            $cart->getPrice(),
            $cart->getLineItems(),
            $salesChannelContext
        );
    }

    /**
     * @param CustomerEntity|OrderCustomerEntity $customer
     * @param CustomerAddressEntity|OrderAddressEntity $billingAddress
     * @param CustomerAddressEntity|OrderAddressEntity $shippingAddress
     * @param LineItemCollection|OrderLineItemCollection $lineItems
     * @noinspection PhpDocMissingThrowsInspection
     */
    protected function getBaseData(
        $customer,
        $billingAddress,
        $shippingAddress,
        CartPrice $price,
        $lineItems,
        SalesChannelContext $salesChannelContext
    ): ?ArrayStruct
    {
        try {
            /** @noinspection NullPointerExceptionInspection */
            $checkoutSessionId = $this->container->get(CreateSessionRequest::class)
                ->execute((new CreateSessionRequestModel())
                    ->setMerchantCustomerId($customer->getCustomerNumber())
                )->getCheckoutSessionId();
        } catch (BillieException $e) {
            // TODO Log error
            return null;
        }

        /** @var PaymentMethodConfigEntity $billieConfig */
        $billieConfig = $salesChannelContext->getPaymentMethod()->get(PaymentMethodExtension::EXTENSION_NAME);

        $salutation = $this->salutationRepository->search(new Criteria([$billingAddress->getSalutationId()]), $salesChannelContext->getContext())->first();

        $widgetData = new ArrayStruct([
            'src' => WidgetHelper::getWidgetUrl($this->configService->isSandbox()),
            'checkoutSessionId' => $checkoutSessionId,
            'checkoutData' => [
                'amount' => (new Amount())
                    ->setGross($price->getTotalPrice())
                    ->setNet($price->getNetPrice())
                    ->setTax($price->getCalculatedTaxes()->getAmount())
                    ->toArray(),
                'duration' => $billieConfig->getDuration(),
                'debtor_company' => AddressHelper::createDebtorCompany($billingAddress)->toArray(),
                'delivery_address' => AddressHelper::createAddress($shippingAddress)->toArray(),
                'debtor_person' => (new Person())
                    ->setValidateOnSet(false)
                    ->setSalutation($this->configService->getSalutation($salutation))
                    ->setFirstname($customer->getFirstName())
                    ->setLastname($customer->getLastName())
                    ->setPhone($billingAddress->getPhoneNumber())
                    ->setMail($customer->getEmail())
                    ->toArray(),
                'line_items' => $this->getLineItems($lineItems, $salesChannelContext->getContext()),
            ],
        ]);

        /** @var WidgetDataBuilt $event */
        $event = $this->eventDispatcher->dispatch(new WidgetDataBuilt(
            $widgetData,
            $customer,
            $billingAddress,
            $shippingAddress,
            $price,
            $lineItems,
            $salesChannelContext
        ));

        return $event->getWidgetData();
    }

    /**
     * @param LineItemCollection|OrderLineItemCollection $collection
     * @noinspection PhpDocMissingThrowsInspection
     */
    protected function getLineItems($collection, Context $context): array
    {
        $lineItems = [];
        /** @var \Shopware\Core\Checkout\Cart\LineItem\LineItem|OrderLineItemEntity $lineItem */
        foreach ($collection->getIterator() as $lineItem) {
            if ($lineItem->getType() !== \Shopware\Core\Checkout\Cart\LineItem\LineItem::PRODUCT_LINE_ITEM_TYPE) {
                // item is not a product (it is a voucher etc.). Billie does only accepts real products
                continue;
            }
            $lineItems[] = $this->getLineItem($lineItem, $context)->toArray();
        }

        return $lineItems;
    }

    /**
     * @param \Shopware\Core\Checkout\Cart\LineItem\LineItem|OrderLineItemEntity $lineItem
     * @noinspection PhpDocMissingThrowsInspection
     */
    protected function getLineItem($lineItem, Context $context): LineItem
    {
        $amount = (new Amount())
            ->setGross($lineItem->getPrice()->getTotalPrice())
            ->setTax($lineItem->getPrice()->getCalculatedTaxes()->getAmount());
        $amount->setNet($amount->getGross() - $amount->getTax());

        $billieLineItem = (new LineItem())
            ->setExternalId($lineItem->getId())
            ->setTitle($lineItem->getLabel())
            ->setQuantity($lineItem->getQuantity())
            ->setAmount($amount);

        $productCriteria = (new Criteria([$lineItem->getReferencedId()])) // TODO product identifier?!
        ->addAssociation('manufacturer')
            ->addAssociation('categories')
            ->setLimit(1);

        /** @var WidgetDataLineItemCriteriaPrepare $event */
        $event = $this->eventDispatcher->dispatch(new WidgetDataLineItemCriteriaPrepare($productCriteria, $lineItem, $context));

        $productResults = $this->productRepository->search($event->getCriteria(), $context);
        if ($productResults->first()) {
            /** @var ProductEntity $product */
            $product = $productResults->first();
            $category = $product->getCategories()->first();
            $billieLineItem
                ->setExternalId($product->getProductNumber())
                ->setCategory($category ? $category->getName() : null)
                ->setBrand($product->getManufacturer() ? $product->getManufacturer()->getName() : null)
                ->setGtin($product->getEan());

            if ($product->getDescription()) {
                $billieLineItem->setDescription(substr($product->getDescription(), 0, 255));
            }
        }

        /** @var WidgetDataLineItemBuilt $event */
        $event = $this->eventDispatcher->dispatch(new WidgetDataLineItemBuilt($billieLineItem, $lineItem, $context, $product ?? null));

        return $event->getBillieLineItem();
    }
}
