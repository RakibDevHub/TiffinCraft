<?php

class Cart
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

    public function getCartItems($userId)
    {
        $sql = "SELECT 
                c.cart_id, 
                c.item_id, 
                c.quantity,
                mi.name, 
                mi.price, 
                mi.description, 
                mi.item_image,
                mi.daily_stock,
                mi.is_available,
                k.kitchen_id, 
                k.name as kitchen_name,
                k.approval_status,
                k.owner_id,
                -- Kitchen suspension check
                CASE 
                    WHEN ks.reference_id IS NOT NULL 
                        AND ks.reference_type = 'KITCHEN'
                        AND ks.status = 'active'
                        AND (ks.suspended_until IS NULL OR ks.suspended_until > SYSDATE) 
                    THEN 1 
                    ELSE 0 
                END as is_kitchen_suspended,
                -- User suspension check
                CASE 
                    WHEN us.reference_id IS NOT NULL 
                        AND us.reference_type = 'USER'
                        AND us.status = 'active'
                        AND (us.suspended_until IS NULL OR us.suspended_until > SYSDATE) 
                    THEN 1 
                    ELSE 0 
                END as is_user_suspended,
                -- Active subscription check
                ss.status as subscription_status,
                ss.end_date as subscription_end_date,
                -- Service areas check
                (SELECT COUNT(*) 
                 FROM kitchen_service_zones ksz 
                 WHERE ksz.kitchen_id = k.kitchen_id) as service_areas_count
            FROM cart c
            JOIN menu_items mi ON c.item_id = mi.item_id
            JOIN kitchens k ON mi.kitchen_id = k.kitchen_id
            LEFT JOIN suspensions ks ON k.kitchen_id = ks.reference_id 
                AND ks.reference_type = 'KITCHEN'
            LEFT JOIN suspensions us ON k.owner_id = us.reference_id 
                AND us.reference_type = 'USER'
            LEFT JOIN seller_subscriptions ss ON k.owner_id = ss.seller_id 
                AND ss.status = 'ACTIVE'
                AND ss.end_date >= TRUNC(SYSDATE)
            WHERE c.user_id = :user_id
            ORDER BY c.added_at DESC";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':user_id', $userId);
        oci_execute($stmt);

        $items = [];
        while ($row = oci_fetch_assoc($stmt)) {
            // Process the row first
            $item = $this->processRow($row);

            // Check if item should be available in cart based on business rules
            $isAvailable = $this->isCartItemAvailable($item);

            if ($isAvailable) {
                $items[] = $item;
            }
        }

        oci_free_statement($stmt);
        return $items;
    }

    private function isCartItemAvailable($item)
    {
        if (strtoupper($item['APPROVAL_STATUS'] ?? '') !== 'APPROVED') {
            return false;
        }

        if (($item['IS_KITCHEN_SUSPENDED'] ?? 0) == 1) {
            return false;
        }

        if (($item['IS_USER_SUSPENDED'] ?? 0) == 1) {
            return false;
        }

        if (strtoupper($item['SUBSCRIPTION_STATUS'] ?? '') !== 'ACTIVE') {
            return false;
        }

        if (isset($item['SUBSCRIPTION_END_DATE']) && $item['SUBSCRIPTION_END_DATE']) {
            try {
                $endDate = new DateTime($item['SUBSCRIPTION_END_DATE']);
                $today = new DateTime();
                if ($endDate < $today) {
                    return false;
                }
            } catch (Exception $e) {
                return false;
            }
        }

        if (($item['SERVICE_AREAS_COUNT'] ?? 0) <= 0) {
            return false;
        }

        if (($item['IS_AVAILABLE'] ?? 0) != 1) {
            return false;
        }

        $dailyStock = (int)($item['DAILY_STOCK'] ?? 0);
        $quantity = (int)($item['QUANTITY'] ?? 0);
        if ($dailyStock < $quantity) {
            return false;
        }

        return true;
    }

    public function getCartTotal($userId)
    {
        $sql = "SELECT SUM(mi.price * c.quantity) as total
            FROM cart c
            JOIN menu_items mi ON c.item_id = mi.item_id
            JOIN kitchens k ON mi.kitchen_id = k.kitchen_id
            LEFT JOIN suspensions ks ON k.kitchen_id = ks.reference_id 
                AND ks.reference_type = 'KITCHEN'
            LEFT JOIN suspensions us ON k.owner_id = us.reference_id 
                AND us.reference_type = 'USER'
            LEFT JOIN seller_subscriptions ss ON k.owner_id = ss.seller_id 
                AND ss.status = 'ACTIVE'
                AND ss.end_date >= TRUNC(SYSDATE)
            WHERE c.user_id = :user_id
            AND k.approval_status = 'approved'
            AND (ks.reference_id IS NULL OR ks.status != 'active' 
                OR (ks.suspended_until IS NOT NULL AND ks.suspended_until < SYSDATE))
            AND (us.reference_id IS NULL OR us.status != 'active' 
                OR (us.suspended_until IS NOT NULL AND us.suspended_until < SYSDATE))
            AND ss.status = 'ACTIVE'
            AND mi.is_available = 1
            AND mi.daily_stock >= c.quantity
            AND EXISTS (
                SELECT 1 
                FROM kitchen_service_zones ksz 
                WHERE ksz.kitchen_id = k.kitchen_id
            )";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':user_id', $userId);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        $total = $row ? $row['TOTAL'] : 0;

        oci_free_statement($stmt);
        return $total;
    }

    public function getKitchenInfo($kitchenId)
    {
        $sql = "SELECT 
                k.kitchen_id, 
                k.name as kitchen_name, 
                k.address, 
                k.signature_dish,
                k.approval_status,
                -- Check if kitchen is suspended
                CASE 
                    WHEN ks.reference_id IS NOT NULL 
                        AND ks.reference_type = 'KITCHEN'
                        AND ks.status = 'active'
                        AND (ks.suspended_until IS NULL OR ks.suspended_until > SYSDATE) 
                    THEN 1 
                    ELSE 0 
                END as is_kitchen_suspended,
                -- Check if user is suspended
                CASE 
                    WHEN us.reference_id IS NOT NULL 
                        AND us.reference_type = 'USER'
                        AND us.status = 'active'
                        AND (us.suspended_until IS NULL OR us.suspended_until > SYSDATE) 
                    THEN 1 
                    ELSE 0 
                END as is_user_suspended,
                -- Check active subscription
                ss.status as subscription_status,
                ss.end_date as subscription_end_date
            FROM kitchens k
            LEFT JOIN suspensions ks ON k.kitchen_id = ks.reference_id 
                AND ks.reference_type = 'KITCHEN'
            LEFT JOIN suspensions us ON k.owner_id = us.reference_id 
                AND us.reference_type = 'USER'
            LEFT JOIN seller_subscriptions ss ON k.owner_id = ss.seller_id 
                AND ss.status = 'ACTIVE'
                AND ss.end_date >= TRUNC(SYSDATE)
            WHERE k.kitchen_id = :kitchen_id";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        if (!$row) {
            oci_free_statement($stmt);
            return null;
        }

        $kitchenInfo = $this->processRow($row);

        // Additional validation - check if kitchen meets all requirements
        if (!$this->isKitchenAvailable($kitchenInfo)) {
            oci_free_statement($stmt);
            return null;
        }

        oci_free_statement($stmt);
        return $kitchenInfo;
    }

    private function isKitchenAvailable($kitchenInfo)
    {
        if (strtoupper($kitchenInfo['APPROVAL_STATUS'] ?? '') !== 'APPROVED') {
            return false;
        }

        if (($kitchenInfo['IS_KITCHEN_SUSPENDED'] ?? 0) == 1) {
            return false;
        }

        if (($kitchenInfo['IS_USER_SUSPENDED'] ?? 0) == 1) {
            return false;
        }

        if (strtoupper($kitchenInfo['SUBSCRIPTION_STATUS'] ?? '') !== 'ACTIVE') {
            return false;
        }

        if (isset($kitchenInfo['SUBSCRIPTION_END_DATE']) && $kitchenInfo['SUBSCRIPTION_END_DATE']) {
            try {
                $endDate = new DateTime($kitchenInfo['SUBSCRIPTION_END_DATE']);
                $today = new DateTime();
                if ($endDate < $today) {
                    return false;
                }
            } catch (Exception $e) {
                return false;
            }
        }

        return true;
    }

    public function getItemQuantity($userId, $itemId)
    {
        $sql = "SELECT quantity FROM cart 
            WHERE user_id = :user_id AND item_id = :item_id";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':user_id', $userId);
        oci_bind_by_name($stmt, ':item_id', $itemId);

        try {
            oci_execute($stmt);
            $row = oci_fetch_assoc($stmt);
            $quantity = $row ? $row['QUANTITY'] : 0;
            oci_free_statement($stmt);
            return $quantity;
        } catch (Exception $e) {
            oci_free_statement($stmt);
            error_log("Get item quantity error: " . $e->getMessage());
            return 0;
        }
    }

    public function addItemToCart($userId, $itemId, $quantity = 1)
    {
        if ($quantity <= 0) {
            return ['success' => false, 'message' => 'Invalid quantity'];
        }

        oci_execute(oci_parse($this->conn, "SET TRANSACTION ISOLATION LEVEL SERIALIZABLE"));

        try {
            $checkSql = "SELECT 
                    mi.item_id,
                    mi.daily_stock,
                    mi.is_available,
                    mi.name as item_name,
                    k.approval_status,
                    ss.status as subscription_status
                FROM menu_items mi
                JOIN kitchens k ON mi.kitchen_id = k.kitchen_id
                LEFT JOIN seller_subscriptions ss ON k.owner_id = ss.seller_id 
                    AND ss.status = 'ACTIVE'
                    AND ss.end_date >= TRUNC(SYSDATE)
                WHERE mi.item_id = :item_id
                FOR UPDATE";

            $checkStmt = oci_parse($this->conn, $checkSql);
            oci_bind_by_name($checkStmt, ':item_id', $itemId);
            oci_execute($checkStmt);
            $itemData = oci_fetch_assoc($checkStmt);
            oci_free_statement($checkStmt);

            if (!$itemData) {
                oci_rollback($this->conn);
                return ['success' => false, 'message' => 'Item not found'];
            }

            // Process the row data
            $item = $this->processRow($itemData);
            $itemName = $item['ITEM_NAME'] ?? 'Item';

            if ($item['IS_AVAILABLE'] != 1) {
                oci_rollback($this->conn);
                return ['success' => false, 'message' => "$itemName is no longer available"];
            }

            if (strtoupper($item['APPROVAL_STATUS'] ?? '') !== 'APPROVED') {
                oci_rollback($this->conn);
                return ['success' => false, 'message' => "$itemName is currently unavailable"];
            }

            if (strtoupper($item['SUBSCRIPTION_STATUS'] ?? '') !== 'ACTIVE') {
                oci_rollback($this->conn);
                return ['success' => false, 'message' => "$itemName is currently unavailable"];
            }

            $availableStock = (int)$item['DAILY_STOCK'];
            $currentCartQty = $this->getItemQuantity($userId, $itemId);
            $requestedTotal = $currentCartQty + $quantity;

            if ($availableStock < $requestedTotal) {
                oci_rollback($this->conn);
                $available = max(0, $availableStock - $currentCartQty);

                if ($available > 0) {
                    return [
                        'success' => false,
                        'message' => "Only $available more available. You already have $currentCartQty in cart."
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => "Out of stock. You already have $currentCartQty in cart."
                    ];
                }
            }

            if ($currentCartQty > 0) {
                $updateSql = "UPDATE cart SET quantity = quantity + :quantity 
                      WHERE user_id = :user_id AND item_id = :item_id";
            } else {
                $updateSql = "INSERT INTO cart (user_id, item_id, quantity, added_at) 
                      VALUES (:user_id, :item_id, :quantity, SYSDATE)";
            }

            $updateStmt = oci_parse($this->conn, $updateSql);
            oci_bind_by_name($updateStmt, ':user_id', $userId);
            oci_bind_by_name($updateStmt, ':item_id', $itemId);
            oci_bind_by_name($updateStmt, ':quantity', $quantity);

            $result = oci_execute($updateStmt);
            $rowsAffected = oci_num_rows($updateStmt);
            oci_free_statement($updateStmt);

            if ($result && $rowsAffected > 0) {
                oci_commit($this->conn);
                return [
                    'success' => true,
                    'message' => "$itemName added to cart successfully!",
                    'item_name' => $itemName
                ];
            } else {
                oci_rollback($this->conn);
                return ['success' => false, 'message' => 'Failed to update cart'];
            }
        } catch (Exception $e) {
            oci_rollback($this->conn);
            error_log("Cart add error: " . $e->getMessage());

            $error = oci_error($this->conn);
            if ($error && strpos($error['message'], 'resource busy') !== false) {
                return ['success' => false, 'message' => 'Item is being processed. Please try again in a moment.'];
            }

            return ['success' => false, 'message' => 'An error occurred. Please try again.'];
        }
    }

    public function updateCartItem($userId, $itemId, $quantity)
    {
        if ($quantity <= 0) {
            return $this->removeFromCart($userId, $itemId);
        }

        oci_execute(oci_parse($this->conn, "SET TRANSACTION ISOLATION LEVEL SERIALIZABLE"));

        try {
            $stockSql = "SELECT 
                        mi.daily_stock,
                        mi.name as item_name,
                        mi.is_available
                    FROM menu_items mi
                    WHERE mi.item_id = :item_id
                    FOR UPDATE";

            $stmt = oci_parse($this->conn, $stockSql);
            oci_bind_by_name($stmt, ':item_id', $itemId);
            oci_execute($stmt);
            $row = oci_fetch_assoc($stmt);
            oci_free_statement($stmt);

            if (!$row) {
                oci_rollback($this->conn);
                return ['success' => false, 'message' => 'Item not found'];
            }

            $item = $this->processRow($row);
            $itemName = $item['ITEM_NAME'] ?? 'Item';

            if ($item['IS_AVAILABLE'] != 1) {
                oci_rollback($this->conn);
                return [
                    'success' => false,
                    'message' => "$itemName is no longer available. Removing from cart.",
                    'should_remove' => true
                ];
            }

            $availableStock = (int)$item['DAILY_STOCK'];
            if ($availableStock < $quantity) {
                oci_rollback($this->conn);
                return [
                    'success' => false,
                    'message' => "Only $availableStock available in stock",
                    'max_available' => $availableStock
                ];
            }

            $sql = "UPDATE cart SET quantity = :quantity 
                WHERE user_id = :user_id AND item_id = :item_id";

            $stmt = oci_parse($this->conn, $sql);
            oci_bind_by_name($stmt, ':quantity', $quantity);
            oci_bind_by_name($stmt, ':user_id', $userId);
            oci_bind_by_name($stmt, ':item_id', $itemId);

            $result = oci_execute($stmt);
            $rowsAffected = oci_num_rows($stmt);
            oci_free_statement($stmt);

            if ($result && $rowsAffected > 0) {
                oci_commit($this->conn);
                return ['success' => true, 'message' => "Quantity updated to $quantity"];
            } else {
                oci_rollback($this->conn);
                return ['success' => false, 'message' => 'Failed to update quantity'];
            }
        } catch (Exception $e) {
            oci_rollback($this->conn);
            error_log("Cart update error: " . $e->getMessage());

            $error = oci_error($this->conn);
            if ($error && strpos($error['message'], 'resource busy') !== false) {
                return ['success' => false, 'message' => 'Item is being processed. Please try again.'];
            }

            return ['success' => false, 'message' => 'An error occurred while updating quantity'];
        }
    }

    public function removeKitchenItemsFromCart($userId, $kitchenId)
    {
        try {
            $sql = "DELETE FROM cart 
                WHERE user_id = :user_id 
                AND item_id IN (
                    SELECT item_id FROM menu_items WHERE kitchen_id = :kitchen_id
                )";

            $stmt = oci_parse($this->conn, $sql);
            oci_bind_by_name($stmt, ':user_id', $userId);
            oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);

            $result = oci_execute($stmt);
            $rowsAffected = oci_num_rows($stmt);
            oci_free_statement($stmt);

            return $result && $rowsAffected > 0;

        } catch (Exception $e) {
            error_log("Remove kitchen items from cart error: " . $e->getMessage());
            return false;
        }
    }

    public function removeFromCart($userId, $itemId)
    {
        try {
            $itemSql = "SELECT mi.name as item_name 
                    FROM menu_items mi 
                    WHERE mi.item_id = :item_id";

            $stmt = oci_parse($this->conn, $itemSql);
            oci_bind_by_name($stmt, ':item_id', $itemId);
            oci_execute($stmt);
            $row = oci_fetch_assoc($stmt);
            $itemName = $row ? $row['ITEM_NAME'] : 'Item';
            oci_free_statement($stmt);

            $sql = "DELETE FROM cart 
                WHERE user_id = :user_id AND item_id = :item_id";

            $stmt = oci_parse($this->conn, $sql);
            oci_bind_by_name($stmt, ':user_id', $userId);
            oci_bind_by_name($stmt, ':item_id', $itemId);

            $result = oci_execute($stmt);
            $rowsAffected = oci_num_rows($stmt);
            oci_free_statement($stmt);

            if ($result && $rowsAffected > 0) {
                return ['success' => true, 'message' => "$itemName removed from cart"];
            } else {
                return ['success' => false, 'message' => 'Item not found in cart'];
            }
        } catch (Exception $e) {
            error_log("Remove from cart error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to remove item from cart'];
        }
    }

    public function clearCart($userId)
    {
        try {
            $sql = "DELETE FROM cart WHERE user_id = :user_id";
            $stmt = oci_parse($this->conn, $sql);
            oci_bind_by_name($stmt, ':user_id', $userId);

            $result = oci_execute($stmt);
            oci_free_statement($stmt);

            return $result;
        } catch (Exception $e) {
            error_log("Cart clearing failed for user {$userId}: " . $e->getMessage());
            return false;
        }
    }

    public function getCartItemsByKitchen($userId, $kitchenId)
    {
        $sql = 
            "SELECT 
                c.cart_id, 
                c.item_id, 
                c.quantity, 
                mi.name, 
                mi.price, 
                mi.description, 
                mi.item_image,
                mi.daily_stock,
                mi.is_available,
                k.kitchen_id, 
                k.name as kitchen_name,
                k.approval_status,
                -- Kitchen suspension
                CASE 
                    WHEN ks.reference_id IS NOT NULL 
                        AND ks.reference_type = 'KITCHEN'
                        AND ks.status = 'active'
                        AND (ks.suspended_until IS NULL OR ks.suspended_until > SYSDATE) 
                    THEN 1 
                    ELSE 0 
                END as is_kitchen_suspended,
                -- User suspension
                CASE 
                    WHEN us.reference_id IS NOT NULL 
                        AND us.reference_type = 'USER'
                        AND us.status = 'active'
                        AND (us.suspended_until IS NULL OR us.suspended_until > SYSDATE) 
                    THEN 1 
                    ELSE 0 
                END as is_user_suspended,
                ss.status as subscription_status,
                ss.end_date as subscription_end_date,
                (SELECT COUNT(*) 
                FROM kitchen_service_zones ksz 
                WHERE ksz.kitchen_id = k.kitchen_id) as service_areas_count
            FROM cart c
            JOIN menu_items mi ON c.item_id = mi.item_id
            JOIN kitchens k ON mi.kitchen_id = k.kitchen_id
            LEFT JOIN suspensions ks ON k.kitchen_id = ks.reference_id 
                AND ks.reference_type = 'KITCHEN'
            LEFT JOIN suspensions us ON k.owner_id = us.reference_id 
                AND us.reference_type = 'USER'
            LEFT JOIN seller_subscriptions ss ON k.owner_id = ss.seller_id 
                AND ss.status = 'ACTIVE'
                AND ss.end_date >= TRUNC(SYSDATE)
            WHERE c.user_id = :user_id
            AND mi.kitchen_id = :kitchen_id
            ORDER BY c.added_at DESC";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':user_id', $userId);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);

        if (!oci_execute($stmt)) {
            return [];
        }

        $items = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $item = $this->processRow($row);

            // SAME rule as cart page
            if ($this->isCartItemAvailable($item)) {
                $items[] = $item;
            }
        }

        oci_free_statement($stmt);
        return $items;
    }
}
