<?php

$routes = [

    // Public pages
    ''          => ['controller' => 'PageController', 'method' => 'home'],
    'home'      => ['controller' => 'PageController', 'method' => 'home'],
    'business'      => ['controller' => 'PageController', 'method' => 'business'],
    'kitchens'  => ['controller' => 'PageController', 'method' => 'kitchens'],
    'dishes'    => ['controller' => 'PageController', 'method' => 'dishes'],
    'about'     => ['controller' => 'PageController', 'method' => 'about'],
    'contact'   => ['controller' => 'PageController', 'method' => 'contact'],
    'reviews' => ['controller' => 'PageController', 'method' => 'submitTiffinCraftReview'],

    'dishe/review'    => ['controller' => 'ReviewController', 'method' => 'handleItemReview'],
    'kitchen/review' => ['controller' => 'ReviewController', 'method' => 'handleKitchenReview', 'role' => 'buyer'],

    // Auth
    'login'     => ['controller' => 'AuthController', 'method' => 'login'],
    'business/login'     => ['controller' => 'AuthController', 'method' => 'login'],
    'register'  => ['controller' => 'AuthController', 'method' => 'register'],
    'business/register'  => ['controller' => 'AuthController', 'method' => 'register'],
    'logout'    => ['controller' => 'AuthController', 'method' => 'logout', 'role' => 'any'],
    'verify'    => ['controller' => 'AuthController', 'method' => 'verifyEmail'],
    'resend-verification'    => ['controller' => 'AuthController', 'method' => 'resendVerification'],
    'forgot-password'    => ['controller' => 'AuthController', 'method' => 'forgotPassword'],
    'reset-password'    => ['controller' => 'AuthController', 'method' => 'resetPassword'],

    // Buyer
    'settings' => ['controller' => 'BuyerController', 'method' => 'accountSettings', 'role' => 'buyer'],
    'cart' => ['controller' => 'BuyerController', 'method' => 'manageCart', 'role' => 'buyer'],
    'cart/add' => ['controller' => 'BuyerController', 'method' => 'addToCart', 'role' => 'buyer'],
    'cart/update' => ['controller' => 'BuyerController', 'method' => 'updateQuantity', 'role' => 'buyer'],
    'cart/prepare' => ['controller' => 'BuyerController', 'method' => 'prepareCart', 'role' => 'buyer'],

    'checkout' => ['controller' => 'PaymentController', 'method' => 'processOrderCheckout', 'role' => 'buyer'],
    'checkout/callback' => ['controller' => 'PaymentController', 'method' => 'orderPaymentCallback', 'role' => 'buyer'],

    'orders' => ['controller' => 'BuyerController', 'method' => 'manageOrders', 'role' => 'buyer'],
    'orders/cancel' => ['controller' => 'BuyerController', 'method' => 'cancelOrder', 'role' => 'buyer'],
    'orders/delete' => ['controller' => 'BuyerController', 'method' => 'deleteOrder', 'role' => 'buyer'],
    'orders/clear-all' => ['controller' => 'BuyerController', 'method' => 'clearAllOrders', 'role' => 'buyer'],
    'orders/request-refund' => ['controller' => 'BuyerController', 'method' => 'refundRequests', 'role' => 'buyer'],
    'refunds' => ['controller' => 'BuyerController', 'method' => 'manageRefunds', 'role' => 'buyer'],
    'refunds/cancel' => ['controller' => 'BuyerController', 'method' => 'cancelRefund', 'role' => 'buyer'],
    'favorites' => ['controller' => 'BuyerController', 'method' => 'manageFavorites', 'role' => 'buyer'],
    'favorites/toggle' => ['controller' => 'BuyerController', 'method' => 'toggleFavorite', 'role' => 'buyer'],



    // Admin
    'admin'       => ['controller' => 'AdminController', 'method' => 'dashboard', 'role' => 'admin'],
    'admin/dashboard'       => ['controller' => 'AdminController', 'method' => 'dashboard', 'role' => 'admin'],
    'admin/dashboard/users'       => ['controller' => 'AdminController', 'method' => 'manageUsers', 'role' => 'admin'],
    'admin/dashboard/kitchens'       => ['controller' => 'AdminController', 'method' => 'manageKitchens', 'role' => 'admin'],
    'admin/dashboard/categories'       => ['controller' => 'AdminController', 'method' => 'manageCategories', 'role' => 'admin'],
    'admin/dashboard/areas'       => ['controller' => 'AdminController', 'method' => 'manageAreas', 'role' => 'admin'],
    'admin/dashboard/reviews'       => ['controller' => 'AdminController', 'method' => 'manageReviews', 'role' => 'admin'],
    'admin/dashboard/subscriptions'       => ['controller' => 'AdminController', 'method' => 'manageSubscriptions', 'role' => 'admin'],
    'admin/dashboard/settings'       => ['controller' => 'AdminController', 'method' => 'accountSettings', 'role' => 'admin'],

    'admin/dashboard/refunds'       => ['controller' => 'AdminController', 'method' => 'manageRefunds', 'role' => 'admin'],
    'admin/dashboard/withdrawals'       => ['controller' => 'AdminController', 'method' => 'manageWithdrawals', 'role' => 'admin'],
    'admin/dashboard/transactions'       => ['controller' => 'AdminController', 'method' => 'viewTransactions', 'role' => 'admin'],

    // New Seller
    'business/dashboard/kitchen-setup'    => ['controller' => 'SellerController', 'method' => 'kitchenSetup', 'role' => 'seller'],
    'business/dashboard/select-plan'    => ['controller' => 'SellerController', 'method' => 'planSelection', 'role' => 'seller'],
    // 'business/dashboard/subscription/payment'    => ['controller' => 'PaymentController', 'method' => 'processPayment', 'role' => 'seller'],
    // 'business/dashboard/subscription/callback'    => ['controller' => 'PaymentController', 'method' => 'paymentCallback', 'role' => 'seller'],
    'business/dashboard/subscription/payment'    => ['controller' => 'PaymentController', 'method' => 'processSubscriptionPayment', 'role' => 'seller'],
    'business/dashboard/subscription/callback'    => ['controller' => 'PaymentController', 'method' => 'subscriptionPaymentCallback', 'role' => 'seller'],

    // Seller
    'business/dashboard'    => ['controller' => 'SellerController', 'method' => 'dashboard', 'role' => 'seller'],
    'business/dashboard/orders'    => ['controller' => 'SellerController', 'method' => 'manageOrders', 'role' => 'seller'],
    'business/dashboard/menu-items'    => ['controller' => 'SellerController', 'method' => 'manageMenu', 'role' => 'seller'],
    'business/dashboard/subscriptions'    => ['controller' => 'SellerController', 'method' => 'manageSubscription', 'role' => 'seller'],
    'business/dashboard/service-areas'    => ['controller' => 'SellerController', 'method' => 'manageServiceAreas', 'role' => 'seller'],
    'business/dashboard/reviews'    => ['controller' => 'SellerController', 'method' => 'manageReviews', 'role' => 'seller'],
    'business/dashboard/reviews/report'    => ['controller' => 'SellerController', 'method' => 'manageReviews', 'role' => 'seller'],
    'business/dashboard/withdrawals'    => ['controller' => 'SellerController', 'method' => 'manageWithdrawals', 'role' => 'seller'],
    'business/dashboard/analytics'    => ['controller' => 'SellerController', 'method' => 'manageAnalytics', 'role' => 'seller'],
    'business/dashboard/settings'       => ['controller' => 'SellerController', 'method' => 'accountSettings', 'role' => 'seller'],
];
