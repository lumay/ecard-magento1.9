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

    const STATUS_CANCELED = array(
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
        $order = $this->getOrder();

        // Check order status
        if (!$order || !$order->getEntityId()) {
            return $this->errorOrder('Order not exists');
        }

        Mage::register('pledg_order', $order);

        $this->loadLayout();
        $this->renderLayout();
    }

    public function successAction() {

        $orderId = $this->getRequest()->getParam('order_id');
        $order =  $this->getOrderById($orderId);

        if (!$order && !$order->getEntityId()) {
            return $this->errorOrder('Order not exists');
        }

        $this->setOrder($order);

        // Decode secret
        $dataPledg = explode('#'.$this->getOrder()->getPayment()->getMethod().'#',
            base64_decode($this->getRequest()->getParam('secret'))
        );

        if(Mage::helper('aims_pledg/config')->getPledgIsInDebugMode()) {
            Mage::log(__METHOD__ . " " . __LINE__, null , "pledg.log", true);
            Mage::log($this->getRequest()->getParams(), null , "pledg.log", true);
            Mage::log($dataPledg, null , "pledg.log", true);
        }

        if (count($dataPledg) != 3) {
            return $this->errorOrder('Secret Pledg invalid count');
        }

        // Check merchant uid
        if ($this->getMerchantUid() != $dataPledg[2]) {
            return $this->errorOrder('Secret Pledg invalid uid');
        }

        // Check transaction id
        $transactionId = $dataPledg[0];
        if ($transactionId != $this->getRequest()->getParam('transaction_id')) {
            return $this->errorOrder('Pledg Transaction ID invalid');
        }

        // Check quote id
        if ($dataPledg[1] != $this->getOrder()->getEntityId()) {
            return $this->errorOrder('Secret Pledg invalid quote');
        }

        $this->getCheckoutSession()->setLastQuoteId($order->getQuoteId())
            ->setLastSuccessQuoteId($order->getQuoteId())
            ->setLastOrderId($order->getId())
            ->setLastRealOrderId($order->getIncrementId())
            ->setLastOrderStatus($order->getStatus());

        return $this->_redirect('checkout/onepage/success', array('_secure'=> false));
    }

    /**
     * Cancel action
     */
    public function cancelAction() {
        $orderId = $this->getRequest()->getParam('order_id');
        $order =  $this->getOrderById($orderId);

        $errorMessage = $this->getRequest()->getParam('error');

        if ($order && $order->getId()) {
            if(Mage::helper('aims_pledg/config')->getPledgIsInDebugMode()) {
                Mage::log(__METHOD__ . " " . __LINE__, null , "pledg.log", true);
                Mage::log("Pledg: ".($order->getIncrementId())." has been canceled. Error : " . $errorMessage, null , "pledg.log", true);
            }

            $this->setOrder($order);

            $this->getCheckoutHelper()->cancelCurrentOrder("Pledg: ".($order->getIncrementId())." has been canceled. Error : " . $errorMessage, $order);
            $this->getCheckoutHelper()->restoreQuote($order);

            if($errorMessage == "Bouton Retour") {
                $this->getCheckoutSession()->addError($this->__("Your Pledg payment has been cancelled."));
            } else {
                $this->getCheckoutSession()->addError($this->__("Your Pledg payment has been cancelled : %s.", $errorMessage));
            }
            
        }
        $this->_redirect('checkout/cart');
    }

    public function notificationsAction()
    {
        /** THIS IS WHAT WE SHOULD RECEIVE WITH BACK MODE */
        /*$params = array(
            "created_at" => "2019-04-04T12:20:34.97138Z",
             "id" => "test-valid",
             "additional_data" => array("xx" => "yy"),
             "metadata" =>  array("foo" => "bar"),
             "status" => "completed",
             "sandbox" => "true",
             "error" => "",
             "reference" => "PLEDG_1086986786391",
             "signature" => "B1C777835C01CA96AC4C3097FD46A7CA49B92BE157EDE0CB3552880D12A15359"
        );*/
        /** THIS IS WHAT WE SHOULD RECEIVE WITH TRANSFER MODE */
        /*$params = array(
            "signature" => "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJyZWZlcmVuY2UiOiJwdXJjaGFzZV9yZWZlcmVuY2UiLCJjcmVhdGVkIjoiMjAyMC0wOS0xNyAxNTowMDowMi4wNTIxNzciLCJ0cmFuc2Zlcl9vcmRlcl9pdGVtX3VpZCI6InRyaV9mYXNka2ZpYXNmc2QyMzRuZnM3ZjVkIiwiYW1vdW50X2NlbnRzIjoxMDAwMH0.6ZhlvdcPgjwwBfxCrRpzaBGoRxTCoqtWJzFhB8pw4Ys"
        );*/
        /** THIS IS WHAT THE PREVIOUS SIGNATURE SHOULD LOOK AFTER DECRYPT */
        /*$paramsDecoded = array(
            "reference" => "purchase_reference",
            "created_at" => "2019-04-04T12:20:34.97138Z",
            "transfer_order_item_uid" => "test-valid",
            "amount_cents" => 10000,
        );*/

        $params = json_decode($this->getRequest()->getRawBody(), true);

        if(Mage::helper('aims_pledg/config')->getPledgIsInDebugMode()) {
            Mage::log(__METHOD__ . " " . __LINE__, null , "pledg.log", true);
            Mage::log($params, null , "pledg.log", true);
        }

        if(!is_array($params) || empty($params['signature'])) {
            if(Mage::helper('aims_pledg/config')->getPledgIsInDebugMode()) {
                Mage::log(__METHOD__ . " " . __LINE__, null, "pledg.log", true);
                Mage::log("Error : no signature param", null, "pledg.log", true);
            }
            return;
        }

        $signatureParam = $params['signature'];

        if(count($params) == 1) {
            // Transfer Mode
            $mode = "transfer";
            $params = Mage::helper('aims_pledg/crypto')->readSignature($signatureParam);

            if(Mage::helper('aims_pledg/config')->getPledgIsInDebugMode()) {
                Mage::log(__METHOD__ . " " . __LINE__, null , "pledg.log", true);
                Mage::log($params, null , "pledg.log", true);
            }

            $transactionId = $params['transfer_order_item_uid'];
        } else {
            // Back Mode
            $mode = "back";
            $transactionId = $params['id'];
        }

        $incrementId = Mage::helper('aims_pledg')->getIncrementIdByReference($params['reference']);
        $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);

        if(!$order) {
            if(Mage::helper('aims_pledg/config')->getPledgIsInDebugMode()) {
                Mage::log(__METHOD__ . " " . __LINE__, null , "pledg.log", true);
                Mage::log("Order " . $incrementId . " can't be found.", null , "pledg.log", true);
            }
            return;
        }

        $verify = false;
        if($mode == "transfer") {
            try {
                Mage::helper('aims_pledg/crypto')->decryptSignature($signatureParam, $this->_getSecretKey($order));
                $verify = true;
            } catch (Exception $e) {
                if(Mage::helper('aims_pledg/config')->getPledgIsInDebugMode()) {
                    Mage::log(__METHOD__ . " " . __LINE__, null , "pledg.log", true);
                    Mage::log("Order " . $incrementId . " decrypt failed : " . $e->getMessage(), null , "pledg.log", true);
                }
            }
        } elseif($mode == "back") {
            $signatureArray = array(
                "created_at" => $params['created_at'],
                "error" => $params['error'],
                "id" => $params['id'],
                "reference" => $params['reference'],
                "sandbox" => $params['sandbox'],
                "status" => $params['status']
            );

            $signatureToCheck = $this->_generateSignature($signatureArray, $this->_getSecretKey($order));

            if($signatureToCheck == $signatureParam) {
                $verify = true;
            }
        }

        if($verify == false) {
            if(Mage::helper('aims_pledg/config')->getPledgIsInDebugMode()) {
                Mage::log(__METHOD__ . " " . __LINE__, null, "pledg.log", true);
                Mage::log("Signature is not correct. Cancel Order " . $incrementId, null, "pledg.log", true);
            }
            $this->getCheckoutHelper()->cancelCurrentOrder("Pledg Notification: ".($incrementId)." has been canceled. Error : Signature is not correct", $order);
            return;
        }

        // Check order status
        if ($order->getState() !== Mage_Sales_Model_Order::STATE_NEW) {
            if(Mage::helper('aims_pledg/config')->getPledgIsInDebugMode()) {
                Mage::log(__METHOD__ . " " . __LINE__, null , "pledg.log", true);
                Mage::log("Order " . $incrementId . " is not in New State. Nothing to do.", null , "pledg.log", true);
            }

            $order->addStatusHistoryComment("Pledg Notification: ".($incrementId)." is not in New State. Nothing to do.")
                ->setIsCustomerNotified(false);
            return;
        }

        $this->setOrder($order);

        if($mode == "back") {
            if (in_array($params['status'], self::STATUS_COMPLETED)) {
                if (Mage::helper('aims_pledg/config')->getPledgIsInDebugMode()) {
                    Mage::log(__METHOD__ . " " . __LINE__, null , "pledg.log", true);
                    Mage::log("Order " . $incrementId . " : Pledg returned " . $params['status'] . " status. Invoice Order.", null, "pledg.log", true);
                }

                $this->_invoiceOrder($order, $transactionId);

                $order->addStatusHistoryComment("Pledg Notification: " . $incrementId . " status received is : " . $params['status'] . ". Invoice Order.")
                    ->setIsCustomerNotified(false);
                return;
            } elseif (in_array($params['status'], self::STATUS_CANCELED)) {
                if (Mage::helper('aims_pledg/config')->getPledgIsInDebugMode()) {
                    Mage::log(__METHOD__ . " " . __LINE__, null , "pledg.log", true);
                    Mage::log("Order " . $incrementId . " : Pledg returned " . $params['status'] . " status. Cancel Order.", null, "pledg.log", true);
                }

                $this->getCheckoutHelper()->cancelCurrentOrder("Pledg Notification: " . $incrementId ." status received is : " . $params['status']  . "Cancel Order.", $order);
                return;
            } elseif (in_array($params['status'], self::STATUS_PENDING)) {
                /** NOTHING TO DO */
                if (Mage::helper('aims_pledg/config')->getPledgIsInDebugMode()) {
                    Mage::log(__METHOD__ . " " . __LINE__, null , "pledg.log", true);
                    Mage::log("Order " . $incrementId . " : Pledg returned " . $params['status'] . " status. Nothing to do.", null, "pledg.log", true);
                }

                $order->addStatusHistoryComment("Pledg Notification: " . ($incrementId) . " status received is : " . $params['status'] . ". Nothing to do.")
                    ->setIsCustomerNotified(false);
                return;
            }
        } elseif($mode == "transfer") {
            if (Mage::helper('aims_pledg/config')->getPledgIsInDebugMode()) {
                Mage::log(__METHOD__ . " " . __LINE__, null , "pledg.log", true);
                Mage::log("Order " . $incrementId . " : Transfer mode always return completed. Invoice Order.", null, "pledg.log", true);
            }

            $this->_invoiceOrder($order, $transactionId);

            $order->addStatusHistoryComment("Pledg Notification: " . ($incrementId) . " Transfer mode always return completed. Invoice Order.")
                ->setIsCustomerNotified(false);
            return;
        }
    }

    /**
     * @param string|string $message
     */
    protected function errorOrder($message) {
        $this
            ->getCheckoutHelper()
            ->cancelCurrentOrder(
                "Order #".($this->getOrder()->getIncrementId())." " . $message,
                $this->getOrder()
            );
        $this->getCheckoutHelper()->restoreQuote($this->getOrder()); //restore cart
        $this->getCheckoutSession()->addError($this->__($message));
        return $this->_redirect('checkout/cart', array('_secure'=> false));
    }

    /**
     * Generate Invoice
     *
     * @param $order
     * @param $transactionId
     */
    protected function _invoiceOrder($order, $transactionId)
    {
        $orderState = Mage_Sales_Model_Order::STATE_PROCESSING;
        $orderStatus = $order->getConfig()->getStateDefaultStatus($orderState);

        $order->setState($orderState)
            ->setStatus($orderStatus)
            ->addStatusHistoryComment("Pledg authorisation success. Transaction #$transactionId")
            ->setIsCustomerNotified(false);

        $payment = $order->getPayment();
        $payment->setTransactionId($transactionId);
        $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE, null, true);
        $order->save();

        if(!$order->canInvoice()){
            throw new Mage_Core_Exception($this->__('Cannot create an invoice.'));
        }

        $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();

        if (!$invoice->getTotalQty()) {
            throw new Mage_Core_Exception($this->__("You can't create an invoice without products."));
        }

        $invoice->setTransactionId($transactionId);
        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
        $invoice->register();

        $transaction = Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder());
        $transaction->save();

        if (!$order->getEmailSent()) {
            $order->sendNewOrderEmail();
        }
    }

    protected function _getSecretKey($order) {
        return Mage::helper('aims_pledg')->getGatewayConfig($order)->getSecretKey();
    }

    protected function _generateSignature($data, $secret) {
        $dataString = "";
        $arraySize = count($data);
        $count = 0;
        foreach($data as $key=>$value) {
            $count++;

            $dataString .= $key ."=". $value;
            if($count >= $arraySize) {
                continue;
            }
            $dataString .= $secret;
        }

        $signature = strtoupper(hash('sha256', $dataString));

        return $signature;
    }
}