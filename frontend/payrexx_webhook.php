<?php declare(strict_types=1);

use Plugin\jtl_payrexx\Webhook\Dispatcher;
use JTL\Plugin\Helper as PluginHelper;
use Plugin\jtl_payrexx\Service\PayrexxApiService;

/** @global JTL\Plugin\PluginInterface $plugin */
$dispatcher = new Dispatcher($plugin);
$plugin = PluginHelper::getPluginById('jtl_payrexx');
$platform = trim($plugin->getConfig()->getValue('payrexx_platform'));
$instance = trim($plugin->getConfig()->getValue('payrexx_instance'));
$apiKey = trim($plugin->getConfig()->getValue('payrexx_api_key'));
$lookAndFeel = trim($plugin->getConfig()->getValue('payrexx_look_and_feel_id'));
$payrexxApiService = new PayrexxApiService(
    $platform,
    $instance,
    $apiKey,
    $lookAndFeel
);
$dispatcher->processWebhookResponse();
exit;