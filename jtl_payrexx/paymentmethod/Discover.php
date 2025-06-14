<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class Discover
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class Discover extends Base
{
    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, 'discover');
    }
}
