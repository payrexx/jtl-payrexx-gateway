<?php

namespace Plugin\jtl_payrexx\Service;

use JTL\Checkout\Zahlungsart;
use JTL\DB\ReturnType;
use JTL\Plugin\Payment\Method;
use JTL\Shop;
use Payrexx\Models\Response\Transaction;
use stdClass;

class OrderService {

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Set payrexx gateway id
     *
     * @param string $orderId
     * @param string $gatewayId
     */
    public function setPaymentGatewayId(string $orderId, string $gatewayId): void
    {
        $payrexxPayment = new stdClass();
        $payrexxPayment->order_id = $orderId;
        $payrexxPayment->gateway_id = $gatewayId;
        $payrexxPayment->created_at =  date('Y-m-d H:i:s');

        Shop::Container()->getDB()->insert('payrexx_payments', $payrexxPayment);
    }

    /**
     * Get gateway id
     *
     * @param string $orderId
     * @param string $gatewayId
     */
    public function getPaymentGatewayId(string $orderId, string $gatewayId)
    {
        $info = Shop::Container()->getDB()->queryPrepared(
            'SELECT `gateway_id`, `order_id` FROM `payrexx_payments` WHERE `order_id`  = :shopOrderId and `gateway_id` = :gatewayId',
            [
                ':shopOrderId' => $orderId,
                ':gatewayId' => $gatewayId
            ],
            ReturnType::SINGLE_OBJECT
        );
        return $info->gateway_id;
    }

    /**
     * @param string $orderId
     */
    public function getShopOrder(string $orderId)
    {
        return Shop::Container()->getDB()->queryPrepared(
            'SELECT * FROM `tbestellung` WHERE `cBestellNr`  = :cBestellNr',
            [':cBestellNr' => $orderId],
            ReturnType::SINGLE_OBJECT
        );
    }

    /**
     * Handle transaction status.
     *
     * @param string $orderId
     * @param string $status
     * @param string $uuid
     * @param string $currency
     * @param int    $amount
     */
    public function handleTransactionStatus(
        string $orderId,
        string $status,
        string $uuid,
        string $currency,
        int $amount
    ) {
        $orderNewStatus = '';
        switch ($status) {
            case Transaction::WAITING:
                $orderNewStatus = \BESTELLUNG_STATUS_IN_BEARBEITUNG;
                $comment = 'Awaiting payment';
                break;
            case Transaction::CONFIRMED:
                $orderNewStatus = \BESTELLUNG_STATUS_BEZAHLT;
                $this->addIncommingPayment($orderId, $uuid, $currency, $amount);
                return;
            case Transaction::REFUNDED:
                // Refunded
                $orderNewStatus = 'refunded';
                $comment = 'Payment refunded (' . $uuid .')';
                break;
            case Transaction::PARTIALLY_REFUNDED:
                // partially refunded
                $orderNewStatus = 'partially-refunded';
                $comment = 'Payment was partially refunded (' . $uuid .')';
                break;
            case Transaction::CANCELLED:
            case Transaction::EXPIRED:
            case Transaction::DECLINED:
            case Transaction::ERROR:
                $orderNewStatus = \BESTELLUNG_STATUS_STORNO;
                $comment = 'Payment was failed or cancelled by the customer';
                break;
        }

        $orderCurrentStatus = self::getShopOrder($orderId)->cStatus;
        if (empty($orderNewStatus) || !$this->transitionAllowed($orderCurrentStatus, $orderNewStatus)) {
			return;
		}
        if (in_array($orderNewStatus, ['refunded', 'partially-refunded'])) {
            $this->updateOrderComment($orderId, $comment);
            return;
        }
        $this->updateOrderStatus($orderId, $orderCurrentStatus, $orderNewStatus, $comment);
    }

    /**
     * @param string $currentStatus
     * @param string $newStatus
     * @return bool
     */
    private function transitionAllowed($currentStatus, $newStatus)
    {
        if ($currentStatus == $newStatus) {
            return false;
        }
        switch ($newStatus) {
            case \BESTELLUNG_STATUS_STORNO:
                return !in_array( $currentStatus, [\BESTELLUNG_STATUS_BEZAHLT] );
            case \BESTELLUNG_STATUS_BEZAHLT:
                return !in_array( $currentStatus, [\BESTELLUNG_STATUS_STORNO] );
            case \BESTELLUNG_STATUS_IN_BEARBEITUNG:
                return !in_array( $currentStatus, [\BESTELLUNG_STATUS_BEZAHLT] );
        }
        return true;
    }

    /**
     * @param string $orderId
     * @param string $currentStatus
     * @param string $newStatus
     * @param string $comment
     */
    private function updateOrderStatus($orderId, $currentStatus, $newStatus, $comment = '')
    {
        Shop::Container()->getDB()->update(
            'tbestellung',
            ['kBestellung', 'cStatus'],
            [$orderId, $currentStatus],
            (object)['cStatus' => $newStatus, 'cKommentar' => $comment]
        );
    }

    /**
     * @param string $orderId
     * @param string $comment
     */
    private function updateOrderComment($orderId, $comment)
    {
        Shop::Container()->getDB()->update(
            'tbestellung',
            ['kBestellung'],
            [$orderId],
            (object)['cKommentar' => $comment]
        );
    }

    /**
     * Add incoming payment
     *
     * @param string $orderId
     * @param string $uuid
     * @param string $currency
     * @param int    $amount
     */
    private function addIncommingPayment(
        $orderId,
        $uuid,
        $currency,
        $amount
    ) {
        $order = $this->getShopOrder($orderId);
        $incommingPayment = Shop::Container()->getDB()->selectSingleRow('tzahlungseingang', 'kBestellung', $orderId);
        // We check if there's record for incomming payment for current order
        if (!empty($incommingPayment->kZahlungseingang)) {
            return;
        }
        $paymentMethodEntity = new Zahlungsart((int)$order->kZahlungsart);
        $moduleId = $paymentMethodEntity->cModulId ?? '';
        $paymentMethod = new Method($moduleId);
        $paymentMethod->setOrderStatusToPaid($order);
        $incomingPayment = new stdClass();
        $incomingPayment->fBetrag = $amount / 100;
        $incomingPayment->cISO = $currency;
        $incomingPayment->cZahlungsanbieter = $order->cZahlungsartName;
        $incomingPayment->cHinweis = $uuid;
        $paymentMethod->addIncomingPayment($order, $incomingPayment);
    }
}