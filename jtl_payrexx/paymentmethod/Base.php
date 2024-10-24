<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

use JTL\Alert\Alert;
use JTL\Plugin\Payment\Method;
use JTL\Plugin\Helper as PluginHelper;
use JTL\Checkout\Bestellung;
use JTL\Plugin\PluginInterface;
use JTL\Plugin\Data\PaymentMethod;
use JTL\Session\Frontend;
use JTL\Shop;
use Payrexx\Models\Response\Transaction;
use Plugin\jtl_payrexx\Service\OrderService;
use Plugin\jtl_payrexx\Service\PayrexxApiService;
use Plugin\jtl_payrexx\Util\BasketUtil;

/**
 * Class Base
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class Base extends Method
{
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

    /**
     * @var string
     */
    private $pm;

    /**
     * PayrexxPayment constructor.
     *
     * @param string $moduleID
     */
    public function __construct(string $moduleID, $pm)
    {
        $this->pm = $pm;
        parent::__construct($moduleID);
    }

    /**
     * Sets the name and caption for the payment method
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
        return parent::isValidIntern($args_arr) && $this->duringCheckout === 0;
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
        $payrexxApiService = new PayrexxApiService();
        $paymentHash = $this->generateHash($order);
        $successUrl = $this->getNotificationURL($paymentHash);
        $cancelUrl =  $this->getNotificationURL($paymentHash) . '&cancelled';
        $basketItems = BasketUtil::getBasketDetails($order);
        $basketAmount = BasketUtil::getBasketAmount($basketItems);
        
        $currencyFactor = Frontend::getCurrency()->getConversionFactor();
        $convertedPrice = $order->fGesamtsumme * $currencyFactor;
        $totalAmount = (float)number_format($convertedPrice, 2, '.', '');
        $currency = $order->Waehrung->cISO;

        $basket = [];
        $purpose = '';
        if ($totalAmount && $totalAmount === $basketAmount) {
            $basket = $basketItems;
        } else {
            $purpose = BasketUtil::createPurposeByBasket($basketItems);
        }

        $orderHash = $this->generateHash($order);
        if (!$order->kBestellung) {
            \header('Location:' . $this->getNotificationURL($orderHash));
            exit();
        }

        $gateway = $payrexxApiService->createPayrexxGateway(
            $order,
            $currency,
            $successUrl,
            $cancelUrl,
            $this->pm,
            $basket,
            $purpose,
            $totalAmount
        );
        if ($gateway) {
            $orderService = new OrderService();
            $orderService->setPaymentGatewayId($order->kBestellung, $gateway->getId());
            $lang = $_SESSION['currentLanguage']->localizedName ?? 'en';
            $redirect = $gateway->getLink();
            if (in_array($lang, ['en', 'de'])) {
                $redirect = str_replace('?', $lang . '/?', $redirect);
            }            
            \header('Location:' . $redirect);
            exit();
        }
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
        return true;
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

        if (isset($args['cancelled'])) {
            $orderService = new OrderService();
            $orderService->handleTransactionStatus(
                $order,
                Transaction::CANCELLED
            );

            $langCode = $_SESSION['currentLanguage']->localizedName ?? 'en';
            $langText = 'jtl_payrexx_payment_cancelled';
            $errorMessage = $this->plugin->getLocalization()->getTranslation($langText, $langCode);;
            $errorMessage = $errorMessage ?? 'Your order has been canceled. Please choose a payment method to create a new order.';
            $alertHelper = Shop::Container()->getAlertService();
            $alertHelper->addAlert(Alert::TYPE_ERROR, $errorMessage, md5($errorMessage), ['saveInSession' => true]);
            $linkHelper = Shop::Container()->getLinkService();
            \header('Location: ' . $linkHelper->getStaticRoute('bestellvorgang.php') . '?editZahlungsart=1');
            exit();
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

    /**
     * @inheritdoc
     */
    public function canPayAgain(): bool
    {
        return true;
    }
}
