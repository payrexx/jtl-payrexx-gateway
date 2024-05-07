<?php declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class BankTransfer
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class BankTransfer extends Base
{   
    /**
     * @var pm 
     */
    private $pm = 'bank-transfer';

    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, $this->pm);
    }
}
