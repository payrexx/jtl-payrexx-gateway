<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class CembraPay
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class CembraPay extends Base
{
    /**
     * @var string $pm payrexx payment method Id
     */
    private $pm = 'cembrapay';

    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, $this->pm);
    }
}
