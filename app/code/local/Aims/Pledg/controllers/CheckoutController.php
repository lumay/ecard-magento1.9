<?php
/**
 * AIMS
 * @category   AIMS
 * @package    Aims_Hypnia
 * @copyright  Copyright (c) 2020 Unicode Systems. (http://www.unicodesystems.in)
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class Aims_Pledg_CheckoutController extends Aims_Pledg_Controller_Abstract
{
    const MODE_TRANSFER = 'transfer';
    const MODE_BACK = 'back';

    const STATUS_PENDING = array(
        "waiting",
        "pending",
        "authorized",
        "pending-capture",
        "in-review",
        "retrieval-request",
        "fraud-notification",
        "chargeback-initiated",
        "solved",
        "reversed"
    );

    const STATUS_CANCELLED = array(
        "failed",
        "voided",
        "refunded",
        "pending-capture",
        "blocked"
    );

    const STATUS_COMPLETED = array(
        "completed"
    );

    public function formAction()
    {
        try {
            $order = $this->getOrder(array(
                Mage_Sales_Model_Order::STATE_NEW
            ));

            $merchantApiKey = Mage::helper('aims_pledg')->getMerchantIdForOrder($order);
            if ($merchantApiKey === null) {
                throw new \Exception(sprintf(
                    'Could not retrieve api key for country %s on order %s',
                    $order->getBillingAddress()->getCountryId(),
                    $order->getIncrementId()
                ));
            }

            $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true, $this->__('Customer accessed payment page'));
            $order->save();

            Mage::register('pledg_order', $order);

            $this->loadLayout();
            $this->renderLayout();
        } catch (\Exception $e) {
            Mage::log('An error occurred on pledg payment page : ' . $e->getMessage());
            $this->getCheckoutSession()->addError($this->__('An error occurred while processing your payment. Please try again.'));

            return $this->_redirect('checkout/cart', array('_secure'=> false));
        }
    }

    /**
     * Cancel action
     */
    public function cancelAction()
    {
        try {
            $order = $this->getOrder(array(
                Mage_Sales_Model_Order::STATE_NEW,
                Mage_Sales_Model_Order::STATE_PENDING_PAYMENT
            ));

            $comment = $this->__('Payment has been cancelled by customer');
            $errorMessage = $this->getRequest()->getParam('pledg_error');
            if (!empty($errorMessage)) {
                $comment = $this->__($errorMessage);
            }
            $this->getCheckoutHelper()->cancelCurrentOrder($comment, $order);
            $this->getCheckoutHelper()->restoreQuote();

            if (!empty($errorMessage)) {
                $this->getCheckoutSession()->addError($this->__('An error occurred while processing your payment.'));
            } else {
                $this->getCheckoutSession()->addSuccess($this->__('Your payment has successfully been cancelled.'));
            }
        } catch (\Exception $e) {
            Mage::log('An error occurred on pledg cancel page : ' . $e->getMessage());

            $this->getCheckoutSession()->addError(
                $this->__('An error occurred while cancelling your order. Please try again.')
            );
        }

        return $this->_redirect('checkout/cart');
    }

    /**
     * @param mixed $message
     * @param bool  $forceLog
     */
    private function log($message, $forceLog = false)
    {
        if (Mage::helper('aims_pledg/config')->getPledgIsInDebugMode() || $forceLog) {
            Mage::log($message, null, "pledg.log", true);
        }
    }

    /**
     * @param array  $array
     * @param string $key
     * @param string $defaultValue
     *
     * @return mixed
     */
    private function getValueFromArray($array, $key, $defaultValue = '')
    {
        $value = $defaultValue;
        if (array_key_exists($key, $array) && $array[$key] !== null) {
            $value = $array[$key];
        }

        return $value;
    }

    public function notificationsAction()
    {
        $success = true;
        $responseCode = 200;
        $message = '';

        try {
            $params = json_decode($this->getRequest()->getRawBody(), true);

            $secretKey = Mage::getStoreConfig(
                sprintf('payment/%s/secret_key', $this->getRequest()->getParam('pledg_method')),
                (int)$this->getRequest()->getParam('ipn_store_id')
            );
            if ($secretKey === null) {
                $secretKey = '';
            }

            $this->log('Received IPN');
            $this->log($params);

            if (isset($params['signature'])) {
                if (count($params) === 1) {
                    $this->log('Mode signed transfer');

                    $signature = $params['signature'];
                    $params = Mage::helper('aims_pledg/crypto')->decryptSignature($signature, $secretKey);
                    $this->log('Decrypted message');
                    $this->log($params);

                    $this->handleTransferMode($params);
                } else {
                    $this->log('Mode signed back');

                    if ($params['signature'] !== $this->generateSignature($params, $secretKey)) {
                        throw new \Exception('Invalid signature');
                    }

                    $this->handleBackMode($params);
                }
            } else {
                $this->log('Mode unsigned transfer');
                $this->handleTransferMode($params);
            }
        } catch (\Exception $e) {
            $this->log('An error occurred while processing IPN : ' . $e->getMessage(), true);

            $success = false;
            $responseCode = 500;
            $message = $e->getMessage();
        }

        $responseContent = array('success' => $success, 'message' => $message);
        $this->getResponse()->setHttpResponseCode($responseCode);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($responseContent));

        $this->log('IPN response [' . $responseCode . ']');
        $this->log($responseContent);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param string $transactionId
     * @param string $ipnMessage
     *
     * @throws \Exception
     */
    private function invoiceOrder($order, $transactionId, $ipnMessage)
    {
        if (!$order->canInvoice() || $order->getState() !== Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
            throw new \Exception(sprintf('Order with state %s cannot be processed and invoiced', $order->getState()));
        }

        $comment = $this->__('Registered update about approved payment.') . ' ' . str_replace('%1', $transactionId, $this->__('Transaction ID: "%1"'));
        $state = Mage_Sales_Model_Order::STATE_PROCESSING;
        $transactionSave = Mage::getModel('core/resource_transaction');
        $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
        if (!$invoice->getTotalQty()) {
            Mage::throwException(
                $this->__('Cannot create an invoice without products.')
            );
        }
        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
        $invoice->register();
        $invoice->setTransactionId($transactionId);
        $transactionSave->addObject($invoice);

        $order->setState($state, $order->getConfig()->getStateDefaultStatus($state), $comment);
        $transactionSave->addObject($order);
        $transactionSave->save();

        $order->sendNewOrderEmail();

        $this->addMessageOnOrder($order, $ipnMessage);
    }

    /**
     * @param array $params
     *
     * @throws \Exception
     */
    private function handleBackMode(array $params)
    {
        $order = $this->getIpnOrder($params);

        $pledgStatus = $this->getValueFromArray($params, 'status');
        $transactionId = $this->getValueFromArray($params, 'id');
        $this->log('Payment status received with back mode : ' . $pledgStatus);

        $this->addPaymentInformation($order, $transactionId, self::MODE_BACK, $pledgStatus);

        if (in_array($pledgStatus, self::STATUS_COMPLETED)) {
            $this->log('Invoice order after receiving back notification');
            $this->invoiceOrder($order, $transactionId, str_replace(
                '%1',
                $pledgStatus,
                $this->__('Received invoicing order from Pledg back notification with status %1')
            ));

            return;
        }

        if (in_array($pledgStatus, self::STATUS_CANCELLED)) {
            $this->log('Cancel order after receiving back notification');
            if (!$order->canCancel()) {
                throw new \Exception(sprintf('Order %s cannot be canceled', $order->getIncrementId()));
            }
            $order->registerCancellation(str_replace(
                '%1',
                $pledgStatus,
                $this->__('Received cancellation order from Pledg back notification with status %1')
            ))->save();

            return;
        }

        if (in_array($pledgStatus, self::STATUS_PENDING)) {
            $this->log('Received back notification with Pending status. Do nothing');
            $this->addMessageOnOrder($order, str_replace(
                '%1',
                $pledgStatus,
                'Received Pledg back notification with status %1. Waiting for further instructions to update order.'
            ));

            return;
        }

        $this->log('Received unhandled status from Pledg back notification : ' . $pledgStatus, true);
    }

    /**
     * @param array $params
     *
     * @throws \Exception
     */
    private function handleTransferMode(array $params)
    {
        $order = $this->getIpnOrder($params);

        // In transfer mode, notification is only sent when payment is validated
        $transactionId = $this->getValueFromArray($params, 'purchase_uid');
        $this->log('Invoice order after receiving transfer notification');

        $this->addPaymentInformation($order, $transactionId, self::MODE_TRANSFER, 'completed');
        $this->invoiceOrder($order, $transactionId, $this->__('Received invoicing order from Pledg transfer notification'));
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param string $transactionId
     * @param string $mode
     * @param string $pledgStatus
     */
    private function addPaymentInformation($order, $transactionId, $mode, $pledgStatus)
    {
        $orderPayment = $order->getPayment();
        $orderPayment->setAdditionalInformation('transaction_id', $transactionId);
        $orderPayment->setAdditionalInformation('pledg_mode', $mode);
        $orderPayment->setAdditionalInformation('pledg_status', $pledgStatus);
        $orderPayment->save();
    }

    /**
     * @param Mage_Sales_Model_Order  $order
     * @param string $message
     *
     * @throws \Exception
     */
    private function addMessageOnOrder($order, $message)
    {
        $order->addStatusHistoryComment($message);
        $order->save();
    }

    /**
     * @param array $params
     *
     * @return Mage_Sales_Model_Order
     *
     * @throws \Exception
     */
    private function getIpnOrder($params)
    {
        $orderIncrementId = Mage::helper('aims_pledg')->getIncrementIdByReference($params['reference']);
        $order = $this->getOrderById($orderIncrementId);

        if ($order === null) {
            throw new \Exception(sprintf('Could not retrieve order with id %s', $orderIncrementId));
        }

        $paymentMethod = $order->getPayment()->getMethodInstance();
        if (strstr($paymentMethod->getCode(), 'pledg_gateway_') === false) {
            throw new \Exception(sprintf('Order with method %s should not be updated via Pledg notification', $paymentMethod->getCode()));
        }

        return $order;
    }

    /**
     * @param array  $params
     * @param string $secret
     *
     * @return string
     */
    private function generateSignature($params, $secret)
    {
        $paramsToValidate = array(
            'created_at',
            'error',
            'id',
            'reference',
            'sandbox',
            'status',
        );

        $stringToValidate = array();
        foreach ($paramsToValidate as $param) {
            $stringToValidate[] = $param . '=' . $this->getValueFromArray($params, $param);
        }

        return strtoupper(hash('sha256', implode($secret, $stringToValidate)));
    }
}
