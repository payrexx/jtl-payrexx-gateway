<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class Payrexx
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class Payrexx extends Base
{
    /**
     * PayrexxPayment constructor.
     *
     * @param string $moduleID
     */
    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, '');
    }
}
