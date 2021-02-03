<?php
/**
 * AIMS
 * @category   AIMS
 * @package    Aims_Hypnia
 * @copyright  Copyright (c) 2020 Unicode Systems. (http://www.unicodesystems.in)
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * Class Info
 */
class Aims_Pledg_Block_Info extends Mage_Payment_Block_Info
{
    /**
     * Returns label
     *
     * @param string $field
     * @return string
     */
    protected function getLabel($field)
    {
        return $this->__($field);
    }
}
