<?php


namespace Billie\BilliePayment\Components;


use Shopware\Storefront\Framework\Cookie\CookieProviderInterface;

class CookieProvider implements CookieProviderInterface
{

    /**
     * @var CookieProviderInterface
     */
    private $originalService;

    public function __construct(CookieProviderInterface $service)
    {
        $this->originalService = $service;
    }

    public function getCookieGroups(): array
    {
        return array_merge(
            $this->originalService->getCookieGroups(),
            [
                [
                    'snippet_name' => 'billie.cookie.group_name',
                    'cookie' => 'billie-payment',
                    'isRequired' => true,
                ]
            ]
        );
    }
}
