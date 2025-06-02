<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class DinersClub
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class DinersClub extends Base
{
    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, 'diners-club');
    }
}
