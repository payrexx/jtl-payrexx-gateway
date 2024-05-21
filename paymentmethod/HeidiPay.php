<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class HeidiPay
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class HeidiPay extends Base
{
    /**
     * @var string $pm payrexx payment method Id
     */
    private $pm = 'heidipay';

    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, $this->pm);
    }
}
