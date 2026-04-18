<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class FenixTrace extends Module
{
    public function __construct()
    {
        $this->name = 'fenixtrace';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Fenix Software Labs';
        $this->need_instance = false;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('FenixTrace Blockchain Connector');
        $this->description = $this->l('Register PrestaShop products on the IOTA L1 blockchain via the FenixTrace Integration Kit.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall FenixTrace?');
        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        // Create sync table
        $sql = file_get_contents(dirname(__FILE__) . '/sql/install.sql');
        $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);
        if (!Db::getInstance()->execute($sql)) {
            return false;
        }

        return parent::install()
            && $this->registerHook('displayAdminProductsExtra')
            && $this->registerHook('actionProductSave')
            && $this->registerHook('displayBackOfficeHeader')
            && Configuration::updateValue('FENIXTRACE_KIT_URL', 'http://localhost:3005')
            && Configuration::updateValue('FENIXTRACE_TEMPLATE', 'generic')
            && Configuration::updateValue('FENIXTRACE_AUTO_SYNC', '0')
            && Configuration::updateValue('FENIXTRACE_UPLOAD_DIR', '');
    }

    public function uninstall()
    {
        $sql = file_get_contents(dirname(__FILE__) . '/sql/uninstall.sql');
        $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);
        Db::getInstance()->execute($sql);

        Configuration::deleteByName('FENIXTRACE_KIT_URL');
        Configuration::deleteByName('FENIXTRACE_TEMPLATE');
        Configuration::deleteByName('FENIXTRACE_AUTO_SYNC');
        Configuration::deleteByName('FENIXTRACE_UPLOAD_DIR');

        return parent::uninstall();
    }

    /**
     * Module configuration page.
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitFenixTraceSettings')) {
            $kit_url_raw = trim((string) Tools::getValue('FENIXTRACE_KIT_URL'));
            $kit_url_valid = filter_var($kit_url_raw, FILTER_VALIDATE_URL);
            $kit_url_scheme = $kit_url_valid ? parse_url($kit_url_valid, PHP_URL_SCHEME) : '';
            if (!$kit_url_valid || !in_array($kit_url_scheme, array('http', 'https'), true)) {
                return $this->displayError(
                    $this->l('Integration Kit URL must be a valid http(s) URL.')
                ) . $this->renderForm();
            }
            Configuration::updateValue('FENIXTRACE_KIT_URL', pSQL($kit_url_valid));
            Configuration::updateValue('FENIXTRACE_UPLOAD_DIR', pSQL(Tools::getValue('FENIXTRACE_UPLOAD_DIR')));
            Configuration::updateValue('FENIXTRACE_TEMPLATE', pSQL(Tools::getValue('FENIXTRACE_TEMPLATE')));
            Configuration::updateValue('FENIXTRACE_AUTO_SYNC', (int) Tools::getValue('FENIXTRACE_AUTO_SYNC'));
            $output .= $this->displayConfirmation($this->l('Settings updated successfully.'));
        }

        // Sync single product via GET — require a CSRF-style token bound to the
        // product id so a cross-site or accidental link cannot trigger a sync.
        if (Tools::getValue('fenixtrace_sync_product')) {
            $id_product = (int) Tools::getValue('fenixtrace_sync_product');
            $token = (string) Tools::getValue('fenixtrace_sync_token');
            $expected_token = Tools::getAdminTokenLite('AdminModules');
            $expected_product_token = hash_hmac(
                'sha256',
                'fenixtrace_sync|' . $id_product,
                $expected_token ?: _COOKIE_KEY_
            );
            if (!$id_product || !hash_equals($expected_product_token, $token)) {
                $output .= $this->displayError(
                    $this->l('Invalid or missing sync token. Reload the product page and try again.')
                );
            } else {
                require_once dirname(__FILE__) . '/classes/FenixTraceApi.php';
                $result = FenixTraceApi::syncProduct($id_product);
                $output .= $result['success']
                    ? $this->displayConfirmation('Product synced! TX: ' . htmlspecialchars((string) $result['txHash'], ENT_QUOTES, 'UTF-8'))
                    : $this->displayError('Sync failed: ' . htmlspecialchars((string) ($result['error'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8'));
            }
        }

        return $output . $this->renderForm();
    }

    /**
     * Render configuration form.
     */
    protected function renderForm()
    {
        $templates = array();
        foreach (array('generic', 'agro', 'pharma', 'fashion', 'logistics', 'electronics', 'art', 'automotive', 'cosmetics', 'chemicals', 'machinery', 'custom') as $t) {
            $templates[] = array('id' => $t, 'name' => ucfirst($t));
        }

        $fields = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('FenixTrace Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Integration Kit URL'),
                        'name' => 'FENIXTRACE_KIT_URL',
                        'desc' => $this->l('URL where the FenixTrace Integration Kit is running.'),
                        'required' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Upload Directory'),
                        'name' => 'FENIXTRACE_UPLOAD_DIR',
                        'desc' => $this->l('Optional. Local path to the Integration Kit uploads/ folder.'),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Product Template'),
                        'name' => 'FENIXTRACE_TEMPLATE',
                        'options' => array(
                            'query' => $templates,
                            'id' => 'id',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Auto-sync on Save'),
                        'name' => 'FENIXTRACE_AUTO_SYNC',
                        'desc' => $this->l('Automatically send product to FenixTrace when saved.'),
                        'is_bool' => true,
                        'values' => array(
                            array('id' => 'on', 'value' => 1, 'label' => $this->l('Yes')),
                            array('id' => 'off', 'value' => 0, 'label' => $this->l('No')),
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                ),
            ),
        );

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submitFenixTraceSettings';
        $helper->fields_value = array(
            'FENIXTRACE_KIT_URL' => Configuration::get('FENIXTRACE_KIT_URL'),
            'FENIXTRACE_UPLOAD_DIR' => Configuration::get('FENIXTRACE_UPLOAD_DIR'),
            'FENIXTRACE_TEMPLATE' => Configuration::get('FENIXTRACE_TEMPLATE'),
            'FENIXTRACE_AUTO_SYNC' => Configuration::get('FENIXTRACE_AUTO_SYNC'),
        );

        return $helper->generateForm(array($fields));
    }

    /**
     * Hook: display FenixTrace tab on product edit page.
     */
    public function hookDisplayAdminProductsExtra($params)
    {
        $id_product = (int) ($params['id_product'] ?? Tools::getValue('id_product'));
        if (!$id_product) return '';

        $sync = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'fenixtrace_sync` WHERE `id_product` = ' . (int) $id_product . ' ORDER BY `id_sync` DESC'
        );

        $admin_token = Tools::getAdminTokenLite('AdminModules');
        $sync_token = hash_hmac(
            'sha256',
            'fenixtrace_sync|' . (int) $id_product,
            $admin_token ?: _COOKIE_KEY_
        );

        $this->context->smarty->assign(array(
            'fenixtrace_sync' => $sync ?: array(),
            'fenixtrace_sync_url' => $this->context->link->getAdminLink('AdminModules')
                . '&configure=' . $this->name
                . '&fenixtrace_sync_product=' . (int) $id_product
                . '&fenixtrace_sync_token=' . urlencode($sync_token),
        ));

        return $this->display(__FILE__, 'views/templates/admin/product_tab.tpl');
    }

    /**
     * Hook: auto-sync product on save.
     */
    public function hookActionProductSave($params)
    {
        if (!Configuration::get('FENIXTRACE_AUTO_SYNC')) {
            return;
        }

        $id_product = (int) ($params['id_product'] ?? 0);
        if (!$id_product) return;

        require_once dirname(__FILE__) . '/classes/FenixTraceApi.php';
        FenixTraceApi::syncProduct($id_product);
    }

    /**
     * Hook: load admin CSS.
     */
    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/fenixtrace-admin.css');
    }
}
