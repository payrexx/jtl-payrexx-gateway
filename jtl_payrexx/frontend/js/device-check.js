(function ($) {
    'use strict';
    $(document).ready(() => {
        setTimeout(() => {
            const $isApplePayAvailable = $(".checkout-payment-options [id^='kPlugin_'][id$='_applepay']");
            if ($isApplePayAvailable.length) {
                checkApplePaySupport($isApplePayAvailable);
            }

            const $isGooglePayAvailable = $(".checkout-payment-options [id^='kPlugin_'][id$='_googlepay']");
            if ($isGooglePayAvailable.length) {
                loadGooglePayScript(() => {
                    checkGooglePaySupport($isGooglePayAvailable);
                });
            }

            const $isSamsungPayAvailable = $(".checkout-payment-options [id^='kPlugin_'][id$='_samsungpay']");
            if ($isSamsungPayAvailable.length) {
                checkSamsungPaySupport($isSamsungPayAvailable);
            }

        }, 100);
    });

    /**
     * Check if Apple Pay is supported.
     */
    const checkApplePaySupport = ($isApplePayAvailable) => {
        if (!(window.ApplePaySession && ApplePaySession.canMakePayments())) {
            $isApplePayAvailable.hide();
        }
    };

     /**
     * Load Google Pay SDK
     */
     const loadGooglePayScript = (callback) => {
        if (typeof google === "undefined" || typeof google.payments === "undefined") {
            const script = document.createElement("script");
            script.src = "https://pay.google.com/gp/p/js/pay.js";
            script.async = true;
            script.onload = callback;
            document.head.appendChild(script);
        } else {
            callback();
        }
    };

    /**
     * Check if the device supports Google Pay.
     */
    const checkGooglePaySupport = ($isGooglePayAvailable) => {
        $isGooglePayAvailable.hide();

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
                .then((response) => {
                    if (response.result) {
                        $isGooglePayAvailable.show();
                    }
                })
                .catch((err) => {
                    console.error("Google Pay isReadyToPay Error:", err);
                });

        } catch (err) {
            console.error("Google Pay SDK Error:", err);
        }
    };

    /**
     * Check if Samsung Pay is supported.
     */
    const checkSamsungPaySupport = ($isSamsungPayAvailable) => {
        const ua = navigator.userAgent;
        if (!(ua.indexOf("Android") > 0 && ua.indexOf("Mobile") > 0)) {
            $isSamsungPayAvailable.hide();
        }
    };
}(jQuery));
