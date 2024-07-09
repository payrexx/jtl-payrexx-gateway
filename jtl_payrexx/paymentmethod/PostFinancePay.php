<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class PostFinancePay
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class PostFinancePay extends Base
{
    /**
     * @var string $pm payrexx payment method Id
     */
    private $pm = 'post-finance-pay';

    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, $this->pm);
    }
}
