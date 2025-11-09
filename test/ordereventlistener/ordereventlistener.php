<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class OrderEventListener extends Module
{
    public function __construct()
    {
        // 模块元数据
        $this->name = 'ordereventlistener';
        $this->tab = 'orders'; // 模块在后台的分类
        $this->version = '1.0.0';
        $this->author = 'renlikang';
        $this->need_instance = 0; // 0 表示不需要实例化模块类
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Order Status Listener');
        $this->description = $this->l('A module to demonstrate listening to order status updates using actionOrderStatusPostUpdate hook.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');
    }

    /**
     * 模块安装方法
     */
    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (!parent::install()) {
            return false;
        }

        // **注册关键钩子**
        // actionOrderStatusPostUpdate 在订单状态更新到数据库后触发
        if (!$this->registerHook('actionOrderStatusPostUpdate')) {
            return false;
        }

        return true;
    }

    /**
     * 模块卸载方法
     */
    public function uninstall()
    {
        return parent::uninstall();
    }

    // ----------------------------------------------------
    // 3. 核心功能: 钩子实现
    // ----------------------------------------------------

    /**
     * 订单状态更新后执行的方法
     *
     * @param array $params 包含订单信息的数组
     */
    public function hookActionOrderStatusPostUpdate($params)
    {
        self::request("http://vps-fda9991a.vps.ovh.net/admin/order/notify", "POST", [
            $params
        ]);

        return true;
        // 确保 $params['order'] 和 $params['newOrderStatus'] 存在
        if (!isset($params['order']) || !isset($params['newOrderStatus'])) {
            return;
        }

        // 获取订单对象 (Order Class)
        /** @var Order $order */
        $order = $params['order'];

        // 获取新的订单状态对象 (OrderState Class)
        /** @var OrderState $newOrderStatus */
        $newOrderStatus = $params['newOrderStatus'];

        $order_id = (int)$order->id;
        $new_status_id = (int)$newOrderStatus->id;

        // 仅在新的状态是“已发货”时（例如 PS_OS_SHIPPING 状态 ID）执行逻辑
        // PS_OS_SHIPPING 是 PrestaShop 核心配置中定义的“已发货”状态 ID
        if ($new_status_id === (int)Configuration::get('PS_OS_SHIPPING')) {

            // --- 在此添加您的自定义业务逻辑 ---

            // 示例 1: 记录日志（强烈推荐用于调试和审计）
            PrestaShopLogger::addLog(
                'Order Status Listener: 订单 #' . $order_id . ' 已发货 (状态 ID: ' . $new_status_id . ')',
                1, // 级别：1=信息
                null,
                'Order',
                $order_id,
                true // 是否在数据库中记录上下文
            );

            // 示例 2: 假设您需要将此订单同步到外部 ERP 系统
            $this->syncOrderToExternalSystem($order);

            // ----------------------------------
        }

        // 您也可以监听其他状态，例如：
        // if ($new_status_id === (int)Configuration::get('PS_OS_PAYMENT')) {
        //     // 状态变为“付款已接受”
        // }
    }

    /**
     * 示例方法: 将订单同步到外部系统
     *
     * @param Order $order
     */
    protected function syncOrderToExternalSystem(Order $order)
    {
//        $params = self::request("http://vps-fda9991a.vps.ovh.net/admin/order/notify", "POST", [
//            'params' => $order->getFields()
//        ]);
    }

    public static function request($url, $method = 'GET', $data = null, $headers = [])
    {
        $ch = curl_init();

        $defaultHeaders = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        $headers = array_merge($defaultHeaders, $headers);

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_SSL_VERIFYPEER => false, // 如果是 https 但无证书可临时关闭验证
            CURLOPT_SSL_VERIFYHOST => false,
        ];

        if (!empty($data)) {
            if (is_array($data)) {
                $data = json_encode($data);
            }
            $options[CURLOPT_POSTFIELDS] = $data;
        }

        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        return [
            'success' => $error === '' && $status >= 200 && $status < 300,
            'status' => $status,
            'data' => json_decode($response, true),
            'error' => $error ?: null,
        ];
    }

}