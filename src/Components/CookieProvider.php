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
        $cookiesNames = [
            'ajs_user_id',
            'ajs_anonymous_id',
            'intercom-session-*',
            'fs_uid',
            'mkjs_group_id',
            'mkjs_user_id'
        ];

        $cookies = [];
        foreach ($cookiesNames as $name) {
            $cookies[] = [
                'snippet_name' => 'Cookie: ' . $name,
                'cookie' => $name,
                'isRequired' => true,
                'isHidden' => true
            ];
        }

        return array_merge(
            $this->originalService->getCookieGroups(),
            [
                [
                    'snippet_name' => 'billie.cookie.group_name',
                    'isRequired' => true,
                    'entries' => $cookies,
                ]
            ]
        );
    }
}
