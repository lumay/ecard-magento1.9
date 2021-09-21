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
    protected $_template = 'aims_pledg/checkout/method.phtml';

    /**
     * Payment method additional label part getter
     * Add cards logos
     *
     * @return string
     */
    public function getMethodLabelAfterHtml()
    {
        $logoURL = $this->_checkAndGetSkinUrl($this->getMethod()->getConfigData('gateway_logo'));

        if ($logoURL !== false) {
            $logo = Mage::getConfig()->getBlockClassName('core/template');
            $logo = new $logo;
            $logo->setTemplate('aims_pledg/checkout/logo.phtml');
            $logo->setLogoSrc($logoURL);
            $logo->setMethodTitle($this->getMethod()->getConfigData('title'));

            return $logo->toHtml();
        }

        return '';
    }

    /**
     * @param string $fileName
     *
     * @return false|string
     */
    protected function _checkAndGetSkinUrl($fileName)
    {
        if (!$fileName) {
            $fileName = "default/pledg_logo.png";
        }

        $filePath = Mage::getBaseDir('media') . DS . 'aims_pledg' . DS . $fileName;
        $io = new Varien_Io_File();
        if (!$io->fileExists($filePath)) {
            Mage::log(sprintf("Pledg logo %s does not exist", $filePath));

            return false;
        }

        $logoUrl = Mage::getBaseUrl('media') . 'aims_pledg/' . $fileName;

        return $logoUrl;
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
}
