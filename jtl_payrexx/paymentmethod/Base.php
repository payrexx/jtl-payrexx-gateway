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
use JTL\Session\Frontend;
use JTL\Shop;
use Payrexx\Models\Response\Transaction;
use Plugin\jtl_payrexx\Service\OrderService;
use Plugin\jtl_payrexx\Service\PayrexxApiService;
use Plugin\jtl_payrexx\Service\WebhookOrderService;
use Plugin\jtl_payrexx\Util\BasketUtil;
use Plugin\jtl_payrexx\Util\LoggerUtil;

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

/**
 * Class Base
 * @package Plugin\jtl_payrexx\paymentmethod
 */
class Base extends Method
{
    private PluginInterface $plugin;

    private string $pm;

    private OrderService $orderService;

    private PayrexxApiService $payrexxApiService;

    private WebhookOrderService $webhookOrderService;

    private $supportedLang = ['en', 'de', 'it', 'fr', 'nl', 'pt', 'tr'];

    /**
     * PayrexxPayment constructor.
     */
    public function __construct(string $moduleID, string $pm)
    {
        $this->pm = $pm;
        parent::__construct($moduleID);
        $this->orderService = new OrderService();
        $this->payrexxApiService = new PayrexxApiService();
        $this->webhookOrderService = new WebhookOrderService($this->orderService);
    }

    public function init(int $nAgainCheckout = 0): self
    {
        parent::init($nAgainCheckout);

        $pluginID     = PluginHelper::getIDByModuleID($this->moduleID);
        $this->plugin = PluginHelper::getLoaderByPluginID($pluginID)->init($pluginID);

        return $this;
    }

    public function isValidIntern(array $args_arr = []): bool
    {
        return parent::isValidIntern($args_arr);
    }

    public function handleAdditional(array $post): bool
    {
        return true;
    }

    /**
     * Initiates the Payment process
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
        $incomingPayments = $this->orderService->getIncomingPayments($order);
        $basketItems = BasketUtil::getBasketDetails($order, $incomingPayments);
        $basketAmount = BasketUtil::getBasketAmount($basketItems);

        $currencyFactor = Frontend::getCurrency()->getConversionFactor();
        // Part of the order can already be paid by another provider, e.g. a JTL-Vouchers gift voucher
        $convertedPrice = $order->fGesamtsumme * $currencyFactor
            - $this->orderService->sumIncomingPayments($incomingPayments);
        $totalAmount = (float)number_format($convertedPrice, 2, '.', '');
        $currency = $order->Waehrung->cISO;

        $basket = [];
        $purpose = '';
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

        $metaData = [];
        try {
            $metaData = [
                'X-Shop-Version'   => (string) Shop::getShopDatabaseVersion(),
                'X-Plugin-Version' => (string) $this->plugin->getMeta()->getVersion(),
            ];
        } catch (Exception $e) {}

        $gatewayLang = '';
        $lang = $_SESSION['currentLanguage']->getIso639() ?? 'en';
        $lang = strtolower(substr($lang, 0, 2));
        if (in_array($lang, $this->supportedLang)) {
            $gatewayLang = $lang;
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
            $orderHash,
            $metaData,
            $gatewayLang
        );
        if ($gateway) {
            $this->orderService->setPaymentGatewayId(
                $order->kBestellung ?? null,
                $gateway->getId(),
                $order->cBestellNr ?? $orderHash,
                // The webhook creates the order from this payment session if the shopper does not return
                $order->kBestellung ? null : ltrim($orderHash, '_'),
            );
            $_SESSION['payrexxOrder'] = [
                'gatewayId' => $gateway->getId(),
                'orderHash' => $orderHash,
            ];
            if ($orderNumber) {
                $_SESSION['payrexxOrder']['orderNo'] = $orderNumber;
            }
            \header('Location:' . $gateway->getLink());
            exit();
        }
        \header('Location:' . $this->getNotificationURL($orderHash));
    }

    /**
     * Called on notification URL
     */
    public function finalizeOrder(Bestellung $order, string $hash, array $args): bool
    {
        if (isset($args['payed'])) {
            $this->redirectIfWebhookCreatedOrder($hash, $args);
            $orderNumber = $args['orderNo'] ?? '';
            if (!empty($orderNumber)) {
                $order->cBestellNr = $orderNumber;
            }
            LoggerUtil::addLog(
                'Payrexx::finalizeOrder(), Payment success (Payment before order completion) ' . $orderNumber
            );
            return true;
        }
        $this->handleCancellation('jtl_before_order_payrexx_payment_cancelled');
        return false;
    }

    /**
     * Called when order is finalized and created on notification URL
     */
    public function handleNotification(Bestellung $order, string $hash, array $args): void
    {
        parent::handleNotification($order, $hash, $args);

        if (isset($args['cancelled'])) {
            $this->orderService->handleTransactionStatus(
                $order,
                Transaction::CANCELLED
            );

            LoggerUtil::addLog(
                'Payrexx::handleNotification(), Payment was cancelled: ' . $order->cBestellNr
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
            // The webhook can book the same payment at the same time
            $this->lockPaymentSession(ltrim($hash, '_'));
            $gatewayId = (int) $_SESSION['payrexxOrder']['gatewayId'];
            $this->orderService->setPaymentGatewayId(
                $order->kBestellung,
                $gatewayId,
                $order->cBestellNr
            );
            $transaction = $this->payrexxApiService->getTransactionByGatewayId($gatewayId);
            $gateway = $this->payrexxApiService->getPayrexxGateway($gatewayId);
            if (!$transaction || !$gateway) {
                return;
            }
            LoggerUtil::addLog(
                'Payrexx::handleNotification(), Process handleTransactionStatus(): ' . $order->cBestellNr
            );
            $this->orderService->handleTransactionStatus(
                $order,
                $transaction->getStatus(),
                $transaction->getUuid(),
                $transaction->getInvoice()['currencyAlpha3'],
                (int) $transaction->getInvoice()['totalAmount'],
                $this->payrexxApiService->getConfirmedAmount($gateway)
            );
        }
    }

    public function redirectOnPaymentSuccess(): bool
    {
        return true;
    }

    public function redirectOnCancel(): bool
    {
        return true;
    }

    public function canPayAgain(): bool
    {
        return false;
    }

    /**
     * The webhook can have created the order of the payment session already. The lock stays until the end of the
     * request, so that the shop creates the order under it otherwise.
     */
    private function redirectIfWebhookCreatedOrder(string $paymentId, array $args): void
    {
        $this->lockPaymentSession($paymentId);
        $paymentSession = $this->webhookOrderService->getPaymentSession($paymentId);
        $orderId = (int) ($paymentSession->kBestellung ?? 0);
        if ($orderId > 0) {
            $order = new Bestellung($orderId);
            $order->fuelleBestellung(false);
        } else {
            // The webhook can have saved the order and failed before it updated the payment session
            // Only the order number reserved in this payment session, never one from the URL alone
            $orderNumber = (string) ($_SESSION['payrexxOrder']['orderNo'] ?? '');
            $order = $orderNumber !== '' && $orderNumber === (string) ($args['orderNo'] ?? '')
                ? $this->webhookOrderService->getOrderByNumber($orderNumber)
                : null;
            if ($order === null) {
                return;
            }
        }
        LoggerUtil::addLog('Payrexx::finalizeOrder(), order already created by the webhook: ' . $order->kBestellung);
        Frontend::getInstance()->cleanUp();
        $this->handleNotification($order, '_' . $paymentId, $args);
        \header('Location: ' . $this->getReturnURL($order));
        exit();
    }

    private function lockPaymentSession(string $paymentId): void
    {
        if (!$this->webhookOrderService->lockPaymentSession($paymentId)) {
            LoggerUtil::addLog('Payrexx: payment session lock not acquired');
        }
    }

    /**
     * Handle payment cancellation
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
