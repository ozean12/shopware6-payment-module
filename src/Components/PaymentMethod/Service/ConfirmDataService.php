<?php declare(strict_types=1);


namespace Billie\BilliePayment\Components\PaymentMethod\Service;


use Billie\BilliePayment\Components\BillieApi\Util\AddressHelper;
use Billie\BilliePayment\Components\PaymentMethod\Event\ConfirmModelBuilt;
use Billie\BilliePayment\Components\PaymentMethod\Model\Extension\PaymentMethodExtension;
use Billie\BilliePayment\Components\PaymentMethod\Model\PaymentMethodConfigEntity;
use Billie\BilliePayment\Util\CriteriaHelper;
use Billie\Sdk\Model\Amount;
use Billie\Sdk\Model\Request\CheckoutSessionConfirmRequestModel;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ConfirmDataService
{

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var EntityRepositoryInterface
     */
    private $orderRepository;

    public function __construct(
        EntityRepositoryInterface $orderRepository,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->orderRepository = $orderRepository;
    }

    public function getConfirmModel(string $sessionUuid, OrderEntity $orderEntity): CheckoutSessionConfirmRequestModel
    {

        $criteria = CriteriaHelper::getCriteriaForOrder($orderEntity->getId());
        /** @var OrderEntity $orderEntity */
        $orderEntity = $this->orderRepository->search($criteria, Context::createDefaultContext())->first();


        /** @var PaymentMethodConfigEntity $paymentConfig */
        $paymentConfig = $orderEntity->getTransactions()->first()->getPaymentMethod()->getExtension(PaymentMethodExtension::EXTENSION_NAME);

        $billingAddressId = $orderEntity->getBillingAddressId();
        $shippingAddressId = $orderEntity->getDeliveries()->first()->getShippingOrderAddressId();

        $model = (new CheckoutSessionConfirmRequestModel())
            ->setSessionUuid($sessionUuid)
            ->setCompany(AddressHelper::createDebtorCompany($orderEntity->getAddresses()->get($billingAddressId)))
            ->setDeliveryAddress(AddressHelper::createAddress($orderEntity->getAddresses()->get($shippingAddressId)))
            ->setDuration($paymentConfig->getDuration())
            ->setAmount((new Amount())
                ->setGross($orderEntity->getAmountTotal())
                ->setNet($orderEntity->getAmountNet())
            );

        /** @var ConfirmModelBuilt $event */
        $event = $this->eventDispatcher->dispatch(new ConfirmModelBuilt($model, $orderEntity));
        return $event->getModel();
    }

}
