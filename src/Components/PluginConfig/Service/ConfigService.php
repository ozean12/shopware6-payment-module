<?php declare(strict_types=1);

namespace Billie\BilliePayment\Components\PluginConfig\Service;

use Shopware\Core\System\Salutation\SalutationEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ConfigService
{
    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * returns true if the plugin config has been set
     *
     * @return bool
     */
    public function isConfigReady(): bool
    {
        return count($this->getPluginConfiguration()) > 0;
    }

    public function getPluginConfiguration(): array
    {
        return $this->systemConfigService->get('BilliePayment.config') ?: [];
    }

    public function getClientId(): ?string
    {
        $config = $this->getPluginConfiguration();

        return $config['clientId'] ?? null;
    }

    public function getClientSecret(): ?string
    {
        $config = $this->getPluginConfiguration();

        return $config['clientSecret'] ?? null;
    }

    public function isSandbox(): bool
    {
        $config = $this->getPluginConfiguration();

        return (bool) $config['sandbox'];
    }

    public function getSalutation(SalutationEntity $salutationEntity): string
    {
        $config = $this->getPluginConfiguration();

        switch ($salutationEntity->getId()) {
            case $config['salutationMale']:
                $return = 'm';
                break;
            case $config['salutationFemale']:
                $return = 'f';
                break;
            default :
                $return = $config['salutationFallback'];
                break;
        }

        return in_array($return, ['m', 'f']) ? $return : 'm';
    }

    public function getStateForShip(): ?string
    {
        $config = $this->getPluginConfiguration();

        return $config['stateShipped'] ?? null;
    }

    public function getStateCancel(): ?string
    {
        $config = $this->getPluginConfiguration();

        return $config['stateCanceled'] ?? null;
    }

    public function isStateWatchingEnabled(): bool
    {
        $config = $this->getPluginConfiguration();

        return (bool) $config['stateEnabled'];
    }

}
