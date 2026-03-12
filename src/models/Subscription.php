<?php

class Subscription
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

    public function countAllPlans()
    {
        $sql = "SELECT COUNT(*) AS total FROM subscription_plans";
        $stmt = oci_parse($this->conn, $sql);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return (int)($row['TOTAL'] ?? 0);
    }

    public function getAllActivePlans()
    {
        $sql = "SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY monthly_fee ASC";
        $stmt = oci_parse($this->conn, $sql);

        if (!oci_execute($stmt)) {
            return [];
        }

        $plans = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $row = $this->processRow($row);
            $plans[] = $row;
        }

        oci_free_statement($stmt);

        return $plans;
    }

    public function getAllPlanDetails($limit, $offset)
    {
        $limit = (int)$limit;
        $offset = (int)$offset;

        $sql = "SELECT 
                    sp.plan_id,
                    sp.plan_name,
                    DBMS_LOB.SUBSTR(sp.description, 4000, 1) AS description,
                    sp.monthly_fee,
                    sp.commission_rate,
                    sp.max_items,
                    sp.is_active,
                    sp.is_highlight,
                    sp.created_at,
                    sp.updated_at,
                    COUNT(DISTINCT ss.seller_id) AS total_subscribers,
                    COUNT(DISTINCT CASE WHEN ss.status = 'ACTIVE' THEN ss.seller_id END) AS active_subscribers
                FROM subscription_plans sp
                LEFT JOIN seller_subscriptions ss ON sp.plan_id = ss.plan_id
                GROUP BY 
                    sp.plan_id, sp.plan_name, DBMS_LOB.SUBSTR(sp.description, 4000, 1),
                    sp.monthly_fee, sp.commission_rate, sp.max_items, sp.is_active, 
                    sp.is_highlight, sp.created_at, sp.updated_at
                ORDER BY sp.created_at DESC
                OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";

        $stmt = oci_parse($this->conn, $sql);

        if (!oci_execute($stmt)) {
            $error = oci_error($stmt);
            error_log("Oracle Error: " . $error['message']);
            return [];
        }

        $plans = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $row = $this->processRow($row);
            $plans[] = $row;
        }

        oci_free_statement($stmt);

        return $plans;
    }

    public function getPlanById($planId)
    {
        $sql = "SELECT * FROM subscription_plans WHERE plan_id = :plan_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':plan_id', $planId);

        if (!oci_execute($stmt)) {
            return false;
        }

        $plan = oci_fetch_assoc($stmt);

        if ($plan) {
            $plan = $this->processRow($plan);
        }
        oci_free_statement($stmt);

        return $plan;
    }

    public function createPlan($data)
    {
        $sql = "INSERT INTO subscription_plans (plan_name, description, monthly_fee, commission_rate, max_items, is_active, is_highlight) 
                VALUES (:plan_name, :description, :monthly_fee, :commission_rate, :max_items, :is_active, :is_highlight) 
                RETURNING plan_id INTO :plan_id";

        $stmt = oci_parse($this->conn, $sql);
        $planId = null;

        oci_bind_by_name($stmt, ':plan_name', $data['planName']);
        oci_bind_by_name($stmt, ':description', $data['description']);
        oci_bind_by_name($stmt, ':monthly_fee', $data['monthlyFee']);
        oci_bind_by_name($stmt, ':commission_rate', $data['commissionRate']);
        oci_bind_by_name($stmt, ':max_items', $data['maxItems']);
        oci_bind_by_name($stmt, ':is_active', $data['isActive']);
        oci_bind_by_name($stmt, ':is_highlight', $data['isHighlight']);
        oci_bind_by_name($stmt, ':plan_id', $planId, -1, SQLT_INT);

        if (oci_execute($stmt)) {
            return $planId;
        }

        return false;
    }

    public function updatePlan($data)
    {
        $sql = 
            "UPDATE subscription_plans 
            SET plan_name = :plan_name, 
                description = :description, 
                monthly_fee = :monthly_fee, 
                commission_rate = :commission_rate, 
                max_items = :max_items, 
                is_active = :is_active, 
                is_highlight = :is_highlight,
                updated_at = SYSTIMESTAMP
            WHERE plan_id = :plan_id";

        $stmt = oci_parse($this->conn, $sql);

        oci_bind_by_name($stmt, ':plan_name', $data['planName']);
        oci_bind_by_name($stmt, ':description', $data['description']);
        oci_bind_by_name($stmt, ':monthly_fee', $data['monthlyFee']);
        oci_bind_by_name($stmt, ':commission_rate', $data['commissionRate']);
        oci_bind_by_name($stmt, ':max_items', $data['maxItems']);
        oci_bind_by_name($stmt, ':is_active', $data['isActive']);
        oci_bind_by_name($stmt, ':is_highlight', $data['isHighlight']);
        oci_bind_by_name($stmt, ':plan_id', $data['planId']);

        return oci_execute($stmt);
    }

    public function deletePlanById($planId)
    {
        $checkSql = "SELECT COUNT(*) FROM seller_subscriptions WHERE plan_id = :plan_id AND status = 'ACTIVE'";
        $checkStmt = oci_parse($this->conn, $checkSql);
        oci_bind_by_name($checkStmt, ':plan_id', $planId);
        oci_execute($checkStmt);
        $activeSubscriptions = oci_fetch_row($checkStmt)[0];
        oci_free_statement($checkStmt);

        if ($activeSubscriptions > 0) {
            throw new Exception("Cannot delete plan with active subscriptions");
        }

        $sql = "DELETE FROM subscription_plans WHERE plan_id = :plan_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':plan_id', $planId);

        return oci_execute($stmt);
    }

    public function getSubById($sellerId)
    {
        $sql = "SELECT * FROM seller_subscriptions WHERE seller_id = :seller_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':seller_id', $sellerId);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        $row = $this->processRow($row);
        oci_free_statement($stmt);
        return $row ?: null;
    }

    public function getSubHistory($sellerId)
    {
        $sql =
            "SELECT 
                sp.plan_id, sp.plan_name, sp.description, sp.max_items, 
                sp.monthly_fee, sp.commission_rate,
                ss.subscription_id, ss.created_at, ss.start_date, ss.end_date, ss.status, ss.change_type, ss.updated_at,
                pt.payment_method, pt.amount AS payment_amount, pt.status as payment_status, pt.transaction_id, pt.metadata, pt.created_at as payment_date
            FROM seller_subscriptions ss
            JOIN subscription_plans sp ON ss.plan_id = sp.plan_id
            LEFT JOIN payment_transactions pt 
                ON ss.subscription_id = pt.reference_id 
                AND pt.reference_type = 'SUBSCRIPTION'
            WHERE ss.seller_id = :seller_id 
            ORDER BY ss.created_at DESC";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':seller_id', $sellerId);

        if (!oci_execute($stmt)) {
            return false;
        }

        $subscription = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $row = $this->processRow($row);
            $subscription[] = $row;
        }

        oci_free_statement($stmt);

        return $subscription;
    }

    public function createSub($data)
    {
        $sql = "INSERT INTO seller_subscriptions (
                seller_id, plan_id, start_date, end_date, status, change_type
            ) VALUES (
                :seller_id, :plan_id,
                TO_DATE(:start_date, 'YYYY-MM-DD HH24:MI:SS'),
                TO_DATE(:end_date, 'YYYY-MM-DD HH24:MI:SS'),
                :status, :change_type
            ) RETURNING subscription_id INTO :subscription_id";

        $data['start_date'] = date('Y-m-d H:i:s');
        $data['end_date']   = date('Y-m-d H:i:s', strtotime('+1 month'));

        $stmt = oci_parse($this->conn, $sql);
        $subscriptionId = null;

        oci_bind_by_name($stmt, ':seller_id', $data['seller_id']);
        oci_bind_by_name($stmt, ':plan_id', $data['plan_id']);
        oci_bind_by_name($stmt, ':start_date', $data['start_date']);
        oci_bind_by_name($stmt, ':end_date', $data['end_date']);
        oci_bind_by_name($stmt, ':status', $data['status']);
        oci_bind_by_name($stmt, ':change_type', $data['change_type']);
        oci_bind_by_name($stmt, ':subscription_id', $subscriptionId, -1, SQLT_INT);

        if (oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            return $subscriptionId;
        }

        return false;
    }

    public function updateSubscriptionStatus($subId, $status, $autoCommit = false)
    {
        $updateSql = "UPDATE seller_subscriptions SET status = :status, updated_at = SYSTIMESTAMP WHERE subscription_id = :subscription_id";
        $updateStmt = oci_parse($this->conn, $updateSql);
        oci_bind_by_name($updateStmt, ':status', $status);
        oci_bind_by_name($updateStmt, ':subscription_id', $subId);

        $exec = oci_execute($updateStmt, $autoCommit ? OCI_COMMIT_ON_SUCCESS : OCI_NO_AUTO_COMMIT);

        if (!$exec) {
            $e = oci_error($updateStmt);
            throw new RuntimeException("Failed to update subscription: " . $e['message']);
        }

        return true;
    }
}
