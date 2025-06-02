<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class PostFinancePay
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class PostFinancePay extends Base
{
    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, 'post-finance-pay');
    }
}
