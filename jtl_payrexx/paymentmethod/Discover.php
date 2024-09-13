<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class Discover
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class Discover extends Base
{
    /**
     * @var string $pm
     */
    private $pm = 'discover';

    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, $this->pm);
    }
}
