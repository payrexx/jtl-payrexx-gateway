<?php 
// declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

use JTL\Plugin\Payment\Method;
use JTL\Plugin\Helper as PluginHelper;
use JTL\Checkout\Bestellung;
use JTL\Shop;
use Plugin\jtl_payrexx\paymentmethod\PayrexxPaymentGateway;
use JTL\Alert\Alert;
use stdClass;
use JTL\Plugin\PluginInterface;
use JTL\Plugin\Data\PaymentMethod;
use JTL\Session\Frontend;
use Plugin\jtl_payrexx\Service\PayrexxApiService;

/**
 * Class Payrexx
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class Payrexx extends Method
{     
    /**
     * @var PayrexxPaymentGateway
     */
    private $payrexxPaymentGateway;
    
    /**
     * @var string
     */
    public $name;
    
    /**
     * @var string
     */
    public $caption;
    
    /** @var PluginInterface */
    private PluginInterface $plugin;

    /** @var PaymentMethod|null */
    private ?PaymentMethod $method;

    /** @var bool */
    private bool $payAgain;

    /**
     * PayrexxPayment constructor.
     * 
     * @param string $moduleID
     */
    public function __construct(string $moduleID)
    {
        // $this->payrexxPaymentGateway = new PayrexxPaymentGateway();
        parent::__construct($moduleID);
    }
    
    /**
     * Sets the name and caption for the payment method - required for WAWI Synchronization
     * 
     * @param  int $nAgainCheckout
     * @return $this
     */
    public function init(int $nAgainCheckout = 0): self
    {
        parent::init($nAgainCheckout);

        $pluginID       = PluginHelper::getIDByModuleID($this->moduleID);
        $this->plugin   = PluginHelper::getLoaderByPluginID($pluginID)->init($pluginID);
        $this->method   = $this->plugin->getPaymentMethods()->getMethodByID($this->moduleID);
        $this->payAgain = $nAgainCheckout > 0;

        return $this;
    }
    
    /**
     * Check the payment condition for displaying the payment on payment page
     * 
     * @param  array $args_arr
     * @return bool
     */
    public function isValidIntern(array $args_arr = []): bool
    {
        return true;
        return ($this->payrexxPaymentGateway->canPaymentMethodProcessed());
    }
    
    /**
     * Called when additional template is used
     *
     * @param  array $post
     * @return bool
     */
    public function handleAdditional(array $post): bool
    {       
        return true;
    }
    
    /**
     * Initiates the Payment process
     * 
     * @param  Bestellung $order
     * @return none
     */
    public function preparePaymentProcess(Bestellung $order): void
    {
        // $totalAmount = $order->fGesamtsumme;
        // $orderNumber = $order->cBestellNr;
        // $customerDetails = Frontend::getCustomer();
        $currency  = Frontend::getCurrency()->getCode();
        $paymentHash = $this->generateHash($order);
        $successUrl = $this->getNotificationURL($paymentHash) . '&payed';
        $cancelUrl =  $this->getReturnURL($order);
        $payrexxApiService = self::getPayrexxApiService();
        $pm = '';
        $basket = [];
        $purpose = [];
        $gateway = $payrexxApiService->createPayrexxGateway(
            $order,
            $currency,
            $successUrl,
            $cancelUrl,
            $pm,
            $basket,
            $purpose
        );
		if ($gateway) {
			\header('Location:' . $gateway->getLink());
            exit();
		}
        $orderHash = $this->generateHash($order);
		\header('Location:' . $this->getNotificationURL($orderHash));
    }
    
    /**
     * Called on notification URL
     *
     * @param  Bestellung $order
     * @param  string     $hash
     * @param  array      $args
     * @return bool
     */
    public function finalizeOrder(Bestellung $order, string $hash, array $args): bool
    {
        
    }
    
    /**
     * Called when order is finalized and created on notification URL
     *
     * @param  Bestellung $order
     * @param  string     $hash
     * @param  array      $args
     * @return none
     */
    public function handleNotification(Bestellung $order, string $hash, array $args): void
    {  
        parent::handleNotification($order, $hash, $args);

        if (isset($args['payed'])) {
            $this->addIncomingPayment($order, (object)[
                'fBetrag'           => $order->fGesamtsumme,
                'fZahlungsgebuehr'  => 0,
            ]);
            $this->setOrderStatusToPaid($order);
        }
    }

    /**
     * @inheritDoc
     */
    public function redirectOnPaymentSuccess(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function redirectOnCancel(): bool
    {
        return true;
    }


    public static function getPayrexxApiService() {
        $plugin = PluginHelper::getPluginById('jtl_payrexx');
        $platform = trim($plugin->getConfig()->getValue('payrexx_platform'));
        $instance = trim($plugin->getConfig()->getValue('payrexx_instance'));
        $apiKey = trim($plugin->getConfig()->getValue('payrexx_api_key'));
        $lookAndFeel = trim($plugin->getConfig()->getValue('payrexx_look_and_feel_id'));
        return new PayrexxApiService(
            $platform,
            $instance,
            $apiKey,
            $lookAndFeel
        );
    }
}
