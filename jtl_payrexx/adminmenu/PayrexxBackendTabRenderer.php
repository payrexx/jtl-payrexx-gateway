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
    public function renderPayrexxTabs(string $tabName, int $menuID, JTLSmarty $smarty): ?string
    {
        $this->smarty = $smarty;

        if ($tabName == 'validate_configuration') {
            if (isset($_GET['act'])) {
                $this->handlePost();
            }
            return $this->renderSignatureValidatePage();
        } else {
            return '';
        }
    }

    private function renderSignatureValidatePage(): string
    {
        $langCode = ($_SESSION['AdminAccount']->language == 'de-DE') ? 'ger' : 'eng';
        $translateTexts = [
            'jtl_payrexx_signature_check_success',
            'jtl_payrexx_signature_check_fail',
            'jtl_signature_check_submit',
            'jtl_signature_check_submit_tooltip',
            'jtl_payrexx_connect_new_integration',
            'jtl_payrexx_connect_new_integration_tooltip',
            'jtl_payrexx_no_instance',
        ];
        foreach ($translateTexts as $lang) {
            $langTexts[$lang] = $this->plugin->getLocalization()->getTranslation($lang, $langCode);
        }

        $baseUrl = Shop::getURL() . '/admin/plugin.php?kPlugin=' . $this->plugin->getID();
        $this->smarty
            ->assign('postValidateCredentialsUrl', $baseUrl . '&act=validate')
            ->assign('postConnectUrl', $baseUrl . '&act=connect')
            ->assign('payrexx_api_key', $this->plugin->getConfig()->getValue('payrexx_api_key') ?? '')
            ->assign('payrexx_instance', $this->plugin->getConfig()->getValue('payrexx_instance') ?? '')
            ->assign('languageTexts', $langTexts);
        return $this->smarty->fetch($this->plugin->getPaths()->getAdminPath() . 'templates/validate_signature.tpl');
    }

    private function handlePost()
    {
        if (isset($_GET['act']) && $_GET['act'] === 'validate') {
            $this->handleValidateSignature();
        } else if (isset($_GET['act']) && $_GET['act'] === 'connect') {
            $this->handleConnectPayrexx();
        }
    }

    private function handleValidateSignature()
    {
        $instance = $this->plugin->getConfig()->getValue('payrexx_instance');
        $apiKey = $this->plugin->getConfig()->getValue('payrexx_api_key');
        if (!$instance || !$apiKey) {
            echo json_encode(['success' => false, 'message' => 'error']);
            exit();
        }

        $payrexxApiService = new PayrexxApiService();
        if ($payrexxApiService->validateSignature()) {
            echo json_encode(['success' => true, 'message' => 'success']);
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'error']);
            exit();
        }
    }

    private function handleConnectPayrexx()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data['payrexx_instance'] || !$data['payrexx_api_key']) {
            echo json_encode(['error' => true, 'message' => 'error']);
            exit();
        }

        $currentApiKey = $this->plugin->getConfig()->getValue('payrexx_api_key');
        $currentInstance = $this->plugin->getConfig()->getValue('payrexx_instance');

        // write instance name and api key into db
        Shop::Container()->getDB()->update(
            'tplugineinstellungen',
            ['kPlugin', 'cName', 'cWert'],
            [$this->plugin->getID(), 'payrexx_instance', $currentInstance],
            (object)['cWert' => $data['payrexx_instance']]
        );
        Shop::Container()->getDB()->update(
            'tplugineinstellungen',
            ['kPlugin', 'cName', 'cWert'],
            [$this->plugin->getID(), 'payrexx_api_key', $currentApiKey],
            (object)['cWert' => $data['payrexx_api_key']]
        );

        echo json_encode(['success' => true, 'message' => 'connect']);
        exit();
    }
}
