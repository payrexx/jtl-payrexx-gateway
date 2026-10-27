<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\Webhook;

use Exception;
use JTL\Checkout\Bestellung;
use Payrexx\Models\Response\Transaction;
use Plugin\jtl_payrexx\Service\OrderService;
use Plugin\jtl_payrexx\Util\LoggerUtil;

class Dispatcher
{
    /**
     * @var PayrexxApiService
     */
    private $payrexxApiService;

    /**
     * @var orderService
     */
    private $orderService;

    /**
     *  @var array $data
     */
    private $data;

    /**
     * @param $payrexxApiService
     */
    public function __construct($payrexxApiService)
    {
        $this->payrexxApiService = $payrexxApiService;
        $this->orderService = new OrderService();
        $this->data = $_POST;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function processWebhookResponse()
    {
        try {
            if (empty($this->data)) {
                $this->sendResponse('Webhook data incomplete');
            }
            LoggerUtil::addLog("Payrexx:processWebhookResponse() started", $this->data);
            if (empty($this->data['transaction']['id'])) {
                $this->sendResponse('Webhook data incomplete');
            }
            if (!isset($this->data['transaction']['status'])) {
                $this->sendResponse('Missing transaction status');
            }
            LoggerUtil::addLog(
                "Payrexx:processWebhookResponse(), process getPayrexxTransaction()",
                $this->data
            );
            $transaction = $this->payrexxApiService->getPayrexxTransaction(
                (int) $this->data['transaction']['id']
            );
            LoggerUtil::addLog("Payrexx:processWebhookResponse(), process getPayrexxTransaction() finished");

            if (!$transaction) {
                $this->sendResponse('Transactions not found');
            }
            if ($transaction->getStatus() !== $this->data['transaction']['status']) {
                $this->sendResponse('Fraudulent transaction status');
            }

            // The order comes from the fetched transaction, and the transaction must belong to a gateway stored
            // for that order. The posted gateway id is only the key to look that gateway up.
            $referenceId = $this->getReferenceIdByTransaction($transaction);
            $gatewayId = (int) ($this->data['transaction']['invoice']['paymentRequestId'] ?? 0);
            if (empty($referenceId)) {
                $this->sendResponse('Webhook data incomplete');
            }
            LoggerUtil::addLog(
                "Payrexx:processWebhookResponse(), process getOrderGatewayId(): " . $referenceId,
                $this->data
            );
            $verify = $this->orderService->getOrderGatewayId($referenceId, $gatewayId);
            LoggerUtil::addLog("Payrexx:processWebhookResponse(), process getOrderGatewayId() finished");
            $gateway = $verify ? $this->payrexxApiService->getPayrexxGateway($gatewayId) : null;
            if (
                !$gateway
                || !$this->payrexxApiService->isTransactionOfGateway($gateway, (int) $transaction->getId())
            ) {
                LoggerUtil::addLog(
                    'Payrexx:processWebhookResponse(), transaction ' . $transaction->getId()
                        . ' does not belong to a gateway of order ' . $referenceId
                );
                $this->sendResponse('Verification failed');
            }
            $order = null;
            LoggerUtil::addLog(
                "Payrexx:processWebhookResponse(), process getOrderInfoByReference(): " . $referenceId,
            );
            $orderInfo = $this->orderService->getOrderInfoByReference($referenceId);
            LoggerUtil::addLog("Payrexx:processWebhookResponse(), process getOrderInfoByReference() finished");
            if ($orderInfo !== null) {
                $order = new Bestellung((int)$orderInfo->order_id);
            }
            if ($order !== null && $order->kBestellung) {
                LoggerUtil::addLog(
                    "Payrexx:processWebhookResponse(), Process handleTransactionStatus(): " .  $order->cBestellNr,
                    $this->data
                );
                $this->orderService->handleTransactionStatus(
                    $order,
                    $transaction->getStatus(),
                    $transaction->getUuid(),
                    $transaction->getInvoice()['currencyAlpha3'],
                    (int) $transaction->getInvoice()['totalAmount'],
                    $this->payrexxApiService->getConfirmedAmount($gateway)
                );
            } else {
                LoggerUtil::addLog(
                    "Payrexx:processWebhookResponse(), Webhook received before creating order: " . $referenceId,
                    $this->data
                );
                $this->sendResponse('Webhook received before order creation,
                 Order will be created on the success page. Order Number is ' . $referenceId
                );
            }
            $this->sendResponse('Webhook processed successfully!');
        } catch (Exception $e) {
            $this->sendResponse('Error: ' . json_encode($e->getMessage()));
        }
    }

    /**
     * The gateway sets the order number or hash as referenceId. The API returns it at the top level and in the
     * invoice, depending on the transaction type.
     */
    private function getReferenceIdByTransaction(Transaction $transaction): string
    {
        $referenceId = (string) $transaction->getReferenceId();
        if ($referenceId === '') {
            $referenceId = (string) ($transaction->getInvoice()['referenceId'] ?? '');
        }
        return $referenceId;
    }

    /**
     * Returns webhook response.
     *
     * @param string $message success or error message.
     * @param array $data response data.
     * @param string|int $responseCode response code.
     */
    private function sendResponse($message, $data = [], $responseCode = 200)
    {
        $response['message'] = $message;
        if (!empty($data)) {
            $response['data'] = $data;
        }
        echo json_encode($response);
        http_response_code($responseCode);
        die;
    }
}
