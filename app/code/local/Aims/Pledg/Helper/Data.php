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
    /**
     * Return true if this is an admin session.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return Mage::app()->getStore()->isAdmin();
    }

    /**
     * Return true if file exists. Uses Varien_Io_File API.
     *
     * @param  string $fileName
     * @return bool
     */
    public function fileExists($fileName)
    {
        $io = new Varien_Io_File();
        return $io->fileExists($fileName);
    }

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
