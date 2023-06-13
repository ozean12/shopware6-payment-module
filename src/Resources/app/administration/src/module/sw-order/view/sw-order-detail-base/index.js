import template from './sw-order-detail-base.html.twig';
import './sw-order-detail-base.scss';

const {Component} = Shopware;

Component.override('sw-order-detail-base', {
    template,

    inject: ['acl']
});
