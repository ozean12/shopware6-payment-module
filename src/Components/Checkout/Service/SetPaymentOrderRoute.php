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
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

class SetPaymentOrderRoute extends CoreSetPaymentOrderRoute
{
    private AbstractSetPaymentOrderRoute $innerService;

    /**
     * @var EntityRepository
     * the interface has been deprecated, but shopware is using the Interface in a decorator for the repository.
     * so it will crash, if we are only using EntityRepository, cause an object of the decorator got injected into the constructor.
     * After Shopware has removed the decorator, we can replace this by a normal definition
     * TODO remove comment on Shopware Version 6.5.0.0 & readd type hint & change constructor argument type
     */
    private object $paymentMethodRepository;

    /**
     * @noinspection MagicMethodsValidityInspection
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(
        AbstractSetPaymentOrderRoute $innerService,
        object $paymentMethodRepository
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
