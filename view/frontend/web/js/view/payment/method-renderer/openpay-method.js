
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
                template: 'Openpay_Stores/payment/openpay-offline'
            },
            country: function() {
                console.log('getCountry()', window.checkoutConfig.openpay_stores.country);
                return window.checkoutConfig.openpay_stores.country;
            }
        });
    }
);