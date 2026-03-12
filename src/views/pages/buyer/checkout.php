<?php
$cartItems = $data['cartItems'] ?? [];
$totalAmount = $data['totalAmount'] ?? 0;
$deliveryFee = $data['deliveryFee'] ?? 0;
$grandTotal = $data['grandTotal'] ?? 0;
$kitchenInfo = $data['kitchenInfo'] ?? null;
$selectedAreaData = $data['selectedAreaData'] ?? null;
$currentUser = $data['currentUser'] ?? null;

// Get data from session that was passed from cart
$deliveryAddress = Session::get('delivery_address') ?? '';
$contactPhone = Session::get('contact_phone') ?? '';
$specialInstructions = Session::get('special_instructions') ?? '';

include BASE_PATH . '/src/views/components/flash-popup.php';
?>

<main class="checkout-page">
    <section class="checkout-section">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header" style="margin-top: 2rem;">
                <h1 class="page-title">Order Confirmation</h1>
                <p class="page-subtitle">Review your order details</p>
            </div>

            <div class="checkout-container">
                <div class="checkout-layout">
                    <!-- Left Column: Order Details -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Order Summary</h3>
                        </div>
                        <div class="card-body">
                            <!-- Delivery Information -->
                            <div class="info-section">
                                <h4><i class="fas fa-map-marker-alt"></i> Delivery Information</h4>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span class="info-label">Delivery Address:</span>
                                        <span class="info-value"><?= htmlspecialchars($deliveryAddress) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Contact Phone:</span>
                                        <span class="info-value"><?= htmlspecialchars($contactPhone) ?></span>
                                    </div>
                                    <?php if (!empty($specialInstructions)): ?>
                                        <div class="info-item">
                                            <span class="info-label">Special Instructions:</span>
                                            <span class="info-value"><?= htmlspecialchars($specialInstructions) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($selectedAreaData): ?>
                                        <div class="info-item">
                                            <span class="info-label">Delivery Area:</span>
                                            <span class="info-value"><?= htmlspecialchars($selectedAreaData['AREA_NAME']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Kitchen Information -->
                            <div class="info-section">
                                <h4><i class="fas fa-store"></i> Kitchen Information</h4>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span class="info-label">Kitchen Name:</span>
                                        <span class="info-value"><?= htmlspecialchars($kitchenInfo['KITCHEN_NAME']) ?></span>
                                    </div>
                                    <?php if (!empty($kitchenInfo['ADDRESS'])): ?>
                                        <div class="info-item">
                                            <span class="info-label">Kitchen Address:</span>
                                            <span class="info-value"><?= htmlspecialchars($kitchenInfo['ADDRESS']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($kitchenInfo['SIGNATURE_DISH'])): ?>
                                        <div class="info-item">
                                            <span class="info-label">Specialty:</span>
                                            <span class="info-value"><?= htmlspecialchars($kitchenInfo['SIGNATURE_DISH']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Order Items -->
                            <div class="info-section">
                                <h4><i class="fas fa-utensils"></i> Order Items (<?= count($cartItems) ?>)</h4>
                                <div class="order-items-list">
                                    <?php foreach ($cartItems as $item): ?>
                                        <div class="order-item">
                                            <div class="item-info">
                                                <span class="item-name"><?= htmlspecialchars($item['NAME']) ?></span>
                                                <span class="item-quantity">x<?= $item['QUANTITY'] ?></span>
                                            </div>
                                            <div class="item-pricing">
                                                <span class="item-price">৳<?= number_format($item['PRICE'], 2) ?> each</span>
                                                <span class="item-total">৳<?= number_format($item['PRICE'] * $item['QUANTITY'], 2) ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Payment & Confirmation -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Payment & Confirmation</h3>
                        </div>
                        <div class="card-body">
                            <!-- Price Summary -->
                            <div class="price-summary">
                                <h4>Price Summary</h4>
                                <div class="price-row">
                                    <span>Subtotal</span>
                                    <span>৳<?= number_format($totalAmount, 2) ?></span>
                                </div>
                                <div class="price-row">
                                    <span>Delivery Fee</span>
                                    <span>৳<?= number_format($deliveryFee, 2) ?></span>
                                </div>
                                <div class="price-row total">
                                    <span>Total Amount</span>
                                    <span>৳<?= number_format($grandTotal, 2) ?></span>
                                </div>
                            </div>

                            <!-- Payment Method Selection -->
                            <div class="payment-section">
                                <h4>Select Payment Method</h4>
                                <form method="POST" action="/checkout" id="checkout-form">
                                    <input type="hidden" name="kitchen_id"
                                        value="<?= htmlspecialchars($kitchenInfo['KITCHEN_ID'] ?? '') ?>">
                                    <input type="hidden" name="area_id"
                                        value="<?= htmlspecialchars($selectedAreaData['AREA_ID'] ?? '') ?>">
                                    <input type="hidden" name="contact_phone"
                                        value="<?= htmlspecialchars($contactPhone) ?>">
                                    <input type="hidden" name="delivery_address"
                                        value="<?= htmlspecialchars($deliveryAddress) ?>">
                                    <input type="hidden" name="special_instructions"
                                        value="<?= htmlspecialchars($specialInstructions) ?>">
                                    <input type="hidden" name="csrf_token"
                                        value="<?= htmlspecialchars($csrfToken) ?>">

                                    <div class="payment-methods">
                                        <label class="payment-method">
                                            <input type="radio" name="payment_method" value="online" checked>
                                            <div class="payment-option">
                                                <i class="fas fa-credit-card"></i>
                                                <div class="payment-details">
                                                    <span class="payment-title">Online Payment</span>
                                                    <small class="payment-description">Pay securely with SSLCommerz</small>
                                                </div>
                                            </div>
                                        </label>
                                        <label class="payment-method">
                                            <input type="radio" name="payment_method" disabled="true" value="cod">
                                            <div class="payment-option">
                                                <i class="fas fa-money-bill-wave"></i>
                                                <div class="payment-details" style="position: relative; width: 100%;">
                                                    <span style="position: absolute; right: -10px; top: -10px; background-color: #fff3cd; border-radius: 15px; padding: 2px 10px; font-size: small;">Comming Soon</span>
                                                    <span class="payment-title">Cash on Delivery</span>
                                                    <small class="payment-description">Pay when you receive your order</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="action-buttons">
                                        <button type="submit" class="btn btn-primary btn-lg btn-confirm">
                                            <i class="fas fa-lock"></i>
                                            <span id="confirm-button-text">
                                                Confirm & Pay ৳<?= number_format($grandTotal, 2) ?>
                                            </span>
                                        </button>
                                        <a href="/cart" class="btn btn-outline btn-edit">
                                            <i class="fas fa-edit"></i> Edit Order
                                        </a>
                                    </div>
                                </form>
                            </div>

                            <!-- Security & Notes -->
                            <div class="additional-info">
                                <div class="security-notice">
                                    <i class="fas fa-shield-alt"></i>
                                    <span>Your payment information is secure and encrypted</span>
                                </div>
                                <div class="order-note">
                                    <i class="fas fa-info-circle"></i>
                                    <span>By confirming this order, you agree to our terms of service</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
    <?php
    $fillColor = '#FFFBEB';
    $invert = true;
    // $offset = true;

    include BASE_PATH . '/src/views/components/divider-banner.php';
    ?>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
        const confirmButtonText = document.getElementById('confirm-button-text');
        const grandTotal = <?= $grandTotal ?>;

        paymentMethods.forEach(method => {
            method.addEventListener('change', function() {
                if (this.value === 'cod') {
                    confirmButtonText.textContent = `Confirm Order - ৳${grandTotal.toFixed(2)}`;
                } else {
                    confirmButtonText.textContent = `Confirm & Pay ৳${grandTotal.toFixed(2)}`;
                }
            });
        });
    });
</script>