<?php
/**
 * AIMS
 * @category   AIMS
 * @package    Aims_Hypnia
 * @copyright  Copyright (c) 2020 Unicode Systems. (http://www.unicodesystems.in)
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class Aims_Pledg_Model_Method_Gateway3 extends Aims_Pledg_Model_Method_Gateway
{
    protected $_code = "pledg_gateway_3";
    protected $_formBlockType = 'aims_pledg/form_gateway3';

    /**
     * Retrieve Payment Method Code
     * @return string
     */
    public function getCode()
    {
        return $this->_code;
    }
}