<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class VerdCash
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class VerdCash extends Base
{
    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, 'verd-cash');
    }
}
