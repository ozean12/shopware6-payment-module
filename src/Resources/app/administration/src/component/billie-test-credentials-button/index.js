import template from './billie-test-credentials-button.html.twig';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const { Component, Mixin } = Shopware;

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
      isTestSuccessful: false,
      currentConfig: null
    };
  },

  created() {
    this.createdComponent();
  },

  destroyed() {
    this.destroyedComponent();
  },

  methods: {
    createdComponent() {
      this.$parent.$parent.$parent.$on('config-changed', this.onConfigChanged);
    },

    destroyedComponent() {
      this.$parent.$parent.$parent.$off('config-changed');
    },

    onConfigChanged(config) {
      this.currentConfig = config;
    },

    onClick() {
      this.testCredentials();
    },

    onTestFinish() {
      this.isTestSuccessful = false;
    },

    testCredentials() {
      this.isTestSuccessful = false;
      this.isLoading = true;

      let id = this.currentConfig ? this.currentConfig['BilliePayment.config.clientId'] : null;
      let secret = this.currentConfig ? this.currentConfig['BilliePayment.config.clientSecret'] : null;
      let isSandbox = this.currentConfig ? this.currentConfig['BilliePayment.config.sandbox'] : null;

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
