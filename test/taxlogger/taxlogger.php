<?php
/**
 * taxlogger - PrestaShop 8 Module
 * 目标：在订单详情页展示订单产品的 id_tax_rules_group 对应的具体税率。
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class TaxLogger extends Module
{
    public function __construct()
    {
        $this->name = 'taxlogger';
        $this->tab = 'front_office_features';
        $this->version = '1.0.1';
        $this->author = 'Gemini Assistant';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Order Tax Rate Details');
        $this->description = $this->l('Displays the detailed tax rates associated with products in the order details page.');
    }

    /**
     * 模块安装：只注册 displayOrderDetail Hook
     */
    public function install()
    {
        return parent::install() &&
            $this->registerHook('displayOrderDetail');
    }

    /**
     * 模块卸载
     */
    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Hook: displayOrderDetail
     * 在客户账户的订单详情页显示税率信息
     * @param array $params
     * @return string
     */
    public function hookDisplayOrderDetail($params)
    {
        // 检查 Order 对象是否存在
        if (!isset($params['order']) || !($params['order'] instanceof Order)) {
            return '';
        }

        /** @var Order $order */
        $order = $params['order'];
        $id_address_delivery = (int) $order->id_address_delivery;

        // 实例化 Address 对象，用于获取国家/州ID
        $delivery_address = new Address($id_address_delivery);
        if (!Validate::isLoadedObject($delivery_address)) {
            return '';
        }

        $id_country = (int) $delivery_address->id_country;
        $id_state = (int) $delivery_address->id_state;

        // 1. 获取订单中所有产品的 id_tax_rules_group 列表
        $db = Db::getInstance();
        $details = $db->executeS('
        SELECT DISTINCT od.id_tax_rules_group
        FROM `' . _DB_PREFIX_ . 'order_detail` od
        WHERE od.`id_order` = ' . (int) $order->id . '
        AND od.`id_tax_rules_group` > 0
    ');

        if (!$details) {
            return '';
        }

        $tax_rules_groups_data = [];
        $id_tax_rules_groups = [];

        foreach ($details as $detail) {
            $id_tax_rules_groups[] = (int) $detail['id_tax_rules_group'];
        }

        // 2. 针对每一个 id_tax_rules_group，获取其包含的具体税率
        foreach (array_unique($id_tax_rules_groups) as $id_group) {

            $tax_group = new TaxRulesGroup($id_group);

            if (!Validate::isLoadedObject($tax_group)) {
                continue;
            }

            $group_name = $tax_group->name;

            $tax_rates_list = [];

            try {
                // **最终修正：使用直接的数据库查询替换被移除的方法**
                $tax_rules = $db->executeS('
                SELECT *
                FROM `' . _DB_PREFIX_ . 'tax_rule` tr
                WHERE tr.`id_tax_rules_group` = ' . (int) $id_group . '
            ');

                if (!is_array($tax_rules)) {
                    continue;
                }

                foreach ($tax_rules as $rule) {
                    // 检查这条规则是否适用于当前的国家/州
                    if (
                        ($rule['id_country'] == 0 || $rule['id_country'] == $id_country) &&
                        ($rule['id_state'] == 0 || $rule['id_state'] == $id_state)
                    ) {
                        $tax = new Tax((int) $rule['id_tax']);
                        if (Validate::isLoadedObject($tax)) {
                            $tax_rates_list[] = [
                                'name' => $tax->name[(int) $this->context->language->id],
                                'rate' => $tax->rate,
                            ];
                        }
                    }
                }

            } catch (Exception $e) {
                // 捕获任何错误
                continue;
            }

            $tax_rules_groups_data[] = [
                'group_id' => $id_group,
                'group_name' => $group_name,
                'rates' => $tax_rates_list,
            ];
        }

        // 3. 将数据分配给 Smarty 模板
        $this->context->smarty->assign([
            'tax_rules_groups' => $tax_rules_groups_data,
            'tax_info_title' => $this->l('Product Tax Details'),
        ]);

        // 4. 渲染并返回您的模板内容
        return $this->display(__FILE__, 'order_tax_details.tpl');
    }
}