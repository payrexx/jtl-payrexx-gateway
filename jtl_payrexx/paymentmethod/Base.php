<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\paymentmethod;

use Exception;
use JTL\Alert\Alert;
use JTL\Plugin\Payment\Method;
use JTL\Plugin\Helper as PluginHelper;
use JTL\Checkout\Bestellung;
use JTL\Checkout\OrderHandler;
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
     * @var OrderService
     */
    private $orderService;

    /**
     * @var PayrexxApiService
     */
    private $payrexxApiService;

    /**
     * PayrexxPayment constructor.
     *
     * @param string $moduleID
     */
    public function __construct(string $moduleID, $pm)
    {
        $this->pm = $pm;
        parent::__construct($moduleID);
        $this->orderService = new OrderService();
        $this->payrexxApiService = new PayrexxApiService();
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
        return parent::isValidIntern($args_arr);
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
        parent::preparePaymentProcess($order);
        if (isset($_SESSION['payrexxOrder'])) {
            $this->payrexxApiService->deletePayrexxGateway(
                (int) $_SESSION['payrexxOrder']['gatewayId']
            );
            unset($_SESSION['payrexxOrder']);
        }
        $payrexxApiService = new PayrexxApiService();
        $orderHash = $this->generateHash($order);
        $successUrl = $this->getReturnURL($order);
        $cancelUrl =  $this->getNotificationURL($orderHash) . '&cancelled';
        $currencyFactor = Frontend::getCurrency()->getConversionFactor();
        $convertedPrice = $order->fGesamtsumme * $currencyFactor;
        $totalAmount = (float)number_format($convertedPrice, 2, '.', '');
        $currency = $order->Waehrung->cISO;

        $voucherPayments = [];
        $totalVoucherAmount = 0;

        if ($order->kBestellung) {
            $incomingPayments = $this->orderService->getIncomingPayments($order);
            if (!empty($incomingPayments)) {
                foreach ($incomingPayments as $incomingPayment) {
                    if (strpos($incomingPayment->cHinweis, 'Voucher') !== false) {
                        $voucherPayments[] = $incomingPayment;
                        $totalVoucherAmount += $incomingPayment->fBetrag;
                    }
                }
            }
        }

        $totalAmount -= $totalVoucherAmount; // Deduct all voucher payments

        $basket = [];
        $purpose = '';
        $basketItems = BasketUtil::getBasketDetails($order, $voucherPayments);
        $basketAmount = BasketUtil::getBasketAmount($basketItems);
        if ($totalAmount && $totalAmount === $basketAmount) {
            $basket = $basketItems;
        } else {
            $purpose = BasketUtil::createPurposeByBasket($basketItems);
        }

        $orderNumber = '';
        if (!$order->kBestellung) { // payment before order creation
            try {
                $orderHandler = new OrderHandler(
                    Shop::Container()->getDB(),
                    Frontend::getCustomer(),
                    Frontend::getCart()
                );
                // It is available from version 5.2.0
                if (method_exists($orderHandler, 'createOrderNo')) {
                    $orderNumber = $orderHandler->createOrderNo();
                }
            } catch(Exception $e) {
            }
            $successUrl = $this->getNotificationURL($orderHash) . '&payed';
            if ($orderNumber) {
                $successUrl .= '&orderNo=' . $orderNumber;
                $order->cBestellNr = $orderNumber;
            }
        }

        $gateway = $payrexxApiService->createPayrexxGateway(
            $order,
            $currency,
            $successUrl,
            $cancelUrl,
            $this->pm,
            $basket,
            $purpose,
            $totalAmount,
            $orderHash
        );
        if ($gateway) {
            $this->orderService->setPaymentGatewayId(
                $order->kBestellung ?? null,
                $gateway->getId(),
                $order->cBestellNr ?? $orderHash,
            );
            $_SESSION['payrexxOrder'] = [
                'gatewayId' => $gateway->getId(),
                'orderHash' => $orderHash,
            ];
            if ($orderNumber) {
                $_SESSION['payrexxOrder']['orderNo'] = $orderNumber;
            }
            $lang = $_SESSION['currentLanguage']->localizedName ?? 'en';
            $redirect = $gateway->getLink();
            $lang = strtolower(substr($lang, 0, 2));
            if (in_array($lang, ['en', 'de', 'it', 'fr', 'nl', 'pt', 'tr'])) {
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
        if (isset($args['payed'])) {
            $orderNumber = $args['orderNo'] ?? '';
            if (!empty($orderNumber)) {
                $order->cBestellNr = $orderNumber;
            }
            return true;
        }
        $this->handleCancellation('jtl_before_order_payrexx_payment_cancelled');
        return false;
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
            $this->orderService->handleTransactionStatus(
                $order,
                Transaction::CANCELLED
            );
            $this->handleCancellation('jtl_after_order_payrexx_payment_cancelled');
        }

        $orderNumber = $args['orderNo'] ?? '';
        if (isset($args['sh']) &&
            isset($_SESSION['payrexxOrder']) &&
            (
                ($args['sh'] === $_SESSION['payrexxOrder']['orderHash']) ||
                (
                    !empty($orderNumber) &&
                    isset($_SESSION['payrexxOrder']['orderNo']) &&
                    $args['orderNo'] === $_SESSION['payrexxOrder']['orderNo']
                )
            )
        ) {
            $this->orderService->setPaymentGatewayId(
                $order->kBestellung,
                (int) $_SESSION['payrexxOrder']['gatewayId'],
                $order->cBestellNr
            );
            $transaction = $this->payrexxApiService->getTransactionByGatewayId(
                (int) $_SESSION['payrexxOrder']['gatewayId']
            );
            if (!$transaction) {
                return;
            }
            $this->orderService->handleTransactionStatus(
                $order,
                $transaction->getStatus(),
                $transaction->getUuid(),
                $transaction->getInvoice()['currencyAlpha3'],
                (int) $transaction->getInvoice()['totalAmount']
            );
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
        return false;
    }

    /**
     * Handle payment cancellation
     *
     * @param string $messageKey
     */
    private function handleCancellation(string $messageKey): void
    {
        $langCode = $_SESSION['currentLanguage']->iso ?? 'eng';
        $errorMessage = $this->plugin->getLocalization()->getTranslation(
            $messageKey,
            $langCode
        ) ?? '';
        if (!empty($errorMessage)) {
            $alertHelper = Shop::Container()->getAlertService();
            $alertHelper->addAlert(
                Alert::TYPE_ERROR,
                $errorMessage,
                md5($errorMessage),
                ['saveInSession' => true]
            );
        }

        $linkHelper = Shop::Container()->getLinkService();
        \header('Location: ' . $linkHelper->getStaticRoute('bestellvorgang.php') . '?editZahlungsart=1');
        exit();
    }
}
