<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class Centi
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class Centi extends Base
{
    /**
     * @var string $pm payrexx payment method Id
     */
    private $pm = 'centi';

    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, $this->pm);
    }
}
