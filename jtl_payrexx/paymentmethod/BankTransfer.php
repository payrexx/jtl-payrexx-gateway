<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class BankTransfer
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class BankTransfer extends Base
{
    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, 'bank-transfer');
    }
}
