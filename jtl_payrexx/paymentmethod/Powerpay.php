<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class Powerpay
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class Powerpay extends Base
{
    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, 'powerpay');
    }
}
