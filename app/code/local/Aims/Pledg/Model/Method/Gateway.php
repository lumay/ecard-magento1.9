<?php
/**
 * AIMS
 * @category   AIMS
 * @package    Aims_Hypnia
 * @copyright  Copyright (c) 2020 Unicode Systems. (http://www.unicodesystems.in)
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class Aims_Pledg_Model_Method_Gateway extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = "pledg_gateway";
    protected $_formBlockType = 'aims_pledg/form_gateway';
    protected $_infoBlockType = 'aims_pledg/info';

    protected $_isInitializeNeeded      = true;
    protected $_canUseInternal          = false;
    protected $_canUseForMultishipping  = false;
    protected $_canUseCheckout = true;

    const KEY_ACTIVE = 'active';
    const KEY_TITLE = 'title';
    const KEY_DESCRIPTION = 'description';
    const KEY_GATEWAY_LOGO = 'gateway_logo';
    const KEY_API_KEY = 'api_key';
    const KEY_SECRET_KEY = 'secret_key';
    const KEY_SEUIL = 'seuil';

    /**
     * Retrieve Payment Method Code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->_code;
    }

    /**
     * Get Is Active
     *
     * @return string
     */
    public function isActive()
    {
        return $this->getConfigData(self::KEY_ACTIVE);
    }

    /**
     * Get Merchant number
     *
     * @return string
     */
    public function getMerchantNumber() {
        return $this->getConfigData(self::KEY_API_KEY);
    }

    /**
     * Get Secret
     *
     * @return string
     */
    public function getSecretKey() {
        return $this->getConfigData(self::KEY_SECRET_KEY);
    }

    /**
     * Get Title
     *
     * @return string
     */
    public function getTitle() {
        return $this->getConfigData(self::KEY_TITLE);
    }

    /**
     * Get Description
     *
     * @return string
     */
    public function getDescription() {
        return $this->getConfigData(self::KEY_DESCRIPTION);
    }

    /**
     * Retrieve Payment Method Seuil
     * @return string
     */
    public function getSeuil()
    {
        return $this->getConfigData(self::KEY_SEUIL);
    }

    /**
     * Get Logo
     *
     * @return string
     */
    public function getLogo() {
        return $this->getConfigData(self::KEY_GATEWAY_LOGO);
    }

    /**
     * @return mixed
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('pledg/checkout/form');
    }
}