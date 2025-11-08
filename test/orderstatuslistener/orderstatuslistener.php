<?php
if (!defined('PS_VERSION')) {
    exit;
}

use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

class OrderStatusListener extends Module
{
    public function __construct()
    {
        $this->name = 'ps_orderstatuslistener';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'You';
        $this->need_instance = 0;
        parent::__construct();
        $this->displayName = $this->l('Order Status Listener');
        $this->description = $this->l('Listen to order status changes and log them.');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('actionOrderStatusPostUpdate');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
        try {
            $order = isset($params['order']) ? $params['order'] : null;
            $newState = $params['newOrderStatus'] ?? $params['new_order_state'] ?? null;
            $oldState = $params['oldOrderStatus'] ?? $params['old_order_state'] ?? null;
            $orderId = $order ? (int)$order->id : ($params['id_order'] ?? null);
            $newStateId = is_object($newState) ? ($newState->id ?? null) : $newState;
            $oldStateId = is_object($oldState) ? ($oldState->id ?? null) : $oldState;

            $message = sprintf(
                '[%s] Order %s status changed from %s to %s',
                date('Y-m-d H:i:s'),
                $orderId ?? 'unknown',
                $oldStateId ?? 'unknown',
                $newStateId ?? 'unknown'
            );

            // 使用 PrestaShop 日志
            PrestaShopLogger::addLog($message, 1, null, 'Order', $orderId);
        } catch (Exception $e) {
            PrestaShopLogger::addLog('ps_orderstatuslistener error: ' . $e->getMessage(), 3);
        }
    }
}
