<?php

class SellerController
{
    private $conn;
    private $authModel;
    private $menuModel;
    private $categoryModel;
    private $areaModel;
    private $orderModel;
    private $kitchenModel;
    private $withdrawModel;
    private $subscriptionModel;
    private $reviewModel;
    private $analyticsModel;

    public function __construct()
    {
        $this->conn = Database::getConnection();
        $this->authModel = new Auth($this->conn);
        $this->menuModel = new Menu($this->conn);
        $this->categoryModel = new Category($this->conn);
        $this->areaModel = new ServiceArea($this->conn);
        $this->orderModel = new Order($this->conn);
        $this->kitchenModel = new Kitchen($this->conn);
        $this->withdrawModel = new Withdraw($this->conn);
        $this->subscriptionModel = new Subscription($this->conn);
        $this->reviewModel = new Review($this->conn);
        $this->analyticsModel = new Analytics($this->conn);
    }

    private function checkSellerSetup()
    {
        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $sellerId = Session::get('user_id');

        $excludedPaths = [
            '/business/dashboard/kitchen-setup',
            '/business/dashboard/select-plan',
            '/business/dashboard/subscriptions',
            '/business/dashboard/subscription/payment',
        ];

        if (in_array($currentPath, $excludedPaths)) {
            return;
        }

        $kitchen = $this->kitchenModel->getByOwnerId($sellerId);

        if (!$kitchen) {
            header("Location: /business/dashboard/kitchen-setup");
            exit;
        }

        $subscriptionModel = new Subscription($this->conn);
        $subHistory = $subscriptionModel->getSubHistory($sellerId);

        $subscriptionModel = new Subscription($this->conn);
        $subHistory = $subscriptionModel->getSubHistory($sellerId);

        $hasActiveSub = false;

        if (!empty($subHistory)) {
            foreach ($subHistory as $sub) {
                if ($sub['STATUS'] === 'ACTIVE' && strtotime($sub['END_DATE']) >= time()) {
                    $hasActiveSub = true;
                    break;
                }
            }
        }

        if (empty($subHistory)) {
            header("Location: /business/dashboard/select-plan");
            exit;
        } else {
            if (!$hasActiveSub && $currentPath !== "/business/dashboard/subscriptions") {
                header("Location: /business/dashboard/subscriptions");
                exit;
            }

            if ($hasActiveSub && $currentPath === "/business/dashboard/select-plan") {
                header("Location: /business/dashboard");
                exit;
            }
        }
    }

    public function dashboard()
    {
        if (!AuthHelper::isLoggedIn('seller')) {
            header("Location: /login");
            exit;
        }

        $this->checkSellerSetup();

        $user = $this->authModel->getById(Session::get('user_id'));
        $kitchen = $this->kitchenModel->getKitchenWithStats($user['USER_ID']);
        $recentOrders = $this->orderModel->getRecentOrders($kitchen['KITCHEN_ID'], 5);
        $dashboardStats = $this->orderModel->getDashboardStats($kitchen['KITCHEN_ID']);
        $popularItems = $this->orderModel->getPopularItems($kitchen['KITCHEN_ID'], 5);
        $orderStats = $this->orderModel->getOrderStats($kitchen['KITCHEN_ID']);
        $serviceAreas = $this->areaModel->getKitchenServiceArea($kitchen['KITCHEN_ID']);
        $balance = $this->withdrawModel->getSellerBalance($user['USER_ID']);
        $subscriptionHistory = $this->subscriptionModel->getSubHistory($user['USER_ID']);
        $activeSubscription = null;

        foreach ($subscriptionHistory as $sub) {
            if ($sub['STATUS'] === 'ACTIVE' && strtotime($sub['END_DATE']) >= time()) {
                $activeSubscription = $sub;
                break;
            }
        }

        return [
            'title' => 'Dashboard',
            'page' => 'dashboard',
            'currentUser' => $user,
            'kitchen' => $kitchen,
            'subscription' => $activeSubscription,
            'recentOrders' => $recentOrders,
            'stats' => $dashboardStats,
            'totalOrders' => $dashboardStats['totalOrders'],
            'todayOrders' => $dashboardStats['todayOrders'],
            'canceledOrders' => $dashboardStats['canceledOrders'],
            'acceptedOrders' => $dashboardStats['acceptedOrders'],
            'readyOrders' => $dashboardStats['readyOrders'],
            'current_balance' => $balance,
            'todayRevenue' => $dashboardStats['todayRevenue'],
            'todayRevenueData' => $dashboardStats['todayRevenueData'],
            'weeklyRevenue' => $dashboardStats['weeklyRevenue'],
            'weeklyRevenueData' => $dashboardStats['weeklyRevenueData'] ?? [],
            'monthlyRevenue' => $dashboardStats['monthlyRevenue'],
            'monthlyRevenueData' => $dashboardStats['monthlyRevenueData'] ?? [],
            'totalRevenue' => $dashboardStats['totalRevenue'],
            'totalRevenueData' => $dashboardStats['totalRevenueData'] ?? [],
            'popularItems' => $popularItems,
            'orderStats' => $orderStats,
            'serviceAreas' => $serviceAreas,
            'viewFile' => BASE_PATH . '/src/views/pages/seller/dashboard.php'
        ];
    }

    public function kitchenSetup()
    {
        if (!AuthHelper::isLoggedIn('seller')) {
            header("Location: /login");
            exit;
        }

        $sellerId = Session::get('user_id');
        $kitchen = $this->kitchenModel->getByOwnerId($sellerId);

        if ($kitchen) {
            $subHistory = $this->subscriptionModel->getSubHistory($sellerId);

            if (empty($subHistory)) {
                header("Location: /business/dashboard/select-plan");
            } else {
                header("Location: /business/dashboard");
            }
            exit;
        }

        $user = $this->authModel->getById(Session::get('user_id'));
        $serviceAreas = $this->areaModel->getAllActiveAreas();
        CSRF::generateToken();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $token = $_POST['csrf_token'] ?? '';

            if (!CSRF::validateToken($token)) {
                Session::flash('error', "Invalid request. Please try again.");
                header("Location: /business/dashboard/kitchen-setup");
                exit;
            }

            if ($action === 'create_kitchen') {
                $this->createKitchen($_POST, $_FILES, $user['USER_ID']);
                header("Location: /business/dashboard/select-plan");
                exit;
            }
        }

        return [
            'title' => 'Kitchen Setup',
            'page' => 'kitchen-setup',
            'currentUser' => $user,
            'serviceAreas' => $serviceAreas,
            'viewFile' => BASE_PATH . '/src/views/pages/seller/kitchenSetup.php',
        ];
    }

    private function createKitchen($postData, $files, $ownerId)
    {
        $name = trim($postData['name'] ?? '');
        $description = trim($postData['description'] ?? '');
        $address = trim($postData['address'] ?? '');
        $googleMapsUrl = trim($postData['google_maps_url'] ?? '');
        $yearsExperience = (int)($postData['years_experience'] ?? 0);
        $signatureDish = trim($postData['signature_dish'] ?? '');
        $avgPrepTime = (int)($postData['avg_prep_time'] ?? 30);
        $selectedAreas = $postData['service_areas'] ?? [];

        if (empty($name) || empty($address)) {
            Session::flash('error', 'Kitchen name and address are required');
            return false;
        }

        if (empty($selectedAreas)) {
            Session::flash('error', 'Please select at least one service area');
            return false;
        }

        $coverImage = null;
        $newImageUploaded = false;

        if (!empty($files['cover_image']['name'])) {
            $uploadResult = ImageHelper::uploadImage($files['cover_image'], 'kitchen');
            if (!$uploadResult['success']) {
                Session::flash('error', $uploadResult['message']);
                return false;
            }
            $coverImage = $uploadResult['filename'];
            $newImageUploaded = true;
        }

        $kitchenId = $this->kitchenModel->create([
            'owner_id' => $ownerId,
            'name' => $name,
            'description' => $description,
            'cover_image' => $coverImage,
            'address' => $address,
            'google_maps_url' => $googleMapsUrl,
            'years_experience' => $yearsExperience,
            'signature_dish' => $signatureDish,
            'avg_prep_time' => $avgPrepTime,
            'service_areas' => $selectedAreas
        ]);

        if ($kitchenId) {
            Session::flash('success', 'Kitchen setup successfully! Now choose a subscription plan.');
            return true;
        } else {
            if ($newImageUploaded) {
                ImageHelper::deleteImage($coverImage, 'kitchen');
            }
            Session::flash('error', 'Failed to create kitchen. Please try again.');
            return false;
        }
    }

    public function planSelection()
    {
        if (!AuthHelper::isLoggedIn('seller')) {
            header("Location: /login");
            exit;
        }

        $sellerId = Session::get('user_id');

        $subHistory = $this->subscriptionModel->getSubHistory($sellerId);

        if (!empty($subHistory)) {
            header("Location: /business/dashboard/subscriptions");
            exit;
        }

        $user = $this->authModel->getById($sellerId);
        $kitchen = $this->kitchenModel->getByOwnerId($sellerId);
        $plans = $this->subscriptionModel->getAllActivePlans();

        CSRF::generateToken();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';

            if (!CSRF::validateToken($token)) {
                Session::flash('error', "Invalid request. Please try again.");
                header("Location: /business/dashboard/select-plan");
                exit;
            }

            $planId = $_POST['plan_id'] ?? '';
            if (empty($planId)) {
                Session::flash('error', 'Please select a subscription plan');
                header("Location: /business/dashboard/select-plan");
                exit;
            }

            $plan = $this->subscriptionModel->getPlanById($planId);
            if (!$plan) {
                Session::flash('error', 'Invalid subscription plan');
                header("Location: /business/dashboard/select-plan");
                exit;
            }

            Session::set('pending_subscription', [
                'plan_id' => $planId,
                'sub_type' => 'NEW',
                'amount' => $plan['MONTHLY_FEE']
            ]);

            header("Location: /business/dashboard/subscription/payment");
            exit;
        }

        return [
            'title' => 'Choose Subscription Plan',
            'page' => 'subscriptions',
            'currentUser' => $user,
            'kitchen' => $kitchen,
            'plans' => $plans,
            'viewFile' => BASE_PATH . '/src/views/pages/seller/subscriptionPlans.php',
        ];
    }

    public function manageSubscription()
    {
        if (!AuthHelper::isLoggedIn('seller')) {
            header("Location: /login");
            exit;
        }

        if (isset($_GET['export']) && $_GET['export'] === 'subscription_history') {
            $this->authModel = new Auth($this->conn);
            $user = $this->authModel->getById(Session::get('user_id'));
            $subscriptionModel = new Subscription($this->conn);
            $subscriptionHistory = $subscriptionModel->getSubHistory($user['USER_ID']);
            $this->exportSubscriptionHistory($subscriptionHistory);
            exit;
        }

        $this->checkSellerSetup();

        $user = $this->authModel->getById(Session::get('user_id'));
        $kitchen = $this->kitchenModel->getByOwnerId($user['USER_ID']);
        $subscriptionHistory = $this->subscriptionModel->getSubHistory($user['USER_ID']);
        $allPlans = $this->subscriptionModel->getAllActivePlans();

        $activeSubscription = null;
        $historySubscriptions = [];
        $lastSubscription = null;

        foreach ($subscriptionHistory as $sub) {
            $endDate = strtotime($sub['END_DATE']);
            $currentTime = time();

            if ($sub['STATUS'] === 'ACTIVE') {
                if ($endDate >= $currentTime) {
                    $activeSubscription = $sub;
                } else {
                    $this->subscriptionModel->updateSubscriptionStatus($sub['SUBSCRIPTION_ID'], 'EXPIRED', true);
                    $sub['STATUS'] = 'EXPIRED';
                    $historySubscriptions[] = $sub;
                }
            } else {
                $historySubscriptions[] = $sub;
            }
        }

        if (!$activeSubscription && !empty($historySubscriptions)) {
            $lastSubscription = $historySubscriptions[0];
        }

        CSRF::generateToken();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $token = $_POST['csrf_token'] ?? '';

            if (!CSRF::validateToken($token)) {
                Session::flash('error', "Invalid request. Please try again.");
                header("Location: /business/dashboard/subscriptions");
                exit;
            }

            if ($action === 'change_plan') {
                $this->handleSubscriptionPlanChange($_POST, $activeSubscription, $lastSubscription);
                header("Location: /business/dashboard/subscriptions");
                exit;
            }

            Session::flash('error', "Invalid action.");
            header("Location: /business/dashboard/subscriptions");
            exit;
        }

        return [
            'title' => 'Subscription Management',
            'page' => 'subscriptions',
            'currentUser' => $user,
            'kitchen' => $kitchen,
            'activeSubscription' => $activeSubscription,
            'subscriptionHistory' => $historySubscriptions,
            'lastSubscription' => $lastSubscription,
            'plans' => $allPlans,
            'viewFile' => BASE_PATH . '/src/views/pages/seller/subscriptions.php',
        ];
    }

    private function handleSubscriptionPlanChange($postData, $activeSubscription, $lastSubscription)
    {
        $planId = $postData['plan_id'] ?? '';

        if (empty($planId)) {
            Session::flash('error', 'Please select a subscription plan');
            return false;
        }

        $subscriptionModel = new Subscription($this->conn);
        $selectedPlan = $subscriptionModel->getPlanById($planId);

        if (!$selectedPlan) {
            Session::flash('error', 'Invalid subscription plan');
            return false;
        }

        $subType = $this->determineSubType($activeSubscription, $lastSubscription, $selectedPlan);

        if (!$subType) {
            return false;
        }

        $amountToPay = $this->calculatePaymentAmount($activeSubscription, $selectedPlan, $subType);

        if ($amountToPay === false) {
            Session::flash('error', 'Unable to calculate payment amount');
            return false;
        }

        Session::set('pending_subscription', [
            'plan_id' => $selectedPlan['PLAN_ID'],
            'sub_type' => $subType,
            'amount' => $amountToPay,
            'previous_plan' => $activeSubscription ? [
                'subscription_id' => $activeSubscription['SUBSCRIPTION_ID'],
                'plan_id' => $activeSubscription['PLAN_ID'],
                'monthly_fee' => $activeSubscription['MONTHLY_FEE'],
                'start_date' => $activeSubscription['START_DATE'],
                'end_date' => $activeSubscription['END_DATE'],
                'remaining_days' => max(0, ceil((strtotime($activeSubscription['END_DATE']) - time()) / 86400)),
                'status' => $activeSubscription['STATUS']
            ] : ($lastSubscription ? [
                'subscription_id' => $lastSubscription['SUBSCRIPTION_ID'],
                'plan_id' => $lastSubscription['PLAN_ID'],
                'monthly_fee' => $lastSubscription['MONTHLY_FEE'],
                'start_date' => $lastSubscription['START_DATE'],
                'end_date' => $lastSubscription['END_DATE'],
                'remaining_days' => 0,
                'status' => $lastSubscription['STATUS']
            ] : null)
        ]);

        header("Location: /business/dashboard/subscription/payment");
        exit;
    }

    private function determineSubType($activeSubscription, $lastSubscription, $selectedPlan)
    {
        $currentTime = time();

        if ($activeSubscription) {
            $endDate = strtotime($activeSubscription['END_DATE']);
            $isExpired = $endDate < $currentTime;

            $currentFee = (float)$activeSubscription['MONTHLY_FEE'];
            $selectedFee = (float)$selectedPlan['MONTHLY_FEE'];

            if ($selectedPlan['PLAN_ID'] == $activeSubscription['PLAN_ID']) {
                if (!$isExpired) {
                    Session::flash('error', "You are already subscribed to this plan.");
                    return false;
                }
                return 'RENEWAL';
            }

            if ($isExpired) {
                return $selectedFee > $currentFee ? 'UPGRADE' : 'DOWNGRADE';
            }

            if ($selectedFee > $currentFee) {
                return 'UPGRADE';
            }

            $endDateFormatted = date('M j, Y', $endDate);
            Session::flash('error', "You cannot downgrade your plan now. Please wait until your current plan ends on {$endDateFormatted}.");
            return false;
        }

        if ($lastSubscription) {
            $lastFee = (float)$lastSubscription['MONTHLY_FEE'];
            $selectedFee = (float)$selectedPlan['MONTHLY_FEE'];

            if ($selectedPlan['PLAN_ID'] == $lastSubscription['PLAN_ID']) {
                return 'RENEWAL';
            }

            return $selectedFee > $lastFee ? 'UPGRADE' : 'DOWNGRADE';
        }

        return 'NEW';
    }

    private function calculatePaymentAmount($activeSubscription, $selectedPlan, $subType)
    {
        $currentTime = time();
        $newFee = (float)$selectedPlan['MONTHLY_FEE'];

        if (!$activeSubscription || $subType === 'NEW') {
            return $newFee;
        }

        $startDate = strtotime($activeSubscription['START_DATE']);
        $endDate = strtotime($activeSubscription['END_DATE']);
        $currentFee = (float)$activeSubscription['MONTHLY_FEE'];

        $remainingDays = max(0, ceil(($endDate - $currentTime) / 86400));
        $totalDays = max(1, ($endDate - $startDate) / (60 * 60 * 24));;

        switch ($subType) {
            case 'RENEWAL':
                return $newFee;

            case 'UPGRADE':
                $proratedCredit = ($currentFee / $totalDays) * $remainingDays;
                return max(0, $newFee - $proratedCredit);

            case 'DOWNGRADE':
                return $newFee;

            default:
                return false;
        }
    }

    private function exportSubscriptionHistory($subscriptions)
    {
        if (empty($subscriptions)) {
            Session::flash('error', 'No subscription data available for export');
            header("Location: /business/dashboard/subscriptions");
            exit;
        }

        try {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="subscription_history_' . date('Y-m-d_H-i-s') . '.csv"');

            $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new Exception('Could not open output stream');
            }

            fputs($output, "\xEF\xBB\xBF");

            // CSV headers
            $headers = [
                'Plan Name',
                'Description',
                'Monthly Fee',
                'Commission Rate',
                'Max Items',
                'Max Areas',
                'Start Date',
                'End Date',
                'Status',
                'Change Type',
                'Created Date',
                'Updated Date',
                'Payment Method',
                'Payment Amount',
                'Transaction ID',
                'Payment Date'
            ];

            fputcsv($output, $headers);

            // Add data rows
            foreach ($subscriptions as $subscription) {
                $row = [
                    $subscription['PLAN_NAME'] ?? '',
                    $subscription['DESCRIPTION'] ?? '',
                    $subscription['MONTHLY_FEE'] ?? 0,
                    $subscription['COMMISSION_RATE'] ?? 0,
                    $subscription['MAX_ITEMS'] ?? 0,
                    $subscription['MAX_AREAS'] ?? 0,
                    $this->formatDateForExport($subscription['START_DATE'] ?? ''),
                    $this->formatDateForExport($subscription['END_DATE'] ?? ''),
                    $subscription['STATUS'] ?? '',
                    $subscription['CHANGE_TYPE'] ?? '',
                    $this->formatDateForExport($subscription['CREATED_AT'] ?? ''),
                    $this->formatDateForExport($subscription['UPDATED_AT'] ?? ''),
                    $subscription['PAYMENT_METHOD'] ?? '',
                    $subscription['PAYMENT_AMOUNT'] ?? 0,
                    $subscription['TRANSACTION_ID'] ?? '',
                    $this->formatDateForExport($subscription['PAYMENT_DATE'] ?? '')
                ];

                fputcsv($output, $row);
            }

            fclose($output);
            exit;
        } catch (Exception $e) {
            error_log('Export error: ' . $e->getMessage());
            Session::flash('error', 'Failed to generate export file');
            header("Location: /business/dashboard/subscriptions");
            exit;
        }
    }

    private function formatDateForExport($dateString)
    {
        if (empty($dateString) || $dateString === 'N/A') {
            return '';
        }

        $formats = [
            'd-M-y h.i.s.u A',
            'Y-m-d H:i:s',
            'd/m/Y H:i:s',
            'm/d/Y H:i:s'
        ];

        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $dateString);
            if ($date !== false) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        $timestamp = strtotime($dateString);
        if ($timestamp !== false) {
            return date('Y-m-d H:i:s', $timestamp);
        }

        return '';
    }

    public function manageMenu()
    {
        if (!AuthHelper::isLoggedIn('seller')) {
            header("Location: /login");
            exit;
        }

        $this->checkSellerSetup();

        $user = $this->authModel->getById(Session::get('user_id'));
        $kitchen = $this->kitchenModel->getByOwnerId($user['USER_ID']);
        $subscriptionHistory = $this->subscriptionModel->getSubHistory($user['USER_ID']);

        $activeSubscription = null;

        foreach ($subscriptionHistory as $sub) {
            if ($sub['STATUS'] === 'ACTIVE' && strtotime($sub['END_DATE']) >= time()) {
                $activeSubscription = $sub;
                break;
            }
        }

        $currentItemCount = $this->menuModel->countByKitchen($kitchen['KITCHEN_ID']);
        $maxItems = $activeSubscription ? $activeSubscription['MAX_ITEMS'] : 0;
        $canAddMore = $maxItems === 0 || $currentItemCount < $maxItems;

        $menuItems = $this->menuModel->getMenuItemsByKitchenId($kitchen['KITCHEN_ID'], true);

        $allCategories = $this->categoryModel->getAllCategories();

        CSRF::generateToken();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $token = $_POST['csrf_token'] ?? '';

            if (!CSRF::validateToken($token)) {
                Session::flash('error', "Invalid request. Please try again.");
                header("Location: /business/dashboard/menu-items");
                exit;
            }

            switch ($action) {
                case 'add_item':
                    $this->addMenuItem($_POST, $_FILES, $kitchen['KITCHEN_ID'], $maxItems, $currentItemCount);
                    break;
                case 'update_item':
                    $this->updateMenuItem($_POST, $_FILES, $kitchen['KITCHEN_ID']);
                    break;
                case 'toggle_availability':
                    $this->toggleMenuItemAvailability($_POST, $kitchen['KITCHEN_ID']);
                    break;
                case 'update_stock':
                    $this->updateMenuItemStock($_POST, $kitchen['KITCHEN_ID']);
                    break;
                case 'delete_item':
                    $this->deleteMenuItem($_POST, $kitchen['KITCHEN_ID']);
                    break;
                default:
                    Session::flash('error', "Invalid action.");
            }

            header("Location: /business/dashboard/menu-items");
            exit;
        }

        return [
            'title' => 'Menu Management',
            'page' => 'menu-items',
            'currentUser' => $user,
            'kitchen' => $kitchen,
            'menuItems' => $menuItems,
            'allCategories' => $allCategories,
            'currentItemCount' => $currentItemCount,
            'maxItems' => $maxItems,
            'canAddMore' => $canAddMore,
            'activeSubscription' => $activeSubscription,
            'viewFile' => BASE_PATH . '/src/views/pages/seller/menu-items.php',
        ];
    }

    private function addMenuItem($postData, $files, $kitchenId, $maxItems, $currentItemCount)
    {
        if ($maxItems > 0 && $currentItemCount >= $maxItems) {
            Session::flash('error', "You have reached the maximum limit of {$maxItems} menu items for your subscription plan.");
            return false;
        }

        $name = trim($postData['name'] ?? '');
        $description = trim($postData['description'] ?? '');
        $portionSize = trim($postData['portion_size'] ?? '');
        $price = (float)($postData['price'] ?? 0);
        $spiceLevel = (int)($postData['spice_level'] ?? 1);
        $categoryIds = $postData['category_ids'] ?? [];

        $dailyStock = (int)($postData['daily_stock'] ?? 10);
        $isAvailable = isset($postData['is_available']) ? 1 : 0;

        if (empty($name) || empty($price)) {
            Session::flash('error', 'Name and price are required fields.');
            return false;
        }

        if ($price <= 0) {
            Session::flash('error', 'Price must be greater than 0.');
            return false;
        }

        if ($spiceLevel < 1 || $spiceLevel > 3) {
            Session::flash('error', 'Spice level must be between 1 and 3.');
            return false;
        }

        if ($dailyStock === 0 && $isAvailable === 1) {
            $isAvailable = 0;
        }

        $itemImage = null;
        $newImageUploaded = false;

        if (!empty($files['item_image']['name'])) {
            $uploadResult = ImageHelper::uploadImage($files['item_image'], 'menu');
            if (!$uploadResult['success']) {
                Session::flash('error', $uploadResult['message']);
                return false;
            }
            $itemImage = $uploadResult['filename'];
            $newImageUploaded = true;
        }

        $itemData = [
            'kitchen_id' => $kitchenId,
            'name' => $name,
            'description' => $description,
            'portion_size' => $portionSize,
            'price' => $price,
            'spice_level' => $spiceLevel,
            'daily_stock' => $dailyStock,
            'is_available' => $isAvailable,
            'item_image' => $itemImage,
            'category_ids' => $categoryIds
        ];

        $itemId = $this->menuModel->create($itemData);

        if ($itemId) {
            Session::flash('success', 'Menu item added successfully!');
            return true;
        } else {
            if ($newImageUploaded) {
                ImageHelper::deleteImage($itemImage, 'menu');
            }
            Session::flash('error', 'Failed to add menu item. Please try again.');
            return false;
        }
    }

    private function updateMenuItem($postData, $files, $kitchenId)
    {
        $itemId = $postData['item_id'] ?? '';
        $name = trim($postData['name'] ?? '');
        $description = trim($postData['description'] ?? '');
        $portionSize = trim($postData['portion_size'] ?? '');
        $price = (float)($postData['price'] ?? 0);
        $spiceLevel = (int)($postData['spice_level'] ?? 1);
        $categoryIds = $postData['category_ids'] ?? [];

        $dailyStock = (int)($postData['daily_stock'] ?? 10);
        $isAvailable = isset($postData['is_available']) ? 1 : 0;

        if (empty($itemId) || empty($name) || empty($price)) {
            Session::flash('error', 'Required fields are missing.');
            return false;
        }

        if ($price <= 0) {
            Session::flash('error', 'Price must be greater than 0.');
            return false;
        }

        if ($spiceLevel < 1 || $spiceLevel > 3) {
            Session::flash('error', 'Spice level must be between 1 and 3.');
            return false;
        }

        $existingItem = $this->menuModel->getByKitchenAndId($kitchenId, $itemId);
        if (!$existingItem) {
            Session::flash('error', 'Menu item not found or you do not have permission to edit it.');
            return false;
        }

        if ($dailyStock === 0 && $isAvailable === 1) {
            $isAvailable = 0;
        }

        $itemImage = $existingItem['ITEM_IMAGE'];
        $newImageUploaded = false;

        if (!empty($files['item_image']['name'])) {
            $uploadResult = ImageHelper::uploadImage($files['item_image'], 'menu');
            if (!$uploadResult['success']) {
                Session::flash('error', $uploadResult['message']);
                return false;
            }
            $itemImage = $uploadResult['filename'];
            $newImageUploaded = true;

            if ($existingItem['ITEM_IMAGE']) {
                ImageHelper::deleteImage($existingItem['ITEM_IMAGE'], 'menu');
            }
        }

        $itemData = [
            'name' => $name,
            'description' => $description,
            'portion_size' => $portionSize,
            'price' => $price,
            'spice_level' => $spiceLevel,
            'daily_stock' => $dailyStock,
            'is_available' => $isAvailable,
            'item_image' => $itemImage,
            'category_ids' => $categoryIds
        ];

        $result = $this->menuModel->update($itemId, $itemData);

        if ($result) {
            Session::flash('success', 'Menu item updated successfully!');
            return true;
        } else {
            if ($newImageUploaded) {
                ImageHelper::deleteImage($itemImage, 'menu');
            }
            Session::flash('error', 'Failed to update menu item. Please try again.');
            return false;
        }
    }

    private function updateMenuItemStock($postData, $kitchenId)
    {
        $itemId = $postData['item_id'] ?? '';
        $dailyStock = (int)($postData['daily_stock'] ?? 0);

        if (empty($itemId)) {
            Session::flash('error', 'Item ID is required.');
            return false;
        }

        if ($dailyStock < 0) {
            Session::flash('error', 'Daily stock cannot be negative.');
            return false;
        }

        $existingItem = $this->menuModel->getByKitchenAndId($kitchenId, $itemId);
        if (!$existingItem) {
            Session::flash('error', 'Menu item not found or you do not have permission to edit it.');
            return false;
        }

        $isAvailable = $dailyStock > 0 ? 1 : 0;

        $result = $this->menuModel->updateStockAndAvailability($itemId, $dailyStock, $isAvailable);

        if ($result) {
            if ($dailyStock === 0) {
                Session::flash('success', "Daily stock updated to {$dailyStock}! Item has been automatically marked as unavailable.");
            } else {
                Session::flash('success', "Daily stock updated to {$dailyStock}!");
            }
            return true;
        }

        Session::flash('error', 'Failed to update daily stock.');
        return false;
    }

    private function toggleMenuItemAvailability($postData, $kitchenId)
    {
        $itemId = $postData['item_id'] ?? '';
        $isAvailable = (int)($postData['is_available'] ?? 0);

        if (empty($itemId)) {
            Session::flash('error', 'Item ID is required.');
            return false;
        }

        $existingItem = $this->menuModel->getByKitchenAndId($kitchenId, $itemId);
        if (!$existingItem) {
            Session::flash('error', 'Menu item not found or you do not have permission to edit it.');
            return false;
        }

        $currentStock = $this->menuModel->getItemStock($itemId);

        if ($isAvailable === 1 && $currentStock === 0) {
            Session::flash('error', 'Cannot make item available when stock is zero. Please update stock first.');
            return false;
        }

        $result = $this->menuModel->updateStockAndAvailability(
            $itemId,
            $currentStock,
            $isAvailable
        );

        if ($result) {
            $status = $isAvailable ? 'available' : 'unavailable';
            Session::flash('success', "Menu item marked as {$status}!");
            return true;
        }

        Session::flash('error', 'Failed to update menu item availability.');
        return false;
    }

    private function deleteMenuItem($postData, $kitchenId)
    {
        $itemId = $postData['item_id'] ?? '';

        if (empty($itemId)) {
            Session::flash('error', 'Item ID is required.');
            return false;
        }

        $existingItem = $this->menuModel->getByKitchenAndId($kitchenId, $itemId);
        if (!$existingItem) {
            Session::flash('error', 'Menu item not found or you do not have permission to delete it.');
            return false;
        }

        if ($existingItem['IMAGE_URL']) {
            ImageHelper::deleteImage($existingItem['IMAGE_URL'], 'menu');
        }

        $result = $this->menuModel->delete($itemId);

        if ($result) {
            Session::flash('success', 'Menu item deleted successfully!');
            return true;
        }

        Session::flash('error', 'Failed to delete menu item. Please try again.');
        return false;
    }

    public function manageOrders()
    {
        if (!AuthHelper::isLoggedIn('seller')) {
            header("Location: /login");
            exit;
        }

        $this->checkSellerSetup();

        $user = $this->authModel->getById(Session::get('user_id'));
        $kitchen = $this->kitchenModel->getByOwnerId($user['USER_ID']);

        $orders = $this->orderModel->getOrdersByKitchenId($kitchen['KITCHEN_ID']);
        $dashboardStats = $this->orderModel->getDashboardStats($kitchen['KITCHEN_ID']);
        $todayOrderStats = $this->orderModel->getTodayOrderStats($kitchen['KITCHEN_ID']);
        $activeOrdersCount = $this->orderModel->getActiveOrdersCount($kitchen['KITCHEN_ID']);

        CSRF::generateToken();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $token = $_POST['csrf_token'] ?? '';

            if (!CSRF::validateToken($token)) {
                Session::flash('error', "Invalid request. Please try again.");
                header("Location: /business/dashboard/orders");
                exit;
            }

            if ($action === 'update_order_status') {
                $orderId = $_POST['order_id'] ?? '';
                $status = $_POST['status'] ?? '';
                $reason = $_POST['reason'] ?? '';

                if ($orderId && $status) {
                    $success = $this->orderModel->updateOrderStatus([
                        'kitchenId' => $kitchen['KITCHEN_ID'],
                        'orderId' => $orderId,
                        'status' => $status,
                        'reason' => $reason
                    ]);

                    if ($success) {
                        $statusMessages = [
                            'ACCEPTED' => 'Order accepted successfully.',
                            'READY' => 'Order marked as ready for delivery.',
                            'DELIVERED' => 'Order marked as delivered.',
                            'CANCELLED' => 'Order cancelled successfully.',
                        ];

                        Session::flash('success', $statusMessages[$status] ?? "Order status updated successfully.");
                    } else {
                        Session::flash('error', "Failed to update order status.");
                    }
                }
            }

            header("Location: /business/dashboard/orders");
            exit;
        }

        return [
            'title' => 'Order Management',
            'page' => 'orders',
            'currentUser' => $user,
            'kitchen' => $kitchen,
            'orders' => $orders,
            'todayOrderStats' => $todayOrderStats,
            'todayOrders' => $dashboardStats['todayOrders'],
            'activeOrdersCount' => $activeOrdersCount,
            'viewFile' => BASE_PATH . '/src/views/pages/seller/orders.php',
        ];
    }

    public function manageServiceAreas()
    {
        if (!AuthHelper::isLoggedIn('seller')) {
            header("Location: /login");
            exit;
        }

        $this->checkSellerSetup();

        $user = $this->authModel->getById(Session::get('user_id'));
        $kitchen = $this->kitchenModel->getByOwnerId($user['USER_ID']);
        $serviceAreas = $this->areaModel->getKitchenServiceArea($kitchen['KITCHEN_ID']);
        $availableAreas = $this->areaModel->getAllActiveAreas();

        CSRF::generateToken();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $token = $_POST['csrf_token'] ?? '';

            if (!CSRF::validateToken($token)) {
                Session::flash('error', "Invalid request. Please try again.");
                header("Location: /business/dashboard/service-areas");
                exit;
            }

            switch ($action) {
                case 'add_area':
                    $areaId = $_POST['area_id'] ?? '';
                    $deliveryFee = $_POST['delivery_fee'] ?? 0;
                    $minOrder = $_POST['min_order'] ?? 0;

                    if ($areaId) {
                        $success = $this->areaModel->addKitchenServiceArea([
                            'kitchen_id' => $kitchen['KITCHEN_ID'],
                            'area_id' => $areaId,
                            'delivery_fee' => $deliveryFee,
                            'min_order' => $minOrder
                        ]);

                        if ($success) {
                            Session::flash('success', "Service area added successfully.");
                        } else {
                            Session::flash('error', "Failed to add service area. It might already exist.");
                        }
                    }
                    break;

                case 'update_area':
                    $areaId = $_POST['area_id'] ?? '';
                    $deliveryFee = $_POST['delivery_fee'] ?? 0;
                    $minOrder = $_POST['min_order'] ?? 0;

                    if ($areaId) {
                        $success = $this->areaModel->updateKitchenServiceArea([
                            'kitchen_id' => $kitchen['KITCHEN_ID'],
                            'area_id' => $areaId,
                            'delivery_fee' => $deliveryFee,
                            'min_order' => $minOrder
                        ]);

                        if ($success) {
                            Session::flash('success', "Service area updated successfully.");
                        } else {
                            Session::flash('error', "Failed to update service area.");
                        }
                    }
                    break;

                case 'remove_area':
                    $areaId = $_POST['area_id'] ?? '';

                    if ($areaId) {
                        $success = $this->areaModel->removeKitchenServiceArea([
                            'kitchen_id' => $kitchen['KITCHEN_ID'],
                            'area_id' => $areaId
                        ]);

                        if ($success) {
                            Session::flash('success', "Service area removed successfully.");
                        } else {
                            Session::flash('error', "Failed to remove service area.");
                        }
                    }
                    break;
            }

            header("Location: /business/dashboard/service-areas");
            exit;
        }

        return [
            'title' => 'Manage Service Areas',
            'page' => 'service-areas',
            'currentUser' => $user,
            'kitchen' => $kitchen,
            'serviceAreas' => $serviceAreas,
            'availableAreas' => $availableAreas,
            'viewFile' => BASE_PATH . '/src/views/pages/seller/service-areas.php'
        ];
    }

    public function manageReviews()
    {
        if (!AuthHelper::isLoggedIn('seller')) {
            header("Location: /login");
            exit;
        }

        $this->checkSellerSetup();

        $user = $this->authModel->getById(Session::get('user_id'));
        $kitchen = $this->kitchenModel->getByOwnerId($user['USER_ID']);

        $allReviews = $this->reviewModel->getAllReviewsForKitchen($kitchen['KITCHEN_ID'], true);
        $reviewStats = $this->reviewModel->getReviewStatsForKitchen($kitchen['KITCHEN_ID']);

        CSRF::generateToken();

        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $token = $_POST['csrf_token'] ?? '';

            if (!CSRF::validateToken($token)) {
                Session::flash('error', "Invalid request. Please try again.");
                header("Location: /business/dashboard/reviews");
                exit;
            }

            switch ($action) {
                case 'report_review':
                    $reviewId = $_POST['review_id'] ?? '';
                    $reason = $_POST['reason'] ?? '';

                    if ($reviewId) {
                        $success = $this->reviewModel->reportReview($reviewId, $user['USER_ID'], $reason);
                        if ($success) {
                            Session::flash('success', "Review reported to admin successfully.");
                        } else {
                            Session::flash('error', "Failed to report review.");
                        }
                    }
                    break;

                default:
                    Session::flash('error', "Invalid action.");
            }

            header("Location: /business/dashboard/reviews");
            exit;
        }

        return [
            'title' => 'Review Management',
            'page' => 'reviews',
            'currentUser' => $user,
            'kitchen' => $kitchen,
            'allReviews' => $allReviews,
            'reviewStats' => $reviewStats,
            'viewFile' => BASE_PATH . '/src/views/pages/seller/reviews.php'
        ];
    }

    public function manageWithdrawals()
    {
        if (!AuthHelper::isLoggedIn('seller')) {
            header("Location: /login");
            exit;
        }

        $this->checkSellerSetup();

        $user = $this->authModel->getById(Session::get('user_id'));
        $balanceInfo = $this->withdrawModel->getSellerBalance($user['USER_ID']);
        $withdrawalHistory = $this->withdrawModel->getWithdrawalHistory($user['USER_ID']);
        $hasPendingWithdrawals = $this->withdrawModel->hasPendingWithdrawals($user['USER_ID']);

        CSRF::generateToken();

        // Handle withdrawal request
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $token = $_POST['csrf_token'] ?? '';

            if (!CSRF::validateToken($token)) {
                Session::flash('error', "Invalid request. Please try again.");
                header("Location: /business/dashboard/withdrawals");
                exit;
            }

            if ($action === 'request_withdrawal') {
                $amount = (float)($_POST['amount'] ?? 0);
                $method = $_POST['method'] ?? '';
                $accountDetails = $_POST['account_details'] ?? '';

                // Validation
                if ($amount <= 0) {
                    Session::flash('error', "Please enter a valid amount.");
                } elseif ($amount > ($balanceInfo['CURRENT_BALANCE'] ?? 0)) {
                    Session::flash('error', "Withdrawal amount exceeds your available balance.");
                } elseif (empty($method)) {
                    Session::flash('error', "Please select a withdrawal method.");
                } elseif (empty($accountDetails)) {
                    Session::flash('error', "Please provide account details.");
                } elseif ($hasPendingWithdrawals) {
                    Session::flash('error', "You already have pending withdrawals. Please wait for them to be processed.");
                } else {
                    $withdrawId = $this->withdrawModel->createWithdrawalRequest($user['USER_ID'], $amount, $method, $accountDetails);

                    if ($withdrawId) {
                        Session::flash('success', "Withdrawal request submitted successfully! It will be processed within 3-5 business days.");
                    } else {
                        Session::flash('error', "Failed to submit withdrawal request. Please try again.");
                    }
                }
            }

            header("Location: /business/dashboard/withdrawals");
            exit;
        }

        return [
            'title' => 'Withdrawal Management',
            'page' => 'withdrawals',
            'currentUser' => $user,
            'balanceInfo' => $balanceInfo,
            'withdrawalHistory' => $withdrawalHistory,
            'hasPendingWithdrawals' => $hasPendingWithdrawals,
            'viewFile' => BASE_PATH . '/src/views/pages/seller/withdrawals.php',
        ];
    }

    public function manageAnalytics()
    {
        if (!AuthHelper::isLoggedIn('seller')) {
            header("Location: /login");
            exit;
        }

        $this->checkSellerSetup();

        $user = $this->authModel->getById(Session::get('user_id'));
        $kitchen = $this->kitchenModel->getByOwnerId($user['USER_ID']);

        $period = $_GET['period'] ?? 'month';
        $months = $_GET['months'] ?? 6;
        $months = max(1, min(12, (int)$months));

        // Get all analytics data
        $salesOverview = $this->analyticsModel->getSalesOverview($kitchen['KITCHEN_ID'], $period);
        $revenueTrend = $this->analyticsModel->getRevenueTrend($kitchen['KITCHEN_ID'], $months);
        $popularItems = $this->analyticsModel->getPopularItems($kitchen['KITCHEN_ID'], 10);
        $busyHours = $this->analyticsModel->getBusyHours($kitchen['KITCHEN_ID']);
        $areaPerformance = $this->analyticsModel->getServiceAreaPerformance($kitchen['KITCHEN_ID']);
        $customerAnalytics = $this->analyticsModel->getCustomerAnalytics($kitchen['KITCHEN_ID']);
        $cancellationAnalytics = $this->analyticsModel->getCancellationAnalytics($kitchen['KITCHEN_ID'], 3);
        $dailyPerformance = $this->analyticsModel->getDailyPerformance($kitchen['KITCHEN_ID'], 7);

        return [
            'title' => 'Analytics Dashboard',
            'page' => 'analytics',
            'currentUser' => $user,
            'kitchen' => $kitchen,
            'period' => $period,
            'months' => $months,
            'salesOverview' => $salesOverview,
            'revenueTrend' => $revenueTrend,
            'popularItems' => $popularItems,
            'busyHours' => $busyHours,
            'areaPerformance' => $areaPerformance,
            'customerAnalytics' => $customerAnalytics,
            'cancellationAnalytics' => $cancellationAnalytics,
            'dailyPerformance' => $dailyPerformance,
            'viewFile' => BASE_PATH . '/src/views/pages/seller/analytics.php'
        ];
    }

    public function accountSettings()
    {
        if (!AuthHelper::isLoggedIn('seller')) {
            header("Location: /login");
            exit;
        }

        $userId = Session::get('user_id');
        $user = $this->authModel->getById($userId);
        $kitchen = $this->kitchenModel->getByOwnerId($userId);

        CSRF::generateToken();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $token = $_POST['csrf_token'] ?? '';

            if (!CSRF::validateToken($token)) {
                Session::flash('error', "Invalid request. Please try again.");
                header("Location: /business/dashboard/settings");
                exit;
            }

            switch ($action) {
                case 'update_profile':
                    $this->updateProfile($_POST, $_FILES);
                    break;
                case 'update_kitchen':
                    $this->updateKitchen($_POST, $_FILES);
                    break;
                case 'update_email':
                    $this->updateEmail($_POST);
                    break;
                case 'update_password':
                    $this->updatePassword($_POST);
                    break;
            }

            header("Location: /business/dashboard/settings");
            exit;
        }

        return [
            'title' => 'Account Settings',
            'page' => 'settings',
            'currentUser' => $user,
            'kitchen' => $kitchen,
            'viewFile' => BASE_PATH . '/src/views/pages/seller/settings.php',
        ];
    }

    private function updateProfile($postData, $files)
    {
        $userId = Session::get('user_id');
        $name = trim($postData['name'] ?? '');
        $phone = trim($postData['phone'] ?? '');
        $gender = trim($postData['gender'] ?? '');

        if (empty($name)) {
            Session::flash('error', 'Name is required');
            return false;
        }

        if (!preg_match("/^[a-zA-Z. ]+$/", $name)) {
            Session::flash('error', 'Name can only contain letters, spaces, and dots');
            return false;
        }

        if (strlen($name) < 2 || strlen($name) > 50) {
            Session::flash('error', 'Name must be between 2 and 50 characters');
            return false;
        }

        if (!preg_match("/^01[0-9]{9}$/", $phone)) {
            Session::flash('error', 'Phone number must be 11 digits and start with 01');
            return false;
        }

        if (!empty($gender) && !in_array($gender, ['male', 'female', 'other'])) {
            Session::flash('error', 'Invalid gender selection');
            return false;
        }

        $currentUser = $this->authModel->getById($userId);
        $currentProfileImage = $currentUser['PROFILE_IMAGE'] ?? null;

        $profileImage = $currentProfileImage;
        if (!empty($files['profile_image']['name'])) {
            $uploadResult = ImageHelper::uploadImage($files['profile_image'], 'profile');
            if (!$uploadResult['success']) {
                Session::flash('error', $uploadResult['message']);
                return false;
            }

            if ($currentProfileImage && $uploadResult['success']) {
                ImageHelper::deleteImage($currentProfileImage, 'profile');
            }

            $profileImage = $uploadResult['filename'];
        }

        $success = $this->authModel->updateProfile($userId, [
            'name' => $name,
            'phone' => $phone,
            'gender' => $gender,
            'profile_image' => $profileImage
        ]);

        if ($success) {
            Session::flash('success', 'Profile updated successfully');
            return true;
        }

        Session::flash('error', 'Failed to update profile');
        return false;
    }

    private function updateKitchen($postData, $files)
    {
        $userId = Session::get('user_id');
        $kitchen = $this->kitchenModel->getByOwnerId($userId);

        if (!$kitchen) {
            Session::flash('error', 'Kitchen not found');
            return false;
        }

        $kitchenId = $kitchen['KITCHEN_ID'];
        $name = trim($postData['kitchen_name'] ?? '');
        $description = trim($postData['description'] ?? '');
        $address = trim($postData['address'] ?? '');
        $googleMapsUrl = trim($postData['google_maps_url'] ?? '');
        $yearsExperience = !empty($postData['years_experience']) ? (int)$postData['years_experience'] : null;
        $signatureDish = trim($postData['signature_dish'] ?? '');
        $avgPrepTime = !empty($postData['avg_prep_time']) ? (int)$postData['avg_prep_time'] : 30;
        $isHalal = isset($postData['is_halal']) ? 1 : 0;
        $cleanlinessPledge = isset($postData['cleanliness_pledge']) ? 1 : 0;

        if (empty($name)) {
            Session::flash('error', 'Kitchen name is required');
            return false;
        }

        if (empty($address)) {
            Session::flash('error', 'Address is required');
            return false;
        }

        if (strlen($name) < 2 || strlen($name) > 100) {
            Session::flash('error', 'Kitchen name must be between 2 and 100 characters');
            return false;
        }

        $currentCoverImage = $kitchen['COVER_IMAGE'] ?? null;

        $coverImage = $currentCoverImage;
        if (!empty($files['cover_image']['name'])) {
            $uploadResult = ImageHelper::uploadImage($files['cover_image'], 'kitchen');
            if (!$uploadResult['success']) {
                Session::flash('error', $uploadResult['message']);
                return false;
            }

            if ($currentCoverImage && $uploadResult['success']) {
                ImageHelper::deleteImage($currentCoverImage, 'kitchen');
            }

            $coverImage = $uploadResult['filename'];
        }

        $updateData = [
            'name' => $name,
            'description' => $description,
            'address' => $address,
            'google_maps_url' => $googleMapsUrl,
            'years_experience' => $yearsExperience,
            'signature_dish' => $signatureDish,
            'avg_prep_time' => $avgPrepTime,
            'is_halal' => $isHalal,
            'cleanliness_pledge' => $cleanlinessPledge
        ];

        if ($coverImage) {
            $updateData['cover_image'] = $coverImage;
        }

        $success = $this->kitchenModel->updateKitchen($kitchenId, $updateData);

        if ($success) {
            Session::flash('success', 'Kitchen information updated successfully');
            return true;
        }

        Session::flash('error', 'Failed to update kitchen information');
        return false;
    }
    private function updateEmail($postData)
    {
        $userId = Session::get('user_id');
        $currentEmail = $postData['current_email'] ?? '';
        $newEmail = trim($postData['new_email'] ?? '');
        $password = $postData['password'] ?? '';

        if (empty($currentEmail) || empty($newEmail) || empty($password)) {
            Session::flash('error', 'All fields are required');
            return false;
        }

        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Invalid email format');
            return false;
        }

        $user = $this->authModel->getById($userId);
        if ($user['EMAIL'] !== $currentEmail) {
            Session::flash('error', 'Current email does not match');
            return false;
        }

        if (!password_verify($password, $user['PASSWORD_HASH'])) {
            Session::flash('error', 'Invalid password');
            return false;
        }

        if ($this->authModel->getByEmail($newEmail)) {
            Session::flash('error', 'Email already exists');
            return false;
        }

        $success = $this->authModel->updateEmail($userId, $newEmail);

        if ($success) {
            Session::flash('success', 'Email updated successfully');
            return true;
        }

        Session::flash('error', 'Failed to update email');
        return false;
    }

    private function updatePassword($postData)
    {
        $userId = Session::get('user_id');
        $currentPassword = $postData['current_password'] ?? '';
        $newPassword = $postData['new_password'] ?? '';
        $confirmPassword = $postData['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            Session::flash('error', 'All fields are required');
            return false;
        }

        if ($newPassword !== $confirmPassword) {
            Session::flash('error', 'New passwords do not match');
            return false;
        }

        if (strlen($newPassword) < 8) {
            Session::flash('error', 'Password must be at least 8 characters long');
            return false;
        }

        $user = $this->authModel->getById($userId);
        if (!password_verify($currentPassword, $user['PASSWORD_HASH'])) {
            Session::flash('error', 'Current password is incorrect');
            return false;
        }

        $success = $this->authModel->updatePassword($userId, $newPassword);

        if ($success) {
            Session::flash('success', 'Password updated successfully');
            return true;
        }

        Session::flash('error', 'Failed to update password');
        return false;
    }
}
