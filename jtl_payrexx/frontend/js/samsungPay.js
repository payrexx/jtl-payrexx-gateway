(function ($) {
    'use strict';

    $(document).ready(function() {
        setTimeout(function() {
            if ($(".checkout-payment-options [id^='kPlugin_'][id$='_samsungpay']").length) {
                checkSamsungPaySupport();
            }
        }, 100);
    });

    /**
     * Check the device to support samsung pay.
     */
    function checkSamsungPaySupport() {
        var ua = window.navigator.userAgent;
        if (!(ua.indexOf("Android") > 0) && !(ua.indexOf("Mobile") > 0)) {
            $(".checkout-payment-options [id^='kPlugin_'][id$='_samsungpay']").hide();
        }
    }
}(jQuery));
