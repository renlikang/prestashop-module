<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class MyOrderAfterModule extends Module
{
    public function __construct()
    {
        $this->name = 'myorderaftermodule';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'renlikang';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Order After Listener');
        $this->description = $this->l('Listens to order creationing via hooks.');
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('actionObjectOrderAddAfter');
    }

    /**
     * 监听订单生成 Hook
     * 触发时机：当订单通过 PaymentModule 类验证成功时
     * * @param array $params 包含订单上下文信息的数组
     */
    public function hookActionObjectOrderAddAfter($params)
    {
        //$order = $params['object'];       // Order 对象
        self::request("http://vps-fda9991a.vps.ovh.net/admin/order/notify", "POST", [
            ['order_id' => $params, 'type' => 'create', 'app_reference' => 'main']
        ]);
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
