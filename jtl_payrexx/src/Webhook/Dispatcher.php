<?php

declare(strict_types=1);

namespace Plugin\jtl_payrexx\Webhook;

use Exception;
use JTL\Checkout\Bestellung;
use Plugin\jtl_payrexx\Service\OrderService;

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
            $orderId = $this->data['transaction']['invoice']['referenceId'] ?? '';
            $gatewayId = $this->data['transaction']['invoice']['paymentRequestId'] ?? '';

            if (empty($orderId)) {
                $this->sendResponse('Webhook data incomplete');
            }
            $verify = $this->orderService->getOrderGatewayId((int) $orderId, (int) $gatewayId);
            if (!$verify) {
                $this->sendResponse('Verification failed');
            }

            if (!isset($this->data['transaction']['status'])) {
                $this->sendResponse('Missing transaction status');
            }
            $transaction = $this->payrexxApiService->getPayrexxTransaction(
                (int) $this->data['transaction']['id']
            );

            if (!$transaction) {
                $this->sendResponse('Transactions not found');
            }
            if ($transaction->getStatus() !== $this->data['transaction']['status']) {
                $this->sendResponse('Fraudulent transaction status');
            }
            $order = new Bestellung((int)$orderId);
            $this->orderService->handleTransactionStatus(
                $order,
                $transaction->getStatus(),
                $transaction->getUuid(),
                $transaction->getInvoice()['currencyAlpha3'],
                (int) $transaction->getInvoice()['totalAmount']
            );
            $this->sendResponse('Webhook processed successfully!');
        } catch (Exception $e) {
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
