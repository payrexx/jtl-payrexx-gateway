<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class ApplePay
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class ApplePay extends Base
{
    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, 'apple-pay');
    }
}
