const { Component } = Shopware;

Component.override('sw-order-state-history-card', {
  methods: {
    createStateChangeErrorNotification(error) {
      if (error.response && error.response.data && error.response.data.errors) {
        let transitionError = error.response.data.errors.pop();
        if (transitionError.code === 'BILLIE__INVOICE_NUMBER_MISSING') {
          this.createNotificationError({
            message: this.$tc('billie.transition.errors.invoiceNumberMissing')
          });
        } else {
          this.$super('createStateChangeErrorNotification', error)
        }
      } else {
        this.$super('createStateChangeErrorNotification', error)
      }
    }
  }
});
