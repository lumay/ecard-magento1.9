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
    const ORDER_REFERENCE_PREFIX = 'order_';

    /**
     * @param string $incrementId
     *
     * @return string
     */
    public function getReferenceByIncrementId($incrementId)
    {
        return self::ORDER_REFERENCE_PREFIX . $incrementId;
    }

    /**
     * @param string $reference
     *
     * @return string
     */
    public function getIncrementIdByReference($reference)
    {
        return str_replace(self::ORDER_REFERENCE_PREFIX, '', $reference);
    }

    public function getGatewayConfig($order)
    {
        return $order->getPayment()->getMethodInstance();
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return string|null
     */
    public function getMerchantIdForOrder($order)
    {
        $paymentMethod = $order->getPayment()->getMethodInstance();
        if (!$paymentMethod->getConfigData('active')) {
            return null;
        }

        $mappings = $paymentMethod->getConfigData('api_key_mapping');
        $mappings = preg_split("/\r\n|\n|\r/", trim($mappings));

        foreach ($mappings as $mapping) {
            $mapping = trim($mapping);
            if (empty($mapping)) {
                continue;
            }
            $mapping = explode(':', $mapping);
            if (count($mapping) !== 2) {
                continue;
            }
            if ($mapping[0] === $order->getBillingAddress()->getCountryId()) {
                return $mapping[1];
            }
        }

        return null;
    }
}
