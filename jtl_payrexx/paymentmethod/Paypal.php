<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class Paypal
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class Paypal extends Base
{
    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, 'paypal');
    }
}
