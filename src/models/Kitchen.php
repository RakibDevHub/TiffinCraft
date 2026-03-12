<?php

class Kitchen
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
        $sql = "SELECT COUNT(*) AS total FROM kitchens";
        $stmt = oci_parse($this->conn, $sql);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return (int)($row['TOTAL'] ?? 0);
    }

    public function countByStatus($status)
    {
        $statusLower = strtolower($status);
        $sql = "SELECT COUNT(*) AS total FROM kitchens WHERE LOWER(approval_status) = :status";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':status', $statusLower);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return (int)($row['TOTAL'] ?? 0);
    }

    private function kitchenStatusCheck()
    {
        $sql =
            "UPDATE seller_subscriptions 
            SET status = 'EXPIRED', updated_at = SYSTIMESTAMP 
            WHERE end_date < SYSDATE 
            AND status = 'ACTIVE'";

        $stmt = oci_parse($this->conn, $sql);
        oci_execute($stmt);
        oci_free_statement($stmt);
    }

    public function getAllKitchenDetails($limit = 50, $offset = 0)
    {
        $limit  = (int)$limit;
        $offset = (int)$offset;

        $this->kitchenStatusCheck();

        $sql =
            "SELECT 
            k.kitchen_id,
            k.name AS kitchen_name,
            k.description,
            k.cover_image,
            k.address AS kitchen_address,
            k.google_maps_url,
            k.years_experience,
            k.signature_dish,
            k.avg_prep_time,
            k.approval_status,
            k.created_at AS kitchen_created_at,
            k.updated_at AS kitchen_updated_at,

            u.user_id AS owner_id,
            u.name AS owner_name,
            u.email AS owner_email,
            u.phone AS owner_phone,
            u.profile_image AS owner_profile_image,
            u.status AS owner_status,

            latest_ss.subscription_id,
            latest_ss.start_date AS subscription_start,
            latest_ss.end_date AS subscription_end,
            latest_ss.status AS subscription_status,

            sp.plan_name,
            sp.monthly_fee,
            sp.commission_rate,
            sp.max_items,

            /* --- Suspension (SAFE: single active row) --- */
            CASE 
                WHEN ks.reference_id IS NOT NULL THEN 1 ELSE 0 
            END AS is_kitchen_suspended,
            ks.reason AS kitchen_suspension_reason,
            ks.suspended_until AS kitchen_suspended_until,

            /* --- Service Areas --- */
            (SELECT LISTAGG(sa.name, ', ') WITHIN GROUP (ORDER BY sa.name)
            FROM kitchen_service_zones ksz
            JOIN service_areas sa ON sa.area_id = ksz.area_id
            WHERE ksz.kitchen_id = k.kitchen_id) AS service_areas,

            (SELECT COUNT(*) 
            FROM kitchen_service_zones 
            WHERE kitchen_id = k.kitchen_id) AS total_service_areas,

            /* --- Orders --- */
            (SELECT COUNT(*) FROM orders WHERE kitchen_id = k.kitchen_id) AS total_orders,
            (SELECT COUNT(*) FROM orders WHERE kitchen_id = k.kitchen_id AND status = 'DELIVERED') AS completed_orders,
            (SELECT COUNT(*) FROM orders WHERE kitchen_id = k.kitchen_id AND status = 'CANCELLED') AS cancelled_orders,
            (SELECT COUNT(*) FROM orders WHERE kitchen_id = k.kitchen_id AND status = 'PENDING') AS pending_orders,

            /* --- Earnings --- */
            (SELECT NVL(SUM(total_amount + delivery_fee), 0)
            FROM orders
            WHERE kitchen_id = k.kitchen_id AND status = 'DELIVERED') AS total_earnings,

            /* --- Ratings --- */
            (SELECT AVG(r.rating) 
            FROM reviews r 
            WHERE r.reference_id = k.kitchen_id 
            AND r.reference_type = 'KITCHEN'
            AND r.status = 'PUBLIC') AS average_rating,

            (SELECT COUNT(*) 
            FROM reviews r 
            WHERE r.reference_id = k.kitchen_id 
            AND r.reference_type = 'KITCHEN') AS total_reviews,

            /* --- Menu --- */
            (SELECT COUNT(*) FROM menu_items WHERE kitchen_id = k.kitchen_id) AS total_menu_items,
            (SELECT COUNT(*) FROM menu_items WHERE kitchen_id = k.kitchen_id AND is_available = 1) AS available_menu_items,

            /* --- Activity --- */
            (SELECT MAX(created_at) FROM orders WHERE kitchen_id = k.kitchen_id) AS last_order_date,

            /* --- Completion Rate --- */
            CASE 
                WHEN (SELECT COUNT(*) FROM orders WHERE kitchen_id = k.kitchen_id) > 0
                THEN ROUND(
                    (SELECT COUNT(*) FROM orders WHERE kitchen_id = k.kitchen_id AND status = 'DELIVERED') /
                    (SELECT COUNT(*) FROM orders WHERE kitchen_id = k.kitchen_id) * 100, 2
                )
                ELSE 0
            END AS completion_rate,

            /* --- Combined Status --- */
            CASE
                WHEN ks.reference_id IS NOT NULL THEN 'suspended'
                WHEN k.approval_status = 'approved' AND latest_ss.status = 'ACTIVE' THEN 'active'
                WHEN k.approval_status = 'approved' THEN 'inactive'
                WHEN k.approval_status = 'pending' THEN 'pending'
                WHEN k.approval_status = 'rejected' THEN 'rejected'
                ELSE 'unknown'
            END AS combined_status

        FROM kitchens k
        JOIN users u ON u.user_id = k.owner_id

        /* Latest subscription only */
        LEFT JOIN (
            SELECT t.*
            FROM (
                SELECT ss.*,
                    ROW_NUMBER() OVER (
                        PARTITION BY ss.seller_id
                        ORDER BY 
                            CASE ss.status
                                WHEN 'ACTIVE' THEN 1
                                WHEN 'EXPIRED' THEN 2
                                ELSE 3
                            END,
                            ss.start_date DESC,
                            ss.subscription_id DESC
                    ) AS rn
                FROM seller_subscriptions ss
                WHERE ss.status IN ('ACTIVE', 'EXPIRED', 'CANCELLED')
            ) t
            WHERE t.rn = 1
        ) latest_ss ON latest_ss.seller_id = u.user_id

        LEFT JOIN subscription_plans sp ON sp.plan_id = latest_ss.plan_id

        /* Active suspension only */
        LEFT JOIN suspensions ks 
            ON ks.reference_id = k.kitchen_id
        AND ks.reference_type = 'KITCHEN'
        AND ks.status = 'active'
        AND (ks.suspended_until IS NULL OR ks.suspended_until > SYSDATE)

        ORDER BY k.created_at DESC
        OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':offset', $offset);
        oci_bind_by_name($stmt, ':limit', $limit);

        if (!oci_execute($stmt)) {
            error_log(oci_error($stmt)['message']);
            return [];
        }

        $kitchens = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $row['is_kitchen_suspended'] = (bool)$row['IS_KITCHEN_SUSPENDED'];
            $kitchens[] = $this->processRow($row);
        }

        oci_free_statement($stmt);
        return $kitchens;
    }

    public function getKitchenById($kitchenId)
    {
        $this->kitchenStatusCheck();

        try {
            $sql = "SELECT 
                        k.kitchen_id,
                        k.name AS kitchen_name,
                        k.description,
                        k.cover_image,
                        k.address,
                        k.google_maps_url,
                        k.years_experience,
                        k.signature_dish,
                        k.avg_prep_time,
                        k.created_at,
                        
                        -- Owner info
                        u.name AS owner_name,
                        u.profile_image AS owner_profile_image,
                        
                        -- Service areas
                        (
                            SELECT LISTAGG(sa.name, ', ') WITHIN GROUP (ORDER BY sa.name)
                            FROM kitchen_service_zones ksz
                            JOIN service_areas sa ON ksz.area_id = sa.area_id
                            WHERE ksz.kitchen_id = k.kitchen_id
                        ) AS service_areas,
                        
                        -- Stats
                        (SELECT COUNT(*) FROM orders WHERE kitchen_id = k.kitchen_id AND status = 'DELIVERED') AS orders_delivered,
                        (SELECT COUNT(*) FROM reviews WHERE reference_id = k.kitchen_id AND reference_type = 'KITCHEN' AND status = 'PUBLIC') AS review_count,
                        (
                            SELECT AVG(rating) 
                            FROM reviews 
                            WHERE reference_id = k.kitchen_id 
                            AND reference_type = 'KITCHEN' 
                            AND status = 'PUBLIC'
                        ) AS avg_rating

                    FROM kitchens k
                    JOIN users u ON u.user_id = k.owner_id
                    
                    /* Exclude suspended kitchens */
                    LEFT JOIN suspensions ks 
                        ON ks.reference_id = k.kitchen_id
                    AND ks.reference_type = 'KITCHEN'
                    AND ks.status = 'active'
                    AND (ks.suspended_until IS NULL OR ks.suspended_until > SYSDATE)
                    
                    WHERE k.kitchen_id = :kitchen_id
                    AND k.approval_status = 'approved'
                    AND u.status = 'active'
                    AND ks.reference_id IS NULL";

            $stmt = oci_parse($this->conn, $sql);
            oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);

            if (!oci_execute($stmt)) {
                error_log(oci_error($stmt)['message']);
                return null;
            }

            $row = oci_fetch_assoc($stmt);

            if (!$row) {
                oci_free_statement($stmt);
                return null;
            }

            $kitchen = $this->processRow($row);
            oci_free_statement($stmt);

            return $kitchen;
        } catch (Exception $e) {
            error_log("getKitchenById error: " . $e->getMessage());
            return null;
        }
    }

    public function getTotalOrders($kitchenId)
    {
        $sql = "SELECT COUNT(*) AS total_orders FROM orders WHERE kitchen_id = :kitchen_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return (int)($row['TOTAL_ORDERS'] ?? 0);
    }

    public function getCompletedOrders($kitchenId)
    {
        $sql =
            "SELECT COUNT(*) AS completed_orders
            FROM orders
            WHERE kitchen_id = :kitchen_id
              AND status = 'DELIVERED'";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return (int)($row['COMPLETED_ORDERS'] ?? 0);
    }

    public function getAvgPrepTime($kitchenId)
    {
        $sql =
            "SELECT NVL(AVG(estimated_delivery_time),0) AS avg_prep_time
            FROM orders
            WHERE kitchen_id = :kitchen_id
              AND status = 'DELIVERED'";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return (float)($row['AVG_PREP_TIME'] ?? 0);
    }

    public function getTopKitchens($limit = 5)
    {
        $limit = (int)$limit;
        $sql =
            "SELECT k.kitchen_id,
                   k.name AS kitchen_name,
                   NVL(SUM(o.total_amount + o.delivery_fee),0) AS total_revenue
            FROM kitchens k
            LEFT JOIN orders o ON o.kitchen_id = k.kitchen_id AND o.status = 'DELIVERED'
            GROUP BY k.kitchen_id, k.name
            ORDER BY total_revenue DESC
            FETCH FIRST {$limit} ROWS ONLY";

        $stmt = oci_parse($this->conn, $sql);
        oci_execute($stmt);
        $kitchens = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $processedRow = $this->processRow($row);
            if ($processedRow) {
                $kitchens[] = $processedRow;
            }
        }
        oci_free_statement($stmt);
        return $kitchens;
    }

    public function getRecentKitchens($limit = 5)
    {
        $limit = (int)$limit;
        $sql =
            "SELECT kitchen_id, name, approval_status, created_at
            FROM kitchens
            ORDER BY created_at DESC
            FETCH FIRST {$limit} ROWS ONLY";

        $stmt = oci_parse($this->conn, $sql);
        oci_execute($stmt);

        $kitchens = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $processedRow = $this->processRow($row);
            if ($processedRow) {
                $kitchens[] = $processedRow;
            }
        }

        oci_free_statement($stmt);
        return $kitchens;
    }

    public function getByOwnerId($ownerId)
    {
        $sql = "SELECT * FROM kitchens WHERE owner_id = :owner_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':owner_id', $ownerId);

        if (!oci_execute($stmt)) {
            return false;
        }

        $row = oci_fetch_assoc($stmt);

        // Handle case when no kitchen is found
        if (!$row) {
            oci_free_statement($stmt);
            return false;
        }

        $kitchen = $this->processRow($row);
        oci_free_statement($stmt);

        return $kitchen;
    }

    public function getKitchenWithStats($ownerId)
    {
        $sql =
            "SELECT 
            k.*,
            u.name as owner_name,
            u.email as owner_email,
            u.phone as owner_phone,
            
            -- Service areas
            (SELECT COUNT(*) FROM kitchen_service_zones ksz WHERE ksz.kitchen_id = k.kitchen_id) as total_areas,
            
            -- Rating information
            (SELECT AVG(r.rating) FROM reviews r 
             WHERE r.reference_id = k.kitchen_id AND r.reference_type = 'KITCHEN') as rating,
            (SELECT COUNT(*) FROM reviews r 
             WHERE r.reference_id = k.kitchen_id AND r.reference_type = 'KITCHEN') as total_reviews,
            
            -- Menu items count
            (SELECT COUNT(*) FROM menu_items mi WHERE mi.kitchen_id = k.kitchen_id) as total_menu_items,
            (SELECT COUNT(*) FROM menu_items mi WHERE mi.kitchen_id = k.kitchen_id AND mi.is_available = 1) as available_menu_items
            
        FROM kitchens k
        JOIN users u ON k.owner_id = u.user_id
        WHERE k.owner_id = :owner_id";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':owner_id', $ownerId);

        if (!oci_execute($stmt)) {
            return false;
        }

        $row = oci_fetch_assoc($stmt);

        if (!$row) {
            oci_free_statement($stmt);
            return false;
        }

        $kitchen = $this->processRow($row);
        oci_free_statement($stmt);

        return $kitchen;
    }

    public function create($data)
    {
        $sql =
            "INSERT INTO kitchens (
                owner_id, name, description, cover_image, address, 
                google_maps_url, years_experience, signature_dish, 
                avg_prep_time
            ) VALUES (
                :owner_id, :name, :description, :cover_image, :address,
                :google_maps_url, :years_experience, :signature_dish,
                :avg_prep_time
            ) RETURNING kitchen_id INTO :kitchen_id";

        $stmt = oci_parse($this->conn, $sql);
        $kitchenId = null;

        // Bind parameters
        oci_bind_by_name($stmt, ':owner_id', $data['owner_id']);
        oci_bind_by_name($stmt, ':name', $data['name']);
        oci_bind_by_name($stmt, ':description', $data['description']);
        oci_bind_by_name($stmt, ':cover_image', $data['cover_image']);
        oci_bind_by_name($stmt, ':address', $data['address']);
        oci_bind_by_name($stmt, ':google_maps_url', $data['google_maps_url']);
        oci_bind_by_name($stmt, ':years_experience', $data['years_experience']);
        oci_bind_by_name($stmt, ':signature_dish', $data['signature_dish']);
        oci_bind_by_name($stmt, ':avg_prep_time', $data['avg_prep_time']);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId, -1, SQLT_INT);

        if (oci_execute($stmt)) {
            // Add service areas
            if (isset($data['service_areas']) && is_array($data['service_areas'])) {
                foreach ($data['service_areas'] as $areaId) {
                    $this->addServiceArea($kitchenId, $areaId);
                }
            }
            return $kitchenId;
        }

        return false;
    }

    public function updateKitchen($kitchenId, $data)
    {
        $sql = "UPDATE kitchens SET 
            name = :name,
            description = :description,
            address = :address,
            google_maps_url = :google_maps_url,
            years_experience = :years_experience,
            signature_dish = :signature_dish,
            avg_prep_time = :avg_prep_time,
            updated_at = SYSTIMESTAMP";

        if (isset($data['cover_image'])) {
            $sql .= ", cover_image = :cover_image";
        }

        $sql .= " WHERE kitchen_id = :kitchen_id";

        $stmt = oci_parse($this->conn, $sql);

        // Bind parameters
        oci_bind_by_name($stmt, ':name', $data['name']);
        oci_bind_by_name($stmt, ':description', $data['description']);
        oci_bind_by_name($stmt, ':address', $data['address']);
        oci_bind_by_name($stmt, ':google_maps_url', $data['google_maps_url']);
        oci_bind_by_name($stmt, ':years_experience', $data['years_experience']);
        oci_bind_by_name($stmt, ':signature_dish', $data['signature_dish']);
        oci_bind_by_name($stmt, ':avg_prep_time', $data['avg_prep_time']);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);

        if (isset($data['cover_image'])) {
            oci_bind_by_name($stmt, ':cover_image', $data['cover_image']);
        }

        $result = oci_execute($stmt);

        if (!$result) {
            $error = oci_error($stmt);
            // Handle error appropriately
            return false;
        }

        oci_free_statement($stmt);
        return $result;
    }

    private function addServiceArea($kitchenId, $areaId)
    {
        $sql = "INSERT INTO kitchen_service_zones (kitchen_id, area_id) VALUES (:kitchen_id, :area_id)";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_bind_by_name($stmt, ':area_id', $areaId);
        return oci_execute($stmt);
    }

    public function approveKitchen($ownerId)
    {
        $sql = "UPDATE kitchens SET approval_status = 'approved' WHERE owner_id = :owner_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':owner_id', $ownerId);

        return oci_execute($stmt);
    }

    public function getFeaturedKitchens($limit = 10)
    {
        $this->kitchenStatusCheck();

        try {
            $limit = max(1, min(20, (int)$limit));

            $params = [];
            $conditions = [];

            // Base conditions (same philosophy as getFilteredKitchens)
            $conditions[] = "k.approval_status = 'approved'";
            $conditions[] = "u.status = 'active' AND u.role = 'seller'";
            $conditions[] = "ks.reference_id IS NULL";
            $conditions[] = "active_ss.seller_id IS NOT NULL";

            // Must have at least 1 available menu item
            $conditions[] = "EXISTS (
            SELECT 1
            FROM menu_items mi
            WHERE mi.kitchen_id = k.kitchen_id
            AND mi.is_available = 1
        )";

            // Must have at least 1 service area
            $conditions[] = "EXISTS (
            SELECT 1
            FROM kitchen_service_zones ksz
            WHERE ksz.kitchen_id = k.kitchen_id
        )";

            $whereClause = 'WHERE ' . implode(' AND ', $conditions);

            $sql = "
            SELECT 
                k.kitchen_id,
                k.name AS kitchen_name,
                k.description,
                k.cover_image,
                k.address,
                k.years_experience,
                k.signature_dish,
                k.avg_prep_time,
                k.created_at,

                -- Owner info
                u.name AS owner_name,
                u.profile_image AS owner_profile_image,

                -- Service areas
                sa.service_areas,

                -- Ratings (PUBLIC only)
                COALESCE(r.avg_rating, 0) AS avg_rating,
                COALESCE(r.review_count, 0) AS review_count

            FROM kitchens k
            JOIN users u 
                ON u.user_id = k.owner_id

            /* Latest ACTIVE subscription */
            LEFT JOIN (
                SELECT seller_id
                FROM (
                    SELECT ss.*,
                        ROW_NUMBER() OVER (
                            PARTITION BY seller_id
                            ORDER BY 
                                CASE status
                                    WHEN 'ACTIVE' THEN 1
                                    WHEN 'EXPIRED' THEN 2
                                    ELSE 3
                                END,
                                start_date DESC
                        ) rn
                    FROM seller_subscriptions ss
                )
                WHERE rn = 1
                AND status = 'ACTIVE'
                AND end_date >= SYSDATE
            ) active_ss ON active_ss.seller_id = u.user_id

            /* Exclude suspended kitchens */
            LEFT JOIN suspensions ks 
                ON ks.reference_id = k.kitchen_id
            AND ks.reference_type = 'KITCHEN'
            AND ks.status = 'active'
            AND (ks.suspended_until IS NULL OR ks.suspended_until > SYSDATE)

            /* Service areas */
            LEFT JOIN (
                SELECT 
                    ksz.kitchen_id,
                    LISTAGG(sa.name, ', ') 
                        WITHIN GROUP (ORDER BY sa.name) AS service_areas
                FROM kitchen_service_zones ksz
                JOIN service_areas sa 
                    ON ksz.area_id = sa.area_id
                GROUP BY ksz.kitchen_id
            ) sa ON sa.kitchen_id = k.kitchen_id

            /* Public ratings */
            LEFT JOIN (
                SELECT 
                    reference_id,
                    AVG(rating) AS avg_rating,
                    COUNT(review_id) AS review_count
                FROM reviews
                WHERE reference_type = 'KITCHEN'
                AND status = 'PUBLIC'
                GROUP BY reference_id
            ) r ON r.reference_id = k.kitchen_id

            $whereClause

            ORDER BY 
                CASE WHEN r.review_count > 0 THEN 0 ELSE 1 END,
                r.avg_rating DESC,
                k.created_at DESC

            FETCH FIRST :limit ROWS ONLY
        ";

            $stmt = oci_parse($this->conn, $sql);
            oci_bind_by_name($stmt, ':limit', $limit);

            if (!oci_execute($stmt)) {
                error_log(oci_error($stmt)['message']);
                return [
                    'kitchens' => [],
                    'hasPublicRatings' => false,
                    'hasAnyRatings' => false
                ];
            }

            $kitchens = [];
            $hasPublicRatings = false;
            $hasAnyRatings = false;

            while ($row = oci_fetch_assoc($stmt)) {

                if ((int)$row['REVIEW_COUNT'] > 0) {
                    $hasAnyRatings = true;
                }

                if ((float)$row['AVG_RATING'] > 0) {
                    $hasPublicRatings = true;
                }

                $kitchens[] = $this->processRow($row);
            }

            oci_free_statement($stmt);

            return [
                'kitchens' => $kitchens,
                'hasPublicRatings' => $hasPublicRatings,
                'hasAnyRatings' => $hasAnyRatings
            ];
        } catch (Exception $e) {
            error_log("getFeaturedKitchens error: " . $e->getMessage());
            return [
                'kitchens' => [],
                'hasPublicRatings' => false,
                'hasAnyRatings' => false
            ];
        }
    }

    public function getFilteredKitchens($filters, $limit = 12, $offset = 0)
    {
        $this->kitchenStatusCheck();

        try {
            $params = [];
            $conditions = [];

            $conditions[] = "k.approval_status = 'approved'";
            $conditions[] = "u.status = 'active' AND u.role = 'seller'";
            $conditions[] = "ks.reference_id IS NULL";
            $conditions[] = "active_ss.seller_id IS NOT NULL";

            if (!empty($filters['search'])) {
                $conditions[] = "(LOWER(k.name) LIKE :search OR LOWER(u.name) LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }

            if (!empty($filters['location'])) {
                $conditions[] = "EXISTS (
                SELECT 1 
                FROM kitchen_service_zones ksz2 
                JOIN service_areas sa2 ON ksz2.area_id = sa2.area_id
                WHERE ksz2.kitchen_id = k.kitchen_id
                AND LOWER(sa2.name) = LOWER(:location)
            )";
                $params[':location'] = $filters['location'];
            }

            if (!empty($filters['experience']) && is_numeric($filters['experience'])) {
                $conditions[] = "k.years_experience >= :experience";
                $params[':experience'] = (int)$filters['experience'];
            }

            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

            $sql =
                "SELECT 
                k.kitchen_id,
                k.name AS kitchen_name,
                k.description,
                k.cover_image,
                k.address,
                k.years_experience,
                k.signature_dish,
                k.avg_prep_time,
                k.created_at,

                u.name AS owner_name,
                u.profile_image AS owner_profile_image,

                -- Service areas concatenated
                (
                    SELECT LISTAGG(sa.name, ', ') WITHIN GROUP (ORDER BY sa.name)
                    FROM kitchen_service_zones ksz2
                    JOIN service_areas sa ON ksz2.area_id = sa.area_id
                    WHERE ksz2.kitchen_id = k.kitchen_id
                ) AS service_areas,

                COALESCE(r.avg_rating, 0) AS avg_rating,
                COALESCE(r.review_count, 0) AS review_count

            FROM kitchens k
            JOIN users u ON u.user_id = k.owner_id
            
            /* Latest ACTIVE subscription */
            LEFT JOIN (
                SELECT seller_id
                FROM (
                    SELECT ss.*,
                        ROW_NUMBER() OVER (
                            PARTITION BY seller_id
                            ORDER BY 
                                CASE status
                                    WHEN 'ACTIVE' THEN 1
                                    WHEN 'EXPIRED' THEN 2
                                    ELSE 3
                                END,
                                start_date DESC
                        ) rn
                    FROM seller_subscriptions ss
                )
                WHERE rn = 1
                AND status = 'ACTIVE'
                AND end_date >= SYSDATE
            ) active_ss ON active_ss.seller_id = u.user_id

            /* Exclude suspended kitchens */
            LEFT JOIN suspensions ks 
                ON ks.reference_id = k.kitchen_id
            AND ks.reference_type = 'KITCHEN'
            AND ks.status = 'active'
            AND (ks.suspended_until IS NULL OR ks.suspended_until > SYSDATE)

            /* Public ratings */
            LEFT JOIN (
                SELECT 
                    reference_id,
                    AVG(rating) AS avg_rating,
                    COUNT(review_id) AS review_count
                FROM reviews
                WHERE reference_type = 'KITCHEN'
                AND status = 'PUBLIC'
                GROUP BY reference_id
            ) r ON r.reference_id = k.kitchen_id

            $whereClause

            -- Must have at least 1 available menu item
            AND EXISTS (
                SELECT 1
                FROM menu_items mi
                WHERE mi.kitchen_id = k.kitchen_id
                AND mi.is_available = 1
            )

            -- Must have at least 1 service area
            AND EXISTS (
                SELECT 1
                FROM kitchen_service_zones ksz
                WHERE ksz.kitchen_id = k.kitchen_id
            )";

            if (!empty($filters['rating'])) {
                if ($filters['rating'] === 'high_to_low') {
                    $sql .= " ORDER BY r.avg_rating DESC NULLS LAST";
                } elseif ($filters['rating'] === 'most_reviews') {
                    $sql .= " ORDER BY r.review_count DESC NULLS LAST";
                }
            } else {
                $sql .= " ORDER BY k.created_at DESC";
            }

            $sql .= " OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";
            $params[':offset'] = $offset;
            $params[':limit'] = $limit;

            $stmt = oci_parse($this->conn, $sql);

            foreach ($params as $key => $value) {
                oci_bind_by_name($stmt, $key, $params[$key]);
            }

            if (!oci_execute($stmt)) {
                error_log(oci_error($stmt)['message']);
                return [];
            }

            $kitchens = [];
            while ($row = oci_fetch_assoc($stmt)) {
                $kitchens[] = $this->processRow($row);
            }

            oci_free_statement($stmt);
            return $kitchens;
        } catch (Exception $e) {
            error_log("getFilteredKitchens error: " . $e->getMessage());
            return [];
        }
    }

    public function countFilteredKitchens($filters)
    {
        $this->kitchenStatusCheck();

        try {
            $params = [];
            $conditions = [];

            $conditions[] = "k.approval_status = 'approved'";
            $conditions[] = "u.status = 'active' AND u.role = 'seller'";
            $conditions[] = "ks.reference_id IS NULL";
            $conditions[] = "active_ss.seller_id IS NOT NULL";

            if (!empty($filters['search'])) {
                $conditions[] = "(LOWER(k.name) LIKE :search OR LOWER(u.name) LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }

            if (!empty($filters['location'])) {
                $conditions[] = "EXISTS (
                SELECT 1 
                FROM kitchen_service_zones ksz2 
                JOIN service_areas sa2 ON ksz2.area_id = sa2.area_id
                WHERE ksz2.kitchen_id = k.kitchen_id
                AND LOWER(sa2.name) = LOWER(:location)
            )";
                $params[':location'] = $filters['location'];
            }

            if (!empty($filters['experience']) && is_numeric($filters['experience'])) {
                $conditions[] = "k.years_experience >= :experience";
                $params[':experience'] = (int)$filters['experience'];
            }

            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

            $sql = "SELECT COUNT(*) as total
                    FROM kitchens k
                    JOIN users u ON u.user_id = k.owner_id
                    
                    LEFT JOIN (
                        SELECT seller_id
                        FROM (
                            SELECT ss.*,
                                ROW_NUMBER() OVER (
                                    PARTITION BY seller_id
                                    ORDER BY 
                                        CASE status
                                            WHEN 'ACTIVE' THEN 1
                                            WHEN 'EXPIRED' THEN 2
                                            ELSE 3
                                        END,
                                        start_date DESC
                                ) rn
                            FROM seller_subscriptions ss
                        )
                        WHERE rn = 1
                        AND status = 'ACTIVE'
                        AND end_date >= SYSDATE
                    ) active_ss ON active_ss.seller_id = u.user_id

                    LEFT JOIN suspensions ks 
                        ON ks.reference_id = k.kitchen_id
                    AND ks.reference_type = 'KITCHEN'
                    AND ks.status = 'active'
                    AND (ks.suspended_until IS NULL OR ks.suspended_until > SYSDATE)

                    $whereClause

                    AND EXISTS (
                        SELECT 1
                        FROM menu_items mi
                        WHERE mi.kitchen_id = k.kitchen_id
                        AND mi.is_available = 1
                    )

                    AND EXISTS (
                        SELECT 1
                        FROM kitchen_service_zones ksz
                        WHERE ksz.kitchen_id = k.kitchen_id
                    )";

            $stmt = oci_parse($this->conn, $sql);

            foreach ($params as $key => $value) {
                oci_bind_by_name($stmt, $key, $params[$key]);
            }

            if (!oci_execute($stmt)) {
                error_log(oci_error($stmt)['message']);
                return 0;
            }

            $row = oci_fetch_assoc($stmt);
            $total = $row['TOTAL'] ?? 0;

            oci_free_statement($stmt);
            return (int)$total;
        } catch (Exception $e) {
            error_log("countFilteredKitchens error: " . $e->getMessage());
            return 0;
        }
    }
}
