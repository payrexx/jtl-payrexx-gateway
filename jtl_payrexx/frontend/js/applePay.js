(function ($) {
    'use strict';

    $(document).ready(() => {
        setTimeout(() => {
            if ($(".checkout-payment-options [id^='kPlugin_'][id$='_applepay']").length) {
                checkApplePaySupport();
            }
        }, 100);
    });

    /**
     * Check the device to support apple pay.
     */
    const checkApplePaySupport = () => {
        if ((window.ApplePaySession && ApplePaySession.canMakePayments()) !== true) {
            $(".checkout-payment-options [id^='kPlugin_'][id$='_applepay']").hide();
        }
    };
}(jQuery));
