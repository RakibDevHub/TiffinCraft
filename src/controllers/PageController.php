<?php

class PageController
{
    private $conn;
    private $orderModel;
    private $menuModel;
    private $categoryModel;
    private $kitchenModel;
    private $reviewModel;
    private $favoriteModel;
    private $serviceAreaModel;
    private $subscriptionModel;

    public function __construct()
    {
        $this->conn = Database::getConnection();
        $this->orderModel = new Order($this->conn);
        $this->menuModel = new Menu($this->conn);
        $this->categoryModel = new Category($this->conn);
        $this->kitchenModel = new Kitchen($this->conn);
        $this->reviewModel = new Review($this->conn);
        $this->favoriteModel = new Favorite($this->conn);
        $this->serviceAreaModel = new ServiceArea($this->conn);
        $this->subscriptionModel = new Subscription($this->conn);
    }

    public function home()
    {
        $categories = $this->categoryModel->getFeaturedCategories();
        $featuredKitchens = $this->kitchenModel->getFeaturedKitchens(10);
        $platform_reviews = $this->reviewModel->getTiffinCraftReviews();
        $reviewStats = $this->reviewModel->getTiffinCraftStats();

        $kitchenFavorites = [];
        $hasReviewed = false;

        if (AuthHelper::isLoggedIn()) {
            $userId = Session::get('user_id');
            foreach ($featuredKitchens['kitchens'] as $kitchen) {
                $kitchenFavorites[$kitchen['KITCHEN_ID']] = $this->favoriteModel->isItemInFavorites(
                    $userId,
                    $kitchen['KITCHEN_ID'],
                    'KITCHEN'
                );
            }

            $hasReviewed = $this->reviewModel->hasUserReviewedTiffinCraft($userId);
        }

        CSRF::generateToken();

        return [
            'title' => 'Home',
            'page' => 'home',
            'categories' => $categories,
            'featuredKitchens' => $featuredKitchens['kitchens'],
            'hasPublicRatings' => $featuredKitchens['hasPublicRatings'],
            'hasAnyRatings' => $featuredKitchens['hasAnyRatings'],
            'isLoggedIn' => AuthHelper::isLoggedIn(),
            'platform_reviews' => $platform_reviews,
            'reviewStats' => $reviewStats,
            'hasReviewed' => $hasReviewed,
            'kitchenFavorites' => $kitchenFavorites,
            'viewFile' => BASE_PATH . '/src/views/pages/common/home.php',
        ];
    }

    public function dishes()
    {
        if (isset($_GET['view']) && $_GET['view'] === 'item' && isset($_GET['id'])) {
            return $this->showItemDetails($_GET['id']);
        }

        $filters = [
            'category' => isset($_GET['category']) ? urldecode(trim($_GET['category'])) : null,
            'search' => isset($_GET['search']) ? strtolower(trim($_GET['search'])) : null,
            'location' => isset($_GET['location']) ? urldecode(trim($_GET['location'])) : null,
            'price' => isset($_GET['price']) ? $_GET['price'] : null,
        ];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 12;
        $offset = ($page - 1) * $limit;

        try {
            $menuItems = $this->menuModel->getFilteredDishes($filters, $limit, $offset);
            $totalItems = $this->menuModel->countFilteredDishes($filters);
            $totalPages = max(1, ceil($totalItems / $limit));

            if ($page > $totalPages && $totalPages > 0) {
                $page = $totalPages;
                $offset = ($page - 1) * $limit;
                $menuItems = $this->menuModel->getFilteredDishes($filters, $limit, $offset);
            }
        } catch (Exception $e) {
            error_log("DishesController error: " . $e->getMessage());
            $menuItems = [];
            $totalItems = 0;
            $totalPages = 1;
        }

        $categories = $this->categoryModel->getFeaturedCategories(20);
        $locations = $this->serviceAreaModel->getAllActiveAreas();

        $itemFavorites = [];
        if (AuthHelper::isLoggedIn()) {
            $userId = Session::get('user_id');
            foreach ($menuItems as $item) {
                $itemFavorites[$item['ITEM_ID']] = $this->favoriteModel->isItemInFavorites(
                    $userId,
                    $item['ITEM_ID'],
                    'ITEM'
                );
            }
        }

        CSRF::generateToken();

        return [
            'title' => 'Delicious Dishes - TiffinCraft',
            'page' => 'dishes',
            'menuItems' => $menuItems,
            'categories' => $categories,
            'locations' => $locations,
            'totalItems' => $totalItems,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'selectedCategory' => $filters['category'],
            'searchTerm' => $filters['search'],
            'selectedLocation' => $filters['location'],
            'priceSort' => $filters['price'],
            'itemFavorites' => $itemFavorites,
            'isLoggedIn' => AuthHelper::isLoggedIn(),
            'viewFile' => BASE_PATH . '/src/views/pages/common/dishes.php',
        ];
    }

    public function kitchens()
    {
        if (isset($_GET['view']) && $_GET['view'] === 'kitchen' && isset($_GET['id'])) {
            return $this->showKitchenDetails($_GET['id']);
        }

        $filters = [
            'search' => isset($_GET['search']) ? strtolower(trim($_GET['search'])) : null,
            'location' => isset($_GET['location']) ? urldecode(trim($_GET['location'])) : null,
            'rating' => isset($_GET['rating']) ? $_GET['rating'] : null,
            'experience' => isset($_GET['experience']) ? $_GET['experience'] : null,
        ];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 12;
        $offset = ($page - 1) * $limit;

        try {
            $kitchens = $this->kitchenModel->getFilteredKitchens($filters, $limit, $offset);
            $totalItems = $this->kitchenModel->countFilteredKitchens($filters);
            $totalPages = max(1, ceil($totalItems / $limit));

            if ($page > $totalPages && $totalPages > 0) {
                $page = $totalPages;
                $offset = ($page - 1) * $limit;
                $kitchens = $this->kitchenModel->getFilteredKitchens($filters, $limit, $offset);
            }
        } catch (Exception $e) {
            error_log("KitchensController error: " . $e->getMessage());
            $kitchens = [];
            $totalItems = 0;
            $totalPages = 1;
        }

        $locations = $this->serviceAreaModel->getAllActiveAreas();

        $hasPublicRatings = false;
        $hasAnyRatings = false;
        foreach ($kitchens as $kitchen) {
            if ((int)$kitchen['REVIEW_COUNT'] > 0) {
                $hasAnyRatings = true;
            }
            if ((float)$kitchen['AVG_RATING'] > 0) {
                $hasPublicRatings = true;
            }
        }

        $kitchenFavorites = [];
        if (AuthHelper::isLoggedIn()) {
            $userId = Session::get('user_id');
            foreach ($kitchens as $kitchen) {
                $kitchenFavorites[$kitchen['KITCHEN_ID']] = $this->favoriteModel->isItemInFavorites(
                    $userId,
                    $kitchen['KITCHEN_ID'],
                    'KITCHEN'
                );
            }
        }

        CSRF::generateToken();

        return [
            'title' => 'Browse Kitchens - TiffinCraft',
            'page' => 'kitchens',
            'kitchens' => $kitchens,
            'locations' => $locations,
            'totalItems' => $totalItems,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'selectedLocation' => $filters['location'],
            'searchTerm' => $filters['search'],
            'ratingSort' => $filters['rating'],
            'experienceFilter' => $filters['experience'],
            'hasPublicRatings' => $hasPublicRatings,
            'hasAnyRatings' => $hasAnyRatings,
            'kitchenFavorites' => $kitchenFavorites,
            'isLoggedIn' => AuthHelper::isLoggedIn(),
            'viewFile' => BASE_PATH . '/src/views/pages/common/kitchens.php',
        ];
    }

    public function contact()
    {
        return [
            'title' => 'Contact',
            'page' => 'contact',
            'viewFile' => BASE_PATH . '/src/views/pages/common/contact.php',
        ];
    }

    public function submitTiffinCraftReview()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /");
            exit;
        }

        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid CSRF token.');
            header("Location: /#testimonials");
            exit;
        }

        if (!AuthHelper::isLoggedIn()) {
            Session::flash('error', 'Please login to submit a review.');
            header("Location: /login");
            exit;
        }

        $userId = Session::get('user_id');
        $rating = (int)($_POST['rating'] ?? 0);
        $comments = trim($_POST['comments'] ?? '');

        if ($rating < 1 || $rating > 5) {
            Session::flash('error', 'Please select a valid rating.');
            header("Location: /#testimonials");
            exit;
        }

        if (empty($comments)) {
            Session::flash('error', 'Please write your review comments.');
            header("Location: /#testimonials");
            exit;
        }

        if ($this->reviewModel->hasUserReviewedTiffinCraft($userId)) {
            Session::flash('error', 'You have already submitted a review.');
            header("Location: /#testimonials");
            exit;
        }

        try {
            $reviewData = [
                'reviewer_id' => $userId,
                'reference_id' => null,
                'reference_type' => 'TIFFINCRAFT',
                'rating' => $rating,
                'comments' => $comments,
                'status' => 'PUBLIC'
            ];

            $this->reviewModel->createReview($reviewData);
            Session::flash('success', 'Thank you for your review!');
        } catch (Exception $e) {
            error_log("TiffinCraft review error: " . $e->getMessage());
            Session::flash('error', 'Failed to submit review. Please try again.');
        }

        header("Location: /#testimonials");
        exit;
    }

    private function showItemDetails($itemId)
    {
        $item = $this->menuModel->getById($itemId);
        if (!$item) {
            header("HTTP/1.0 404 Not Found");
            return [
                'title' => 'Item Not Found - TiffinCraft',
                'page' => '404',
                'viewFile' => BASE_PATH . '/src/views/pages/common/error.php'
            ];
        }

        $reviews = $this->reviewModel->getItemReviews($itemId);
        $reviewStats = $this->reviewModel->getItemReviewStats($itemId);

        $userHasOrdered = false;
        $userReview = null;

        if (AuthHelper::isLoggedIn('buyer')) {
            $userId = Session::get('user_id');
            $userHasOrdered = $this->orderModel->hasUserOrderedItem($userId, $itemId);
            $userReview = $this->reviewModel->getUserReviewForItem($userId, $itemId);
        }

        $relatedItems = $this->menuModel->getRelatedItems($itemId, 4);

        $isFavorite = false;
        if (AuthHelper::isLoggedIn()) {
            $isFavorite = $this->favoriteModel->isItemInFavorites(
                Session::get('user_id'),
                $itemId,
                'ITEM'
            );
        }

        CSRF::generateToken();

        return [
            'title' => $item['NAME'] . ' - TiffinCraft',
            'page' => 'item-details',
            'item' => $item,
            'reviews' => $reviews,
            'reviewStats' => $reviewStats,
            'userHasOrdered' => $userHasOrdered,
            'userReview' => $userReview,
            'relatedItems' => $relatedItems,
            'isFavorite' => $isFavorite,
            'isLoggedIn' => AuthHelper::isLoggedIn(),
            'viewFile' => BASE_PATH . '/src/views/pages/common/item-details.php'
        ];
    }

    private function showKitchenDetails($kitchenId)
    {
        if (!$kitchenId) {
            header('Location: /kitchens');
            exit;
        }

        $kitchen = $this->kitchenModel->getKitchenById($kitchenId);

        if (!$kitchen) {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => 'Kitchen not found or is no longer available.'
            ];
            header('Location: /kitchens');
            exit;
        }

        $menuItems = $this->menuModel->getMenuItemsByKitchen($kitchenId);
        $reviews = $this->reviewModel->getKitchenReviews($kitchenId);
        $reviewStats = $this->reviewModel->getKitchenReviewStats($kitchenId);

        $userHasOrdered = false;
        $userReview = null;

        if (AuthHelper::isLoggedIn('buyer')) {
            $userId = Session::get('user_id');

            $userHasOrdered = $this->orderModel->hasUserOrderedFromKitchen($userId, $kitchenId);
            $userReview = $this->reviewModel->getUserReviewForKitchen($userId, $kitchenId);
        }

        $isFavorite = false;
        $itemFavorites = [];

        if (AuthHelper::isLoggedIn()) {
            $userId = Session::get('user_id');

            $isFavorite = $this->favoriteModel->isItemInFavorites(
                $userId,
                $kitchenId,
                'KITCHEN'
            );

            foreach ($menuItems as $item) {
                $itemFavorites[$item['ITEM_ID']] = $this->favoriteModel->isItemInFavorites(
                    $userId,
                    $item['ITEM_ID'],
                    'ITEM'
                );
            }
        }

        $csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $csrfToken;

        CSRF::generateToken();

        return [
            'title' => $kitchen['KITCHEN_NAME'] . ' - Menu | TiffinCraft',
            'page' => 'kitchen-menu',
            'kitchen' => $kitchen,
            'menuItems' => $menuItems,
            'reviews' => $reviews,
            'reviewStats' => $reviewStats,
            'avgRating' => $reviewStats['average'] ?? 0,
            'reviewCount' => $reviewStats['total'] ?? count($reviews),
            'userHasOrdered' => $userHasOrdered,
            'userReview' => $userReview,
            'isFavorite' => $isFavorite,
            'itemFavorites' => $itemFavorites,
            'isLoggedIn' => AuthHelper::isLoggedIn(),
            'viewFile' => BASE_PATH . '/src/views/pages/common/kitchen-menu.php',
        ];
    }


    public function business()
    {
        // $categories = $this->categoryModel->getFeaturedCategories();
        // $featuredKitchens = $this->kitchenModel->getFeaturedKitchens(10);
        // $platform_reviews = $this->reviewModel->getTiffinCraftReviews();
        // $reviewStats = $this->reviewModel->getTiffinCraftStats();
        $subscriptionPlans = $this->subscriptionModel->getAllActivePlans();

        $kitchenFavorites = [];
        $hasReviewed = false;

        // if (AuthHelper::isLoggedIn()) {
        //     $userId = Session::get('user_id');
        //     foreach ($featuredKitchens['kitchens'] as $kitchen) {
        //         $kitchenFavorites[$kitchen['KITCHEN_ID']] = $this->favoriteModel->isItemInFavorites(
        //             $userId,
        //             $kitchen['KITCHEN_ID'],
        //             'KITCHEN'
        //         );
        //     }

        //     $hasReviewed = $this->reviewModel->hasUserReviewedTiffinCraft($userId);
        // }

        CSRF::generateToken();

        return [
            'title' => 'Business',
            'page' => 'business',
            // 'categories' => $categories,
            // 'featuredKitchens' => $featuredKitchens['kitchens'],
            // 'hasPublicRatings' => $featuredKitchens['hasPublicRatings'],
            // 'hasAnyRatings' => $featuredKitchens['hasAnyRatings'],
            'isLoggedIn' => AuthHelper::isLoggedIn(),
            // 'platform_reviews' => $platform_reviews,
            // 'reviewStats' => $reviewStats,
            'hasReviewed' => $hasReviewed,
            'kitchenFavorites' => $kitchenFavorites,
            'subscriptionPlans' => $subscriptionPlans,
            'viewFile' => BASE_PATH . '/src/views/pages/common/business.php',
        ];
    }
}
