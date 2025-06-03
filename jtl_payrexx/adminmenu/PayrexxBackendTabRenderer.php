<?php

namespace Plugin\jtl_payrexx\adminmenu;

use JTL\Plugin\PluginInterface;
use JTL\Shop;
use JTL\DB\DbInterface;
use JTL\Smarty\JTLSmarty;
use Plugin\jtl_payrexx\Service\PayrexxApiService;

class PayrexxBackendTabRenderer
{
    private PluginInterface $plugin;

    private DbInterface $db;

    private JTLSmarty $smarty;

    public function __construct(PluginInterface $plugin, DbInterface $db)
    {
        $this->plugin = $plugin;
        $this->db = $db;
    }

    /**
     * Renders payrexx tab HTML.
     *
     * @throws \SmartyException If template rendering fails.
     */
    public function renderPayrexxTabs(string $tabName, int $menuID, JTLSmarty $smarty): string
    {
        $this->smarty = $smarty;

        if ($tabName == 'validate_configuration') {
            return $this->renderSignatureValidatePage();
        } else {
            return '';
        }
    }

    private function renderSignatureValidatePage(): string
    {
        $request = $_REQUEST;
        if (isset($request['validate'])) {
            $payrexxApiService = new PayrexxApiService();
            if ($payrexxApiService->validateSignature()) {
                $this->smarty->assign('valid', 1);
            } else {
                $this->smarty->assign('valid', 0);
            }
        }
        $langCode = ($_SESSION['AdminAccount']->language == 'de-DE') ? 'ger' : 'eng';
        $translateTexts = [
            'jtl_payrexx_signature_check_success',
            'jtl_payrexx_signature_check_fail',
            'jtl_signature_check_submit',
        ];
        foreach ($translateTexts as $lang) {
            $langTexts[$lang] = $this->plugin->getLocalization()->getTranslation($lang, $langCode);
        }
        $this->smarty->assign('postUrl', Shop::getURL() . '/admin/plugin.php?kPlugin=' . $this->plugin->getID())
                    ->assign('languageTexts', $langTexts);
        return $this->smarty->fetch($this->plugin->getPaths()->getAdminPath() . 'templates/validate_signature.tpl');
    }
}
