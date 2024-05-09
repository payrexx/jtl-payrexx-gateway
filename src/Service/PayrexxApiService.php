<?php

namespace Plugin\jtl_payrexx\Service;

use Exception;
use JTL\Checkout\Bestellung;
use JTL\Plugin\Helper as PluginHelper;
use Payrexx\Models\Request\Gateway;
use Payrexx\Models\Request\Transaction;
use Payrexx\Models\Response\Transaction as ResponseTransaction;
use Payrexx\Payrexx;

class PayrexxApiService
{
    /**
     * @var string
     */
    private $instance;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $platform;

    /**
     * @var string
     */
    private $lookAndFeelId;

    /**
     * Constructor
     *
     * @param EntityRepository $customerRepository
     * @param LoggerInterface $logger
     */
    public function __construct()
    {
        self::init();
    }

    /**
     * initialize payrexx config;
     */
    private function init()
    {
        $plugin = PluginHelper::getPluginById('jtl_payrexx');
        $config = $plugin->getConfig();
        $this->platform = trim($config->getValue('payrexx_platform'));
        $this->instance = trim($config->getValue('payrexx_instance'));
        $this->apiKey   = trim($config->getValue('payrexx_api_key'));
        $this->lookAndFeelId = trim($config->getValue('payrexx_look_and_feel_id'));
    }
    /**
     * Create Gateway
     *
     * @param Bestellung $order
     * @param string $currency
     * @param string $successUrl
     * @param string $cancelUrl
     * @param string $pm
     * @param array $basket
     * @param string $purpose
     * @return Gateway|null
     */
    public function createPayrexxGateway(
        Bestellung $order,
        string $currency,
        string $successUrl,
        string $cancelUrl,
        string $pm,
        array $basket,
        string $purpose,
    ) {
        $totalAmount = $order->fGesamtsumme;
        $orderId = $order->kBestellung;

        $payrexx = $this->getInterface();
        $gateway = new Gateway();
        $gateway->setAmount($totalAmount * 100);
        $gateway->setCurrency($currency);
        $gateway->setSuccessRedirectUrl($successUrl);
        $gateway->setFailedRedirectUrl($cancelUrl);
        $gateway->setCancelRedirectUrl($cancelUrl);
        $gateway->setSkipResultPage(true);
        $gateway->setLookAndFeelProfile($this->lookAndFeelId ?? null);

        $gateway->setPsp([]);
        $gateway->setPm([$pm]);
        $gateway->setReferenceId($orderId);
        $gateway->setValidity(15);

        $customer = $order->oKunde;
        $gateway->addField('forename', $customer->cVorname);
        $gateway->addField('surname', $customer->cNachname);
        $gateway->addField('email', $customer->cMail);
        $gateway->addField('company', $customer->cFirma);
        $gateway->addField('street', $customer->cStrasse);
        $gateway->addField('postcode', $customer->cPLZ);
        $gateway->addField('place', $customer->cLocation);
        $gateway->addField('country', $customer->cLand);
        $gateway->addField('custom_field_1', $order->kBestellung, 'Shop order ID');
        $gateway->addField('custom_field_2', $order->cBestellNr, 'Shop Order Number');

        if (!empty($basket)) {
            $gateway->setBasket($basket);
        } else {
            $gateway->setPurpose($purpose);
        }

        try {
            return $payrexx->create($gateway);
        } catch (\Payrexx\PayrexxException $e) {
            return null;
        }
    }

    /**
     * @return \Payrexx\Payrexx
     */
    public function getInterface(): \Payrexx\Payrexx
    {
        $platform = !empty($this->platform) ? $this->platform : \Payrexx\Communicator::API_URL_BASE_DOMAIN;
        return new Payrexx($this->instance, $this->apiKey, '', $platform);
    }

    /**
     * Get payrexx transaction
     *
     * @param int $payrexxTransactionId
     * @return \Payrexx\Models\Response\Transaction|null
     */
    public function getPayrexxTransaction(int $payrexxTransactionId): ?ResponseTransaction
    {
        $payrexx = $this->getInterface();

        $payrexxTransaction = new Transaction();
        $payrexxTransaction->setId($payrexxTransactionId);

        try {
            $response = $payrexx->getOne($payrexxTransaction);
            return $response;
        } catch (\Payrexx\PayrexxException $e) {
            return null;
        }
    }

    /**
     * @param integer $gatewayId
     * @return \Payrexx\Models\Request\Gateway|Exception
     */
    public function getPayrexxGateway(int $gatewayId)
    {
        $payrexx = $this->getInterface();
        $gateway = new Gateway();
        $gateway->setId($gatewayId);
        try {
            $payrexxGateway = $payrexx->getOne($gateway);
            return $payrexxGateway;
        } catch (\Payrexx\PayrexxException $e) {
            throw new Exception('No gateway found by ID: ' . $gatewayId);
        }
    }
}
