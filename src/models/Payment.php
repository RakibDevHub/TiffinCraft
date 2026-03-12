<?php
require_once BASE_PATH . '/src/models/Cart.php';
require_once BASE_PATH . '/src/models/Order.php';
require_once BASE_PATH . '/src/models/Kitchen.php';
require_once BASE_PATH . '/src/models/Subscription.php';
require_once BASE_PATH . '/src/helpers/SSLCommerz.php';

class Payment
{
    private $conn;
    private $cartModel;
    private $orderModel;
    private $kitchenModel;
    private $subscriptionModel;
    
    private $sslCommerz;

    public function __construct($connection)
    {
        $this->conn = $connection;
        $this->cartModel = new Cart($this->conn);
        $this->orderModel = new Order($this->conn);
        $this->kitchenModel = new Kitchen($this->conn);
        $this->subscriptionModel = new Subscription($this->conn);
    
        $this->sslCommerz = new SSLCommerz();
    }

    // * PAYMENT METHODS

    public function subscriptionPayment($paymentData, $metadata = [])
    {
        try {
            $tranId = $this->generateTransactionId('SUB');

            $sslData = $this->organizeSSLCommerzData($paymentData, $tranId, 'SUBSCRIPTION', $metadata);

            Session::set('pending_subscription_transaction', [
                'transaction_id' => $tranId,
                'user_id' => $paymentData['user_id'],
                'amount' => $paymentData['amount'],
                'metadata' => $metadata,
                'description' => $paymentData['description'] ?? ''
            ]);
            return $this->sslCommerz->makePayment($sslData);
        } catch (Exception $e) {
            error_log("Payment processing error: " . $e->getMessage());
            throw new RuntimeException("Payment initialization failed: " . $e->getMessage());
        }
    }

    public function orderPayment($paymentData, $metadata = [])
    {
        try {
            $tranId = $this->generateTransactionId('ORD');

            $sslData = $this->organizeSSLCommerzData($paymentData, $tranId, 'ORDER', $metadata);

            Session::set('pending_order_transaction', [
                'transaction_id' => $tranId,
                'user_id' => $paymentData['user_id'],
                'amount' => $paymentData['amount'],
                'description' => $paymentData['description'] ?? ''
            ]);

            return $this->sslCommerz->makePayment($sslData);
        } catch (Exception $e) {
            error_log("Order payment processing error: " . $e->getMessage());
            throw new RuntimeException("Order payment initialization failed: " . $e->getMessage());
        }
    }

    // * PAYMENT CALLBACK HANDLERS
    public function handleSubscriptionPaymentCallback($postData, $getData)
    {
        $tranId = $this->getTransactionId($postData, $getData);

        try {
            $validationResult = $this->validatePayment($tranId, $postData, $getData);
            if (!$validationResult['isValid']) {
                $this->recordFailedTransaction($tranId, $validationResult['amount'], 'Payment validation failed', 'SUBSCRIPTION');
                Session::flash('error', 'Payment validation failed. Please try again.');
                return 'FAILED';
            }

            $pendingTransaction = Session::get('pending_subscription_transaction');
            if (!$pendingTransaction || $pendingTransaction['transaction_id'] !== $tranId) {
                $this->recordFailedTransaction($tranId, $validationResult['amount'] ?? 0, 'Invalid or missing pending transaction', 'SUBSCRIPTION');
                Session::flash('error', 'Invalid or missing pending transaction.');
                return 'FAILED';
            }

            $this->beginTransaction();

            try {
                $this->handlePreviousSubscription($pendingTransaction);
                $subscriptionId = $this->createSellerSubscription($pendingTransaction);
                $this->recordSuccessfulTransaction($pendingTransaction, $subscriptionId, $validationResult, 'SUBSCRIPTION');
                $this->kitchenModel->approveKitchen($pendingTransaction['user_id'] ?? null);
                $this->commitTransaction();
                $this->cleanupSubscriptionSession();

                Session::flash('success', 'Payment successful! Your subscription is now active.');
                return 'SUCCESS';
            } catch (Exception $e) {
                $this->rollbackTransaction();
                $this->recordFailedTransaction($tranId, $pendingTransaction['amount'] ?? 0, $e->getMessage(), 'SUBSCRIPTION');
                throw $e;
            }
        } catch (Exception $e) {
            error_log("Payment callback error: " . $e->getMessage());
            $this->recordFailedTransaction($tranId, 0, $e->getMessage(), 'SUBSCRIPTION');
            Session::flash('error', 'Payment processing error: ' . $e->getMessage());
            return 'FAILED';
        }
    }

    public function handleOrderPaymentCallback($postData, $getData)
    {
        $tranId = $this->getTransactionId($postData, $getData);

        try {
            $validationResult = $this->validatePayment($tranId, $postData, $getData);
            if (!$validationResult['isValid']) {
                $this->recordFailedTransaction($tranId, $validationResult['amount'], 'Payment validation failed', 'ORDER');
                $this->cleanupOrderSession();
                Session::flash('error', 'Payment validation failed. Please try again.');
                return 'FAILED';
            }

            $pendingTransaction = Session::get('pending_order_transaction');
            if (!$pendingTransaction || $pendingTransaction['transaction_id'] !== $tranId) {
                $this->recordFailedTransaction($tranId, $validationResult['amount'] ?? 0, 'Invalid or missing pending transaction', 'ORDER');
                $this->cleanupOrderSession();
                return 'FAILED';
            }

            $orderData = Session::get('pending_order_data');
            if (!$orderData) {
                $this->recordFailedTransaction($tranId, $validationResult['amount'] ?? 0, 'Order data not found', 'ORDER');
                $this->cleanupOrderSession();
                return 'FAILED';
            }

            $this->beginTransaction();

            try {
                $orderId = $this->createOrderRecord($orderData);

                if (!$orderId) {
                    throw new RuntimeException("Failed to create order record");
                }

                $this->removeCartItems($orderData['buyer_id'], $orderData['kitchen_id']);
                $this->recordSuccessfulTransaction($pendingTransaction, $orderId, $validationResult, 'ORDER');
                $this->commitTransaction();

                $this->cleanupOrderSession();

                return 'SUCCESS';
            } catch (Exception $e) {
                $this->rollbackTransaction();

                if (isset($orderId)) {
                    $this->restoreOrderStock($orderId);
                }

                $this->recordFailedTransaction($tranId, $validationResult['amount'] ?? 0, $e->getMessage(), 'ORDER');
                $this->cleanupOrderSession();
                throw $e;
            }
        } catch (Exception $e) {
            error_log("Order payment callback error: " . $e->getMessage());
            $this->recordFailedTransaction($tranId, 0, $e->getMessage(), 'ORDER');
            $this->cleanupOrderSession();
            return 'FAILED';
        }
    }

    private function recordSuccessfulTransaction($pendingTransaction, $referenceId, $validationResult, $referenceType)
    {
        $this->recordTransaction([
            'transaction_id' => $pendingTransaction['transaction_id'],
            'user_id' => $pendingTransaction['user_id'],
            'amount' => $pendingTransaction['amount'],
            'currency' => 'BDT',
            'transaction_type' => 'PAYMENT',
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'payment_method' => $validationResult['gatewayResponse']['card_issuer'] ?? 'sslcommerz',
            'status' => 'SUCCESS',
            'description' => $pendingTransaction['description'],
            'gateway_response' => json_encode($validationResult['gatewayResponse']),
            'message' => 'Payment validated successfully',
            'metadata' => $pendingTransaction['metadata'] ?? ['reference_id' => $referenceId]
        ]);
    }

    private function recordFailedTransaction($transactionId, $amount, $errorMessage, $referenceType = 'ORDER')
    {
        $userId = Session::get('user_id');
        $description = $referenceType === 'SUBSCRIPTION' ? 'Subscription payment' : 'Order payment';

        $this->recordTransaction([
            'transaction_id' => $transactionId,
            'user_id' => $userId,
            'amount' => (float) $amount,
            'currency' => 'BDT',
            'transaction_type' => 'PAYMENT',
            'reference_type' => $referenceType,
            'reference_id' => null, // NULL for failed transactions
            'payment_method' => 'sslcommerz',
            'status' => 'FAILED',
            'description' => $description,
            'gateway_response' => '{}',
            'message' => substr($errorMessage, 0, 500), // Truncate to 500 chars
            'metadata' => ['failed_reason' => substr($errorMessage, 0, 500)]
        ], true);
    }

    private function recordTransaction($transactionData, $autoCommit = false)
    {
        try {
            $sql = "INSERT INTO payment_transactions (
                    transaction_id, user_id, amount, currency, transaction_type, 
                    reference_type, reference_id, payment_method, status, description, 
                    gateway_response, message, metadata, created_at, updated_at
                ) VALUES (
                    :transaction_id, :user_id, :amount, :currency, :transaction_type, 
                    :reference_type, :reference_id, :payment_method, :status, :description, 
                    :gateway_response, :message, :metadata, SYSTIMESTAMP, SYSTIMESTAMP
                )";

            $stmt = oci_parse($this->conn, $sql);

            $transactionId = (string) $transactionData['transaction_id'];
            $userId = (int) $transactionData['user_id'];
            $amount = (float) $transactionData['amount'];
            $currency = (string) ($transactionData['currency'] ?? 'BDT');
            $transactionType = (string) $transactionData['transaction_type'];
            $referenceType = (string) $transactionData['reference_type'];

            $referenceId = isset($transactionData['reference_id']) ?
                (int) $transactionData['reference_id'] : null;

            $paymentMethod = (string) ($transactionData['payment_method'] ?? 'sslcommerz');
            $status = (string) $transactionData['status'];
            $description = (string) ($transactionData['description'] ?? '');

            // Gateway response needs to be a string for CLOB
            $gatewayResponse = is_string($transactionData['gateway_response'] ?? '{}') ?
                $transactionData['gateway_response'] :
                json_encode($transactionData['gateway_response'] ?? []);

            $message = (string) ($transactionData['message'] ?? $this->getDefaultMessage($status));

            // Metadata must be valid JSON for the check constraint
            $metadata = '{}';
            if (!empty($transactionData['metadata'])) {
                if (is_string($transactionData['metadata'])) {
                    // Validate it's valid JSON
                    json_decode($transactionData['metadata']);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $metadata = $transactionData['metadata'];
                    }
                } else {
                    $metadata = json_encode($transactionData['metadata']);
                }
            }

            // Bind parameters
            oci_bind_by_name($stmt, ':transaction_id', $transactionId);
            oci_bind_by_name($stmt, ':user_id', $userId);
            oci_bind_by_name($stmt, ':amount', $amount);
            oci_bind_by_name($stmt, ':currency', $currency);
            oci_bind_by_name($stmt, ':transaction_type', $transactionType);
            oci_bind_by_name($stmt, ':reference_type', $referenceType);
            oci_bind_by_name($stmt, ':reference_id', $referenceId);
            oci_bind_by_name($stmt, ':payment_method', $paymentMethod);
            oci_bind_by_name($stmt, ':status', $status);
            oci_bind_by_name($stmt, ':description', $description);
            oci_bind_by_name($stmt, ':gateway_response', $gatewayResponse);
            oci_bind_by_name($stmt, ':message', $message);
            oci_bind_by_name($stmt, ':metadata', $metadata);

            return oci_execute($stmt, $autoCommit ? OCI_COMMIT_ON_SUCCESS : OCI_NO_AUTO_COMMIT);
        } catch (Exception $e) {
            error_log("Failed to record transaction: " . $e->getMessage());
            throw new RuntimeException("Failed to record transaction: " . $e->getMessage());
        }
    }

    // * SUBSCRIPTION METHODS
    private function createSellerSubscription($subData)
    {
        $metadata = $this->parseMetadata($subData['metadata']);

        if (empty($metadata['plan_id'])) {
            throw new RuntimeException("Missing plan_id in subscription metadata");
        }

        return $this->subscriptionModel->createSub([
            'seller_id'   => $subData['user_id'],
            'plan_id'     => $metadata['plan_id'],
            'start_date'  => date('Y-m-d'),
            'end_date'    => date('Y-m-d', strtotime('+1 month')),
            'status'      => 'ACTIVE',
            'change_type' => $metadata['subscription_type']
        ]);
    }

    private function handlePreviousSubscription($pendingTransaction)
    {
        if (empty($pendingTransaction['metadata']['previous_plan'])) {
            return;
        }

        $prevPlan = $pendingTransaction['metadata']['previous_plan'];
        $endDate = strtotime($prevPlan['end_date'] ?? '');
        $now = time();

        if (
            $pendingTransaction['metadata']['subscription_type'] === 'UPGRADE'
            && $prevPlan['status'] === 'ACTIVE'
            && $endDate >= $now
        ) {
            $this->subscriptionModel->updateSubscriptionStatus(
                $prevPlan['subscription_id'],
                'CANCELLED',
                false
            );
        }
    }

    // * ORDER METHODS
    private function createOrderRecord($orderData)
    {
        try {

            $orderId = $this->orderModel->createOrder($orderData, $orderData['cart_items']);

            if (!$orderId) {
                throw new RuntimeException("Failed to create order after payment confirmation");
            }

            return $orderId;
        } catch (Exception $e) {
            error_log("Order creation failed: " . $e->getMessage());
            throw new RuntimeException("Order creation failed: " . $e->getMessage());
        }
    }

    private function removeCartItems($userId, $kitchenId)
    {
        try {
            $result = $this->cartModel->removeKitchenItemsFromCart($userId, $kitchenId);

            if (!$result) {
                throw new RuntimeException("Failed to clear user cart");
            }

            return $result;
        } catch (Exception $e) {
            error_log("Cart clearing failed: " . $e->getMessage());
            throw new RuntimeException("Cart clearing failed: " . $e->getMessage());
        }
    }

    private function restoreOrderStock($orderId)
    {
        try {
            return $this->orderModel->restoreStock($orderId);
        } catch (Exception $e) {
            error_log("Stock restoration failed for order {$orderId}: " . $e->getMessage());
            return false;
        }
    }


    // * Validate payment with SSLCommerz
    private function validatePayment($tranId, $postData, $getData)
    {
        $amount = (float) ($postData['amount'] ?? $getData['amount'] ?? 0);
        $valId = $postData['val_id'] ?? $getData['val_id'] ?? null;

        $isValid = $this->sslCommerz->validatePayment($tranId, $amount, $valId);

        return [
            'isValid' => $isValid,
            'amount' => $amount,
            'valId' => $valId,
            'gatewayResponse' => array_merge($postData, $getData)
        ];
    }

    private function parseMetadata($metadata)
    {
        if (is_object($metadata) && get_class($metadata) === 'OCILob') {
            $metadataStr = $metadata->read($metadata->size());
        } elseif (is_array($metadata)) {
            $metadataStr = json_encode($metadata);
        } else {
            $metadataStr = (string) $metadata;
        }

        return json_decode($metadataStr, true) ?: [];
    }

    // * Organize SSLCommerz data array
    private function organizeSSLCommerzData($paymentData, $tranId, $type, $metadata = [])
    {
        $baseData = [
            'total_amount' => $paymentData['amount'],
            'currency' => $paymentData['currency'] ?? 'BDT',
            'tran_id' => $tranId,
            'cus_name' => $paymentData['customer_name'],
            'cus_email' => $paymentData['customer_email'],
            'cus_phone' => $paymentData['customer_phone'],
            'cus_add1' => $paymentData['customer_address'] ?? 'Not Provided',
            'product_name' => $paymentData['product_name'],
            'success_url' => $paymentData['success_url'],
            'fail_url' => $paymentData['fail_url'],
            'cancel_url' => $paymentData['cancel_url'],
            'value_a' => $type,
            'value_b' => json_encode(array_merge($metadata, ['tran_id' => $tranId]))
        ];

        if ($type === 'SUBSCRIPTION') {
            $baseData['product_category'] = 'Subscription';
            $baseData['product_profile'] = 'non-physical-goods';
        } else {
            $baseData['product_category'] = 'Food';
            $baseData['product_profile'] = 'physical-goods';
        }

        return $baseData;
    }

    private function cleanupSubscriptionSession()
    {
        Session::remove('pending_subscription_transaction');
        Session::remove('pending_subscription');
    }

    private function cleanupOrderSession()
    {
        Session::remove('pending_order_transaction');
        Session::remove('pending_order_data');
    }

    private function generateTransactionId($prefix = 'TXN')
    {
        $datePart = date('Ymd_His');
        $randomPart = strtoupper(bin2hex(random_bytes(4)));
        return $prefix . '_' . $datePart . '_' . $randomPart;
    }

    private function getTransactionId($postData, $getData)
    {
        $tranId = $postData['tran_id'] ?? $getData['tran_id'] ?? '';

        if (empty($tranId)) {
            throw new RuntimeException("Transaction ID not found in callback");
        }

        return $tranId;
    }

    private function getDefaultMessage($status)
    {
        return $status === 'SUCCESS' ? 'Payment validated successfully' : 'Payment failed';
    }

    private function beginTransaction()
    {
        return true;
    }

    private function commitTransaction()
    {
        return oci_commit($this->conn);
    }

    private function rollbackTransaction()
    {
        return oci_rollback($this->conn);
    }
}
