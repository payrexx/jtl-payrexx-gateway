<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class PayByBank
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class PayByBank extends Base
{
    /**
     * @var string $pm payrexx payment method Id
     */
    private $pm = 'pay-by-bank';

    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, $this->pm);
    }
}
