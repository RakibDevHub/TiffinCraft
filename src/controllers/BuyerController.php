<?php

class BuyerController
{
    private $conn;
    private $authModel;
    private $cartModel;
    private $orderModel;
    private $refundModel;
    private $favoriteModel;
    private $serviceAreaModel;


    public function __construct()
    {
        $this->conn = Database::getConnection();
        $this->authModel = new Auth($this->conn);
        $this->cartModel = new Cart($this->conn);
        $this->orderModel = new Order($this->conn);
        $this->refundModel = new Refund($this->conn);
        $this->favoriteModel = new Favorite($this->conn);
        $this->serviceAreaModel = new ServiceArea($this->conn);
    }

    public function accountSettings()
    {
        if (!AuthHelper::isLoggedIn('buyer')) {
            header("Location: /login");
            exit;
        }

        $userId = Session::get('user_id');
        $user = $this->authModel->getById($userId);

        CSRF::generateToken();

        Session::regenerate();
        Session::set('user_id', $user['USER_ID']);
        Session::set('user_name', $user['NAME']);
        Session::set('user_role', strtolower($user['ROLE']));
        Session::set('user_image', strtolower($user['PROFILE_IMAGE']));
        Session::set('last_activity', time());

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $token = $_POST['csrf_token'] ?? '';

            if (!CSRF::validateToken($token)) {
                Session::flash('error', "Invalid request. Please try again.");
                header("Location: /settings");
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

            header("Location: /settings");
            exit;
        }

        return [
            'title' => 'Account Settings',
            'page' => 'settings',
            'currentUser' => $user,
            'viewFile' => BASE_PATH . '/src/views/pages/buyer/settings.php',
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

    public function manageCart()
    {
        if (!AuthHelper::isLoggedIn('buyer')) {
            header("Location: /login");
            exit;
        }

        $userId = Session::get('user_id');
        $cartItems = $this->cartModel->getCartItems($userId);
        $totalAmount = $this->cartModel->getCartTotal($userId);

        $activeKitchenId = $_GET['kitchen'] ?? null;

        $kitchenGroups = [];
        $kitchenIds = [];

        foreach ($cartItems as $item) {
            $kitchenId = $item['KITCHEN_ID'];
            if (!isset($kitchenGroups[$kitchenId])) {
                $kitchenGroups[$kitchenId] = [
                    'kitchen_id' => $kitchenId,
                    'kitchen_name' => $item['KITCHEN_NAME'],
                    'items' => [],
                    'total' => 0,
                    'service_areas' => $this->serviceAreaModel->getKitchenServiceArea($kitchenId)
                ];

                $kitchenInfo = $this->cartModel->getKitchenInfo($kitchenId);
                if ($kitchenInfo) {
                    $kitchenGroups[$kitchenId]['address'] = $kitchenInfo['ADDRESS'] ?? '';
                    $kitchenGroups[$kitchenId]['signature_dish'] = $kitchenInfo['SIGNATURE_DISH'] ?? '';
                }

                $kitchenIds[] = $kitchenId;
            }

            $itemTotal = $item['PRICE'] * $item['QUANTITY'];
            $kitchenGroups[$kitchenId]['items'][] = $item;
            $kitchenGroups[$kitchenId]['total'] += $itemTotal;
        }

        $index = 1;
        foreach ($kitchenGroups as &$kitchenGroup) {
            $kitchenGroup['index'] = $index++;
        }

        $hasMultipleKitchens = count($kitchenGroups) > 1;

        if (!$activeKitchenId && !empty($kitchenGroups)) {
            $firstKitchen = reset($kitchenGroups);
            $activeKitchenId = $firstKitchen['kitchen_id'];
        }

        if ($activeKitchenId && !isset($kitchenGroups[$activeKitchenId])) {
            $activeKitchenId = null;
            if (!empty($kitchenGroups)) {
                $firstKitchen = reset($kitchenGroups);
                $activeKitchenId = $firstKitchen['kitchen_id'];
            }
        }

        $selectedArea = Session::get('selected_delivery_area') ?? '';
        $kitchenInfo = null;
        $deliveryFee = 0;

        if ($activeKitchenId && isset($kitchenGroups[$activeKitchenId])) {
            $activeKitchen = $kitchenGroups[$activeKitchenId];
            $kitchenInfo = $this->cartModel->getKitchenInfo($activeKitchenId);

            if ($selectedArea) {
                $deliveryFee = $this->serviceAreaModel->getDeliveryFeeForKitchenArea($activeKitchenId, $selectedArea);
            }
        }

        CSRF::generateToken();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $token = $_POST['csrf_token'] ?? '';

            if (!CSRF::validateToken($token)) {
                Session::flash('error', "Invalid request. Please try again.");
                header("Location: /cart");
                exit;
            }

            switch ($action) {
                case 'clear_cart':
                    $this->clearCart();
                    break;
                case 'remove_item':
                    $this->removeFromCart($_POST);
                    break;
                case 'remove_kitchen_items':
                    $this->removeKitchenItems($_POST);
                    break;
                default:
                    Session::flash('error', "Invalid action.");
            }

            header("Location: /cart");
            exit;
        }

        return [
            'title' => 'Shopping Cart',
            'page' => 'cart',
            'cartItems' => $cartItems,
            'totalAmount' => $totalAmount,
            'deliveryFee' => $deliveryFee,
            'selectedArea' => $selectedArea,
            'kitchenInfo' => $kitchenInfo,
            'hasMultipleKitchens' => $hasMultipleKitchens,
            'kitchenGroups' => $kitchenGroups,
            'activeKitchenId' => $activeKitchenId,
            'viewFile' => BASE_PATH . '/src/views/pages/buyer/cart.php',
        ];
    }

    public function addToCart()
    {
        if (!AuthHelper::isLoggedIn('buyer')) {
            Session::flash('error', 'Please login to add items to cart');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
            exit;
        }

        $userId = Session::get('user_id');
        $dishId = $_POST['dish_id'] ?? null;
        $quantity = (int)($_POST['quantity'] ?? 1);

        if (!$dishId || $quantity < 1 || $quantity > 50) {
            Session::flash('error', 'Invalid request');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
            exit;
        }

        $result = $this->cartModel->addItemToCart($userId, $dishId, $quantity);

        if ($result['success']) {
            Session::flash('success', $result['message']);
        } else {
            Session::flash('error', $result['message']);
        }

        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit;
    }

    public function updateQuantity()
    {
        if (!AuthHelper::isLoggedIn('buyer')) {
            Session::flash('error', 'Please login to update cart');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
            exit;
        }

        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
            exit;
        }

        $userId = Session::get('user_id');
        $dishId = $_POST['dish_id'] ?? null;
        $action = $_POST['action'] ?? '';

        if (!$dishId) {
            Session::flash('error', 'Invalid dish ID');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
            exit;
        }

        try {
            $currentQuantity = $this->cartModel->getItemQuantity($userId, $dishId);

            if ($action === 'remove' || ($action === 'decrease' && $currentQuantity <= 1)) {
                $result = $this->cartModel->removeFromCart($userId, $dishId);
            } elseif ($action === 'increase') {
                $newQuantity = $currentQuantity + 1;
                $result = $this->cartModel->updateCartItem($userId, $dishId, $newQuantity);
            } elseif ($action === 'decrease') {
                $newQuantity = $currentQuantity - 1;
                $result = $this->cartModel->updateCartItem($userId, $dishId, $newQuantity);
            } else {
                Session::flash('error', 'Invalid action');
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
                exit;
            }

            if ($result['success']) {
                Session::flash('success', $result['message']);

                if (isset($result['should_remove']) && $result['should_remove']) {
                    $this->cartModel->removeFromCart($userId, $dishId);
                }

                if (isset($result['max_available'])) {
                    $this->cartModel->updateCartItem($userId, $dishId, $result['max_available']);
                    Session::flash('info', "Quantity adjusted to maximum available: {$result['max_available']}");
                }
            } else {
                Session::flash('error', $result['message']);
            }
        } catch (Exception $e) {
            error_log("Update cart error: " . $e->getMessage());
            Session::flash('error', 'An error occurred while updating cart');
        }

        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit;
    }

    public function prepareCart()
    {
        if (!AuthHelper::isLoggedIn('buyer')) {
            header("Location: /login");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
                Session::flash('error', 'Invalid request');
                header("Location: /cart");
                exit;
            }

            $deliveryAddress = trim($_POST['delivery_address'] ?? '');
            $contactPhone = trim($_POST['contact_phone'] ?? '');
            $specialInstructions = trim($_POST['special_instructions'] ?? '');
            $kitchenId = $_POST['kitchen_id'] ?? '';
            $areaId = $_POST['area_id'] ?? '';

            // Validate delivery address
            if (empty($deliveryAddress) || strlen($deliveryAddress) < 10) {
                Session::flash('error', 'Please provide a detailed delivery address (at least 10 characters)');
                header("Location: /cart?kitchen=" . $kitchenId);
                exit;
            }

            // Validate phone number
            if (empty($contactPhone) || !preg_match("/^01[0-9]{9}$/", $contactPhone)) {
                Session::flash('error', 'Please enter a valid 11-digit Bangladeshi mobile number starting with 01');
                header("Location: /cart?kitchen=" . $kitchenId);
                exit;
            }

            // Validate area selection
            if (empty($areaId)) {
                Session::flash('error', 'Please select a delivery area');
                header("Location: /cart?kitchen=" . $kitchenId);
                exit;
            }

            // Save ALL delivery info to session
            Session::set('selected_delivery_area', $areaId);
            Session::set('delivery_address', $deliveryAddress);
            Session::set('contact_phone', $contactPhone);
            Session::set('special_instructions', $specialInstructions);

            // Redirect to checkout page WITH kitchen ID
            header("Location: /checkout?kitchen=" . $kitchenId);
            exit;
        }

        header("Location: /cart");
        exit;
    }

    private function clearCart()
    {
        $userId = Session::get('user_id');

        try {
            $result = $this->cartModel->clearCart($userId);

            if ($result) {
                Session::flash('success', 'Cart cleared successfully');
            } else {
                Session::flash('error', 'Failed to clear cart');
            }
        } catch (Exception $e) {
            error_log("Clear cart error: " . $e->getMessage());
            Session::flash('error', 'An error occurred while clearing cart');
        }

        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit;
    }

    private function removeFromCart($postData)
    {
        $userId = Session::get('user_id');
        $dishId = $postData['dish_id'] ?? null;

        try {
            $result = $this->cartModel->removeFromCart($userId, $dishId);

            if ($result) {
                Session::flash('success', 'Item removed from cart successfully');
            } else {
                Session::flash('error', 'Failed to remove from cart');
            }
        } catch (Exception $e) {
            error_log("Remove from cart error: " . $e->getMessage());
            Session::flash('error', 'An error occurred while removing item');
        }

        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit;
    }

    private function removeKitchenItems($postData)
    {
        $userId = Session::get('user_id');
        $kitchenId = $postData['kitchen_id'] ?? 0;

        try {
            $result = $this->cartModel->removeKitchenItemsFromCart($userId, $kitchenId);

            if ($result) {
                Session::flash('success', 'All items removed from kitchen successfully');
            } else {
                Session::flash('error', 'Failed to remove kitchen items');
            }
        } catch (Exception $e) {
            error_log("Remove from cart error: " . $e->getMessage());
            Session::flash('error', 'An error occurred while removing item');
        }

        header("Location: /cart");
        exit;
    }

    public function manageOrders()
    {
        if (!AuthHelper::isLoggedIn('buyer')) {
            header("Location: /login");
            exit;
        }

        $userId = Session::get('user_id');

        $statusFilter = $_GET['status'] ?? 'all';
        $searchTerm = $_GET['search'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $result = $this->orderModel->getBuyerOrders($userId, $statusFilter, $searchTerm, $limit, $offset);

        $orders = $result['orders'];

        foreach ($orders as &$order) {
            $order['REFUND_STATUS'] = $this->refundModel->getRefundStatus($order['ORDER_ID']);
        }

        return [
            'title' => 'My Orders',
            'page' => 'buyer-orders',
            'orders' => $orders,
            'statusFilter' => $statusFilter,
            'searchTerm' => $searchTerm,
            'currentPage' => $page,
            'totalPages' => ceil($result['total'] / $limit),
            'totalItems' => $result['total'],
            'viewFile' => BASE_PATH . '/src/views/pages/buyer/orders.php',
        ];
    }

    public function cancelOrder()
    {
        return $this->handleOrderAction('cancel');
    }

    public function deleteOrder()
    {
        return $this->handleOrderAction('delete');
    }

    public function clearAllOrders()
    {
        if (!AuthHelper::isLoggedIn('buyer')) {
            header("Location: /login");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Session::flash('error', 'Invalid request method.');
            header("Location: /orders");
            exit;
        }

        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid CSRF token.');
            header("Location: /orders");
            exit;
        }


        $userId = Session::get('user_id');

        $result = $this->orderModel->clearOrderHistory($userId);

        if ($result) {
            Session::flash('success', 'All order history cleared successfully.');
        } else {
            Session::flash('error', 'Unable to clear order history.');
        }

        header("Location: /orders");
        exit;
    }

    private function handleOrderAction($actionType)
    {
        if (!AuthHelper::isLoggedIn('buyer')) {
            header("Location: /login");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Session::flash('error', 'Invalid request method.');
            header("Location: /orders");
            exit;
        }

        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid CSRF token.');
            header("Location: /orders");
            exit;
        }

        $orderId = (int)($_POST['order_id'] ?? 0);
        $userId = Session::get('user_id');

        if ($orderId <= 0) {
            Session::flash('error', 'Invalid order.');
            header("Location: /orders");
            exit;
        }

        if ($actionType === 'cancel') {
            $confirmation = $_POST['confirmation'] ?? '';

            if ($confirmation !== 'CANCEL') {
                Session::flash('error', 'You must type CANCEL to confirm.');
                header("Location: /orders");
                exit;
            }

            $result = $this->orderModel->cancelBuyerOrder($orderId, $userId);

            if ($result) {
                $message = 'Order cancelled successfully.';
            } else {
                $message = 'Unable to cancel this order.';
            }
        } elseif ($actionType === 'delete') {
            $result = $this->orderModel->hideBuyerOrder($orderId, $userId);

            if ($result) {
                $message = 'Order deleted successfully.';
            } else {
                $message = 'Unable to delete this order.';
            }
        } else {
            Session::flash('error', 'Invalid action.');
            header("Location: /orders");
            exit;
        }

        Session::flash($result ? 'success' : 'error', $message);
        header("Location: /orders");
        exit;
    }

    public function manageFavorites()
    {
        if (!AuthHelper::isLoggedIn('buyer')) {
            header("Location: /login");
            exit;
        }

        $userId = Session::get('user_id');

        $typeFilter = $_GET['type'] ?? 'all';
        $searchTerm = $_GET['search'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = 12;
        $offset = ($page - 1) * $limit;

        $result = $this->favoriteModel->getUserFavorites(
            $userId,
            $typeFilter,
            $searchTerm,
            $limit,
            $offset
        );

        $favorites = $result['items'] ?? [];
        $totalItems = $result['total'] ?? 0;
        $totalPages = $result['total_pages'] ?? 1;

        return [
            'title' => 'My Favorites',
            'page' => 'buyer-favorites',
            'favorites' => $favorites,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'viewFile' => BASE_PATH . '/src/views/pages/buyer/favorites.php',
        ];
    }

    public function toggleFavorite()
    {
        if (!AuthHelper::isLoggedIn('buyer')) {
            Session::flash('error', 'Please login to manage favorites');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
            exit;
        }

        $userId = Session::get('user_id');
        $referenceId = $_POST['reference_id'] ?? null;
        $referenceType = $_POST['reference_type'] ?? 'ITEM';

        if (!$referenceId) {
            Session::flash('error', 'Invalid request');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
            exit;
        }

        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token. Please try again.');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
            exit;
        }

        try {
            $result = $this->favoriteModel->toggleFavorite($userId, $referenceId, $referenceType);

            if ($result) {
                // Check if it was added or removed
                $isFavorite = $this->favoriteModel->isItemInFavorites($userId, $referenceId, $referenceType);
                $message = $isFavorite ? 'Added to favorites!' : 'Removed from favorites';
                Session::flash('success', $message);
            } else {
                Session::flash('error', 'Failed to update favorites');
            }
        } catch (Exception $e) {
            error_log("Toggle favorite error: " . $e->getMessage());
            Session::flash('error', 'An error occurred while updating favorites');
        }

        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit;
    }

    public function clearAllFavorites()
    {
        if (!AuthHelper::isLoggedIn('buyer')) {
            Session::flash('error', 'Please login to manage favorites');
            header('Location: /login');
            exit;
        }

        $userId = Session::get('user_id');

        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token. Please try again.');
            header('Location: /favorites');
            exit;
        }

        try {
            $result = $this->favoriteModel->clearAllUserFavorites($userId);

            if ($result) {
                Session::flash('success', 'All favorites have been removed successfully!');
            } else {
                Session::flash('error', 'Failed to clear favorites');
            }
        } catch (Exception $e) {
            error_log("Clear all favorites error: " . $e->getMessage());
            Session::flash('error', 'An error occurred while clearing favorites');
        }

        header('Location: /favorites');
        exit;
    }

    public function manageRefunds()
    {
        if (!AuthHelper::isLoggedIn('buyer')) {
            header("Location: /login");
            exit;
        }

        $userId = Session::get('user_id');

        $statusFilter = $_GET['status'] ?? 'all';
        $searchTerm = $_GET['search'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $result = $this->refundModel->getBuyerRefunds($userId, $statusFilter, $searchTerm, $limit, $offset);
        $stats = $this->refundModel->getBuyerRefundStats($userId);

        return [
            'title' => 'My Refunds',
            'page' => 'refunds',
            'refunds' => $result['refunds'],
            'stats' => $stats,
            'statusFilter' => $statusFilter,
            'searchTerm' => $searchTerm,
            'currentPage' => $page,
            'totalPages' => ceil($result['total'] / $limit),
            'totalItems' => $result['total'],
            'csrfToken' => CSRF::generateToken(),
            'viewFile' => BASE_PATH . '/src/views/pages/buyer/refunds.php',
        ];
    }

    public function refundRequests()
    {
        if (!AuthHelper::isLoggedIn('buyer')) {
            header("Location: /login");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /orders");
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!CSRF::validateToken($token)) {
            Session::flash('error', "Invalid request. Please try again.");
            header("Location: /orders");
            exit;
        }

        $orderId = $_POST['order_id'] ?? '';
        $reason = $_POST['reason'] ?? '';
        $refundMethod = $_POST['method'] ?? '';
        $mobileNumber = $_POST['mobile_number'] ?? '';

        if (!$orderId || empty($reason) || empty($mobileNumber) || empty($refundMethod)) {
            Session::flash('error', "Please provide all required information.");
            header("Location: /orders");
            exit;
        }

        if (!preg_match('/^01[3-9]\d{8}$/', $mobileNumber)) {
            Session::flash('error', "Please enter a valid Bangladeshi mobile number (e.g., 01XXXXXXXXX).");
            header("Location: /orders");
            exit;
        }

        $allowedMethods = [
            'bKash Personal',
            'bKash Agent',
            'Nagad Personal',
            'Nagad Agent',
            'Rocket Personal',
            'Rocket Agent'
        ];

        if (!in_array($refundMethod, $allowedMethods)) {
            Session::flash('error', "Invalid payment method selected.");
            header("Location: /orders");
            exit;
        }

        $userId = Session::get('user_id');

        $eligibility = $this->refundModel->isOrderEligibleForRefund($orderId, $userId);

        if (!$eligibility['eligible']) {
            Session::flash('error', $eligibility['message']);
            header("Location: /orders");
            exit;
        }

        if ($this->refundModel->refundRequestExists($orderId)) {
            Session::flash('error', 'A refund request already exists for this order.');
            header("Location: /orders");
            exit;
        }

        $refundData = [
            'order_id' => $orderId,
            'buyer_id' => $userId,
            'amount' => $eligibility['max_amount'],
            'reason' => $reason,
            'method' => $refundMethod,
            'mobile_number' => $mobileNumber
        ];

        $success = $this->refundModel->createRefundRequest($refundData);

        if ($success) {
            $cancelledBy = $eligibility['cancelled_by'] ?? '';
            $refundAmount = number_format($eligibility['max_amount'], 2);

            if ($cancelledBy === 'SELLER') {
                $message = "Refund request submitted successfully! You will receive ৳{$refundAmount} (full amount).";
            } elseif ($cancelledBy === 'BUYER') {
                $serviceCharge = number_format($eligibility['calculation']['service_charge'], 2);
                $message = "Refund request submitted successfully! You will receive ৳{$refundAmount} (minus ৳{$serviceCharge} service charge).";
            } else {
                $message = "Refund request submitted successfully! You will receive ৳{$refundAmount}.";
            }

            Session::flash('success', $message);
        } else {
            Session::flash('error', "Failed to submit refund request. Please try again.");
        }

        header("Location: /orders");
        exit;
    }

    public function cancelRefund()
    {
        if (!AuthHelper::isLoggedIn('buyer')) {
            header("Location: /login");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /refunds");
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!CSRF::validateToken($token)) {
            Session::flash('error', "Invalid request. Please try again.");
            header("Location: /refunds");
            exit;
        }

        $refundId = $_POST['refund_id'] ?? '';
        $userId = Session::get('user_id');

        if (empty($refundId)) {
            Session::flash('error', 'Invalid refund request');
            header('Location: /refunds');
            exit;
        }

        $result = $this->refundModel->cancelRefundRequest($refundId, $userId);

        if ($result['success']) {
            Session::flash('success', $result['message']);
        } else {
            Session::flash('error', $result['message']);
        }

        header('Location: /refunds');
        exit;
    }
}
