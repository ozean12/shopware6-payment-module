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
use Shopware\Core\Checkout\Document\DocumentService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 */
class DocumentController extends \Shopware\Core\Checkout\Document\Controller\DocumentController
{
    /**
     * @var DocumentUrlHelper
     */
    private $documentUrlHelper;

    public function __construct(
        DocumentService $documentService,
        EntityRepository $documentRepository,
        DocumentUrlHelper $documentUrlHelper
    ) {
        parent::__construct($documentService, $documentRepository);
        $this->documentUrlHelper = $documentUrlHelper;
    }

    /**
     * @Route(name="billie.payment.document", path="/billie/document/{documentId}/{deepLinkCode}/{token}")
     */
    public function downloadDocument(Request $request, string $documentId, string $deepLinkCode, Context $context): Response
    {
        if ($this->documentUrlHelper->getToken() !== $request->attributes->get('token')) {
            throw $this->createNotFoundException();
        }

        return parent::downloadDocument($request, $documentId, $deepLinkCode, $context);
    }
}
