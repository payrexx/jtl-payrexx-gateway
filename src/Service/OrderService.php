<?php

namespace Plugin\jtl_payrexx\Service;

use JTL\DB\ReturnType;
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
     * TO DO:
     * Handle transaction status.
     */
    public function handleTransactionStatus($orderId, $status, $transactionUuid)
    {
        $orderStatus = 'test'; // Get current order status from order id.
        switch ($status) {
            case Transaction::WAITING:
                $orderNewStatus = 'hold';
                break;
            case Transaction::CONFIRMED:
                // set as paid
                return;
            case Transaction::AUTHORIZED:
                break;
            case Transaction::REFUNDED:
                // Refunded
                break;
            case Transaction::PARTIALLY_REFUNDED:
                // partially refunded
                return;
            case Transaction::CANCELLED:
            case Transaction::EXPIRED:
            case Transaction::DECLINED:
                $orderNewStatus = 'cancelled';
                break;
            case Transaction::ERROR:
                $orderNewStatus = 'failed';
        }

        if (!$orderNewStatus || !$this->transitionAllowed($orderNewStatus, $orderStatus)) {
			return;
		}
        $this->updateOrderStatus($orderId, $orderStatus, $orderNewStatus);
    }

    /**
     * @param string $newStatus
     * @param string $oldStatus
     * @return bool
     */
    private function transitionAllowed($oldStatus, $newStatus)
    {
        return true;
    }

    private function updateOrderStatus($orderId, $currentStatus, $newStatus)
    {
        return Shop::Container()
        ->getDB()->update(
            'tbestellung',
            ['kBestellung', 'cStatus'],
            [$orderId, $currentStatus],
            (object)['cStatus' => $newStatus]
        );
    }
}