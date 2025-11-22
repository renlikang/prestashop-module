<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class MyOrderModule extends Module
{
    public function __construct()
    {
        $this->name = 'myordermodule';
        $this->tab = 'administration';
        $this->version = '1.0.1';
        $this->author = 'renlikang';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Order Listener');
        $this->description = $this->l('Listens to order creation via hooks.');
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('actionValidateOrder');
        // 也可以根据需要注册 actionObjectOrderAddAfter
    }

    /**
     * 监听订单生成 Hook
     * 触发时机：当订单通过 PaymentModule 类验证成功时
     * * @param array $params 包含订单上下文信息的数组
     */
    public function hookActionValidateOrder($params)
    {
        return;
        $order = $params['order'];       // Order 对象
        self::request("http://vps-fda9991a.vps.ovh.net/admin/order/notify", "POST", [
            ['order_id' => $order->id, 'type' => 'create', 'app_reference' => 'main']
        ]);
        return;

        // 1. 获取核心对象
        $cart = $params['cart'];         // Cart 对象
        $customer = $params['customer']; // Customer 对象
        $currency = $params['currency']; // Currency 对象
        $orderStatus = $params['orderStatus']; // 订单状态对象

        // 2. 简单的数据校验，确保是对像
        if (!Validate::isLoadedObject($order)) {
            return;
        }

        // 3. 业务逻辑示例：记录日志
        $orderId = $order->id;
        $totalPaid = $order->total_paid;
        $customerEmail = $customer->email;

        PrestaShopLogger::addLog(
            "New Order Created! ID: $orderId, Amount: $totalPaid, Email: $customerEmail",
            1,
            null,
            'Order',
            $orderId,
            true
        );

        // 4. 业务逻辑示例：获取订单产品列表
        // 注意：有时直接从 $order->getProducts() 获取可能不全，建议结合购物车
        $products = $cart->getProducts();

        // 在这里执行你的 API 调用或数据库操作...
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
