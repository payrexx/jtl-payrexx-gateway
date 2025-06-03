<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class Mastercard
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class Mastercard extends Base
{
    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, 'mastercard');
    }
}
