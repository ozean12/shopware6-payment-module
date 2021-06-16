<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\BillieApi\Service;

use Billie\BilliePayment\Components\PluginConfig\Service\ConfigService;
use Billie\Sdk\HttpClient\BillieClient;

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

    public function createBillieClient(): BillieClient
    {
        return \Billie\Sdk\Util\BillieClientFactory::getBillieClientInstance(
            $this->configService->getClientId(),
            $this->configService->getClientSecret(),
            $this->configService->isSandbox()
        );
    }
}
