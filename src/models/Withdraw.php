<?php
class Withdraw
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    private function convertLobToString($value)
    {
        if (is_object($value) && get_class($value) === 'OCILob') {
            return $value->load() ?: '';
        }
        return $value;
    }

    private function processRow($row)
    {
        if (!$row) {
            return false;
        }

        foreach ($row as $key => $value) {
            $row[$key] = $this->convertLobToString($value);
        }
        return $row;
    }

    public function getSellerBalance($sellerId)
    {
        $sql = "SELECT 
                    k.kitchen_id,
                    u.user_id,
                    -- Total earnings (order amounts minus commission)
                    (SELECT NVL(SUM((o.total_amount + o.delivery_fee) - (o.total_amount * valid_sp.commission_rate / 100)), 0)
                     FROM orders o
                     JOIN payment_transactions pt ON pt.reference_id = o.order_id
                         AND pt.reference_type = 'ORDER'
                         AND pt.transaction_type = 'PAYMENT' 
                         AND pt.status = 'SUCCESS'
                     JOIN (
                         SELECT 
                             seller_id,
                             plan_id,
                             start_date,
                             end_date
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
                     ) valid_ss ON valid_ss.seller_id = k.owner_id
                         AND o.created_at BETWEEN valid_ss.start_date AND valid_ss.end_date
                     JOIN subscription_plans valid_sp ON valid_ss.plan_id = valid_sp.plan_id
                     WHERE o.kitchen_id = k.kitchen_id 
                     AND o.status = 'DELIVERED') AS total_earnings,
                    
                    -- Total withdrawn
                    (SELECT NVL(SUM(wr.amount), 0) 
                     FROM withdraw_requests wr 
                     WHERE wr.seller_id = u.user_id 
                     AND wr.status IN ('PROCESSED')) AS total_withdrawn,
                    
                    -- Current balance
                    ((SELECT NVL(SUM((o.total_amount + o.delivery_fee) - (o.total_amount * valid_sp.commission_rate / 100)), 0)
                      FROM orders o
                      JOIN payment_transactions pt ON pt.reference_id = o.order_id
                          AND pt.reference_type = 'ORDER'
                          AND pt.transaction_type = 'PAYMENT' 
                          AND pt.status = 'SUCCESS'
                      JOIN (
                          SELECT 
                              seller_id,
                              plan_id,
                              start_date,
                              end_date
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
                      ) valid_ss ON valid_ss.seller_id = k.owner_id
                          AND o.created_at BETWEEN valid_ss.start_date AND valid_ss.end_date
                      JOIN subscription_plans valid_sp ON valid_ss.plan_id = valid_sp.plan_id
                      WHERE o.kitchen_id = k.kitchen_id 
                      AND o.status = 'DELIVERED') - 
                     (SELECT NVL(SUM(wr.amount), 0) 
                      FROM withdraw_requests wr 
                      WHERE wr.seller_id = u.user_id 
                      AND wr.status IN ('PROCESSED'))) AS current_balance,

                    -- Pending withdrawals
                    (SELECT NVL(SUM(wr.amount), 0) 
                     FROM withdraw_requests wr 
                     WHERE wr.seller_id = u.user_id 
                     AND wr.status IN ('PENDING', 'APPROVED')) AS pending_withdrawals

                FROM users u
                JOIN kitchens k ON k.owner_id = u.user_id
                WHERE u.user_id = :seller_id";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':seller_id', $sellerId);
        oci_execute($stmt);

        $balance = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $this->processRow($balance);
    }

    public function getWithdrawalHistory($sellerId)
    {
        $sql = "SELECT 
                wr.withdraw_id,
                wr.amount,
                wr.method,
                wr.account_details,
                wr.status,
                wr.created_at,
                wr.updated_at,
                wr.admin_notes
            FROM withdraw_requests wr
            WHERE wr.seller_id = :seller_id
            ORDER BY wr.created_at DESC";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':seller_id', $sellerId);
        oci_execute($stmt);

        $withdrawals = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $withdrawals[] = $this->processRow($row);
        }
        oci_free_statement($stmt);

        return $withdrawals;
    }

    public function createWithdrawalRequest($sellerId, $amount, $method, $accountDetails)
    {
        $sql = "INSERT INTO withdraw_requests (
                seller_id, amount, method, account_details, status, created_at
            ) VALUES (
                :seller_id, :amount, :method, :account_details, 'PENDING', CURRENT_TIMESTAMP
            ) RETURNING withdraw_id INTO :withdraw_id";

        $stmt = oci_parse($this->conn, $sql);

        // Declare a variable to hold the returned ID
        $withdrawId = null;

        oci_bind_by_name($stmt, ':seller_id', $sellerId);
        oci_bind_by_name($stmt, ':amount', $amount);
        oci_bind_by_name($stmt, ':method', $method);
        oci_bind_by_name($stmt, ':account_details', $accountDetails);
        oci_bind_by_name($stmt, ':withdraw_id', $withdrawId, -1, SQLT_INT);

        $result = oci_execute($stmt);

        if ($result) {
            oci_free_statement($stmt);
            return $withdrawId;
        } else {
            oci_free_statement($stmt);
            return false;
        }
    }

    public function hasPendingWithdrawals($sellerId)
    {
        $sql = "SELECT COUNT(*) as pending_count 
                FROM withdraw_requests 
                WHERE seller_id = :seller_id 
                AND status IN ('PENDING', 'APPROVED')";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':seller_id', $sellerId);
        oci_execute($stmt);

        $result = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return ($result['PENDING_COUNT'] ?? 0) > 0;
    }
}
