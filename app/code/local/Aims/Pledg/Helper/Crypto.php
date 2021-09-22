<?php
/**
 * AIMS
 * @category   AIMS
 * @package    Aims_Hypnia
 * @copyright  Copyright (c) 2020 Unicode Systems. (http://www.unicodesystems.in)
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

require_once(Mage::getBaseDir() . DS . 'lib' . DS . 'Firebase' . DS . 'JWT.php');
require_once(Mage::getBaseDir() . DS . 'lib' . DS . 'Firebase' . DS . 'BeforeValidException.php');
require_once(Mage::getBaseDir() . DS . 'lib' . DS . 'Firebase' . DS . 'ExpiredException.php');
require_once(Mage::getBaseDir() . DS . 'lib' . DS . 'Firebase' . DS . 'SignatureInvalidException.php');

/**
 * Crypto workflow helper
 *
 * Class Checkout
 * @package Aims_Pledg_Helper_Crypto
 */
class Aims_Pledg_Helper_Crypto extends Mage_Core_Helper_Abstract
{
    /**
     * generates a hmac based on an associative array and an api key
     * @param $query array
     * @param $secretKey string
     * @return string
     */
    public function generateSignature($query, $secretKey)
    {
        $jwt = \Firebase\JWT\JWT::encode($query, $secretKey);

        return $jwt;
    }

    public function decryptSignature($query, $secretKey)
    {
        $jwt = \Firebase\JWT\JWT::decode($query, $secretKey, array('HS256'));

        return $jwt;
    }

    public function readSignature($query)
    {
        $jwt = \Firebase\JWT\JWT::read($query, array('HS256'));

        return json_decode(json_encode($jwt), true);
    }
}
