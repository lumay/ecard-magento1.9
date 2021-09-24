<?php
/**
 * AIMS
 * @category   AIMS
 * @package    Aims_Hypnia
 * @copyright  Copyright (c) 2020 Unicode Systems. (http://www.unicodesystems.in)
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class Aims_Pledg_Block_Checkout_Form extends Mage_Core_Block_Template
{
    protected $_order;

    public function getLogoSrc()
    {
        $logo = Mage::getStoreConfig('design/header/logo_src');
        return $this->getSkinUrl($logo);
    }

    public function getLogoAlt()
    {
        return Mage::getStoreConfig('design/header/logo_alt');
    }

    public function getOrder()
    {
        if(!$this->_order) {
            $order = Mage::registry('pledg_order');

            if($order == null) {
                $this->_getCheckoutSession()->addError('Unable to get order from loaded order id.');
                $this->_redirect('checkout/onepage/error', array('_secure'=> false));
            }

            $this->_order = $order;
        }

        return $this->_order;
    }

    protected function _getCheckoutSession() {
        return Mage::getSingleton('checkout/session');
    }

    public function getGatewayConfig()
    {
        return Mage::helper('aims_pledg')->getGatewayConfig($this->getOrder());
    }

    public function getMerchantUid() {
        return $this->getGatewayConfig()->getMerchantNumber();
    }

    protected function _getSecreKey() {
        return $this->getGatewayConfig()->getSecretKey();
    }

    public function getGeneratedSignature() {
        $order = $this->getOrder();

        $query = array('data' => array(
            "expireIn" => strtotime("+1 day"),
            "merchantUid" => $this->getMerchantUid(),
            "amountCents" => round(($order->getTotalDue() * 100), 0),
            "email" => $order->getCustomerEmail(),
            "title" => 'Order ' . $order->getIncrementId(),
            "reference" => Mage::helper('aims_pledg')->getReferenceByIncrementId($order->getIncrementId()),
            "firstName" => $order->getCustomerFirstname(),
            "lastName" => $order->getCustomerLastname(),
            "currency" => $order->getOrderCurrencyCode(),
            "phoneNumber" => $order->getBillingAddress()->getPortable(),
            "address" => array(
                "street" => $order->getBillingAddress()->getStreet(1),
                "city" => $order->getBillingAddress()->getCity(),
                "zipcode" => $order->getBillingAddress()->getPostcode(),
                "stateProvince" => $order->getBillingAddress()->getRegion(),
                "country" => $order->getBillingAddress()->getCountryId()
            ),
            "shipping_address" => array(
                "street" => $order->getShippingAddress()->getStreet(1),
                "city" => $order->getShippingAddress()->getCity(),
                "zipcode" => $order->getShippingAddress()->getPostcode(),
                "stateProvince" => $order->getShippingAddress()->getRegion(),
                "country" => $order->getShippingAddress()->getCountryId()
            ),
            "showCloseButton" => true,
            'paymentNotificationUrl' => $this->getUrl('pledg/checkout/notifications')
        ));

        if(Mage::helper('aims_pledg/config')->getPledgIsInDebugMode()) {
            Mage::log(__METHOD__ . " " . __LINE__, null , "pledg.log", true);
            Mage::log($query, null , "pledg.log", true);
        }

        $signature = Mage::helper('aims_pledg/crypto')->generateSignature($query, $this->_getSecreKey());

        if(Mage::helper('aims_pledg/config')->getPledgIsInDebugMode()) {
            Mage::log(__METHOD__ . " " . __LINE__, null , "pledg.log", true);
            Mage::log($signature, null , "pledg.log", true);
        }

        return $signature;
    }
}