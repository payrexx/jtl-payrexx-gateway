<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class SamsungPay
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class SamsungPay extends Base
{
    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, 'samsung-pay');
    }
}
