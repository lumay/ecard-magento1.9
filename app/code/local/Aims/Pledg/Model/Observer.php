<?php
/**
 * AIMS
 * @category   AIMS
 * @package    Aims_Hypnia
 * @copyright  Copyright (c) 2020 Unicode Systems. (http://www.unicodesystems.in)
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class Aims_Pledg_Model_Observer
{
    /**
     * Check if we must to show Pledg payment method
     * @param Varien_Event_Observer $observer
     */
    public function paymentMethodAvailable(Varien_Event_Observer $observer)
    {
        $mainPlegeCode = "pledg_gateway";
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

        $cart = Mage::getSingleton('checkout/cart');
        $grandTotal = $cart->getQuote()->getGrandTotal();

        if($observer->getEvent()->getMethodInstance()->getCode() == $mainPlegeCode) {
            $checkResult = $observer->getEvent()->getResult();
            if(Mage::helper('aims_pledg/config')->getPledgIsActive()) {
                $allPaymentMethods = Mage::getModel('payment/config')->getAllMethods();
                foreach ($allPaymentMethods as $paymentMethod) {
                    if(in_array($paymentMethod->getCode(), $pledgCodes) &&
                        $paymentMethod->isActive() &&
                        (!$paymentMethod->getSeuil()|| $grandTotal >= $paymentMethod->getSeuil()))
                    {
                        $checkResult->isAvailable = true;
                        return;
                    }
                }
            }

            $checkResult->isAvailable = false;
            return;
        }

        $code = $observer->getEvent()->getMethodInstance()->getCode();
        if(in_array($code, $pledgCodes)){

            $checkResult = $observer->getEvent()->getResult();
            if(Mage::registry('pledg_gateway_available') == $code) {
                $checkResult->isAvailable = true;
            } else {
                $checkResult->isAvailable = false;
            }
        }
    }

    public function setPledgPaymentMethod(Varien_Event_Observer $observer)
    {
        $controller = $observer->getControllerAction();
        $action = $controller->getFullActionName();

        if($action != "aw_onestepcheckout_ajax_placeOrder" &&
            $action != "aw_onestepcheckout_ajax_savePaymentMethod" ) {
            return;
        }

        /** @var Mage_Core_Controller_Request_Http $request */
        $request = $controller->getRequest();
        if (!$request->isPost() || !$request->isAjax() || !$request->getPost('payment')) {
            return;
        }

        $payment = $request->getPost('payment');
        if(!empty($payment['pledg_method'])) {
            $payment['method'] = $payment['pledg_method'];
            $request->setPost("payment", $payment);

            Mage::register('pledg_gateway_available', $payment['pledg_method']);
        }

    }
}