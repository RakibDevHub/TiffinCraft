<?php

class User
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

    public function countAll()
    {
        $sql = "SELECT COUNT(*) FROM users";
        // return $this->fetchValue($sql);
        $stmt = oci_parse($this->conn, $sql);
        oci_execute($stmt);
        $row = oci_fetch_row($stmt);
        oci_free_statement($stmt);
        return $row[0] ?? 0;
    }

    public function countByRole($role)
    {
        $roleLower = strtolower($role);
        $sql = "SELECT COUNT(*) FROM users WHERE LOWER(role) = :role";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':role', $roleLower);
        oci_execute($stmt);
        $row = oci_fetch_row($stmt);
        oci_free_statement($stmt);
        return $row[0] ?? 0;
    }

    public function topSellers($limit = 5)
    {
        $sql =
            "SELECT 
                k.kitchen_id,
                k.name AS kitchen_name,
                k.cover_image,
                u.name AS owner_name,
                u.user_id,
                COUNT(o.order_id) AS total_orders,
                NVL(SUM(o.total_amount + o.delivery_fee), 0) AS total_revenue,
                NVL(AVG(r.rating), 0) AS avg_rating,
                COUNT(r.review_id) AS total_reviews
            FROM kitchens k
            JOIN users u ON k.owner_id = u.user_id
            JOIN orders o ON k.kitchen_id = o.kitchen_id 
                AND o.status = 'DELIVERED'
                AND o.created_at >= ADD_MONTHS(SYSDATE, -1)
            LEFT JOIN reviews r ON k.kitchen_id = r.reference_id AND r.reference_type = 'KITCHEN'
            WHERE k.approval_status = 'approved'
            GROUP BY k.kitchen_id, k.name, k.cover_image, u.name, u.user_id
            ORDER BY total_revenue DESC, total_orders DESC
            FETCH FIRST :limit ROWS ONLY";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':limit', $limit);
        oci_execute($stmt);

        $sellers = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $row = $this->processRow($row);
            $sellers[] = $row;
        }

        oci_free_statement($stmt); 
        return $sellers;
    }

    public function countByStatus($status)
    {
        $statusLower = strtolower($status);
        $sql = "SELECT COUNT(*) FROM users WHERE LOWER(status) = :status";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':status', $statusLower);
        oci_execute($stmt);
        $row = oci_fetch_row($stmt);
        oci_free_statement($stmt);
        return $row[0] ?? 0;
    }

    public function getAllUserDetails($limit = 50, $offset = 0)
    {
        $limit = (int)$limit;
        $offset = (int)$offset;

        $sql =
            "SELECT 
                u.*,
                s.reason AS suspension_reason,
                s.suspended_until,
                s.status AS suspension_status,
                CASE 
                    WHEN s.reference_id IS NOT NULL 
                            AND s.reference_type = 'USER'
                            AND (s.suspended_until IS NULL OR s.suspended_until > SYSDATE) 
                    THEN 1 
                    ELSE 0 
                END AS is_suspended,
                -- Buyer information
                (SELECT COUNT(*) FROM orders o WHERE o.buyer_id = u.user_id) AS total_orders,
                (SELECT COUNT(*) FROM orders o WHERE o.buyer_id = u.user_id AND o.status = 'CANCELLED') AS cancelled_orders,
                (SELECT COUNT(*) FROM orders o WHERE o.buyer_id = u.user_id AND o.status = 'DELIVERED') AS completed_orders,
                
                -- Seller information
                k.name AS business_name,
                k.address AS business_address,
                (SELECT COUNT(*) FROM menu_items mi WHERE mi.kitchen_id = k.kitchen_id) AS total_products,
                (SELECT AVG(r.rating) FROM reviews r 
                JOIN orders o ON r.reference_id = o.order_id 
                JOIN menu_items mi ON o.kitchen_id = mi.kitchen_id 
                WHERE mi.kitchen_id = k.kitchen_id AND r.reference_type = 'KITCHEN') AS average_rating
            FROM users u
            LEFT JOIN suspensions s 
                ON u.user_id = s.reference_id
                AND s.reference_type = 'USER'
                AND s.status = 'active'
            LEFT JOIN kitchens k 
                ON u.user_id = k.owner_id
            ORDER BY u.created_at DESC
            OFFSET {$offset} ROWS FETCH NEXT {$limit} ROWS ONLY";

        $stmt = oci_parse($this->conn, $sql);

        if (!oci_execute($stmt)) {
            $error = oci_error($stmt);
            error_log("Oracle Error: " . $error['message']);
            return [];
        }

        $users = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $row = $this->processRow($row);
            $row['is_suspended'] = (bool)$row['IS_SUSPENDED'];
            $users[] = $row;
        }
        oci_free_statement($stmt);

        return $users;
    }

    public function getRecentUsers($limit = 5)
    {
        $limit = (int)$limit;
        $sql =
            "SELECT user_id, name, email, role, status, created_at
            FROM users
            ORDER BY created_at DESC
            FETCH FIRST {$limit} ROWS ONLY";

        $stmt = oci_parse($this->conn, $sql);
        oci_execute($stmt);

        $users = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $row = $this->processRow($row);
            $users[] = $row;
        }
        oci_free_statement($stmt);
        return $users;
    }

    public function countActiveSellers()
    {
        $sql =
            "SELECT COUNT(*)
            FROM users u
            JOIN seller_subscriptions ss ON ss.seller_id = u.user_id
            WHERE LOWER(u.role) = 'seller'
            AND LOWER(u.status) = 'active'
            AND LOWER(ss.status) = 'active'";

        $stmt = oci_parse($this->conn, $sql);
        oci_execute($stmt);
        $row = oci_fetch_row($stmt);
        oci_free_statement($stmt);
        return $row[0] ?? 0;
    }

    public function getActiveSellers()
    {
        $sql = 
            "SELECT u.user_id, u.name, u.email, u.phone, u.status, ss.status AS subscription_status
            FROM users u
            JOIN seller_subscriptions ss ON ss.seller_id = u.user_id
            WHERE LOWER(u.role) = 'seller'
              AND LOWER(u.status) = 'active'
              AND LOWER(ss.status) = 'active'
            ORDER BY u.created_at DESC";

        $stmt = oci_parse($this->conn, $sql);
        oci_execute($stmt);

        $sellers = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $row = $this->processRow($row);
            $sellers[] = $row;
        }
        oci_free_statement($stmt);
        return $sellers;
    }
}
