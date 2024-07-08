<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\Order\Controller;

use Billie\BilliePayment\Components\Order\Util\DocumentUrlHelper;
use Shopware\Core\Checkout\Document\SalesChannel\AbstractDocumentRoute;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: [
    '_routeScope' => ['storefront'],
])]
class DocumentController extends StorefrontController
{
    public function __construct(
        private readonly AbstractDocumentRoute $documentRoute,
        private readonly DocumentUrlHelper $documentUrlHelper
    ) {
    }

    #[Route(path: 'billie/document/{documentId}/{deepLinkCode}/{token}', name: 'billie.payment.document')]
    public function downloadDocument(Request $request, string $documentId, string $deepLinkCode, SalesChannelContext $context): Response
    {
        if ($this->documentUrlHelper->getToken() !== $request->attributes->get('token')) {
            throw $this->createNotFoundException();
        }

        return $this->documentRoute->download($documentId, $request, $context, $deepLinkCode);
    }
}
