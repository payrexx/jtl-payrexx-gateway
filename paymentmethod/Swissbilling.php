<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class Swissbilling
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class Swissbilling extends Base
{
    /**
     * @var string $pm payrexx payment method Id
     */
    private $pm = 'swissbilling';

    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, $this->pm);
    }
}
