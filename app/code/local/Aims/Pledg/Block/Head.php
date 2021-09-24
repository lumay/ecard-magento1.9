<?php
/**
 * AIMS
 * @category   AIMS
 * @package    Aims_Hypnia
 * @copyright  Copyright (c) 2020 Unicode Systems. (http://www.unicodesystems.in)
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class Aims_Pledg_Block_Head extends Mage_Core_Block_Template
{
    /**
     * @return string
     */
    public function getCustomJs()
    {
        if (Mage::helper('aims_pledg/config')->getPledgIsStaging()) {
            return 'https://s3-eu-west-1.amazonaws.com/pledg-assets/ecard-plugin/staging/plugin.min.js';
        }

        return 'https://s3-eu-west-1.amazonaws.com/pledg-assets/ecard-plugin/master/plugin.min.js';
    }
}
