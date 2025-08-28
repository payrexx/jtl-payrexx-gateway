<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class Crypto
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class Crypto extends Base
{
    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, 'crypto');
    }
}
