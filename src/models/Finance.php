<?php

class Finance
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // FINANCIAL TRANSACTIONS
    public function getPaymentTransactions($limit = 50, $offset = 0, $filters = [])
    {
        $limit = (int)$limit;
        $offset = (int)$offset;

        $sql = "SELECT 
                    pt.*,
                    u.name as user_name,
                    u.role as user_role,
                    u.email,
                    -- Additional info based on reference type
                    CASE 
                        WHEN pt.reference_type = 'ORDER' THEN o.status
                        WHEN pt.reference_type = 'SUBSCRIPTION' THEN ss.status
                        ELSE NULL 
                    END as reference_entity_status,
                    CASE 
                        WHEN pt.reference_type = 'ORDER' THEN o.order_id
                        WHEN pt.reference_type = 'SUBSCRIPTION' THEN ss.subscription_id
                        ELSE NULL 
                    END as reference_entity_id
                FROM payment_transactions pt
                LEFT JOIN users u ON pt.user_id = u.user_id
                LEFT JOIN orders o ON pt.reference_type = 'ORDER' AND pt.reference_id = o.order_id
                LEFT JOIN seller_subscriptions ss ON pt.reference_type = 'SUBSCRIPTION' AND pt.reference_id = ss.subscription_id
                WHERE 1=1";

        $conditions = [];
        $params = [];
        $paramIndex = 1;

        // Add filters
        if (!empty($filters['status'])) {
            $conditions[] = "pt.status = :status";
            $params[":status"] = $filters['status'];
        }

        if (!empty($filters['transaction_type'])) {
            $conditions[] = "pt.transaction_type = :transaction_type";
            $params[":transaction_type"] = $filters['transaction_type'];
        }

        if (!empty($filters['reference_type'])) {
            $conditions[] = "pt.reference_type = :reference_type";
            $params[":reference_type"] = $filters['reference_type'];
        }

        if (!empty($filters['search'])) {
            $searchTerm = '%' . strtolower($filters['search']) . '%';
            $conditions[] = "(
                LOWER(pt.transaction_id) LIKE :search 
                OR LOWER(u.name) LIKE :search 
                OR LOWER(u.email) LIKE :search
                OR LOWER(pt.description) LIKE :search
            )";
            $params[":search"] = $searchTerm;
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = "TRUNC(pt.created_at) >= TO_DATE(:date_from, 'YYYY-MM-DD')";
            $params[":date_from"] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = "TRUNC(pt.created_at) <= TO_DATE(:date_to, 'YYYY-MM-DD')";
            $params[":date_to"] = $filters['date_to'];
        }

        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY pt.created_at DESC
                  OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";

        $stmt = oci_parse($this->conn, $sql);

        // Bind parameters
        oci_bind_by_name($stmt, ':offset', $offset);
        oci_bind_by_name($stmt, ':limit', $limit);

        foreach ($params as $key => $value) {
            oci_bind_by_name($stmt, $key, $value);
        }

        if (!oci_execute($stmt)) {
            error_log("Error fetching payment transactions: " . oci_error($stmt));
            return [];
        }

        $transactions = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $row = $this->processRow($row);
            $transactions[] = $row;
        }

        oci_free_statement($stmt);
        return $transactions;
    }

    public function countPaymentTransactions($filters = [])
    {
        $sql = "SELECT COUNT(*) as total
                FROM payment_transactions pt
                LEFT JOIN users u ON pt.user_id = u.user_id
                WHERE 1=1";

        $conditions = [];
        $params = [];

        // Add filters (same as getPaymentTransactions)
        if (!empty($filters['status'])) {
            $conditions[] = "pt.status = :status";
            $params[":status"] = $filters['status'];
        }

        if (!empty($filters['transaction_type'])) {
            $conditions[] = "pt.transaction_type = :transaction_type";
            $params[":transaction_type"] = $filters['transaction_type'];
        }

        if (!empty($filters['reference_type'])) {
            $conditions[] = "pt.reference_type = :reference_type";
            $params[":reference_type"] = $filters['reference_type'];
        }

        if (!empty($filters['search'])) {
            $searchTerm = '%' . strtolower($filters['search']) . '%';
            $conditions[] = "(
                LOWER(pt.transaction_id) LIKE :search 
                OR LOWER(u.name) LIKE :search 
                OR LOWER(u.email) LIKE :search
                OR LOWER(pt.description) LIKE :search
            )";
            $params[":search"] = $searchTerm;
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = "TRUNC(pt.created_at) >= TO_DATE(:date_from, 'YYYY-MM-DD')";
            $params[":date_from"] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = "TRUNC(pt.created_at) <= TO_DATE(:date_to, 'YYYY-MM-DD')";
            $params[":date_to"] = $filters['date_to'];
        }

        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }

        $stmt = oci_parse($this->conn, $sql);

        foreach ($params as $key => $value) {
            oci_bind_by_name($stmt, $key, $value);
        }

        if (!oci_execute($stmt)) {
            error_log("Error counting payment transactions: " . oci_error($stmt));
            return 0;
        }

        $row = oci_fetch_assoc($stmt);
        $count = $row['TOTAL'] ?? 0;

        oci_free_statement($stmt);
        return $count;
    }

    // WITHDRAWAL MANAGEMENT
    public function getWithdrawalRequests($limit = 50, $offset = 0, $filters = [])
    {
        $sql = "SELECT 
                wr.*,
                u.name as seller_name,
                u.email,
                u.phone,
                -- Calculate available balance
                (SELECT NVL(SUM((o.total_amount + o.delivery_fee) - 
                                (o.total_amount * sp.commission_rate / 100)), 0)
                 FROM orders o
                 JOIN kitchens k ON o.kitchen_id = k.kitchen_id
                 JOIN seller_subscriptions ss ON k.owner_id = ss.seller_id
                 JOIN subscription_plans sp ON ss.plan_id = sp.plan_id
                 WHERE k.owner_id = wr.seller_id
                 AND o.status = 'DELIVERED'
                 AND ss.status = 'ACTIVE') 
                - 
                (SELECT NVL(SUM(amount), 0)
                 FROM withdraw_requests
                 WHERE seller_id = wr.seller_id
                 AND status IN ('PROCESSED')) as available_balance
            FROM withdraw_requests wr
            JOIN users u ON wr.seller_id = u.user_id
            WHERE 1=1";

        $params = [];

        // Apply status filter
        if (!empty($filters['status'])) {
            $sql .= " AND wr.status = :status";
            $params[':status'] = $filters['status'];
        } else {
            // Default to show all statuses if not specified
            $sql .= " AND wr.status IN ('PENDING', 'APPROVED', 'PROCESSED', 'REJECTED')";
        }

        // Apply search filter
        if (!empty($filters['search'])) {
            $searchTerm = '%' . strtolower($filters['search']) . '%';
            $sql .= " AND (
            LOWER(u.name) LIKE :search 
            OR LOWER(u.email) LIKE :search
            OR LOWER(wr.account_details) LIKE :search
            OR wr.withdraw_id LIKE :search_id
        )";
            $params[':search'] = $searchTerm;
            $params[':search_id'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY wr.created_at DESC
              OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";

        $stmt = oci_parse($this->conn, $sql);

        // Bind limit and offset
        oci_bind_by_name($stmt, ':offset', $offset);
        oci_bind_by_name($stmt, ':limit', $limit);

        // Bind other parameters
        foreach ($params as $key => $value) {
            oci_bind_by_name($stmt, $key, $value);
        }

        if (!oci_execute($stmt)) {
            error_log("Error fetching withdrawal requests: " . oci_error($stmt));
            return [];
        }

        $requests = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $row = $this->processRow($row);
            $row['AMOUNT'] = (float)$row['AMOUNT'];
            $row['AVAILABLE_BALANCE'] = isset($row['AVAILABLE_BALANCE']) ? (float)$row['AVAILABLE_BALANCE'] : 0;
            $requests[] = $row;
        }

        oci_free_statement($stmt);
        return $requests;
    }

    public function countWithdrawalRequests($filters = [])
    {
        $sql = "SELECT COUNT(*) as total
            FROM withdraw_requests wr
            JOIN users u ON wr.seller_id = u.user_id
            WHERE 1=1";

        $params = [];

        // Apply status filter
        if (!empty($filters['status'])) {
            $sql .= " AND wr.status = :status";
            $params[':status'] = $filters['status'];
        } else {
            // Default to count all statuses if not specified
            $sql .= " AND wr.status IN ('PENDING', 'APPROVED', 'PROCESSED', 'REJECTED')";
        }

        // Apply search filter
        if (!empty($filters['search'])) {
            $searchTerm = '%' . strtolower($filters['search']) . '%';
            $sql .= " AND (
            LOWER(u.name) LIKE :search 
            OR LOWER(u.email) LIKE :search
            OR LOWER(wr.account_details) LIKE :search
            OR wr.withdraw_id LIKE :search_id
        )";
            $params[':search'] = $searchTerm;
            $params[':search_id'] = '%' . $filters['search'] . '%';
        }

        $stmt = oci_parse($this->conn, $sql);

        // Bind parameters
        foreach ($params as $key => $value) {
            oci_bind_by_name($stmt, $key, $value);
        }

        if (!oci_execute($stmt)) {
            error_log("Error counting withdrawal requests: " . oci_error($stmt));
            return 0;
        }

        $row = oci_fetch_assoc($stmt);
        $count = $row['TOTAL'] ?? 0;

        oci_free_statement($stmt);
        return $count;
    }

    public function getWithdrawalById($withdrawId)
    {
        $sql = "SELECT * FROM withdraw_requests WHERE withdraw_id = :withdraw_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':withdraw_id', $withdrawId);

        if (!oci_execute($stmt)) {
            error_log("Error fetching withdrawal by ID: " . oci_error($stmt));
            return false;
        }

        $row = oci_fetch_assoc($stmt);
        if ($row) {
            $row = $this->processRow($row);
        }

        oci_free_statement($stmt);
        return $row ?: false;
    }

    public function updateWithdrawStatus($withdrawId, $status, $adminNotes = '')
    {
        $sql =
            "UPDATE withdraw_requests 
            SET status = :status, 
                admin_notes = :admin_notes,
                updated_at = SYSTIMESTAMP
            WHERE withdraw_id = :withdraw_id";

        if ($status === 'APPROVED' || $status === 'REJECTED') {
            $sql .= " AND status = 'PENDING'";
        }

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':withdraw_id', $withdrawId);
        oci_bind_by_name($stmt, ':status', $status);
        oci_bind_by_name($stmt, ':admin_notes', $adminNotes);

        if (!oci_execute($stmt)) {
            error_log("Error updating withdrawal status: " . oci_error($stmt));
            return false;
        }

        $rowsAffected = oci_num_rows($stmt);
        oci_free_statement($stmt);

        return $rowsAffected > 0;
    }

    public function markWithdrawalAsProcessed($withdrawId, $adminNotes = '')
    {
        $sql = "UPDATE withdraw_requests 
            SET status = 'PROCESSED', 
                admin_notes = :admin_notes,
                updated_at = SYSTIMESTAMP
            WHERE withdraw_id = :withdraw_id
            AND status = 'APPROVED'";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':withdraw_id', $withdrawId);
        oci_bind_by_name($stmt, ':admin_notes', $adminNotes);

        $success = oci_execute($stmt, OCI_NO_AUTO_COMMIT);

        if (!$success) {
            $e = oci_error($stmt);
            error_log("Error marking withdrawal as processed: " . $e['message']);
            oci_free_statement($stmt);
            return false;
        }

        $rowsAffected = oci_num_rows($stmt);
        oci_free_statement($stmt);

        return $rowsAffected > 0;
    }

    // REFUND MANAGEMENT
    public function getRefundRequests($limit = 50, $offset = 0, $filters = [])
    {
        $sql = "SELECT 
                rr.*,
                u.name as buyer_name,
                u.email,
                u.phone,
                o.order_id,
                o.total_amount,
                o.delivery_fee,
                o.status as order_status,
                o.cancel_by,
                k.name as kitchen_name
            FROM refund_requests rr
            JOIN users u ON rr.buyer_id = u.user_id
            JOIN orders o ON rr.order_id = o.order_id
            JOIN kitchens k ON o.kitchen_id = k.kitchen_id
            WHERE 1=1";

        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND rr.status = :status";
            $params[':status'] = $filters['status'];
        } else {
            $sql .= " AND rr.status IN ('PENDING', 'APPROVED', 'PROCESSED', 'REJECTED', 'CANCELLED')";
        }

        if (!empty($filters['search'])) {
            $searchTerm = '%' . strtolower($filters['search']) . '%';
            $sql .= " AND (
            LOWER(u.name) LIKE :search 
            OR LOWER(u.email) LIKE :search
            OR LOWER(rr.reason) LIKE :search
            OR LOWER(k.name) LIKE :search
            OR rr.refund_id LIKE :search_id
            OR rr.order_id LIKE :search_order_id
        )";
            $params[':search'] = $searchTerm;
            $params[':search_id'] = '%' . $filters['search'] . '%';
            $params[':search_order_id'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY rr.created_at DESC
              OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";

        $stmt = oci_parse($this->conn, $sql);

        oci_bind_by_name($stmt, ':offset', $offset);
        oci_bind_by_name($stmt, ':limit', $limit);

        foreach ($params as $key => $value) {
            oci_bind_by_name($stmt, $key, $value);
        }

        if (!oci_execute($stmt)) {
            error_log("Error fetching refund requests: " . oci_error($stmt));
            return [];
        }

        $requests = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $row = $this->processRow($row);
            $row['AMOUNT'] = (float)$row['AMOUNT'];
            $row['TOTAL_AMOUNT'] = (float)$row['TOTAL_AMOUNT'];
            $row['DELIVERY_FEE'] = (float)$row['DELIVERY_FEE'];

            $orderTotal = $row['TOTAL_AMOUNT'] + $row['DELIVERY_FEE'];
            $row['ORDER_TOTAL'] = $orderTotal;
            $row['REFUND_PERCENTAGE'] = $orderTotal > 0 ? round(($row['AMOUNT'] / $orderTotal) * 100, 1) : 0;

            $requests[] = $row;
        }

        oci_free_statement($stmt);
        return $requests;
    }

    public function countRefundRequests($filters = [])
    {
        $sql = "SELECT COUNT(*) as total
            FROM refund_requests rr
            JOIN users u ON rr.buyer_id = u.user_id
            JOIN orders o ON rr.order_id = o.order_id
            JOIN kitchens k ON o.kitchen_id = k.kitchen_id
            WHERE 1=1";

        $params = [];

        // Apply status filter
        if (!empty($filters['status'])) {
            $sql .= " AND rr.status = :status";
            $params[':status'] = $filters['status'];
        } else {
            // Default to count all statuses if not specified
            $sql .= " AND rr.status IN ('PENDING', 'APPROVED', 'PROCESSED', 'REJECTED')";
        }

        // Apply search filter
        if (!empty($filters['search'])) {
            $searchTerm = '%' . strtolower($filters['search']) . '%';
            $sql .= " AND (
            LOWER(u.name) LIKE :search 
            OR LOWER(u.email) LIKE :search
            OR LOWER(rr.reason) LIKE :search
            OR LOWER(k.name) LIKE :search
            OR rr.refund_id LIKE :search_id
            OR rr.order_id LIKE :search_order_id
        )";
            $params[':search'] = $searchTerm;
            $params[':search_id'] = '%' . $filters['search'] . '%';
            $params[':search_order_id'] = '%' . $filters['search'] . '%';
        }

        $stmt = oci_parse($this->conn, $sql);

        // Bind parameters
        foreach ($params as $key => $value) {
            oci_bind_by_name($stmt, $key, $value);
        }

        if (!oci_execute($stmt)) {
            return 0;
        }

        $row = oci_fetch_assoc($stmt);
        $count = $row['TOTAL'] ?? 0;

        oci_free_statement($stmt);
        return $count;
    }

    public function getRefundById($refundId)
    {
        $sql = "SELECT rr.*, u.name as buyer_name, u.email, u.phone,
                   o.order_id, o.status as order_status, o.cancel_by, 
                   o.total_amount, o.delivery_fee,
                   k.name as kitchen_name
            FROM refund_requests rr
            JOIN users u ON rr.buyer_id = u.user_id
            JOIN orders o ON rr.order_id = o.order_id
            JOIN kitchens k ON o.kitchen_id = k.kitchen_id
            WHERE rr.refund_id = :refund_id";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':refund_id', $refundId);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        if ($row) {
            $row = $this->processRow($row);
            $row['AMOUNT'] = (float)$row['AMOUNT'];
            $row['TOTAL_AMOUNT'] = (float)$row['TOTAL_AMOUNT'];
            $row['DELIVERY_FEE'] = (float)$row['DELIVERY_FEE'];

            // Calculate refund percentage dynamically
            $orderTotal = $row['TOTAL_AMOUNT'] + $row['DELIVERY_FEE'];
            $row['ORDER_TOTAL'] = $orderTotal;
            $row['REFUND_PERCENTAGE'] = $orderTotal > 0 ? round(($row['AMOUNT'] / $orderTotal) * 100, 1) : 0;
        }

        oci_free_statement($stmt);
        return $row ?: null;
    }

    public function updateRefundStatus($refundId, $status, $adminNotes = '')
    {
        $sql = "UPDATE refund_requests 
            SET status = :status, 
                admin_notes = :admin_notes,
                updated_at = SYSTIMESTAMP
            WHERE refund_id = :refund_id";

        if ($status === 'APPROVED' || $status === 'REJECTED') {
            $sql .= " AND status = 'PENDING'";
        } elseif ($status === 'PROCESSED') {
            $sql .= " AND status = 'APPROVED'";
        }

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':refund_id', $refundId);
        oci_bind_by_name($stmt, ':status', $status);
        oci_bind_by_name($stmt, ':admin_notes', $adminNotes);

        if (!oci_execute($stmt)) {
            error_log("Error updating refund status: " . oci_error($stmt));
            return false;
        }

        $rowsAffected = oci_num_rows($stmt);
        oci_free_statement($stmt);

        return $rowsAffected > 0;
    }

    public function markRefundAsProcessed($refundId, $adminNotes = '')
    {
        $sql = "UPDATE refund_requests 
            SET status = 'PROCESSED', 
                admin_notes = :admin_notes,
                updated_at = SYSTIMESTAMP
            WHERE refund_id = :refund_id 
            AND status = 'APPROVED'";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':refund_id', $refundId);
        oci_bind_by_name($stmt, ':admin_notes', $adminNotes);

        // Execute without auto-commit
        $success = oci_execute($stmt, OCI_NO_AUTO_COMMIT);

        if (!$success) {
            $e = oci_error($stmt);
            error_log("Error marking refund as processed: " . $e['message']);
            oci_free_statement($stmt);
            return false;
        }

        $rowsAffected = oci_num_rows($stmt);
        oci_free_statement($stmt);

        return $rowsAffected > 0;
    }

    // TRANSACTION MANAGEMENT
    public function recordTransaction($data)
    {
        $sql = "INSERT INTO payment_transactions (
                    transaction_id,
                    user_id,
                    amount,
                    currency,
                    transaction_type,
                    reference_type,
                    reference_id,
                    payment_method,
                    status,
                    description,
                    gateway_response,
                    message,
                    metadata,
                    created_at,
                    updated_at
                ) VALUES (
                    :transaction_id,
                    :user_id,
                    :amount,
                    NVL(:currency, 'BDT'),
                    :transaction_type,
                    :reference_type,
                    :reference_id,
                    :payment_method,
                    :status,
                    :description,
                    :gateway_response,
                    :message,
                    :metadata,
                    SYSTIMESTAMP,
                    SYSTIMESTAMP
                )";

        $stmt = oci_parse($this->conn, $sql);

        oci_bind_by_name($stmt, ":transaction_id", $data['transaction_id']);
        oci_bind_by_name($stmt, ":user_id", $data['user_id']);
        oci_bind_by_name($stmt, ":amount", $data['amount']);
        $currency = $data['currency'] ?? 'BDT';
        oci_bind_by_name($stmt, ":currency", $currency);
        oci_bind_by_name($stmt, ":transaction_type", $data['transaction_type']);
        oci_bind_by_name($stmt, ":reference_type", $data['reference_type']);
        oci_bind_by_name($stmt, ":reference_id", $data['reference_id']);
        oci_bind_by_name($stmt, ":payment_method", $data['payment_method']);
        oci_bind_by_name($stmt, ":status", $data['status']);
        oci_bind_by_name($stmt, ":description", $data['description']);

        $gateway_response = $data['gateway_response'] ?? null;
        $message = $data['message'] ?? null;
        $metadata = $data['metadata'] ?? null;

        oci_bind_by_name($stmt, ":gateway_response", $gateway_response);
        oci_bind_by_name($stmt, ":message", $message);
        oci_bind_by_name($stmt, ":metadata", $metadata);

        $success = oci_execute($stmt, OCI_NO_AUTO_COMMIT);

        if ($success) {
            oci_free_statement($stmt);
            return true;
        } else {
            $e = oci_error($stmt);
            error_log("Transaction insert failed: " . $e['message']);
            oci_free_statement($stmt);
            return false;
        }
    }

    public function generateTransactionId($prefix = 'TXN')
    {
        $datePart = date('Ymd_His');
        $randomPart = strtoupper(bin2hex(random_bytes(4)));
        return $prefix . '_' . $datePart . '_' . $randomPart;
    }

    // DASHBOARD STATS 
    public function masterWallet()
    {
        $sql =
            "SELECT NVL(SUM(
                CASE 
                    WHEN transaction_type = 'PAYMENT' 
                    AND reference_type IN ('ORDER','SUBSCRIPTION')
                    AND status = 'SUCCESS' 
                    THEN amount
                    WHEN transaction_type = 'PAYOUT' 
                    AND reference_type IN ('WITHDRAWAL','REFUND')
                    AND status = 'SUCCESS' 
                    THEN -amount
                    ELSE 0
                END
            ), 0) FROM payment_transactions";

        $stmt = oci_parse($this->conn, $sql);
        oci_execute($stmt);
        $row = oci_fetch_row($stmt);
        oci_free_statement($stmt);
        return $row[0] ?? 0;
    }

    public function orderWallet()
    {
        $sql = "SELECT 
                NVL(SUM(o.total_amount), 0) as order_total,
                NVL(SUM(o.delivery_fee), 0) as delivery_fee,
                NVL(SUM(o.total_amount + o.delivery_fee), 0) as total_amount
            FROM payment_transactions pt
            JOIN orders o 
                ON pt.reference_id = o.order_id
                AND pt.reference_type = 'ORDER'
                AND pt.transaction_type = 'PAYMENT'
                AND pt.status = 'SUCCESS'
            WHERE o.status != 'CANCELLED'";

        $stmt = oci_parse($this->conn, $sql);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return [
            'order_total' => (float)($row['ORDER_TOTAL'] ?? 0),
            'delivery_fee' => (float)($row['DELIVERY_FEE'] ?? 0),
            'total_amount' => (float)($row['TOTAL_AMOUNT'] ?? 0)
        ];
    }

    public function subscriptionFee()
    {
        $sql =
            "SELECT NVL(SUM(pt.amount), 0)
            FROM payment_transactions pt
            WHERE pt.reference_type = 'SUBSCRIPTION'
                AND pt.transaction_type = 'PAYMENT'
                AND pt.status = 'SUCCESS'";

        $stmt = oci_parse($this->conn, $sql);
        oci_execute($stmt);
        $row = oci_fetch_row($stmt);
        oci_free_statement($stmt);
        return $row[0] ?? 0;
    }

    public function orderCommission()
    {
        $sql =
            "SELECT 
                o.order_id,
                o.total_amount,
                sp.commission_rate,
                o.total_amount * sp.commission_rate / 100 as order_commission,
                ss.status as subscription_status,
                ss.start_date,
                ss.end_date
            FROM orders o
            JOIN payment_transactions pt 
                ON pt.reference_id = o.order_id
                AND pt.reference_type = 'ORDER'
                AND pt.transaction_type = 'PAYMENT'
                AND pt.status = 'SUCCESS'
            JOIN kitchens k 
                ON o.kitchen_id = k.kitchen_id
            JOIN (
                SELECT 
                    seller_id,
                    plan_id,
                    start_date,
                    end_date,
                    status
                FROM (
                    SELECT 
                        ss.*,
                        ROW_NUMBER() OVER (
                            PARTITION BY seller_id, start_date, end_date 
                            ORDER BY 
                                CASE status 
                                    WHEN 'ACTIVE' THEN 1
                                    WHEN 'EXPIRED' THEN 2  
                                    WHEN 'CANCELLED' THEN 3
                                    ELSE 4
                                END,
                                start_date DESC
                        ) as rn
                    FROM seller_subscriptions ss
                    WHERE status IN ('ACTIVE', 'EXPIRED', 'CANCELLED')
                )
                WHERE rn = 1
            ) ss ON ss.seller_id = k.owner_id
                AND o.created_at BETWEEN ss.start_date AND ss.end_date
            JOIN subscription_plans sp 
                ON sp.plan_id = ss.plan_id
            WHERE o.status = 'DELIVERED'
            ORDER BY o.order_id";

        $stmt = oci_parse($this->conn, $sql);
        oci_execute($stmt);
        $total = 0;
        while ($row = oci_fetch_assoc($stmt)) {
            $total += $row['ORDER_COMMISSION'];
        }

        return $total;
    }

    public function sellerWithdrawals()
    {
        $sql =
            "SELECT NVL(SUM(wr.amount), 0)
            FROM withdraw_requests wr
            JOIN payment_transactions pt 
                ON pt.reference_id = wr.withdraw_id
                AND pt.reference_type = 'WITHDRAWAL'
                AND pt.transaction_type = 'PAYOUT'
            WHERE wr.status = 'PROCESSED'
                AND pt.status = 'SUCCESS'";

        $stmt = oci_parse($this->conn, $sql);
        oci_execute($stmt);
        $row = oci_fetch_row($stmt);
        oci_free_statement($stmt);
        return $row[0] ?? 0;
    }

    public function orderRefunds()
    {
        $sql = "SELECT 
            NVL(SUM(o.total_amount), 0) as cancelled_order_total,
            NVL(SUM(o.delivery_fee), 0) as cancelled_delivery_fee,
            NVL(SUM(o.total_amount + o.delivery_fee), 0) as cancelled_total_amount,
            COUNT(o.order_id) as cancelled_count
        FROM payment_transactions pt
        JOIN orders o 
            ON pt.reference_id = o.order_id
            AND pt.reference_type = 'ORDER'
            AND pt.transaction_type = 'PAYMENT'
            AND pt.status = 'SUCCESS'
        WHERE o.status = 'CANCELLED'";

        $stmt = oci_parse($this->conn, $sql);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return [
            'cancelled_order_total' => (float)($row['CANCELLED_ORDER_TOTAL'] ?? 0),
            'cancelled_delivery_fee' => (float)($row['CANCELLED_DELIVERY_FEE'] ?? 0),
            'cancelled_total_amount' => (float)($row['CANCELLED_TOTAL_AMOUNT'] ?? 0),
            'cancelled_count' => (int)($row['CANCELLED_COUNT'] ?? 0)
        ];
    }

    public function buyerRefunds()
    {
        $sql =
            "SELECT NVL(SUM(rr.amount), 0)
            FROM refund_requests rr
            JOIN payment_transactions pt 
                ON pt.reference_id = rr.refund_id
                AND pt.reference_type = 'REFUND'
                AND pt.transaction_type = 'PAYOUT'
            WHERE rr.status = 'PROCESSED'
                AND pt.status = 'SUCCESS'";

        $stmt = oci_parse($this->conn, $sql);
        oci_execute($stmt);
        $row = oci_fetch_row($stmt);
        oci_free_statement($stmt);
        return $row[0] ?? 0;
    }

    public function countWithdrawByStatus($status)
    {
        $statusTolower = strtolower($status);
        $sql = "SELECT COUNT(*) FROM withdraw_requests WHERE LOWER(status) = :status";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':status', $statusTolower);
        oci_execute($stmt);
        $row = oci_fetch_row($stmt);
        oci_free_statement($stmt);
        return $row[0] ?? 0;
    }

    public function complectRefunds()
    {
        $sql =
            "SELECT NVL(SUM(rr.amount), 0)
            FROM refund_requests rr
            JOIN payment_transactions pt 
                ON pt.reference_id = rr.refund_id
                AND pt.reference_type = 'REFUND'
                AND pt.transaction_type = 'PAYOUT'
            WHERE rr.status = 'PROCESSED'
                AND pt.status = 'SUCCESS'";

        $stmt = oci_parse($this->conn, $sql);
        oci_execute($stmt);
        $row = oci_fetch_row($stmt);
        oci_free_statement($stmt);
        return $row[0] ?? 0;
    }

    public function countRefundByStatus($status)
    {
        $statusTolower = strtolower($status);
        $sql = "SELECT COUNT(*) FROM refund_requests WHERE LOWER(status) = :status";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':status', $statusTolower);
        oci_execute($stmt);
        $row = oci_fetch_row($stmt);
        oci_free_statement($stmt);
        return $row[0] ?? 0;
    }

    // GROWTH STSTS 

    public function incomeGrowth($months = 12)
    {
        $sql =
            "WITH order_commissions AS (
                SELECT 
                    TRUNC(o.created_at, 'MM') AS month_start,
                    SUM(o.total_amount * sp.commission_rate / 100) AS commission_amount
                FROM orders o
                JOIN payment_transactions pt 
                    ON pt.reference_id = o.order_id
                    AND pt.reference_type = 'ORDER'
                    AND pt.transaction_type = 'PAYMENT'
                    AND pt.status = 'SUCCESS'
                JOIN kitchens k 
                    ON o.kitchen_id = k.kitchen_id
                JOIN seller_subscriptions ss 
                    ON ss.seller_id = k.owner_id
                    AND ss.status = 'ACTIVE'
                    AND o.created_at BETWEEN ss.start_date AND ss.end_date
                JOIN subscription_plans sp 
                    ON sp.plan_id = ss.plan_id
                WHERE o.created_at >= ADD_MONTHS(TRUNC(SYSDATE, 'MM'), -:months)
                    AND o.status = 'DELIVERED'
                GROUP BY TRUNC(o.created_at, 'MM')
            ),
            subscription_income AS (
                SELECT 
                    TRUNC(created_at, 'MM') AS month_start,
                    SUM(amount) AS subscription_amount
                FROM payment_transactions
                WHERE created_at >= ADD_MONTHS(TRUNC(SYSDATE, 'MM'), -:months)
                    AND reference_type = 'SUBSCRIPTION'
                    AND status = 'SUCCESS'
                    AND transaction_type = 'PAYMENT'
                GROUP BY TRUNC(created_at, 'MM')
            ),
            all_months AS (
                SELECT ADD_MONTHS(TRUNC(SYSDATE, 'MM'), -LEVEL + 1) AS month_start
                FROM dual
                CONNECT BY LEVEL <= :months
                ORDER BY month_start
            )
            SELECT 
                TO_CHAR(am.month_start, 'Mon YYYY') AS month_name,
                TO_CHAR(am.month_start, 'YYYY-MM') AS month,
                NVL(oc.commission_amount, 0) AS order_commissions,
                NVL(si.subscription_amount, 0) AS subscription_fees
            FROM all_months am
            LEFT JOIN order_commissions oc ON am.month_start = oc.month_start
            LEFT JOIN subscription_income si ON am.month_start = si.month_start
            ORDER BY am.month_start";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':months', $months);
        oci_execute($stmt);

        $result = [];
        while ($r = oci_fetch_assoc($stmt)) {
            $r = $this->processRow($r);
            $result[] = [
                'MONTH' => $r['MONTH'],
                'MONTH_NAME' => $r['MONTH_NAME'],
                'ORDER_COMMISSIONS' => (float)$r['ORDER_COMMISSIONS'],
                'SUBSCRIPTION_FEES' => (float)$r['SUBSCRIPTION_FEES']
            ];
        }

        return $result;
    }

    public function orderGrowth($months = 12)
    {
        $sql =
            "SELECT 
                TO_CHAR(TRUNC(created_at, 'MM'), 'Mon YYYY') AS month_name,
                TO_CHAR(TRUNC(created_at, 'MM'), 'YYYY-MM') AS month,
                COUNT(*) AS total_orders,
                SUM(CASE WHEN status = 'DELIVERED' THEN 1 ELSE 0 END) AS completed_orders,
                SUM(CASE WHEN status = 'CANCELLED' THEN 1 ELSE 0 END) AS cancelled_orders
            FROM orders
            WHERE created_at >= ADD_MONTHS(TRUNC(SYSDATE, 'MM'), -:months)
            GROUP BY TRUNC(created_at, 'MM')
            ORDER BY TRUNC(created_at, 'MM')";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':months', $months);
        oci_execute($stmt);

        $result = [];
        while ($r = oci_fetch_assoc($stmt)) {
            $r = $this->processRow($r);
            $result[] = [
                'MONTH' => $r['MONTH'],
                'MONTH_NAME' => $r['MONTH_NAME'],
                'TOTAL_ORDERS' => (int)$r['TOTAL_ORDERS'],
                'COMPLETED_ORDERS' => (int)$r['COMPLETED_ORDERS'],
                'CANCELLED_ORDERS' => (int)$r['CANCELLED_ORDERS']
            ];
        }

        return $result;
    }

    public function userGrowth($months = 12)
    {
        $sql =
            "SELECT 
                TO_CHAR(TRUNC(created_at, 'MM'), 'Mon YYYY') AS month_name,
                TO_CHAR(TRUNC(created_at, 'MM'), 'YYYY-MM') AS month,
                SUM(CASE WHEN role = 'buyer' THEN 1 ELSE 0 END) AS new_buyers,
                SUM(CASE WHEN role = 'seller' THEN 1 ELSE 0 END) AS new_sellers
            FROM users
            WHERE created_at >= ADD_MONTHS(TRUNC(SYSDATE, 'MM'), -:months)
            GROUP BY TRUNC(created_at, 'MM')
            ORDER BY TRUNC(created_at, 'MM')";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':months', $months);
        oci_execute($stmt);

        $result = [];
        while ($r = oci_fetch_assoc($stmt)) {
            $r = $this->processRow($r);
            $result[] = [
                'MONTH' => $r['MONTH'],
                'MONTH_NAME' => $r['MONTH_NAME'],
                'NEW_BUYERS' => (int)$r['NEW_BUYERS'],
                'NEW_SELLERS' => (int)$r['NEW_SELLERS']
            ];
        }

        return $result;
    }

    // HELPER METHODS

    public function beginTransaction()
    {
        return true;
    }

    public function commitTransaction()
    {
        return oci_commit($this->conn);
    }

    public function rollbackTransaction()
    {
        return oci_rollback($this->conn);
    }

    private function processRow($row)
    {
        if (!$row) {
            return false;
        }

        // Convert LOB to string
        foreach ($row as $key => $value) {
            if (is_object($value) && get_class($value) === 'OCILob') {
                $row[$key] = $value->load() ?: '';
            }
        }

        // Format amounts
        if (isset($row['AMOUNT'])) {
            $row['AMOUNT'] = (float)$row['AMOUNT'];
        }

        if (isset($row['TOTAL_AMOUNT'])) {
            $row['TOTAL_AMOUNT'] = (float)$row['TOTAL_AMOUNT'];
        }

        if (isset($row['DELIVERY_FEE'])) {
            $row['DELIVERY_FEE'] = (float)$row['DELIVERY_FEE'];
        }

        if (isset($row['ORDER_TOTAL'])) {
            $row['ORDER_TOTAL'] = (float)$row['ORDER_TOTAL'];
        }

        if (isset($row['AVAILABLE_BALANCE'])) {
            $row['AVAILABLE_BALANCE'] = (float)$row['AVAILABLE_BALANCE'];
        }

        if (isset($row['REFUND_PERCENTAGE'])) {
            $row['REFUND_PERCENTAGE'] = (float)$row['REFUND_PERCENTAGE'];
        }

        return $row;
    }
}
