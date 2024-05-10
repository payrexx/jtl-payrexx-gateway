<?php

namespace Plugin\jtl_payrexx\Service;

use JTL\Checkout\Bestellung;
use JTL\Checkout\Zahlungsart;
use JTL\DB\ReturnType;
use JTL\Plugin\Payment\Method;
use JTL\Shop;
use Payrexx\Models\Response\Transaction;
use stdClass;

class OrderService
{
    /**
     * Set payrexx gateway id
     *
     * @param int $orderId
     * @param int $gatewayId
     */
    public function setPaymentGatewayId(int $orderId, int $gatewayId): void
    {
        $payrexxPayment = new stdClass();
        $payrexxPayment->order_id = $orderId;
        $payrexxPayment->gateway_id = $gatewayId;
        $payrexxPayment->created_at =  date('Y-m-d H:i:s');

        Shop::Container()->getDB()->insert('plugin_jtl_payrexx_payments', $payrexxPayment);
    }

    /**
     * Get gateway id
     *
     * @param int $orderId
     * @param int $gatewayId
     * @return object
     */
    public function getOrderGatewayId(int $orderId, int $gatewayId)
    {
        $info = Shop::Container()->getDB()->queryPrepared(
            'SELECT `gateway_id`, `order_id`
                FROM `plugin_jtl_payrexx_payments`
                WHERE `order_id`  = :shopOrderId and `gateway_id` = :gatewayId',
            [
                ':shopOrderId' => $orderId,
                ':gatewayId' => $gatewayId
            ],
            ReturnType::SINGLE_OBJECT
        );
        return $info->gateway_id;
    }

    /**
     * Handle transaction status.
     *
     * @param Bestellung $order
     * @param string $status
     * @param string $uuid
     * @param string $currency
     * @param int    $amount
     */
    public function handleTransactionStatus(
        Bestellung $order,
        string $status,
        string $uuid = '',
        string $currency = '',
        int $amount = 0
    ) {
        $orderNewStatus = '';
        switch ($status) {
            case Transaction::WAITING:
                $orderNewStatus = \BESTELLUNG_STATUS_IN_BEARBEITUNG;
                $comment = 'Awaiting payment';
                break;
            case Transaction::CONFIRMED:
                $orderNewStatus = \BESTELLUNG_STATUS_BEZAHLT;
                $this->addIncommingPayment($order, $uuid, $currency, $amount);
                return;
            case Transaction::REFUNDED:
                // Refunded
                $orderNewStatus = 'refunded';
                $comment = 'Payment refunded (' . $uuid . ')';
                break;
            case Transaction::PARTIALLY_REFUNDED:
                // partially refunded
                $orderNewStatus = 'partially-refunded';
                $comment = 'Payment was partially refunded (' . $uuid . ')';
                break;
            case Transaction::EXPIRED:
            case Transaction::DECLINED:
            case Transaction::ERROR:
                $orderNewStatus = \BESTELLUNG_STATUS_STORNO;
                $comment = 'Payment was failed.';
                break;
            case Transaction::CANCELLED:
                $orderNewStatus = \BESTELLUNG_STATUS_STORNO;
                $comment = 'Payment was cancelled';
                break;
        }

        
        if (empty($orderNewStatus) || !$this->transitionAllowed($order->cStatus, $orderNewStatus)) {
            return;
        }
        if (in_array($orderNewStatus, ['refunded', 'partially-refunded'])) {
            $this->updateOrderComment($order, $comment);
            return;
        }
        $this->updateOrderStatus($order, $order->cStatus, $orderNewStatus, $comment);
    }

    /**
     * @param string $currentStatus
     * @param string $newStatus
     * @return bool
     */
    private function transitionAllowed($currentStatus, $newStatus): bool
    {
        if ($currentStatus == $newStatus) {
            return false;
        }
        switch ($newStatus) {
            case \BESTELLUNG_STATUS_STORNO:
                return !in_array($currentStatus, [\BESTELLUNG_STATUS_BEZAHLT]);
            case \BESTELLUNG_STATUS_BEZAHLT:
                return !in_array($currentStatus, [\BESTELLUNG_STATUS_STORNO]);
            case \BESTELLUNG_STATUS_IN_BEARBEITUNG:
                return !in_array($currentStatus, [\BESTELLUNG_STATUS_BEZAHLT]);
        }
        return true;
    }

    /**
     * @param Bestellung $orderId
     * @param string $currentStatus
     * @param string $newStatus
     * @param string $comment
     */
    private function updateOrderStatus(Bestellung $order, $currentStatus, $newStatus, $comment = '')
    {
        Shop::Container()->getDB()->update(
            'tbestellung',
            ['kBestellung', 'cStatus'],
            [$order->kBestellung, $currentStatus],
            (object)[
                'cStatus' => $newStatus,
                'cKommentar' => $order->cKommentar . '; ' . $comment
            ]
        );
    }

    /**
     * @param int $orderId
     * @param string $comment
     */
    private function updateOrderComment($order, string $comment)
    {
        Shop::Container()->getDB()->update(
            'tbestellung',
            ['kBestellung'],
            [$order->kBestellung],
            (object)['cKommentar' => $order->cKommentar . '; ' . $comment]
        );
    }

    /**
     * Add incoming payment
     *
     * @param Bestellung $order
     * @param string $uuid
     * @param string $currency
     * @param int    $amount
     */
    private function addIncommingPayment(
        Bestellung $order,
        string $uuid,
        string $currency,
        int $amount
    ): void {
        $incommingPayment = Shop::Container()->getDB()->selectSingleRow(
            'tzahlungseingang',
            'kBestellung',
            $order->kBestellung
        );
        // check the record for incomming payment for current order
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
        $paymentMethod->sendConfirmationMail($order);
    }
}
