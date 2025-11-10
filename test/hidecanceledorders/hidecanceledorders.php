<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class Hidecanceledorders extends Module
{
    public function __construct()
    {
        $this->name = 'hidecanceledorders';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'ChatGPT';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Hide Cancelled Orders');
        $this->description = $this->l('Hide orders with cancelled status from customer order history.');
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayCustomerAccount')
            && $this->registerHook('actionFrontControllerSetMedia')
            && $this->installDefaultConfig();
    }

    public function uninstall()
    {
        $this->uninstallConfig();
        return parent::uninstall();
    }

    protected function installDefaultConfig()
    {
        // default list of substrings to treat as cancelled (multilang safe)
        return Configuration::updateValue('HCO_STATUS_SUBSTRINGS', json_encode([
            'canceled',
            'cancelled',
            '已取消',
            '取消'
        ]));
    }

    protected function uninstallConfig()
    {
        Configuration::deleteByName('HCO_STATUS_SUBSTRINGS');
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submitHideCancelledOrders')) {
            $value = Tools::getValue('HCO_STATUS_SUBSTRINGS');
            // Expect newline separated list
            $lines = array_filter(array_map('trim', preg_split('/\r?\n/', $value)));
            Configuration::updateValue('HCO_STATUS_SUBSTRINGS', json_encode(array_values($lines)));
            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }

        $current = json_decode(Configuration::get('HCO_STATUS_SUBSTRINGS', '[]'));
        $textarea = implode("\n", $current);

        $output .= $this->renderForm($textarea);
        return $output;
    }

    protected function renderForm($textarea)
    {
        $html = '<form method="post" class="defaultForm">';
        $html .= '<fieldset><legend>' . $this->l('Hidden status substrings') . '</legend>';
        $html .= '<p>' . $this->l('Enter substrings (one per line). If an order state name contains any of these substrings, the order will be hidden from the customer order history.') . '</p>';
        $html .= '<textarea name="HCO_STATUS_SUBSTRINGS" rows="6" style="width:100%;">' . htmlspecialchars($textarea) . '</textarea>';
        $html .= '<br/><button type="submit" name="submitHideCancelledOrders" class="btn btn-primary">' . $this->l('Save') . '</button>';
        $html .= '</fieldset></form>';
        return $html;
    }

    // Add small script/CSS only on front controllers
    public function hookActionFrontControllerSetMedia($params)
    {
        $controller = $this->context->controller;
        // only load on order history page controllers
        if (Tools::getValue('controller') === 'history' || $controller instanceof OrderHistoryController) {
            $this->context->controller->registerJavascript(
                'modules-hidecanceledorders-js',
                'modules/' . $this->name . '/views/js/hidecanceledorders.js',
                ['position' => 'bottom', 'priority' => 150]
            );
        }
    }

    // Inject inline variables via template hook so JS can read substrings
    public function hookDisplayCustomerAccount($params)
    {
        $substrings = json_decode(Configuration::get('HCO_STATUS_SUBSTRINGS', '[]'));
        $this->context->smarty->assign('hco_status_substrings', $substrings);
        return $this->display(__FILE__, 'views/templates/hook/hidecanceledorders.tpl');
    }
}