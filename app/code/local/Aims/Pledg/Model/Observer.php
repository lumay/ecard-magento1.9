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

    /**
     * @param Varien_Event_Observer $observer
     */
    public function saveConfigPledg(Varien_Event_Observer $observer)
    {
        $groups = Mage::app()->getRequest()->getPost('groups');
        if (!$groups) {
            return;
        }
        foreach ($groups as $section => $values) {
            if (strstr($section, 'pledg_gateway_') === false) {
                continue;
            }
            $paymentId = str_replace('pledg_gateway_', '', $section);

            $fields = $values['fields'];
            if (!array_key_exists('active', $fields) || !array_key_exists('api_key_mapping', $fields)) {
                continue;
            }
            if (!$fields['active']['value']) {
                continue;
            }
            $mappings = trim($fields['api_key_mapping']['value']);
            $mappings = preg_split("/\r\n|\n|\r/", $mappings);
            $countries = array();
            foreach ($mappings as $key => $mapping) {
                $mapping = trim($mapping);
                if (empty($mapping)) {
                    continue;
                }
                $mapping = explode(':', $mapping);
                if (count($mapping) !== 2) {
                    $groups[$section]['fields']['active']['value'] = 0;
                    $this->addAdminError('Line %1 is not formatted correctly for pledg payment method %2.', array('%1', '%2'), array($key + 1, $paymentId));
                    continue;
                }
                $country = $mapping[0];
                if (strlen($country) !== 2) {
                    $groups[$section]['fields']['active']['value'] = 0;
                    $this->addAdminError('Country on line %1 is not formatted correctly for pledg payment method %2.', array('%1', '%2'), array($key + 1, $paymentId));
                    continue;
                }
                if (in_array($country, $countries)) {
                    $groups[$section]['fields']['active']['value'] = 0;
                    $this->addAdminError('Please remove duplicate mapping for country %1 on pledg payment method %2.', array('%1', '%2'), array($country, $paymentId));
                    continue;
                }
                $countries[] = $country;
            }
            if (count($countries) === 0) {
                $groups[$section]['fields']['active']['value'] = 0;
                $this->addAdminError('You must select at least one country to be able to activate pledg payment method %1.', array('%1'), array($paymentId));
                continue;
            }
            $groups[$section]['fields']['allowspecific']['value'] = 1;
            $groups[$section]['fields']['specificcountry']['value'] = implode(',', $countries);
        }

        Mage::app()->getRequest()->setPost('groups', $groups);
    }

    /**
     * @param string $message
     * @param array  $placeholders
     * @param array  $replacements
     */
    private function addAdminError($message, $placeholders = array(), $replacements = array())
    {
        Mage::getSingleton('adminhtml/session')
            ->addError(str_replace($placeholders, $replacements, Mage::helper('aims_pledg')->__($message)));
    }
}
