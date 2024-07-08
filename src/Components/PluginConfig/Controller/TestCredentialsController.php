<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\PluginConfig\Controller;

use Billie\Sdk\Exception\UserNotAuthorizedException;
use Billie\Sdk\Util\BillieClientFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api/billie', defaults: [
    '_routeScope' => ['api'],
])]
class TestCredentialsController extends AbstractController
{
    #[Route(
        path: '/test-credentials',
        name: 'api.action.billie.test-credentials',
        methods: ['POST']
    )]
    public function testCredentials(Request $request): JsonResponse
    {
        $success = true;
        try {
            BillieClientFactory::getBillieClientInstance(
                $request->request->get('id'),
                $request->request->get('secret'),
                $request->request->get('isSandbox')
            );
        } catch (UserNotAuthorizedException $userNotAuthorizedException) {
            $success = false;
        }

        return new JsonResponse([
            'success' => $success,
        ]);
    }
}
