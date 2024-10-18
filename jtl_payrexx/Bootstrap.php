<?php

/**
 * This file refers to the initialization of a plugin for the subsequent use
 *
 * @author    Payrexx
 * @copyright Copyright(c)Payrexx
 *
 * Script: Bootstrap.php
 */

namespace Plugin\jtl_payrexx;

if (file_exists(dirname(__DIR__) . '/jtl_payrexx/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/jtl_payrexx/vendor/autoload.php';
}

use JTL\Backend\Notification;
use JTL\Backend\NotificationEntry;
use JTL\Checkout\Zahlungsart;
use JTL\Events\Dispatcher;
use JTL\Plugin\Bootstrapper;
use JTL\Plugin\Payment\Method;
use JTL\Shop;
use JTL\Smarty\JTLSmarty;
use Plugin\jtl_payrexx\adminmenu\PayrexxBackendTabRenderer;
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
            // Hooks
            $dispatcher->listen(
                'shop.hook.' . \HOOK_MAIL_PRERENDER,
                function ($args) {
                    if (isset($args['mail'])) {
                        try {
                            $email = $args['mail'];
                            $emailData = $email->getData();

                            // Check if order data and payment method exist
                            if (isset($emailData->tbestellung) && $emailData->tbestellung->kZahlungsart) {
                                $paymentMethodEntity = new Zahlungsart((int)$emailData->tbestellung->kZahlungsart);
                                $paymentProvider = $paymentMethodEntity->cAnbieter ?? '';

                                if ($paymentProvider === 'Payrexx' &&
                                    $email->getTemplate()->getId() === \MAILTEMPLATE_BESTELLBESTAETIGUNG
                                ) {
                                    $email->setToMail(''); // Set an empty recipient to stop sending
                                }
                            }
                        } catch(\Exception $e) {
                            // nothing
                        }
                    }
                },
                10
            );
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

    /**
     * Render the payrexx admin tabs in the shop backend
     *
     * @param  string    $tabName
     * @param  int       $menuID
     * @param  JTLSmarty $smarty
     * @return string
     */
    public function renderAdminMenuTab(string $tabName, int $menuID, JTLSmarty $smarty): string
    {
        $backendRenderer = new PayrexxBackendTabRenderer($this->getPlugin(), $this->getDB());
        return $backendRenderer->renderPayrexxTabs($tabName, $menuID, $smarty);
    }
}
