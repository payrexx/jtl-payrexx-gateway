(function ($) {
    'use strict';

    $(document).ready(() => {
        setTimeout(() => {
            if ($(".checkout-payment-options [id^='kPlugin_'][id$='_samsungpay']").length) {
                checkSamsungPaySupport();
            }
        }, 100);
    });

    /**
     * Check the device to support Samsung Pay.
     */
    const checkSamsungPaySupport = () => {
        const ua = navigator.userAgent;
        if (!(ua.indexOf("Android") > 0 && ua.indexOf("Mobile") > 0)) {
            $(".checkout-payment-options [id^='kPlugin_'][id$='_samsungpay']").hide();
        }
    };
}(jQuery));
