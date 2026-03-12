<?php

class Favorite
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

    private function normalizeRow(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[strtoupper($key)] = $this->convertLobToString($value);
        }
        return $normalized;
    }

    public function isItemInFavorites($userId, $referenceId, $referenceType = 'ITEM')
    {
        $sql = "SELECT COUNT(*) AS count 
                FROM favorites 
                WHERE user_id = :user_id 
                  AND reference_id = :reference_id 
                  AND reference_type = :reference_type";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':user_id', $userId);
        oci_bind_by_name($stmt, ':reference_id', $referenceId);
        oci_bind_by_name($stmt, ':reference_type', $referenceType);

        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return ($row['COUNT'] ?? 0) > 0;
    }

    public function getUserFavorites($userId, $typeFilter = 'all', $searchTerm = '', $limit = 12, $offset = 0)
    {
        if ($typeFilter === 'kitchens') {
            $typeFilter = 'KITCHEN';
        } elseif ($typeFilter === 'menu-items') {
            $typeFilter = 'ITEM';
        } else {
            $typeFilter = 'all';
        }

        // Base query
        $baseSql = "FROM favorites f
                    LEFT JOIN kitchens k 
                        ON f.reference_type = 'KITCHEN' 
                        AND f.reference_id = k.kitchen_id

                    LEFT JOIN menu_items mi 
                        ON f.reference_type = 'ITEM' 
                        AND f.reference_id = mi.item_id

                    LEFT JOIN kitchens k2 
                        ON mi.kitchen_id = k2.kitchen_id

                    LEFT JOIN suspensions ks 
                        ON k.kitchen_id = ks.reference_id 
                        AND ks.reference_type = 'KITCHEN'
                        
                    /* Subscription information for kitchens */
                    LEFT JOIN seller_subscriptions ss 
                        ON k.owner_id = ss.seller_id 
                        AND ss.status = 'ACTIVE'
                        AND ss.end_date >= TRUNC(SYSDATE)
                        
                    /* Subscription information for item kitchens */
                    LEFT JOIN seller_subscriptions ss2 
                        ON k2.owner_id = ss2.seller_id 
                        AND ss2.status = 'ACTIVE'
                        AND ss2.end_date >= TRUNC(SYSDATE)

                    WHERE f.user_id = :user_id

                    /* Drop broken references */
                    AND (
                            (f.reference_type = 'KITCHEN' AND k.kitchen_id IS NOT NULL)
                        OR (f.reference_type = 'ITEM' AND mi.item_id IS NOT NULL)
                    )

                    /* Hide unapproved kitchens */
                    AND (
                            f.reference_type != 'KITCHEN'
                        OR k.approval_status = 'approved'
                    )

                    /* Hide suspended kitchens */
                    AND (
                            f.reference_type != 'KITCHEN'
                        OR (
                                ks.reference_id IS NULL
                            OR ks.status != 'active'
                            OR ks.suspended_until < SYSDATE
                            )
                    )

                    /* Hide kitchens with expired or no active subscription */
                    AND (
                            f.reference_type != 'KITCHEN'
                        OR (
                                ss.subscription_id IS NOT NULL
                            AND ss.status = 'ACTIVE'
                            AND ss.end_date >= TRUNC(SYSDATE)
                            )
                    )

                    /* Hide unavailable items */
                    AND (
                            f.reference_type != 'ITEM'
                        OR mi.is_available = 1
                    )

                    /* Hide items from unapproved kitchens */
                    AND (
                            f.reference_type != 'ITEM'
                        OR k2.approval_status = 'approved'
                    )

                    /* Hide items from suspended kitchens */
                    AND (
                            f.reference_type != 'ITEM'
                        OR NOT EXISTS (
                            SELECT 1 FROM suspensions s2
                            WHERE s2.reference_type = 'KITCHEN'
                            AND s2.reference_id = k2.kitchen_id
                            AND s2.status = 'active'
                            AND (s2.suspended_until IS NULL OR s2.suspended_until > SYSDATE)
                        )
                    )

                    /* Hide items from kitchens with expired or no active subscription */
                    AND (
                            f.reference_type != 'ITEM'
                        OR (
                                ss2.subscription_id IS NOT NULL
                            AND ss2.status = 'ACTIVE'
                            AND ss2.end_date >= TRUNC(SYSDATE)
                            )
                    )";

        if (!empty($searchTerm)) {
            $searchLike = '%' . strtoupper($searchTerm) . '%';
            $baseSql .= " AND (
                UPPER(k.name) LIKE :search 
                OR UPPER(mi.name) LIKE :search
                OR UPPER(SUBSTR(k.description, 1, 4000)) LIKE :search
                OR UPPER(SUBSTR(mi.description, 1, 4000)) LIKE :search
                OR EXISTS (
                    SELECT 1 FROM menu_item_categories mic
                    JOIN categories cat ON mic.category_id = cat.category_id
                    WHERE mic.item_id = mi.item_id
                    AND UPPER(cat.name) LIKE :search
                )
            )";
        }

        if ($typeFilter !== 'all') {
            $baseSql .= " AND f.reference_type = :reference_type";
        }

        $countSql = "SELECT COUNT(*) as total " . $baseSql;
        $stmt = oci_parse($this->conn, $countSql);
        oci_bind_by_name($stmt, ':user_id', $userId);

        if (!empty($searchTerm)) {
            oci_bind_by_name($stmt, ':search', $searchLike);
        }

        if ($typeFilter !== 'all') {
            oci_bind_by_name($stmt, ':reference_type', $typeFilter);
        }

        if (!oci_execute($stmt)) {
            $error = oci_error($stmt);
            error_log("Oracle Count Error: " . print_r($error, true));
            oci_free_statement($stmt);
            return [
                'items' => [],
                'total' => 0,
                'total_pages' => 0,
                'current_page' => 1
            ];
        }

        $countRow = oci_fetch_assoc($stmt);
        $total = $countRow['TOTAL'] ?? 0;
        oci_free_statement($stmt);

        $dataSql = "SELECT 
                f.favorite_id,
                f.reference_id,
                f.reference_type,
                f.added_at,
                
                CASE 
                    WHEN f.reference_type = 'KITCHEN' THEN k.name
                    WHEN f.reference_type = 'ITEM' THEN mi.name
                END AS name,
                
                CASE 
                    WHEN f.reference_type = 'KITCHEN' THEN TO_CHAR(SUBSTR(k.description, 1, 4000))
                    WHEN f.reference_type = 'ITEM' THEN TO_CHAR(SUBSTR(mi.description, 1, 4000))
                END AS description,
                
                CASE 
                    WHEN f.reference_type = 'KITCHEN' THEN k.cover_image
                    WHEN f.reference_type = 'ITEM' THEN mi.item_image
                END AS image,
                
                k.kitchen_id,
                k.address,
                k.years_experience,
                k.approval_status,
                k.avg_prep_time,
                k.cover_image as kitchen_image,
                
                (SELECT u.name FROM users u WHERE k.owner_id = u.user_id) as owner_name,
                
                (SELECT TO_CHAR(LISTAGG(TO_CHAR(sa.name), ', ') WITHIN GROUP (ORDER BY sa.name))
                 FROM kitchen_service_zones ksz
                 JOIN service_areas sa ON ksz.area_id = sa.area_id
                 WHERE ksz.kitchen_id = k.kitchen_id) AS service_areas,
                
                mi.item_id,
                mi.name as item_name,
                mi.price,
                mi.is_available,
                mi.daily_stock,
                mi.kitchen_id AS item_kitchen_id,
                mi.portion_size,
                mi.spice_level,
                mi.item_image as item_image,
                
                k2.name AS kitchen_name,
                k2.kitchen_id AS parent_kitchen_id,
                k2.owner_id AS kitchen_owner_id,
                
                (SELECT TO_CHAR(LISTAGG(TO_CHAR(cat.name), ', ') WITHIN GROUP (ORDER BY cat.name))
                 FROM menu_item_categories mic
                 JOIN categories cat ON mic.category_id = cat.category_id
                 WHERE mic.item_id = mi.item_id) AS categories,
                
                (SELECT AVG(r.rating)
                 FROM reviews r
                 WHERE (
                     (f.reference_type = 'KITCHEN' AND r.reference_id = k.kitchen_id AND r.reference_type = 'KITCHEN')
                     OR (f.reference_type = 'ITEM' AND r.reference_id = mi.item_id AND r.reference_type = 'ITEM')
                 )
                 AND r.status = 'PUBLIC') AS rating,
                
                (SELECT COUNT(*)
                 FROM reviews r
                 WHERE (
                     (f.reference_type = 'KITCHEN' AND r.reference_id = k.kitchen_id AND r.reference_type = 'KITCHEN')
                     OR (f.reference_type = 'ITEM' AND r.reference_id = mi.item_id AND r.reference_type = 'ITEM')
                 )
                 AND r.status = 'PUBLIC') AS review_count,
                
                CASE 
                    WHEN ks.reference_id IS NOT NULL
                    AND ks.status = 'active'
                    AND (ks.suspended_until IS NULL OR ks.suspended_until > SYSDATE)
                    THEN 1 ELSE 0
                END AS is_kitchen_suspended,
                
                CASE 
                    WHEN f.reference_type = 'KITCHEN' THEN 
                        CASE 
                            WHEN ss.subscription_id IS NOT NULL 
                                AND ss.status = 'ACTIVE'
                                AND ss.end_date >= TRUNC(SYSDATE) 
                            THEN 'ACTIVE'
                            ELSE 'EXPIRED'
                        END
                    ELSE NULL
                END AS kitchen_subscription_status,
                
                CASE 
                    WHEN f.reference_type = 'ITEM' THEN 
                        CASE 
                            WHEN ss2.subscription_id IS NOT NULL 
                                AND ss2.status = 'ACTIVE'
                                AND ss2.end_date >= TRUNC(SYSDATE) 
                            THEN 'ACTIVE'
                            ELSE 'EXPIRED'
                        END
                    ELSE NULL
                END AS item_kitchen_subscription_status
                
            " . $baseSql . "
            ORDER BY f.added_at DESC
            OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";

        $stmt = oci_parse($this->conn, $dataSql);
        oci_bind_by_name($stmt, ':user_id', $userId);
        oci_bind_by_name($stmt, ':offset', $offset);
        oci_bind_by_name($stmt, ':limit', $limit);

        if (!empty($searchTerm)) {
            oci_bind_by_name($stmt, ':search', $searchLike);
        }

        if ($typeFilter !== 'all') {
            oci_bind_by_name($stmt, ':reference_type', $typeFilter);
        }

        if (!oci_execute($stmt)) {
            $error = oci_error($stmt);
            error_log("Oracle Data Error: " . print_r($error, true));
            oci_free_statement($stmt);
            return [
                'items' => [],
                'total' => $total,
                'total_pages' => ceil($total / $limit),
                'current_page' => ceil($offset / $limit) + 1
            ];
        }

        $items = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $row = $this->normalizeRow($row);
            $items[] = $this->processFavoriteRow($row);
        }

        oci_free_statement($stmt);

        return [
            'items' => $items,
            'total' => (int)$total,
            'total_pages' => $limit > 0 ? ceil($total / $limit) : 1,
            'current_page' => $limit > 0 ? ceil($offset / $limit) + 1 : 1
        ];
    }

    private function processFavoriteRow(array $row): array
    {
        $type = strtoupper($row['REFERENCE_TYPE'] ?? '');

        $baseData = [
            'FAVORITE_ID'       => $row['FAVORITE_ID'] ?? null,
            'REFERENCE_TYPE'   => $type,
            'REFERENCE_ID'     => $row['REFERENCE_ID'] ?? null,
            'NAME'             => $row['NAME'] ?? '',
            'DESCRIPTION'      => $row['DESCRIPTION'] ?? '',
            'IMAGE'            => $row['IMAGE'] ?? '',
            'ADDED_AT'         => $row['ADDED_AT'] ?? date('Y-m-d H:i:s'),
            'RATING'           => $row['RATING'] ? round((float)$row['RATING'], 1) : null,
            'REVIEW_COUNT'     => $row['REVIEW_COUNT'] ? (int)$row['REVIEW_COUNT'] : 0,
        ];

        if ($type === 'KITCHEN') {
            return array_merge($baseData, [
                'KITCHEN_ID'        => $row['KITCHEN_ID'] ?? null,
                'OWNER_NAME'        => $row['OWNER_NAME'] ?? null,
                'ADDRESS'          => $row['ADDRESS'] ?? '',
                'YEARS_EXPERIENCE' => $row['YEARS_EXPERIENCE'] ?? null,
                'SERVICE_AREAS'    => $row['SERVICE_AREAS'] ?? '',
                'APPROVAL_STATUS'  => $row['APPROVAL_STATUS'] ?? '',
                'IS_SUSPENDED'     => $row['IS_KITCHEN_SUSPENDED'] ?? 0,
                'AVG_PREP_TIME'    => $row['AVG_PREP_TIME'] ?? null,
                'SUBSCRIPTION_STATUS' => $row['KITCHEN_SUBSCRIPTION_STATUS'] ?? 'EXPIRED',
                'IS_ACTIVE'        => ($row['KITCHEN_SUBSCRIPTION_STATUS'] ?? 'EXPIRED') === 'ACTIVE',
            ]);
        }

        return array_merge($baseData, [
            'ITEM_ID'           => $row['ITEM_ID'] ?? null,
            'PRICE'            => $row['PRICE'] ?? 0,
            'IS_AVAILABLE'     => $row['IS_AVAILABLE'] ?? 0,
            'DAILY_STOCK'      => $row['DAILY_STOCK'] ?? 0,
            'KITCHEN_ID'       => $row['ITEM_KITCHEN_ID'] ?? null,
            'KITCHEN_NAME'     => $row['KITCHEN_NAME'] ?? '',
            'CATEGORIES'       => $row['CATEGORIES'] ?? '',
            'CATEGORY_NAME'    => $row['CATEGORIES'] ?? '',
            'PORTION_SIZE'     => $row['PORTION_SIZE'] ?? '',
            'SPICE_LEVEL'      => $row['SPICE_LEVEL'] ?? 1,
            'SUBSCRIPTION_STATUS' => $row['ITEM_KITCHEN_SUBSCRIPTION_STATUS'] ?? 'EXPIRED',
            'IS_ACTIVE'        => ($row['ITEM_KITCHEN_SUBSCRIPTION_STATUS'] ?? 'EXPIRED') === 'ACTIVE',
        ]);
    }

    public function addToFavorites($userId, $referenceId, $referenceType = 'ITEM')
    {
        if ($this->isItemInFavorites($userId, $referenceId, $referenceType)) {
            return false;
        }

        $sql = "INSERT INTO favorites (user_id, reference_id, reference_type, added_at) 
                VALUES (:user_id, :reference_id, :reference_type, SYSDATE)";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':user_id', $userId);
        oci_bind_by_name($stmt, ':reference_id', $referenceId);
        oci_bind_by_name($stmt, ':reference_type', $referenceType);

        $result = oci_execute($stmt);
        oci_free_statement($stmt);

        return $result;
    }

    public function removeFromFavorites($userId, $referenceId, $referenceType = 'ITEM')
    {
        $sql = "DELETE FROM favorites 
                WHERE user_id = :user_id 
                  AND reference_id = :reference_id 
                  AND reference_type = :reference_type";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':user_id', $userId);
        oci_bind_by_name($stmt, ':reference_id', $referenceId);
        oci_bind_by_name($stmt, ':reference_type', $referenceType);

        $result = oci_execute($stmt);
        oci_free_statement($stmt);

        return $result;
    }

    public function toggleFavorite($userId, $referenceId, $referenceType = 'ITEM')
    {
        if ($this->isItemInFavorites($userId, $referenceId, $referenceType)) {
            $sql = "DELETE FROM favorites 
                    WHERE user_id = :user_id 
                    AND reference_id = :reference_id 
                    AND reference_type = :reference_type";
        } else {
            $sql = "INSERT INTO favorites (user_id, reference_type, reference_id, added_at) 
                    VALUES (:user_id, :reference_type, :reference_id, SYSTIMESTAMP)";
        }

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':user_id', $userId);
        oci_bind_by_name($stmt, ':reference_id', $referenceId);
        oci_bind_by_name($stmt, ':reference_type', $referenceType);

        $result = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
        oci_free_statement($stmt);

        return $result;
    }

    public function getFavoriteCount($userId)
    {
        $sql = "SELECT COUNT(*) AS count 
                FROM favorites 
                WHERE user_id = :user_id";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':user_id', $userId);

        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row['COUNT'] ?? 0;
    }

    public function clearAllUserFavorites($userId)
    {
        $sql = "DELETE FROM favorites WHERE user_id = :user_id";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':user_id', $userId);

        $result = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);

        if (!$result) {
            $error = oci_error($stmt);
            error_log("Oracle Error in delete: " . $error['message']);
            oci_free_statement($stmt);
            return false;
        }

        oci_free_statement($stmt);

        return $result;
    }

    public function getFavoriteStatus($userId, $itemIds, $type = 'ITEM')
    {
        if (empty($itemIds)) {
            return [];
        }

        $itemIdsStr = implode(',', array_map('intval', $itemIds));

        $sql = "SELECT reference_id 
                FROM favorites 
                WHERE user_id = :user_id 
                  AND reference_type = :reference_type 
                  AND reference_id IN ($itemIdsStr)";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':user_id', $userId);
        oci_bind_by_name($stmt, ':reference_type', $type);

        oci_execute($stmt);

        $favoriteItems = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $favoriteItems[] = $row['REFERENCE_ID'];
        }

        oci_free_statement($stmt);

        $result = [];
        foreach ($itemIds as $itemId) {
            $result[$itemId] = in_array($itemId, $favoriteItems);
        }

        return $result;
    }

    public function getFavoriteKitchens($userId)
    {
        $sql = "SELECT f.favorite_id, f.added_at,
                       k.kitchen_id, k.name AS kitchen_name, k.description, 
                       k.cover_image, k.address, k.signature_dish,
                       k.avg_prep_time
                FROM favorites f
                JOIN kitchens k ON f.reference_id = k.kitchen_id
                WHERE f.user_id = :user_id 
                  AND f.reference_type = 'KITCHEN'
                ORDER BY f.added_at DESC";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':user_id', $userId);
        oci_execute($stmt);

        $kitchens = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $row = $this->normalizeRow($row);
            $kitchens[] = $row;
        }

        oci_free_statement($stmt);
        return $kitchens;
    }
}
