<?php declare(strict_types=1);

namespace Plugin\jtl_payrexx\Webhook;

use Exception;
use JTL\Plugin\Plugin;
use JTL\Shop;

class Dispatcher
{
    /**
     * @var PayrexxApiService
     */
    private $payrexxApiService;

    /**
     * @param $payrexxApiService
     */
    public function __construct($payrexxApiService)
    {
        $this->payrexxApiService = $payrexxApiService;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function processWebhookResponse()
    {
        try {
            $resp = $_REQUEST;

            print_r($resp);
            exit();
			$order_id = $resp['transaction']['invoice']['referenceId'] ?? '';
			$gateway_id = $resp['transaction']['invoice']['paymentRequestId'] ?? '';

			if ( empty( $order_id ) ) {
				$this->sendResponse( 'Webhook data incomplete' );
			}
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