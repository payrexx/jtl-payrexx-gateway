<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class DinersClub
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class DinersClub extends Base
{
    /**
     * @var string $pm
     */
    private $pm = 'diners-club';

    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, $this->pm);
    }
}
