<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class SamsungPay
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class SamsungPay extends Base
{
    /**
     * @var string $pm
     */
    private $pm = 'samsung-pay';

    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, $this->pm);
    }
}
