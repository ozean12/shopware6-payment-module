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

      let id = document.querySelector('[name="BilliePayment.config.clientId"]').value;
      let secret = document.querySelector('[name="BilliePayment.config.clientSecret"]').value;
      let isSandbox = document.querySelector('[name="BilliePayment.config.sandbox"]').value === 'on';

      this.billieApiService.testCredentials(id, secret, isSandbox).then((response) => {
        this.isLoading = false;

        if (response.success) {
          this.isTestSuccessful = true;
          this.createNotificationSuccess({
            message: this.$tc('billie.config.notification.correctCredentials')
          });
        } else {
          this.createNotificationError({
            message: this.$tc('billie.config.notification.incorrectCredentials')
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
