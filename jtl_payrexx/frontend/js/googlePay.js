(function ($) {
    'use strict';

    $(document).ready(function () {
        setTimeout(function() {
            if ($(".checkout-payment-options [id^='kPlugin_'][id$='_googlepay']").length) {
                loadGooglePayScript(function () {
                    checkGooglePaySupport();
                });
            }
        }, 100);
    });

    /**
     * Load Google Pay SDK
     */
    function loadGooglePayScript(callback) {
        if (typeof google === "undefined" || typeof google.payments === "undefined") {
            let script = document.createElement("script");
            script.src = "https://pay.google.com/gp/p/js/pay.js";
            script.async = true;
            script.onload = callback;
            document.head.appendChild(script);
        } else {
            callback();
        }
    }

    /**
     * Check if the device supports Google Pay.
     */
    function checkGooglePaySupport() {
        $(".checkout-payment-options [id^='kPlugin_'][id$='_googlepay']").hide();
        try {
            const baseRequest = {
                apiVersion: 2,
                apiVersionMinor: 0
            };
            const allowedCardNetworks = ['MASTERCARD', 'VISA'];
            const allowedCardAuthMethods = ['CRYPTOGRAM_3DS'];
            const baseCardPaymentMethod = {
                type: 'CARD',
                parameters: {
                    allowedAuthMethods: allowedCardAuthMethods,
                    allowedCardNetworks: allowedCardNetworks
                }
            };

            const isReadyToPayRequest = Object.assign({}, baseRequest);
            isReadyToPayRequest.allowedPaymentMethods = [baseCardPaymentMethod];

            const paymentsClient = new google.payments.api.PaymentsClient({
                environment: 'TEST'
            });

            paymentsClient.isReadyToPay(isReadyToPayRequest)
                .then(function (response) {
                    if (response.result) {
                        $(".checkout-payment-options [id^='kPlugin_'][id$='_googlepay']").show();
                    }
                })
                .catch(function (err) {
                    console.error("Google Pay Error:", err);
                });

        } catch (err) {
            console.error("Google Pay SDK Error:", err);
        }
    }
}(jQuery));
