<?php

/**
 * AIMS
 * @category   AIMS
 * @package    Aims_Hypnia
 * @copyright  Copyright (c) 2020 Unicode Systems. (http://www.unicodesystems.in)
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * Class Data
 * @package Aims_Pledg_Helper_Data
 */
class Aims_Pledg_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getReferenceByIncrementId($incrementId) {
        return 'order_'.$incrementId;
    }

    public function getIncrementIdByReference($reference) {
        $refeArray = explode('_',$reference);
        return $refeArray[1];
    }

    public function getGatewayConfig($order)
    {
        return $order->getPayment()->getMethodInstance();
    }
}
