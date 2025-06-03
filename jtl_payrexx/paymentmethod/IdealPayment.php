<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class IdealPayment
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class IdealPayment extends Base
{
    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, 'ideal-payment');
    }
}
