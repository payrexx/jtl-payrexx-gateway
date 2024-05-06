<?php declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class Payrexx
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class PostFinanceEFinance extends Base
{     
    /**
     * @var pm 
     */
    private $pm = 'post-finance-e-finance';

    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, $this->pm);
    }
}
