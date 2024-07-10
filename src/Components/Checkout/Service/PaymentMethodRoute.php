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
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Payment\SalesChannel\AbstractPaymentMethodRoute;
use Shopware\Core\Checkout\Payment\SalesChannel\PaymentMethodRouteResponse;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Country\CountryCollection;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PaymentMethodRoute extends AbstractPaymentMethodRoute
{
    /**
     * @param EntityRepository<OrderCollection> $orderRepository
     * @param EntityRepository<CountryCollection> $countryRepository
     * @noinspection MagicMethodsValidityInspection
     */
    public function __construct(
        private readonly AbstractPaymentMethodRoute $innerService,
        private readonly RequestStack $requestStack,
        private readonly EntityRepository $orderRepository,
        private readonly EntityRepository $countryRepository,
        private readonly ConfigService $configService
    ) {
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

        $isPluginReady = $this->configService->isConfigReady();

        if ($isPluginReady === false) {
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
        if ($orderId) {
            $criteria = (new Criteria([$orderId]))
                ->addAssociation('addresses');
            $order = $this->orderRepository->search($criteria, $salesChannelContext->getContext())->first();
            $billingAddress = $order instanceof OrderEntity ? $order->getAddresses()->get($order->getBillingAddressId()) : null;
        } else {
            $customer = $salesChannelContext->getCustomer();
            $billingAddress = $customer instanceof CustomerEntity ? $customer->getActiveBillingAddress() : null;
        }

        if ($billingAddress === null || empty($billingAddress->getCompany()) || $this->getCountryIso($billingAddress) !== 'DE') {
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
    private function getCountryIso($address): ?string
    {
        // the whole function does not make any sense.
        // on the checkout-confirm page, the country-assoc is loaded.
        // but the payment methods will be also requested on the success/finish page. On this page, the country-assoc isn't loaded.
        // there is no reason to load the payment methods on the finish page. - so this is just a fix, during the methods will be loaded on the success-page.

        if (!$address->getCountry() instanceof CountryEntity) {
            /** @var CountryEntity|null $country */
            $country = $this->countryRepository->search(new Criteria([$address->getCountryId()]), Context::createDefaultContext())->first();
        } else {
            $country = $address->getCountry();
        }

        return $country?->getIso();
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
