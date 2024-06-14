<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class Wirpay
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class Wirpay extends Base
{
    /**
     * @var string $pm payrexx payment method Id
     */
    private $pm = 'wirpay';

    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, $this->pm);
    }
}
