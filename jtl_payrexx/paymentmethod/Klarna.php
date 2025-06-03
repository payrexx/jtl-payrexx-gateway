<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class Klarna
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class Klarna extends Base
{
    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, 'klarna');
    }
}
