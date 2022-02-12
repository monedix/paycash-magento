
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
            isEnabled: function()
            {
                let dato = window.checkoutConfig.paycash_pay.active;
                console.log("isEnabled() = " + dato);
                return dato;
            },
            isSandbox: function()
            {
                let dato = window.checkoutConfig.paycash_pay.sandbox;
                console.log("isSandbox() = " + dato);
                return dato;
            },
            /*getTitle: function()
            {
                let dato = window.checkoutConfig.paycash_pay.title;
                console.log("getTitle() = " + dato);
                return dato;
            },*/
            getTestApikey: function()
            {
                let dato = window.checkoutConfig.paycash_pay.test_apikey;
                console.log("getTestApikey() = " + dato);
                return dato;
            },
            getProductionApikey: function()
            {
                let dato = window.checkoutConfig.paycash_pay.production_apikey;
                console.log("getProductionApikey() = " + dato);
                return dato;
            },
            getCountry: function()
            {
                let dato = window.checkoutConfig.paycash_pay.country;
                console.log("getCountry() = " + dato);
                return dato;
            },
            getValidity: function()
            {
                let dato = window.checkoutConfig.paycash_pay.validity;
                console.log("getValidity() = " + dato);
                return dato;
            },
            getDescription: function()
            {
                let dato = window.checkoutConfig.paycash_pay.description;
                console.log("getDescription() = " + dato);
                return dato;
            },
            getInstructions: function()
            {
                let dato = window.checkoutConfig.paycash_pay.instructions;
                console.log("getInstructions() = " + dato);
                return dato;
            }
        });
    }
);