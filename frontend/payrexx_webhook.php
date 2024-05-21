<?php

declare(strict_types=1);

use Plugin\jtl_payrexx\Webhook\Dispatcher;
use Plugin\jtl_payrexx\Service\PayrexxApiService;

$payrexxApiService = new PayrexxApiService();
$dispatcher = new Dispatcher($payrexxApiService);
$dispatcher->processWebhookResponse();
exit;
