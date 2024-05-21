<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class Masterpass
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class Masterpass extends Base
{
    /**
     * @var string $pm payrexx payment method Id
     */
    private $pm = 'masterpass';

    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, $this->pm);
    }
}
