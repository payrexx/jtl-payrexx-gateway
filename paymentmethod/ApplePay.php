<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class ApplePay
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class ApplePay extends Base
{
    /**
     * @var string $pm payrexx payment method Id
     */
    private $pm = 'apple-pay';

    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, $this->pm);
    }
}
