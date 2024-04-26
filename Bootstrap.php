<?php
/**
 * This file refers to the initialization of a plugin for the subsequent use
 *
 * @author      Payrexx
 * @copyright   Copyright (c) Payrexx
 *
 * Script: Bootstrap.php
 *
 */

namespace Plugin\jtl_payrexx;

if (file_exists(dirname(__DIR__) . '/jtl_payrexx/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/jtl_payrexx/vendor/autoload.php';
}

use JTL\Backend\Notification;
use JTL\Backend\NotificationEntry;
use JTL\Events\Dispatcher;
use JTL\Plugin\Bootstrapper;
use JTL\Plugin\Payment\Method;
use JTL\Shop;
use Plugin\jtl_payrexx\paymentmethod\Payrexx;

/**
 * Class Bootstrap
 * @package Plugin\jtl_payrexx
 */
class Bootstrap extends Bootstrapper
{
    /**
     * Boot additional services for the payment method
     */
    public function boot(Dispatcher $dispatcher): void
    {
        parent::boot($dispatcher);
        if (Shop::isFrontend()) {
            //... do whatever is neccessary in frontend
        } else {
            $dispatcher->listen('backend.notification', [$this, 'checkPayments']);
        }
    }

    /**
     * @return void
     */
    public function checkPayments(): void
    {
        foreach ($this->getPlugin()->getPaymentMethods()->getMethods() as $paymentMethod) {
            $method = Method::create($paymentMethod->getModuleID());
            if ($method instanceof Payrexx && $method->duringCheckout !== 0) {
                $note = new NotificationEntry(
                    NotificationEntry::TYPE_WARNING,
                    $paymentMethod->getName(),
                    'Die Zahlungsart kann nicht mit Zahlung vor Bestellabschluss verwendet werden',
                    Shop::getAdminURL() . '/paymentmethods?kZahlungsart=' . $method->kZahlungsart
                    . '&token=' . $_SESSION['jtl_token']
                );
                $note->setPluginId($this->getPlugin()->getPluginID());
                Notification::getInstance()->addNotify($note);
            }
        }
    }
}
