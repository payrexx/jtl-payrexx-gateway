<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class Maestro
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class Maestro extends Base
{
    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, 'maestro');
    }
}
