<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class Masterpass
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class Masterpass extends Base
{
    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, 'masterpass');
    }
}
