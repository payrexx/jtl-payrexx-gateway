<?php

namespace Plugin\jtl_payrexx\Service;

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
     * @return \Payrexx\Payrexx
     */
    public function getInterface(): \Payrexx\Payrexx
    {
        $platform = !empty($this->platform) ? $this->platform : \Payrexx\Communicator::API_URL_BASE_DOMAIN;
        return new \Payrexx\Payrexx($this->instance, $this->apiKey, '', $platform);
    }
}