<?php declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

/**
 * Class Visa
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class Visa extends Base
{   
    /**
     * @var pm 
     */
    private $pm = 'visa';

    public function __construct(string $moduleID)
    {
        parent::__construct($moduleID, $this->pm);
    }
}
