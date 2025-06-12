<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class CembraPay
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class CembraPay extends Base
{
    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, 'cembrapay');
    }
}
