<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class PostFinanceCard
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class PostFinanceCard extends Base
{
    /**
     * @var string $pm payrexx payment method Id
     */
    private $pm = 'post-finance-card';

    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, $this->pm);
    }
}
