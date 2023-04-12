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
    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * TODO remove interface and increase min. SW Version to 6.5
     * @var EntityRepository|\Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface
     */
    private $salutationRepository;

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
