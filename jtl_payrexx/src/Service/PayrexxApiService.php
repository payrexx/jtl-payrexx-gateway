<?php

namespace Plugin\jtl_payrexx\Service;

use JTL\Checkout\Bestellung;
use JTL\Plugin\Helper as PluginHelper;
use Payrexx\Models\Request\Gateway;
use Payrexx\Models\Request\SignatureCheck;
use Payrexx\Models\Request\Transaction;
use Payrexx\Models\Response\Transaction as ResponseTransaction;
use Payrexx\Payrexx;
use Payrexx\PayrexxException;

class PayrexxApiService
{
    private string $instance;

    private string $apiKey;

    private string $platform;

    private string $lookAndFeelId;

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
     */
    public function createPayrexxGateway(
        Bestellung $order,
        string $currency,
        string $successUrl,
        string $cancelUrl,
        string $pm,
        array $basket,
        string $purpose,
        float $totalAmount,
        string $orderHash,
        array $metaData,
        string $language
    ): ?Gateway {
        $referenceId = $order->cBestellNr ?? $orderHash;

        $payrexx = $this->getInterface();
        $gateway = new Gateway();
        $gateway->setAmount((int)(string)($totalAmount * 100));
        $gateway->setCurrency($currency);
        $gateway->setSuccessRedirectUrl($successUrl);
        $gateway->setFailedRedirectUrl($cancelUrl);
        $gateway->setCancelRedirectUrl($cancelUrl);
        $gateway->setSkipResultPage(true);
        $gateway->setLookAndFeelProfile($this->lookAndFeelId ?? null);

        $gateway->setPsp([]);
        $gateway->setPm([$pm]);
        $gateway->setReferenceId($referenceId);
        $gateway->setValidity(15);

        $billingAddress = $order->oRechnungsadresse ?? $order->oKunde;
        $street = $billingAddress->cStrasse . ' ' . $billingAddress->cHausnummer;
        $gateway->addField('forename', $billingAddress->cVorname);
        $gateway->addField('surname', $billingAddress->cNachname);
        $gateway->addField('email', $billingAddress->cMail);
        $gateway->addField('company', $billingAddress->cFirma);
        $gateway->addField('street', $street);
        $gateway->addField('postcode', $billingAddress->cPLZ);
        $gateway->addField('place', $billingAddress->cOrt);
        $gateway->addField('country', $billingAddress->cLand);
        $gateway->addField('custom_field_1', $order->kBestellung, 'Shop order ID');
        $gateway->addField('custom_field_2', $order->cBestellNr, 'Shop Order Number');

        $deliveryInfo = $order->Lieferadresse;
        $deliveryStreet = $deliveryInfo->cStrasse . ' ' . $deliveryInfo->cHausnummer;
        $gateway->addField('delivery_forename', $deliveryInfo->cVorname);
        $gateway->addField('delivery_surname', $deliveryInfo->cNachname);
        $gateway->addField('delivery_company', $deliveryInfo->cFirma);
        $gateway->addField('delivery_street', $deliveryStreet);
        $gateway->addField('delivery_postcode', $deliveryInfo->cPLZ);
        $gateway->addField('delivery_place', $deliveryInfo->cOrt);
        $gateway->addField('delivery_country', $deliveryInfo->cLand);

        if (!empty($basket)) {
            $gateway->setBasket($basket);
        } else {
            $gateway->setPurpose($purpose);
        }

        if (!empty($language)) {
            // $gateway->setLanguage($language);
        }

        if (!empty($metaData)) {
            // $payrexx->setHttpHeaders($metaData);
        }

        try {
            return $payrexx->create($gateway);
        } catch (PayrexxException $e) {
            return null;
        }
    }

    public function getInterface(): \Payrexx\Payrexx
    {
        $platform = !empty($this->platform) ? $this->platform : \Payrexx\Communicator::API_URL_BASE_DOMAIN;
        return new Payrexx($this->instance, $this->apiKey, '', $platform);
    }

    public function getPayrexxTransaction(int $payrexxTransactionId): ?ResponseTransaction
    {
        $payrexx = $this->getInterface();

        $payrexxTransaction = new Transaction();
        $payrexxTransaction->setId($payrexxTransactionId);

        try {
            $response = $payrexx->getOne($payrexxTransaction);
            return $response;
        } catch (PayrexxException $e) {
            return null;
        }
    }

    public function getPayrexxGateway(int $gatewayId): ?Gateway
    {
        $payrexx = $this->getInterface();
        $gateway = new Gateway();
        $gateway->setId($gatewayId);
        try {
            return $payrexx->getOne($gateway);
        } catch (PayrexxException $e) {
            return null;
        }
    }

    public function validateSignature(): bool
    {
        $payrexx = $this->getInterface();
        try {
            $payrexx->getOne(new SignatureCheck());
            return true;
        } catch (PayrexxException $e) {
            return false;
        }
    }

    public function getTransactionByGatewayId(int $gatewayId): ?ResponseTransaction
    {
        if (!$gateway = $this->getPayrexxGateway($gatewayId)) {
            return null;
        }
        if (!in_array(
            $gateway->getStatus(),
            [
                ResponseTransaction::CONFIRMED,
                ResponseTransaction::WAITING
            ]
        )) {
            return null;
        }

        $invoices = $gateway->getInvoices();

        if (!$invoices || !$invoice = end($invoices)) {
            return null;
        }

        if (!$transactions = $invoice['transactions']) {
            return null;
        }

        return $this->getPayrexxTransaction(end($transactions)['id']);
    }

    public function deletePayrexxGateway(int $gatewayId): void
    {
        if (!$gateway = $this->getPayrexxGateway($gatewayId)) {
            return;
        }
        $invoices = $gateway->getInvoices();

        if ($invoices) {
            $invoice = end($invoices);
            if (!empty($invoice['transactions'])) {
                return;
            }
        }
        $payrexx = $this->getInterface();
        try {
            $payrexx->delete($gateway);
        } catch (\Payrexx\PayrexxException $e) {
            // no action.
        }
    }
}