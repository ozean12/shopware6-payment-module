import BillieCheckout from './billie-checkout.plugin';

const PluginManager = window.PluginManager;

if (PluginManager.getPluginList().BillieCheckout === undefined) {
    PluginManager.register('BillieCheckout', BillieCheckout, '[data-billie-checkout="true"]');
}
