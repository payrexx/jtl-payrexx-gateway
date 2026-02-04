<?php

declare(strict_types=1);

use Plugin\jtl_payrexx\Webhook\Dispatcher;

$dispatcher = new Dispatcher();
$dispatcher->processWebhookResponse();
exit;
