<?php
/**
 * AIMS
 * @category   AIMS
 * @package    Aims_Hypnia
 * @copyright  Copyright (c) 2020 Unicode Systems. (http://www.unicodesystems.in)
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class Aims_Pledg_Block_Checkout_Form extends Mage_Core_Block_Template
{
    /**
     * @var Mage_Sales_Model_Order
     */
    private $order;

    /**
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if (!$this->order) {
            $order = Mage::registry('pledg_order');

            $this->order = $order;
        }

        return $this->order;
    }

    /**
     * @return array
     */
    public function getPledgData()
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $this->getOrder();
        $orderIncrementId = $order->getIncrementId();
        $orderAddress = $order->getBillingAddress();

        $pledgData = array(
            'merchantUid' => Mage::helper('aims_pledg')->getMerchantIdForOrder($order),
            'amountCents' => round($order->getGrandTotal() * 100),
            'email' => $order->getCustomerEmail(),
            'title' => 'Order ' . $orderIncrementId,
            'reference' => Mage::helper('aims_pledg')->getReferenceByIncrementId($order->getIncrementId()),
            'firstName' => $orderAddress->getFirstname(),
            'lastName' => $orderAddress->getLastname(),
            'currency' => $order->getOrderCurrencyCode(),
            'lang' => $this->getLang(),
            'countryCode' => $orderAddress->getCountryId(),
            'address' => $this->getAddressData($orderAddress),
            'metadata' => $this->getMetaData($order),
            'showCloseButton' => true,
            'paymentNotificationUrl' => $this->getUrl('pledg/checkout/notifications', array(
                '_secure' => true,
                'ipn_store_id' => $order->getStoreId(),
                'pledg_method' => $order->getPayment()->getMethodInstance()->getCode(),
            )),
        );

        if (!$order->getIsVirtual()) {
            $pledgData['shipping_address'] = $this->getAddressData($order->getShippingAddress());
        }

        $telephone = $orderAddress->getTelephone();
        if (!empty($telephone)) {
            $pledgData['phoneNumber'] = preg_replace('/^(\+|00)(.*)$/', '$2', $telephone);
        }

        Mage::helper('aims_pledg')->log($pledgData);

        $secretKey = $order->getPayment()->getMethodInstance()->getConfigData('secret_key', $order->getStoreId());
        if (empty($secretKey)) {
            return $this->encodeData($pledgData);
        }

        $signature = Mage::helper('aims_pledg/crypto')->generateSignature(array('data' => $pledgData), $secretKey);

        Mage::helper('aims_pledg')->log($signature);

        return array(
            'signature' => $signature,
        );
    }

    /**
     * @return string
     */
    private function getLang()
    {
        $lang = Mage::getStoreConfig('general/locale/code');

        $allowedLangs = array(
            'fr_FR',
            'de_DE',
            'en_GB',
            'es_ES',
            'it_IT',
            'nl_NL',
        );

        if (in_array($lang, $allowedLangs)) {
            return $lang;
        }

        return reset($allowedLangs);
    }

    /**
     * @param Mage_Sales_Model_Order_Address $orderAddress
     *
     * @return array
     */
    private function getAddressData($orderAddress)
    {
        return array(
            'street' => $orderAddress->getStreet(1),
            'city' => $orderAddress->getCity(),
            'zipcode' => (string)$orderAddress->getPostcode(),
            'stateProvince' => (string)$orderAddress->getRegion(),
            'country' => $orderAddress->getCountryId(),
        );
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    private function getMetaData($order)
    {
        $physicalProductTypes = array(
            'simple',
            'configurable',
            'bundle',
            'grouped',
        );

        $products = array();
        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            $productType = $item->getProductType();
            $products[] = array(
                'reference' => $item->getSku(),
                'type' => in_array($productType, $physicalProductTypes) ? 'physical' : 'virtual',
                'quantity' => (int)$item->getQtyOrdered(),
                'name' => $item->getName(),
                'unit_amount_cents' => round($item->getPriceInclTax() * 100),
            );
            if (count($products) === 5) {
                // Metadata field is limited in size
                // Include max 5 products information
                break;
            }
        }

        return array_merge(array(
            'plugin' => sprintf(
                'magento%s-pledg-plugin%s',
                Mage::getVersion(),
                Mage::getConfig()->getModuleConfig('Aims_Pledg')->version
            ),
            'products' => $products,
        ), $this->getCustomerData($order));
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    private function getCustomerData($order)
    {
        $customerId = (int)$order->getCustomerId();
        if (empty($customerId)) {
            return array();
        }

        try {
            $customer = Mage::getModel('customer/customer')->load($customerId);
            $orders = Mage::getResourceModel('sales/order_collection')
                ->addFieldToFilter('customer_id', Mage::getSingleton('customer/session')->getCustomer()->getId())
            ;

            return array('account' => array(
                'creation_date' => (new \DateTime($customer->getCreatedAt()))->format('Y-m-d'),
                'number_of_purchases' => (int)$orders->getSize(),
            ));
        } catch (\Exception $e) {
            Mage::helper('aims_pledg')->log(sprintf('Could not resolve order %s customer for pledg data : %s', $order->getIncrementId(), $e->getMessage()), true);
        }

        return array();
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function encodeData($data)
    {
        $convertedData = array();
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $convertedData[$key] = $this->encodeData($value);
                continue;
            }

            if (mb_check_encoding($value, 'UTF-8') === false) {
                $value = $this->convToUtf8($value);
            }
            $convertedData[$key] = $value;
        }

        return $convertedData;
    }

    /**
     * @param string $stringToEncode
     * @param string $encodingTypes
     *
     * @return string
     */
    private function convToUtf8(
        $stringToEncode,
        $encodingTypes = "UTF-8,ASCII,windows-1252,ISO-8859-15,ISO-8859-1"
    ) {
        $detect = mb_detect_encoding($stringToEncode, $encodingTypes, true);
        if ($detect && $detect !== "UTF-8") {
            if ($detect === 'ISO-8859-15') {
                $stringToEncode = preg_replace('/\x9c/', '|oe|', $stringToEncode);
            }
            $stringToEncode = iconv($detect, "UTF-8", $stringToEncode);
            if ($detect === 'ISO-8859-15') {
                $stringToEncode = preg_replace('/\|oe\|/', 'œ', $stringToEncode);
            }
        }

        return $stringToEncode;
    }
}
