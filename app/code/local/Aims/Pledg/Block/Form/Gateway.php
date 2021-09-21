<?php
/**
 * AIMS
 * @category   AIMS
 * @package    Aims_Hypnia
 * @copyright  Copyright (c) 2020 Unicode Systems. (http://www.unicodesystems.in)
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * Class Form
 */
class Aims_Pledg_Block_Form_Gateway extends Mage_Payment_Block_Form
{
    protected $_model = 'gateway';

    protected function _construct()
    {
        parent::_construct();

        $this->setTemplate('aims_pledg/checkout/method.phtml');

        $logoURL = $this->_checkAndGetSkinUrl($this->_getModel()->getLogo());

        if (!$this->_getHelper()->isAdmin() && $logoURL) {
            $logo = Mage::getConfig()->getBlockClassName('core/template');
            $logo = new $logo;
            $logo->setTemplate('aims_pledg/logo.phtml');
            $logo->setLogoSrc($logoURL);
            $logo->setMethodTitle($this->_getModel()->getTitle());

            // Add logo to the method title.
            $this->setMethodLabelAfterHtml($logo->toHtml());
        }
    }

    protected function _checkAndGetSkinUrl($fileName)
    {
        if (!$fileName) {
            $fileName = "pledg_logo.png";
        }

        $filePath = Mage::getBaseDir('media') . DS . 'aims_pledg' . DS . $fileName;
        if (! $this->_getHelper()->fileExists($filePath)) {
            Mage::log("Donot exists");
            return false;
        }

        $logoUrl = Mage::getBaseUrl('media') . 'aims_pledg/' . $fileName;

        return $logoUrl;
    }

    protected function _getModel()
    {
        return Mage::getModel('aims_pledg/method_' . $this->_model);
    }

    /**
     * Return Pledg data helper.
     *
     * @return Aims_Pledg_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('aims_pledg');
    }

    public function getAvailableMethods()
    {
        $pledgCodes = [
            'pledg_gateway_1',
            'pledg_gateway_2',
            'pledg_gateway_3',
            'pledg_gateway_4',
            'pledg_gateway_5',
            'pledg_gateway_6',
            'pledg_gateway_7',
            'pledg_gateway_8'
        ];

        $availableMethods = array();
        $grandTotal = $this->getQuote()->getGrandTotal();

        $allPaymentMethods = Mage::getModel('payment/config')->getAllMethods();
        foreach ($allPaymentMethods as $paymentMethod) {
            if(in_array($paymentMethod->getCode(), $pledgCodes) &&
                $paymentMethod->isActive() &&
                (!$paymentMethod->getSeuil() || $grandTotal >= $paymentMethod->getSeuil()))
            {
                $availableMethods[] = $paymentMethod;
            }
        }

        return $availableMethods;
    }

    protected function getQuote()
    {
        $cart = Mage::getSingleton('checkout/cart');
        return $cart->getQuote();
    }
}