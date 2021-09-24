<?php
/**
 * AIMS
 * @category   AIMS
 * @package    Aims_Hypnia
 * @copyright  Copyright (c) 2020 Unicode Systems. (http://www.unicodesystems.in)
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * Image config field renderer
 */


/**
 * Class Image Field
 * @method getFieldConfig()
 * @method setFieldConfig()
 */
class Aims_Pledg_Block_System_Config_Form_Field_Image extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Get country selector html
     *
     * @param Varien_Data_Form_Element_Abstract $element $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $html = '';

        if (!(string)$element->getValue()) {
            $defaultImage = Mage::getBaseUrl('media') . 'aims_pledg/pledg_logo.png';

            $html .= '<img src="' . $defaultImage . '" alt="Pledg logo" height="50" class="small-image-preview v-middle" />';
            $html .= '<p class="note"><span>'.Mage::helper('aims_pledg')->__('Upload a new image if you wish to replace this logo.') . '</span></p>';
        }

        //the standard image preview is very small- bump the height up a bit and remove the width
        $html .= str_replace('height="22" width="22"', 'height="50"', parent::_getElementHtml($element));

        return $html;
    }
}
