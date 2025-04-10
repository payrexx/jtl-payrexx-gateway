(function ($) {
    'use strict';
    $(document).ready(() => {
        setTimeout(() => {
            const $applePay = $(".checkout-payment-options [id^='kPlugin_'][id$='_applepay']");
            if ($applePay.length) {
                checkApplePaySupport($applePay);
            }

            const $googlePay = $(".checkout-payment-options [id^='kPlugin_'][id$='_googlepay']");
            if ($googlePay.length) {
                loadGooglePayScript(() => {
                    checkGooglePaySupport($googlePay);
                });
            }

            const $samsungPay = $(".checkout-payment-options [id^='kPlugin_'][id$='_samsungpay']");
            if ($samsungPay.length) {
                checkSamsungPaySupport($samsungPay);
            }

        }, 100);
    });

    /**
     * Check if Apple Pay is supported.
     */
    const checkApplePaySupport = ($applePay) => {
        if (!(window.ApplePaySession && ApplePaySession.canMakePayments())) {
            $applePay.hide();
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
    const checkGooglePaySupport = ($googlePay) => {
        $googlePay.hide();

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
                        $googlePay.show();
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
    const checkSamsungPaySupport = ($samsungPay) => {
        const ua = navigator.userAgent;
        if (!(ua.indexOf("Android") > 0 && ua.indexOf("Mobile") > 0)) {
            $samsungPay.hide();
        }
    };
}(jQuery));
