<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\PluginConfig\Controller;

use Billie\BilliePayment\Components\PluginConfig\Service\ConfigService;
use Billie\Sdk\Exception\UserNotAuthorizedException;
use Billie\Sdk\Util\BillieClientFactory;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 */
class TestCredentialsController extends AbstractController
{
    /**
     * @var ConfigService
     */
    private $configService;

    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * @Route("/api/v{version}/billie/test-credentials", name="api.action.billie.test-credentials", methods={"POST"})
     */
    public function testCredentials(Request $request): JsonResponse
    {
        $success = true;
        try {
            // Prefer the provided data and take stored data as fallback
            BillieClientFactory::getBillieClientInstance(
                $request->request->get('id') ?? $this->configService->getClientId(),
                $request->request->get('secret') ?? $this->configService->getClientSecret(),
                $request->request->get('isSandbox') ?? $this->configService->isSandbox()
            );
        } catch (UserNotAuthorizedException $exception) {
            $success = false;
        }

        return new JsonResponse([
            'success' => $success,
        ]);
    }
}
