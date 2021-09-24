<?php
/**
 * AIMS
 * @category   AIMS
 * @package    Aims_Hypnia
 * @copyright  Copyright (c) 2020 Unicode Systems. (http://www.unicodesystems.in)
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class Aims_Pledg_Controller_Abstract extends Mage_Core_Controller_Front_Action
{
    protected $_order;

    protected function getCheckoutSession() {
        return Mage::getSingleton('checkout/session');
    }

    protected function getDataHelper() {
        return Mage::helper('aims_pledg');
    }

    protected function getCheckoutHelper() {
        return Mage::helper('aims_pledg/checkout');
    }

    protected function getGatewayConfig() {
        return $this->getOrder()->getPayment()->getMethodInstance();
    }

    protected function setOrder($order) {
        $this->_order = $order;
    }

    /**
     * @param array $validStates
     *
     * @return Mage_Sales_Model_Order
     *
     * @throws Exception
     */
    protected function getOrder($validStates)
    {
        if (!$this->_order) {
            $orderId = $this->getCheckoutSession()->getLastRealOrderId();

            if (!isset($orderId)) {
                throw new \Exception('Could not retrieve last order id');
            }
            $order = $this->getOrderById($orderId);

            if ($order === null) {
                throw new \Exception(sprintf('Could not retrieve order with id %s', $orderId));
            }

            $paymentMethod = $order->getPayment()->getMethodInstance();
            if (strstr($paymentMethod->getCode(), 'pledg_gateway_') === false) {
                throw new \Exception(sprintf('Order with method %s wrongfully accessed Pledg page', $paymentMethod->getCode()));
            }

            if (!in_array($order->getState(), $validStates)) {
                throw new \Exception(sprintf('Order with state %s wrongfully accessed Pledg page', $order->getState()));
            }

            $this->_order = $order;
        }

        return $this->_order;
    }

    protected function getOrderById($orderId)
    {
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        if (!$order->getId()) {
            return null;
        }

        return $order;
    }

    protected function getMerchantUid() {
        return $this->getGatewayConfig()->getMerchantNumber();
    }
}
