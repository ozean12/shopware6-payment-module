(this.webpackJsonp=this.webpackJsonp||[]).push([["billie-payment-s-w6"],{"8/lK":function(e,i,n){},F6FM:function(e,i){e.exports='{% block billie_test_credentials_button %}\n    <div class="billie-test-credentials-button">\n        {% block billie_test_credentials_button_process_button %}\n            <sw-button-process\n                class="billie-test-credentials-button__process-button"\n                :isLoading="isLoading"\n                :processSuccess="isTestSuccessful"\n                :disabled="isLoading"\n                @click.prevent="testCredentials"\n                @process-finish="onTestFinish"\n                block>\n                {{ $tc(\'billie.config.testCredentialsButton.text\') }}\n            </sw-button-process>\n        {% endblock %}\n    </div>\n{% endblock %}\n'},H5VR:function(e,i){e.exports='{% block sw_order_detail_base_custom_fields %}\n\n    {% block billie_payment_order_data %}\n        <sw-card class="billie-payment-order-data-card"\n                 :title="$tc(\'billie.orderData.cart.title\')"\n                 v-if="order.extensions.billieData"\n                 :isLoading="isLoading">\n\n            <sw-container columns="repeat(auto-fit, minmax(250px, 1fr))" gap="30px 30px">\n\n                <sw-description-list columns="1fr" grid="1fr">\n                    <dt>{{ $tc(\'billie.orderData.fields.referenceId.label\') }}</dt>\n                    <dd>\n                        {{ order.extensions.billieData.referenceId }}\n                    </dd>\n                </sw-description-list>\n\n                <sw-description-list columns="1fr" grid="1fr">\n                    <dt>{{ $tc(\'billie.orderData.fields.externalInvoiceNumber.label\') }}</dt>\n                    <dd>\n                        <sw-order-inline-field v-model="order.extensions.billieData.externalInvoiceNumber"\n                                               :displayValue="order.extensions.billieData.externalInvoiceNumber ? order.extensions.billieData.externalInvoiceNumber : \'-\'"\n                                               :value="order.extensions.billieData.externalInvoiceNumber"\n                                               :editable="isEditing"\n                                               @input="saveAndReload"\n                                               class="sw-order-inline-field__truncateable">\n                        </sw-order-inline-field>\n                    </dd>\n                </sw-description-list>\n\n                <sw-description-list columns="1fr" grid="1fr">\n                    <dt>{{ $tc(\'billie.orderData.fields.externalInvoiceUrl.label\') }}</dt>\n                    <dd>\n                        <sw-order-inline-field v-model="order.extensions.billieData.externalInvoiceUrl"\n                                               :displayValue="order.extensions.billieData.externalInvoiceUrl ? order.extensions.billieData.externalInvoiceUrl : \'-\'"\n                                               :value="order.extensions.billieData.externalInvoiceUrl"\n                                               :editable="isEditing"\n                                               @input="saveAndReload"\n                                               class="sw-order-inline-field__truncateable">\n                        </sw-order-inline-field>\n                    </dd>\n                </sw-description-list>\n\n                <sw-description-list columns="1fr" grid="1fr">\n                    <dt>{{ $tc(\'billie.orderData.fields.externalDeliveryNoteUrl.label\') }}</dt>\n                    <dd>\n                        <sw-order-inline-field v-model="order.extensions.billieData.externalDeliveryNoteUrl"\n                                               :displayValue="order.extensions.billieData.externalDeliveryNoteUrl ? order.extensions.billieData.externalDeliveryNoteUrl : \'-\'"\n                                               :value="order.extensions.billieData.externalDeliveryNoteUrl"\n                                               :editable="isEditing"\n                                               @input="saveAndReload"\n                                               class="sw-order-inline-field__truncateable">\n                        </sw-order-inline-field>\n                    </dd>\n                </sw-description-list>\n\n            </sw-container>\n\n        </sw-card>\n\n    {% endblock %}\n\n    {% parent %}\n{% endblock %}\n'},IaS6:function(e,i,n){"use strict";n.r(i);const t=Shopware.Classes.ApiService;class s extends t{constructor(e,i,n="billie"){super(e,i,n),this.name="billieApiService"}testCredentials(e,i,n){return this.httpClient.post(`${this.getApiBasePath()}/test-credentials`,{id:e,secret:i,isSandbox:n},{headers:this.getBasicHeaders()}).then(e=>t.handleResponse(e))}}Shopware.Application.addServiceProvider("billieApiService",()=>{const e=Shopware.Application.getContainer("factory"),i=Shopware.Application.getContainer("init"),n=e.apiService,t=new s(i.httpClient,Shopware.Service("loginService")),r=t.name;return n.register(r,t),t});var r=n("eu7C"),l=n.n(r);const{Component:a}=Shopware;a.override("sw-settings-payment-detail",{template:l.a});var o=n("H5VR"),d=n.n(o);n("Wpim");const{Component:c}=Shopware;c.override("sw-order-detail-base",{template:d.a,inject:["acl"]});n("XemQ");var p=n("F6FM"),b=n.n(p),u=n("kSqU"),f=n("nstg");const{Component:g,Mixin:m}=Shopware;g.register("billie-test-credentials-button",{template:b.a,inject:["billieApiService"],mixins:[m.getByName("notification")],snippets:{"de-DE":u,"en-GB":f},props:{apiMode:{type:String,required:!0}},data:()=>({isLoading:!1,isTestSuccessful:!1}),methods:{onTestFinish(){this.isTestSuccessful=!1},testCredentials(){this.isTestSuccessful=!1,this.isLoading=!0;let e=document.querySelector(`[name="BilliePaymentSW6.config.${this.apiMode}ClientId"]`).value,i=document.querySelector(`[name="BilliePaymentSW6.config.${this.apiMode}ClientSecret"]`).value,n="test"===this.apiMode;this.billieApiService.testCredentials(e,i,n).then(e=>{this.isLoading=!1,e.success?(this.isTestSuccessful=!0,this.createNotificationSuccess({message:this.$tc("billie.config.notification.validCredentials")})):this.createNotificationError({message:this.$tc("billie.config.notification.invalidCredentials")})}).catch(()=>{this.isLoading=!1,this.createNotificationError({message:this.$tc("billie.config.notification.failedToTestCredentials")})})}}})},Wpim:function(e,i,n){var t=n("8/lK");"string"==typeof t&&(t=[[e.i,t,""]]),t.locals&&(e.exports=t.locals);(0,n("SZ7m").default)("65e00961",t,!0,{})},XemQ:function(e,i){const{Component:n}=Shopware;n.override("sw-order-state-history-card",{methods:{createStateChangeErrorNotification(e){if(e.response&&e.response.data&&e.response.data.errors){"BILLIE__INVOICE_NUMBER_MISSING"===e.response.data.errors.pop().code?this.createNotificationError({message:this.$tc("billie.transition.errors.invoiceNumberMissing")}):this.$super("createStateChangeErrorNotification",e)}else this.$super("createStateChangeErrorNotification",e)}}})},eu7C:function(e,i){e.exports='{% block sw_settings_payment_detail_content_card %}\n    {% parent %}\n\n    {% block billie_payment_config_content %}\n        <sw-card v-if="paymentMethod && paymentMethod.extensions.billieConfig != null"\n                 class="billie--payment-config--card"\n                 :title="$tc(\'billie.paymentConfig.card.title\')"\n                 :isLoading="isLoading">\n            <template v-if="!isLoading">\n\n                {% block billie_payment_config_fields %}\n                    <sw-field\n                        type="number"\n                        :label="$tc(\'billie.paymentConfig.fields.duration.label\')"\n                        v-model="paymentMethod.extensions.billieConfig.duration"\n                        min="1">\n                    </sw-field>\n                {% endblock %}\n            </template>\n        </sw-card>\n    {% endblock %}\n{% endblock %}\n'},kSqU:function(e){e.exports=JSON.parse('{"billie":{"config":{"testCredentialsButton":{"text":"Zugangsdaten testen"},"notification":{"validCredentials":"Die Zugangsdaten sind korrekt!","invalidCredentials":"Die Zugangsdaten sind leider falsch!","failedToTestCredentials":"Beim Prüfen der Zugangsdaten ist ein Fehler aufgetreten!"}}}}')},nstg:function(e){e.exports=JSON.parse('{"billie":{"config":{"testCredentialsButton":{"text":"Test credentials"},"notification":{"validCredentials":"The credentials are correct!","invalidCredentials":"The credentials are unfortunately wrong!","failedToTestCredentials":"An error occurred while checking the credentials!"}}}}')}},[["IaS6","runtime","vendors-node"]]]);