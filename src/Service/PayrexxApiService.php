<?php

namespace Plugin\jtl_payrexx\Service;

use JTL\Checkout\Bestellung;

class PayrexxApiService {
    private $instance;
    private $apiKey;
    private $platform;
    private $lookAndFeelId;

    /**
     * Constructor
     *
     * @param EntityRepository $customerRepository
     * @param LoggerInterface $logger
     */
    public function __construct($platform, $instance, $apiKey, $lookAndFeelId)
    {
        $this->instance = $instance;
        $this->apiKey = $apiKey;
        $this->platform = $platform;
        $this->lookAndFeelId = $lookAndFeelId;
    }

    /**
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
        $orderNumber = $order->cBestellNr;

        $payrexx = $this->getInterface();
        $gateway = new \Payrexx\Models\Request\Gateway();
        $gateway->setAmount($totalAmount * 100);
        $gateway->setCurrency($currency);
        $gateway->setSuccessRedirectUrl($successUrl);
        $gateway->setFailedRedirectUrl($cancelUrl);
        $gateway->setCancelRedirectUrl($cancelUrl);
        $gateway->setSkipResultPage(true);
        $gateway->setLookAndFeelProfile($this->lookAndFeelId ?? null);

        $gateway->setPsp([]);
        $gateway->setPm([$pm]);
        $gateway->setReferenceId($orderNumber);
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
        return new \Payrexx\Payrexx($this->instance, $this->apiKey, '', $platform);
    }

    public function deleteGatewayById($gatewayId):bool 
    {
        $payrexx = $this->getInterface();

        $gateway = new \Payrexx\Models\Request\Gateway();
        $gateway->setId($gatewayId);

        try {
            $payrexx->delete($gateway);
        } catch (\Payrexx\PayrexxException $e) {
            return false;
        }
        return true;
    }

    public function getPayrexxTransaction(int $payrexxTransactionId): ?\Payrexx\Models\Response\Transaction
    {
        $payrexx = $this->getInterface();

        $payrexxTransaction = new \Payrexx\Models\Request\Transaction();
        $payrexxTransaction->setId($payrexxTransactionId);

        try {
            $response = $payrexx->getOne($payrexxTransaction);
            return $response;
        } catch (\Payrexx\PayrexxException $e) {
            return null;
        }
    }

    public function chargeTransaction($transactionId, $amount) {
        $payrexx = $this->getInterface();
        $transaction = new \Payrexx\Models\Request\Transaction();
        $transaction->setId($transactionId);
        $transaction->setAmount(floatval($amount) * 100);
        try {
            $payrexx->charge($transaction);
            return true;
        } catch (\Payrexx\PayrexxException $e) {
        }
        return false;
    }

    /**
     * @param $gatewayId
     * @return \Payrexx\Models\Request\Gateway
     */
    public function getPayrexxGateway($gatewayId)
    {
        $payrexx = $this->getInterface();
        $gateway = new \Payrexx\Models\Request\Gateway();
        $gateway->setId($gatewayId);
        try {
            $payrexxGateway = $payrexx->getOne($gateway);
            return $payrexxGateway;
        } catch (\Payrexx\PayrexxException $e) {
            throw new \Exception('No gateway found by ID: '. $gatewayId);
        }
    }
}