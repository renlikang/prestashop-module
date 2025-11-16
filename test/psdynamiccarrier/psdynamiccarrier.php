<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class Psdynamiccarrier extends Module
{
    public function __construct()
    {
        $this->name = 'psdynamiccarrier';
        $this->version = '1.0.0';
        $this->author = 'renlikang';
        $this->tab = 'shipping_logistics';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Dynamic Carrier Filter');
        $this->description = $this->l('Dynamically filter carriers during checkout based on rules.');
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('actionGetDeliveryOptions');
    }

    /**
     * Hook: 动态筛选物流方法
     */
    public function hookActionGetDeliveryOptions($params)
    {
        var_dump($params);exit;
        /** @var Cart $cart */
        $cart = $params['cart'];
        $addressId = $cart->id_address_delivery;

        // -------------------------
        // 1️⃣ 获取用户配送地址
        // -------------------------
        $address = new Address($addressId);
        $postcode = $address->postcode;
        $country = $address->id_country;
        $weight = $cart->getTotalWeight();

        // -------------------------
        // 2️⃣ 你的业务规则（自行修改）
        // -------------------------

        $allowedCarriers = [];

        // 示例规则：按西班牙特殊邮区
        if (preg_match('/^07/', $postcode)) {
            // Baleares
            $allowedCarriers = [3];
        } elseif (preg_match('/^(35|38)/', $postcode)) {
            // Canarias
            $allowedCarriers = [5];
        } elseif (preg_match('/^51/', $postcode)) {
            // Ceuta
            $allowedCarriers = [8];
        } elseif (preg_match('/^52/', $postcode)) {
            // Melilla
            $allowedCarriers = [9];
        } else {
            // 默认 carrier
            $allowedCarriers = [1, 2];
        }


        // -------------------------
        // 3️⃣ 筛选 PrestaShop 提供的选项
        // -------------------------
        $options = $params['delivery_option_list'];

        foreach ($options[$addressId] as $key => $option) {
            foreach ($option['carrier_list'] as $carrier) {
                $carrierId = (int)$carrier['instance']->id;

                if (!in_array($carrierId, $allowedCarriers)) {
                    unset($options[$addressId][$key]);
                }
            }
        }

        // 更新
        $params['delivery_option_list'] = $options;

        // -------------------------
        // 4️⃣ 如果只剩一个，自动设为默认
        // -------------------------
        if (count($options[$addressId]) === 1) {
            $newKey = array_key_first($options[$addressId]);
            $params['default_carrier'] = explode(',', $newKey)[1];
        }

        return $params;
    }
}