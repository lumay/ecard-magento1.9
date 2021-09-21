<?php
/**
 * AIMS
 * @category   AIMS
 * @package    Aims_Hypnia
 * @copyright  Copyright (c) 2020 Unicode Systems. (http://www.unicodesystems.in)
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

abstract class Aims_Pledg_Model_Method_Gateway extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = "pledg_gateway";
    protected $_formBlockType = 'aims_pledg/form_gateway';
    protected $_infoBlockType = 'aims_pledg/info';

    protected $_isInitializeNeeded     = true;
    protected $_canUseInternal         = false;
    protected $_canUseForMultishipping = false;
    protected $_canUseCheckout         = true;

    /**
     * @return mixed
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('pledg/checkout/form');
    }
}
