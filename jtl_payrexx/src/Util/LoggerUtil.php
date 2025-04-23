<?php

namespace Plugin\jtl_payrexx\Util;

use Exception;
use JTL\Plugin\Helper as PluginHelper;
use JTL\Shop;

class LoggerUtil
{
    /**
     * Add log
     *
     * @param string $message
     * @param array  $logData
     */
    public static function addLog(
        string $message,
        array $logData = [],
    ): void {
        try {
            $plugin = PluginHelper::getPluginById('jtl_payrexx');
            $config = $plugin->getConfig();
            if (trim($config->getValue('payrexx_log')) !== 'yes') {
                return;
            }
            if (\method_exists($plugin, 'getLogger')) {
                $logger = $plugin->getLogger();
            } else {
                // fallback for shop versions < 5.3.0
                $logger = Shop::Container()->getLogService();
            }
            $logger->info($message . (!empty($logData) ? 'data:' . json_encode($logData) : ''));
        } catch (Exception) {
        }
    }
}
