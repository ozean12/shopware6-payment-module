<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\Checkout\Service;

use Billie\BilliePayment\Components\PaymentMethod\Model\Extension\PaymentMethodExtension;
use Billie\BilliePayment\Components\PaymentMethod\Model\PaymentMethodConfigEntity;
use Billie\BilliePayment\Components\PaymentMethod\Util\MethodHelper;
use Billie\BilliePayment\Components\PluginConfig\Service\ConfigService;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Payment\SalesChannel\AbstractPaymentMethodRoute;
use Shopware\Core\Checkout\Payment\SalesChannel\PaymentMethodRouteResponse;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PaymentMethodRoute extends AbstractPaymentMethodRoute
{
    private AbstractPaymentMethodRoute $innerService;

    private RequestStack $requestStack;

    /**
     * @var EntityRepository
     * the interface has been deprecated, but shopware is using the Interface in a decorator for the repository.
     * so it will crash, if we are only using EntityRepository, cause an object of the decorator got injected into the constructor.
     * After Shopware has removed the decorator, we can replace this by a normal definition
     * TODO remove comment on Shopware Version 6.5.0.0 & readd type hint & change constructor argument type
     */
    private object $orderRepository;

    /**
     * @var EntityRepository
     * the interface has been deprecated, but shopware is using the Interface in a decorator for the repository.
     * so it will crash, if we are only using EntityRepository, cause an object of the decorator got injected into the constructor.
     * After Shopware has removed the decorator, we can replace this by a normal definition
     * TODO remove comment on Shopware Version 6.5.0.0 & readd type hint & change constructor argument type
     */
    private object $countryRepository;

    private ConfigService $configService;

    /**
     * @noinspection MagicMethodsValidityInspection
     */
    public function __construct(
        AbstractPaymentMethodRoute $innerService,
        RequestStack $requestStack,
        object $orderRepository,
        object $countryRepository,
        ConfigService $configService
    ) {
        $this->innerService = $innerService;
        $this->requestStack = $requestStack;
        $this->orderRepository = $orderRepository;
        $this->countryRepository = $countryRepository;
        $this->configService = $configService;
    }

    public function getDecorated(): AbstractPaymentMethodRoute
    {
        return $this;
    }

    public function load(Request $request, SalesChannelContext $salesChannelContext, ?Criteria $criteria = null): PaymentMethodRouteResponse
    {
        $response = $this->innerService->load($request, $salesChannelContext, $criteria);

        $currentRequest = $this->requestStack->getCurrentRequest();
        if (!$currentRequest instanceof Request) {
            return $response;
        }

        $filterMethods = $request->query->getBoolean('onlyAvailable', false);
        $isPluginReady = $this->configService->isConfigReady();

        if ($isPluginReady === false && $filterMethods) {
            return $this->removeAllBillieMethods($response);
        }

        // Replace variables of billie payment descriptions, names and other translatable fields
        foreach ($response->getPaymentMethods() as $paymentMethod) {
            if (MethodHelper::isBilliePayment($paymentMethod)) {
                $this->replaceVariables($paymentMethod);
            }
        }

        // if the order id is set, the oder has been already placed, and the customer may try to change/edit
        // the payment method. - e.g. in case of a failed payment
        $orderId = $currentRequest->get('orderId');
        $order = null;
        if ($orderId) {
            $criteria = (new Criteria([$orderId]))
                ->addAssociation('addresses');
            /** @var OrderEntity $order */
            $order = $this->orderRepository->search($criteria, $salesChannelContext->getContext())->first();
            $billingAddress = $order->getAddresses()->get($order->getBillingAddressId());
        } else {
            $customer = $salesChannelContext->getCustomer();
            $billingAddress = $customer instanceof CustomerEntity ? $customer->getActiveBillingAddress() : null;
        }

        if (($order || $filterMethods) && ($billingAddress === null ||
            ($billingAddress->getCompany() === null || $billingAddress->getCompany() === '') || $this->getCountryIso($billingAddress) !== 'DE')) {
            return $this->removeAllBillieMethods($response);
        }

        return $response;
    }

    private function removeAllBillieMethods(PaymentMethodRouteResponse $response): PaymentMethodRouteResponse
    {
        foreach ($response->getPaymentMethods() as $key => $paymentMethod) {
            if (MethodHelper::isBilliePayment($paymentMethod)) {
                $response->getPaymentMethods()->remove($key);
            }
        }

        return $response;
    }

    /**
     * @param CustomerAddressEntity|OrderAddressEntity $address
     */
    private function getCountryIso($address): string
    {
        // the whole function does not make any sense.
        // on the checkout-confirm page, the country-assoc is loaded.
        // but the payment methods will be also requested on the success/finish page. On this page, the country-assoc isn't loaded.
        // there is no reason to load the payment methods on the finish page. - so this is just a fix, during the methods will be loaded on the success-page.

        if (!$address->getCountry() instanceof CountryEntity) {
            $country = $this->countryRepository->search(new Criteria([$address->getCountryId()]), Context::createDefaultContext())->first();
        } else {
            $country = $address->getCountry();
        }

        return $country->getIso();
    }

    private function replaceVariables(PaymentMethodEntity $paymentMethod): void
    {
        /** @var PaymentMethodConfigEntity|null $extension */
        $extension = $paymentMethod->getExtension(PaymentMethodExtension::EXTENSION_NAME);
        if ($extension instanceof PaymentMethodConfigEntity) {
            // Prepare variables
            $duration = (string) $extension->getDuration();

            // Description
            $description = $paymentMethod->getDescription();
            $description = str_replace('{duration}', $duration, $description);
            $paymentMethod->setDescription($description);

            // Name
            $name = $paymentMethod->getName();
            $name = str_replace('{duration}', $duration, $name);
            $paymentMethod->setName($name);

            // Translations
            $prepared = [];
            foreach ($paymentMethod->getTranslated() as $key => $translated) {
                if (is_string($translated)) {
                    $translated = str_replace('{duration}', $duration, $translated);
                }

                $prepared[$key] = $translated;
            }

            $paymentMethod->setTranslated($prepared);
        }
    }
}
