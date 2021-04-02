<?php


namespace Billie\BilliePayment\Components\BillieApi\Service;

use Billie\BilliePayment\Components\PluginConfig\Service\ConfigService;

class BillieClientFactory
{

    /**
     * @var ConfigService
     */
    private $configService;

    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }

    public function createBillieClient()
    {
        return \Billie\Sdk\Util\BillieClientFactory::getBillieClientInstance(
            $this->configService->getClientId(),
            $this->configService->getClientSecret(),
            $this->configService->isSandbox()
        );
    }
}
