<?php
$user = $data['currentUser'];
$kitchen = $data['kitchen'];
$period = $data['period'];
$months = $data['months'];

$salesOverview = $data['salesOverview'];
$revenueTrend = $data['revenueTrend'];
$popularItems = $data['popularItems'];
$busyHours = $data['busyHours'];
$areaPerformance = $data['areaPerformance'];
$customerAnalytics = $data['customerAnalytics'];
$cancellationAnalytics = $data['cancellationAnalytics'];
$dailyPerformance = $data['dailyPerformance'];

function formatPrice($price)
{
    if (is_null($price)) {
        return '৳0.00';
    }
    return '৳' . number_format((float)$price, 2);
}

function formatNumber($number)
{
    if (is_null($number)) {
        return '0';
    }
    return number_format((int)$number);
}

function formatDecimal($number, $decimals = 1)
{
    if (is_null($number)) {
        return '0.0';
    }
    return number_format((float)$number, $decimals);
}

function formatDeliveryTime($minutes)
{
    if (is_null($minutes) || $minutes <= 0) {
        return '0 min';
    }

    $minutes = round($minutes); // keep your edited line

    $days = intdiv($minutes, 1440); // 1440 = 60 * 24
    $remainingAfterDays = $minutes % 1440;

    $hours = intdiv($remainingAfterDays, 60);
    $remainingMinutes = $remainingAfterDays % 60;

    $parts = [];

    if ($days > 0) {
        $parts[] = $days . 'd';
    }

    if ($hours > 0) {
        $parts[] = $hours . 'h';
    }

    if ($remainingMinutes > 0) {
        $parts[] = $remainingMinutes . 'm';
    }

    return implode(' ', $parts);
}

function format12Hour($hour24)
{
    $hour = (int)$hour24;
    if ($hour == 0) {
        return '12 AM';
    } elseif ($hour < 12) {
        return $hour . ' AM';
    } elseif ($hour == 12) {
        return '12 PM';
    } else {
        return ($hour - 12) . ' PM';
    }
}

function getTrendIcon($current, $previous)
{
    if ($previous == 0) return 'fa-minus';
    $change = (($current - $previous) / $previous) * 100;
    return $change >= 0 ? 'fa-arrow-up text-success' : 'fa-arrow-down text-danger';
}

function getTrendClass($current, $previous)
{
    if ($previous == 0) return '';
    $change = (($current - $previous) / $previous) * 100;
    return $change >= 0 ? 'positive' : 'negative';
}

// Ensure all data has proper default values
$salesOverview = array_merge([
    'TOTAL_ORDERS' => 0,
    'COMPLETED_ORDERS' => 0,
    'CANCELLED_ORDERS' => 0,
    'TOTAL_REVENUE' => 0,
    'AVG_DELIVERY_TIME' => 0,
    'AVG_ORDER_VALUE' => 0
], $salesOverview);

$customerAnalytics = array_merge([
    'TOTAL_CUSTOMERS' => 0,
    'NEW_CUSTOMERS_30D' => 0,
    'REPEAT_CUSTOMERS_90D' => 0,
    'AVG_CUSTOMER_SPEND' => 0
], $customerAnalytics);

include BASE_PATH . '/src/views/components/flash-popup.php';
?>

<!-- Page Header -->
<div class="page-header dashboard-overview">
    <div class="header-content">
        <div class="header-text">
            <h1 class="page-title" style="color: #fff;"><?= htmlspecialchars(ucfirst($title)) ?></h1>
            <p class="page-subtitle" style="color: #fff;">Track your business performance and make data-driven decisions</p>
        </div>
        <div class="header-actions">
            <form method="GET" class="period-selector" style="display: flex; gap: 10px; align-items: center;">
                <select name="period" class="form-control" onchange="this.form.submit()" style="width: auto;">
                    <option value="today" <?= $period == 'today' ? 'selected' : '' ?>>Today</option>
                    <option value="week" <?= $period == 'week' ? 'selected' : '' ?>>This Week</option>
                    <option value="month" <?= $period == 'month' ? 'selected' : '' ?>>This Month</option>
                    <option value="year" <?= $period == 'year' ? 'selected' : '' ?>>This Year</option>
                </select>
                <select name="months" class="form-control" onchange="this.form.submit()" style="width: auto;">
                    <option value="3" <?= $months == 3 ? 'selected' : '' ?>>3 Months Trend</option>
                    <option value="6" <?= $months == 6 ? 'selected' : '' ?>>6 Months Trend</option>
                    <option value="12" <?= $months == 12 ? 'selected' : '' ?>>12 Months Trend</option>
                </select>
            </form>
        </div>
    </div>
</div>

<!-- Overview Stats -->
<div class="stats-grid">
    <div class="dashboard-card stat-card">
        <div class="card-header">
            <div class="card-title">
                <p>Total Orders</p>
                <h1><?= formatNumber($salesOverview['TOTAL_ORDERS']) ?></h1>
            </div>
            <div class="card-icon blue"><i class="fas fa-shopping-bag"></i></div>
        </div>
        <div class="card-body stat-trend">
            <i class="fas fa-check-circle text-success"></i>
            <span><?= formatNumber($salesOverview['COMPLETED_ORDERS']) ?> completed</span>
        </div>
    </div>

    <div class="dashboard-card stat-card">
        <div class="card-header">
            <div class="card-title">
                <p>Total Revenue</p>
                <h1><?= formatPrice($salesOverview['TOTAL_REVENUE']) ?></h1>
            </div>
            <div class="card-icon green"><i class="fas fa-money-bill-wave"></i></div>
        </div>
        <div class="card-body stat-trend">
            <i class="fas fa-chart-line text-info"></i>
            <span>Avg: <?= formatPrice($salesOverview['AVG_ORDER_VALUE']) ?></span>
        </div>
    </div>

    <div class="dashboard-card stat-card">
        <div class="card-header">
            <div class="card-title">
                <p>Avg Delivery Time</p>
                <h1><?= formatDeliveryTime($salesOverview['AVG_DELIVERY_TIME']) ?></h1>
            </div>
            <div class="card-icon orange"><i class="fas fa-clock"></i></div>
        </div>
        <div class="card-body stat-trend">
            <i class="fas fa-bolt text-warning"></i>
            <span>Prep efficiency</span>
        </div>
    </div>

    <div class="dashboard-card stat-card">
        <div class="card-header">
            <div class="card-title">
                <p>Cancellation Rate</p>
                <h1>
                    <?php
                    $totalOrders = (int)$salesOverview['TOTAL_ORDERS'];
                    $cancelledOrders = (int)$salesOverview['CANCELLED_ORDERS'];
                    $cancellationRate = $totalOrders > 0 ? ($cancelledOrders / $totalOrders) * 100 : 0;
                    echo formatDecimal($cancellationRate, 1) . '%';
                    ?>
                </h1>
            </div>
            <div class="card-icon red"><i class="fas fa-ban"></i></div>
        </div>
        <div class="card-body stat-trend">
            <i class="fas fa-exclamation-triangle text-danger"></i>
            <span><?= formatNumber($salesOverview['CANCELLED_ORDERS']) ?> cancelled</span>
        </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="dashboard-grid">
    <!-- Revenue Trend Chart -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3>Revenue Trend (Last <?= $months ?> Months)</h3>
        </div>
        <div class="card-body" style="max-height: 600px; overflow: auto;">
            <?php if (!empty($revenueTrend)): ?>
                <div class="table-responsive">
                    <table class="analytics-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Total Orders</th>
                                <th>Completed Orders</th>
                                <th>Revenue</th>
                                <th>Avg Delivery Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($revenueTrend as $trend): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($trend['MONTH_NAME'] ?? 'N/A') ?></strong></td>
                                    <td><?= formatNumber($trend['TOTAL_ORDERS'] ?? 0) ?></td>
                                    <td><?= formatNumber($trend['COMPLETED_ORDERS'] ?? 0) ?></td>
                                    <td class="text-success"><strong><?= formatPrice($trend['REVENUE'] ?? 0) ?></strong></td>
                                    <td><?= formatDeliveryTime($trend['AVG_DELIVERY_TIME'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-chart-line"></i>
                    <p>No revenue data available for this period</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Popular Items -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3>Top Selling Items</h3>
        </div>
        <div class="card-body" style="max-height: 600px; overflow: auto;">
            <?php if (!empty($popularItems)): ?>
                <div class="popular-items-list">
                    <?php foreach ($popularItems as $index => $item): ?>
                        <div class="popular-item">
                            <div class="item-rank">#<?= $index + 1 ?></div>
                            <div class="item-info">
                                <div class="">
                                    <h4 class="item-name"><?= htmlspecialchars($item['ITEM_NAME'] ?? 'N/A') ?></h4>
                                    <div class="item-meta">
                                        <span class="item-price"><?= formatPrice($item['PRICE'] ?? 0) ?></span>
                                        <span class="item-prep">Avg prep: <?= formatDeliveryTime($item['AVG_PREP_TIME'] ?? 0) ?></span>
                                    </div>
                                    <div class="item-stats" style="flex-direction: row;">
                                        <div class="stat">
                                            <i class="fas fa-shopping-cart"></i>
                                            <span><?= formatNumber($item['TOTAL_ORDERS'] ?? 0) ?> orders</span>
                                        </div>
                                        <div class="stat">
                                            <i class="fas fa-box"></i>
                                            <span><?= formatNumber($item['TOTAL_QUANTITY'] ?? 0) ?> sold</span>
                                        </div>
                                        <div class="stat">
                                            <i class="fas fa-money-bill-wave"></i>
                                            <span><?= formatPrice($item['TOTAL_REVENUE'] ?? 0) ?></span>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-utensils"></i>
                    <p>No popular items data available</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Service Area Performance -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3>Service Area Performance</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($areaPerformance)): ?>
                <div class="table-responsive">
                    <table class="analytics-table">
                        <thead>
                            <tr>
                                <th>Area</th>
                                <th>Orders</th>
                                <th>Completed</th>
                                <th>Revenue</th>
                                <th>Avg Delivery</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($areaPerformance as $area): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($area['AREA_NAME'] ?? 'N/A') ?></strong></td>
                                    <td><?= formatNumber($area['TOTAL_ORDERS'] ?? 0) ?></td>
                                    <td><?= formatNumber($area['COMPLETED_ORDERS'] ?? 0) ?></td>
                                    <td class="text-success"><strong><?= formatPrice($area['REVENUE'] ?? 0) ?></strong></td>
                                    <td><?= formatDeliveryTime($area['AVG_DELIVERY_TIME'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-map-marker-alt"></i>
                    <p>No service area data available</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Customer Analytics -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3>Customer Insights</h3>
        </div>
        <div class="card-body" style="border: none;">
            <div class="customer-stats">
                <div class="customer-stat">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= formatNumber($customerAnalytics['TOTAL_CUSTOMERS']) ?></h3>
                        <p>Total Customers</p>
                    </div>
                </div>
                <div class="customer-stat">
                    <div class="stat-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= formatNumber($customerAnalytics['NEW_CUSTOMERS_30D']) ?></h3>
                        <p>New (30 days)</p>
                    </div>
                </div>
                <div class="customer-stat">
                    <div class="stat-icon">
                        <i class="fas fa-redo"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= formatNumber($customerAnalytics['REPEAT_CUSTOMERS_90D']) ?></h3>
                        <p>Repeat (90 days)</p>
                    </div>
                </div>
                <div class="customer-stat">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= formatPrice($customerAnalytics['AVG_CUSTOMER_SPEND']) ?></h3>
                        <p>Avg Spend</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Busy Hours -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3>Busy Hours Analysis</h3>
        </div>
        <div class="card-body" style="overflow: auto;">
            <?php if (!empty($busyHours)): ?>
                <div class="busy-hours">
                    <?php
                    // Get max order count for scaling
                    $maxOrderCount = 0;
                    foreach ($busyHours as $hour) {
                        $count = (int)($hour['ORDER_COUNT'] ?? 0);
                        if ($count > $maxOrderCount) {
                            $maxOrderCount = $count;
                        }
                    }
                    $maxOrderCount = max(1, $maxOrderCount);
                    ?>

                    <?php for ($hour = 0; $hour < 24; $hour++): ?>
                        <?php
                        $hourData = null;
                        foreach ($busyHours as $h) {
                            if ($h['HOUR_OF_DAY'] == sprintf('%02d', $hour)) {
                                $hourData = $h;
                                break;
                            }
                        }
                        $orderCount = $hourData['ORDER_COUNT'] ?? 0;
                        $revenue = $hourData['REVENUE'] ?? 0;
                        $height = min(100, ($orderCount / $maxOrderCount) * 100);
                        ?>
                        <div class="hour-bar">
                            <div class="bar-label"><?= format12Hour($hour) ?></div>
                            <div class="bar-container">
                                <div class="bar-fill" style="height: <?= $height ?>%;"
                                    title="<?= $orderCount ?> orders, <?= formatPrice($revenue) ?> revenue">
                                </div>
                            </div>
                            <div class="bar-value"><?= $orderCount ?></div>
                        </div>
                    <?php endfor; ?>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-clock"></i>
                    <p>No busy hours data available</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Daily Performance -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3>Last 7 Days Performance</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($dailyPerformance)): ?>
                <div class="table-responsive">
                    <table class="analytics-table">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Orders</th>
                                <th>Completed</th>
                                <th>Revenue</th>
                                <th>Avg Prep Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dailyPerformance as $day): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($day['DAY_NAME'] ?? 'N/A') ?></strong></td>
                                    <td><?= formatNumber($day['TOTAL_ORDERS'] ?? 0) ?></td>
                                    <td><?= formatNumber($day['COMPLETED_ORDERS'] ?? 0) ?></td>
                                    <td class="text-success"><strong><?= formatPrice($day['REVENUE'] ?? 0) ?></strong></td>
                                    <td><?= formatDeliveryTime($day['AVG_PREP_TIME'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-calendar-day"></i>
                    <p>No daily performance data available</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    /* Analytics specific styles */
    .analytics-table {
        width: 100%;
        border-collapse: collapse;
    }

    .analytics-table th {
        background: #f8f9fa;
        padding: 12px;
        text-align: left;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        color: #495057;
    }

    .analytics-table td {
        padding: 12px;
        border-bottom: 1px solid #dee2e6;
    }

    .analytics-table tr:hover {
        background: #f8f9fa;
    }

    .popular-items-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .popular-item {
        display: flex;
        align-items: center;
        padding: 12px;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }

    .item-rank {
        background: #007bff;
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-right: 12px;
        flex-shrink: 0;
    }

    .item-info {
        flex: 1;
    }

    .item-name {
        margin: 0 0 8px 0;
        font-size: 16px;
        color: #343a40;
    }

    .item-stats {
        display: flex;
        gap: 16px;
        margin-bottom: 8px;
    }

    .item-stats .stat {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 14px;
        color: #6c757d;
    }

    .item-meta {
        display: flex;
        gap: 12px;
        font-size: 14px;
    }

    .item-price {
        color: #28a745;
        font-weight: 600;
    }

    .item-prep {
        color: #6c757d;
    }

    .customer-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 20px;
    }

    .customer-stat {
        text-align: center;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }

    .customer-stat .stat-icon {
        font-size: 32px;
        margin-bottom: 12px;
    }

    .customer-stat h3 {
        margin: 0 0 8px 0;
        font-size: 24px;
        color: #343a40;
    }

    .customer-stat p {
        margin: 0;
        color: #6c757d;
        font-size: 14px;
    }

    .busy-hours {
        display: flex;
        gap: 8px;
        height: 200px;
        align-items: flex-end;
    }

    .hour-bar {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .bar-container {
        width: 20px;
        height: 120px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
        position: relative;
    }

    .bar-fill {
        width: 100%;
        background: linear-gradient(to top, #007bff, #0056b3);
        position: absolute;
        bottom: 0;
        transition: height 0.3s ease;
    }

    .bar-label {
        margin-top: 8px;
        font-size: 12px;
        color: #6c757d;
    }

    .bar-value {
        margin-top: 4px;
        font-size: 12px;
        font-weight: 600;
    }

    .period-selector {
        background: white;
        padding: 8px 16px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .period-selector .form-control {
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 6px 12px;
        font-size: 14px;
    }

    .no-data {
        text-align: center;
        padding: 40px 20px;
        color: #6c757d;
    }

    .no-data i {
        font-size: 48px;
        margin-bottom: 16px;
        color: #dee2e6;
    }

    .no-data p {
        margin: 0;
        font-size: 16px;
    }
</style>