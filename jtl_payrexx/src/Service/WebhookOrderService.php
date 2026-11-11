<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\Service;

use JTL\Checkout\Bestellung;
use JTL\Checkout\OrderHandler;
use JTL\DB\ReturnType;
use JTL\Plugin\Helper as PluginHelper;
use JTL\Session\Frontend;
use JTL\Shop;
use Payrexx\Models\Response\Gateway as ResponseGateway;
use Plugin\jtl_payrexx\Util\LoggerUtil;
use stdClass;
use Throwable;

/**
 * Creates the order of a payment before order completion from the shopper's payment session, so that the order
 * does not depend on the shopper returning to the shop before the session expires.
 */
class WebhookOrderService
{
    private const LOCK_TIMEOUT = 10;

    /**
     * @var string[] payment ids locked by the current request
     */
    private static array $lockedPaymentIds = [];

    private OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Returns the order of the payment session of the gateway and creates it from the shopper's session if needed.
     * The lock stays until the end of the request, so that the payment is booked under it, too.
     */
    public function createOrderFromPaymentSession(ResponseGateway $gateway, string $referenceId): ?Bestellung
    {
        $paymentId = $this->orderService->getPaymentSessionId((int) $gateway->getId(), $referenceId);
        if ($paymentId === '') {
            return null;
        }
        $session = null;
        $locked = false;
        try {
            $paymentSession = $this->getPaymentSession($paymentId);
            if ($paymentSession === null) {
                return null;
            }
            // The shopper returned first and the order was created on the success page
            if ((int) $paymentSession->kBestellung > 0) {
                return new Bestellung((int) $paymentSession->kBestellung);
            }
            // The shopper's return holds the session before the lock, so the webhook takes them in the same order
            $session = $this->restoreSession((string) $paymentSession->cSID);
            $locked = $this->lockPaymentSession($paymentId);
            if (!$locked) {
                LoggerUtil::addLog(
                    'Payrexx::createOrderFromPaymentSession(), payment session is locked: ' . $referenceId
                );
                return null;
            }
            // The shopper can have returned while the webhook waited for the session and the lock
            $paymentSession = $this->getPaymentSession($paymentId);
            if ($paymentSession === null) {
                return null;
            }
            if ((int) $paymentSession->kBestellung > 0) {
                // The restored session is written back at the end of the request and must not bring the cart back
                $session->cleanUp();
                return new Bestellung((int) $paymentSession->kBestellung);
            }
            // A failed attempt can have saved the order already, so a retry must not create it again
            $order = $this->getOrderByNumber($referenceId);
            if ($order === null) {
                if (!$this->isSessionOfGateway($gateway, $referenceId)) {
                    LoggerUtil::addLog(
                        'Payrexx::createOrderFromPaymentSession(), session does not belong to the gateway: '
                            . $referenceId
                    );
                    return null;
                }
                $orderHandler = new OrderHandler(
                    Shop::Container()->getDB(),
                    Frontend::getCustomer(),
                    Frontend::getCart()
                );
                $order = $orderHandler->finalizeOrder($referenceId);
                $orderHandler->saveUploads($order);
            }
            return $this->completeOrder($order, $paymentId, $gateway, $session);
        } catch (Throwable $e) {
            LoggerUtil::addLog(
                'Payrexx::createOrderFromPaymentSession(), order creation failed: ' . $referenceId,
                ['error' => $e->getMessage()]
            );
            if (!$locked || $session === null) {
                return null;
            }
            // The order can have been saved before the failure
            try {
                $order = $this->getOrderByNumber($referenceId);
                return $order === null ? null : $this->completeOrder($order, $paymentId, $gateway, $session);
            } catch (Throwable $e) {
                LoggerUtil::addLog(
                    'Payrexx::createOrderFromPaymentSession(), order completion failed: ' . $referenceId,
                    ['error' => $e->getMessage()]
                );
                return null;
            }
        } finally {
            // The webhook caller must not receive the shopper's session cookie, also if the order creation fails
            header_remove('Set-Cookie');
        }
    }

    /**
     * Links the created order to the payment session and the gateway like the notify.php of the shop does
     */
    private function completeOrder(
        Bestellung $order,
        string $paymentId,
        ResponseGateway $gateway,
        Frontend $session
    ): ?Bestellung {
        $session->cleanUp();
        if ((int) $order->kBestellung <= 0) {
            return null;
        }
        $paymentSessionUpdate = new stdClass();
        $paymentSessionUpdate->nBezahlt = 1;
        $paymentSessionUpdate->dZeitBezahlt = 'NOW()';
        $paymentSessionUpdate->kBestellung = (int) $order->kBestellung;
        Shop::Container()->getDB()->update('tzahlungsession', 'cZahlungsID', $paymentId, $paymentSessionUpdate);
        $this->orderService->setPaymentGatewayId(
            (int) $order->kBestellung,
            (int) $gateway->getId(),
            $order->cBestellNr
        );
        LoggerUtil::addLog('Payrexx::createOrderFromPaymentSession(), order created: ' . $order->cBestellNr);
        return $order;
    }

    /**
     * The Payrexx order with the number reserved for the gateway, if it was saved already
     */
    public function getOrderByNumber(string $orderNumber): ?Bestellung
    {
        $orders = Shop::Container()->getDB()->queryPrepared(
            'SELECT `b`.`kBestellung`, `za`.`cModulId`
                FROM `tbestellung` AS `b`
                JOIN `tzahlungsart` AS `za` ON `za`.`kZahlungsart` = `b`.`kZahlungsart`
                WHERE `b`.`cBestellNr` = :orderNumber
                ORDER BY `b`.`kBestellung`',
            [':orderNumber' => $orderNumber],
            ReturnType::ARRAY_OF_OBJECTS
        );
        $pluginId = PluginHelper::getIDByPluginID('jtl_payrexx');
        foreach ($orders as $order) {
            if ($pluginId > 0 && PluginHelper::getIDByModuleID((string) $order->cModulId) === $pluginId) {
                return (new Bestellung((int) $order->kBestellung))->fuelleBestellung(false);
            }
        }
        return null;
    }

    /**
     * Webhook and shopper return can create the order of a payment session at the same time. The lock is released
     * at the end of the request, also for persistent connections that would keep it otherwise.
     */
    public function lockPaymentSession(string $paymentId): bool
    {
        if (in_array($paymentId, self::$lockedPaymentIds, true)) {
            return true;
        }
        $lockName = 'jtl_payrexx_' . md5($paymentId);
        try {
            $result = Shop::Container()->getDB()->queryPrepared(
                'SELECT GET_LOCK(:lockName, :timeout) AS `acquired`',
                [
                    ':lockName' => $lockName,
                    ':timeout' => self::LOCK_TIMEOUT,
                ],
                ReturnType::SINGLE_OBJECT
            );
        } catch (Throwable) {
            return false;
        }
        if (!is_object($result) || (int) $result->acquired !== 1) {
            return false;
        }
        self::$lockedPaymentIds[] = $paymentId;
        register_shutdown_function(static function () use ($lockName): void {
            try {
                Shop::Container()->getDB()->queryPrepared(
                    'SELECT RELEASE_LOCK(:lockName)',
                    [':lockName' => $lockName],
                    ReturnType::SINGLE_OBJECT
                );
            } catch (Throwable) {
            }
        });
        return true;
    }

    /**
     * Webhook and shopper return book the payment of a gateway under this lock, also for orders created before the
     * payment, whose notification hash differs from the payment session. Every request takes the locks in the same
     * order to avoid deadlocks: shopper's session, payment session lock (order creation only), booking lock.
     */
    public function lockBooking(int $gatewayId): bool
    {
        return $this->lockPaymentSession('gw_' . $gatewayId);
    }

    public function getPaymentSession(string $paymentId): ?stdClass
    {
        return Shop::Container()->getDB()->select('tzahlungsession', 'cZahlungsID', $paymentId);
    }

    /**
     * Continues the shopper's session like the notify.php of the shop does for payment notifications
     */
    private function restoreSession(string $sessionId): Frontend
    {
        if (session_id() !== $sessionId) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }
            session_id($sessionId);
            $session = Frontend::getInstance(true, true);
            // The webhook caller must not receive the shopper's session cookie
            header_remove('Set-Cookie');
        } else {
            $session = Frontend::getInstance(false);
        }
        $session->deferredUpdate();
        if (is_file(\PFAD_ROOT . \PFAD_INCLUDES . 'bestellabschluss_inc.php')) {
            require_once \PFAD_ROOT . \PFAD_INCLUDES . 'bestellabschluss_inc.php';
        }
        return $session;
    }

    /**
     * The session can have moved on to another payment or cart since the gateway was created
     */
    private function isSessionOfGateway(ResponseGateway $gateway, string $referenceId): bool
    {
        $pluginId = PluginHelper::getIDByPluginID('jtl_payrexx');
        $moduleId = (string) ($_SESSION['Zahlungsart']->cModulId ?? '');
        $payrexxOrder = $_SESSION['payrexxOrder'] ?? [];
        return $pluginId > 0
            && PluginHelper::getIDByModuleID($moduleId) === $pluginId
            && (int) ($payrexxOrder['gatewayId'] ?? 0) === (int) $gateway->getId()
            && (string) ($payrexxOrder['orderNo'] ?? '') === $referenceId
            && strcasecmp((string) Frontend::getCurrency()->getCode(), (string) $gateway->getCurrency()) === 0
            && Frontend::getCart()->posTypEnthalten(\C_WARENKORBPOS_TYP_ARTIKEL);
    }
}
