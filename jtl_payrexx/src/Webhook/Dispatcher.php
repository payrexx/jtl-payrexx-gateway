<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\Webhook;

use Exception;
use JTL\Checkout\Bestellung;
use Plugin\jtl_payrexx\Service\OrderService;
use Plugin\jtl_payrexx\Service\PayrexxApiService;
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

    public function __construct()
    {
        $this->payrexxApiService = new PayrexxApiService();
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
            // reference id refers order id or order hash
            $referenceId = $this->data['transaction']['invoice']['referenceId'] ?? '';
            $gatewayId = $this->data['transaction']['invoice']['paymentRequestId'] ?? '';

            if (empty($referenceId)) {
                $this->sendResponse('Webhook data incomplete');
            }
            LoggerUtil::addLog(
                "Payrexx:processWebhookResponse(), process getOrderGatewayId(): " . $referenceId,
                $this->data
            );
            $verify = $this->orderService->getOrderGatewayId($referenceId, (int) $gatewayId);
            LoggerUtil::addLog("Payrexx:processWebhookResponse(), process getOrderGatewayId() finished");
            if (!$verify) {
                $this->sendResponse('Verification failed');
            }

            if (!isset($this->data['transaction']['status'])) {
                $this->sendResponse('Missing transaction status');
            }
            LoggerUtil::addLog(
                "Payrexx:processWebhookResponse(), process getPayrexxTransaction(): " . $referenceId,
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
                    (int) $transaction->getInvoice()['totalAmount']
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
        } catch (\Throwable $e) {
            $this->sendResponse('Error: ' . json_encode($e->getMessage()));
        }
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
