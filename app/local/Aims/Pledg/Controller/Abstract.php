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

    protected function getOrder()
    {
        if(!$this->_order) {
            $orderId = $this->getCheckoutSession()->getLastRealOrderId();

            if (!isset($orderId)) {
                return null;
            }
            $this->_order =$this->getOrderById($orderId);
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