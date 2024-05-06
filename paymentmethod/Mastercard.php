<?php declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class Payrexx
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class Mastercard extends Base
{   
    /**
     * @var pm 
     */
    private $pm = 'mastercard';

    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, $this->pm);
    }
}
