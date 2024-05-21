<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class Reka
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class Reka extends Base
{
    /**
     * @var string $pm payrexx payment method Id
     */
    private $pm = 'reka';

    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, $this->pm);
    }
}
