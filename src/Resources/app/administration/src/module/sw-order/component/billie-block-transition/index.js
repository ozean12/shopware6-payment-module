const {Component} = Shopware;

const newMethod = function (errorMessage) {
    if (errorMessage.response && errorMessage.response.data && errorMessage.response.data.errors) {
        let transitionError = errorMessage.response.data.errors.pop();
        if (transitionError.code === 'BILLIE__INVOICE_NUMBER_MISSING') {
            this.createNotificationError({
                message: this.$tc('billie.transition.errors.invoiceNumberMissing')
            });
        } else {
            this.$super('createStateChangeErrorNotification', errorMessage)
        }
    } else {
        this.$super('createStateChangeErrorNotification', errorMessage)
    }
}

Component.override('sw-order-state-history-card', {
    methods: {
        createStateChangeErrorNotification: newMethod
    }
});

Component.override('sw-order-general-info', {
    methods: {
        createStateChangeErrorNotification: newMethod
    }
});

Component.override('sw-order-details-state-card', {
    methods: {
        createStateChangeErrorNotification: newMethod
    }
});
