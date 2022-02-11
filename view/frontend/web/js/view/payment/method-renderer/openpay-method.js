
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Paycash_Pay/payment/openpay-offline'
            },
            getDescription: function()
            {
                console.log("1 Método getDescription() = " + window.checkoutConfig.payment.description[this.item.method]);
                console.log("2 Método getDescription() = " + window.checkoutConfig.payment.description);
                console.log("3 Método getDescription() = " + window.checkoutConfig.paycash_pay.description);
                return window.checkoutConfig.payment.description[this.item.method];
            }/*,
            country: function() {
                console.log('getCountry()', window.checkoutConfig.paycash_pay.country);
                return window.checkoutConfig.paycash_pay.country;
            }*/
        });
    }
);