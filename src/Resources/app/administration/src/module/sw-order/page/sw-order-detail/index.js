import template from './sw-order-detail.html.twig';
import deDE from "./snippet/de-DE.json";
import enGB from "./snippet/en-GB.json";

const {Component} = Shopware;

Component.override('sw-order-detail', {
    template,
    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    methods: {
        /**
         * @deprecated will be removed in future releases which are only compatible with Shopware versions > 6.5
         */
        createdComponent() {
            this.$super('createdComponent');
            this._65to64LoadOrderBackwardCompatibility();
        },

        /**
         * @deprecated will be removed in future releases which are only compatible with Shopware versions > 6.5
         * this is a workaround for Shopware versions < 6.5.
         * in older SW Versions the order entity is not available in the global state/store, so we need to fetch the order
         * manually.
         *
         * if you also need to load the order for your own extension, please copy the method to your own module with
         * the same name. So we make sure that the order got loaded for your module and the Billie module, without loading it multiple times.
         * please do not forget to copy the method `createdComponent()`.
         */
        _65to64LoadOrderBackwardCompatibility() {
            if (this.versionContext || (this.order && this.order.id === this.orderId)) {
                // if the versionContext has been already set, it seems like FEATURE_NEXT_7530 has been enabled or
                // shopware is on version > 6.5.
                return;
            }

            State.commit('swOrderDetail/setLoading', ['order', true]);
            this.orderRepository.get(this.orderId, Shopware.Context.api, this.orderCriteria).then((response) => {
                State.commit('swOrderDetail/setOrder', response);
            });
        }
    }
});
