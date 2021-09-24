<?php
/**
 * AIMS
 * @category   AIMS
 * @package    Aims_Hypnia
 * @copyright  Copyright (c) 2020 Unicode Systems. (http://www.unicodesystems.in)
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * Checkout workflow helper
 *
 * Class Config
 * @package Aims_Pledg_Helper_Checkout
 */
class Aims_Pledg_Helper_Config extends Mage_Core_Helper_Abstract
{
    public function getPledgIsStaging()
    {
        return Mage::getStoreConfig('pledg_gateway/payment/staging');
    }

    public function getPledgIsInDebugMode()
    {
        return Mage::getStoreConfig('pledg_gateway/payment/debug');
    }
}
