<?php

class Refund
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getBuyerRefunds($userId, $statusFilter = 'all', $searchTerm = '', $limit = 10, $offset = 0)
    {
        $sql = "SELECT 
                r.refund_id,
                r.order_id,
                r.amount,
                r.reason,
                r.method,
                r.account_details,
                r.status,
                r.admin_notes,
                r.created_at,
                r.updated_at,
                o.total_amount,
                o.delivery_fee,
                o.status as order_status,
                o.cancel_by,
                k.kitchen_id,
                k.name as kitchen_name,
                k.cover_image,
                pt.transaction_id,
                pt.payment_method as transaction_method,
                pt.status as transaction_status,
                pt.created_at as transaction_created_at,
                pt.message as transaction_message
            FROM refund_requests r
            JOIN orders o ON r.order_id = o.order_id
            JOIN kitchens k ON o.kitchen_id = k.kitchen_id
            LEFT JOIN payment_transactions pt 
                ON pt.reference_type = 'REFUND'
                AND pt.reference_id = r.refund_id
                AND pt.transaction_type = 'PAYOUT'
            WHERE r.buyer_id = :user_id
            AND r.buyer_delete = 0";

        if ($statusFilter !== 'all') {
            $sql .= " AND LOWER(r.status) = LOWER(:status)";
        }

        if (!empty($searchTerm)) {
            $searchPattern = '%' . strtolower($searchTerm) . '%';
            $sql .= " AND (
                LOWER(k.name) LIKE :search OR 
                TO_CHAR(r.order_id) LIKE :search_num OR 
                LOWER(r.reason) LIKE :search OR 
                LOWER(r.status) LIKE :search
            )";
        }

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM refund_requests r 
                     JOIN orders o ON r.order_id = o.order_id 
                     JOIN kitchens k ON o.kitchen_id = k.kitchen_id 
                     WHERE r.buyer_id = :user_id AND r.buyer_delete = 0";

        if ($statusFilter !== 'all') {
            $countSql .= " AND LOWER(r.status) = LOWER(:status)";
        }

        if (!empty($searchTerm)) {
            $countSql .= " AND (
                LOWER(k.name) LIKE :search OR 
                TO_CHAR(r.order_id) LIKE :search_num OR 
                LOWER(r.reason) LIKE :search OR 
                LOWER(r.status) LIKE :search
            )";
        }

        $countStmt = oci_parse($this->conn, $countSql);
        oci_bind_by_name($countStmt, ':user_id', $userId);

        if ($statusFilter !== 'all') {
            oci_bind_by_name($countStmt, ':status', $statusFilter);
        }

        if (!empty($searchTerm)) {
            $searchPattern = '%' . strtolower($searchTerm) . '%';
            $searchNumPattern = '%' . $searchTerm . '%';
            oci_bind_by_name($countStmt, ':search', $searchPattern);
            oci_bind_by_name($countStmt, ':search_num', $searchNumPattern);
        }

        oci_execute($countStmt);
        $countRow = oci_fetch_assoc($countStmt);
        $total = $countRow ? (int)$countRow['TOTAL'] : 0;
        oci_free_statement($countStmt);

        // Main query with pagination
        $sql .= " ORDER BY r.created_at DESC";
        if ($limit > 0) {
            $sql .= " OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";
        }

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':user_id', $userId);

        if ($statusFilter !== 'all') {
            oci_bind_by_name($stmt, ':status', $statusFilter);
        }

        if (!empty($searchTerm)) {
            $searchPattern = '%' . strtolower($searchTerm) . '%';
            $searchNumPattern = '%' . $searchTerm . '%';
            oci_bind_by_name($stmt, ':search', $searchPattern);
            oci_bind_by_name($stmt, ':search_num', $searchNumPattern);
        }

        if ($limit > 0) {
            $offsetVal = (int)$offset;
            $limitVal = (int)$limit;
            oci_bind_by_name($stmt, ':offset', $offsetVal);
            oci_bind_by_name($stmt, ':limit', $limitVal);
        }

        $refunds = [];
        if (oci_execute($stmt)) {
            while ($row = oci_fetch_assoc($stmt)) {
                $row['AMOUNT'] = (float)$row['AMOUNT'];
                $row['TOTAL_AMOUNT'] = (float)$row['TOTAL_AMOUNT'];
                $row['DELIVERY_FEE'] = (float)$row['DELIVERY_FEE'];

                $originalTotal = $row['TOTAL_AMOUNT'] + $row['DELIVERY_FEE'];
                if ($originalTotal > 0) {
                    $row['REFUND_PERCENTAGE'] = round(($row['AMOUNT'] / $originalTotal) * 100, 1);
                } else {
                    $row['REFUND_PERCENTAGE'] = 0;
                }

                $refunds[] = $row;
            }
        }

        oci_free_statement($stmt);
        return ['refunds' => $refunds, 'total' => $total];
    }

    public function getBuyerRefundStats($userId)
    {
        $sql = "SELECT 
                status,
                COUNT(*) as count,
                SUM(amount) as total_amount
            FROM refund_requests
            WHERE buyer_id = :user_id
            AND buyer_delete = 0
            GROUP BY status";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':user_id', $userId);
        oci_execute($stmt);

        $stats = [
            'total' => ['count' => 0, 'amount' => 0],
            'PENDING' => ['count' => 0, 'amount' => 0],
            'APPROVED' => ['count' => 0, 'amount' => 0],
            'REJECTED' => ['count' => 0, 'amount' => 0],
            'PROCESSED' => ['count' => 0, 'amount' => 0]
        ];

        while ($row = oci_fetch_assoc($stmt)) {
            $status = $row['STATUS'];
            if (isset($stats[$status])) {
                $stats[$status]['count'] = (int)$row['COUNT'];
                $stats[$status]['amount'] = (float)$row['TOTAL_AMOUNT'];
                $stats['total']['count'] += (int)$row['COUNT'];
                $stats['total']['amount'] += (float)$row['TOTAL_AMOUNT'];
            }
        }

        oci_free_statement($stmt);
        return $stats;
    }

    public function createRefundRequest($data)
    {
        $sql = "INSERT INTO refund_requests (
                order_id, 
                buyer_id, 
                amount, 
                method,
                account_details,
                reason,
                status,
                created_at,
                updated_at
            ) VALUES (
                :order_id, 
                :buyer_id, 
                :amount, 
                :method,
                :account_details,
                :reason,
                'PENDING',
                SYSTIMESTAMP,
                SYSTIMESTAMP
            )";

        $stmt = oci_parse($this->conn, $sql);

        oci_bind_by_name($stmt, ':order_id', $data['order_id']);
        oci_bind_by_name($stmt, ':buyer_id', $data['buyer_id']);
        oci_bind_by_name($stmt, ':amount', $data['amount']);
        oci_bind_by_name($stmt, ':method', $data['method']);
        oci_bind_by_name($stmt, ':account_details', $data['mobile_number']);
        oci_bind_by_name($stmt, ':reason', $data['reason']);

        $result = oci_execute($stmt);
        oci_free_statement($stmt);

        return $result;
    }

    public function refundRequestExists($orderId)
    {
        $sql = "SELECT COUNT(*) as count 
                FROM refund_requests 
                WHERE order_id = :order_id
                AND buyer_delete = 0";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':order_id', $orderId);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        $count = $row['COUNT'] ?? 0;

        oci_free_statement($stmt);
        return $count > 0;
    }

    public function getRefundStatus($orderId)
    {
        $sql = "SELECT status
            FROM refund_requests
            WHERE order_id = :order_id
            AND buyer_delete = 0
            ORDER BY created_at DESC
            FETCH FIRST 1 ROWS ONLY";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':order_id', $orderId);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row['STATUS'] ?? null;
    }

    public function getRefundRequestById($refundId)
    {
        $sql = "SELECT r.*, o.order_id, o.total_amount, o.delivery_fee, o.status as order_status,
                       o.cancel_by, k.name as kitchen_name, k.cover_image
                FROM refund_requests r
                JOIN orders o ON r.order_id = o.order_id
                JOIN kitchens k ON o.kitchen_id = k.kitchen_id
                WHERE r.refund_id = :refund_id";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':refund_id', $refundId);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        if ($row) {
            $row['AMOUNT'] = (float)$row['AMOUNT'];
            $row['TOTAL_AMOUNT'] = (float)$row['TOTAL_AMOUNT'];
            $row['DELIVERY_FEE'] = (float)$row['DELIVERY_FEE'];

            $originalTotal = $row['TOTAL_AMOUNT'] + $row['DELIVERY_FEE'];
            if ($originalTotal > 0) {
                $row['REFUND_PERCENTAGE'] = round(($row['AMOUNT'] / $originalTotal) * 100, 1);
            } else {
                $row['REFUND_PERCENTAGE'] = 0;
            }
        }

        return $row ?: null;
    }

    public function calculateRefundAmount($orderTotal, $deliveryFee, $orderStatus, $cancelledBy = null)
    {
        $foodAmount = $orderTotal;
        $originalTotal = $orderTotal + $deliveryFee;

        if (strtoupper($orderStatus) === 'CANCELLED') {
            $cancelledBy = strtoupper($cancelledBy ?? '');

            if ($cancelledBy === 'SELLER') {
                return [
                    'food_amount' => $foodAmount,
                    'delivery_fee' => $deliveryFee,
                    'service_charge' => 0,
                    'total_refundable' => $originalTotal,
                    'refund_policy' => 'Full refund (seller cancelled)',
                    'service_charge_percent' => 0
                ];
            } elseif ($cancelledBy === 'BUYER') {
                $serviceCharge = $foodAmount * 0.10;
                return [
                    'food_amount' => $foodAmount,
                    'delivery_fee' => $deliveryFee,
                    'service_charge' => $serviceCharge,
                    'total_refundable' => $originalTotal - $serviceCharge,
                    'refund_policy' => 'Partial refund minus 10% service charge (buyer cancelled)',
                    'service_charge_percent' => 10
                ];
            } else {
                return [
                    'food_amount' => $foodAmount,
                    'delivery_fee' => $deliveryFee,
                    'service_charge' => 0,
                    'total_refundable' => $originalTotal,
                    'refund_policy' => 'Full refund (system cancelled)',
                    'service_charge_percent' => 0
                ];
            }
        } elseif (strtoupper($orderStatus) === 'DELIVERED') {
            return [
                'food_amount' => $foodAmount,
                'delivery_fee' => $deliveryFee,
                'service_charge' => 0,
                'total_refundable' => $originalTotal,
                'refund_policy' => 'Case-by-case review required',
                'service_charge_percent' => 0
            ];
        }

        return null;
    }

    public function isOrderEligibleForRefund($orderId, $userId)
    {
        $sql = "SELECT o.status, o.total_amount, o.delivery_fee, o.created_at,
                   o.buyer_id, o.kitchen_id, o.cancel_by,
                   (SELECT COUNT(*) FROM refund_requests rr WHERE rr.order_id = o.order_id AND rr.buyer_delete = 0) as refund_count
            FROM orders o
            WHERE o.order_id = :order_id";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':order_id', $orderId);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        if (!$row) {
            return ['eligible' => false, 'message' => 'Order not found'];
        }

        if ($row['BUYER_ID'] != $userId) {
            return ['eligible' => false, 'message' => 'You are not authorized to request refund for this order'];
        }

        if ($row['REFUND_COUNT'] > 0) {
            return ['eligible' => false, 'message' => 'Refund already requested for this order'];
        }

        $status = strtoupper($row['STATUS']);
        if ($status !== 'CANCELLED') {
            return ['eligible' => false, 'message' => 'Only cancelled orders are eligible for refund'];
        }

        $checkSql = "SELECT CASE WHEN (SYSDATE - :created_at) <= 7 THEN 1 ELSE 0 END as within_limit FROM DUAL";
        $checkStmt = oci_parse($this->conn, $checkSql);
        oci_bind_by_name($checkStmt, ':created_at', $row['CREATED_AT']);
        oci_execute($checkStmt);
        $checkRow = oci_fetch_assoc($checkStmt);
        oci_free_statement($checkStmt);

        if (!$checkRow['WITHIN_LIMIT']) {
            return ['eligible' => false, 'message' => 'Refund period (7 days) has expired'];
        }

        $refundCalculation = $this->calculateRefundAmount(
            (float)$row['TOTAL_AMOUNT'],
            (float)$row['DELIVERY_FEE'],
            $row['STATUS'],
            $row['CANCEL_BY'] ?? ''
        );

        if (!$refundCalculation) {
            return ['eligible' => false, 'message' => 'Unable to calculate refund amount'];
        }

        return [
            'eligible' => true,
            'message' => 'Order is eligible for refund',
            'max_amount' => $refundCalculation['total_refundable'],
            'order_status' => $row['STATUS'],
            'cancelled_by' => $row['CANCEL_BY'] ?? '',
            'calculation' => $refundCalculation
        ];
    }

    public function cancelRefundRequest($refundId, $userId)
    {
        $sql = "SELECT status FROM refund_requests 
                WHERE refund_id = :refund_id AND buyer_id = :user_id AND buyer_delete = 0";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':refund_id', $refundId);
        oci_bind_by_name($stmt, ':user_id', $userId);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        if (!$row) {
            return ['success' => false, 'message' => 'Refund request not found'];
        }

        if (strtoupper($row['STATUS']) !== 'PENDING') {
            return ['success' => false, 'message' => 'Only pending refund requests can be cancelled'];
        }

        $updateSql = "UPDATE refund_requests 
                     SET status = 'CANCELLED',
                         updated_at = SYSTIMESTAMP
                     WHERE refund_id = :refund_id";

        $updateStmt = oci_parse($this->conn, $updateSql);
        oci_bind_by_name($updateStmt, ':refund_id', $refundId);
        $result = oci_execute($updateStmt);
        oci_free_statement($updateStmt);

        return $result ?
            ['success' => true, 'message' => 'Refund request cancelled successfully'] :
            ['success' => false, 'message' => 'Failed to cancel refund request'];
    }

    public function getOrderForRefundForm($orderId, $userId)
    {
        $sql = "SELECT 
                o.order_id,
                o.total_amount,
                o.delivery_fee,
                o.status,
                o.cancel_by,
                o.created_at,
                k.name as kitchen_name,
                k.cover_image
            FROM orders o
            JOIN kitchens k ON o.kitchen_id = k.kitchen_id
            WHERE o.order_id = :order_id AND o.buyer_id = :user_id";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':order_id', $orderId);
        oci_bind_by_name($stmt, ':user_id', $userId);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        if (!$row) {
            return null;
        }

        $row['TOTAL_AMOUNT'] = (float)$row['TOTAL_AMOUNT'];
        $row['DELIVERY_FEE'] = (float)$row['DELIVERY_FEE'];

        $refundCalculation = $this->calculateRefundAmount(
            $row['TOTAL_AMOUNT'],
            $row['DELIVERY_FEE'],
            $row['STATUS'],
            $row['CANCEL_BY'] ?? ''
        );

        $row['REFUND_CALCULATION'] = $refundCalculation;
        $row['ELIGIBLE'] = ($row['STATUS'] === 'CANCELLED');

        return $row;
    }

    public function getAllRefunds($statusFilter = 'all', $searchTerm = '', $limit = 10, $offset = 0)
    {
        $sql = "SELECT 
                r.refund_id,
                r.order_id,
                r.amount,
                r.reason,
                r.method,
                r.account_details,
                r.status,
                r.admin_notes,
                r.created_at,
                r.updated_at,
                o.total_amount,
                o.delivery_fee,
                o.status as order_status,
                o.cancel_by,
                k.name as kitchen_name,
                u.full_name as buyer_name,
                u.email as buyer_email
            FROM refund_requests r
            JOIN orders o ON r.order_id = o.order_id
            JOIN kitchens k ON o.kitchen_id = k.kitchen_id
            JOIN users u ON r.buyer_id = u.user_id
            WHERE 1=1";

        if ($statusFilter !== 'all') {
            $sql .= " AND LOWER(r.status) = LOWER(:status)";
        }

        if (!empty($searchTerm)) {
            $searchPattern = '%' . strtolower($searchTerm) . '%';
            $sql .= " AND (
                LOWER(k.name) LIKE :search OR 
                LOWER(u.full_name) LIKE :search OR 
                LOWER(u.email) LIKE :search OR 
                TO_CHAR(r.order_id) LIKE :search_num OR 
                LOWER(r.reason) LIKE :search
            )";
        }

        $countSql = "SELECT COUNT(*) as total FROM refund_requests r 
                     JOIN orders o ON r.order_id = o.order_id 
                     JOIN kitchens k ON o.kitchen_id = k.kitchen_id 
                     JOIN users u ON r.buyer_id = u.user_id 
                     WHERE 1=1";

        if ($statusFilter !== 'all') {
            $countSql .= " AND LOWER(r.status) = LOWER(:status)";
        }

        if (!empty($searchTerm)) {
            $countSql .= " AND (
                LOWER(k.name) LIKE :search OR 
                LOWER(u.full_name) LIKE :search OR 
                LOWER(u.email) LIKE :search OR 
                TO_CHAR(r.order_id) LIKE :search_num OR 
                LOWER(r.reason) LIKE :search
            )";
        }

        $countStmt = oci_parse($this->conn, $countSql);

        if ($statusFilter !== 'all') {
            oci_bind_by_name($countStmt, ':status', $statusFilter);
        }

        if (!empty($searchTerm)) {
            $searchPattern = '%' . strtolower($searchTerm) . '%';
            $searchNumPattern = '%' . $searchTerm . '%';
            oci_bind_by_name($countStmt, ':search', $searchPattern);
            oci_bind_by_name($countStmt, ':search_num', $searchNumPattern);
        }

        oci_execute($countStmt);
        $countRow = oci_fetch_assoc($countStmt);
        $total = $countRow ? (int)$countRow['TOTAL'] : 0;
        oci_free_statement($countStmt);

        $sql .= " ORDER BY r.created_at DESC";
        if ($limit > 0) {
            $sql .= " OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";
        }

        $stmt = oci_parse($this->conn, $sql);

        if ($statusFilter !== 'all') {
            oci_bind_by_name($stmt, ':status', $statusFilter);
        }

        if (!empty($searchTerm)) {
            $searchPattern = '%' . strtolower($searchTerm) . '%';
            $searchNumPattern = '%' . $searchTerm . '%';
            oci_bind_by_name($stmt, ':search', $searchPattern);
            oci_bind_by_name($stmt, ':search_num', $searchNumPattern);
        }

        if ($limit > 0) {
            $offsetVal = (int)$offset;
            $limitVal = (int)$limit;
            oci_bind_by_name($stmt, ':offset', $offsetVal);
            oci_bind_by_name($stmt, ':limit', $limitVal);
        }

        $refunds = [];
        if (oci_execute($stmt)) {
            while ($row = oci_fetch_assoc($stmt)) {
                $row['AMOUNT'] = (float)$row['AMOUNT'];
                $row['TOTAL_AMOUNT'] = (float)$row['TOTAL_AMOUNT'];
                $row['DELIVERY_FEE'] = (float)$row['DELIVERY_FEE'];

                $originalTotal = $row['TOTAL_AMOUNT'] + $row['DELIVERY_FEE'];
                if ($originalTotal > 0) {
                    $row['REFUND_PERCENTAGE'] = round(($row['AMOUNT'] / $originalTotal) * 100, 1);
                } else {
                    $row['REFUND_PERCENTAGE'] = 0;
                }

                $refunds[] = $row;
            }
        }

        oci_free_statement($stmt);
        return ['refunds' => $refunds, 'total' => $total];
    }
}
