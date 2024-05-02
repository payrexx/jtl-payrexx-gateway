<?php declare(strict_types=1);

namespace Plugin\jtl_payrexx\Webhook;

use Exception;
use JTL\Plugin\Plugin;
use JTL\Shop;
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
     * @param $payrexxApiService
     */
    public function __construct($payrexxApiService)
    {
        $this->payrexxApiService = $payrexxApiService;
        $this->orderService = new OrderService();
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function processWebhookResponse()
    {
        try {
            $resp = $_REQUEST;
            if (empty($resp)) {
                $this->sendResponse('Webhook data incomplete');
            }
            $orderId = $resp['transaction']['invoice']['referenceId'] ?? '';
            $gatewayId = $resp['transaction']['invoice']['paymentRequestId'] ?? '';

            if (empty($orderId)) {
                $this->sendResponse('Webhook data incomplete');
            }
            $verify = $this->orderService->getPaymentGatewayId($orderId, $gatewayId);
            if (!$verify) {
                $this->sendResponse('Verification failed');
            }

            if (!isset($resp['transaction']['status'])) {
                $this->sendResponse('Missing transaction status');
            }
            $transaction = $this->payrexxApiService->getPayrexxTransaction(
                $resp['transaction']['id']
            );

            if ($transaction->getStatus() !== $resp['transaction']['status']) {
                $this->sendResponse('Fraudulent transaction status');
            }
            $this->orderService->handleTransactionStatus(
                $orderId,
                $transaction->getStatus(),
                $transaction->getUuid()
            );
        } catch (Exception) {

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