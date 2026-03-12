<?php
$cartItems = $data['cartItems'] ?? [];
$totalAmount = $data['totalAmount'] ?? 0;
$deliveryFee = $data['deliveryFee'] ?? 0;
$selectedArea = $data['selectedArea'] ?? '';
$kitchenInfo = $data['kitchenInfo'] ?? null;
$hasMultipleKitchens = $data['hasMultipleKitchens'] ?? false;
$kitchenGroups = $data['kitchenGroups'] ?? [];
$activeKitchenId = $data['activeKitchenId'] ?? null;
$currentUser = $data['currentUser'] ?? null;

include BASE_PATH . '/src/views/components/flash-popup.php';
?>

<main class="cart-page">
    <section class="cart-section">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">Shopping Cart</h1>
                <p class="page-subtitle">
                    <?php if ($hasMultipleKitchens): ?>
                        Order from one kitchen at a time
                    <?php else: ?>
                        Review your items and proceed to checkout
                    <?php endif; ?>
                </p>
            </div>

            <div class="cart-container">
                <?php if (empty($cartItems)): ?>
                    <!-- Empty Cart State -->
                    <div class="empty-cart-state">
                        <div class="empty-state-content">
                            <div class="empty-state-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <h2>Your cart is empty</h2>
                            <p>Add some delicious food items to get started</p>
                            <a href="/dishes" class="btn btn-primary btn-lg">
                                <i class="fas fa-utensils"></i> Browse Menu
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Cart with Items -->
                    <div class="cart-grid">
                        <div class="cart-left-column">
                            <?php if ($hasMultipleKitchens): ?>
                                <!-- Kitchen Selection -->
                                <div class="card card-b kitchen-selection-card">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-store"></i>
                                            Select Kitchen to Order From
                                        </h3>
                                        <button type="button" class="btn-clear-cart" onclick="openCartConfirmModal('clear_cart', null, 'Are you sure you want to clear your entire cart?', 'This will remove all items from all kitchens. This action cannot be undone.')">
                                            <i class="fas fa-trash"></i> Clear Cart
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div class="kitchen-tabs">
                                            <?php foreach ($kitchenGroups as $kitchenId => $kitchenData): ?>
                                                <div class="kitchen-tab <?= $activeKitchenId == $kitchenId ? 'active' : '' ?>">
                                                    <div class="kitchen-tab-content">
                                                        <a href="/cart?kitchen=<?= $kitchenId ?>" class="kitchen-tab-link">
                                                            <div class="kitchen-info">
                                                                <h4 class="kitchen-name"><?= htmlspecialchars($kitchenData['kitchen_name']) ?></h4>
                                                                <div class="kitchen-details">
                                                                    <span class="item-count">
                                                                        <i class="fas fa-box"></i>
                                                                        <?= count($kitchenData['items']) ?> items
                                                                    </span>
                                                                    <span class="kitchen-price">
                                                                        <i class="fas fa-tag"></i>
                                                                        ৳<?= number_format($kitchenData['total'], 2) ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </a>
                                                        <button type="button" class="btn-remove-kitchen" onclick="openCartConfirmModal('remove_kitchen', <?= $kitchenId ?>, 'Remove all items from <?= htmlspecialchars(addslashes($kitchenData['kitchen_name'])) ?>?', 'This will remove all items from this kitchen from your cart.')">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php if ($hasMultipleKitchens && count($kitchenGroups) > 1): ?>
                                            <div class="cart-total-summary">
                                                <span class="total-label">Total in Cart:</span>
                                                <span class="total-amount">৳<?= number_format($totalAmount, 2) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php
                            if ($hasMultipleKitchens && $activeKitchenId && isset($kitchenGroups[$activeKitchenId])) {
                                $activeKitchen = $kitchenGroups[$activeKitchenId];
                            } elseif (!$hasMultipleKitchens && !empty($kitchenGroups)) {
                                $activeKitchen = reset($kitchenGroups);
                                $activeKitchenId = $activeKitchen['kitchen_id'];
                            } else {
                                $activeKitchen = null;
                            }
                            ?>

                            <?php if ($activeKitchen): ?>
                                <!-- Active Kitchen Information -->
                                <div class="card card-b active-kitchen-card">
                                    <div class="card-header">
                                        <div class="kitchen-header-content">
                                            <div class="kitchen-title-section">
                                                <h3 class="kitchen-title">
                                                    <i class="fas fa-utensils"></i>
                                                    <?= htmlspecialchars($activeKitchen['kitchen_name']) ?>
                                                </h3>
                                                <?php if ($hasMultipleKitchens): ?>
                                                    <p class="kitchen-subtitle">
                                                        Currently ordering from this kitchen. Other kitchen items remain in your cart.
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="kitchen-status">
                                                <span class="status-badge active">
                                                    <i class="fas fa-check-circle"></i> Selected Kitchen
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($activeKitchen['signature_dish'])): ?>
                                            <div class="kitchen-feature">
                                                <span class="feature-label">Specialty:</span>
                                                <span class="feature-value"><?= htmlspecialchars($activeKitchen['signature_dish']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($activeKitchen['address'])): ?>
                                            <div class="kitchen-feature">
                                                <span class="feature-label">Address:</span>
                                                <span class="feature-value"><?= htmlspecialchars($activeKitchen['address']) ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <div class="service-areas-section">
                                            <h4 class="section-title">Available Service Areas</h4>
                                            <?php if (!empty($activeKitchen['service_areas'])): ?>
                                                <div class="service-areas-grid">
                                                    <?php foreach ($activeKitchen['service_areas'] as $area): ?>
                                                        <div class="service-area-card">
                                                            <div class="area-info">
                                                                <h5 class="area-name"><?= htmlspecialchars($area['AREA_NAME']) ?></h5>
                                                                <div class="area-details">
                                                                    <span class="delivery-fee">
                                                                        <i class="fas fa-truck"></i>
                                                                        ৳<?= number_format($area['DELIVERY_FEE'], 2) ?>
                                                                    </span>
                                                                    <?php if ($area['MIN_ORDER'] > 0): ?>
                                                                        <span class="min-order">
                                                                            <i class="fas fa-exclamation-circle"></i>
                                                                            Min: ৳<?= number_format($area['MIN_ORDER'], 2) ?>
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="no-service-areas">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <p>No service areas available for this kitchen</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Cart Items -->
                                <div class="card card-b cart-items-card">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-shopping-basket"></i>
                                            Items from this Kitchen
                                            <?php if (!empty($activeKitchen['items'])): ?>
                                                <span class="item-count-badge"><?= count($activeKitchen['items']) ?></span>
                                            <?php endif; ?>
                                        </h3>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($activeKitchen['items'])): ?>
                                            <div class="empty-kitchen-state">
                                                <div class="empty-state-icon">
                                                    <i class="fas fa-shopping-basket"></i>
                                                </div>
                                                <h4>No items in this kitchen</h4>
                                                <p>Add items from <?= htmlspecialchars($activeKitchen['kitchen_name']) ?> to proceed</p>
                                                <a href="/dishes?kitchen=<?= $activeKitchenId ?>" class="btn btn-outline">
                                                    <i class="fas fa-plus"></i> Browse Menu
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <div class="cart-items-list">
                                                <?php foreach ($activeKitchen['items'] as $item): ?>
                                                    <div class="cart-item-card">
                                                        <div class="item-image">
                                                            <img src="<?= !empty($item['ITEM_IMAGE']) ? '/uploads/menu/' . htmlspecialchars($item['ITEM_IMAGE']) : '/assets/images/default-food.jpg' ?>"
                                                                alt="<?= htmlspecialchars($item['NAME']) ?>">
                                                        </div>
                                                        <div class="item-content">
                                                            <div class="item-details">
                                                                <h4 class="item-name"><?= htmlspecialchars($item['NAME']) ?></h4>
                                                                <?php if (!empty($item['DESCRIPTION'])): ?>
                                                                    <p class="item-description"><?= htmlspecialchars($item['DESCRIPTION']) ?></p>
                                                                <?php endif; ?>
                                                                <div class="item-price-info">
                                                                    <span class="unit-price">৳<?= number_format($item['PRICE'], 2) ?> each</span>
                                                                    <span class="item-total">৳<?= number_format($item['PRICE'] * $item['QUANTITY'], 2) ?></span>
                                                                </div>
                                                            </div>
                                                            <div class="item-actions">
                                                                <form method="POST" action="/cart/update" class="quantity-form">
                                                                    <input type="hidden" name="dish_id" value="<?= $item['ITEM_ID'] ?>">
                                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                                    <div class="quantity-control">
                                                                        <button type="submit" name="action" value="decrease" class="quantity-btn decrease">
                                                                            <i class="fas fa-minus"></i>
                                                                        </button>
                                                                        <span class="quantity-display"><?= $item['QUANTITY'] ?></span>
                                                                        <button type="submit" name="action" value="increase" class="quantity-btn increase">
                                                                            <i class="fas fa-plus"></i>
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                                <button type="button" class="btn-remove-item" onclick="openCartConfirmModal('remove_item', <?= $item['ITEM_ID'] ?>, 'Remove <?= htmlspecialchars(addslashes($item['NAME'])) ?>?', 'This item will be removed from your cart.')">
                                                                    <i class="fas fa-trash"></i>
                                                                    Remove
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>

                                            <!-- Kitchen Subtotal -->
                                            <div class="kitchen-subtotal">
                                                <div class="subtotal-content">
                                                    <span class="subtotal-label">Kitchen Subtotal</span>
                                                    <span class="subtotal-amount">৳<?= number_format($activeKitchen['total'], 2) ?></span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="cart-right-column">
                            <div class="card card-b checkout-card">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-clipboard-list"></i>
                                        Order Summary
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <?php if ($activeKitchen && !empty($activeKitchen['items'])): ?>
                                        <div class="checkout-form-section">
                                            <h4 class="section-title">
                                                <i class="fas fa-shipping-fast"></i>
                                                Delivery Information
                                            </h4>

                                            <div class="form-errors" id="form-errors"></div>

                                            <form method="POST" action="/cart/prepare" id="checkout-form" novalidate>
                                                <input type="hidden" name="kitchen_id" value="<?= $activeKitchenId ?>">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                                                <div class="form-group">
                                                    <label for="area_id" class="form-label">
                                                        <i class="fas fa-map-marker-alt"></i>
                                                        Delivery Area *
                                                    </label>
                                                    <select class="form-select" id="area_id" name="area_id" required>
                                                        <option value="">Choose a delivery area</option>
                                                        <?php foreach ($activeKitchen['service_areas'] as $area): ?>
                                                            <option value="<?= $area['AREA_ID'] ?>"
                                                                <?= $selectedArea == $area['AREA_ID'] ? 'selected' : '' ?>
                                                                data-fee="<?= $area['DELIVERY_FEE'] ?>"
                                                                data-min-order="<?= $area['MIN_ORDER'] ?>">
                                                                <?= htmlspecialchars($area['AREA_NAME']) ?> - ৳<?= number_format($area['DELIVERY_FEE'], 2) ?>
                                                                <?php if ($area['MIN_ORDER'] > 0): ?>
                                                                    (Min: ৳<?= number_format($area['MIN_ORDER'], 2) ?>)
                                                                <?php endif; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <div class="form-help">Select the area where you want your food delivered</div>
                                                </div>

                                                <div class="form-group">
                                                    <label for="delivery_address" class="form-label">
                                                        <i class="fas fa-home"></i>
                                                        Delivery Address *
                                                    </label>
                                                    <textarea
                                                        id="delivery_address"
                                                        name="delivery_address"
                                                        class="form-textarea"
                                                        rows="3"
                                                        placeholder="Enter your complete delivery address with house number, road, area, etc."
                                                        required><?= htmlspecialchars(Session::get('delivery_address') ?? ($currentUser['ADDRESS'] ?? '')) ?></textarea>
                                                    <div class="form-help">Please provide detailed address for accurate delivery (minimum 10 characters)</div>
                                                </div>

                                                <!-- Contact Phone -->
                                                <div class="form-group">
                                                    <label for="contact_phone" class="form-label">
                                                        <i class="fas fa-phone"></i>
                                                        Contact Phone Number *
                                                    </label>
                                                    <input
                                                        type="tel"
                                                        id="contact_phone"
                                                        name="contact_phone"
                                                        class="form-input"
                                                        placeholder="01XXXXXXXXX"
                                                        value="<?= htmlspecialchars(Session::get('contact_phone') ?? ($currentUser['PHONE'] ?? '')) ?>"
                                                        pattern="01[0-9]{9}"
                                                        required>
                                                    <div class="form-help">11-digit Bangladeshi mobile number starting with 01</div>
                                                </div>

                                                <!-- Special Instructions -->
                                                <div class="form-group">
                                                    <label for="special_instructions" class="form-label">
                                                        <i class="fas fa-sticky-note"></i>
                                                        Special Instructions (Optional)
                                                    </label>
                                                    <textarea
                                                        id="special_instructions"
                                                        name="special_instructions"
                                                        class="form-textarea"
                                                        rows="2"
                                                        placeholder="Any special delivery instructions, building landmarks, or delivery preferences"><?= htmlspecialchars(Session::get('special_instructions') ?? '') ?></textarea>
                                                    <div class="form-help">Additional instructions for delivery</div>
                                                </div>

                                                <!-- Order Summary -->
                                                <div class="order-summary-section">
                                                    <h5 class="summary-title">Order Summary</h5>
                                                    <div class="summary-items">
                                                        <div class="summary-item">
                                                            <span class="summary-label">Subtotal</span>
                                                            <span class="summary-value" id="subtotal-display">৳<?= number_format($activeKitchen['total'], 2) ?></span>
                                                        </div>
                                                        <div class="summary-item">
                                                            <span class="summary-label">Delivery Fee</span>
                                                            <span class="summary-value" id="delivery-fee-display">৳<?= number_format($deliveryFee, 2) ?></span>
                                                        </div>
                                                        <div class="summary-divider"></div>
                                                        <div class="summary-item total">
                                                            <span class="summary-label">Total Amount</span>
                                                            <span class="summary-value" id="total-display">৳<?= number_format($activeKitchen['total'] + $deliveryFee, 2) ?></span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Checkout Actions -->
                                                <div class="checkout-actions">
                                                    <button type="submit" class="btn btn-primary btn-checkout" id="checkout-button" <?= !$selectedArea ? 'disabled' : '' ?>>
                                                        <i class="fas fa-credit-card"></i>
                                                        <span id="checkout-button-text">
                                                            <?= !$selectedArea ? 'Select Delivery Area' : 'Proceed to Checkout' ?>
                                                        </span>
                                                    </button>
                                                    <a href="/dishes?kitchen=<?= $activeKitchenId ?>" class="btn btn-secondary">
                                                        <i class="fas fa-plus-circle"></i> Add More Items
                                                    </a>
                                                </div>

                                                <?php if ($hasMultipleKitchens): ?>
                                                    <div class="info-notice">
                                                        <div class="notice-icon">
                                                            <i class="fas fa-info-circle"></i>
                                                        </div>
                                                        <div class="notice-content">
                                                            <p><strong>Note:</strong> After completing this order, you can return to your cart to order from other kitchens.</p>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <!-- No Active Kitchen State -->
                                        <div class="no-kitchen-state">
                                            <?php if ($hasMultipleKitchens): ?>
                                                <div class="state-content">
                                                    <div class="state-icon">
                                                        <i class="fas fa-store"></i>
                                                    </div>
                                                    <h4>Select a Kitchen</h4>
                                                    <p>Choose a kitchen from the left to view items and proceed with checkout.</p>
                                                    <div class="state-info">
                                                        <i class="fas fa-shopping-cart"></i>
                                                        <span>Your cart contains items from <?= count($kitchenGroups) ?> different kitchens</span>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="state-content">
                                                    <div class="state-icon">
                                                        <i class="fas fa-shopping-cart"></i>
                                                    </div>
                                                    <h4>Cart is Empty</h4>
                                                    <p>Add some delicious items to get started with your order.</p>
                                                    <a href="/dishes" class="btn btn-primary">
                                                        <i class="fas fa-utensils"></i> Browse Menu
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php
    $fillColor = '#FFFBEB';
    $invert = true;
    include BASE_PATH . '/src/views/components/divider-banner.php';
    ?>
</main>

<!-- Cart Confirmation Modal -->
<div class="modal-overlay" id="cartConfirmModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-question-circle" id="cartConfirmIcon"></i>
                <span id="cartConfirmTitle">Confirm Action</span>
            </h2>
            <button class="modal-close" onclick="closeCartConfirmModal()">&times;</button>
        </div>

        <form method="POST" id="cartConfirmForm" class="confirm-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="action" id="cartConfirmAction" value="">
            <input type="hidden" name="kitchen_id" id="cartConfirmKitchenId" value="">
            <input type="hidden" name="dish_id" id="cartConfirmDishId" value="">

            <div class="modal-body">
                <div class="confirm-content">
                    <p id="cartConfirmMessage" class="confirm-message"></p>
                    <p id="cartConfirmDetails" class="confirm-details"></p>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCartConfirmModal()">Cancel</button>
                <button type="submit" class="btn btn-danger" id="cartConfirmActionBtn">
                    <i class="fas fa-check"></i>
                    <span id="cartConfirmActionText">Confirm</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update delivery fee and button state
    const areaSelect = document.getElementById('area_id');
    if (areaSelect) {
        areaSelect.addEventListener('change', function() {
            updateDeliverySummary();
            updateCheckoutButton();
        });

        // Initial update
        updateDeliverySummary();
        updateCheckoutButton();
    }

    // Form validation without alerts
    const checkoutForm = document.getElementById('checkout-form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
            } else {
                // Show loading state
                const submitButton = this.querySelector('.btn-checkout');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                }
            }
        });
    }

    // Close modal when clicking outside
    const modal = document.getElementById('cartConfirmModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                closeCartConfirmModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('active')) {
                closeCartConfirmModal();
            }
        });
    }
});

// Cart confirmation modal functions
function openCartConfirmModal(action, id, message, details) {
    const modal = document.getElementById('cartConfirmModal');
    const title = document.getElementById('cartConfirmTitle');
    const icon = document.getElementById('cartConfirmIcon');
    const messageEl = document.getElementById('cartConfirmMessage');
    const detailsEl = document.getElementById('cartConfirmDetails');
    const actionInput = document.getElementById('cartConfirmAction');
    const kitchenIdInput = document.getElementById('cartConfirmKitchenId');
    const dishIdInput = document.getElementById('cartConfirmDishId');
    const actionText = document.getElementById('cartConfirmActionText');
    const actionBtn = document.getElementById('cartConfirmActionBtn');

    // Set values based on action
    actionInput.value = action;
    
    if (action === 'clear_cart') {
        title.textContent = 'Clear Cart';
        icon.className = 'fas fa-trash-alt';
        actionText.textContent = 'Clear Cart';
        kitchenIdInput.value = '';
        dishIdInput.value = '';
    } else if (action === 'remove_kitchen') {
        title.textContent = 'Remove Kitchen';
        icon.className = 'fas fa-store-alt';
        actionText.textContent = 'Remove Kitchen';
        kitchenIdInput.value = id;
        dishIdInput.value = '';
    } else if (action === 'remove_item') {
        title.textContent = 'Remove Item';
        icon.className = 'fas fa-trash';
        actionText.textContent = 'Remove Item';
        kitchenIdInput.value = '';
        dishIdInput.value = id;
    }

    // Set message and details
    messageEl.textContent = message;
    detailsEl.textContent = details || 'This action cannot be undone.';

    // Set form action
    const form = document.getElementById('cartConfirmForm');
    form.action = '/cart';

    // Show modal
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeCartConfirmModal() {
    const modal = document.getElementById('cartConfirmModal');
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
    
    // Reset form
    const form = document.getElementById('cartConfirmForm');
    form.reset();
}

function updateDeliverySummary() {
    const areaSelect = document.getElementById('area_id');
    if (!areaSelect) return;

    const selectedOption = areaSelect.options[areaSelect.selectedIndex];
    const deliveryFee = parseFloat(selectedOption.getAttribute('data-fee')) || 0;
    const subtotal = <?= $activeKitchen['total'] ?? 0 ?>;
    const total = subtotal + deliveryFee;

    // Update displays
    document.getElementById('delivery-fee-display').textContent = '৳' + deliveryFee.toFixed(2);
    document.getElementById('total-display').textContent = '৳' + total.toFixed(2);
}

function updateCheckoutButton() {
    const areaSelect = document.getElementById('area_id');
    const checkoutButton = document.getElementById('checkout-button');
    const buttonText = document.getElementById('checkout-button-text');

    if (!areaSelect || !checkoutButton) return;

    const selectedOption = areaSelect.options[areaSelect.selectedIndex];
    const minOrder = parseFloat(selectedOption.getAttribute('data-min-order')) || 0;
    const subtotal = <?= $activeKitchen['total'] ?? 0 ?>;
    const areaId = areaSelect.value;

    if (!areaId) {
        checkoutButton.disabled = true;
        buttonText.textContent = 'Select Delivery Area';
    } else if (minOrder > 0 && subtotal < minOrder) {
        checkoutButton.disabled = true;
        buttonText.textContent = 'Minimum Order Not Met';
    } else {
        checkoutButton.disabled = false;
        buttonText.textContent = 'Proceed to Checkout';
    }
}

function validateForm() {
    const errors = [];
    const errorContainer = document.getElementById('form-errors');

    // Clear previous errors
    errorContainer.innerHTML = '';
    errorContainer.style.display = 'none';

    // Validate delivery area
    const areaId = document.getElementById('area_id').value;
    if (!areaId) {
        errors.push('Please select a delivery area.');
    }

    // Validate delivery address
    const deliveryAddress = document.getElementById('delivery_address').value.trim();
    if (!deliveryAddress) {
        errors.push('Delivery address is required.');
    } else if (deliveryAddress.length < 10) {
        errors.push('Please provide a detailed delivery address (at least 10 characters).');
    }

    // Validate phone number
    const contactPhone = document.getElementById('contact_phone').value.trim();
    if (!contactPhone) {
        errors.push('Contact phone number is required.');
    } else if (!/^01[0-9]{9}$/.test(contactPhone)) {
        errors.push('Please enter a valid 11-digit Bangladeshi mobile number starting with 01.');
    }

    // Display errors if any
    if (errors.length > 0) {
        errorContainer.style.display = 'block';
        errors.forEach(error => {
            const errorElement = document.createElement('div');
            errorElement.className = 'error-message';
            errorElement.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${error}`;
            errorContainer.appendChild(errorElement);
        });

        // Scroll to errors
        errorContainer.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
        return false;
    }

    return true;
}
</script>