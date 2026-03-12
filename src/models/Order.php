<?php

class Order
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function countAll()
    {
        $sql = "SELECT COUNT(*) AS total FROM orders";
        $stmt = oci_parse($this->conn, $sql);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return (int)($row['TOTAL'] ?? 0);
    }

    public function countOrderByStatus($status)
    {
        $statusLower = strtolower($status);
        $sql = "SELECT COUNT(*) FROM orders WHERE LOWER(status) = :status";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':status', $statusLower);
        oci_execute($stmt);
        $row = oci_fetch_row($stmt);
        oci_free_statement($stmt);
        return $row[0] ?? 0;
    }

    public function orderGrowth($months = 6)
    {
        $sql =
            "SELECT TO_CHAR(created_at,'YYYY-MM') AS month, COUNT(*) AS total_orders
            FROM orders
            WHERE created_at >= ADD_MONTHS(TRUNC(SYSDATE,'MM'), -:months)
            GROUP BY TO_CHAR(created_at,'YYYY-MM')
            ORDER BY month";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':months', $months);
        oci_execute($stmt);
        $rows = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $rows[] = $this->processRow($row);
        }
        return $rows;
    }

    public function countOrders($kitchenId)
    {
        $sql = "SELECT COUNT(*) AS total FROM orders WHERE kitchen_id = :kitchen_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return (int)($row['TOTAL'] ?? 0);
    }

    public function countOrdersByStatus($kitchenId, $status)
    {
        $statusLower = strtolower($status);
        $sql = "SELECT COUNT(*) AS total FROM orders WHERE kitchen_id = :kitchen_id AND LOWER(status) = :status";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_bind_by_name($stmt, ':status', $statusLower);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return (int)($row['TOTAL'] ?? 0);
    }

    public function getRecentOrders($kitchenId, $limit = 5)
    {
        $sql =
            "SELECT 
                o.order_id,
                o.buyer_id,
                o.total_amount,
                o.delivery_fee,
                o.status,
                o.created_at,
                o.estimated_delivery_time,
                o.delivery_address,
                u.name as customer_name,
                u.phone as customer_phone,
                (SELECT COUNT(*) FROM order_items oi2 WHERE oi2.order_id = o.order_id) as item_count,
                oi.item_id,
                oi.quantity,
                oi.price_at_order as price,
                oi.special_request,
                mi.name as item_name
            FROM orders o
            LEFT JOIN users u ON o.buyer_id = u.user_id
            LEFT JOIN order_items oi ON o.order_id = oi.order_id
            LEFT JOIN menu_items mi ON oi.item_id = mi.item_id
            WHERE o.kitchen_id = :kitchen_id
            ORDER BY o.created_at DESC";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_execute($stmt);

        $orders = [];
        $currentOrderId = null;
        $currentOrder = null;

        while ($row = oci_fetch_assoc($stmt)) {
            $row = $this->processRow($row);

            if ($row['ORDER_ID'] !== $currentOrderId) {
                if ($currentOrder !== null && count($orders) < $limit) {
                    $orders[] = $currentOrder;
                }

                if (count($orders) >= $limit) {
                    break;
                }

                $currentOrderId = $row['ORDER_ID'];
                $currentOrder = [
                    'order_id' => $row['ORDER_ID'],
                    'order_number' => 'ORD-' . str_pad($row['ORDER_ID'], 6, '0', STR_PAD_LEFT),
                    'customer_name' => $row['CUSTOMER_NAME'] ?? 'Customer',
                    'customer_phone' => $row['CUSTOMER_PHONE'] ?? '',
                    'total_amount' => (float)$row['TOTAL_AMOUNT'],
                    'delivery_fee' => (float)($row['DELIVERY_FEE'] ?? 0),
                    'grand_total' => (float)$row['TOTAL_AMOUNT'] + (float)($row['DELIVERY_FEE'] ?? 0),
                    'status' => $row['STATUS'],
                    'created_at' => $row['CREATED_AT'],
                    'delivery_address' => $row['DELIVERY_ADDRESS'] ?? '',
                    'estimated_delivery_time' => $row['ESTIMATED_DELIVERY_TIME'] ?? null,
                    'item_count' => (int)($row['ITEM_COUNT'] ?? 0),
                    'items' => []
                ];
            }

            if (!empty($row['ITEM_ID'])) {
                $currentOrder['items'][] = [
                    'item_id' => $row['ITEM_ID'],
                    'item_name' => $row['ITEM_NAME'] ?? 'Unknown Item',
                    'quantity' => (int)$row['QUANTITY'],
                    'price' => (float)$row['PRICE'],
                    'special_request' => $row['SPECIAL_REQUEST'] ?? ''
                ];
            }
        }

        if ($currentOrder !== null && count($orders) < $limit) {
            $orders[] = $currentOrder;
        }

        oci_free_statement($stmt);
        return $orders;
    }

    public function getTodayOrders($kitchenId)
    {
        $sql =
            "SELECT COUNT(*) AS total 
            FROM orders 
            WHERE kitchen_id = :kitchen_id 
            AND TRUNC(created_at) = TRUNC(SYSDATE)";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return (int)($row['TOTAL'] ?? 0);
    }

    public function getTodayOrderStats($kitchenId)
    {
        $sql =
            "SELECT status,
                COUNT(*) AS count,
                NVL(SUM(total_amount + delivery_fee), 0) AS revenue
            FROM orders
            WHERE kitchen_id = :kitchen_id
            AND TRUNC(created_at) = TRUNC(SYSDATE)
            GROUP BY status";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_execute($stmt);

        $stats = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $row = $this->processRow($row);
            $status = strtolower($row['STATUS']); // now STATUS exists
            $stats[$status] = [
                'count'   => (int)$row['COUNT'],
                'revenue' => (float)$row['REVENUE']
            ];
        }

        oci_free_statement($stmt);
        return $stats;
    }

    public function getPopularItems($kitchenId, $limit = 5)
    {
        $sql =
            "SELECT 
                mi.name as item_name,
                mi.item_id,
                mi.item_image,
                COUNT(oi.item_id) as order_count,
                SUM(oi.quantity) as total_quantity,
                AVG(oi.price_at_order) as avg_price
            FROM order_items oi
            JOIN menu_items mi ON oi.item_id = mi.item_id
            JOIN orders o ON oi.order_id = o.order_id
            WHERE o.kitchen_id = :kitchen_id
            AND o.status IN ('DELIVERED', 'COMPLETED')
            GROUP BY mi.name, mi.item_id, mi.item_image
            ORDER BY total_quantity DESC";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_execute($stmt);

        $items = [];
        $count = 0;

        while (($row = oci_fetch_assoc($stmt)) !== false && $count < $limit) {
            $row = $this->processRow($row);
            $items[] = $row;
            $count++;
        }

        oci_free_statement($stmt);
        return $items;
    }

    public function getOrderStats($kitchenId)
    {
        $sql =
            "SELECT 
                status,
                COUNT(*) as count,
                NVL(SUM(total_amount + delivery_fee), 0) as revenue
            FROM orders 
            WHERE kitchen_id = :kitchen_id
            GROUP BY status";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_execute($stmt);

        $stats = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $row = $this->processRow($row);
            $stats[strtolower($row['STATUS'])] = [
                'count' => (int)$row['COUNT'],
                'revenue' => (float)$row['REVENUE']
            ];
        }

        oci_free_statement($stmt);
        return $stats;
    }

    private function getRevenue($kitchenId, $condition = "")
    {
        $sql =
            "SELECT 
            -- Totals across all orders
            NVL(SUM(o.total_amount), 0) AS gross_food_amount,
            NVL(SUM(o.delivery_fee), 0) AS total_delivery_fees,
            NVL(SUM(o.total_amount + o.delivery_fee), 0) AS gross_revenue,
            
            -- Commission calculation per order based on its subscription at order time
            NVL(SUM(
                o.total_amount * 
                COALESCE(
                    (SELECT sp.commission_rate / 100 
                     FROM seller_subscriptions ss
                     JOIN subscription_plans sp ON ss.plan_id = sp.plan_id
                     WHERE ss.seller_id = k.owner_id
                     AND o.created_at BETWEEN ss.start_date AND ss.end_date
                     AND ss.status = 'ACTIVE'
                     AND ROWNUM = 1),  -- Get first active subscription for the order date
                    0.15  -- Default commission rate if no subscription found (e.g., 15%)
                )
            ), 0) AS commission,
            
            -- Net revenue: (food - commission) + delivery
            NVL(SUM(
                o.total_amount - 
                (o.total_amount * 
                 COALESCE(
                    (SELECT sp.commission_rate / 100 
                     FROM seller_subscriptions ss
                     JOIN subscription_plans sp ON ss.plan_id = sp.plan_id
                     WHERE ss.seller_id = k.owner_id
                     AND o.created_at BETWEEN ss.start_date AND ss.end_date
                     AND ss.status = 'ACTIVE'
                     AND ROWNUM = 1),
                    0.15
                 )
                ) + 
                o.delivery_fee
            ), 0) AS net_revenue,
            
            COUNT(*) AS total_orders
        FROM orders o
        JOIN kitchens k ON o.kitchen_id = k.kitchen_id
        WHERE o.kitchen_id = :kitchen_id
        AND o.status = 'DELIVERED'
        $condition";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return [
            'gross_food_amount' => (float)($row['GROSS_FOOD_AMOUNT'] ?? 0),
            'total_delivery_fees' => (float)($row['TOTAL_DELIVERY_FEES'] ?? 0),
            'gross_revenue' => (float)($row['GROSS_REVENUE'] ?? 0),
            'commission' => (float)($row['COMMISSION'] ?? 0),
            'net_revenue' => (float)($row['NET_REVENUE'] ?? 0),
            'total_orders' => (int)($row['TOTAL_ORDERS'] ?? 0)
        ];
    }

    public function getTodayRevenue($kitchenId)
    {
        return $this->getRevenue($kitchenId, "AND TRUNC(o.created_at) = TRUNC(SYSDATE)");
    }

    public function getLast7DaysRevenue($kitchenId)
    {
        return $this->getRevenue($kitchenId, "AND o.created_at >= TRUNC(SYSDATE) - 6");
    }

    public function getMonthlyRevenue($kitchenId)
    {
        return $this->getRevenue($kitchenId, "AND o.created_at >= TRUNC(SYSDATE, 'MM')");
    }

    public function getTotalRevenue($kitchenId)
    {
        // All-time revenue
        return $this->getRevenue($kitchenId);
    }

    public function getDashboardStats($kitchenId)
    {
        $todayRevenueData = $this->getTodayRevenue($kitchenId);
        $weeklyRevenueData = $this->getLast7DaysRevenue($kitchenId);
        $monthlyRevenueData = $this->getMonthlyRevenue($kitchenId);
        $totalRevenueData = $this->getTotalRevenue($kitchenId);

        return [
            'totalOrders' => $this->countOrders($kitchenId),
            'todayOrders' => $this->getTodayOrders($kitchenId),
            'pendingOrders' => $this->countOrdersByStatus($kitchenId, 'PENDING'),
            'acceptedOrders' => $this->countOrdersByStatus($kitchenId, 'ACCEPTED'),
            'readyOrders' => $this->countOrdersByStatus($kitchenId, 'READY'),
            'completedOrders' => $this->countOrdersByStatus($kitchenId, 'DELIVERED'),
            'canceledOrders' => $this->countOrdersByStatus($kitchenId, 'CANCELLED'),
            'todayRevenue' => $todayRevenueData['net_revenue'] ?? 0,
            'todayRevenueData' => $todayRevenueData,
            'weeklyRevenue' => $weeklyRevenueData['net_revenue'] ?? 0,
            'weeklyRevenueData' => $weeklyRevenueData,
            'monthlyRevenue' => $monthlyRevenueData['net_revenue'] ?? 0,
            'monthlyRevenueData' => $monthlyRevenueData,
            'totalRevenue' => $totalRevenueData['net_revenue'] ?? 0,
            'totalRevenueData' => $totalRevenueData,
        ];
    }

    public function getOrdersByKitchenId($kitchenId)
    {
        $sql = "SELECT 
                o.order_id,
                o.buyer_id,
                u.name as customer_name,
                u.phone as customer_phone,
                o.total_amount,
                o.delivery_fee,
                o.status,
                o.delivery_address,
                o.contact_phone,
                o.estimated_delivery_time,
                o.created_at as order_date,
                o.updated_at,
                sa.name as delivery_area,
                (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) as item_count,
                (SELECT LISTAGG(mi.name || ' (x' || oi.quantity || ')', ', ') 
                 FROM order_items oi 
                 JOIN menu_items mi ON oi.item_id = mi.item_id 
                 WHERE oi.order_id = o.order_id) as items_summary
            FROM orders o
            JOIN users u ON o.buyer_id = u.user_id
            LEFT JOIN service_areas sa ON o.delivery_area_id = sa.area_id
            WHERE o.kitchen_id = :kitchen_id
            ORDER BY o.created_at DESC";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);

        if (!oci_execute($stmt)) {
            return [];
        }

        $orders = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $orders[] = $this->processRow($row);
        }

        oci_free_statement($stmt);
        return $orders;
    }

    public function createOrder($orderData, $cartItems)
    {
        try {

            $this->validateStock($cartItems);

            $orderId = $this->insertOrderRecord($orderData);
            if (!$orderId) {
                throw new RuntimeException("Failed to create order record");
            }

            $this->addOrderItems($orderId, $cartItems);

            $this->updateStock($cartItems);

            return $orderId;
        } catch (Exception $e) {
            error_log("Order creation failed: " . $e->getMessage());
            throw new RuntimeException("Order creation failed: " . $e->getMessage());
        }
    }

    public function restoreStock($orderId)
    {
        try {
            $sql = "SELECT item_id, quantity FROM order_items WHERE order_id = :order_id";
            $stmt = oci_parse($this->conn, $sql);
            oci_bind_by_name($stmt, ':order_id', $orderId);
            oci_execute($stmt);

            $items = [];
            while ($row = oci_fetch_assoc($stmt)) {
                $items[] = $row;
            }
            oci_free_statement($stmt);

            foreach ($items as $item) {
                $sql = "UPDATE menu_items 
                    SET daily_stock = daily_stock + :quantity,
                        is_available = CASE WHEN daily_stock + :quantity > 0 THEN 1 ELSE 0 END,
                        updated_at = SYSTIMESTAMP
                    WHERE item_id = :item_id";

                $stmt = oci_parse($this->conn, $sql);
                oci_bind_by_name($stmt, ':quantity', $item['QUANTITY']);
                oci_bind_by_name($stmt, ':item_id', $item['ITEM_ID']);
                oci_execute($stmt, OCI_NO_AUTO_COMMIT);
                oci_free_statement($stmt);
            }

            return true;
        } catch (Exception $e) {
            error_log("Stock restoration failed for order {$orderId}: " . $e->getMessage());
            return false;
        }
    }

    public function hasUserOrderedItem($userId, $itemId)
    {
        $sql = "SELECT 1 FROM ORDER_ITEMS oi
            JOIN ORDERS o ON oi.ORDER_ID = o.ORDER_ID
            WHERE o.BUYER_ID = :user_id
            AND oi.ITEM_ID = :item_id
            AND o.STATUS IN ('DELIVERED')
            AND ROWNUM = 1";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':user_id', $userId);
        oci_bind_by_name($stmt, ':item_id', $itemId);

        if (!oci_execute($stmt)) {
            error_log("Oracle Error in hasUserOrderedItem: " . oci_error($stmt)['message']);
            return false;
        }

        $hasOrdered = (oci_fetch($stmt) !== false);
        oci_free_statement($stmt);

        return $hasOrdered;
    }

    public function getBuyerOrders($buyerId, $statusFilter = 'all', $searchTerm = '', $limit = 10, $offset = 0)
    {
        $sql = "SELECT 
                o.*,
                k.name as kitchen_name, 
                k.address as kitchen_address,
                k.cover_image,
                sa.name as delivery_area
            FROM orders o
            JOIN kitchens k ON o.kitchen_id = k.kitchen_id
            LEFT JOIN service_areas sa ON o.delivery_area_id = sa.area_id
            WHERE o.buyer_id = :buyer_id
            AND o.buyer_delete = 0";

        $conditions = [];

        if ($statusFilter !== 'all') {
            $conditions[] = "LOWER(o.status) = LOWER(:status)";
        }

        if (!empty($searchTerm)) {
            // Create a parameterized search pattern
            $searchPattern = '%' . strtolower($searchTerm) . '%';

            // Item search subquery - use a separate parameter
            $itemSearchSubquery = "SELECT 1 
                          FROM order_items oi 
                          JOIN menu_items mi ON oi.item_id = mi.item_id 
                          WHERE oi.order_id = o.order_id 
                          AND (LOWER(mi.name) LIKE :item_search 
                               OR LOWER(mi.description) LIKE :item_search)";

            $searchConditions = [
                "LOWER(k.name) LIKE :search",
                "o.order_id LIKE :search_num",
                "LOWER(sa.name) LIKE :search",
                "LOWER(o.delivery_address) LIKE :search",
                "LOWER(o.contact_phone) LIKE :search_phone",
                "EXISTS ($itemSearchSubquery)"
            ];

            $conditions[] = "(" . implode(" OR ", $searchConditions) . ")";
        }

        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }

        // Count
        $countSql = "SELECT COUNT(*) as total FROM ($sql)";
        $countStmt = oci_parse($this->conn, $countSql);

        oci_bind_by_name($countStmt, ':buyer_id', $buyerId);

        if ($statusFilter !== 'all') {
            oci_bind_by_name($countStmt, ':status', $statusFilter);
        }

        if (!empty($searchTerm)) {
            $searchPattern = '%' . strtolower($searchTerm) . '%';
            $searchNumPattern = '%' . $searchTerm . '%'; // For order ID (keep numbers)
            $searchPhonePattern = '%' . $searchTerm . '%'; // For phone (no case change)

            oci_bind_by_name($countStmt, ':search', $searchPattern);
            oci_bind_by_name($countStmt, ':search_num', $searchNumPattern);
            oci_bind_by_name($countStmt, ':search_phone', $searchPhonePattern);
            oci_bind_by_name($countStmt, ':item_search', $searchPattern);
        }

        $total = 0;
        if (oci_execute($countStmt)) {
            $countRow = oci_fetch_assoc($countStmt);
            $total = $countRow ? (int)$countRow['TOTAL'] : 0;
        }
        oci_free_statement($countStmt);

        // Pagination
        $sql .= " ORDER BY o.created_at DESC";
        if ($limit > 0) {
            $sql .= " OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";
        }

        $stmt = oci_parse($this->conn, $sql);

        oci_bind_by_name($stmt, ':buyer_id', $buyerId);

        if ($statusFilter !== 'all') {
            oci_bind_by_name($stmt, ':status', $statusFilter);
        }

        if (!empty($searchTerm)) {
            $searchPattern = '%' . strtolower($searchTerm) . '%';
            $searchNumPattern = '%' . $searchTerm . '%';
            $searchPhonePattern = '%' . $searchTerm . '%';

            oci_bind_by_name($stmt, ':search', $searchPattern);
            oci_bind_by_name($stmt, ':search_num', $searchNumPattern);
            oci_bind_by_name($stmt, ':search_phone', $searchPhonePattern);
            oci_bind_by_name($stmt, ':item_search', $searchPattern);
        }

        if ($limit > 0) {
            $offsetVal = (int)$offset;
            $limitVal  = (int)$limit;

            oci_bind_by_name($stmt, ':offset', $offsetVal);
            oci_bind_by_name($stmt, ':limit', $limitVal);
        }

        $orders = [];
        if (oci_execute($stmt)) {
            while ($row = oci_fetch_assoc($stmt)) {
                $row = $this->processRow($row);
                $row['ITEMS'] = $this->getOrderItems($row['ORDER_ID']);
                $orders[] = $row;
            }
        }

        oci_free_statement($stmt);
        return ['orders' => $orders, 'total' => $total];
    }

    public function updateOrderStatus($data)
    {
        $kitchenId = $data['kitchenId'];
        $orderId = $data['orderId'];
        $status = $data['status'];
        $reason = $data['reason'] ?? null;

        // valid status transitions
        $validTransitions = [
            'PENDING' => ['ACCEPTED', 'CANCELLED'],
            'ACCEPTED' => ['READY', 'DELIVERED'],
            'READY' => ['DELIVERED']
        ];

        // current status
        $currentStatus = $this->getOrderStatus($kitchenId, $orderId);

        // Check transition
        if (!isset($validTransitions[$currentStatus]) || !in_array($status, $validTransitions[$currentStatus])) {
            return false;
        }

        $sql = "UPDATE orders 
            SET status = :status, 
                updated_at = SYSTIMESTAMP";

        // Add delivery time for delivered orders
        if ($status === 'DELIVERED') {
            $sql .= ", actual_delivery_time = SYSTIMESTAMP";
        }

        // Add cancellation reason if provided
        if ($status === 'CANCELLED' && $reason) {
            $sql .= ", cancellation_reason = :reason, cancel_by = 'SELLER'";
        }

        $sql .= " WHERE order_id = :order_id 
              AND kitchen_id = :kitchen_id";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':status', $status);
        oci_bind_by_name($stmt, ':order_id', $orderId);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);

        if ($status === 'CANCELLED' && $reason) {
            oci_bind_by_name($stmt, ':reason', $reason);
        }

        $result = oci_execute($stmt);
        oci_free_statement($stmt);

        return $result;
    }

    private function getOrderStatus($kitchenId, $orderId)
    {
        $sql = "SELECT status FROM orders 
            WHERE order_id = :order_id 
            AND kitchen_id = :kitchen_id";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':order_id', $orderId);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);

        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? $row['STATUS'] : null;
    }

    public function getActiveOrdersCount($kitchenId)
    {
        $sql = "SELECT 
                SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'ACCEPTED' THEN 1 ELSE 0 END) as accepted_count,
                SUM(CASE WHEN status = 'READY' THEN 1 ELSE 0 END) as ready_count
            FROM orders 
            WHERE kitchen_id = :kitchen_id 
            AND status IN ('PENDING', 'ACCEPTED', 'READY')";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return [
            'pending' => (int)($row['PENDING_COUNT'] ?? 0),
            'accepted' => (int)($row['ACCEPTED_COUNT'] ?? 0),
            'ready' => (int)($row['READY_COUNT'] ?? 0)
        ];
    }

    public function getOrderItems($orderId)
    {
        $sql = "SELECT 
                oi.order_item_id,
                oi.item_id,
                oi.quantity,
                oi.price_at_order,
                oi.special_request,
                mi.name,
                mi.description,
                mi.item_image
            FROM order_items oi
            LEFT JOIN menu_items mi ON oi.item_id = mi.item_id
            WHERE oi.order_id = :order_id
            ORDER BY oi.order_item_id";

        $stmt = oci_parse($this->conn, $sql);

        $orderIdVal = (int)$orderId; // avoid bind-by-ref issues
        oci_bind_by_name($stmt, ':order_id', $orderIdVal);

        $items = [];

        if (oci_execute($stmt)) {
            while ($row = oci_fetch_assoc($stmt)) {
                $row = $this->processRow($row);

                $items[] = [
                    'ORDER_ITEM_ID'   => $row['ORDER_ITEM_ID'],
                    'ITEM_ID'         => $row['ITEM_ID'],
                    'NAME'            => $row['NAME'] ?? 'Unknown Item',
                    'DESCRIPTION'     => $row['DESCRIPTION'] ?? '',
                    'ITEM_IMAGE'      => $row['ITEM_IMAGE'] ?? '',
                    'QUANTITY'        => (int)$row['QUANTITY'],
                    'PRICE_AT_ORDER'  => (float)$row['PRICE_AT_ORDER'],
                    'SPECIAL_REQUEST' => $row['SPECIAL_REQUEST'] ?? ''
                ];
            }
        } else {
            $error = oci_error($stmt);
            error_log("Database error in getOrderItems({$orderId}): " . $error['message']);
        }

        oci_free_statement($stmt);
        return $items;
    }

    private function validateStock($cartItems)
    {
        foreach ($cartItems as $item) {
            $currentStock = $this->getCurrentStock($item['ITEM_ID']);

            if ($currentStock === false) {
                throw new RuntimeException("Item not found: " . ($item['NAME'] ?? 'Unknown item'));
            }

            if ($currentStock < $item['QUANTITY']) {
                throw new RuntimeException(
                    "Insufficient stock for {$item['NAME']}. " .
                        "Available: {$currentStock}, Requested: {$item['QUANTITY']}"
                );
            }
        }
    }

    private function getCurrentStock($itemId)
    {
        $sql = "SELECT daily_stock, is_available FROM menu_items WHERE item_id = :item_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':item_id', $itemId);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        if (!$row) {
            return false;
        }

        if (!$row['IS_AVAILABLE']) {
            throw new RuntimeException("Item is currently unavailable");
        }

        return (int)$row['DAILY_STOCK'];
    }

    private function updateStock($cartItems)
    {
        foreach ($cartItems as $item) {
            $sql = "UPDATE menu_items 
                    SET daily_stock = daily_stock - :quantity,
                        updated_at = SYSTIMESTAMP
                    WHERE item_id = :item_id 
                    AND daily_stock >= :quantity";

            $stmt = oci_parse($this->conn, $sql);
            oci_bind_by_name($stmt, ':quantity', $item['QUANTITY']);
            oci_bind_by_name($stmt, ':item_id', $item['ITEM_ID']);

            $result = oci_execute($stmt, OCI_NO_AUTO_COMMIT);

            if (!$result) {
                $error = oci_error($stmt);
                throw new RuntimeException("Failed to update stock for item {$item['ITEM_ID']}: " . $error['message']);
            }

            $this->checkAndUpdateAvailability($item['ITEM_ID']);
        }
    }

    private function checkAndUpdateAvailability($itemId)
    {
        $sql = "UPDATE menu_items 
                SET is_available = CASE WHEN daily_stock <= 0 THEN 0 ELSE 1 END,
                    updated_at = SYSTIMESTAMP
                WHERE item_id = :item_id";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':item_id', $itemId);
        oci_execute($stmt, OCI_NO_AUTO_COMMIT);
    }

    private function insertOrderRecord($orderData)
    {
        $sql = "INSERT INTO orders (
            buyer_id, kitchen_id, delivery_area_id, 
            delivery_address, contact_phone, total_amount, 
            delivery_fee, status, estimated_delivery_time,
            created_at, updated_at
        ) VALUES (
            :buyer_id, :kitchen_id, :delivery_area_id, 
            :delivery_address, :contact_phone, :total_amount, 
            :delivery_fee, :status, :estimated_delivery_time,
            SYSTIMESTAMP, SYSTIMESTAMP
        ) RETURNING order_id INTO :order_id";

        $stmt = oci_parse($this->conn, $sql);

        $status = 'PENDING';

        $buyerId = $orderData['buyer_id'];
        $kitchenId = $orderData['kitchen_id'];
        $deliveryAreaId = $orderData['delivery_area_id'];
        $deliveryAddress = $orderData['delivery_address'];
        $contactPhone = $orderData['buyer_phone'] ?? '';
        $totalAmount = $orderData['subtotal'];
        $deliveryFee = $orderData['delivery_fee'];
        $estimatedTime = 45;

        oci_bind_by_name($stmt, ':buyer_id', $buyerId);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_bind_by_name($stmt, ':delivery_area_id', $deliveryAreaId);
        oci_bind_by_name($stmt, ':delivery_address', $deliveryAddress);
        oci_bind_by_name($stmt, ':contact_phone', $contactPhone);
        oci_bind_by_name($stmt, ':total_amount', $totalAmount);
        oci_bind_by_name($stmt, ':delivery_fee', $deliveryFee);
        oci_bind_by_name($stmt, ':status', $status);
        oci_bind_by_name($stmt, ':estimated_delivery_time', $estimatedTime);

        $orderId = 0;
        oci_bind_by_name($stmt, ':order_id', $orderId, -1, SQLT_INT);

        $result = oci_execute($stmt, OCI_NO_AUTO_COMMIT);

        if (!$result) {
            $error = oci_error($stmt);
            throw new RuntimeException("Database error: " . $error['message']);
        }

        return $orderId;
    }

    private function addOrderItems($orderId, $cartItems)
    {
        foreach ($cartItems as $item) {
            $sql = "INSERT INTO order_items (
                        order_id, item_id, quantity, price_at_order, special_request
                    ) VALUES (
                        :order_id, :item_id, :quantity, :price_at_order, :special_request
                    )";

            $stmt = oci_parse($this->conn, $sql);

            $priceAtOrder = $item['PRICE'];
            $specialRequest = $item['special_request'] ?? '';

            oci_bind_by_name($stmt, ':order_id', $orderId);
            oci_bind_by_name($stmt, ':item_id', $item['ITEM_ID']);
            oci_bind_by_name($stmt, ':quantity', $item['QUANTITY']);
            oci_bind_by_name($stmt, ':price_at_order', $priceAtOrder);
            oci_bind_by_name($stmt, ':special_request', $specialRequest);

            $result = oci_execute($stmt, OCI_NO_AUTO_COMMIT);

            if (!$result) {
                $error = oci_error($stmt);
                throw new RuntimeException("Failed to add order item: " . $error['message']);
            }
        }
    }

    public function cancelBuyerOrder(int $orderId, int $buyerId): bool
    {
        $sql =
            "UPDATE orders
            SET status = 'CANCELLED', cancel_by = 'BUYER'
            WHERE order_id = :order_id
            AND buyer_id = :buyer_id
            AND status IN ('PENDING')
            AND created_at >= (SYSDATE - (10 / 1440))";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':order_id', $orderId);
        oci_bind_by_name($stmt, ':buyer_id', $buyerId);

        $result = oci_execute($stmt, OCI_NO_AUTO_COMMIT);

        if ($result && oci_num_rows($stmt) > 0) {
            oci_commit($this->conn);
            return true;
        }

        oci_rollback($this->conn);
        return false;
    }

    public function hideBuyerOrder(int $orderId, int $buyerId): bool
    {
        $sql =
            "UPDATE orders
            SET buyer_delete = 1
            WHERE order_id = :order_id
            AND buyer_id = :buyer_id
            AND status = 'DELIVERED'";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':order_id', $orderId);
        oci_bind_by_name($stmt, ':buyer_id', $buyerId);

        $result = oci_execute($stmt, OCI_NO_AUTO_COMMIT);

        if ($result && oci_num_rows($stmt) > 0) {
            oci_commit($this->conn);
            return true;
        }

        oci_rollback($this->conn);
        return false;
    }

    public function clearOrderHistory(int $buyerId): bool
    {
        $sql =
            "UPDATE orders
            SET buyer_delete = 1
            WHERE buyer_id = :buyer_id
            AND status IN ('DELIVERED', 'CANCELLED')
            AND buyer_delete = 0";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':buyer_id', $buyerId);

        $result = oci_execute($stmt, OCI_NO_AUTO_COMMIT);

        if ($result && oci_num_rows($stmt) > 0) {
            oci_commit($this->conn);
            return true;
        }

        oci_rollback($this->conn);
        return false;
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

    public function hasUserOrderedFromKitchen($userId, $kitchenId)
    {
        $sql = "SELECT COUNT(*) as order_count
            FROM orders o
            INNER JOIN order_items oi ON o.order_id = oi.order_id
            INNER JOIN menu_items mi ON oi.item_id = mi.item_id
            WHERE o.buyer_id = :user_id
            AND mi.kitchen_id = :kitchen_id
            AND o.status = 'DELIVERED'";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':user_id', $userId);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return ($row['ORDER_COUNT'] ?? 0) > 0;
    }
}
