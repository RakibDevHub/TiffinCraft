<?php
$user = $data['currentUser'];
$kitchen = $data['kitchen'];
$orders = $data['orders'];
$orderStats = $data['todayOrderStats'];
$todayOrders = $data['todayOrders'] ?? 0;

function formatPrice($price)
{
    return '৳' . number_format($price, 2);
}

function formatDate($dateString, $format = 'M j, Y g:i A')
{
    if (!$dateString) return '';
    $date = DateTime::createFromFormat('d-M-y h.i.s.u A', $dateString);
    return $date ? $date->format($format) : htmlspecialchars($dateString);
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

function getNextStatusOptions($currentStatus)
{
    $options = [];

    switch ($currentStatus) {
        case 'PENDING':
            $options = [
                'ACCEPTED' => 'Accept Order',
                'CANCELLED' => 'Cancel Order'
            ];
            break;
        case 'ACCEPTED':
            $options = [
                'READY' => 'Mark as Ready',
                'DELIVERED' => 'Mark as Delivered',
            ];
            break;
        case 'READY':
            $options = [
                'DELIVERED' => 'Mark as Delivered',
            ];
            break;
        default:
            $options = [];
    }

    return $options;
}

include BASE_PATH . '/src/views/components/flash-popup.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title"><?= htmlspecialchars(ucfirst($title)) ?></h1>
    <p class="page-subtitle">Manage and track your kitchen orders</p>
</div>

<!-- Order Stats -->
<div class="stats-grid-wrapper">
    <div class="stats-grid">
        <!-- Total Orders Today Card -->
        <div class="stat-card">
            <div class="stat-icon-wrapper" style="background: rgba(79, 70, 229, 0.1); color: #4f46e5;">
                <i class="fas fa-shopping-bag"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $todayOrders ?></div>
                <div class="stat-label">Total Orders Today</div>
                <div class="stat-trend">
                    <span class="trend-badge">
                        <i class="fas fa-calendar"></i> <?= date('M j, Y') ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Pending Orders Card -->
        <div class="stat-card">
            <div class="stat-icon-wrapper" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $orderStats['pending']['count'] ?? 0 ?></div>
                <div class="stat-label">Pending</div>
                <div class="stat-trend">
                    <span class="trend-badge warning">
                        <i class="fas fa-clock"></i> Need to accept
                    </span>
                </div>
            </div>
        </div>

        <!-- Accepted Orders Card -->
        <div class="stat-card">
            <div class="stat-icon-wrapper" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                <i class="fas fa-check"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $orderStats['accepted']['count'] ?? 0 ?></div>
                <div class="stat-label">Accepted</div>
                <div class="stat-trend">
                    <span class="trend-badge">
                        <i class="fas fa-utensils"></i> Need to prepare
                    </span>
                </div>
            </div>
        </div>

        <!-- Ready Orders Card -->
        <div class="stat-card">
            <div class="stat-icon-wrapper" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                <i class="fas fa-check-double"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $orderStats['ready']['count'] ?? 0 ?></div>
                <div class="stat-label">Ready</div>
                <div class="stat-trend">
                    <span class="trend-badge">
                        <i class="fas fa-truck"></i> Need to deliver
                    </span>
                </div>
            </div>
        </div>

        <!-- Delivered Orders Card -->
        <div class="stat-card">
            <div class="stat-icon-wrapper" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                <i class="fas fa-truck"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $orderStats['delivered']['count'] ?? 0 ?></div>
                <div class="stat-label">Delivered</div>
                <div class="stat-trend">
                    <span class="trend-badge">
                        <i class="fas fa-check-circle"></i> Completed today
                    </span>
                </div>
            </div>
        </div>

        <!-- Cancelled Orders Card -->
        <div class="stat-card">
            <div class="stat-icon-wrapper" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $orderStats['cancelled']['count'] ?? 0 ?></div>
                <div class="stat-label">Cancelled</div>
                <div class="stat-trend">
                    <span class="trend-badge" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                        <i class="fas fa-ban"></i> Today
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Orders Table -->
<div class="dashboard-card">

    <div class="card-header">
        <h3>Order History</h3>

        <div class="header-actions">
            <!-- Filters and Search -->
            <div class="filters-container">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Search by order, customer, items, or address..." id="orderSearch">
                </div>
    
                <div class="filter-group">
                    <div class="filter-dropdown">
                        <select class="filter-select" id="statusFilter">
                            <option value="">All Statuses</option>
                            <option value="PENDING">Pending</option>
                            <option value="ACCEPTED">Accepted</option>
                            <option value="READY">Ready</option>
                            <option value="DELIVERED">Delivered</option>
                            <option value="CANCELLED">Cancelled</option>
                        </select>
                        <i class="fas fa-chevron-down"></i>
                    </div>
    
                    <div class="filter-dropdown">
                        <select class="filter-select" id="dateRangeFilter">
                            <option value="all">All Time</option>
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                            <option value="custom">Custom Range</option>
                        </select>
                        <i class="fas fa-chevron-down"></i>
                    </div>
    
                    <div class="custom-date-range" id="customDateRange" style="display: none;">
                        <input type="date" class="filter-select" id="dateFrom" placeholder="From Date" style="padding-right: 1rem;">
                        <input type="date" class="filter-select" id="dateTo" placeholder="To Date" style="padding-right: 1rem;">
                    </div>
                </div>
    
                <div class="action-buttons-group">
                    <button class="btn btn-secondary" id="clearFilters">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body">
        <?php if (empty($orders)): ?>
            <div class="no-data">
                <i class="fas fa-shopping-bag"></i>
                <p>No orders found</p>
                <p class="text-muted">You haven't received any orders yet</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Order Details</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <?php
                            $orderDateOnly = '';
                            if ($order['ORDER_DATE']) {
                                $date = DateTime::createFromFormat('d-M-y h.i.s.u A', $order['ORDER_DATE']);
                                if ($date) {
                                    $orderDateOnly = $date->format('Y-m-d');
                                }
                            }

                            $nextStatusOptions = getNextStatusOptions($order['STATUS']);
                            ?>
                            <tr data-status="<?= strtolower($order['STATUS']) ?>"
                                data-order-date="<?= $orderDateOnly ?>"
                                data-search-text="<?= htmlspecialchars(strtolower(
                                                        'ORD-' . str_pad($order['ORDER_ID'], 6, '0', STR_PAD_LEFT) . ' ' .
                                                            $order['CUSTOMER_NAME'] . ' ' .
                                                            $order['CONTACT_PHONE'] . ' ' .
                                                            $order['DELIVERY_ADDRESS'] . ' ' .
                                                            $order['DELIVERY_AREA'] . ' ' .
                                                            $order['ITEMS_SUMMARY']
                                                    )) ?>">
                                <td>
                                    <div class="order-info">
                                        <strong><?= 'ORD-' . str_pad($order['ORDER_ID'], 6, '0', STR_PAD_LEFT) ?></strong>

                                        <?php if ($order['DELIVERY_AREA']): ?>
                                            <br><small class="text-muted">Area: <?= htmlspecialchars($order['DELIVERY_AREA']) ?></small>
                                            <br><small class="text-muted" style="font-weight: bold;"><?= formatDate($order['ORDER_DATE']) ?></small>
                                            <?php if ($order['ESTIMATED_DELIVERY_TIME']): ?>
                                                <br><small class="text-muted">Est: <?= $order['ESTIMATED_DELIVERY_TIME'] ?> min</small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="user-info">
                                        <div class="user-details">
                                            <h4><?= htmlspecialchars($order['CUSTOMER_NAME']) ?></h4>
                                            <p><?= htmlspecialchars($order['CONTACT_PHONE']) ?></p>
                                            <?php if ($order['DELIVERY_ADDRESS']): ?>
                                                <small class="text-muted" title="<?= htmlspecialchars($order['DELIVERY_ADDRESS']) ?>">
                                                    <?= htmlspecialchars(substr($order['DELIVERY_ADDRESS'], 0, 50)) ?>...
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="order-items">
                                        <span class="item-count"><?= $order['ITEM_COUNT'] ?> items</span>
                                        <?php if ($order['ITEMS_SUMMARY']): ?>
                                            <br><small class="text-muted" title="<?= htmlspecialchars($order['ITEMS_SUMMARY']) ?>"><?= htmlspecialchars(substr($order['ITEMS_SUMMARY'], 0, 50)) ?>...</small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="amount-info">
                                        <strong style="color: <?= $order['STATUS'] === 'CANCELLED' ? 'red' : 'green' ?>;"><?= formatPrice($order['TOTAL_AMOUNT'] + $order['DELIVERY_FEE']) ?></strong>
                                        <?php if ($order['DELIVERY_FEE'] > 0): ?>
                                            <br><small class="text-muted">Item Price: <?= formatPrice($order['TOTAL_AMOUNT']) ?></small>
                                            <br><small class="text-muted">Delivery: <?= formatPrice($order['DELIVERY_FEE']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?= getStatusBadge($order['STATUS']) ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action btn-view" onclick="viewOrderDetails(<?= $order['ORDER_ID'] ?>)" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        <?php if (!empty($nextStatusOptions)): ?>
                                            <div class="status-dropdown">
                                                <button class="btn-action btn-edit" title="Update Status">
                                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                                </button>
                                                <div class="status-dropdown-menu">
                                                    <?php foreach ($nextStatusOptions as $statusValue => $statusLabel): ?>
                                                        <?php if ($statusValue === 'CANCELLED'): ?>
                                                            <button type="button" class="dropdown-item text-danger" onclick="updateOrderStatus(<?= $order['ORDER_ID'] ?>, '<?= $statusValue ?>', true)">
                                                                <i class="fas fa-times-circle"></i> <?= $statusLabel ?>
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="button" class="dropdown-item" onclick="updateOrderStatus(<?= $order['ORDER_ID'] ?>, '<?= $statusValue ?>')">
                                                                <i class="fas fa-check-circle"></i> <?= $statusLabel ?>
                                                            </button>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal-overlay" id="orderDetailsModal">
    <div class="modal modal-large">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-shopping-bag"></i>
                Order Details
            </h2>
            <button class="modal-close" onclick="closeModal('orderDetailsModal')">&times;</button>
        </div>
        <div class="modal-body">
            <?php foreach ($orders as $order): ?>
                <div class="order-details-content" id="order-details-<?= $order['ORDER_ID'] ?>" style="display: none;">
                    <div class="order-detail-section">
                        <h3>Order Information</h3>
                        <div class="detail-row">
                            <span class="detail-label">Order ID:</span>
                            <span class="detail-value"><?= 'ORD-' . str_pad($order['ORDER_ID'], 6, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Status:</span>
                            <span class="detail-value"><?= getStatusBadge($order['STATUS']) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Order Date:</span>
                            <span class="detail-value"><?= formatDate($order['ORDER_DATE']) ?></span>
                        </div>
                        <?php if ($order['ESTIMATED_DELIVERY_TIME']): ?>
                            <div class="detail-row">
                                <span class="detail-label">Estimated Delivery:</span>
                                <span class="detail-value"><?= $order['ESTIMATED_DELIVERY_TIME'] ?> minutes</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="order-detail-section">
                        <h3>Customer Information</h3>
                        <div class="detail-row">
                            <span class="detail-label">Customer Name:</span>
                            <span class="detail-value"><?= htmlspecialchars($order['CUSTOMER_NAME']) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Contact Phone:</span>
                            <span class="detail-value"><?= htmlspecialchars($order['CONTACT_PHONE']) ?></span>
                        </div>
                        <?php if ($order['DELIVERY_ADDRESS']): ?>
                            <div class="detail-row">
                                <span class="detail-label">Delivery Address:</span>
                                <span class="detail-value"><?= htmlspecialchars($order['DELIVERY_ADDRESS']) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($order['DELIVERY_AREA']): ?>
                            <div class="detail-row">
                                <span class="detail-label">Delivery Area:</span>
                                <span class="detail-value"><?= htmlspecialchars($order['DELIVERY_AREA']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="order-detail-section">
                        <h3>Order Summary</h3>
                        <div class="detail-row">
                            <span class="detail-label">Items:</span>
                            <span class="detail-value"><?= $order['ITEM_COUNT'] ?> items</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Subtotal:</span>
                            <span class="detail-value"><?= formatPrice($order['TOTAL_AMOUNT']) ?></span>
                        </div>
                        <?php if ($order['DELIVERY_FEE'] > 0): ?>
                            <div class="detail-row" style="margin-bottom: 1px;">
                                <span class="detail-label">Delivery Fee:</span>
                                <span class="detail-value"><?= formatPrice($order['DELIVERY_FEE']) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="detail-row total-row" style="margin-top: 1px;">
                            <span class="detail-label">Total Amount:</span>
                            <span class="detail-value"><?= formatPrice($order['TOTAL_AMOUNT'] + $order['DELIVERY_FEE']) ?></span>
                        </div>
                    </div>

                    <?php if ($order['ITEMS_SUMMARY']): ?>
                        <div class="order-detail-section">
                            <h3>Order Items</h3>
                            <div class="items-summary">
                                <?= nl2br(htmlspecialchars($order['ITEMS_SUMMARY'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('orderDetailsModal')">Close</button>
        </div>
    </div>
</div>

<!-- Cancel Order Modal -->
<div class="modal-overlay" id="cancelOrderModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-times-circle text-danger"></i>
                Cancel Order
            </h2>
            <button class="modal-close" onclick="closeModal('cancelOrderModal')">&times;</button>
        </div>

        <div class="modal-body">
            <p>Are you sure you want to cancel this order?</p>

            <div class="form-group">
                <label class="form-label">Cancellation Reason *</label>
                <textarea id="cancelReasonInput"
                    class="form-control"
                    rows="3"
                    placeholder="Enter reason for cancellation..."
                    required></textarea>
            </div>
        </div>

        <div class="modal-footer">
            <button class="btn btn-secondary"
                onclick="closeModal('cancelOrderModal')">
                Close
            </button>

            <button class="btn btn-danger"
                onclick="submitCancelOrder()">
                <i class="fas fa-times"></i> Confirm Cancel
            </button>
        </div>
    </div>
</div>

<!-- Status Update Form -->
<form method="POST" id="statusUpdateForm" style="display: none;">
    <input type="hidden" name="action" value="update_order_status">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <input type="hidden" name="order_id" id="formOrderId">
    <input type="hidden" name="status" id="formStatus">
    <textarea name="reason" id="formReason" style="display: none;"></textarea>
</form>