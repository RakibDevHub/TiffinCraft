<?php

class Menu
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

    public function getMenuItemsByKitchenId($kitchenId, $includeUnavailable = false)
    {
        $sql = "SELECT 
                mi.*,
                (SELECT LISTAGG(c.category_id, ',') WITHIN GROUP (ORDER BY c.name)
                 FROM menu_item_categories mic
                 JOIN categories c ON mic.category_id = c.category_id
                 WHERE mic.item_id = mi.item_id) as category_ids,
                (SELECT LISTAGG(c.name, ', ') WITHIN GROUP (ORDER BY c.name)
                 FROM menu_item_categories mic
                 JOIN categories c ON mic.category_id = c.category_id
                 WHERE mic.item_id = mi.item_id) as categories
            FROM menu_items mi 
            WHERE mi.kitchen_id = :kitchen_id";

        if (!$includeUnavailable) {
            $sql .= " AND mi.is_available = 1";
        }

        $sql .= " ORDER BY mi.created_at DESC";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);

        if (!oci_execute($stmt)) {
            return [];
        }

        $items = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $processedRow = $this->processRow($row);
            if ($processedRow) {
                $items[] = $processedRow;
            }
        }

        oci_free_statement($stmt);
        return $items;
    }

    public function getMenuItemsByKitchen($kitchenId, $categoryFilter = null)
    {
        try {
            $sql = "SELECT 
                        mi.item_id,
                        mi.name,
                        mi.description,
                        mi.price,
                        mi.item_image,
                        mi.spice_level,
                        mi.portion_size,
                        mi.daily_stock,
                        mi.is_available,
                        mi.created_at,
                        
                        -- Categories
                        (
                            SELECT LISTAGG(c.name, ', ') WITHIN GROUP (ORDER BY c.name)
                            FROM menu_item_categories mic
                            JOIN categories c ON mic.category_id = c.category_id
                            WHERE mic.item_id = mi.item_id
                        ) AS category_name,
                        
                        -- Ratings
                        COALESCE(r.avg_rating, 0) AS avg_rating,
                        COALESCE(r.review_count, 0) AS review_count

                    FROM menu_items mi
                    
                    LEFT JOIN (
                        SELECT 
                            reference_id,
                            AVG(rating) AS avg_rating,
                            COUNT(*) AS review_count
                        FROM reviews 
                        WHERE reference_type = 'ITEM'
                        AND status = 'PUBLIC'
                        GROUP BY reference_id
                    ) r ON r.reference_id = mi.item_id
                    
                    WHERE mi.kitchen_id = :kitchen_id
                    AND mi.is_available = 1
                    
                    ORDER BY mi.created_at DESC";

            $stmt = oci_parse($this->conn, $sql);
            oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);

            if (!oci_execute($stmt)) {
                error_log(oci_error($stmt)['message']);
                return [];
            }

            $items = [];
            while ($row = oci_fetch_assoc($stmt)) {
                $items[] = $this->processRow($row);
            }

            oci_free_statement($stmt);
            return $items;
        } catch (Exception $e) {
            error_log("getMenuItemsByKitchen error: " . $e->getMessage());
            return [];
        }
    }

    public function getById($itemId)
    {
        $sql = "SELECT 
                    mi.*, k.kitchen_id, k.name as kitchen_name, k.approval_status,
                    (SELECT LISTAGG(c.category_id, ',') WITHIN GROUP (ORDER BY c.category_id)
                     FROM menu_item_categories mic
                     JOIN categories c ON mic.category_id = c.category_id
                     WHERE mic.item_id = mi.item_id) as category_ids,
                    (SELECT LISTAGG(c.name, ', ') WITHIN GROUP (ORDER BY c.name)
                     FROM menu_item_categories mic
                     JOIN categories c ON mic.category_id = c.category_id
                     WHERE mic.item_id = mi.item_id) as categories
                FROM menu_items mi JOIN kitchens k ON mi.kitchen_id = k.kitchen_id
                WHERE mi.item_id = :item_id";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':item_id', $itemId);

        if (!oci_execute($stmt)) {
            return false;
        }

        $row = oci_fetch_assoc($stmt);
        if (!$row) {
            return false;
        }

        $item = $this->processRow($row);
        oci_free_statement($stmt);

        return $item;
    }

    public function getByKitchenAndId($kitchenId, $itemId)
    {
        $sql = "SELECT 
                    mi.*, 
                    (SELECT LISTAGG(c.category_id, ',') WITHIN GROUP (ORDER BY c.category_id)
                     FROM menu_item_categories mic
                     JOIN categories c ON mic.category_id = c.category_id
                     WHERE mic.item_id = mi.item_id) as category_ids,
                    (SELECT LISTAGG(c.name, ', ') WITHIN GROUP (ORDER BY c.name)
                     FROM menu_item_categories mic
                     JOIN categories c ON mic.category_id = c.category_id
                     WHERE mic.item_id = mi.item_id) as categories
                FROM menu_items mi 
                WHERE mi.kitchen_id = :kitchen_id AND mi.item_id = :item_id";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_bind_by_name($stmt, ':item_id', $itemId);

        if (!oci_execute($stmt)) {
            return false;
        }

        $row = oci_fetch_assoc($stmt);
        if (!$row) {
            return false;
        }

        $item = $this->processRow($row);
        oci_free_statement($stmt);

        return $item;
    }

    public function countByKitchen($kitchenId)
    {
        $sql = "SELECT COUNT(*) AS total FROM menu_items WHERE kitchen_id = :kitchen_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return (int)($row['TOTAL'] ?? 0);
    }

    public function countAvailableByKitchen($kitchenId)
    {
        $sql = "SELECT COUNT(*) AS total FROM menu_items WHERE kitchen_id = :kitchen_id AND is_available = 1";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return (int)($row['TOTAL'] ?? 0);
    }

    public function create($data)
    {
        // First insert the menu item
        $sql =
            "INSERT INTO menu_items (
                kitchen_id, name, description, portion_size, price, 
                spice_level, daily_stock, is_available, item_image
            ) VALUES (
                :kitchen_id, :name, :description, :portion_size, :price,
                :spice_level, :daily_stock, :is_available, :item_image
            ) RETURNING item_id INTO :item_id";

        $stmt = oci_parse($this->conn, $sql);
        $itemId = null;

        // Bind parameters
        oci_bind_by_name($stmt, ':kitchen_id', $data['kitchen_id']);
        oci_bind_by_name($stmt, ':name', $data['name']);
        oci_bind_by_name($stmt, ':description', $data['description']);
        oci_bind_by_name($stmt, ':portion_size', $data['portion_size']);
        oci_bind_by_name($stmt, ':price', $data['price']);
        oci_bind_by_name($stmt, ':spice_level', $data['spice_level']);
        oci_bind_by_name($stmt, ':daily_stock', $data['daily_stock']);
        oci_bind_by_name($stmt, ':is_available', $data['is_available']);
        oci_bind_by_name($stmt, ':item_image', $data['item_image']);
        oci_bind_by_name($stmt, ':item_id', $itemId, -1, SQLT_INT);

        if (!oci_execute($stmt)) {
            oci_free_statement($stmt);
            return false;
        }
        oci_free_statement($stmt);

        // Then insert category associations
        if (!empty($data['category_ids']) && is_array($data['category_ids'])) {
            foreach ($data['category_ids'] as $categoryId) {
                $this->addItemToCategory($itemId, $categoryId);
            }
        }

        return $itemId;
    }

    public function update($itemId, $data)
    {
        // Update the menu item
        $sql =
            "UPDATE menu_items SET 
                name = :name,
                description = :description,
                portion_size = :portion_size,
                price = :price,
                spice_level = :spice_level,
                daily_stock = :daily_stock,
                is_available = :is_available,
                item_image = :item_image,
                updated_at = CURRENT_TIMESTAMP
            WHERE item_id = :item_id";

        $stmt = oci_parse($this->conn, $sql);

        // Bind parameters
        oci_bind_by_name($stmt, ':name', $data['name']);
        oci_bind_by_name($stmt, ':description', $data['description']);
        oci_bind_by_name($stmt, ':portion_size', $data['portion_size']);
        oci_bind_by_name($stmt, ':price', $data['price']);
        oci_bind_by_name($stmt, ':spice_level', $data['spice_level']);
        oci_bind_by_name($stmt, ':daily_stock', $data['daily_stock']);
        oci_bind_by_name($stmt, ':is_available', $data['is_available']);
        oci_bind_by_name($stmt, ':item_image', $data['item_image']);
        oci_bind_by_name($stmt, ':item_id', $itemId);

        $result = oci_execute($stmt);
        oci_free_statement($stmt);

        if (!$result) {
            return false;
        }

        // Update categories if provided
        if (isset($data['category_ids'])) {
            // First remove existing categories
            $this->removeAllCategories($itemId);

            // Then add new categories
            if (!empty($data['category_ids']) && is_array($data['category_ids'])) {
                foreach ($data['category_ids'] as $categoryId) {
                    $this->addItemToCategory($itemId, $categoryId);
                }
            }
        }

        return true;
    }

    public function getItemStock($itemId)
    {
        $sql = "SELECT daily_stock FROM menu_items WHERE item_id = :item_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':item_id', $itemId);

        if (!oci_execute($stmt)) {
            return null;
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? (int)$row['DAILY_STOCK'] : null;
    }

    public function updateStockAndAvailability($itemId, $dailyStock, $isAvailable = null)
    {
        try {
            // If isAvailable is not provided, determine it based on stock
            if ($isAvailable === null) {
                $isAvailable = $dailyStock > 0 ? 1 : 0;
            }

            $sql = "UPDATE menu_items 
                SET daily_stock = :daily_stock, 
                    is_available = :is_available,
                    updated_at = SYSTIMESTAMP 
                WHERE item_id = :item_id";

            $stmt = oci_parse($this->conn, $sql);
            oci_bind_by_name($stmt, ':daily_stock', $dailyStock);
            oci_bind_by_name($stmt, ':is_available', $isAvailable);
            oci_bind_by_name($stmt, ':item_id', $itemId);

            $result = oci_execute($stmt);

            if (!$result) {
                $error = oci_error($stmt);
                error_log("Failed to update stock and availability: " . $error['message']);
                return false;
            }

            return true;
        } catch (Exception $e) {
            error_log("Error in updateStockAndAvailability: " . $e->getMessage());
            return false;
        }
    }

    public function delete($itemId)
    {
        $sql = "DELETE FROM menu_items WHERE item_id = :item_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':item_id', $itemId);

        $result = oci_execute($stmt);
        oci_free_statement($stmt);

        return $result;
    }

    public function getPopularItems($kitchenId, $limit = 5)
    {
        $sql =
            "SELECT mi.*, COUNT(oi.order_item_id) as order_count
            FROM menu_items mi
            LEFT JOIN order_items oi ON mi.item_id = oi.item_id
            WHERE mi.kitchen_id = :kitchen_id
            GROUP BY mi.item_id, mi.kitchen_id, mi.name, mi.description, mi.portion_size, 
                     mi.price, mi.spice_level, mi.daily_stock, mi.is_available, 
                     mi.item_image, mi.created_at, mi.updated_at
            ORDER BY order_count DESC
            FETCH FIRST :limit ROWS ONLY";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_bind_by_name($stmt, ':limit', $limit);

        if (!oci_execute($stmt)) {
            return [];
        }

        $items = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $row = $this->processRow($row);
            // if ($processedRow) {
            $items[] = $row;
            // }
        }

        oci_free_statement($stmt);
        return $items;
    }

    public function popularItems($limit = 5)
    {
        $sql =
            "SELECT 
                mi.item_id,
                mi.name AS item_name,
                mi.item_image,
                mi.price,
                k.name AS kitchen_name,
                k.kitchen_id,
                COUNT(DISTINCT o.order_id) AS total_orders,
                SUM(oi.quantity) AS total_quantity,
                NVL(AVG(r.rating), 0) AS avg_rating,
                COUNT(r.review_id) AS total_reviews
            FROM menu_items mi
            JOIN kitchens k ON mi.kitchen_id = k.kitchen_id
            JOIN order_items oi ON mi.item_id = oi.item_id
            JOIN orders o ON oi.order_id = o.order_id AND o.status = 'DELIVERED'
            LEFT JOIN reviews r ON o.order_id = r.reference_id AND r.reference_type = 'ITEM'
            WHERE mi.is_available = 1
                AND k.approval_status = 'approved'
                AND o.created_at >= ADD_MONTHS(SYSDATE, -1)
            GROUP BY mi.item_id, mi.name, mi.item_image, mi.price, k.name, k.kitchen_id
            ORDER BY total_quantity DESC, total_orders DESC
            FETCH FIRST :limit ROWS ONLY";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':limit', $limit);
        oci_execute($stmt);

        $items = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $row = $this->processRow($row);
            $items[] = $row;
        }

        oci_free_statement($stmt);
        return $items;
    }

    private function addItemToCategory($itemId, $categoryId)
    {
        $sql = "INSERT INTO menu_item_categories (item_id, category_id) VALUES (:item_id, :category_id)";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':item_id', $itemId);
        oci_bind_by_name($stmt, ':category_id', $categoryId);
        $result = oci_execute($stmt);
        oci_free_statement($stmt);
        return $result;
    }

    private function removeAllCategories($itemId)
    {
        $sql = "DELETE FROM menu_item_categories WHERE item_id = :item_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':item_id', $itemId);
        $result = oci_execute($stmt);
        oci_free_statement($stmt);
        return $result;
    }

    public function getItemsByCategory($kitchenId, $categoryId)
    {
        $sql = "SELECT mi.* 
                FROM menu_items mi
                JOIN menu_item_categories mic ON mi.item_id = mic.item_id
                WHERE mi.kitchen_id = :kitchen_id AND mic.category_id = :category_id AND mi.is_available = 1
                ORDER BY mi.name";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_bind_by_name($stmt, ':category_id', $categoryId);

        if (!oci_execute($stmt)) {
            return [];
        }

        $items = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $processedRow = $this->processRow($row);
            if ($processedRow) {
                $items[] = $processedRow;
            }
        }

        oci_free_statement($stmt);
        return $items;
    }

    public function getFilteredDishes($filters, $limit, $offset)
    {
        try {
            $params = [];
            $conditions = [];

            // Base conditions
            $conditions[] = "mi.IS_AVAILABLE = 1";
            $conditions[] = "mi.DAILY_STOCK > 0";
            $conditions[] = "k.APPROVAL_STATUS = 'approved'";
            $conditions[] = "u.STATUS = 'active' AND u.ROLE = 'seller'";
            $conditions[] = "ks.REFERENCE_ID IS NULL";
            $conditions[] = "active_ss.seller_id IS NOT NULL";
            $conditions[] = "EXISTS (
            SELECT 1
            FROM KITCHEN_SERVICE_ZONES ksz
            WHERE ksz.KITCHEN_ID = k.KITCHEN_ID
        )";

            // Category filter
            if (!empty($filters['category'])) {
                $conditions[] = "EXISTS (
                SELECT 1 
                FROM MENU_ITEM_CATEGORIES mic
                JOIN CATEGORIES c ON mic.CATEGORY_ID = c.CATEGORY_ID
                WHERE mic.ITEM_ID = mi.ITEM_ID
                AND LOWER(c.NAME) = :category_name
            )";
                $params[':category_name'] = strtolower($filters['category']);
            }

            // Search filter
            if (!empty($filters['search'])) {
                $conditions[] = "(
                LOWER(mi.NAME) LIKE :search 
                OR LOWER(TO_CHAR(SUBSTR(mi.DESCRIPTION, 1, 4000))) LIKE :search
            )";
                $params[':search'] = '%' . strtolower($filters['search']) . '%';
            }

            // Location filter
            if (!empty($filters['location'])) {
                $conditions[] = "EXISTS (
                SELECT 1
                FROM KITCHEN_SERVICE_ZONES ksz
                JOIN SERVICE_AREAS sa ON sa.AREA_ID = ksz.AREA_ID
                WHERE ksz.KITCHEN_ID = k.KITCHEN_ID
                AND LOWER(sa.NAME) = :location_name
            )";
                $params[':location_name'] = strtolower($filters['location']);
            }

            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

            $sql = "SELECT 
                    mi.ITEM_ID,
                    mi.NAME,
                    mi.DESCRIPTION,
                    mi.PRICE,
                    mi.ITEM_IMAGE,
                    mi.SPICE_LEVEL,
                    mi.PORTION_SIZE,
                    mi.DAILY_STOCK,
                    mi.CREATED_AT,

                    k.KITCHEN_ID,
                    k.NAME AS KITCHEN_NAME,

                    (
                        SELECT LISTAGG(c.NAME, ', ') WITHIN GROUP (ORDER BY c.NAME)
                        FROM MENU_ITEM_CATEGORIES mic
                        JOIN CATEGORIES c ON mic.CATEGORY_ID = c.CATEGORY_ID
                        WHERE mic.ITEM_ID = mi.ITEM_ID
                    ) AS CATEGORY_NAME,

                    -- (
                    --     SELECT LISTAGG(sa.name, ', ') WITHIN GROUP (ORDER BY sa.name)
                    --     FROM kitchen_service_zones ksz2
                    --     JOIN service_areas sa ON ksz2.area_id = sa.area_id
                    --     WHERE ksz2.kitchen_id = k.kitchen_id
                    -- ) AS SERVICE_AREAS,

                    COALESCE(r.avg_rating, 0) AS AVG_RATING,
                    COALESCE(r.review_count, 0) AS REVIEW_COUNT,
                    
                    -- Recent reviews as JSON (will be converted by processRow)
                    (
                        SELECT JSON_ARRAYAGG(
                            JSON_OBJECT(
                                'review_id' VALUE rev.review_id,
                                'reviewer_name' VALUE u2.name,
                                'rating' VALUE rev.rating,
                                'comments' VALUE TO_CHAR(SUBSTR(rev.comments, 1, 1000)),
                                'review_date' VALUE TO_CHAR(rev.created_at, 'YYYY-MM-DD HH24:MI:SS')
                            ) RETURNING CLOB
                        )
                        FROM (
                            SELECT * FROM (
                                SELECT r.*, ROW_NUMBER() OVER (ORDER BY r.created_at DESC) as rn
                                FROM reviews r
                                WHERE r.reference_id = mi.ITEM_ID
                                AND r.reference_type = 'ITEM'
                                AND r.status = 'PUBLIC'
                            )
                            WHERE rn <= 3
                        ) rev
                        JOIN users u2 ON rev.reviewer_id = u2.user_id
                    ) AS RECENT_REVIEWS

                FROM MENU_ITEMS mi
                JOIN KITCHENS k ON mi.KITCHEN_ID = k.KITCHEN_ID
                JOIN USERS u ON u.USER_ID = k.OWNER_ID

                /* Latest ACTIVE subscription */
                LEFT JOIN (
                    SELECT seller_id, status, start_date, end_date
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
                        FROM SELLER_SUBSCRIPTIONS ss
                    )
                    WHERE rn = 1
                    AND status = 'ACTIVE'
                    AND end_date >= SYSDATE
                ) active_ss ON active_ss.seller_id = u.USER_ID

                /* Exclude suspended kitchens */
                LEFT JOIN SUSPENSIONS ks 
                    ON ks.REFERENCE_ID = k.KITCHEN_ID
                    AND ks.REFERENCE_TYPE = 'KITCHEN'
                    AND ks.STATUS = 'active'
                    AND (ks.SUSPENDED_UNTIL IS NULL OR ks.SUSPENDED_UNTIL > SYSDATE)

                /* Public ratings (ITEM) */
                LEFT JOIN (
                    SELECT 
                        REFERENCE_ID,
                        AVG(RATING) AS avg_rating,
                        COUNT(*) AS review_count
                    FROM REVIEWS 
                    WHERE REFERENCE_TYPE = 'ITEM'
                    AND STATUS = 'PUBLIC'
                    GROUP BY REFERENCE_ID
                ) r ON r.REFERENCE_ID = mi.ITEM_ID

                $whereClause";

            // Sorting
            if (!empty($filters['price'])) {
                if ($filters['price'] === 'low_to_high') {
                    $sql .= " ORDER BY mi.PRICE ASC";
                } elseif ($filters['price'] === 'high_to_low') {
                    $sql .= " ORDER BY mi.PRICE DESC";
                }
            } else {
                $sql .= " ORDER BY mi.CREATED_AT DESC";
            }

            // Pagination
            $sql .= " OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";
            $params[':offset'] = $offset;
            $params[':limit'] = $limit;

            $stmt = oci_parse($this->conn, $sql);

            // Bind all parameters
            foreach ($params as $key => &$value) {
                oci_bind_by_name($stmt, $key, $value);
            }

            if (!oci_execute($stmt)) {
                $error = oci_error($stmt);
                error_log("Oracle Error in getFilteredDishes: " . ($error['message'] ?? 'Unknown error'));
                return [];
            }

            $items = [];
            while ($row = oci_fetch_assoc($stmt)) {
                $item = $this->processRow($row);

                // Parse JSON string to array
                if (!empty($item['RECENT_REVIEWS'])) {
                    $item['RECENT_REVIEWS'] = json_decode($item['RECENT_REVIEWS'], true) ?? [];
                } else {
                    $item['RECENT_REVIEWS'] = [];
                }

                $items[] = $item;
            }

            oci_free_statement($stmt);
            return $items;
        } catch (Exception $e) {
            error_log("getFilteredDishes error: " . $e->getMessage());
            return [];
        }
    }

    public function countFilteredDishes($filters)
    {
        try {
            $params = [];
            $conditions = [];

            // Same conditions as getFilteredDishes()
            $conditions[] = "mi.IS_AVAILABLE = 1";
            $conditions[] = "mi.DAILY_STOCK > 0";
            $conditions[] = "k.APPROVAL_STATUS = 'approved'";
            $conditions[] = "u.STATUS = 'active' AND u.ROLE = 'seller'";
            $conditions[] = "ks.REFERENCE_ID IS NULL";
            $conditions[] = "active_ss.seller_id IS NOT NULL";
            $conditions[] = "EXISTS (
                SELECT 1 FROM KITCHEN_SERVICE_ZONES ksz WHERE ksz.KITCHEN_ID = k.KITCHEN_ID
            )";

            // Category filter
            if (!empty($filters['category'])) {
                $conditions[] = "EXISTS (
                SELECT 1 
                FROM MENU_ITEM_CATEGORIES mic
                JOIN CATEGORIES c ON mic.CATEGORY_ID = c.CATEGORY_ID
                WHERE mic.ITEM_ID = mi.ITEM_ID
                AND LOWER(c.NAME) = :category_name
            )";
                $params[':category_name'] = strtolower($filters['category']);
            }

            // Search filter
            if (!empty($filters['search'])) {
                $conditions[] = "(
                LOWER(mi.NAME) LIKE :search 
                OR LOWER(TO_CHAR(SUBSTR(mi.DESCRIPTION, 1, 4000))) LIKE :search
            )";
                $params[':search'] = '%' . strtolower($filters['search']) . '%';
            }

            // Location filter
            if (!empty($filters['location'])) {
                $conditions[] = "EXISTS (
                SELECT 1
                FROM KITCHEN_SERVICE_ZONES ksz
                JOIN SERVICE_AREAS sa ON sa.AREA_ID = ksz.AREA_ID
                WHERE ksz.KITCHEN_ID = k.KITCHEN_ID
                AND LOWER(sa.NAME) = :location_name
            )";
                $params[':location_name'] = strtolower($filters['location']);
            }

            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

            $sql = "SELECT COUNT(DISTINCT mi.ITEM_ID) AS TOTAL
                FROM MENU_ITEMS mi
                JOIN KITCHENS k ON mi.KITCHEN_ID = k.KITCHEN_ID
                JOIN USERS u ON u.USER_ID = k.OWNER_ID
                
                /* Latest ACTIVE subscription */
                LEFT JOIN (
                    SELECT seller_id, status, start_date, end_date
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
                        FROM SELLER_SUBSCRIPTIONS ss
                    )
                    WHERE rn = 1
                    AND status = 'ACTIVE'
                    AND end_date >= SYSDATE
                ) active_ss ON active_ss.seller_id = u.USER_ID
                
                /* Exclude suspended kitchens */
                LEFT JOIN SUSPENSIONS ks 
                    ON ks.REFERENCE_ID = k.KITCHEN_ID
                    AND ks.REFERENCE_TYPE = 'KITCHEN'
                    AND ks.STATUS = 'active'
                    AND (ks.SUSPENDED_UNTIL IS NULL OR ks.SUSPENDED_UNTIL > SYSDATE)
                
                $whereClause";

            $stmt = oci_parse($this->conn, $sql);

            // Bind all parameters
            foreach ($params as $key => &$value) {
                oci_bind_by_name($stmt, $key, $value);
            }

            if (!oci_execute($stmt)) {
                $error = oci_error($stmt);
                error_log("Oracle Error in countFilteredDishes: " . ($error['message'] ?? 'Unknown error'));
                oci_free_statement($stmt);
                return 0;
            }

            $row = oci_fetch_assoc($stmt);
            $total = (int)($row['TOTAL'] ?? 0);

            oci_free_statement($stmt);
            return $total;
        } catch (Exception $e) {
            error_log("countFilteredDishes error: " . $e->getMessage());
            return 0;
        }
    }

    public function getRelatedItems($itemId, $limit = 4)
    {
        try {
            $sql = "SELECT * FROM (
            SELECT 
                mi.ITEM_ID,
                mi.NAME,
                mi.DESCRIPTION,
                mi.PRICE,
                mi.ITEM_IMAGE,
                mi.SPICE_LEVEL,
                mi.PORTION_SIZE,
                mi.DAILY_STOCK,
                mi.CREATED_AT,
                mi.IS_AVAILABLE,

                k.KITCHEN_ID,
                k.NAME AS KITCHEN_NAME,
                k.APPROVAL_STATUS,

                (
                    SELECT LISTAGG(c.NAME, ', ') WITHIN GROUP (ORDER BY c.NAME)
                    FROM MENU_ITEM_CATEGORIES mic
                    JOIN CATEGORIES c ON mic.CATEGORY_ID = c.CATEGORY_ID
                    WHERE mic.ITEM_ID = mi.ITEM_ID
                ) AS CATEGORY_NAME,

                -- (
                --     SELECT LISTAGG(sa.name, ', ') WITHIN GROUP (ORDER BY sa.name)
                --     FROM kitchen_service_zones ksz2
                --     JOIN service_areas sa ON ksz2.area_id = sa.area_id
                --     WHERE ksz2.kitchen_id = k.kitchen_id
                -- ) AS SERVICE_AREAS,

                COALESCE(r.avg_rating, 0) AS AVG_RATING,
                COALESCE(r.review_count, 0) AS REVIEW_COUNT,

                priority

            FROM (
                -- Priority 1: Same category, different kitchen
                SELECT mi.*, 1 AS priority
                FROM MENU_ITEMS mi
                WHERE mi.ITEM_ID != :item_id1
                AND mi.DAILY_STOCK > 0
                AND mi.IS_AVAILABLE = 1
                AND mi.KITCHEN_ID != (
                    SELECT KITCHEN_ID FROM MENU_ITEMS WHERE ITEM_ID = :item_id2
                )
                AND EXISTS (
                    SELECT 1 FROM MENU_ITEM_CATEGORIES mic
                    WHERE mic.ITEM_ID = mi.ITEM_ID
                    AND mic.CATEGORY_ID IN (
                        SELECT CATEGORY_ID 
                        FROM MENU_ITEM_CATEGORIES
                        WHERE ITEM_ID = :item_id3
                    )
                )

                UNION ALL

                -- Priority 2: Same kitchen
                SELECT mi.*, 2 AS priority
                FROM MENU_ITEMS mi
                WHERE mi.ITEM_ID != :item_id4
                AND mi.DAILY_STOCK > 0
                AND mi.IS_AVAILABLE = 1
                AND mi.KITCHEN_ID = (
                    SELECT KITCHEN_ID FROM MENU_ITEMS WHERE ITEM_ID = :item_id5
                )
            ) mi

            JOIN KITCHENS k 
                ON mi.KITCHEN_ID = k.KITCHEN_ID
                AND k.APPROVAL_STATUS = 'approved'

            JOIN USERS u 
                ON u.USER_ID = k.OWNER_ID
                AND u.STATUS = 'active'
                AND u.ROLE = 'seller'

            /* Active subscription */
            LEFT JOIN (
                SELECT seller_id
                FROM SELLER_SUBSCRIPTIONS
                WHERE STATUS = 'ACTIVE'
                AND END_DATE >= SYSDATE
                GROUP BY seller_id
            ) active_ss 
                ON active_ss.seller_id = u.USER_ID

            /* Exclude suspended kitchens */
            LEFT JOIN SUSPENSIONS ks 
                ON ks.REFERENCE_ID = k.KITCHEN_ID
                AND ks.REFERENCE_TYPE = 'KITCHEN'
                AND ks.STATUS = 'active'
                AND (ks.SUSPENDED_UNTIL IS NULL OR ks.SUSPENDED_UNTIL > SYSDATE)

            /* Ratings */
            LEFT JOIN (
                SELECT 
                    REFERENCE_ID,
                    AVG(RATING) AS avg_rating,
                    COUNT(*) AS review_count
                FROM REVIEWS 
                WHERE REFERENCE_TYPE = 'ITEM'
                AND STATUS = 'PUBLIC'
                GROUP BY REFERENCE_ID
            ) r 
                ON r.REFERENCE_ID = mi.ITEM_ID

            WHERE active_ss.seller_id IS NOT NULL
            AND ks.REFERENCE_ID IS NULL
            AND EXISTS (
                SELECT 1
                FROM KITCHEN_SERVICE_ZONES ksz
                WHERE ksz.KITCHEN_ID = k.KITCHEN_ID
            )

            ORDER BY priority, mi.CREATED_AT DESC
        )
        FETCH FIRST :limit ROWS ONLY";

            $stmt = oci_parse($this->conn, $sql);

            oci_bind_by_name($stmt, ':item_id1', $itemId);
            oci_bind_by_name($stmt, ':item_id2', $itemId);
            oci_bind_by_name($stmt, ':item_id3', $itemId);
            oci_bind_by_name($stmt, ':item_id4', $itemId);
            oci_bind_by_name($stmt, ':item_id5', $itemId);
            oci_bind_by_name($stmt, ':limit', $limit);

            if (!oci_execute($stmt)) {
                $error = oci_error($stmt);
                error_log("Oracle Error in getRelatedItems: " . ($error['message'] ?? 'Unknown error'));
                oci_free_statement($stmt);
                return [];
            }

            $items = [];
            while ($row = oci_fetch_assoc($stmt)) {
                $items[] = $this->processRow($row);
            }

            oci_free_statement($stmt);
            return $items;
        } catch (Exception $e) {
            error_log("getRelatedItems error: " . $e->getMessage());
            return [];
        }
    }
}
