<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class HeidiPay
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class HeidiPay extends Base
{
    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, 'heidipay');
    }
}
