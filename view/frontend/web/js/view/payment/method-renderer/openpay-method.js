
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
            country: function() {
                console.log('getCountry()', window.checkoutConfig.paycash_pay.country);
                return window.checkoutConfig.paycash_pay.country;
            }
        });
    }
);