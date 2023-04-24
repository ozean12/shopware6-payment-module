<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Bootstrap;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class PluginConfig extends AbstractBootstrap
{
    private ?object $systemConfigService = null;

    /**
     * @var EntityRepository|null
     * the interface has been deprecated, but shopware is using the Interface in a decorator for the repository.
     * so it will crash, if we are only using EntityRepository, cause an object of the decorator got injected into the constructor.
     * After Shopware has removed the decorator, we can replace this by a normal definition
     * TODO remove comment on Shopware Version 6.5.0.0 & readd type hint & change constructor argument type
     */
    private ?object $salutationRepository = null;

    public function injectServices(): void
    {
        $this->systemConfigService = $this->container->get(SystemConfigService::class);
        $this->salutationRepository = $this->container->get('salutation.repository');
    }

    public function install(): void
    {
        // Because we can't define default values in the plugin config for entity selections,
        // we add the default values here, if they do not exist yet.
        $currentValueMale = $this->systemConfigService->get('BilliePayment.config.salutationMale');
        $currentValueFemale = $this->systemConfigService->get('BilliePayment.config.salutationMale');

        if (!$currentValueMale) {
            $salutationMale = $this->getSalutationId('mr');
            if ($salutationMale) {
                $this->systemConfigService->set('BilliePayment.config.salutationMale', $salutationMale);
            }
        }

        if (!$currentValueFemale) {
            $salutationFemale = $this->getSalutationId('mrs');
            if ($salutationFemale) {
                $this->systemConfigService->set('BilliePayment.config.salutationFemale', $salutationFemale);
            }
        }
    }

    public function update(): void
    {
    }

    public function uninstall(bool $keepUserData = false): void
    {
    }

    public function activate(): void
    {
    }

    public function deactivate(): void
    {
    }

    protected function getSalutationId(string $key): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salutationKey', $key));

        $results = $this->salutationRepository->searchIds($criteria, Context::createDefaultContext());

        return $results->firstId();
    }
}
