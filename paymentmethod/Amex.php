<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class Amex
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class Amex extends Base
{
    /**
     * @var string $pm payrexx payment method Id
     */
    private $pm = 'american-express';

    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, $this->pm);
    }
}
