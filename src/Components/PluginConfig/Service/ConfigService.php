<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\PluginConfig\Service;

use Shopware\Core\System\Salutation\SalutationEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ConfigService
{
    public function __construct(
        private readonly SystemConfigService $systemConfigService
    ) {
    }

    /**
     * returns true if the plugin config has been set.
     */
    public function isConfigReady(): bool
    {
        return $this->getClientId() !== null && $this->getClientId() !== '' && $this->getClientSecret() !== null && $this->getClientSecret() !== '';
    }

    public function getPluginConfiguration(): array
    {
        return $this->systemConfigService->get('BilliePaymentSW6.config') ?: [];
    }

    public function getClientId(): ?string
    {
        $config = $this->getPluginConfiguration();

        if ($this->isSandbox()) {
            return $config['testClientId'] ?? null;
        }

        return $config['liveClientId'] ?? null;
    }

    public function getClientSecret(): ?string
    {
        $config = $this->getPluginConfiguration();

        if ($this->isSandbox()) {
            return $config['testClientSecret'] ?? null;
        }

        return $config['liveClientSecret'] ?? null;
    }

    public function isSandbox(): bool
    {
        $config = $this->getPluginConfiguration();

        return isset($config['sandbox']) && $config['sandbox'];
    }

    public function getSalutation(SalutationEntity $salutationEntity): string
    {
        $config = $this->getPluginConfiguration();

        // these fields are required, but shopware does not validate them. So we will set default NULL values, if the index are not set
        $config['salutationMale'] ??= null;
        $config['salutationFemale'] ??= null;
        $config['salutationFallback'] ??= null;

        $return = match ($salutationEntity->getId()) {
            $config['salutationMale'] => 'm',
            $config['salutationFemale'] => 'f',
            default => $config['salutationFallback'],
        };

        return in_array($return, ['m', 'f'], true) ? $return : 'm';
    }

    public function isStateWatchingEnabled(): bool
    {
        $config = $this->getPluginConfiguration();

        return isset($config['stateEnabled']) && $config['stateEnabled'];
    }
}
