<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class GooglePay
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class GooglePay extends Base
{
    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, 'google-pay');
    }
}
