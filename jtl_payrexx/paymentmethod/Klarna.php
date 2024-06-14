<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class Klarna
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class Klarna extends Base
{
    /**
     * @var string $pm payrexx payment method Id
     */
    private $pm = 'klarna';

    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, $this->pm);
    }
}
