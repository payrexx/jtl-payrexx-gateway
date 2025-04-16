<?php

namespace Plugin\jtl_payrexx\Util;

use Exception;
use JTL\Checkout\Bestellung;
use JTL\Checkout\Zahlungsart;
use JTL\Checkout\ZahlungsLog;

class LoggerUtil
{
    /**
     * Add log
     *
     * @param string     $message
     * @param Bestellung $order
     * @param array|null $logData
     * @param int        $logLevel
     */
    public static function addLog(
        string $message,
        Bestellung $order,
        ?array $logData,
        $logLevel = \LOGLEVEL_NOTICE
    ): void {
        try {
            $paymentMethodEntity = new Zahlungsart((int)$order->kZahlungsart);
            $moduleId = $paymentMethodEntity->cModulId ?? '';

            ZahlungsLog::add(
                $moduleId,
                $message,
                !empty($logData) ? json_encode($logData) : '',
                $logLevel
            );
        } catch(Exception $e) {
        }
    }
}
