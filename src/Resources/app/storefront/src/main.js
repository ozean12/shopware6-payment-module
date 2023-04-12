import BillieCheckout from './billie-checkout.plugin';

const PluginManager = window.PluginManager;
let pluginList = PluginManager.getPluginList();

if(!('BillieCheckout' in pluginList)) {
    PluginManager.register('BillieCheckout', BillieCheckout, '[data-billie-checkout="true"]');
}
