<?php

class Analytics
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

    public function getSalesOverview($kitchenId, $period = 'month')
    {
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'DELIVERED' THEN 1 ELSE 0 END) as completed_orders,
                    SUM(CASE WHEN status = 'CANCELLED' THEN 1 ELSE 0 END) as cancelled_orders,
                    SUM(CASE WHEN status = 'DELIVERED' THEN (total_amount + delivery_fee) ELSE 0 END) as total_revenue,
                    AVG(
                        CASE 
                            WHEN status = 'DELIVERED' AND actual_delivery_time IS NOT NULL 
                            THEN ROUND((EXTRACT(DAY FROM (actual_delivery_time - created_at)) * 24 * 60) + 
                                      (EXTRACT(HOUR FROM (actual_delivery_time - created_at)) * 60) + 
                                       EXTRACT(MINUTE FROM (actual_delivery_time - created_at)), 2)
                            WHEN status = 'DELIVERED' AND estimated_delivery_time IS NOT NULL
                            THEN estimated_delivery_time
                            ELSE NULL
                        END
                    ) as avg_delivery_time,
                    AVG(CASE WHEN status = 'DELIVERED' THEN (total_amount + delivery_fee) END) as avg_order_value
                FROM orders 
                WHERE kitchen_id = :kitchen_id";

        switch ($period) {
            case 'today':
                $sql .= " AND TRUNC(created_at) = TRUNC(SYSDATE)";
                break;
            case 'week':
                $sql .= " AND created_at >= TRUNC(SYSDATE) - 7";
                break;
            case 'month':
                $sql .= " AND created_at >= TRUNC(SYSDATE, 'MM')";
                break;
            case 'year':
                $sql .= " AND created_at >= TRUNC(SYSDATE, 'YEAR')";
                break;
        }

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        $result = $this->processRow($row);
        oci_free_statement($stmt);

        return $result ?: [
            'TOTAL_ORDERS' => 0,
            'COMPLETED_ORDERS' => 0,
            'CANCELLED_ORDERS' => 0,
            'TOTAL_REVENUE' => 0,
            'AVG_DELIVERY_TIME' => 0,
            'AVG_ORDER_VALUE' => 0
        ];
    }

    public function getRevenueTrend($kitchenId, $months = 6)
    {
        $sql =
            "WITH all_months AS (
                SELECT ADD_MONTHS(TRUNC(SYSDATE, 'MM'), -LEVEL + 1) AS month_start,
                       TO_CHAR(ADD_MONTHS(TRUNC(SYSDATE, 'MM'), -LEVEL + 1), 'Mon YYYY') AS month_name,
                       TO_CHAR(ADD_MONTHS(TRUNC(SYSDATE, 'MM'), -LEVEL + 1), 'YYYY-MM') AS month
                FROM dual
                CONNECT BY LEVEL <= :months
            )
            SELECT 
                am.month_name,
                am.month,
                COUNT(o.order_id) AS total_orders,
                SUM(CASE WHEN o.status = 'DELIVERED' THEN 1 ELSE 0 END) AS completed_orders,
                SUM(CASE WHEN o.status = 'DELIVERED' THEN (o.total_amount + o.delivery_fee) ELSE 0 END) AS revenue,
                AVG(
                    CASE 
                        WHEN o.status = 'DELIVERED' AND o.actual_delivery_time IS NOT NULL 
                        THEN ROUND((EXTRACT(DAY FROM (o.actual_delivery_time - o.created_at)) * 24 * 60) + 
                                  (EXTRACT(HOUR FROM (o.actual_delivery_time - o.created_at)) * 60) + 
                                   EXTRACT(MINUTE FROM (o.actual_delivery_time - o.created_at)), 2)
                        WHEN o.status = 'DELIVERED' AND o.estimated_delivery_time IS NOT NULL
                        THEN o.estimated_delivery_time
                        ELSE NULL
                    END
                ) AS avg_delivery_time
            FROM all_months am
            LEFT JOIN orders o ON TRUNC(o.created_at, 'MM') = am.month_start 
                AND o.kitchen_id = :kitchen_id
            GROUP BY am.month_name, am.month, am.month_start
            ORDER BY am.month_start DESC";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':months', $months);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_execute($stmt);

        $trends = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $trends[] = $this->processRow($row);
        }
        oci_free_statement($stmt);

        return $trends;
    }

    public function getPopularItems($kitchenId, $limit = 10)
    {
        $sql =
            "SELECT 
                mi.item_id,
                mi.name AS item_name,
                mi.price,
                COUNT(oi.order_item_id) AS total_orders,
                SUM(oi.quantity) AS total_quantity,
                SUM(oi.quantity * oi.price_at_order) AS total_revenue,
                AVG(
                    CASE 
                        WHEN o.status = 'DELIVERED' AND o.actual_delivery_time IS NOT NULL 
                        THEN ROUND((EXTRACT(DAY FROM (o.actual_delivery_time - o.created_at)) * 24 * 60) + 
                                  (EXTRACT(HOUR FROM (o.actual_delivery_time - o.created_at)) * 60) + 
                                   EXTRACT(MINUTE FROM (o.actual_delivery_time - o.created_at)), 2)
                        WHEN o.status = 'DELIVERED' AND o.estimated_delivery_time IS NOT NULL
                        THEN o.estimated_delivery_time
                        ELSE NULL
                    END
                ) AS avg_prep_time
            FROM menu_items mi
            LEFT JOIN order_items oi ON mi.item_id = oi.item_id
            LEFT JOIN orders o ON oi.order_id = o.order_id 
                AND o.status = 'DELIVERED'
            WHERE mi.kitchen_id = :kitchen_id
            GROUP BY mi.item_id, mi.name, mi.price
            ORDER BY total_quantity DESC, total_revenue DESC
            FETCH FIRST :limit ROWS ONLY";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_bind_by_name($stmt, ':limit', $limit);
        oci_execute($stmt);

        $items = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $items[] = $this->processRow($row);
        }
        oci_free_statement($stmt);

        return $items;
    }

    public function getBusyHours($kitchenId)
    {
        $sql =
            "SELECT 
                TO_CHAR(created_at, 'HH24') AS hour_of_day,
                COUNT(*) AS order_count,
                SUM(total_amount + delivery_fee) AS revenue
            FROM orders
            WHERE kitchen_id = :kitchen_id 
                AND status = 'DELIVERED'
            GROUP BY TO_CHAR(created_at, 'HH24')
            ORDER BY hour_of_day ASC";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_execute($stmt);

        $hours = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $hours[] = $this->processRow($row);
        }
        oci_free_statement($stmt);

        return $hours;
    }

    public function getServiceAreaPerformance($kitchenId)
    {
        $sql =
            "SELECT 
                sa.name AS area_name,
                COUNT(o.order_id) AS total_orders,
                SUM(CASE WHEN o.status = 'DELIVERED' THEN 1 ELSE 0 END) AS completed_orders,
                SUM(CASE WHEN o.status = 'DELIVERED' THEN (o.total_amount + o.delivery_fee) ELSE 0 END) AS revenue,
                AVG(
                    CASE 
                        WHEN o.status = 'DELIVERED' AND o.actual_delivery_time IS NOT NULL 
                        THEN ROUND((EXTRACT(DAY FROM (o.actual_delivery_time - o.created_at)) * 24 * 60) + 
                                  (EXTRACT(HOUR FROM (o.actual_delivery_time - o.created_at)) * 60) + 
                                   EXTRACT(MINUTE FROM (o.actual_delivery_time - o.created_at)), 2)
                        WHEN o.status = 'DELIVERED' AND o.estimated_delivery_time IS NOT NULL
                        THEN o.estimated_delivery_time
                        ELSE NULL
                    END
                ) AS avg_delivery_time
            FROM service_areas sa
            JOIN kitchen_service_zones ksz ON sa.area_id = ksz.area_id
            LEFT JOIN orders o ON sa.area_id = o.delivery_area_id 
                AND o.kitchen_id = :kitchen_id
            WHERE ksz.kitchen_id = :kitchen_id2
            GROUP BY sa.name, sa.area_id
            ORDER BY revenue DESC";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_bind_by_name($stmt, ':kitchen_id2', $kitchenId);
        oci_execute($stmt);

        $areas = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $areas[] = $this->processRow($row);
        }
        oci_free_statement($stmt);

        return $areas;
    }

    public function getCustomerAnalytics($kitchenId)
    {
        $sql =
            "SELECT 
                COUNT(DISTINCT buyer_id) AS total_customers,
                COUNT(DISTINCT CASE WHEN created_at >= TRUNC(SYSDATE) - 30 THEN buyer_id END) AS new_customers_30d,
                COUNT(DISTINCT CASE WHEN created_at >= TRUNC(SYSDATE) - 90 THEN buyer_id END) AS repeat_customers_90d,
                AVG(CASE WHEN status = 'DELIVERED' THEN (total_amount + delivery_fee) END) AS avg_customer_spend
            FROM orders
            WHERE kitchen_id = :kitchen_id";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        $result = $this->processRow($row);
        oci_free_statement($stmt);

        return $result ?: [
            'TOTAL_CUSTOMERS' => 0,
            'NEW_CUSTOMERS_30D' => 0,
            'REPEAT_CUSTOMERS_90D' => 0,
            'AVG_CUSTOMER_SPEND' => 0
        ];
    }

    public function getCancellationAnalytics($kitchenId, $months = 3)
    {
        $sql =
            "SELECT 
                TO_CHAR(TRUNC(created_at, 'MM'), 'Mon YYYY') AS month_name,
                TO_CHAR(TRUNC(created_at, 'MM'), 'YYYY-MM') AS month,
                COUNT(*) AS total_orders,
                SUM(CASE WHEN status = 'CANCELLED' THEN 1 ELSE 0 END) AS cancelled_orders,
                ROUND(
                    SUM(CASE WHEN status = 'CANCELLED' THEN 1 ELSE 0 END) / 
                    NULLIF(COUNT(*), 0) * 100, 2
                ) AS cancellation_rate
            FROM orders
            WHERE kitchen_id = :kitchen_id 
                AND created_at >= ADD_MONTHS(TRUNC(SYSDATE, 'MM'), -:months)
            GROUP BY TO_CHAR(TRUNC(created_at, 'MM'), 'Mon YYYY'), TO_CHAR(TRUNC(created_at, 'MM'), 'YYYY-MM'), TRUNC(created_at, 'MM')
            ORDER BY TRUNC(created_at, 'MM') DESC";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_bind_by_name($stmt, ':months', $months);
        oci_execute($stmt);

        $analytics = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $analytics[] = $this->processRow($row);
        }
        oci_free_statement($stmt);

        return $analytics;
    }

    public function getDailyPerformance($kitchenId, $days = 7)
    {
        $sql =
            "SELECT 
                TRUNC(created_at) AS date_day,
                TO_CHAR(created_at, 'Dy') AS day_name,
                TO_CHAR(TRUNC(created_at), 'YYYY-MM-DD') AS date_iso,
                COUNT(*) AS total_orders,
                SUM(CASE WHEN status = 'DELIVERED' THEN 1 ELSE 0 END) AS completed_orders,
                SUM(CASE WHEN status = 'DELIVERED' THEN (total_amount + delivery_fee) ELSE 0 END) AS revenue,
                AVG(
                    CASE 
                        WHEN status = 'DELIVERED' AND actual_delivery_time IS NOT NULL 
                        THEN ROUND((EXTRACT(DAY FROM (actual_delivery_time - created_at)) * 24 * 60) + 
                                  (EXTRACT(HOUR FROM (actual_delivery_time - created_at)) * 60) + 
                                   EXTRACT(MINUTE FROM (actual_delivery_time - created_at)), 2)
                        WHEN status = 'DELIVERED' AND estimated_delivery_time IS NOT NULL
                        THEN estimated_delivery_time
                        ELSE NULL
                    END
                ) AS avg_prep_time
            FROM orders
            WHERE kitchen_id = :kitchen_id 
                AND created_at >= TRUNC(SYSDATE) - :days
            GROUP BY TRUNC(created_at), TO_CHAR(created_at, 'Dy'), TO_CHAR(TRUNC(created_at), 'YYYY-MM-DD')
            ORDER BY date_day DESC";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_bind_by_name($stmt, ':days', $days);
        oci_execute($stmt);

        $performance = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $performance[] = $this->processRow($row);
        }
        oci_free_statement($stmt);

        return $performance;
    }
}
