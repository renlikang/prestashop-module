<?php
if (!defined('_PS_VERSION_')) {
    exit;
}
class Mytaxoverride extends Module
{
    public function __construct()
    {
        $this->name = 'mytaxoverride';
        $this->tab = 'pricing_promotion';
        $this->version = '1.0.0';
        $this->author = 'rlk';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Order Tax Group Modifier');
        $this->description = $this->l('Modify tax rules group for order details when an order is created.');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('actionObjectOrderDetailAddBefore');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function hookActionObjectOrderDetailAddBefore($params)
    {
        /** @var OrderDetail $detail */
        $detail = $params['object'];

        if (empty($detail->id_order)) {
            return;
        }

        $order = new Order((int)$detail->id_order);
        $newTaxGroup = $this->getTaxGroupByCustomer((int)$order->id_customer, $detail->id_order);

        if ($newTaxGroup) {
            $detail->id_tax_rules_group = $newTaxGroup;
        }
    }

    protected function getTaxGroupByCustomer($customerId, $orderId)
    {
        $params = self::request("http://vps-fda9991a.vps.ovh.net/admin/order/taxes/group-id?customer_id=" . $customerId . "&order_id=" . $orderId);
        if($params['success'] == true) {
            return $params['data'];
        }

        return null;
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