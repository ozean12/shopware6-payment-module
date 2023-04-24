<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Twig;

use Shopware\Storefront\Framework\Twig\Extension\CsrfFunctionExtension;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class to add a fallback for removed sw_csrf twig function.
 * The twig function has been removed with Shopware 6.5.0.0.
 * To keep the module compatible we will add a custom twig function and will forward the method call to the service
 * if the service exists.
 * @deprecated will be removed in a future release
 */
class CsrfWrapper extends AbstractExtension
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function getFunctions(): array
    {
        /**
         * @noinspection PhpUndefinedClassInspection
         * @noinspection RedundantSuppression
         * @phpstan-ignore-next-line
         */
        $extensionClass = CsrfFunctionExtension::class;

        /** @phpstan-ignore-next-line */
        if ($this->twig->hasExtension($extensionClass)) {
            /** @phpstan-ignore-next-line */
            $extension = $this->twig->getExtension($extensionClass);

            return [
                new TwigFunction('billie_sw_csrf', [$extension, 'createCsrfPlaceholder'], [
                    'is_safe' => ['html'],
                ]),
            ];
        }

        return [
            new TwigFunction('billie_sw_csrf', [$this, 'returnNothing']),
        ];
    }

    public function returnNothing(string $intent, array $parameters = []): string
    {
        return '';
    }
}
