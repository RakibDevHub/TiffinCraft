<?php

class AdminController
{
    private $conn;
    private $authModel;
    private $userModel;
    private $orderModel;
    private $menuModel;
    private $categoryModel;
    private $kitchenModel;
    private $areaModel;
    private $financeModel;
    private $reviewModel;
    private $planModel;
    private $activitiesModel;


    public function __construct()
    {
        $this->conn = Database::getConnection();
        $this->authModel = new Auth($this->conn);
        $this->userModel = new User($this->conn);
        $this->orderModel = new Order($this->conn);
        $this->menuModel = new Menu($this->conn);
        $this->categoryModel = new Category($this->conn);
        $this->kitchenModel = new Kitchen($this->conn);
        $this->areaModel = new ServiceArea($this->conn);
        $this->financeModel = new Finance($this->conn);
        $this->reviewModel = new Review($this->conn);
        $this->planModel = new Subscription($this->conn);
        $this->activitiesModel = new Activities($this->conn);
    }

    // DASHBOARD MANAGEMENT 
    public function dashboard()
    {
        if (!AuthHelper::isLoggedIn('admin')) {
            header("Location: /login");
            exit;
        }


        $stats = [
            'masterWallet' => $this->financeModel->masterWallet(),
            'orderWallet' => $this->financeModel->orderWallet(),

            'subscriptionFee' => $this->financeModel->subscriptionFee(),
            'orderCommission' => $this->financeModel->orderCommission(),

            'sellerWithdrawals' => $this->financeModel->sellerWithdrawals(),
            'pendingWithdrawls' => $this->financeModel->countWithdrawByStatus('pending'),

            'orderRefunds' => $this->financeModel->orderRefunds(),
            'buyerRefunds' => $this->financeModel->buyerRefunds(),
            'pendingRefunds' => $this->financeModel->countRefundByStatus('pending'),

            'totalOrders' => $this->orderModel->countAll(),
            'complectedOrders' => $this->orderModel->countOrderByStatus('delivered'),
            'cancelledOrders' => $this->orderModel->countOrderByStatus('cancelled'),

            'totalUsers'     => $this->userModel->countAll(),
            'totalBuyers'    => $this->userModel->countByRole('buyer'),
            'totalSellers'   => $this->userModel->countByRole('seller'),
            'totalAdmins'    => $this->userModel->countByRole('admin'),
            'topSellers' => $this->userModel->topSellers(5),
            'popularItems' => $this->menuModel->popularItems(5),
        ];

        $growth = [
            'incomeGrowth' => $this->financeModel->incomeGrowth(12),
            'orderGrowth' => $this->financeModel->orderGrowth(12),
            'userGrowth' => $this->financeModel->userGrowth(12),
        ];

        $recentActivities = $this->activitiesModel->getRecentActivities();

        $user = $this->authModel->getById(Session::get('user_id'));

        return [
            'title'         => 'Admin Dashboard',
            'page'          => 'dashboard',
            'currentUser'   => $user,
            'stats'         => $stats,
            'growth'        => $growth,
            'activities'    => $recentActivities,
            'viewFile'      => BASE_PATH . '/src/views/pages/admin/dashboard.php',
        ];
    }

    // USER MANAGEMENT
    public function manageUsers()
    {
        if (!AuthHelper::isLoggedIn('admin')) {
            header("Location: /login");
            exit;
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = ($page - 1) * $limit;

        $userdata = $this->userModel->getAllUserDetails($limit, $offset);
        $totalUsers = $this->userModel->countAll();

        $user = $this->authModel->getById(Session::get('user_id'));

        CSRF::generateToken();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $userId = $_POST['user_id'] ?? '';
            $token = $_POST['csrf_token'] ?? '';

            if (!CSRF::validateToken($token)) {
                Session::flash('error', "Invalid request. Please try again.");
                header("Location: /admin/dashboard/users");
                exit;
            }

            switch ($action) {
                case 'suspend':
                    $this->suspendUser($userId, $_POST);
                    break;
                case 'delete':
                    $this->deleteUser($userId, $_POST);
                    break;
                case 'add_admin':
                    $this->addAdmin($_POST);
                    break;
                case 'lift_suspension':
                    $this->liftUserSuspension($userId);
                    break;
            }

            header('Location: /admin/dashboard/users');
            exit;
        }

        return [
            'title'         => 'User Oversight',
            'page'          => 'users',
            'userdata'      => $userdata,
            'totalUsers'    => $totalUsers,
            'currentUser'   => $user,
            'viewFile'      => BASE_PATH . '/src/views/pages/admin/users.php',
        ];
    }

    private function suspendUser($userId, $postData)
    {
        $period = $postData['period'] ?? '';
        $reason = trim($postData['reason'] ?? '');

        if (empty($period) || empty($reason)) {
            Session::flash('error', 'Suspension period and reason are required.');
            return;
        }

        // Calculate suspension end date
        if ($period === 'permanent') {
            $endDate = null;
        } else {
            $days = (int) $period;
            $endDate = (new DateTime())->modify("+{$days} days")->format('Y-m-d H:i:s');
        }

        $this->authModel->addSuspension([
            'id'        => $userId,
            'type'      => 'USER',
            'reason'    => $reason,
            'end_date'  => $endDate,
            'admin_id'  => Session::get('user_id')
        ]);

        Session::flash('success', 'User suspended successfully');
        header("Location: /admin/dashboard/users");
        exit;
    }

    private function liftUserSuspension($userId)
    {
        $this->authModel->liftSuspension([
            'id'        => $userId,
            'type'      => 'USER',
        ]);

        Session::flash('success', 'User suspension lifted successfully');
        header("Location: /admin/dashboard/users");
        exit;
    }

    private function deleteUser($userId, $postData)
    {
        $confirmation = $postData['confirmation'] ?? '';

        if ($confirmation !== 'DELETE') {
            Session::flash('error', 'Confirmation text did not match');
            return;
        }

        $this->authModel->delete($userId);

        Session::flash('success', 'User deleted successfully');
        header("Location: /admin/dashboard/users");
        exit;
    }

    private function addAdmin($postData)
    {
        $name  = trim($postData['name'] ?? '');
        $email = filter_var(trim($postData['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $phone = trim($postData['phone'] ?? '');

        // === Validations ===
        if (empty($name) || empty($email) || empty($phone)) {
            Session::flash('error', "All fields are required.");
            header("Location: /admin/dashboard/users");
            exit;
        }

        if (!preg_match("/^[a-zA-Z. ]+$/", $name)) {
            Session::flash('error', "Name can only contain letters, spaces, and dots.");
            header("Location: /admin/dashboard/users");
            exit;
        }

        if (preg_match("/(\.\.| {2,})/", $name)) {
            Session::flash('error', "Name cannot contain consecutive dots or spaces.");
            header("Location: /admin/dashboard/users");
            exit;
        }

        if (preg_match("/(\.\s|\s\.){2,}/", $name)) {
            Session::flash('error', "Name contains invalid formatting near dots and spaces.");
            header("Location: /admin/dashboard/users");
            exit;
        }

        if (strlen($name) < 2 || strlen($name) > 50) {
            Session::flash('error', "Name must be between 2 and 50 characters.");
            header("Location: /admin/dashboard/users");
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', "Invalid email format.");
            header("Location: /admin/dashboard/users");
            exit;
        }

        if (!preg_match("/^01[0-9]{9}$/", $phone)) {
            Session::flash('error', "Phone number must be 11 digits and start with 01.");
            header("Location: /admin/dashboard/users");
            exit;
        }

        if ($this->authModel->getByEmail($email)) {
            Session::flash('error', "Email already registered.");
            header("Location: /admin/dashboard/users");
            exit;
        }

        $password = $this->generateStrongPassword(8);
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $adminId = $this->authModel->createAdmin([
                'name'     => $name,
                'email'    => $email,
                'phone'    => $phone,
                'password' => $passwordHash
            ]);

            if (!$adminId) {
                throw new Exception("Failed to insert Admin.");
            }

            if (!Mailer::sendPassword($email, $password)) {
                throw new Exception("Admin created but email sending failed.");
            }

            oci_commit($this->conn);

            Session::flash('success', 'Admin user created successfully');
            header("Location: /admin/dashboard/users");
            exit;
        } catch (Exception $e) {
            oci_rollback($this->conn);

            Session::flash('error', $e->getMessage());
            header("Location: /admin/dashboard/users");
            exit;
        }
    }

    private function generateStrongPassword($length = 12)
    {
        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $special = '@$!%*?&';

        $password = '';
        $password .= $lower[random_int(0, strlen($lower) - 1)];
        $password .= $upper[random_int(0, strlen($upper) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];

        $all = $lower . $upper . $numbers . $special;
        for ($i = 4; $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        return str_shuffle($password);
    }

    // KITCHEN MANAGEMENT
    public function manageKitchens()
    {
        if (!AuthHelper::isLoggedIn('admin')) {
            header("Location: /login");
            exit;
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = ($page - 1) * $limit;

        $totalKitchens = $this->kitchenModel->countAll();
        $kitchenData = $this->kitchenModel->getAllKitchenDetails($limit, $offset);

        $user = $this->authModel->getById(Session::get('user_id'));

        CSRF::generateToken();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $kitchenId = $_POST['kitchen_id'] ?? '';
            $token = $_POST['csrf_token'] ?? '';

            if (!CSRF::validateToken($token)) {
                Session::flash('error', "Invalid request. Please try again.");
                header("Location: /admin/dashboard/kitchens");
                exit;
            }

            switch ($action) {
                case 'suspend':
                    $this->suspendKitchen($kitchenId, $_POST);
                    break;
                case 'lift_suspension':
                    $this->liftKitchenSuspension($kitchenId);
                    break;
            }

            header('Location: /admin/dashboard/kitchens');
            exit;
        }

        return [
            'title'         => 'Kitchen Oversight',
            'page'          => 'kitchens',
            'kitchendata'      => $kitchenData,
            'totalKitchens'    => $totalKitchens,
            'currentUser'   => $user,
            'viewFile'      => BASE_PATH . '/src/views/pages/admin/kitchens.php',
        ];
    }

    private function suspendKitchen($kitchenId, $postData)
    {
        $period = $postData['period'] ?? '';
        $reason = trim($postData['reason'] ?? '');

        if (empty($period) || empty($reason)) {
            Session::flash('error', 'Suspension period and reason are required.');
            return;
        }

        if ($period === 'permanent') {
            $endDate = null;
        } else {
            $days = (int) $period;
            $endDate = (new DateTime())->modify("+{$days} days")->format('Y-m-d H:i:s');
        }

        $this->authModel->addSuspension([
            'id'        => $kitchenId,
            'type'      => 'KITCHEN',
            'reason'    => $reason,
            'end_date'  => $endDate,
            'admin_id'  => Session::get('user_id')
        ]);

        Session::flash('success', 'Kitchen suspended successfully');
    }

    private function liftKitchenSuspension($kitchenId)
    {
        $this->authModel->liftSuspension([
            'id'        => $kitchenId,
            'type'      => 'KITCHEN',
        ]);

        Session::flash('success', 'Kitchen suspension lifted successfully');
    }

    // CATEGORY MANAGEMENT 
    public function manageCategories()
    {
        if (!AuthHelper::isLoggedIn('admin')) {
            header("Location: /login");
            exit;
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = ($page - 1) * $limit;

        $categoriesData = $this->categoryModel->getAllCategoryDetails($limit, $offset);
        $totalCategories = $this->categoryModel->countAll();

        $user = $this->authModel->getById(Session::get('user_id'));

        CSRF::generateToken();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $categoryId = $_POST['category_id'] ?? '';
            $token = $_POST['csrf_token'] ?? '';

            if (!CSRF::validateToken($token)) {
                Session::flash('error', "Invalid request. Please try again.");
                header("Location: /admin/dashboard/categories");
                exit;
            }

            switch ($action) {
                case 'add':
                    $this->addCategory($_POST, $_FILES);
                    break;
                case 'edit':
                    $this->editCategory($categoryId, $_POST, $_FILES);
                    break;
                case 'delete':
                    $this->deleteCategory($categoryId);
                    break;
            }

            header('Location: /admin/dashboard/categories');
            exit;
        }

        return [
            'title' => 'Category Management',
            'page' => 'categories',
            'categoriesData' => $categoriesData,
            'totalCategories' => $totalCategories,
            'currentUser' => $user,
            'viewFile' => BASE_PATH . '/src/views/pages/admin/categories.php',
        ];
    }

    private function addCategory($postData, $files)
    {
        $name = trim($postData['name']);
        $description = trim($postData['description'] ?? '');

        if (empty($name)) {
            Session::flash('error', "Category name is required");
            return false;
        }

        if (!preg_match("/^[a-zA-Z0-9.,\- ]+$/", $name)) {
            Session::flash('error', "Category name can only contain letters, numbers, spaces, commas, dots, and hyphens.");
            return false;
        }

        if (preg_match("/(\.\.| {2,})/", $name)) {
            Session::flash('error', "Category name cannot contain consecutive dots or spaces.");
            return false;
        }

        if (strlen($name) < 2 || strlen($name) > 50) {
            Session::flash('error', "Category name must be between 2 and 50 characters.");
            return false;
        }

        if (!empty($description)) {
            if (strlen($description) < 5 || strlen($description) > 255) {
                Session::flash('error', "Description must be between 5 and 255 characters.");
                return false;
            }

            if (!preg_match("/^[a-zA-Z0-9.,\-!?:;'\"() ]+$/", $description)) {
                Session::flash('error', "Description contains invalid characters.");
                return false;
            }
        }

        $categoryImage  = null;
        $newImageUploaded = false;

        if (!empty($files['image']['name'])) {
            $uploadResult = ImageHelper::uploadImage($files['image'], 'categories');
            if (!$uploadResult['success']) {
                Session::flash('error', $uploadResult['message']);
                return false;
            }
            $categoryImage  = $uploadResult['filename'];
            $newImageUploaded = true;
        }

        $categoryData = [
            'name' => $name,
            'description' => $description,
            'image' => $categoryImage
        ];

        $categoryId = $this->categoryModel->create($categoryData);

        if ($categoryId) {
            Session::flash('success', 'Category created successfully!');
            return true;
        } else {
            if ($newImageUploaded) {
                ImageHelper::deleteImage($categoryImage, 'categories');
            }
            Session::flash('error', 'Failed to create category. Please try again.');
            return false;
        }
    }

    private function editCategory($categoryId, $postData, $files)
    {
        $name = trim($postData['name']);
        $description = trim($postData['description'] ?? '');

        if (empty($name)) {
            Session::flash('error', "Category name is required");
            return false;
        }

        if (!preg_match("/^[a-zA-Z0-9.,\- ]+$/", $name)) {
            Session::flash('error', "Category name can only contain letters, numbers, spaces, commas, dots, and hyphens.");
            return false;
        }

        if (preg_match("/(\.\.| {2,})/", $name)) {
            Session::flash('error', "Category name cannot contain consecutive dots or spaces.");
            return false;
        }

        if (strlen($name) < 2 || strlen($name) > 50) {
            Session::flash('error', "Category name must be between 2 and 50 characters.");
            return false;
        }

        if (!empty($description)) {
            if (strlen($description) < 5 || strlen($description) > 255) {
                Session::flash('error', "Description must be between 5 and 255 characters.");
                return false;
            }

            if (!preg_match("/^[a-zA-Z0-9.,\-!?:;'\"() ]+$/", $description)) {
                Session::flash('error', "Description contains invalid characters.");
                return false;
            }
        }

        $currentCategory = $this->categoryModel->getById($categoryId);
        if (!$currentCategory) {
            Session::flash('error', "Category not found");
            return false;
        }

        $categoryImage  = $currentCategory['IMAGE'];
        $newImageUploaded = false;

        if (!empty($files['image']['name'])) {
            $uploadResult = ImageHelper::uploadImage($files['image'], 'categories');
            if (!$uploadResult['success']) {
                Session::flash('error', $uploadResult['message']);
                return false;
            }

            $categoryImage = $uploadResult['filename'];
            $newImageUploaded = true;
        }

        $categoryData = [
            'name' => $name,
            'description' => $description,
            'image' => $categoryImage
        ];

        $result = $this->categoryModel->update($categoryId, $categoryData);

        if ($result) {
            if ($newImageUploaded && $currentCategory['IMAGE']) {
                ImageHelper::deleteImage($currentCategory['IMAGE'], 'categories');
            }
            Session::flash('success', 'Category updated successfully!');
            return true;
        } else {
            if ($newImageUploaded) {
                ImageHelper::deleteImage($categoryImage, 'categories');
            }
            Session::flash('error', 'Failed to update category. Please try again.');
            return false;
        }
    }

    private function deleteCategory($categoryId)
    {
        $imagePath = $this->categoryModel->getImagePath($categoryId);

        $success = $this->categoryModel->delete($categoryId);

        if ($success) {
            if ($imagePath) {
                ImageHelper::deleteImage($imagePath, 'categories');
            }
            Session::flash('success', "Category deleted successfully");
            return true;
        } else {
            Session::flash('error', "Failed to delete category");
            return false;
        }
    }

    // AREA MANAGEMENT 
    public function manageAreas()
    {
        if (!AuthHelper::isLoggedIn('admin')) {
            header("Location: /login");
            exit;
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = ($page - 1) * $limit;

        $areasData = $this->areaModel->getAllAreaDetails($limit, $offset);
        $totalAreas = $this->areaModel->countAll();

        $user = $this->authModel->getById(Session::get('user_id'));

        CSRF::generateToken();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $areaId = $_POST['area_id'] ?? '';
            $token = $_POST['csrf_token'] ?? '';

            if (!CSRF::validateToken($token)) {
                Session::flash('error', "Invalid request. Please try again.");
                header("Location: /admin/dashboard/areas");
                exit;
            }

            switch ($action) {
                case 'add':
                    $this->addArea($_POST);
                    break;
                case 'edit':
                    $this->editArea($areaId, $_POST);
                    break;
                case 'delete':
                    $this->deleteArea($areaId);
                    break;
            }

            header('Location: /admin/dashboard/areas');
            exit;
        }

        return [
            'title' => 'Service Area Management',
            'page' => 'areas',
            'areasData' => $areasData,
            'totalAreas' => $totalAreas,
            'currentUser' => $user,
            'viewFile' => BASE_PATH . '/src/views/pages/admin/areas.php',
        ];
    }

    private function addArea($postData)
    {
        $name = trim($postData['name']);
        $city = trim($postData['city']);
        $status = isset($postData['status']) ? 'active' : 'inactive';

        if (empty($name) || empty($city)) {
            Session::flash('error', "Area name and city are required");
            return false;
        }

        if (!preg_match("/^[a-zA-Z. ]+$/", $name)) {
            Session::flash('error', "Name can only contain letters, spaces, and dots.");
            header("Location: /admin/dashboard/areas");
            exit;
        }

        if (preg_match("/(\.\.| {2,})/", $name)) {
            Session::flash('error', "Name cannot contain consecutive dots or spaces.");
            header("Location: /admin/dashboard/areas");
            exit;
        }

        if (preg_match("/(\.\s|\s\.){2,}/", $name)) {
            Session::flash('error', "Name contains invalid formatting near dots and spaces.");
            header("Location: /admin/dashboard/areas");
            exit;
        }

        if (strlen($name) < 2 || strlen($name) > 50) {
            Session::flash('error', "Name must be between 2 and 50 characters.");
            header("Location: /admin/dashboard/areas");
            exit;
        }

        if (!preg_match("/^[a-zA-Z. ]+$/", $city)) {
            Session::flash('error', "City can only contain letters, spaces, and dots.");
            header("Location: /admin/dashboard/areas");
            exit;
        }

        if (strlen($city) < 2 || strlen($city) > 50) {
            Session::flash('error', "City must be between 2 and 50 characters.");
            header("Location: /admin/dashboard/areas");
            exit;
        }

        if (!$this->areaModel->checkUnique($name, $city)) {
            Session::flash('error', "Service area already exists in this city");
            return false;
        }

        $areaId = $this->areaModel->create([
            'name' => $name,
            'city' => $city,
            'status' => $status
        ]);

        if (!$areaId) {
            Session::flash('error', "Failed to add service area");
            return false;
        }

        Session::flash('success', "Service area added successfully");
        return true;
    }

    private function editArea($areaId, $postData)
    {
        $name = trim($postData['name']);
        $city = trim($postData['city']);
        $status = isset($postData['status']) ? 'active' : 'inactive';

        if (empty($name) || empty($city)) {
            Session::flash('error', "Area name and city are required");
            return false;
        }

        $currentArea = $this->areaModel->getById($areaId);
        if (!$currentArea) {
            Session::flash('error', "Service area not found");
            return false;
        }

        if (!$this->areaModel->checkUnique($name, $city, $areaId)) {
            Session::flash('error', "Service area already exists in this city");
            return false;
        }

        $success = $this->areaModel->update([
            'area_id' => $areaId,
            'name' => $name,
            'city' => $city,
            'status' => $status
        ]);

        if (!$success) {
            Session::flash('error', "Failed to update service area");
            return false;
        }

        Session::flash('success', "Service area updated successfully");
        return true;
    }

    private function deleteArea($areaId)
    {
        $success = $this->areaModel->delete($areaId);

        if (!$success) {
            Session::flash('error', "Failed to delete service area");
            return false;
        }

        Session::flash('success', "Service area deleted successfully");
        return true;
    }

    // REVIEW MANAGEMENT                     
    public function manageReviews()
    {
        if (!AuthHelper::isLoggedIn('admin')) {
            header("Location: /login");
            exit;
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = ($page - 1) * $limit;

        $reviewsData = $this->reviewModel->getAllReviewsForAdmin($limit, $offset);
        $totalReviews = $this->reviewModel->countAll();
        $reviewStats = $this->reviewModel->getAdminReviewStats();

        $user = $this->authModel->getById(Session::get('user_id'));

        CSRF::generateToken();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $reviewId = $_POST['review_id'] ?? '';
            $token = $_POST['csrf_token'] ?? '';

            if (!CSRF::validateToken($token)) {
                Session::flash('error', "Invalid request. Please try again.");
                header("Location: /admin/dashboard/reviews");
                exit;
            }

            switch ($action) {
                case 'update_status':
                    $status = $_POST['status'] ?? '';
                    $reason = $_POST['reason'] ?? '';
                    $this->updateReviewStatus($reviewId, $status, $user['USER_ID'], $reason);
                    break;

                case 'delete':
                    $this->deleteReview($reviewId);
                    break;
            }

            header('Location: /admin/dashboard/reviews');
            exit;
        }

        return [
            'title' => 'Reviews Management',
            'page' => 'reviews',
            'reviewsData' => $reviewsData,
            'totalReviews' => $totalReviews,
            'reviewStats' => $reviewStats,
            'currentUser' => $user,
            'viewFile' => BASE_PATH . '/src/views/pages/admin/reviews.php',
        ];
    }

    private function updateReviewStatus($reviewId, $status, $adminId, $reason = '')
    {
        if (!in_array($status, ['PUBLIC', 'HIDDEN', 'REPORTED'])) {
            Session::flash('error', "Invalid status value");
            return false;
        }

        $currentReview = $this->reviewModel->getReviewById($reviewId);
        if (!$currentReview) {
            Session::flash('error', "Review not found");
            return false;
        }

        $success = $this->reviewModel->updateReviewStatus($reviewId, $status, $adminId, $reason);

        if (!$success) {
            Session::flash('error', "Failed to update review status");
            return false;
        }

        Session::flash('success', "Review status updated successfully");
        return true;
    }

    private function deleteReview($reviewId)
    {
        $success = $this->reviewModel->deleteReview($reviewId);

        if (!$success) {
            Session::flash('error', "Failed to delete review");
            return false;
        }

        Session::flash('success', "Review deleted successfully");
        return true;
    }

    // PLAN MANAGEMENT                    
    public function manageSubscriptions()
    {
        if (!AuthHelper::isLoggedIn('admin')) {
            header("Location: /login");
            exit;
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = ($page - 1) * $limit;

        $totalPlans = $this->planModel->countAllPlans();
        $plansData = $this->planModel->getAllPlanDetails($limit, $offset);

        $user = $this->authModel->getById(Session::get('user_id'));

        CSRF::generateToken();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $planId = $_POST['plan_id'] ?? '';
            $token = $_POST['csrf_token'] ?? '';

            if (!CSRF::validateToken($token)) {
                Session::flash('error', "Invalid request. Please try again.");
                header("Location: /admin/dashboard/subscriptions");
                exit;
            }

            switch ($action) {
                case 'add_plan':
                    $this->addPlan($_POST);
                    break;
                case 'edit_plan':
                    $this->editPlan($planId, $_POST);
                    break;
                case 'delete_plan':
                    $this->deletePlan($planId);
                    break;
            }

            header('Location: /admin/dashboard/subscriptions');
            exit;
        }

        return [
            'title' => 'Subscription Plans',
            'page' => 'subscriptions',
            'plansData' => $plansData,
            'totalPlans' => $totalPlans,
            'currentUser' => $user,
            'viewFile' => BASE_PATH . '/src/views/pages/admin/subscriptions.php',
        ];
    }

    private function addPlan($postData)
    {
        $planName = trim($postData['plan_name']);
        $description = trim($postData['description'] ?? '');
        $monthlyFee = (float)($postData['monthly_fee'] ?? 0);
        $commissionRate = (float)($postData['commission_rate'] ?? 0);
        $maxItems = (int)($postData['max_items'] ?? 3);
        $isActive = isset($postData['is_active']) ? 1 : 0;
        $isHighlight = isset($postData['is_highlight']) ? 1 : 0;

        if (empty($planName)) {
            Session::flash('error', "Plan name is required");
            return false;
        }

        if ($monthlyFee < 0) {
            Session::flash('error', "Monthly fee cannot be negative");
            return false;
        }

        if ($commissionRate < 0 || $commissionRate > 100) {
            Session::flash('error', "Commission rate must be between 0 and 100");
            return false;
        }

        $planId = $this->planModel->createPlan([
            'planName' => $planName,
            'description' => $description,
            'monthlyFee' => $monthlyFee,
            'commissionRate' => $commissionRate,
            'maxItems' => $maxItems,
            'isActive' => $isActive,
            'isHighlight' => $isHighlight,
        ]);

        if (!$planId) {
            Session::flash('error', "Failed to add subscription plan");
            return false;
        }

        Session::flash('success', "Subscription plan added successfully");
        return true;
    }

    private function editPlan($planId, $postData)
    {
        $planName = trim($postData['plan_name']);
        $description = trim($postData['description'] ?? '');
        $monthlyFee = (float)($postData['monthly_fee'] ?? 0);
        $commissionRate = (float)($postData['commission_rate'] ?? 0);
        $maxItems = (int)($postData['max_items'] ?? 3);
        $isActive = isset($postData['is_active']) ? 1 : 0;
        $isHighlight = isset($postData['is_highlight']) ? 1 : 0;

        if (empty($planName)) {
            Session::flash('error', "Plan name is required");
            return false;
        }

        if ($monthlyFee < 0) {
            Session::flash('error', "Monthly fee cannot be negative");
            return false;
        }

        if ($commissionRate < 0 || $commissionRate > 100) {
            Session::flash('error', "Commission rate must be between 0 and 100");
            return false;
        }

        $currentPlan = $this->planModel->getPlanById($planId);
        if (!$currentPlan) {
            Session::flash('error', "Subscription plan not found");
            return false;
        }

        $success = $this->planModel->updatePlan([
            'planId' => $planId,
            'planName' => $planName,
            'description' => $description,
            'monthlyFee' => $monthlyFee,
            'commissionRate' => $commissionRate,
            'maxItems' => $maxItems,
            'isActive' => $isActive,
            'isHighlight' => $isHighlight,
        ]);

        if (!$success) {
            Session::flash('error', "Failed to update subscription plan");
            return false;
        }

        Session::flash('success', "Subscription plan updated successfully");
        return true;
    }

    private function deletePlan($planId)
    {
        $success = $this->planModel->deletePlanById($planId);

        if (!$success) {
            Session::flash('error', "Failed to delete subscription plan");
            return false;
        }

        Session::flash('success', "Subscription plan deleted successfully");
        return true;
    }


    // TRANSACTION MANAGEMENT
    public function viewTransactions()
    {
        if (!AuthHelper::isLoggedIn('admin')) {
            header("Location: /login");
            exit;
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = ($page - 1) * $limit;

        // Get filters
        $filters = [];
        if (!empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }
        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        if (!empty($_GET['transaction_type'])) {
            $filters['transaction_type'] = $_GET['transaction_type'];
        }
        if (!empty($_GET['reference_type'])) {
            $filters['reference_type'] = $_GET['reference_type'];
        }
        if (!empty($_GET['date_from'])) {
            $filters['date_from'] = $_GET['date_from'];
        }
        if (!empty($_GET['date_to'])) {
            $filters['date_to'] = $_GET['date_to'];
        }

        $transactions = $this->financeModel->getPaymentTransactions($limit, $offset, $filters);
        $totalTransactions = $this->financeModel->countPaymentTransactions($filters);

        $user = $this->authModel->getById(Session::get('user_id'));

        CSRF::generateToken();

        return [
            'title' => 'Financial Transactions',
            'page' => 'transactions',
            'transactions' => $transactions,
            'totalTransactions' => $totalTransactions,
            'currentPage' => $page,
            'limit' => $limit,
            'filters' => $filters,
            'currentUser' => $user,
            'viewFile' => BASE_PATH . '/src/views/pages/admin/transactions.php',
        ];
    }


    // WITHDRAWAL MANAGEMENT
    public function manageWithdrawals()
    {
        if (!AuthHelper::isLoggedIn('admin')) {
            header("Location: /login");
            exit;
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = ($page - 1) * $limit;

        // Get filters
        $filters = [];
        if (!empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }
        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }

        $withdrawals = $this->financeModel->getWithdrawalRequests($limit, $offset, $filters);
        $totalWithdrawals = $this->financeModel->countWithdrawalRequests($filters);

        $user = $this->authModel->getById(Session::get('user_id'));

        CSRF::generateToken();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $token = $_POST['csrf_token'] ?? '';

            if (!CSRF::validateToken($token)) {
                Session::flash('error', "Invalid request. Please try again.");
                header("Location: /admin/dashboard/withdrawals");
                exit;
            }

            switch ($action) {
                case 'approve_withdraw':
                    $this->approveWithdraw($_POST);
                    break;
                case 'reject_withdraw':
                    $this->rejectWithdraw($_POST);
                    break;
                case 'process_withdraw':
                    $this->processWithdraw($_POST);
                    break;
            }

            header("Location: /admin/dashboard/withdrawals");
            exit;
        }

        return [
            'title' => 'Withdrawal Requests',
            'page' => 'withdrawals',
            'withdrawals' => $withdrawals,
            'totalWithdrawals' => $totalWithdrawals,
            'currentPage' => $page,
            'limit' => $limit,
            'filters' => $filters,
            'currentUser' => $user,
            'viewFile' => BASE_PATH . '/src/views/pages/admin/withdrawals.php',
        ];
    }

    private function approveWithdraw($postData)
    {
        $withdrawId = $postData['withdraw_id'] ?? '';
        $adminNotes = trim($postData['admin_notes'] ?? '');

        if (empty($withdrawId)) {
            Session::flash('error', 'Withdrawal ID is required');
            header("Location: /admin/dashboard/withdrawals");
            exit;
        }

        $withdrawal = $this->financeModel->getWithdrawalById($withdrawId);
        if (!$withdrawal || $withdrawal['STATUS'] !== 'PENDING') {
            Session::flash('error', 'Invalid withdrawal request or request already processed');
            header("Location: /admin/dashboard/withdrawals");
            exit;
        }

        $success = $this->financeModel->updateWithdrawStatus($withdrawId, 'APPROVED', $adminNotes);

        if ($success) {
            Session::flash('success', 'Withdrawal request approved successfully');
        } else {
            Session::flash('error', 'Failed to approve withdrawal request');
        }

        header("Location: /admin/dashboard/withdrawals");
        exit;
    }

    private function rejectWithdraw($postData)
    {
        $withdrawId = $postData['withdraw_id'] ?? '';
        $adminNotes = trim($postData['admin_notes'] ?? '');

        if (empty($withdrawId)) {
            Session::flash('error', 'Withdrawal ID is required');
            header("Location: /admin/dashboard/withdrawals");
            exit;
        }

        if (empty($adminNotes)) {
            Session::flash('error', 'Please provide a reason for rejection');
            header("Location: /admin/dashboard/withdrawals");
            exit;
        }

        $withdrawal = $this->financeModel->getWithdrawalById($withdrawId);
        if (!$withdrawal || $withdrawal['STATUS'] !== 'PENDING') {
            Session::flash('error', 'Invalid withdrawal request or request already processed');
            header("Location: /admin/dashboard/withdrawals");
            exit;
        }

        $success = $this->financeModel->updateWithdrawStatus($withdrawId, 'REJECTED', $adminNotes);

        if ($success) {
            Session::flash('success', 'Withdrawal request rejected successfully');
        } else {
            Session::flash('error', 'Failed to reject withdrawal request');
        }

        header("Location: /admin/dashboard/withdrawals");
        exit;
    }

    private function processWithdraw($postData)
    {
        $withdrawId = $postData['withdraw_id'] ?? '';
        $bkashTrxId  = trim($postData['bkash_trxid'] ?? '');
        $adminNotes = trim($postData['admin_notes'] ?? '');

        if (empty($withdrawId)) {
            Session::flash('error', 'Withdrawal ID is required');
            header("Location: /admin/dashboard/withdrawals");
            exit;
        }

        if (empty($bkashTrxId)) {
            Session::flash('error', 'bKash Transaction ID is required');
            header("Location: /admin/dashboard/withdrawals");
            exit;
        }

        $withdrawal = $this->financeModel->getWithdrawalById($withdrawId);
        if (!$withdrawal || $withdrawal['STATUS'] !== 'APPROVED') {
            Session::flash('error', 'Invalid withdrawal request or request not approved');
            header("Location: /admin/dashboard/withdrawals");
            exit;
        }

        $this->financeModel->beginTransaction();

        try {
            $withdrawSuccess = $this->financeModel->markWithdrawalAsProcessed($withdrawId, $adminNotes);

            $processedBy = $this->authModel->getById($_SESSION['user_id']);
            $processedFor = $this->authModel->getById($withdrawal['SELLER_ID']);

            if (!$withdrawSuccess) {
                throw new Exception('Failed to update withdrawal status');
            }

            $transactionData = [
                'transaction_id' => $this->financeModel->generateTransactionId('WD'),
                'user_id' => $withdrawal['SELLER_ID'],
                'amount' => $withdrawal['AMOUNT'],
                'currency' => 'BDT',
                'transaction_type' => 'PAYOUT',
                'reference_type' => 'WITHDRAWAL',
                'reference_id' => $withdrawId,
                'payment_method' => $withdrawal['METHOD'],
                'status' => 'SUCCESS',
                'description' => 'Withdraw for ' . $processedFor['NAME'] . ' processed via ' . $processedBy['NAME'],
                'gateway_response' => json_encode(['trx_id' => $bkashTrxId]),
                'message' => 'Withdrawal processed successfully',
                'metadata' => json_encode([
                    'withdraw_id' => $withdrawId,
                    'bkash_trx_id' => $bkashTrxId,
                    'account_details' => $withdrawal['ACCOUNT_DETAILS'],
                    'processed_by' => $processedBy['NAME'],
                    'processed_user_id' => $processedBy['USER_ID'],
                    'processed_user_email' => $processedBy['EMAIL'],
                    'processed_user_phone' => $processedBy['PHONE'],
                    'admin_notes' => $adminNotes,
                ])
            ];

            $transactionSuccess = $this->financeModel->recordTransaction($transactionData);

            if (!$transactionSuccess) {
                throw new Exception('Failed to record payment transaction');
            }

            $this->financeModel->commitTransaction();

            Session::flash('success', 'Withdrawal processed successfully');
        } catch (Exception $e) {
            $this->financeModel->rollbackTransaction();

            error_log('Error processing withdrawal: ' . $e->getMessage());
            Session::flash('error', 'Failed to process withdrawal: ' . $e->getMessage());
        }


        header("Location: /admin/dashboard/withdrawals");
        exit;
    }

    // REFUND MANAGEMENT
    public function manageRefunds()
    {
        if (!AuthHelper::isLoggedIn('admin')) {
            header("Location: /login");
            exit;
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = ($page - 1) * $limit;

        $filters = [];
        if (!empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }
        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }

        $refunds = $this->financeModel->getRefundRequests($limit, $offset, $filters);
        $totalRefunds = $this->financeModel->countRefundRequests($filters);

        $user = $this->authModel->getById(Session::get('user_id'));

        CSRF::generateToken();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $token = $_POST['csrf_token'] ?? '';

            if (!CSRF::validateToken($token)) {
                Session::flash('error', "Invalid request. Please try again.");
                header("Location: /admin/dashboard/refunds");
                exit;
            }

            switch ($action) {
                case 'approve_refund':
                    $this->approveRefund($_POST);
                    break;
                case 'reject_refund':
                    $this->rejectRefund($_POST);
                    break;
                case 'process_refund':
                    $this->processRefund($_POST);
                    break;
            }

            header("Location: /admin/dashboard/refunds");
            exit;
        }

        return [
            'title' => 'Refund Requests',
            'page' => 'refunds',
            'refunds' => $refunds,
            'totalRefunds' => $totalRefunds,
            'currentPage' => $page,
            'limit' => $limit,
            'filters' => $filters,
            'currentUser' => $user,
            'viewFile' => BASE_PATH . '/src/views/pages/admin/refunds.php',
        ];
    }

    private function approveRefund($postData)
    {
        $refundId = $postData['refund_id'] ?? '';
        $adminNotes = trim($postData['admin_notes'] ?? '');

        if (empty($refundId)) {
            Session::flash('error', 'Refund ID is required');
            header("Location: /admin/dashboard/refunds");
            exit;
        }

        $refund = $this->financeModel->getRefundById($refundId);
        if (!$refund || $refund['STATUS'] !== 'PENDING') {
            Session::flash('error', 'Invalid refund request or request already processed');
            header("Location: /admin/dashboard/refunds");
            exit;
        }

        $success = $this->financeModel->updateRefundStatus($refundId, 'APPROVED', $adminNotes);

        if ($success) {
            Session::flash('success', 'Refund request approved successfully');
        } else {
            Session::flash('error', 'Failed to approve refund request');
        }

        header("Location: /admin/dashboard/refunds");
        exit;
    }

    private function rejectRefund($postData)
    {
        $refundId = $postData['refund_id'] ?? '';
        $adminNotes = trim($postData['admin_notes'] ?? '');

        if (empty($refundId)) {
            Session::flash('error', 'Refund ID is required');
            header("Location: /admin/dashboard/refunds");
            exit;
        }

        if (empty($adminNotes)) {
            Session::flash('error', 'Please provide a reason for rejection');
            header("Location: /admin/dashboard/refunds");
            exit;
        }

        $refund = $this->financeModel->getRefundById($refundId);
        if (!$refund || $refund['STATUS'] !== 'PENDING') {
            Session::flash('error', 'Invalid refund request or request already processed');
            header("Location: /admin/dashboard/refunds");
            exit;
        }

        $success = $this->financeModel->updateRefundStatus($refundId, 'REJECTED', $adminNotes);

        if ($success) {
            Session::flash('success', 'Refund request rejected successfully');
        } else {
            Session::flash('error', 'Failed to reject refund request');
        }

        header("Location: /admin/dashboard/refunds");
        exit;
    }

    private function processRefund($postData)
    {
        $refundId = $postData['refund_id'] ?? '';
        $transactionId = trim($postData['transaction_id'] ?? '');
        $adminNotes = trim($postData['admin_notes'] ?? '');

        if (empty($refundId)) {
            Session::flash('error', 'Refund ID is required');
            header("Location: /admin/dashboard/refunds");
            exit;
        }

        if (empty($transactionId)) {
            Session::flash('error', 'Transaction ID is required');
            header("Location: /admin/dashboard/refunds");
            exit;
        }

        $refund = $this->financeModel->getRefundById($refundId);
        if (!$refund || $refund['STATUS'] !== 'APPROVED') {
            Session::flash('error', 'Invalid refund request or request not approved');
            header("Location: /admin/dashboard/refunds");
            exit;
        }

        $this->financeModel->beginTransaction();

        try {
            $refundSuccess = $this->financeModel->markRefundAsProcessed($refundId, $adminNotes);

            $processedBy = $this->authModel->getById($_SESSION['user_id']);

            if (!$refundSuccess) {
                throw new Exception('Failed to update refund status');
            }

            $transactionData = [
                'transaction_id' => $this->financeModel->generateTransactionId('RF'),
                'user_id' => $refund['BUYER_ID'],
                'amount' => $refund['AMOUNT'],
                'currency' => 'BDT',
                'transaction_type' => 'PAYOUT',
                'reference_type' => 'REFUND',
                'reference_id' => $refundId,
                'payment_method' => $refund['METHOD'],
                'status' => 'SUCCESS',
                'description' => 'Refund for Order #' . $refund['ORDER_ID'] . ' processed via ' . $processedBy['NAME'],
                'gateway_response' => json_encode(['trx_id' => $transactionId]),
                'message' => 'Refund processed successfully',
                'metadata' => json_encode([
                    'refund_id' => $refundId,
                    'order_id' => $refund['ORDER_ID'],
                    'transaction_id' => $transactionId,
                    'account_details' => $refund['ACCOUNT_DETAILS'],
                    'reason' => $refund['REASON'],
                    'processed_by' => $processedBy['NAME'],
                    'processed_user_id' => $processedBy['USER_ID'],
                    'processed_user_email' => $processedBy['EMAIL'],
                    'admin_notes' => $adminNotes,
                ])
            ];

            $transactionSuccess = $this->financeModel->recordTransaction($transactionData);

            if (!$transactionSuccess) {
                throw new Exception('Failed to record payment transaction');
            }

            $this->financeModel->commitTransaction();

            Session::flash('success', 'Refund processed successfully');
        } catch (Exception $e) {
            $this->financeModel->rollbackTransaction();

            error_log('Error processing refund: ' . $e->getMessage());
            Session::flash('error', 'Failed to process refund: ' . $e->getMessage());
        }

        header("Location: /admin/dashboard/refunds");
        exit;
    }


    // SETTINGS
    public function accountSettings()
    {
        if (!AuthHelper::isLoggedIn('admin')) {
            header("Location: /login");
            exit;
        }

        $user = $this->authModel->getById(Session::get('user_id'));

        CSRF::generateToken();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $token = $_POST['csrf_token'] ?? '';

            if (!CSRF::validateToken($token)) {
                Session::flash('error', "Invalid request. Please try again.");
                header("Location: /admin/dashboard/settings");
                exit;
            }

            switch ($action) {
                case 'update_profile':
                    $this->updateProfile($_POST, $_FILES);
                    break;
                case 'update_email':
                    $this->updateEmail($_POST);
                    break;
                case 'update_password':
                    $this->updatePassword($_POST);
                    break;
            }

            header("Location: /admin/dashboard/settings");
            exit;
        }

        return [
            'title' => 'Account Management',
            'page' => 'settings',
            'currentUser' => $user,
            'viewFile' => BASE_PATH . '/src/views/pages/admin/settings.php',
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

    private function updateEmail($postData)
    {
        $userId = Session::get('user_id');
        $currentEmail = $postData['current_email'] ?? '';
        $newEmail = trim($postData['new_email'] ?? '');
        $password = $postData['password'] ?? '';

        // Validate inputs
        if (empty($currentEmail) || empty($newEmail) || empty($password)) {
            Session::flash('error', 'All fields are required');
            return false;
        }

        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Invalid email format');
            return false;
        }

        // Verify current user
        $user = $this->authModel->getById($userId);
        if ($user['EMAIL'] !== $currentEmail) {
            Session::flash('error', 'Current email does not match');
            return false;
        }

        // Verify password
        if (!password_verify($password, $user['PASSWORD_HASH'])) {
            Session::flash('error', 'Invalid password');
            return false;
        }

        // Check if new email already exists
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

        // Validate inputs
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

        // Verify current password
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
