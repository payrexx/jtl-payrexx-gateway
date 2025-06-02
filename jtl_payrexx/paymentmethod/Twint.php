<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class Twint
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class Twint extends Base
{
    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, 'twint');
    }
}
