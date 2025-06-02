<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class Centi
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class Centi extends Base
{
    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, 'centi');
    }
}
