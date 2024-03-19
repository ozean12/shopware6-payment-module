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
use Billie\BilliePayment\Components\PaymentMethod\PaymentHandler\DirectDebitPaymentHandler;
use Billie\BilliePayment\Components\PaymentMethod\PaymentHandler\InvoicePaymentHandler;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class PaymentMethods extends AbstractBootstrap
{
//    public const METHOD_ID_INVOICE = '52db55acf63741b8a88505e2ea90100e';
    /**
     * @var string
     */
    public const METHOD_ID_DIRECT_DEBIT = '7a7691e33d404e72b8c287266c7ff523';

    /**
     * @var array
     */
    public const PAYMENT_METHODS = [
        [
//            'id' => self::METHOD_ID_INVOICE, // we can not add this, because we can not update the method-id in the saved context of the user.
            'handlerIdentifier' => InvoicePaymentHandler::class,
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
        ], [
            'id' => self::METHOD_ID_DIRECT_DEBIT,
            'handlerIdentifier' => DirectDebitPaymentHandler::class,
            'name' => 'Billie SEPA Direct Debit',
            'description' => 'Pay conveniently and securely using SEPA direct debit by Billie {duration} days upon shipment of goods.',
            'afterOrderEnabled' => false,
            'translations' => [
                'de-DE' => [
                    'name' => 'Billie SEPA Lastschrift',
                    'description' => 'Bezahlen Sie bequem und sicher per SEPA Lastschrift mit Billie {duration} Tage nach Warenversand.',
                ],
                'en-GB' => [
                    'name' => 'Billie SEPA Direct Debit',
                    'description' => 'Pay conveniently and securely using SEPA direct debit by Billie {duration} days upon shipment of goods.',
                ],
            ],
            PaymentMethodExtension::EXTENSION_NAME => [
                'duration' => 14,
            ],
        ],
    ];

    /**
     * @var EntityRepository|null
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
    }

    public function postUpdate(): void
    {
        $this->addPaymentMethods();
    }

    public function postInstall(): void
    {
        $this->addPaymentMethods();
        $this->setActiveFlags(false);
    }

    public function install(): void
    {
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

    private function setActiveFlags(bool $activated): void
    {
        /** @var PaymentMethodEntity[] $paymentEntities */
        $paymentEntities = $this->paymentRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('pluginId', $this->plugin->getId())),
            $this->defaultContext
        )->getElements();

        $updateData = array_map(static fn(PaymentMethodEntity $entity): array => [
            'id' => $entity->getId(),
            'active' => $activated,
        ], $paymentEntities);

        $this->paymentRepository->update(array_values($updateData), $this->defaultContext);
    }

    private function addPaymentMethods():void
    {
        // add payment methods which does not exist yet
        foreach (self::PAYMENT_METHODS as $paymentMethod) {
            if (!isset($paymentMethod['id'])) {
                $criteria = new Criteria();
                $criteria->addFilter(new EqualsFilter('handlerIdentifier', $paymentMethod['handlerIdentifier']));
                $criteria->setLimit(1);
            } else {
                $criteria = new Criteria([$paymentMethod['id']]);
            }

            $id = $this->paymentRepository->searchIds($criteria, $this->defaultContext)->firstId();

            if ($id === null) {
                $paymentMethod['pluginId'] = $this->plugin->getId();
                $paymentMethod['active'] = false;
                $this->paymentRepository->upsert([$paymentMethod], $this->defaultContext);
            }
        }
    }
}
