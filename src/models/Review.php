<?php
class Review
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
        $sql = "SELECT COUNT(*) FROM reviews";
        $stmt = oci_parse($this->conn, $sql);
        oci_execute($stmt);
        $row = oci_fetch_row($stmt);
        oci_free_statement($stmt);
        return $row[0] ?? 0;
    }

    public function getAllReviewsForAdmin($limit = 50, $offset = 0)
    {
        $limit = (int)$limit;
        $offset = (int)$offset;

        $sql = "SELECT 
                r.review_id,
                r.reviewer_id,
                r.reference_id,
                r.reference_type,
                r.rating,
                r.comments,
                r.review_date,
                r.status,
                r.hidden_by,
                r.hidden_at,
                r.hidden_reason,
                u.name as reviewer_name,
                u.email as reviewer_email,
                u.profile_image,
                hu.name as hidden_by_name,
                CASE 
                    WHEN r.reference_type = 'KITCHEN' THEN k.name
                    WHEN r.reference_type = 'ITEM' THEN mi.name
                    ELSE 'TiffinCraft Platform'
                END as reference_name,
                k.kitchen_id,
                k.name as kitchen_name,
                mi.item_id,
                mi.name as item_name
            FROM reviews r
            LEFT JOIN users u ON r.reviewer_id = u.user_id
            LEFT JOIN kitchens k ON r.reference_type = 'KITCHEN' AND r.reference_id = k.kitchen_id
            LEFT JOIN menu_items mi ON r.reference_type = 'ITEM' AND r.reference_id = mi.item_id
            LEFT JOIN users hu ON r.hidden_by = hu.user_id
            ORDER BY r.review_date DESC
            OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";

        $stmt = oci_parse($this->conn, $sql);

        if (!oci_execute($stmt)) {
            $error = oci_error($stmt);
            error_log("Oracle Error: " . $error['message']);
            return [];
        }

        $reviews = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $row = $this->processRow($row);
            $reviews[] = $row;
        }
        oci_free_statement($stmt);

        return $reviews;
    }

    public function getAdminReviewStats()
    {
        $sql = "SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as average_rating,
                COUNT(CASE WHEN status = 'PUBLIC' THEN 1 END) as public_reviews,
                COUNT(CASE WHEN status = 'REPORTED' THEN 1 END) as reported_reviews,
                COUNT(CASE WHEN status = 'HIDDEN' THEN 1 END) as hidden_reviews,
                COUNT(CASE WHEN reference_type = 'KITCHEN' THEN 1 END) as kitchen_reviews,
                COUNT(CASE WHEN reference_type = 'ITEM' THEN 1 END) as item_reviews,
                COUNT(CASE WHEN reference_type = 'TIFFINCRAFT' THEN 1 END) as platform_reviews,
                COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star,
                COUNT(CASE WHEN rating = 4 THEN 1 END) as four_star,
                COUNT(CASE WHEN rating = 3 THEN 1 END) as three_star,
                COUNT(CASE WHEN rating = 2 THEN 1 END) as two_star,
                COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star
            FROM reviews";

        $stmt = oci_parse($this->conn, $sql);
        oci_execute($stmt);
        $stats = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return [
            'total_reviews' => (int)($stats['TOTAL_REVIEWS'] ?? 0),
            'average_rating' => round($stats['AVERAGE_RATING'] ?? 0, 1),
            'public_reviews' => (int)($stats['PUBLIC_REVIEWS'] ?? 0),
            'reported_reviews' => (int)($stats['REPORTED_REVIEWS'] ?? 0),
            'hidden_reviews' => (int)($stats['HIDDEN_REVIEWS'] ?? 0),
            'kitchen_reviews' => (int)($stats['KITCHEN_REVIEWS'] ?? 0),
            'item_reviews' => (int)($stats['ITEM_REVIEWS'] ?? 0),
            'platform_reviews' => (int)($stats['PLATFORM_REVIEWS'] ?? 0),
            'five_star' => (int)($stats['FIVE_STAR'] ?? 0),
            'four_star' => (int)($stats['FOUR_STAR'] ?? 0),
            'three_star' => (int)($stats['THREE_STAR'] ?? 0),
            'two_star' => (int)($stats['TWO_STAR'] ?? 0),
            'one_star' => (int)($stats['ONE_STAR'] ?? 0)
        ];
    }

    public function getTiffinCraftReviews()
    {
        $sql =
            "SELECT 
            r.review_id,
            r.rating,
            r.comments,
            r.review_date,
            u.user_id as reviewer_id,
            u.name as reviewer_name,
            u.profile_image as reviewer_image
        FROM reviews r
        JOIN users u ON r.reviewer_id = u.user_id
        WHERE r.reference_type = 'TIFFINCRAFT' AND r.status = 'PUBLIC'
        ORDER BY r.review_date DESC";

        $stmt = oci_parse($this->conn, $sql);

        if (!oci_execute($stmt)) {
            $error = oci_error($stmt);
            error_log("Oracle Error: " . $error['message']);
            return [];
        }

        $reviews = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $row = $this->processRow($row);
            $reviews[] = $row;
        }
        oci_free_statement($stmt);

        return $reviews;
    }

    public function updateReviewStatus($reviewId, $status, $adminId = null, $reason = '')
    {
        $sql = "UPDATE reviews 
            SET status = :status, 
                updated_at = CURRENT_TIMESTAMP";

        $params = [':status' => $status, ':review_id' => $reviewId];

        if ($adminId && in_array($status, ['HIDDEN', 'REPORTED'])) {
            $sql .= ", hidden_by = :hidden_by, hidden_at = CURRENT_TIMESTAMP, hidden_reason = :hidden_reason";
            $params[':hidden_by'] = $adminId;
            $params[':hidden_reason'] = $reason;
        } elseif ($status === 'PUBLIC') {
            $sql .= ", hidden_by = NULL, hidden_at = NULL, hidden_reason = NULL";
        }

        $sql .= " WHERE review_id = :review_id";

        $stmt = oci_parse($this->conn, $sql);

        foreach ($params as $key => $value) {
            oci_bind_by_name($stmt, $key, $params[$key]);
        }

        $result = oci_execute($stmt);
        oci_free_statement($stmt);
        return $result;
    }

    public function getReviewById($reviewId)
    {
        $sql = "SELECT * FROM reviews WHERE review_id = :review_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':review_id', $reviewId);

        if (!oci_execute($stmt)) {
            return false;
        }

        $review = oci_fetch_assoc($stmt);
        $review = $this->processRow($review);

        oci_free_statement($stmt);

        return $review;
    }

    public function updateStatus($reviewId, $status)
    {
        $sql = "UPDATE reviews SET status = :status WHERE review_id = :review_id";

        $stmt = oci_parse($this->conn, $sql);

        oci_bind_by_name($stmt, ':status', $status);
        oci_bind_by_name($stmt, ':review_id', $reviewId);

        $result = oci_execute($stmt);
        oci_free_statement($stmt);
        return $result;
    }

    public function deleteReview($reviewId)
    {
        $sql = "DELETE FROM reviews WHERE review_id = :review_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':review_id', $reviewId);

        $result = oci_execute($stmt);
        oci_free_statement($stmt);
        return $result;
    }

    public function getAllReviewsForKitchen($kitchenId, $includeHidden = false)
    {
        $sql = "SELECT 
                    r.review_id,
                    r.rating,
                    r.comments,
                    r.review_date,
                    r.status,
                    r.reference_type,
                    r.reference_id,
                    r.hidden_reason,
                    r.hidden_at,
                    u.user_id as reviewer_id,
                    u.name as reviewer_name,
                    u.profile_image as reviewer_image,
                    mi.name as item_name,
                    mi.item_id,
                    k.name as kitchen_name,
                    hu.name as hidden_by_name
                FROM reviews r
                JOIN users u ON r.reviewer_id = u.user_id
                LEFT JOIN menu_items mi ON r.reference_id = mi.item_id AND r.reference_type = 'ITEM'
                LEFT JOIN kitchens k ON r.reference_id = k.kitchen_id AND r.reference_type = 'KITCHEN'
                LEFT JOIN users hu ON r.hidden_by = hu.user_id
                WHERE (r.reference_id = :kitchen_id AND r.reference_type = 'KITCHEN')
                   OR (mi.kitchen_id = :kitchen_id AND r.reference_type = 'ITEM')";

        if (!$includeHidden) {
            $sql .= " AND r.status = 'PUBLIC'";
        }

        $sql .= " ORDER BY r.review_date DESC";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);

        if (!oci_execute($stmt)) {
            return [];
        }

        $reviews = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $reviews[] = $this->processRow($row);
        }
        oci_free_statement($stmt);
        return $reviews;
    }

    public function getReviewStatsForKitchen($kitchenId)
    {
        $sql = "SELECT 
                    COUNT(*) as total_reviews,
                    AVG(rating) as average_rating,
                    COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star,
                    COUNT(CASE WHEN rating = 4 THEN 1 END) as four_star,
                    COUNT(CASE WHEN rating = 3 THEN 1 END) as three_star,
                    COUNT(CASE WHEN rating = 2 THEN 1 END) as two_star,
                    COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star,
                    COUNT(CASE WHEN status = 'HIDDEN' THEN 1 END) as hidden_reviews,
                    COUNT(CASE WHEN status = 'REPORTED' THEN 1 END) as reported_reviews,
                    COUNT(CASE WHEN reference_type = 'KITCHEN' THEN 1 END) as kitchen_reviews,
                    COUNT(CASE WHEN reference_type = 'ITEM' THEN 1 END) as item_reviews
                FROM reviews r
                LEFT JOIN menu_items mi ON r.reference_id = mi.item_id AND r.reference_type = 'ITEM'
                WHERE (r.reference_id = :kitchen_id AND r.reference_type = 'KITCHEN')
                   OR (mi.kitchen_id = :kitchen_id AND r.reference_type = 'ITEM')";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_execute($stmt);

        $stats = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return [
            'total_reviews' => (int)($stats['TOTAL_REVIEWS'] ?? 0),
            'average_rating' => round($stats['AVERAGE_RATING'] ?? 0, 1),
            'five_star' => (int)($stats['FIVE_STAR'] ?? 0),
            'four_star' => (int)($stats['FOUR_STAR'] ?? 0),
            'three_star' => (int)($stats['THREE_STAR'] ?? 0),
            'two_star' => (int)($stats['TWO_STAR'] ?? 0),
            'one_star' => (int)($stats['ONE_STAR'] ?? 0),
            'hidden_reviews' => (int)($stats['HIDDEN_REVIEWS'] ?? 0),
            'reported_reviews' => (int)($stats['REPORTED_REVIEWS'] ?? 0),
            'kitchen_reviews' => (int)($stats['KITCHEN_REVIEWS'] ?? 0),
            'item_reviews' => (int)($stats['ITEM_REVIEWS'] ?? 0)
        ];
    }

    public function getKitchenReviews($kitchenId, $includeHidden = false)
    {
        $sql = "SELECT 
                    r.review_id,
                    r.rating,
                    r.comments,
                    r.review_date,
                    r.status,
                    r.hidden_reason,
                    r.hidden_at,
                    u.user_id as reviewer_id,
                    u.name as reviewer_name,
                    u.profile_image as reviewer_image,
                    hu.name as hidden_by_name
                FROM reviews r
                JOIN users u ON r.reviewer_id = u.user_id
                LEFT JOIN users hu ON r.hidden_by = hu.user_id
                WHERE r.reference_id = :kitchen_id 
                AND r.reference_type = 'KITCHEN'";

        if (!$includeHidden) {
            $sql .= " AND r.status = 'PUBLIC'";
        }

        $sql .= " ORDER BY r.review_date DESC";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);

        if (!oci_execute($stmt)) {
            return [];
        }

        $reviews = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $reviews[] = $this->processRow($row);
        }
        oci_free_statement($stmt);
        return $reviews;
    }

    public function getKitchenItemReviews($kitchenId, $includeHidden = false)
    {
        $sql = "SELECT 
                    r.review_id,
                    r.rating,
                    r.comments,
                    r.review_date,
                    r.status,
                    r.hidden_reason,
                    r.hidden_at,
                    r.reference_id as item_id,
                    u.user_id as reviewer_id,
                    u.name as reviewer_name,
                    u.profile_image as reviewer_image,
                    mi.name as item_name,
                    mi.item_image,
                    hu.name as hidden_by_name
                FROM reviews r
                JOIN users u ON r.reviewer_id = u.user_id
                JOIN menu_items mi ON r.reference_id = mi.item_id
                LEFT JOIN users hu ON r.hidden_by = hu.user_id
                WHERE mi.kitchen_id = :kitchen_id 
                AND r.reference_type = 'ITEM'";

        if (!$includeHidden) {
            $sql .= " AND r.status = 'PUBLIC'";
        }

        $sql .= " ORDER BY r.review_date DESC";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);

        if (!oci_execute($stmt)) {
            return [];
        }

        $reviews = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $reviews[] = $this->processRow($row);
        }
        oci_free_statement($stmt);
        return $reviews;
    }

    public function reportReview($reviewId, $sellerId, $reason = '')
    {
        $sql = "UPDATE reviews 
                SET status = 'REPORTED', 
                    hidden_by = :hidden_by, 
                    hidden_at = CURRENT_TIMESTAMP,
                    hidden_reason = :reason,
                    updated_at = CURRENT_TIMESTAMP
                WHERE review_id = :review_id";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':review_id', $reviewId);
        oci_bind_by_name($stmt, ':hidden_by', $sellerId);
        oci_bind_by_name($stmt, ':reason', $reason);

        $result = oci_execute($stmt);
        oci_free_statement($stmt);
        return $result;
    }

    public function getUserReviewForItem($userId, $itemId)
    {
        $sql = "SELECT * FROM REVIEWS 
            WHERE REVIEWER_ID = :user_id 
            AND REFERENCE_TYPE = 'ITEM' 
            AND REFERENCE_ID = :item_id
            AND STATUS = 'PUBLIC'";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':user_id', $userId);
        oci_bind_by_name($stmt, ':item_id', $itemId);

        if (!oci_execute($stmt)) {
            error_log("Oracle Error in getUserReviewForItem: " . oci_error($stmt)['message']);
            return null;
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? $this->processRow($row) : null;
    }

    public function getItemReviews($itemId)
    {
        $sql = "SELECT r.review_id, r.reviewer_id, r.rating, r.comments,
                   TO_CHAR(r.created_at, 'YYYY-MM-DD HH24:MI:SS') as created_at,
                   u.name as reviewer_name,
                   u.profile_image as reviewer_image
            FROM reviews r
            JOIN users u ON r.reviewer_id = u.user_id
            WHERE r.reference_id = :item_id 
            AND r.reference_type = 'ITEM'
            AND r.status = 'PUBLIC'
            ORDER BY r.created_at DESC";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':item_id', $itemId);
        oci_execute($stmt);

        $reviews = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $reviews[] = [
                'review_id' => $row['REVIEW_ID'],
                'reviewer_id' => $row['REVIEWER_ID'],
                'reviewer_name' => $row['REVIEWER_NAME'],
                'reviewer_image' => $row['REVIEWER_IMAGE'] ?? null,
                'rating' => $row['RATING'],
                'comments' => $row['COMMENTS'],
                'created_at' => $row['CREATED_AT'],
                'formatted_date' => date('F j, Y', strtotime($row['CREATED_AT']))
            ];
        }

        oci_free_statement($stmt);
        return $reviews;
    }

    public function getItemReviewStats($itemId)
    {
        $sql = "SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as avg_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
            FROM reviews 
            WHERE reference_id = :item_id 
            AND reference_type = 'ITEM'
            AND status = 'PUBLIC'";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':item_id', $itemId);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return [
            'total' => (int)($row['TOTAL_REVIEWS'] ?? 0),
            'average' => round((float)($row['AVG_RATING'] ?? 0), 1),
            'breakdown' => [
                5 => (int)($row['FIVE_STAR'] ?? 0),
                4 => (int)($row['FOUR_STAR'] ?? 0),
                3 => (int)($row['THREE_STAR'] ?? 0),
                2 => (int)($row['TWO_STAR'] ?? 0),
                1 => (int)($row['ONE_STAR'] ?? 0)
            ]
        ];
    }

    public function createReview($data)
    {
        $comments = $data['comments'] ?? '';

        $reviewerId = $data['reviewer_id'];
        $referenceId = $data['reference_id'] ?? null;
        $referenceType = $data['reference_type'];
        $rating = $data['rating'];
        $status = $data['status'];

        $sql = "INSERT INTO reviews (
            reviewer_id, reference_id, reference_type, 
            rating, comments, status, created_at, updated_at
        ) VALUES (
            :reviewer_id, :reference_id, :reference_type,
            :rating, EMPTY_CLOB(), :status, SYSTIMESTAMP, SYSTIMESTAMP
        ) RETURNING comments INTO :comments";

        $stmt = oci_parse($this->conn, $sql);

        oci_bind_by_name($stmt, ':reviewer_id', $reviewerId);
        oci_bind_by_name($stmt, ':reference_id', $referenceId); // $referenceId can be null
        oci_bind_by_name($stmt, ':reference_type', $referenceType);
        oci_bind_by_name($stmt, ':rating', $rating);
        oci_bind_by_name($stmt, ':status', $status);

        $clob = oci_new_descriptor($this->conn, OCI_D_LOB);
        oci_bind_by_name($stmt, ':comments', $clob, -1, OCI_B_CLOB);

        if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            error_log("Oracle Error in createReview: " . ($error['message'] ?? 'Unknown error'));
            oci_free_statement($stmt);
            $clob->free();
            throw new Exception("Failed to create review");
        }

        if (!empty($comments)) {
            $clob->save($comments);
        }

        oci_commit($this->conn);

        oci_free_statement($stmt);
        $clob->free();
        return true;
    }

    public function updateReview($reviewId, $data)
    {
        $comments = $data['comments'] ?? '';

        $sql = "UPDATE reviews 
            SET rating = :rating,
                comments = EMPTY_CLOB(),
                updated_at = SYSTIMESTAMP
            WHERE review_id = :review_id
            RETURNING comments INTO :comments";

        $stmt = oci_parse($this->conn, $sql);

        oci_bind_by_name($stmt, ':rating', $data['rating']);
        oci_bind_by_name($stmt, ':review_id', $reviewId);

        $clob = oci_new_descriptor($this->conn, OCI_D_LOB);
        oci_bind_by_name($stmt, ':comments', $clob, -1, OCI_B_CLOB);

        oci_execute($stmt, OCI_NO_AUTO_COMMIT);
        $clob->save($comments);
        oci_commit($this->conn);

        oci_free_statement($stmt);
        $clob->free();
        return true;
    }

    public function getKitchenReviewStats($kitchenId)
    {
        $sql = "SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as avg_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
            FROM reviews 
            WHERE reference_id = :kitchen_id 
            AND reference_type = 'KITCHEN'
            AND status = 'PUBLIC'";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return [
            'total' => (int)($row['TOTAL_REVIEWS'] ?? 0),
            'average' => round((float)($row['AVG_RATING'] ?? 0), 1),
            'breakdown' => [
                5 => (int)($row['FIVE_STAR'] ?? 0),
                4 => (int)($row['FOUR_STAR'] ?? 0),
                3 => (int)($row['THREE_STAR'] ?? 0),
                2 => (int)($row['TWO_STAR'] ?? 0),
                1 => (int)($row['ONE_STAR'] ?? 0)
            ]
        ];
    }

    public function getUserReviewForKitchen($userId, $kitchenId)
    {
        $sql = "SELECT * FROM REVIEWS 
            WHERE REVIEWER_ID = :user_id 
            AND REFERENCE_TYPE = 'KITCHEN' 
            AND REFERENCE_ID = :kitchen_id
            AND STATUS = 'PUBLIC'";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':user_id', $userId);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);

        if (!oci_execute($stmt)) {
            error_log("Oracle Error in getUserReviewForKitchen: " . oci_error($stmt)['message']);
            return null;
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        if (!$row) {
            return null;
        }

        $row = $this->processRow($row);

        return [
            'REVIEW_ID' => $row['REVIEW_ID'],
            'RATING' => (int)$row['RATING'],
            'COMMENTS' => $row['COMMENTS'],
            'CREATED_AT' => $row['CREATED_AT']
        ];
    }

    public function getTiffinCraftReviewsWithPagination($limit = 6, $offset = 0)
    {
        $sql = "SELECT 
                r.review_id,
                r.rating,
                r.comments,
                TO_CHAR(r.created_at, 'YYYY-MM-DD HH24:MI:SS') as review_date,
                u.user_id as reviewer_id,
                u.name as reviewer_name,
                u.profile_image as reviewer_image,
                COUNT(*) OVER() as total_count
            FROM reviews r
            JOIN users u ON r.reviewer_id = u.user_id
            WHERE r.reference_type = 'TIFFINCRAFT' 
            AND r.status = 'PUBLIC'
            ORDER BY r.created_at DESC
            OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':offset', $offset);
        oci_bind_by_name($stmt, ':limit', $limit);

        if (!oci_execute($stmt)) {
            $error = oci_error($stmt);
            error_log("Oracle Error in getTiffinCraftReviewsWithPagination: " . $error['message']);
            return ['reviews' => [], 'total' => 0];
        }

        $reviews = [];
        $totalCount = 0;

        while ($row = oci_fetch_assoc($stmt)) {
            $row = $this->processRow($row);
            $reviews[] = [
                'REVIEW_ID' => $row['REVIEW_ID'],
                'RATING' => (int)$row['RATING'],
                'COMMENTS' => $row['COMMENTS'] ?? '',
                'REVIEW_DATE' => $row['REVIEW_DATE'],
                'REVIEWER_ID' => $row['REVIEWER_ID'],
                'REVIEWER_NAME' => $row['REVIEWER_NAME'],
                'REVIEWER_IMAGE' => $row['REVIEWER_IMAGE'] ?? null,
                // 'FORMATTED_DATE' => date('F j, Y', strtotime($row['REVIEW_DATE']))
            ];
            $totalCount = (int)($row['TOTAL_COUNT'] ?? 0);
        }

        oci_free_statement($stmt);

        return [
            'reviews' => $reviews,
            'total' => $totalCount
        ];
    }

    public function getTiffinCraftStats()
    {
        $sql = "SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as average_rating,
                COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star,
                COUNT(CASE WHEN rating = 4 THEN 1 END) as four_star,
                COUNT(CASE WHEN rating = 3 THEN 1 END) as three_star,
                COUNT(CASE WHEN rating = 2 THEN 1 END) as two_star,
                COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star
            FROM reviews 
            WHERE reference_type = 'TIFFINCRAFT'
            AND status = 'PUBLIC'";

        $stmt = oci_parse($this->conn, $sql);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        $row = $this->processRow($row);

        return [
            'total_reviews' => (int)($row['TOTAL_REVIEWS'] ?? 0),
            'average_rating' => round((float)($row['AVERAGE_RATING'] ?? 0), 1),
            5 => (int)($row['FIVE_STAR'] ?? 0),
            4 => (int)($row['FOUR_STAR'] ?? 0),
            3 => (int)($row['THREE_STAR'] ?? 0),
            2 => (int)($row['TWO_STAR'] ?? 0),
            1 => (int)($row['ONE_STAR'] ?? 0)
        ];
    }

    public function hasUserReviewedTiffinCraft($userId)
    {
        $sql = "SELECT COUNT(*) as count 
            FROM reviews 
            WHERE reviewer_id = :user_id 
            AND reference_type = 'TIFFINCRAFT'
            AND status = 'PUBLIC'";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':user_id', $userId);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return ($row['COUNT'] ?? 0) > 0;
    }
}
