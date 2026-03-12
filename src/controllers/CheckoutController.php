<?php

class CheckoutController
{
    private $conn;
    private $authModel;
    private $cartModel;
    private $paymentModel;
    private $serviceAreaModel;

    public function __construct()
    {
        $this->conn = Database::getConnection();
        $this->authModel = new Auth($this->conn);
        $this->cartModel = new Cart($this->conn);
        $this->paymentModel = new Payment($this->conn);
        $this->serviceAreaModel = new ServiceArea($this->conn);
    }

    // * ORDER PAYMENT FUNCTIONS
    public function processOrderCheckout()
    {

        if (!AuthHelper::isLoggedIn('buyer')) {
            header("Location: /login");
            exit;
        }

        $userId = Session::get('user_id');

        $kitchenId = $_GET['kitchen'] ?? $_POST['kitchen_id'] ?? null;

        if (!$kitchenId) {
            Session::flash('error', 'Please select a kitchen to order from');
            header("Location: /cart");
            exit;
        }

        $cartItems = $this->cartModel->getCartItemsByKitchen($userId, $kitchenId);

        if (empty($cartItems)) {
            Session::flash('error', 'No items found in your cart for this kitchen');
            header("Location: /cart");
            exit;
        }

        $totalAmount = 0;
        foreach ($cartItems as $item) {
            $totalAmount += $item['PRICE'] * $item['QUANTITY'];
        }

        $selectedArea = Session::get('selected_delivery_area');
        if (!$selectedArea) {
            Session::flash('error', 'Please select a delivery area first');
            header("Location: /cart?kitchen=" . $kitchenId);
            exit;
        }

        $kitchenInfo = $this->cartModel->getKitchenInfo($kitchenId);

        $deliveryFee = $this->serviceAreaModel->getDeliveryFeeForKitchenArea($kitchenId, $selectedArea);
        $selectedAreaData = null;

        $serviceAreas = $this->serviceAreaModel->getKitchenServiceArea($kitchenId);
        foreach ($serviceAreas as $area) {
            if ($area['AREA_ID'] == $selectedArea) {
                $selectedAreaData = $area;
                break;
            }
        }

        if ($selectedAreaData && $selectedAreaData['MIN_ORDER'] > 0 && $totalAmount < $selectedAreaData['MIN_ORDER']) {
            Session::flash('error', 'Minimum order amount not met for selected delivery area');
            header("Location: /cart?kitchen=" . $kitchenId);
            exit;
        }

        $grandTotal = $totalAmount + $deliveryFee;

        $user = $this->authModel->getById($userId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';

            if (!CSRF::validateToken($token)) {
                Session::flash('error', "Invalid request. Please try again.");
                header("Location: /checkout?kitchen=" . $kitchenId);
                exit;
            }

            return $this->prepareOrderData($userId, $cartItems, $kitchenInfo, $selectedAreaData, $totalAmount, $deliveryFee, $grandTotal, $_POST, $user);
        }

        CSRF::generateToken();

        return [
            'title' => 'Checkout',
            'page' => 'checkout',
            'cartItems' => $cartItems,
            'totalAmount' => $totalAmount,
            'deliveryFee' => $deliveryFee,
            'grandTotal' => $grandTotal,
            'kitchenInfo' => $kitchenInfo,
            'selectedAreaData' => $selectedAreaData,
            'currentUser' => $user,
            'viewFile' => BASE_PATH . '/src/views/pages/buyer/checkout.php',
        ];
    }

    private function prepareOrderData($userId, $cartItems, $kitchenInfo, $deliveryArea, $subtotal, $deliveryFee, $grandTotal, $postData, $user)
    {
        try {
            $orderData = [
                'buyer_id' => $userId,
                'buyer_name' => $user['NAME'],
                'buyer_email' => $user['EMAIL'],
                'buyer_phone' => $user['PHONE'] ?? $postData['contact_phone'] ?? '',
                'kitchen_id' => $kitchenInfo['KITCHEN_ID'],
                'kitchen_name' => $kitchenInfo['KITCHEN_NAME'],
                'delivery_area_id' => $deliveryArea['AREA_ID'],
                'delivery_area_name' => $deliveryArea['AREA_NAME'],
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total_amount' => $grandTotal,
                'delivery_address' => $postData['delivery_address'] ?? '',
                'special_instructions' => $postData['special_instructions'] ?? '',
                'payment_method' => $postData['payment_method'] ?? 'online',
                'cart_items' => $cartItems,
            ];

            Session::set('pending_order_data', $orderData);

            return $this->processOrderPayment($orderData);
        } catch (Exception $e) {
            Session::flash('error', 'Order processing failed: ' . $e->getMessage());
            header("Location: /checkout");
            exit;
        }
    }

    private function processOrderPayment($orderData)
    {
        try {
            $paymentData = [
                'amount' => $orderData['total_amount'],
                'currency' => 'BDT',
                'user_id' => $orderData['buyer_id'],
                'customer_name' => $orderData['buyer_name'],
                'customer_email' => $orderData['buyer_email'],
                'customer_phone' => $orderData['buyer_phone'],
                'customer_address' => $orderData['delivery_address'] ?? 'Not Provided',
                'product_name' => 'Food Order from ' . htmlspecialchars($orderData['kitchen_name']),
                'description' => 'Payment for food order from ' . htmlspecialchars($orderData['kitchen_name']),
                'success_url' => BASE_URL . '/checkout/callback',
                'fail_url' => BASE_URL . '/checkout/callback',
                'cancel_url' => BASE_URL . '/checkout/callback'
            ];

            Session::set('pending_order_data', $orderData);

            $metadata = [
                'order_data' => $orderData,
                'payment_type' => 'order'
            ];


            $redirectUrl = $this->paymentModel->orderPayment($paymentData, $metadata);

            header("Location: " . $redirectUrl);
            exit;
        } catch (Exception $e) {
            Session::remove('pending_order_data');
            Session::flash('error', 'Payment processing failed: ' . $e->getMessage());
            header("Location: /checkout");
            exit;
        }
    }

    public function orderPaymentCallback()
    {
        try {
            $status = $this->paymentModel->handleOrderPaymentCallback($_POST, $_GET);

            if ($status === 'SUCCESS') {
                Session::flash('success', 'Payment successful! Your order has been placed.');
                header("Location: /orders");
            } else {
                $pendingOrder = Session::get('pending_order_data');
                $kitchenId = $pendingOrder['kitchen_id'] ?? null;

                Session::flash('error', 'Payment failed. Please try again.');

                if ($kitchenId) {
                    header("Location: /checkout?kitchen=" . $kitchenId);
                } else {
                    header("Location: /cart");
                }
            }
        } catch (Exception $e) {
            $pendingOrder = Session::get('pending_order_data');
            $kitchenId = $pendingOrder['kitchen_id'] ?? null;

            Session::flash('error', 'Payment processing error: ' . $e->getMessage());

            if ($kitchenId) {
                header("Location: /checkout?kitchen=" . $kitchenId);
            } else {
                header("Location: /cart");
            }
        }

        exit;
    }
}
