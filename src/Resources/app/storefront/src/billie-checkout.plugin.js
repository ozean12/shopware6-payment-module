import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';

export default class BilliePayment extends Plugin {

    static options = {
        src: null,
        checkoutSessionId: null,
        merchantName: null,
        checkoutData: null,
        csrfToken: ''
    };

    init() {
        this._insertWidget();
        this._registerEvents();
    }

    _registerEvents() {
        this.el.form.addEventListener('beforeSubmit', this._submitForm.bind(this))
    }

    _submitForm(event) {
        if (this._isAddressConfirmed()) {
            //address has been already confirmed and validated. So we will process the order.
            return true;
        } else {
            // address has not been confirmed and must be validated
            event.preventDefault();

            BillieCheckoutWidget.mount({
                billie_config_data: {
                    'session_id': this.options.checkoutSessionId,
                    'merchant_name': this.options.merchantName
                },
                billie_order_data: this.options.checkoutData
            }).then((data) => {
                const client = new HttpClient(window.accessKey, window.contextToken);
                let url = '/billie-payment/update-addresses';
                let locationMatch = window.location.href.match(/account\/order\/edit\/([A-Za-z0-9]+)/);
                if (locationMatch && locationMatch.length === 2) {
                    // payment get updated
                    url += '/' + locationMatch[1];
                }

                if ('csrf' in window && window.csrf.enabled && window.csrf.mode === 'twig') {
                    data['_csrf_token'] = this.options.csrfToken;
                }

                client.post(url, JSON.stringify(data), (response) => {
                    this._setAddressConfirmed(true);
                    this.el.value = this.options.checkoutSessionId;
                    this.el.form.submit();
                });
            }).catch((err) => {
                event.preventDefault();
                console.error('Error occurred', err);
                window.location.reload();
            });
        }
    }

    _insertWidget() {
        window.billiePaymentData = this.options.checkoutData;
        // @formatter:off
        (function (w, d, s, o, f, js, fjs) {
            w['BillieCheckoutWidget'] = o;
            w[o] = w[o] || function () {
                (w[o].q = w[o].q || []).push(arguments)
            };
            w.billieSrc = f;
            js = d.createElement(s);
            fjs = d.getElementsByTagName(s)[0];
            js.id = o;
            js.src = f;
            js.charset = 'utf-8';
            js.async = 1;
            fjs.parentNode.insertBefore(js, fjs);
            bcw('init');
        }(window, document, 'script', 'bcw', this.options.src));
        // @formatter:on
    }

    _setAddressConfirmed(flag) {
        this.el.form.dataset.billieConfirmed = parseInt(flag);
    }

    _isAddressConfirmed() {
        return parseInt(this.el.form.dataset.billieConfirmed) === 1;
    }

}
