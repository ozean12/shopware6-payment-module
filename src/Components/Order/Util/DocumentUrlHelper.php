<?php


namespace Billie\BilliePayment\Components\Order\Util;


use Billie\BilliePayment\Components\PluginConfig\Service\ConfigService;
use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Storefront\Framework\Routing\Router;

class DocumentUrlHelper
{

    /**
     * @var ConfigService
     */
    private $configService;
    /**
     * @var Router
     */
    private $router;

    public function __construct(
        ConfigService $configService,
        Router $router
    )
    {
        $this->configService = $configService;
        $this->router = $router;
    }


    public function generateRouteForDocument(DocumentEntity $document): string
    {
        return $this->router->generate('billie.payment.document', [
            'documentId' => $document->getId(),
            'deepLinkCode' => $document->getDeepLinkCode(),
            'token' => $this->getToken()
        ], Router::ABSOLUTE_URL);
    }

    public function getToken(): string
    {
        return sha1(md5(implode('', [
            $this->configService->getClientId(),
            $this->configService->getClientSecret()
        ])));
    }
}
