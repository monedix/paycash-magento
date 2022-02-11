
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
                //return window.checkoutConfig.paycash_pay.description[this.item.method];
                return window.checkoutConfig.paycash_pay.description;
            },
            country: function() {
                console.log('getCountry()', window.checkoutConfig.paycash_pay.country);
                return window.checkoutConfig.paycash_pay.country;
            }
        });
    }
);