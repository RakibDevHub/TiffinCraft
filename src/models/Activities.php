<?php

class Activities
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getRecentActivities($limit = 10)
    {
        $activities = [];
        $cutoffDate = date('Y-m-d H:i:s', strtotime('-30 days'));

        $activities = array_merge(
            $this->getOrders($cutoffDate),
            $this->getPayments($cutoffDate, 'PAYMENT', 'payment_in', 'received_from'),
            $this->getPayments($cutoffDate, 'PAYOUT', 'payment_out', 'issued_to'),
            $this->getRefunds($cutoffDate),
            $this->getWithdrawals($cutoffDate),
            $this->getKitchens($cutoffDate),
            $this->getUsers($cutoffDate),
            $this->getSubscriptions($cutoffDate)
        );

        usort($activities, function ($a, $b) {
            $timeA = strtotime($a['created_at'] ?? '1970-01-01');
            $timeB = strtotime($b['created_at'] ?? '1970-01-01');
            return $timeB - $timeA;
        });

        $activities = array_slice($activities, 0, $limit);

        return $this->formatActivities($activities);
    }

    private function executeQuery($sql, $params = [])
    {
        $stmt = oci_parse($this->conn, $sql);

        foreach ($params as $key => $value) {
            oci_bind_by_name($stmt, ':' . $key, $params[$key]);
        }

        oci_execute($stmt);

        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = array_change_key_case($row, CASE_LOWER);
        }

        return $results;
    }

    private function getOrders($cutoffDate)
    {
        $sql = "SELECT 
                'order' AS type,
                o.order_id AS id,
                TO_CHAR(o.created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at,
                'New order for ' || k.name AS description,
                u.name AS user_name,
                'placed_by' AS user_relation
            FROM orders o
            JOIN users u ON o.buyer_id = u.user_id
            JOIN kitchens k ON o.kitchen_id = k.kitchen_id
            WHERE o.created_at >= TO_DATE(:cutoff, 'YYYY-MM-DD HH24:MI:SS')";

        return $this->executeQuery($sql, ['cutoff' => $cutoffDate]);
    }

    private function getPayments($cutoffDate, $transactionType, $type, $relation)
    {
        $sql = "SELECT 
                :type AS type,
                pt.id AS id,
                TO_CHAR(pt.created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at,
                'Payment: ৳' || pt.amount || ' (' || pt.reference_type || ')' AS description,
                u.name AS user_name,
                :relation AS user_relation
            FROM payment_transactions pt
            JOIN users u ON pt.user_id = u.user_id
            WHERE pt.created_at >= TO_DATE(:cutoff, 'YYYY-MM-DD HH24:MI:SS')
            AND pt.status = 'SUCCESS'
            AND pt.transaction_type = :trans_type";

        return $this->executeQuery($sql, [
            'cutoff' => $cutoffDate,
            'type' => $type,
            'relation' => $relation,
            'trans_type' => $transactionType
        ]);
    }

    private function getRefunds($cutoffDate)
    {
        $sql = "SELECT 
                'refund' AS type,
                rr.refund_id AS id,
                TO_CHAR(rr.created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at,
                'Refund ' || LOWER(rr.status) || ': ৳' || rr.amount AS description,
                u.name AS user_name,
                'requested_by' AS user_relation
            FROM refund_requests rr
            JOIN users u ON rr.buyer_id = u.user_id
            WHERE rr.created_at >= TO_DATE(:cutoff, 'YYYY-MM-DD HH24:MI:SS')
            AND rr.buyer_delete = 0
            AND rr.status != 'PROCESSED'";

        return $this->executeQuery($sql, ['cutoff' => $cutoffDate]);
    }

    private function getWithdrawals($cutoffDate)
    {
        $sql = "SELECT 
                'withdrawal' AS type,
                wr.withdraw_id AS id,
                TO_CHAR(wr.created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at,
                'Withdrawal ' || LOWER(wr.status) || ': ৳' || wr.amount AS description,
                u.name AS user_name,
                'requested_by' AS user_relation
            FROM withdraw_requests wr
            JOIN users u ON wr.seller_id = u.user_id
            WHERE wr.created_at >= TO_DATE(:cutoff, 'YYYY-MM-DD HH24:MI:SS')
            AND wr.status != 'PROCESSED'";

        return $this->executeQuery($sql, ['cutoff' => $cutoffDate]);
    }

    private function getKitchens($cutoffDate)
    {
        $sql = "SELECT 
                'kitchen' AS type,
                k.kitchen_id AS id,
                TO_CHAR(k.created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at,
                'New kitchen: ' || k.name AS description,
                u.name AS user_name,
                'registered_by' AS user_relation
            FROM kitchens k
            JOIN users u ON k.owner_id = u.user_id
            WHERE k.created_at >= TO_DATE(:cutoff, 'YYYY-MM-DD HH24:MI:SS')";

        return $this->executeQuery($sql, ['cutoff' => $cutoffDate]);
    }

    private function getUsers($cutoffDate)
    {
        $sql = "SELECT 
                'user' AS type,
                u.user_id AS id,
                TO_CHAR(u.created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at,
                'New user registered' AS description,
                u.name AS user_name,
                'self' AS user_relation
            FROM users u
            WHERE u.created_at >= TO_DATE(:cutoff, 'YYYY-MM-DD HH24:MI:SS')";

        return $this->executeQuery($sql, ['cutoff' => $cutoffDate]);
    }

    private function getSubscriptions($cutoffDate)
    {
        $sql = "SELECT 
                'subscription' AS type,
                s.subscription_id AS id,
                TO_CHAR(s.created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at,
                'Subscription: ' || p.plan_name AS description,
                u.name AS user_name,
                'subscribed_by' AS user_relation
            FROM seller_subscriptions s
            JOIN users u ON s.seller_id = u.user_id
            JOIN subscription_plans p ON s.plan_id = p.plan_id
            WHERE s.created_at >= TO_DATE(:cutoff, 'YYYY-MM-DD HH24:MI:SS')";

        return $this->executeQuery($sql, ['cutoff' => $cutoffDate]);
    }

    private function formatActivities($activities)
    {
        $formatted = [];

        foreach ($activities as $item) {
            $date = DateTime::createFromFormat('Y-m-d H:i:s', $item['created_at']);
            if (!$date) {
                $date = new DateTime();
            }

            $relationText = [
                'received_from' => 'Received from',
                'issued_to' => 'Issued to',
                'placed_by' => 'Placed by',
                'subscribed_by' => 'Subscribed by',
                'registered_by' => 'Registered by',
                'requested_by' => 'Requested by',
                'self' => ''
            ][$item['user_relation']] ?? '';

            if ($relationText && $item['user_name']) {
                $displayText = $relationText . ' ' . $item['user_name'] . ' • ' . $date->format('M j, Y g:i A');
            } elseif ($item['user_name']) {
                $displayText = $item['user_name'] . ' • ' . $date->format('M j, Y g:i A');
            } else {
                $displayText = $date->format('M j, Y g:i A');
            }

            $formatted[] = [
                'type' => $item['type'],
                'id' => $item['id'],
                'description' => $item['description'],
                'user' => $item['user_name'],
                'time' => $date->format('M j, Y g:i A'),
                'user_relation' => $displayText,
            ];
        }

        return $formatted;
    }


    // private function convertLobToString($value)
    // {
    //     if (is_object($value) && get_class($value) === 'OCILob') {
    //         return $value->load() ?: '';
    //     }
    //     return $value;
    // }

    // private function processRow($row)
    // {
    //     if (!$row) {
    //         return false;
    //     }

    //     foreach ($row as $key => $value) {
    //         $row[$key] = $this->convertLobToString($value);
    //     }
    //     return $row;
    // }


    // public function getRecentActivities($limit = 10)
    // {
    //     $sql =
    //         "SELECT * FROM (
    //         -- Orders
    //         SELECT 
    //             'order' AS type, 
    //             o.order_id AS id, 
    //             TO_CHAR(o.created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at, 
    //             'New order for ' || k.name AS description, 
    //             u.name AS user_name,
    //             NULL AS reference_id,
    //             NULL AS reference_type,
    //             NULL AS status,
    //             'placed_by' AS user_relation
    //         FROM orders o 
    //         JOIN users u ON o.buyer_id = u.user_id
    //         JOIN kitchens k ON o.kitchen_id = k.kitchen_id
    //         WHERE o.created_at >= SYSTIMESTAMP - 30

    //         UNION ALL

    //         -- Payment Transactions
    //         SELECT 
    //             'payment_in' AS type,
    //             pt.id AS id,
    //             TO_CHAR(pt.created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at,
    //             CASE 
    //                 WHEN pt.reference_type = 'ORDER' 
    //                     THEN 'Order payment: ৳' || pt.amount
    //                 WHEN pt.reference_type = 'SUBSCRIPTION' 
    //                     THEN 'Subscription payment: ৳' || pt.amount
    //                 ELSE 'Payment received: ৳' || pt.amount
    //             END AS description,
    //             u.name AS user_name,
    //             pt.reference_id,
    //             pt.reference_type,
    //             pt.status,
    //             'received_from' AS user_relation
    //         FROM payment_transactions pt
    //         JOIN users u ON pt.user_id = u.user_id
    //         WHERE pt.created_at >= SYSTIMESTAMP - 30
    //         AND pt.status = 'SUCCESS'
    //         AND pt.transaction_type = 'PAYMENT'

    //         UNION ALL

    //         -- Payment Transactions
    //         SELECT 
    //             'payment_out' AS type,
    //             pt.id AS id,
    //             TO_CHAR(pt.created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at,
    //             CASE 
    //                 WHEN pt.reference_type = 'WITHDRAWAL' 
    //                     THEN 'Seller withdrawal: ৳' || pt.amount
    //                 WHEN pt.reference_type = 'REFUND' 
    //                     THEN 'Buyer refund: ৳' || pt.amount
    //                 ELSE 'Payout: ৳' || pt.amount
    //             END AS description,
    //             u.name AS user_name,
    //             pt.reference_id,
    //             pt.reference_type,
    //             pt.status,
    //             'issued_to' AS user_relation
    //         FROM payment_transactions pt
    //         JOIN users u ON pt.user_id = u.user_id
    //         WHERE pt.created_at >= SYSTIMESTAMP - 30
    //         AND pt.status = 'SUCCESS'
    //         AND pt.transaction_type = 'PAYOUT'

    //         UNION ALL

    //         -- Refund Requests
    //         SELECT 
    //             'refund_request' AS type,
    //             rr.refund_id AS id,
    //             TO_CHAR(rr.created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at,
    //             CASE 
    //                 WHEN rr.status = 'PENDING' 
    //                     THEN 'Refund requested: ৳' || rr.amount || ' for Order #' || rr.order_id
    //                 WHEN rr.status = 'APPROVED' 
    //                     THEN 'Refund approved: ৳' || rr.amount || ' for Order #' || rr.order_id
    //                 WHEN rr.status = 'REJECTED' 
    //                     THEN 'Refund rejected: ৳' || rr.amount || ' for Order #' || rr.order_id
    //                 ELSE 'Refund: ৳' || rr.amount || ' for Order #' || rr.order_id
    //             END AS description,
    //             u.name AS user_name,
    //             rr.order_id AS reference_id,
    //             'ORDER' AS reference_type,
    //             rr.status,
    //             'requested_by' AS user_relation
    //         FROM refund_requests rr
    //         JOIN users u ON rr.buyer_id = u.user_id
    //         WHERE rr.created_at >= SYSTIMESTAMP - 30
    //         AND rr.buyer_delete = 0
    //         AND rr.status != 'PROCESSED'

    //         UNION ALL

    //         -- Withdrawal Requests
    //         SELECT 
    //             'withdrawal_request' AS type,
    //             wr.withdraw_id AS id,
    //             TO_CHAR(wr.created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at,
    //             CASE 
    //                 WHEN wr.status = 'PENDING' 
    //                     THEN 'Withdrawal requested: ৳' || wr.amount
    //                 WHEN wr.status = 'APPROVED' 
    //                     THEN 'Withdrawal approved: ৳' || wr.amount
    //                 WHEN wr.status = 'REJECTED' 
    //                     THEN 'Withdrawal rejected: ৳' || wr.amount
    //                 ELSE 'Withdrawal: ৳' || wr.amount
    //             END AS description,
    //             u.name AS user_name,
    //             wr.withdraw_id AS reference_id,
    //             'WITHDRAWAL' AS reference_type,
    //             wr.status,
    //             'requested_by' AS user_relation
    //         FROM withdraw_requests wr
    //         JOIN users u ON wr.seller_id = u.user_id
    //         WHERE wr.created_at >= SYSTIMESTAMP - 30
    //         AND wr.status != 'PROCESSED'

    //         UNION ALL

    //         -- Kitchens
    //         SELECT 
    //             'kitchen' AS type, 
    //             k.kitchen_id AS id, 
    //             TO_CHAR(k.created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at, 
    //             'New kitchen registered: ' || k.name AS description, 
    //             u.name AS user_name,
    //             NULL AS reference_id,
    //             NULL AS reference_type,
    //             NULL AS status,
    //             'registered_by' AS user_relation
    //         FROM kitchens k 
    //         JOIN users u ON k.owner_id = u.user_id
    //         WHERE k.created_at >= SYSTIMESTAMP - 30

    //         UNION ALL

    //         -- Users
    //         SELECT 
    //             'user' AS type, 
    //             u.user_id AS id, 
    //             TO_CHAR(u.created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at, 
    //             'New user registration' AS description, 
    //             u.name AS user_name,
    //             NULL AS reference_id,
    //             NULL AS reference_type,
    //             NULL AS status,
    //             'self' AS user_relation
    //         FROM users u
    //         WHERE u.created_at >= SYSTIMESTAMP - 30

    //         UNION ALL

    //         -- Subscriptions
    //         SELECT 
    //             'subscription' AS type,
    //             s.subscription_id AS id,
    //             TO_CHAR(s.created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at,
    //             CASE 
    //                 WHEN s.change_type = 'NEW' THEN 'New subscription: ' || p.plan_name
    //                 WHEN s.change_type = 'RENEWAL' THEN 'Subscription renewal: ' || p.plan_name
    //                 WHEN s.change_type = 'UPGRADE' THEN 'Subscription upgrade: ' || p.plan_name
    //                 WHEN s.change_type = 'DOWNGRADE' THEN 'Subscription downgrade: ' || p.plan_name
    //             END AS description,
    //             u.name AS user_name,
    //             NULL AS reference_id,
    //             NULL AS reference_type,
    //             NULL AS status,
    //             'subscribed_by' AS user_relation
    //         FROM seller_subscriptions s
    //         JOIN users u ON s.seller_id = u.user_id
    //         JOIN subscription_plans p ON s.plan_id = p.plan_id
    //         WHERE s.created_at >= SYSTIMESTAMP - 30
    //     )
    //     ORDER BY created_at DESC
    //     FETCH FIRST :limit ROWS ONLY";

    //     $stmt = oci_parse($this->conn, $sql);
    //     oci_bind_by_name($stmt, ':limit', $limit);
    //     oci_execute($stmt);

    //     $activities = [];
    //     while ($row = oci_fetch_assoc($stmt)) {
    //         $row = $this->processRow($row);

    //         $createdAt = $row['CREATED_AT'] ?? '';
    //         $type = $row['TYPE'] ?? '';
    //         $id = $row['ID'] ?? 0;
    //         $description = $row['DESCRIPTION'] ?? '';
    //         $userName = $row['USER_NAME'] ?? '';
    //         $userRelation = $row['USER_RELATION'] ?? '';

    //         $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $createdAt);
    //         if (!$dateTime) {
    //             $dateTime = new DateTime();
    //         }

    //         $relationText = match (strtolower($userRelation)) {
    //             'received_from' => 'Received from',
    //             'issued_to' => 'Issued to',
    //             'placed_by' => 'Placed by',
    //             'subscribed_by' => 'Subscribed by',
    //             'registered_by' => 'Registered by',
    //             'requested_by' => 'Requested by',
    //             'self' => '',
    //             default => ''
    //         };

    //         $displayText = '';
    //         if ($relationText && $userName) {
    //             $displayText = $relationText . ' ' . $userName . ' • ' . $dateTime->format('M j, Y g:i A');
    //         } elseif ($userName) {
    //             $displayText = $userName . ' • ' . $dateTime->format('M j, Y g:i A');
    //         } else {
    //             $displayText = $dateTime->format('M j, Y g:i A');
    //         }

    //         $activities[] = [
    //             'type' => strtolower($type),
    //             'id' => $id,
    //             'description' => $description,
    //             'user' => $userName,
    //             'time' => $dateTime->format('M j, Y g:i A'),
    //             'user_relation' => $displayText
    //         ];
    //     }

    //     return $activities;
    // }
}
