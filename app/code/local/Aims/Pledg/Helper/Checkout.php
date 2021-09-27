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
     * Cancel last placed order with specified comment message
     *
     * @param string $comment Comment appended to order history
     * @param Mage_Sales_Model_Order $order
     *
     * @throws \Exception
     */
    public function cancelCurrentOrder($comment, $order)
    {
        $allowedCancelationStates = array(
            Mage_Sales_Model_Order::STATE_NEW,
            Mage_Sales_Model_Order::STATE_PENDING_PAYMENT
        );
        $canceledState = Mage_Sales_Model_Order::STATE_CANCELED;
        if ($order->getState() != $canceledState) {
            if (!$order->canCancel() || !in_array($order->getState(), $allowedCancelationStates)) {
                throw new \Exception('Order cannot be canceled');
            }

            $order->registerCancellation($comment)->save();
        }
    }

    /**
     * Restores quote (restores cart)
     */
    public function restoreQuote()
    {
        $session = Mage::getSingleton('checkout/session');
        $lastQuoteId = $session->getLastSuccessQuoteId();

        if ($lastQuoteId) {
            $quote = $this->_getQuote($lastQuoteId);
            if ($quote->getId()) {
                $quote->setIsActive(1)
                    ->setReservedOrderId(null)
                    ->save();
                $session
                    ->replaceQuote($quote)
                    ->unsLastRealOrderId();
            }
        }
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
