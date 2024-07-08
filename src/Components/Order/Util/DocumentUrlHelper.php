<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\Order\Util;

use Billie\BilliePayment\Components\PluginConfig\Service\ConfigService;
use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Storefront\Framework\Routing\Router;

class DocumentUrlHelper
{
    public function __construct(
        private readonly ConfigService $configService,
        private readonly Router $router
    ) {
    }

    public function generateRouteForDocument(DocumentEntity $document): string
    {
        return $this->router->generate('billie.payment.document', [
            'documentId' => $document->getId(),
            'deepLinkCode' => $document->getDeepLinkCode(),
            'token' => $this->getToken(),
        ], Router::ABSOLUTE_URL);
    }

    public function getToken(): string
    {
        return sha1(md5(implode('', [
            $this->configService->getClientId(),
            $this->configService->getClientSecret(),
        ])));
    }
}
