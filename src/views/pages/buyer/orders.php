<?php
$orders = $data['orders'] ?? [];
$statusFilter = $data['statusFilter'] ?? 'all';
$searchTerm = $data['searchTerm'] ?? '';
$currentPage = $data['currentPage'] ?? 1;
$totalPages = $data['totalPages'] ?? 1;
$totalItems = $data['totalItems'] ?? 0;

function formatDate($dateString, $format = 'M j, Y g:i A')
{
    if (!$dateString) return '';

    if (
        strpos($dateString, 'Feb') === 0 || strpos($dateString, 'Jan') === 0 ||
        strpos($dateString, 'Mar') === 0
    ) {
        return htmlspecialchars($dateString);
    }

    $cleanDate = preg_replace('/\.\d{6}/', '', $dateString);

    $cleanDate = str_replace('.', ':', $cleanDate);

    try {
        $date = new DateTime($cleanDate);
        return $date->format($format);
    } catch (Exception $e) {
        return htmlspecialchars($dateString);
    }
}

function getUnixTimestamp($dateString)
{
    if (!$dateString) return 0;

    $cleanDate = preg_replace('/\.\d{6}/', '', $dateString);
    $cleanDate = str_replace('.', ':', $cleanDate);

    try {
        $date = new DateTime($cleanDate);
        return $date->getTimestamp();
    } catch (Exception $e) {
        return strtotime($cleanDate) ?: 0;
    }
}

function getStatusBadge($status)
{
    $statusClasses = [
        'PENDING' => 'status-pending',
        'ACCEPTED' => 'status-accepted',
        'READY' => 'status-ready',
        'DELIVERED' => 'status-delivered',
        'CANCELLED' => 'status-cancelled'
    ];

    $statusIcons = [
        'PENDING' => 'fa-solid fa-hourglass-half',
        'ACCEPTED' => 'fa-check',
        'READY' => 'fa-check-double',
        'DELIVERED' => 'fa-regular fa-truck',
        'CANCELLED' => 'fa-times-circle'
    ];

    $class = $statusClasses[$status] ?? 'status-pending';
    $icon = $statusIcons[$status] ?? 'fa-clock';

    return "<span class='status-badge {$class}'><i class='fas {$icon}'></i> {$status}</span>";
}

function formatPrice($amount)
{
    return '৳' . number_format($amount, 2);
}

include BASE_PATH . '/src/views/components/flash-popup.php';
?>

<main class="orders-page">
    <section class="orders-section">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">My Orders</h1>
                <p class="page-subtitle">Track your food orders and delivery status</p>
            </div>

            <!-- Filters Section -->
            <div class="filters-container">
                <div class="filter-row">
                    <!-- Search Box -->
                    <div class="search-filter" style="flex: 2;">
                        <label class="filter-label">Search Orders</label>
                        <form method="GET" action="/orders" class="search-form">
                            <div class="search-input-wrapper">
                                <div class="search-icon">
                                    <i class="fas fa-search"></i>
                                </div>
                                <input type="text"
                                    name="search"
                                    placeholder="Search by order ID, kitchen, or items..."
                                    value="<?= htmlspecialchars($searchTerm) ?>"
                                    class="search-input"
                                    onkeypress="if(event.key === 'Enter') this.form.submit()">
                            </div>
                            <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                            <input type="hidden" name="page" value="1">
                        </form>
                    </div>

                    <!-- Status Filter -->
                    <div class="status-filter">
                        <label class="filter-label">Status Filter:</label>
                        <form method="GET" action="/orders" id="statusForm">
                            <select name="status" class="filter-select" onchange="this.form.submit()">
                                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Orders</option>
                                <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="Delivered" <?= $statusFilter === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                <option value="Cancelled" <?= $statusFilter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                            <input type="hidden" name="search" value="<?= htmlspecialchars($searchTerm) ?>">
                            <input type="hidden" name="page" value="1">
                        </form>
                    </div>

                    <!-- Results Count -->
                    <div class="results-count">
                        <span id="resultsCount"><?= $totalItems ?> orders</span>
                    </div>

                    <!-- Clear Filters Button -->
                    <div class="clear-filter">
                        <a href="/orders" class="clear-btn">
                            Clear Filters
                        </a>
                    </div>

                    <!-- Clear All Order History Button -->
                    <?php if ($totalItems > 0): ?>
                        <div class="clear-all-filter">
                            <button class="btn btn-danger btn-sm" onclick="openConfirmModal('clear_all')">
                                <i class="fas fa-trash-alt"></i> Clear All Orders
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Active Filters Display -->
            <?php if ($statusFilter !== 'all' || $searchTerm): ?>
                <div class="active-filters">
                    <?php if ($statusFilter !== 'all'): ?>
                        <span class="active-filter-tag">
                            Status: <?= htmlspecialchars($statusFilter) ?>
                            <a href="?<?= http_build_query(['search' => $searchTerm, 'page' => 1]) ?>" class="remove-filter">
                                <i class="fas fa-times"></i>
                            </a>
                        </span>
                    <?php endif; ?>

                    <?php if ($searchTerm): ?>
                        <span class="active-filter-tag">
                            Search: "<?= htmlspecialchars($searchTerm) ?>"
                            <a href="?<?= http_build_query(['status' => $statusFilter, 'page' => 1]) ?>" class="remove-filter">
                                <i class="fas fa-times"></i>
                            </a>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="orders-container">
                <?php if (empty($orders)): ?>
                    <div class="empty-state">
                        <div class="empty-state-content">
                            <div class="empty-state-icon">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <h2>No Orders Found</h2>
                            <a href="/dishes" class="btn btn-primary btn-lg">
                                <i class="fas fa-utensils"></i> Browse Menu
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Orders Grid -->
                    <div class="orders-grid">
                        <?php foreach ($orders as $order): ?>
                            <div class="order-card-compact">
                                <!-- Order Header -->
                                <div class="order-header-compact">
                                    <div class="order-basic-info">
                                        <div class="kitchen-avatar-mini">
                                            <?php if (!empty($order['COVER_IMAGE'])): ?>
                                                <img
                                                    src="/uploads/kitchen/<?= htmlspecialchars($order['COVER_IMAGE']) ?>"
                                                    alt="<?= htmlspecialchars($order['KITCHEN_NAME']) ?>">
                                            <?php else: ?>
                                                <div class="default-avatar-mini">
                                                    <i class="fas fa-utensils"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="order-meta">
                                            <h4 class="kitchen-name"><?= htmlspecialchars($order['KITCHEN_NAME']) ?></h4>
                                            <div class="order-id-date">
                                                <span class="order-id">#<?= $order['ORDER_ID'] ?></span>
                                                <span class="order-date">• <?= formatDate($order['CREATED_AT']) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="order-status-compact">
                                        <?= getStatusBadge($order['STATUS']) ?>
                                    </div>
                                </div>

                                <!-- Order Items Summary -->
                                <div class="order-items-summary">
                                    <div class="items-preview">
                                        <div class="items-images">
                                            <?php
                                            $displayItems = array_slice($order['ITEMS'], 0, 3);
                                            $remainingItems = count($order['ITEMS']) - 3;
                                            ?>
                                            <?php foreach ($displayItems as $item): ?>
                                                <div class="item-image-mini">
                                                    <?php if (!empty($item['ITEM_IMAGE'])): ?>
                                                        <img src="/uploads/menu/<?= htmlspecialchars($item['ITEM_IMAGE']) ?>" alt="<?= htmlspecialchars($item['NAME']) ?>" title="<?= htmlspecialchars($item['NAME']) ?>">
                                                    <?php else: ?>
                                                        <div class="default-item-image">
                                                            <i class="fas fa-utensils"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                            <?php if ($remainingItems > 0): ?>
                                                <div class="more-items-count">+<?= $remainingItems ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="items-text">
                                            <?php
                                            $itemNames = array_slice(array_column($order['ITEMS'], 'NAME'), 0, 2);
                                            ?>
                                            <span class="items-names"><?= htmlspecialchars(implode(', ', $itemNames)) ?></span>
                                            <?php if ($remainingItems > 0): ?>
                                                <span class="more-items-text">+<?= $remainingItems ?> more</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="item-count"><?= count($order['ITEMS']) ?> items</div>
                                </div>

                                <!-- Order Summary -->
                                <div class="order-summary-compact">
                                    <div class="amount-section">
                                        <span class="total-amount">৳<?= number_format($order['TOTAL_AMOUNT'] + $order['DELIVERY_FEE'], 2) ?></span>
                                        <span class="amount-breakdown">(৳<?= number_format($order['TOTAL_AMOUNT'], 2) ?> + ৳<?= number_format($order['DELIVERY_FEE'], 2) ?> delivery)</span>
                                    </div>
                                </div>

                                <!-- Order Actions -->
                                <div class="order-actions-compact">
                                    <?php
                                    $statusUpper = strtoupper($order['STATUS']);

                                    $orderTimestamp = getUnixTimestamp($order['CREATED_AT']);
                                    $currentTime = time();
                                    $tenMinutesAgo = $currentTime - (10 * 60);

                                    $canCancel = in_array($statusUpper, ['PENDING', 'PLACED']) &&
                                        $orderTimestamp > 0 &&
                                        $orderTimestamp >= $tenMinutesAgo;

                                    if ($canCancel): ?>
                                        <button class="btn btn-danger btn-sm" onclick="openConfirmModal('cancel', <?= $order['ORDER_ID'] ?>, '<?= htmlspecialchars(addslashes($order['KITCHEN_NAME'])) ?>')">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    <?php endif; ?>

                                    <?php
                                    $isDelivered = stripos($order['STATUS'], 'delivered') !== false;
                                    if ($isDelivered && $order['BUYER_DELETE'] == 0): ?>
                                        <button class="btn btn-outline btn-danger btn-sm" onclick="openConfirmModal('delete', <?= $order['ORDER_ID'] ?>, '<?= htmlspecialchars(addslashes($order['KITCHEN_NAME'])) ?>')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($order['STATUS'] === 'CANCELLED'): ?>
                                        <?php if (!$order['REFUND_STATUS']): ?>
                                            <!-- No refund request yet -->
                                            <button class="btn btn-outline btn-warning btn-sm refund-btn"
                                                data-order-id="<?= $order['ORDER_ID'] ?>">
                                                <i class="fas fa-money-bill-wave"></i> Request Refund
                                            </button>

                                        <?php else: ?>
                                            <?php
                                            $status = strtoupper($order['REFUND_STATUS']);
                                            $statusMap = [
                                                'PENDING'   => ['text' => 'Refund Request Sent', 'class' => 'btn-secondary', 'icon' => 'fa-hourglass-half'],
                                                'APPROVED'  => ['text' => 'Refund Approved', 'class' => 'btn-info', 'icon' => 'fa-check'],
                                                'REJECTED'  => ['text' => 'Refund Rejected', 'class' => 'btn-danger', 'icon' => 'fa-times'],
                                                'PROCESSED' => ['text' => 'Refunded', 'class' => 'btn-success', 'icon' => 'fa-check-circle'],
                                            ];

                                            $config = $statusMap[$status] ?? null;
                                            ?>

                                            <?php if ($config): ?>
                                                <button class="btn btn-outline <?= $config['class'] ?> btn-sm" disabled>
                                                    <i class="fas <?= $config['icon'] ?>"></i> <?= $config['text'] ?>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <button class="btn btn-outline btn-primary btn-sm" onclick="openDetailsModal(<?= $order['ORDER_ID'] ?>)">
                                        <i class="fas fa-eye"></i> Details
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($currentPage > 1): ?>
                                <a href="?<?= http_build_query([
                                                'status' => $statusFilter,
                                                'search' => $searchTerm,
                                                'page' => $currentPage - 1
                                            ]) ?>" class="pagination-btn pagination-prev">
                                    &laquo; Prev
                                </a>
                            <?php else: ?>
                                <span class="pagination-btn pagination-disabled">&laquo; Prev</span>
                            <?php endif; ?>

                            <span class="pagination-info">
                                Page <?= htmlspecialchars($currentPage) ?> of <?= htmlspecialchars($totalPages) ?>
                            </span>

                            <?php if ($currentPage < $totalPages): ?>
                                <a href="?<?= http_build_query([
                                                'status' => $statusFilter,
                                                'search' => $searchTerm,
                                                'page' => $currentPage + 1
                                            ]) ?>" class="pagination-btn pagination-next">
                                    Next &raquo;
                                </a>
                            <?php else: ?>
                                <span class="pagination-btn pagination-disabled">Next &raquo;</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php
    $fillColor = '#FFFBEB';
    $invert = true;

    include BASE_PATH . '/src/views/components/divider-banner.php';
    ?>

    <!-- Order Details Modals -->
    <?php foreach ($orders as $order): ?>
        <div class="modal-overlay" id="order-modal-<?= $order['ORDER_ID'] ?>">
            <div class="modal modal-large">
                <div class="modal-header">
                    <h3 class="modal-title">
                        <i class="fas fa-shopping-bag"></i>
                        Order #<?= $order['ORDER_ID'] ?> Details
                    </h3>
                    <button type="button" class="modal-close" onclick="closeModal('order-modal-<?= $order['ORDER_ID'] ?>')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="order-details-content">
                        <!-- Order Information -->
                        <div class="order-detail-section">
                            <h3><i class="fas fa-info-circle"></i> Order Information</h3>
                            <div class="detail-row">
                                <span class="detail-label">Order ID:</span>
                                <span class="detail-value">#<?= $order['ORDER_ID'] ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Status:</span>
                                <span class="detail-value status-badge status-<?= strtolower($order['STATUS']) ?>" style="flex-direction: flex-end;">
                                    <?= htmlspecialchars($order['STATUS']) ?>
                                </span>
                            </div>
                            <?php if ($order['STATUS'] === 'CANCELLED'): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Cancel By:</span>
                                    <span class="detail-value) ?>">
                                        <?= htmlspecialchars($order['CANCEL_BY']) ?>
                                    </span>
                                </div>
                                <?php if ($order['CANCEL_BY'] === 'SELLER'): ?>
                                    <div class="detail-row">
                                        <span class="detail-label">Cancellation Reason:</span>
                                        <span class="detail-value">
                                            <?= htmlspecialchars($order['CANCELLATION_REASON']) ?>
                                        </span>
                                    </div>
                                <?php endif ?>
                            <?php endif ?>

                            <div class="detail-row">
                                <span class="detail-label">Order Date:</span>
                                <span class="detail-value"><?= formatDate($order['CREATED_AT']) ?></span>
                            </div>
                            <?php if ($order['ESTIMATED_DELIVERY_TIME']): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Estimated Delivery:</span>
                                    <span class="detail-value"><?= $order['ESTIMATED_DELIVERY_TIME'] ?> minutes</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Kitchen Information -->
                        <div class="order-detail-section">
                            <h3><i class="fas fa-store"></i> Kitchen Information</h3>
                            <div class="detail-row">
                                <span class="detail-label">Kitchen Name:</span>
                                <span class="detail-value"><?= htmlspecialchars($order['KITCHEN_NAME']) ?></span>
                            </div>
                            <?php if (!empty($order['KITCHEN_ADDRESS'])): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Kitchen Address:</span>
                                    <span class="detail-value"><?= htmlspecialchars($order['KITCHEN_ADDRESS']) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($order['DELIVERY_AREA'])): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Delivery Area:</span>
                                    <span class="detail-value"><?= htmlspecialchars($order['DELIVERY_AREA']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Delivery Information -->
                        <div class="order-detail-section">
                            <h3><i class="fas fa-truck"></i> Delivery Information</h3>
                            <?php if (!empty($order['DELIVERY_ADDRESS'])): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Delivery Address:</span>
                                    <span class="detail-value"><?= htmlspecialchars($order['DELIVERY_ADDRESS']) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($order['CONTACT_PHONE'])): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Contact Phone:</span>
                                    <span class="detail-value"><?= htmlspecialchars($order['CONTACT_PHONE']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Order Items -->
                        <div class="order-detail-section">
                            <h3><i class="fas fa-utensils"></i> Order Items</h3>
                            <?php if (!empty($order['ITEMS'])): ?>
                                <div class="order-items-list">
                                    <?php foreach ($order['ITEMS'] as $item): ?>
                                        <div class="order-item-detail">
                                            <div class="item-image">
                                                <?php if (!empty($item['ITEM_IMAGE'])): ?>
                                                    <img src="/uploads/menu/<?= htmlspecialchars($item['ITEM_IMAGE']) ?>" alt="<?= htmlspecialchars($item['NAME']) ?>">
                                                <?php else: ?>
                                                    <div class="default-item-image">
                                                        <i class="fas fa-utensils"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="item-info">
                                                <h4 class="item-name"><?= htmlspecialchars($item['NAME']) ?></h4>
                                                <?php if (!empty($item['DESCRIPTION'])): ?>
                                                    <p class="item-description"><?= htmlspecialchars($item['DESCRIPTION']) ?></p>
                                                <?php endif; ?>
                                                <?php if (!empty($item['SPECIAL_REQUEST'])): ?>
                                                    <p class="special-request">
                                                        <strong>Special Request:</strong> <?= htmlspecialchars($item['SPECIAL_REQUEST']) ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="item-pricing">
                                                <div class="item-quantity">Qty: <?= $item['QUANTITY'] ?></div>
                                                <div class="item-price">৳<?= number_format($item['PRICE_AT_ORDER'] ?? $item['PRICE'], 2) ?></div>
                                                <div class="item-total">৳<?= number_format($item['QUANTITY'] * ($item['PRICE_AT_ORDER'] ?? $item['PRICE']), 2) ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p>No items found for this order.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Order Summary -->
                        <div class="order-detail-section">
                            <h3><i class="fas fa-receipt"></i> Order Summary</h3>
                            <div class="detail-row">
                                <span class="detail-label">Subtotal:</span>
                                <span class="detail-value">৳<?= number_format($order['TOTAL_AMOUNT'], 2) ?></span>
                            </div>
                            <div class="detail-row" style="margin-bottom: 1px;">
                                <span class="detail-label">Delivery Fee:</span>
                                <span class="detail-value">৳<?= number_format($order['DELIVERY_FEE'], 2) ?></span>
                            </div>
                            <div class="detail-row total-row">
                                <span class="detail-label">Total Amount:</span>
                                <span class="detail-value">৳<?= number_format($order['TOTAL_AMOUNT'] + $order['DELIVERY_FEE'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Confirmation Modal -->
    <div class="modal-overlay" id="confirmModal">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title">
                    <i class="fas fa-question-circle" id="confirmIcon"></i>
                    <span id="confirmTitle">Confirm Action</span>
                </h2>
                <button class="modal-close" onclick="closeConfirmModal()">&times;</button>
            </div>

            <form method="POST" id="confirmForm" class="confirm-form">
                <input type="hidden" name="order_id" id="confirmOrderId">
                <input type="hidden" name="action" id="confirmActionType">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="modal-body">
                    <div class="confirm-content">
                        <p id="confirmMessage" class="confirm-message"></p>
                        <p id="confirmDetails" class="confirm-details"></p>

                        <div class="order-info">
                            <p><strong>Order ID:</strong> <span id="confirmOrderIdText"></span></p>
                            <p><strong>Kitchen:</strong> <span id="confirmKitchenName"></span></p>
                        </div>

                        <!-- TYPE CANCEL confirmation -->
                        <div class="form-group" id="cancelConfirmGroup" style="display:none;">
                            <label class="form-label">Type "CANCEL" to confirm *</label>
                            <input type="text"
                                class="form-control"
                                id="cancelConfirmInput"
                                placeholder="CANCEL"
                                pattern="CANCEL">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeConfirmModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="confirmActionBtn" disabled>
                        <i class="fas fa-check"></i>
                        <span id="confirmActionText">Confirm</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Refund Request Modal -->
    <div class="modal-overlay" id="refundRequestModal">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title">
                    <i class="fas fa-money-bill-wave"></i>
                    Request Refund
                </h2>
                <button class="modal-close" onclick="closeModal('refundRequestModal')">&times;</button>
            </div>
            <form method="POST" action="/orders/request-refund" id="refundRequestForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="order_id" id="refundOrderId">
                <input type="hidden" name="amount" id="refundAmountField">

                <div class="modal-body">
                    <!-- Order Information -->
                    <div class="order-info-refund">
                        <h4>Order Details</h4>
                        <div class="detail-row">
                            <span class="detail-label">Order ID:</span>
                            <span class="detail-value" id="refundOrderIdText"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Kitchen:</span>
                            <span class="detail-value" id="refundKitchenName"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Order Date:</span>
                            <span class="detail-value" id="refundOrderDate"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Status:</span>
                            <span class="detail-value" id="refundOrderStatus"></span>
                        </div>
                    </div>

                    <!-- Refund Calculation -->
                    <div class="refund-breakdown">
                        <h4>Refund Calculation</h4>
                        <div class="refund-row">
                            <span class="refund-label">Food Amount:</span>
                            <span class="refund-value">৳<span id="refundFoodAmount">0.00</span></span>
                        </div>
                        <div class="refund-row">
                            <span class="refund-label">Delivery Fee:</span>
                            <span class="refund-value">৳<span id="refundDeliveryFee">0.00</span></span>
                        </div>
                        <div class="refund-row" id="serviceChargeRow" style="display: none;">
                            <span class="refund-label">Service Charge (<span id="serviceChargePercent">0</span>%):</span>
                            <span class="refund-value">-৳<span id="refundServiceCharge">0.00</span></span>
                        </div>
                        <div class="refund-row refund-total">
                            <span class="refund-label">Total Refundable:</span>
                            <span class="refund-value">৳<span id="refundTotalAmount">0.00</span></span>
                        </div>

                        <div class="refund-note" id="refundNote">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i>
                                Service charge calculation depends on who cancelled the order.
                            </small>
                        </div>
                    </div>

                    <!-- Payment Method Selection -->
                    <div class="form-group">
                        <label class="form-label">Payment Method *</label>
                        <select name="method" class="form-control" required>
                            <option value="">Select Payment Method</option>
                            <option value="bKash Personal">bKash Personal</option>
                            <option value="bKash Agent">bKash Agent</option>
                            <option value="Nagad Personal">Nagad Personal</option>
                            <option value="Nagad Agent">Nagad Agent</option>
                            <option value="Rocket Personal">Rocket Personal</option>
                            <option value="Rocket Agent">Rocket Agent</option>
                        </select>
                    </div>

                    <!-- Mobile Number Input -->
                    <div class="form-group">
                        <label class="form-label">Mobile Number *</label>
                        <div class="input-group">
                            <span class="input-group-text">+88</span>
                            <input type="tel"
                                class="form-control"
                                name="mobile_number"
                                id="mobileNumber"
                                placeholder="Enter mobile number"
                                pattern="01[3-9]\d{8}"
                                maxlength="11"
                                required>
                        </div>
                        <small class="form-text text-muted">Enter your 11-digit Bangladeshi mobile number</small>
                    </div>

                    <!-- Reason for Refund -->
                    <div class="form-group">
                        <label class="form-label">Reason for Refund *</label>
                        <textarea class="form-control"
                            name="reason"
                            rows="3"
                            required
                            placeholder="Please explain why you are requesting a refund..."></textarea>
                        <small class="form-text text-muted">Provide details about why you need a refund</small>
                    </div>

                    <!-- Important Information -->
                    <div class="alert-info alert-info-refund">
                        <i class="fas fa-info-circle"></i>
                        <strong>Important:</strong>
                        <ul class="mb-0 mt-1">
                            <li>Refunds are processed within <strong>3-5 business days</strong></li>
                            <li>Make sure your mobile number is correct</li>
                            <li>You will receive <strong>৳<span id="refundDisplayAmount">0.00</span></strong> if approved</li>
                            <li>Service charges may apply depending on cancellation type</li>
                        </ul>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('refundRequestModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Confirm buttons (consistent with cart.php)
        document.querySelectorAll('[data-confirm]').forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm(this.dataset.confirm)) {
                    e.preventDefault();
                }
            });
        });

        // Setup refund button event listeners
        document.querySelectorAll('.refund-btn').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.getAttribute('data-order-id');
                const kitchenName = this.getAttribute('data-kitchen-name');
                const status = this.getAttribute('data-status');
                const orderDate = this.getAttribute('data-order-date');
                const foodAmount = parseFloat(this.getAttribute('data-food-amount')) || 0;
                const deliveryFee = parseFloat(this.getAttribute('data-delivery-fee')) || 0;
                const cancelledBy = this.getAttribute('data-cancelled-by') || 'BUYER';

                openRefundModal(orderId, kitchenName, status, orderDate, foodAmount, deliveryFee, cancelledBy);
            });
        });

        // Close modal when clicking outside
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeAllModals();
                }
            });
        });

        // ESC key to close modals
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAllModals();
            }
        });
    });

    // Modal functions (consistent with favorites.php)
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }
    }

    function closeAllModals() {
        document.querySelectorAll('.modal-overlay.active').forEach(modal => {
            modal.classList.remove('active');
        });
        document.body.style.overflow = 'auto';
    }

    // Order details modal
    function openDetailsModal(orderId) {
        openModal('order-modal-' + orderId);
    }

    // Confirmation modal
    function openConfirmModal(action, orderId, kitchenName) {
        // Set order info
        document.getElementById("confirmOrderId").value = orderId;
        document.getElementById("confirmOrderIdText").textContent = '#' + orderId;
        document.getElementById("confirmKitchenName").textContent = kitchenName;

        const title = document.getElementById("confirmTitle");
        const icon = document.getElementById("confirmIcon");
        const message = document.getElementById("confirmMessage");
        const details = document.getElementById("confirmDetails");
        const actionBtn = document.getElementById("confirmActionBtn");
        const actionText = document.getElementById("confirmActionText");
        const confirmForm = document.getElementById("confirmForm");
        const confirmGroup = document.getElementById("cancelConfirmGroup");
        const confirmInput = document.getElementById("cancelConfirmInput");
        const orderInfo = document.querySelector('.order-info');

        // Reset
        if (confirmInput) confirmInput.value = "";
        if (confirmForm) confirmForm.reset();
        if (actionBtn) actionBtn.disabled = false;
        if (confirmGroup) confirmGroup.style.display = "none";
        if (orderInfo) orderInfo.style.display = "block";

        // Configure based on action
        if (action === "cancel") {
            title.textContent = "Cancel Order";
            icon.className = "fas fa-times-circle";
            message.textContent = "Are you sure you want to cancel this order?";
            details.textContent = "This action cannot be undone.";
            actionBtn.className = "btn btn-danger";
            actionText.textContent = "Cancel Order";
            confirmForm.action = "/orders/cancel";

            confirmGroup.style.display = "block";
            actionBtn.disabled = true;

            confirmInput.oninput = function() {
                actionBtn.disabled = this.value !== "CANCEL";
            };
        } else if (action === "delete") {
            title.textContent = "Delete Order";
            icon.className = "fas fa-trash";
            message.textContent = "Are you sure you want to delete this order?";
            details.textContent = "This will hide the order from your orders list. This action cannot be undone.";
            actionBtn.className = "btn btn-danger";
            actionText.textContent = "Delete Order";
            confirmForm.action = "/orders/delete";
            confirmForm.method = "POST";
        } else if (action === "clear_all") {
            title.textContent = "Clear All Order History";
            icon.className = "fas fa-trash-alt";
            message.textContent = "Are you sure you want to clear ALL your order history?";
            details.textContent = "This will hide all your delivered and cancelled orders. This action cannot be undone.";
            actionBtn.className = "btn btn-danger";
            actionText.textContent = "Clear All Orders";
            confirmForm.action = "/orders/clear-all";
            confirmForm.method = "POST";
            orderInfo.style.display = 'none';
        }

        // Open modal
        openModal("confirmModal");
    }

    function closeConfirmModal() {
        closeModal("confirmModal");
        const form = document.getElementById("confirmForm");
        if (form) form.reset();
        const orderInfo = document.querySelector('.order-info');
        if (orderInfo) orderInfo.style.display = 'block';
    }

    // Refund modal
    function openRefundModal(orderId, kitchenName, status, orderDate, foodAmount, deliveryFee, cancelledBy) {
        // Reset form first
        const refundForm = document.getElementById("refundRequestForm");
        if (refundForm) {
            refundForm.reset();
        }

        // Set order information
        document.getElementById("refundOrderId").value = orderId;
        document.getElementById("refundOrderIdText").textContent = '#' + orderId;
        document.getElementById("refundKitchenName").textContent = kitchenName;
        document.getElementById("refundOrderDate").textContent = orderDate;
        document.getElementById("refundOrderStatus").textContent = status;

        // Calculate refund based on who cancelled
        const subtotal = foodAmount + deliveryFee;

        let serviceCharge = 0;
        let serviceChargePercent = 0;
        let totalRefundable = 0;

        if (status === 'CANCELLED') {
            if (cancelledBy === 'SELLER') {
                // Seller cancelled: Full refund, no service charge
                serviceCharge = 0;
                serviceChargePercent = 0;
                totalRefundable = subtotal;
            } else {
                // Buyer cancelled: Apply 10% service charge on food amount only
                serviceChargePercent = 10;
                serviceCharge = foodAmount * (serviceChargePercent / 100);
                totalRefundable = subtotal - serviceCharge;
            }
        } else if (status === 'DELIVERED') {
            // For delivered orders, admin decides
            serviceCharge = 0;
            serviceChargePercent = 0;
            totalRefundable = subtotal; // Admin will adjust
        }

        // Update display
        document.getElementById("refundFoodAmount").textContent = foodAmount.toFixed(2);
        document.getElementById("refundDeliveryFee").textContent = deliveryFee.toFixed(2);

        // Show/hide service charge based on calculation
        const serviceChargeRow = document.getElementById("serviceChargeRow");
        if (serviceCharge > 0) {
            document.getElementById("refundServiceCharge").textContent = serviceCharge.toFixed(2);
            document.getElementById("serviceChargePercent").textContent = serviceChargePercent;
            serviceChargeRow.style.display = 'flex';
        } else {
            serviceChargeRow.style.display = 'none';
        }

        document.getElementById("refundTotalAmount").textContent = totalRefundable.toFixed(2);
        document.getElementById("refundDisplayAmount").textContent = totalRefundable.toFixed(2);

        // Update refund note based on policy
        const refundNote = document.getElementById("refundNote");
        if (cancelledBy === 'SELLER') {
            refundNote.innerHTML = `
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i>
                    Since the order was cancelled by the seller, you are eligible for a full refund.
                </small>
            `;
            refundNote.className = 'refund-note seller-cancelled';
        } else if (cancelledBy === 'BUYER') {
            refundNote.innerHTML = `
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i>
                    Service charge (${serviceChargePercent}%) is deducted for processing fees since you cancelled the order.
                </small>
            `;
            refundNote.className = 'refund-note buyer-cancelled';
        } else {
            refundNote.innerHTML = `
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i>
                    Service charge calculation depends on who cancelled the order.
                </small>
            `;
            refundNote.className = 'refund-note';
        }

        // Set hidden field with calculated amount
        document.getElementById("refundAmountField").value = totalRefundable.toFixed(2);

        // Open modal
        openModal('refundRequestModal');
    }
</script>

<style>
    .order-info-refund,
    .alert-info-refund {
        background: #f8f9fa;
        padding: 1rem;
        font-size: 14px;
        border-radius: 0.5rem;
    }

    .alert-info-refund {
        background-color: #d4edda;
    }

    .alert-info-refund ul {
        margin-left: 20px;
        margin-top: 8px;
    }

    .refund-note {
        margin-top: 10px;
        padding: 8px;
        border-radius: 4px;
        font-size: 0.85rem;
    }

    .refund-note.seller-cancelled {
        background: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }

    .refund-note.buyer-cancelled {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        color: #856404;
    }

    .refund-note.delivered-order {
        background: #d1ecf1;
        border: 1px solid #bee5eb;
        color: #0c5460;
    }

    /* Hide service charge row by default */
    #serviceChargeRow {
        display: none;
    }

    /* Refund Breakdown Styles */
    .refund-breakdown {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }

    .refund-breakdown h4 {
        margin-top: 0;
        color: #495057;
        margin-bottom: 15px;
        font-size: 1rem;
        border-bottom: 1px solid #dee2e6;
        padding-bottom: 8px;
    }

    .refund-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        padding: 5px 0;
    }

    .refund-row:not(.refund-total) {
        border-bottom: 1px dashed #dee2e6;
    }

    .refund-label {
        color: #6c757d;
        font-size: 0.9rem;
    }

    .refund-value {
        color: #495057;
        font-weight: 500;
        font-size: 0.9rem;
    }

    .refund-total {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 2px solid #dee2e6;
    }

    .refund-total .refund-label {
        color: #343a40;
        font-weight: 600;
        font-size: 1rem;
    }

    .refund-total .refund-value {
        color: #28a745;
        font-weight: 700;
        font-size: 1.1rem;
    }

    /* Modal sizes consistent with other pages */
    .modal-large {
        max-width: 800px;
    }

    .modal-medium {
        max-width: 600px;
    }
</style>