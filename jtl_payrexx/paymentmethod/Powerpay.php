<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class Powerpay
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class Powerpay extends Base
{
    /**
     * @var string $pm payrexx payment method Id
     */
    private $pm = 'powerpay';

    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, $this->pm);
    }
}