<?php
/**
 * AIMS
 * @category   AIMS
 * @package    Aims_Hypnia
 * @copyright  Copyright (c) 2020 Unicode Systems. (http://www.unicodesystems.in)
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * Checkout workflow helper
 *
 * Class Checkout
 * @package Aims_Pledg_Helper_Checkout
 */
class Aims_Pledg_Helper_Checkout extends Mage_Core_Helper_Abstract
{
    /**
     * @var Mage_Checkout_Model_Session
     */
    protected $_session;

    public function __construct() {
        $this->_session = Mage::getSingleton('checkout/session');
    }

    /**
     * Cancel last placed order with specified comment message
     *
     * @param string $comment Comment appended to order history
     * @throws Mage_Core_Exception
     * @return bool True if order cancelled, false otherwise
     */
    public function cancelCurrentOrder($comment, $order = null)
    {
        if(is_null($order)) {
            $order = $this->_session->getLastRealOrder();
        }

        if ($order->getId() && $order->getState() != Mage_Sales_Model_Order::STATE_CANCELED) {
            $order->registerCancellation($comment)->save();
            return true;
        }
        return false;
    }

    /**
     * Restores quote (restores cart)
     *
     * @return bool
     */
    public function restoreQuote($order = null)
    {
        if(is_null($order)) {
            $order = $this->_session->getLastRealOrder();
        }

        if ($order->getId()) {
            $quote = $this->_getQuote($order->getQuoteId());
            if ($quote->getId()) {
                $quote->setIsActive(1)
                    ->setReservedOrderId(null)
                    ->save();
                $this->_session
                    ->replaceQuote($quote)
                    ->unsLastRealOrderId();
                return true;
            }
        }
        return false;
    }

    /**
     * Return sales quote instance for specified ID
     *
     * @param int $quoteId Quote identifier
     * @return Mage_Sales_Model_Quote
     */
    protected function _getQuote($quoteId)
    {
        return Mage::getModel('sales/quote')->load($quoteId);
    }
}
