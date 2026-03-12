<?php
$user = $data['currentUser'];
$kitchen = $data['kitchen'];
$subscription = $data['subscription'] ?? null;
$stats = $data['stats'] ?? [];
$recentOrders = $data['recentOrders'] ?? [];
$totalOrders = $data['totalOrders'] ?? 0;
$todayOrders = $data['todayOrders'] ?? 0;
$canceledOrders = $data['canceledOrders'] ?? 0;
$acceptedOrders = $data['acceptedOrders'] ?? 0;
$readyOrders = $data['readyOrders'] ?? 0;
$todayRevenue = $data['todayRevenue'] ?? 0;
$weeklyRevenue = $data['weeklyRevenue'] ?? 0;
$monthlyRevenue = $data['monthlyRevenue'] ?? 0;
$totalRevenue = $data['totalRevenue'] ?? 0;
$todayRevenueData = $data['todayRevenueData'] ?? 0;
$weeklyRevenueData = $data['weeklyRevenueData'] ?? [];
$monthlyRevenueData = $data['monthlyRevenueData'] ?? [];
$totalRevenueData = $data['totalRevenueData'] ?? [];

$balance = $data['current_balance'];

$popularItems = $data['popularItems'] ?? [];
$orderStats = $data['orderStats'] ?? [];

$serviceAreas = $data['serviceAreas'];

function dateFormat($dateString, $format = 'M j, Y')
{
    if (!$dateString) {
        return '';
    }

    $date = DateTime::createFromFormat('d-M-y h.i.s.u A', $dateString);

    if ($date) {
        return $date->format($format);
    }

    return htmlspecialchars((string)$dateString);
}

function getStatusBadge($status)
{
    $statusClasses = [
        'PENDING' => 'status-pending',
        'DELIVERED' => 'status-delivered',
        'CANCELLED' => 'status-cancelled'
    ];

    $statusIcons = [
        'PENDING' => 'fa-clock',
        'DELIVERED' => 'fa-check-double',
        'CANCELLED' => 'fa-times-circle'
    ];

    $class = $statusClasses[$status] ?? 'status-pending';
    $icon = $statusIcons[$status] ?? 'fa-clock';

    return "<span class='status-badge {$class}'><i class='fas {$icon}'></i> {$status}</span>";
}

include BASE_PATH . '/src/views/components/flash-popup.php';
?>

<!-- Page Header -->
<div class="page-header dashboard-overview seller-dashboard">
    <div class="header-content">
        <div class="header-text">
            <h1 class="page-title" style="color: #fff;"><?= htmlspecialchars(ucfirst($title)) ?></h1>
            <p class="page-subtitle" style="color: #fff;">Welcome back, <?= htmlspecialchars($user['NAME']) ?>! Here's your business overview.</p>
        </div>
        <div class="header-actions">
            <span class="" id="currentTime"><?= date('l, F j, Y g:i A') ?></span>
            <span class="current-balance">Balance: ৳<?= number_format($balance['CURRENT_BALANCE'] ?? 0, 2) ?></span>
        </div>
    </div>
</div>

<!-- Revenue Stats -->
<div class="dashboard-card revenue-card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-money-bill-wave"></i>
            <h3>Revenue Overview</h3>
        </div>
    </div>

    <div class="card-body revenue-grid">

        <div class="revenue-item">
            <div class="">
                <p>Today</p>
                <h4>৳<?= number_format($todayRevenue, 2) ?></h4>
            </div>
            <small>
                Commission: ৳<?= number_format($todayRevenueData['commission'] ?? 0, 2) ?>
            </small>
        </div>

        <div class="revenue-item">
            <div class="">
                <p>Last 7 Days</p>
                <h4>৳<?= number_format($weeklyRevenue, 2) ?></h4>
            </div>
            <small>
                Commission: ৳<?= number_format($weeklyRevenueData['commission'] ?? 0, 2) ?>
            </small>
        </div>

        <div class="revenue-item">
            <div class="">
                <p>This Month</p>
                <h4>৳<?= number_format($monthlyRevenue, 2) ?></h4>
            </div>
            <small>
                Commission: ৳<?= number_format($monthlyRevenueData['commission'] ?? 0, 2) ?>
            </small>
        </div>

        <div class="revenue-item">
            <div class="">
                <p>All Time</p>
                <h4>৳<?= number_format($totalRevenue, 2) ?></h4>
            </div>
            <small>
                Commission: ৳<?= number_format($totalRevenueData['commission'] ?? 0, 2) ?>
            </small>
        </div>

    </div>
</div>

<!-- Dashboard Stats -->
<div class="stats-grid">
    <div class="dashboard-card stat-card">
        <div class="card-header">
            <div class="card-title">
                <p>Total Orders</p>
                <h1><?= number_format($totalOrders) ?></h1>
                <span><i class="fa-solid fa-ban"></i> Cancled: <?= number_format($canceledOrders) ?></span>
            </div>
            <div class="card-icon blue"><i class="fas fa-shopping-bag"></i></div>
        </div>
        <div class="card-body stat-trend">
            <i class="fas fa-arrow-up"></i>
            <span><?= number_format($todayOrders) ?> today</span>
        </div>
    </div>

    <div class="dashboard-card stat-card">
        <div class="card-header">
            <div class="card-title">
                <p>Pending Orders</p>
                <h1><?= number_format($orderStats['pending']['count'] ?? 0) ?></h1>
                <span style="color: #28a745"><i class="fa-regular fa-circle-check"></i> Accepted: <?= number_format($acceptedOrders) ?></span>
            </div>
            <div class="card-icon orange"><i class="fas fa-clock"></i></div>
        </div>
        <div class="card-body stat-trend">
            <i class="fas fa-list"></i>
            <span>Needs attention</span>
        </div>
    </div>

    <div class="dashboard-card stat-card">
        <div class="card-header">
            <div class="card-title">
                <p>Completed Orders</p>
                <h1><?= number_format($orderStats['delivered']['count'] ?? 0) ?></h1>
                <span style="color: #fdbb14"><i class="fa-regular fa-bell"></i> Need to Deliver: <?= number_format($readyOrders) ?></span>
            </div>
            <div class="card-icon green"><i class="fas fa-check-circle"></i></div>
        </div>
        <div class="card-body stat-trend">
            <i class="fas fa-chart-line"></i>
            <span>৳<?= number_format($orderStats['delivered']['revenue'] ?? 0, 2) ?> gross</span>
        </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="dashboard-grid">
    <!-- Recent Orders -->
    <div class="dashboard-card recent-orders-card">
        <div class="card-header">
            <div class="card-title">
                <i class="fas fa-receipt"></i>
                <h3>Recent Orders</h3>
            </div>
            <a href="/business/dashboard/orders" class="btn-link">
                View All <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($recentOrders)): ?>
                <div class="no-data">
                    <i class="fas fa-receipt"></i>
                    <h4>No recent orders</h4>
                </div>
            <?php else: ?>
                <div class="orders-list">
                    <?php foreach ($recentOrders as $order): ?>
                        <div class="order-item">
                            <div class="order-avatar">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                            <div class="order-content">
                                <div class="order-header">
                                    <h4 class="order-number">Order #<?= $order['order_number'] ?? $order['ORDER_NUMBER'] ?? $order['ORDER_ID'] ?></h4>
                                    <span class="order-amount">৳<?= number_format( ($order['total_amount'] + $order['delivery_fee']) ?? 0, 2) ?></span>
                                </div>
                                <div class="order-details">
                                    <span class="customer-name">
                                        <i class="fas fa-user"></i>
                                        <?= htmlspecialchars($order['customer_name'] ?? $order['CUSTOMER_NAME'] ?? 'Customer') ?>
                                    </span>
                                    <span class="order-date">
                                        <i class="fas fa-clock"></i>
                                        <?= dateFormat($order['created_at'] ?? $order['CREATED_AT'], 'M j, Y g:i A') ?>
                                    </span>
                                </div>
                                <div class="order-footer">
                                    <?= getStatusBadge($order['STATUS'] ?? $order['status']) ?>
                                    <span class="item-count">
                                        <?= $order['item_count'] ?? count($order['items'] ?? []) ?> items
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Popular Items -->
    <div class="dashboard-card popular-items-card">
        <div class="card-header">
            <div class="card-title">
                <i class="fas fa-fire"></i>
                <h3>Popular Items</h3>
            </div>
            <a href="/business/dashboard/menu-items" class="btn btn-primary">
                <i class="fas fa-utensils"></i> Manage Menu
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($popularItems)): ?>
                <div class="no-data">
                    <i class="fas fa-utensils"></i>
                    <h4>No popular items</h4>
                </div>
            <?php else: ?>
                <div class="popular-items-list">
                    <?php foreach ($popularItems as $index => $item): ?>

                        <div class="item-item">
                            <div class="item-rank">#<?= $index + 1 ?></div>
                            <div class="item-image">
                                <?php if ($item['ITEM_IMAGE']): ?>
                                    <img src="/uploads/menu/<?= htmlspecialchars($item['ITEM_IMAGE']) ?>"
                                        alt="<?= htmlspecialchars($item['ITEM_NAME']) ?>">
                                <?php else: ?>
                                    <i class="fa-solid fa-utensils"></i>
                                <?php endif; ?>
                            </div>
                            <div class="item-info">
                                <h4 class="item-name"><?= htmlspecialchars($item['ITEM_NAME']) ?></h4>
                                <div class="item-stats">
                                    <div class="stat">
                                        <i class="fas fa-shopping-cart"></i>
                                        <span><?= number_format($item['TOTAL_QUANTITY']) ?> sold</span>
                                    </div>
                                    <div class="stat">
                                        <i class="fas fa-box"></i>
                                        <span><?= number_format($item['TOTAL_QUANTITY']) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Kitchen Status -->
    <div class="dashboard-card kitchen-status-card">
        <div class="card-header">
            <div class="card-title">
                <i class="fas fa-utensils"></i>
                <h3>Kitchen Status</h3>
            </div>
            <a href="/business/dashboard/settings#kitchen-details" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Kitchen
            </a>
            <!-- <span class="status-indicator status-<?= strtolower($kitchen['APPROVAL_STATUS']) ?>"></span> -->
        </div>
        <div class="card-body">
            <div class="kitchen-info" style="background-image: url('/uploads/kitchen/<?= htmlspecialchars($kitchen['COVER_IMAGE'] ?? '') ?>');">
                <div class="kitchen-overlay"></div>
                <div class="kitchen-avatar">
                    <?php if (!empty($kitchen['COVER_IMAGE'])): ?>
                        <img src="/uploads/kitchen/<?= htmlspecialchars($kitchen['COVER_IMAGE']) ?>" alt="<?= htmlspecialchars($kitchen['NAME']) ?>" class="kitchen-avatar-img">
                    <?php else: ?>
                        <i class="fas fa-store"></i>
                    <?php endif; ?>
                </div>
                <div class="kitchen-details">
                    <h4 class="kitchen-name"><?= htmlspecialchars($kitchen['NAME']) ?></h4>
                    <p class="kitchen-description"><?= htmlspecialchars($kitchen['DESCRIPTION'] ?? 'Your food business') ?></p>
                </div>
            </div>

            <div class="status-grid">
                <div class="status-item">
                    <div class="status-icon">
                        <i class="fas fa-check-circle"></i>
                        <span class="status-label">Approval Status</span>
                    </div>
                    <div class="status-content">
                        <span class="status-value"><?= ucfirst($kitchen['APPROVAL_STATUS']) ?></span>
                    </div>
                </div>

                <div class="status-item">
                    <div class="status-icon">
                        <i class="fas fa-map-marker-alt" style="font-size: 20px;"></i>
                        <span class="status-label">Service Areas</span>
                    </div>
                    <div class="status-content">
                        <?php if (!empty($kitchen['TOTAL_AREAS'])): ?>
                            <?= $kitchen['TOTAL_AREAS'] ?? 0 ?> areas
                        <?php else: ?>
                            <span class="status-value">N/A</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="status-item">
                    <div class="status-icon">
                        <i class="fas fa-clock"></i>
                        <span class="status-label">Avg Prep Time</span>
                    </div>
                    <div class="status-content">
                        <span class="status-value"><?= $kitchen['AVG_PREP_TIME'] ?? '30' ?> min</span>
                    </div>
                </div>

                <div class="status-item">
                    <div class="status-icon">
                        <i class="fas fa-star"></i>
                        <span class="status-label">Customer Rating</span>
                    </div>
                    <div class="status-content">
                        <span class="status-value"><?= $kitchen['RATING'] ?? 'N/A' ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Service Areas & Delivery Card -->
    <div class="dashboard-card service-areas-card">
        <div class="card-header">
            <div class="card-title">
                <i class="fas fa-map-marker-alt"></i>
                <h3>Service Areas & Delivery</h3>
            </div>
            <a href="/business/dashboard/service-areas" class="btn btn-primary btn-sm">
                <i class="fas fa-cog"></i> Manage Areas
            </a>
        </div>
        <div class="card-body">
            <!-- Detailed Service Areas Table -->
            <?php if (!empty($serviceAreas)): ?>
                <div class="detailed-areas">
                    <table class="mini-table">
                        <thead>
                            <tr>
                                <th>Area Name</th>
                                <th>Delivery Fee</th>
                                <th>Min Order</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($serviceAreas as $area): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($area['AREA_NAME']) ?></strong>
                                        <?php if (!empty($area['CITY'])): ?>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($area['CITY']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="fee-amount">৳<?= number_format($area['DELIVERY_FEE'], 2) ?></span>
                                    </td>
                                    <td>
                                        <span class="min-order">৳<?= number_format($area['MIN_ORDER'], 2) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (count($serviceAreas) > 3): ?>
                        <div class="table-footer">
                            <small class="text-muted">
                                Showing <?= count($serviceAreas) ?> service areas
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="no-service-areas">
                    <i class="fas fa-map-marker-alt fa-2x text-muted"></i>
                    <p>No service areas configured</p>
                    <p class="text-muted">Add service areas to start receiving orders</p>
                    <a href="/business/dashboard/service-areas" class="btn btn-primary btn-sm mt-2">
                        <i class="fas fa-plus"></i> Add Areas
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Subscription Info -->
    <?php if ($subscription): ?>
        <div class="dashboard-card subscription-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-crown"></i>
                    <h3>Subscription Plan</h3>
                </div>
                <a href="/business/dashboard/subscriptions" class="btn btn-primary">
                    <i class="fas fa-cog"></i> Manage Subscription
                </a>
                <!-- <span class="plan-badge"><?= $subscription['PLAN_NAME'] ?></span> -->
            </div>
            <div class="card-body">
                <div class="subscription-plan">
                    <div class="plan-header">
                        <h4 class="plan-name"><?= htmlspecialchars($subscription['PLAN_NAME']) ?></h4>
                        <div class="plan-price">৳<?= number_format($subscription['MONTHLY_FEE'], 2) ?><span>/month</span></div>
                    </div>

                    <div class="plan-features">
                        <div class="feature-item">
                            <i class="fas fa-check"></i>
                            <span>Up to <?= $subscription['MAX_ITEMS'] ?> menu items</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check"></i>
                            <span><?= $subscription['COMMISSION_RATE'] ?>% commission</span>
                        </div>
                    </div>

                    <div class="subscription-status">
                        <div class="status-item">
                            <span class="label" style="margin-right: 5px;">Status:</span>
                            <span class="status-badge status-<?= strtolower($subscription['STATUS']) ?>">
                                <?= $subscription['STATUS'] ?>
                            </span>
                        </div>
                        <div class="status-item">
                            <span class="label" style="margin-right: 5px;">Started: </span>
                            <span class="value"> <?= dateFormat($subscription['START_DATE']) ?></span>
                        </div>
                        <div class="status-item">
                            <span class="label" style="margin-right: 5px;">Renews: </span>
                            <span class="value"> <?= dateFormat($subscription['END_DATE']) ?></span>
                        </div>
                    </div>

                    <?php
                    $daysLeft = ceil((strtotime($subscription['END_DATE']) - time()) / (60 * 60 * 24));
                    $progressPercent = min(100, max(0, (30 - $daysLeft) / 30 * 100));
                    ?>
                    <div class="renewal-progress">
                        <div class="progress-header">
                            <span class="progress-label">Renewal in <?= $daysLeft ?> days</span>
                            <span class="progress-percent"><?= number_format($progressPercent) ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= $progressPercent ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="dashboard-card quick-actions-card">
        <div class="card-header">
            <div class="card-title">
                <i class="fas fa-bolt"></i>
                <h3>Quick Actions</h3>
            </div>
        </div>
        <div class="card-body">
            <a href="/business/dashboard/menu-items" class="quick-action">
                <div class="action-icon orange">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="action-content">
                    <h4>Manage Menu</h4>
                    <p>Add or edit menu items</p>
                </div>
                <i class="fas fa-chevron-right action-arrow"></i>
            </a>

            <a href="/business/dashboard/orders" class="quick-action">
                <div class="action-icon blue">
                    <i class="fas fa-list-alt"></i>
                </div>
                <div class="action-content">
                    <h4>View Orders</h4>
                    <p>Process incoming orders</p>
                </div>
                <i class="fas fa-chevron-right action-arrow"></i>
            </a>

            <a href="/business/dashboard/analytics" class="quick-action">
                <div class="action-icon green">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="action-content">
                    <h4>Analytics</h4>
                    <p>View sales reports</p>
                </div>
                <i class="fas fa-chevron-right action-arrow"></i>
            </a>

            <a href="/business/dashboard/settings" class="quick-action">
                <div class="action-icon red">
                    <i class="fas fa-cog"></i>
                </div>
                <div class="action-content">
                    <h4>Settings</h4>
                    <p>Update business settings</p>
                </div>
                <i class="fas fa-chevron-right action-arrow"></i>
            </a>
        </div>
    </div>
</div>