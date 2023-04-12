## Integration of an ERP/WaWi

If you do not use the Shopware default invoice documents, you need to inform the Billie module about the invoice number
form the ERP/WaWi.

Please do NOT modify any data via direct SQL commands!

The following code snippet will show you how you can modify the Billie data correctly. You should create a custom plugin
for this, or integrate it into the adaptor of your WaWi/ERP system.

### Preparation

You should inject the entity repository via DI of Symfony. The name of the service is called
`billie_order_data.repository` and will be an instance
of `\Shopware\Core\Framework\DataAbstractionLayer\EntityRepository`

You can also get it via the container (not the recommended):

```php
/** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */

/** @var \Shopware\Core\Framework\DataAbstractionLayer\EntityRepository $repository */
$repository = $container->get('billie_order_data.repository');
```

### Fetch Billie order data by order id

If you do only have the entity id of the order, you need to use the repository to find the billie data by the order id.

```php
/** @var \Shopware\Core\Framework\DataAbstractionLayer\EntityRepository $repository */

$orderId = 'YOUR_ORDER_ID';
$criteria = new \Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria();
$criteria->addFilter(new \Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter(
    \Billie\BilliePayment\Components\Order\Model\OrderDataEntity::FIELD_ORDER_ID,
    $orderId
));

/** @var \Billie\BilliePayment\Components\Order\Model\OrderDataEntity $billieOrderData */
$billieOrderData = $repository->search($criteria, \Shopware\Core\Framework\Context::createDefaultContext())->first();

$billieOrderData->getId(); // ID of the Billie order data entity
$billieOrderData->getReferenceId(); // billie order reference id (uui)
$billieOrderData->getExternalInvoiceNumber(); // external invoice number
$billieOrderData->getExternalInvoiceUrl(); // external invoice url
$billieOrderData->getExternalDeliveryNoteUrl(); // external delivery note url
$billieOrderData->getBankIban(); // bank account: iban
$billieOrderData->getBankBic(); // bank account: bic
$billieOrderData->getBankName(); // bank account: name
```

### Fetch Billie order data from loaded order entity

If you already have an instance of an order entity, you can get the order data simply by the `getExtension`-method.

```php
/** @var \Shopware\Core\Checkout\Order\OrderEntity $order */
$order = [...];

/** @var \Billie\BilliePayment\Components\Order\Model\OrderDataEntity $billieOrderData */
$billieOrderData = $order->getExtension(\Billie\BilliePayment\Components\Order\Model\Extension\OrderExtension::EXTENSION_NAME);

$billieOrderData->getExternalInvoiceNumber(); // External invoice number
$billieOrderData->getExternalInvoiceUrl(); // external invoice url
$billieOrderData->getExternalDeliveryNoteUrl(); // external delivery note url
```

## Update Billie order data

If you need to modify the Billie data, you should do this with the entity repository and the `upsert`-method.

```php
/** @var \Shopware\Core\Framework\DataAbstractionLayer\EntityRepository $repository */

$repository->upsert([
    [
        \Billie\BilliePayment\Components\Order\Model\OrderDataEntity::FIELD_ID => 'ID of the Billie order data entity', // This is always required !
        \Billie\BilliePayment\Components\Order\Model\OrderDataEntity::FIELD_EXTERNAL_INVOICE_NUMBER => 'external invoice number',
        \Billie\BilliePayment\Components\Order\Model\OrderDataEntity::FIELD_EXTERNAL_INVOICE_URL => 'external invoice url',
        \Billie\BilliePayment\Components\Order\Model\OrderDataEntity::FIELD_EXTERNAL_DELIVERY_NOTE_URL => 'external delivery note url',
    ]
], \Shopware\Core\Framework\Context::createDefaultContext());
```
