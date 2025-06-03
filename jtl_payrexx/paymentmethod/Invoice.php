<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class Invoice
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class Invoice extends Base
{
    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, 'invoice');
    }
}
