<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\Checkout\Service;

use Billie\BilliePayment\Components\Checkout\Exception\PaymentMethodNotAllowedException;
use Billie\BilliePayment\Components\PaymentMethod\Util\MethodHelper;
use Shopware\Core\Checkout\Order\SalesChannel\AbstractSetPaymentOrderRoute;
use Shopware\Core\Checkout\Order\SalesChannel\SetPaymentOrderRoute as CoreSetPaymentOrderRoute;
use Shopware\Core\Checkout\Order\SalesChannel\SetPaymentOrderRouteResponse;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

class SetPaymentOrderRoute extends CoreSetPaymentOrderRoute
{
    /**
     * @var AbstractSetPaymentOrderRoute
     */
    private $innerService;

    /**
     * @var EntityRepositoryInterface
     */
    private $paymentMethodRepository;

    /**
     * @noinspection MagicMethodsValidityInspection
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(
        AbstractSetPaymentOrderRoute $innerService,
        EntityRepositoryInterface $paymentMethodRepository
    ) {
        $this->innerService = $innerService;
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    public function getDecorated(): AbstractSetPaymentOrderRoute
    {
        return $this;
    }

    public function setPayment(Request $request, SalesChannelContext $context): SetPaymentOrderRouteResponse
    {
        $paymentMethodId = $request->get('paymentMethodId');

        $searchResult = $this->paymentMethodRepository->search(new Criteria([$paymentMethodId]), $context->getContext());
        $paymentMethod = $searchResult->first();
        if (MethodHelper::isBilliePayment($paymentMethod)) {
            throw new PaymentMethodNotAllowedException($paymentMethod->getName());
        }

        return $this->innerService->setPayment($request, $context);
    }
}
