<?php

class ReviewController
{
    private $conn;
    private $orderModel;
    private $reviewModel;
    private $menuModel;

    public function __construct()
    {
        $this->conn = Database::getConnection();
        $this->orderModel = new Order($this->conn);
        $this->reviewModel = new Review($this->conn);
        $this->menuModel = new Menu($this->conn);
    }

    public function handleItemReview()
    {

        if (!AuthHelper::isLoggedIn('buyer')) {
            Session::flash('error', 'You are not allowed to write reviews.');
            header("Location: /login");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /dishes");
            exit;
        }

        $userId = Session::get('user_id');
        $reviewId = isset($_POST['review_id']) && !empty($_POST['review_id']) ? (int)$_POST['review_id'] : 0;
        $referenceType = $_POST['reference_type'] ?? 'ITEM';
        $referenceId = (int)($_POST['reference_id'] ?? 0);
        $action = $_POST['action'] ?? ($reviewId > 0 ? 'edit' : 'create');

        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token. Please try again.');
            header("Location: /dishes?view=item&id=" . $referenceId);
            exit;
        }

        if (empty($_POST['rating'])) {
            Session::flash('error', 'Please select a rating.');
            header("Location: /dishes?view=item&id=" . $referenceId);
            exit;
        }

        if ($referenceId <= 0) {
            Session::flash('error', 'Invalid item reference.');
            header("Location: /dishes");
            exit;
        }

        $item = $this->menuModel->getById($referenceId);
        if (!$item) {
            Session::flash('error', 'Item not found.');
            header("Location: /dishes");
            exit;
        }

        if ($referenceType === 'ITEM') {
            $hasOrdered = $this->orderModel->hasUserOrderedItem($userId, $referenceId);
            if (!$hasOrdered) {
                Session::flash('error', 'You must order this item before writing a review.');
                header("Location: /dishes?view=item&id=" . $referenceId);
                exit;
            }
        }

        $reviewData = [
            'reviewer_id' => $userId,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'rating' => (int)$_POST['rating'],
            'comments' => trim($_POST['comments'] ?? ''),
            'status' => 'PUBLIC'
        ];

        try {
            if ($reviewId > 0) {
                $existingReview = $this->reviewModel->getReviewById($reviewId);

                if (!$existingReview) {
                    Session::flash('error', 'Review not found.');
                    header("Location: /dishes?view=item&id=" . $referenceId);
                    exit;
                }

                if ($existingReview['REVIEWER_ID'] != $userId) {
                    Session::flash('error', 'You are not authorized to edit this review.');
                    header("Location: /dishes?view=item&id=" . $referenceId);
                    exit;
                }

                $this->reviewModel->updateReview($reviewId, $reviewData);
                Session::flash('success', 'Review updated successfully!');
            } else {
                $existingReview = $this->reviewModel->getUserReviewForItem($userId, $referenceId);

                if ($existingReview) {
                    Session::flash('error', 'You have already reviewed this item.');
                    header("Location: /dishes?view=item&id=" . $referenceId);
                    exit;
                }

                $this->reviewModel->createReview($reviewData);
                Session::flash('success', 'Review submitted successfully! Thank you for your feedback.');
            }
        } catch (Exception $e) {
            error_log("Review save error: " . $e->getMessage());
            Session::flash('error', 'An error occurred while saving your review. Please try again.');
        }

        // Redirect back to dishes page
        header("Location: /dishes?view=item&id=" . $referenceId);
        exit;
    }

    public function handleKitchenReview()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /kitchens");
            exit;
        }

        $userId = Session::get('user_id');
        $reviewId = $_POST['review_id'] ?? 0;
        $referenceType = $_POST['reference_type'] ?? '';
        $referenceId = $_POST['reference_id'] ?? 0;

        if (!AuthHelper::isLoggedIn('buyer')) {
            Session::flash('error', 'You are not allowed to write reviews.');
            header("Location: /login");
            exit;
        }

        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid CSRF token.');
            header("Location: /kitchens?view=kitchen&id=" . $referenceId);
            exit;
        }


        if (empty($_POST['rating'])) {
            Session::flash('error', 'Please select a rating.');
            header("Location: /kitchens?view=kitchen&id=" . $referenceId);
            exit;
        }

        if ($referenceType !== 'KITCHEN' || !$referenceId) {
            Session::flash('error', 'Invalid review data.');
            header("Location: /kitchens");
            exit;
        }

        $hasOrdered = $this->orderModel->hasUserOrderedFromKitchen($userId, $referenceId);
        if (!$hasOrdered) {
            Session::flash('error', 'You must order from this kitchen before writing a review.');
            header("Location: /kitchens?view=kitchen&id=" . $referenceId);
            exit;
        }

        $reviewData = [
            'reviewer_id' => $userId,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'rating' => (int)$_POST['rating'],
            'comments' => trim($_POST['comments'] ?? ''),
            'status' => 'PUBLIC'
        ];

        try {
            if ($reviewId > 0) {
                $existingReview = $this->reviewModel->getReviewById($reviewId);

                if (!$existingReview || $existingReview['REVIEWER_ID'] != $userId) {
                    Session::flash('error', 'You are not authorized to edit this review.');
                    header("Location: /kitchens?view=kitchen&id=" . $referenceId);
                    exit;
                }

                $this->reviewModel->updateReview($reviewId, $reviewData);
                Session::flash('success', 'Your review has been updated successfully!');
            } else {
                $existingReview = $this->reviewModel->getUserReviewForKitchen($userId, $referenceId);

                if ($existingReview) {
                    Session::flash('error', 'You have already reviewed this kitchen.');
                    header("Location: /kitchens?view=kitchen&id=" . $referenceId);
                    exit;
                }

                $this->reviewModel->createReview($reviewData);
                Session::flash('success', 'Your review has been submitted successfully!');
            }
        } catch (Exception $e) {
            error_log("Kitchen review error: " . $e->getMessage());
            Session::flash('error', 'An error occurred. Please try again.');
        }

        header("Location: /kitchens?view=kitchen&id=" . $referenceId);
        exit;
    }
}
