import template from './billie-test-credentials-button.html.twig';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const {Component, Mixin} = Shopware;

Component.register('billie-test-credentials-button', {
    template,

    inject: [
        'billieApiService'
    ],

    mixins: [
        Mixin.getByName('notification')
    ],

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    props: {
        apiMode: {
            type: String,
            required: true
        }
    },

    data() {
        return {
            isLoading: false,
            isTestSuccessful: false
        };
    },

    methods: {

        onTestFinish() {
            this.isTestSuccessful = false;
        },

        testCredentials() {
            this.isTestSuccessful = false;
            this.isLoading = true;

            let id = document.querySelector(`[name="BilliePaymentSW6.config.${this.apiMode}ClientId"]`).value;
            let secret = document.querySelector(`[name="BilliePaymentSW6.config.${this.apiMode}ClientSecret"]`).value;
            let isSandbox = this.apiMode === 'test';

            this.billieApiService.testCredentials(id, secret, isSandbox).then((response) => {
                this.isLoading = false;

                if (response.success) {
                    this.isTestSuccessful = true;
                    this.createNotificationSuccess({
                        message: this.$tc('billie.config.notification.validCredentials')
                    });
                } else {
                    this.createNotificationError({
                        message: this.$tc('billie.config.notification.invalidCredentials')
                    });
                }
            }).catch(() => {
                this.isLoading = false;
                this.createNotificationError({
                    message: this.$tc('billie.config.notification.failedToTestCredentials')
                });
            });
        }
    }
});
