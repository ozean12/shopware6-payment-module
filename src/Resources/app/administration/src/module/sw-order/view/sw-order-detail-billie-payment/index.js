import template from './sw-order-detail-billie-payment.html.twig';
import './sw-order-detail-billie-payment.scss';

const {Component} = Shopware;
const {mapState} = Component.getComponentHelper();

Component.register('sw-order-detail-billie-payment', {
    template,

    inject: ['acl'],

    metaInfo() {
        return {
            title: 'Billie Payment'
        };
    },

    computed: {
        ...mapState('swOrderDetail', [
            'order',
        ]),
    },
});

Shopware.Module.register('sw-order-detail-tab-billie-payment', {
    routeMiddleware(next, currentRoute) {
        if (currentRoute.name === 'sw.order.detail') {
            currentRoute.children.push({
                name: 'sw.order.detail.billie-payment',
                path: '/sw/order/detail/:id/billie-payment', // TODO maybe the path before "billie-payment" can be removed
                component: 'sw-order-detail-billie-payment',
                meta: {
                    parentPath: "sw.order.detail",
                    meta: {
                        parentPath: 'sw.order.index',
                        privilege: 'order.viewer',
                    },
                }
            });
        }
        next(currentRoute);
    }
});
