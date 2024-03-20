<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Bootstrap;

use Billie\BilliePayment\Components\PaymentMethod\Model\Extension\PaymentMethodExtension;
use Billie\BilliePayment\Components\PaymentMethod\PaymentHandler\PaymentHandler;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class PaymentMethods extends AbstractBootstrap
{
    /**
     * @var array<string, array<string, (class-string<PaymentHandler> | bool | array<string, array<string, string>> | array<string, int> | string)>>
     */
    public const PAYMENT_METHODS = [
        PaymentHandler::class => [
            'handlerIdentifier' => PaymentHandler::class,
            'name' => 'Billie Invoice',
            'description' => 'Pay comfortably and securely on invoice - within {duration} days after receiving the goods.',
            'afterOrderEnabled' => false,
            'translations' => [
                'de-DE' => [
                    'name' => 'Billie Rechnungskauf',
                    'description' => 'Bezahlen Sie bequem und sicher auf Rechnung - innerhalb von {duration} Tagen nach Erhalt der Ware.',
                ],
                'en-GB' => [
                    'name' => 'Billie Invoice',
                    'description' => 'Pay comfortably and securely on invoice - within {duration} days after receiving the goods.',
                ],
            ],
            PaymentMethodExtension::EXTENSION_NAME => [
                'duration' => 14,
            ],
        ],
    ];

    /**
     * @var EntityRepository<PaymentMethodCollection>|null
     * the interface has been deprecated, but shopware is using the Interface in a decorator for the repository.
     * so it will crash, if we are only using EntityRepository, cause an object of the decorator got injected into the constructor.
     * After Shopware has removed the decorator, we can replace this by a normal definition
     * TODO remove comment on Shopware Version 6.5.0.0 & readd type hint & change constructor argument type
     */
    private ?object $paymentRepository = null;

    public function injectServices(): void
    {
        $this->paymentRepository = $this->container->get('payment_method.repository');
    }

    public function update(): void
    {
        foreach (self::PAYMENT_METHODS as $paymentMethod) {
            $this->upsertPaymentMethod($paymentMethod);
        }

        // Keep active flags as they are
    }

    public function install(): void
    {
        foreach (self::PAYMENT_METHODS as $paymentMethod) {
            $this->upsertPaymentMethod($paymentMethod);
        }

        $this->setActiveFlags(false);
    }

    public function uninstall(bool $keepUserData = false): void
    {
        $this->setActiveFlags(false);
    }

    public function activate(): void
    {
        $this->setActiveFlags(true);
    }

    public function deactivate(): void
    {
        $this->setActiveFlags(false);
    }

    protected function upsertPaymentMethod(array $paymentMethod): void
    {
        $paymentSearchResult = $this->paymentRepository->search(
            (
                (new Criteria())
                    ->addFilter(new EqualsFilter('handlerIdentifier', $paymentMethod['handlerIdentifier']))
                    ->setLimit(1)
            ),
            $this->defaultContext
        );

        /** @var PaymentMethodEntity|null $paymentEntity */
        $paymentEntity = $paymentSearchResult->first();
        if ($paymentEntity instanceof PaymentMethodEntity) {
            $paymentMethod['id'] = $paymentEntity->getId();
        }

        $paymentMethod['pluginId'] = $this->plugin->getId();
        $this->paymentRepository->upsert([$paymentMethod], $this->defaultContext);
    }

    protected function setActiveFlags(bool $activated): void
    {
        /** @var PaymentMethodEntity[] $paymentEntities */
        $paymentEntities = $this->paymentRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('pluginId', $this->plugin->getId())),
            $this->defaultContext
        )->getElements();

        $updateData = array_map(static fn (PaymentMethodEntity $entity): array => [
            'id' => $entity->getId(),
            'active' => $activated,
        ], $paymentEntities);

        $this->paymentRepository->update(array_values($updateData), $this->defaultContext);
    }
}
