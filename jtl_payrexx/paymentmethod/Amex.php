<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class Amex
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class Amex extends Base
{
    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, 'american-express');
    }
}
