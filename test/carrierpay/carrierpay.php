<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class CarrierPay extends Module
{
    public function __construct()
    {
        $this->name = 'carrierpay';
        $this->version = '1.0.0';
        $this->author = 'renlikang';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = 'Custom Shipping Cost';
        $this->description = 'Set custom shipping cost during checkout';
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('actionGetShippingCostByCarrierId');
    }

    /**
     * 动态设置运费主逻辑
     */
    public function hookActionGetShippingCostByCarrierId($params)
    {
        return 11.01;
        /** @var Cart $cart */
        $cart = $params['cart'];
        $carrierId = (int) $params['id_carrier'];

        // 你想调整的 carrier ID
        $targetCarrier = 15;  // 改成你的 carrier ID

        if ($carrierId !== $targetCarrier) {
            return false; // 不处理其他 carrier
        }

        // ---- 在这里写你的业务逻辑 ----
        // 示例：根据重量额外加钱
        $weight = $cart->getTotalWeight();

        // 原价（Prestashop 默认计算的价格）
        $original = $params['shipping_cost'];

        // 自定义逻辑：如果重量超过3kg，额外加 $6
        if ($weight > 3) {
            $newPrice = $original + 6;
        } else {
            $newPrice = $original + 2; // 否则固定加2
        }

        return (float) $newPrice;
    }
}