<?php

$stats = $data['stats'] ?? [];
$growth  = $data['growth'] ?? [];
$activities  = $data['activities'] ?? [];
$totalUsers = ($stats['totalUsers'] ?? 0) - ($stats['totalAdmins'] ?? 0);

$masterWalletValue = $stats['masterWallet'] ?? 0;
$orderWalletValue = $stats['orderWallet']['total_amount'] ?? 0;
$orderCommissionValue = $stats['orderCommission'] ?? 0;
$subscriptionFeeValue = $stats['subscriptionFee'] ?? 0;
$sellerWithdrawalsValue = $stats['sellerWithdrawals'] ?? 0;
$pendingWithdrawalsValue = $stats['pendingWithdrawls'] ?? 0;
$orderTotalValue = $stats['orderWallet']['order_total'] ?? 0;
$orderRefundsValue = $stats['orderRefunds']['cancelled_total_amount'] ?? 0;
$buyerRefundsValue = $stats['buyerRefunds'] ?? 0;
$pendingRefundsValue = $stats['pendingRefunds'] ?? 0;

$revenueWalletValue = $orderCommissionValue + $subscriptionFeeValue;
$withdrawableBalanceValue = $orderTotalValue - ($orderCommissionValue + $sellerWithdrawalsValue);
$refundReserveValue = $orderRefundsValue - $buyerRefundsValue;

$masterWallet = number_format($masterWalletValue, 2);
$revenueWallet = number_format($revenueWalletValue, 2);
$orderWallet = number_format($orderWalletValue, 2);

$orderCommission = number_format($orderCommissionValue, 2);
$subscriptionFee = number_format($subscriptionFeeValue, 2);

$withdrawableBalance = number_format($withdrawableBalanceValue, 2);
$sellerWithdrawals = number_format($sellerWithdrawalsValue, 2);
$pendingWithdrawls = number_format($pendingWithdrawalsValue);

$orderRefunds = number_format($refundReserveValue, 2);
$buyerRefunds = number_format($buyerRefundsValue, 2);
$pendingRefunds = number_format($pendingRefundsValue);

$currentMonth = date('F');
?>

<!-- Page Header  -->
<div class="page-header dashboard-overview admin-dashboard">
    <div class="header-content">
        <div class="header-text">
            <h1 class="page-title" style="color: #fff;"><?= htmlspecialchars(ucfirst($title)) ?></h1>
            <p class="page-subtitle" style="color: #fff;">Quick access to manage users, view analytics, and monitor platform health</p>
        </div>
        <div class="header-actions">
            <span class="current-time" id="currentTime"><?= date('l, F j, Y g:i A') ?></span>
            <span class="current-balance">Master Wallet: ৳<?= $masterWallet ?></span>
        </div>
    </div>
</div>

<div class="dashboard-container">
    <!-- Stats Cards Grid -->
    <div class="stats-grid">
        <!-- Total Earnings Card -->
        <div class="dashboard-card stat-card highlight">
            <div class="card-header">
                <div class="card-title">
                    <p>Revenue Wallet</p>
                    <h1>৳<?= $revenueWallet ?></h1>
                </div>
                <div class="card-icon"><i class="fa-solid fa-coins"></i></div>
            </div>
            <div class="card-body">
                <p>Order Commission: <span class="value">৳<?= $orderCommission ?></span></p>
                <p>Subscriptions Fees: <span class="value">৳<?= $subscriptionFee ?></span></p>
            </div>
        </div>

        <!-- Order Balance Card -->
        <div class="dashboard-card stat-card">
            <div class="card-header">
                <div class="card-title">
                    <p>Withdrawable Balance</p>
                    <h1>৳<?= $withdrawableBalance ?></h1>
                </div>
                <div class="card-icon"><i class="fa-solid fa-wallet"></i></div>
            </div>
            <div class="card-body">
                <p>Withdrawals: <span class="value">৳<?= $sellerWithdrawals ?></span></p>
                <p>Pending Requests: <span class="value"><?= $pendingWithdrawls ?></span></p>
            </div>
        </div>

        <!-- Refunds Card -->
        <div class="dashboard-card stat-card">
            <div class="card-header">
                <div class="card-title">
                    <p>Order Refunds</p>
                    <h1>৳<?= $orderRefunds ?></h1>
                </div>
                <div class="card-icon orange"><i class="fa-solid fa-rotate-right"></i></div>
            </div>
            <div class="card-body">
                <p>Refunds: <span class="value">৳<?= $buyerRefunds ?></span></p>
                <p>Pending Request: <span class="value"><?= $pendingRefunds ?></span></p>
            </div>
        </div>

        <!-- Users Card -->
        <div class="dashboard-card stat-card">
            <div class="card-header">
                <div class="card-title">
                    <p>Users</p>
                    <h1><?= number_format($totalUsers) ?></h1>
                </div>
                <div class="card-icon green"><i class="fa-solid fa-users"></i></div>
            </div>
            <div class="card-body">
                <p>Buyers: <span class="value"><?= number_format($stats['totalBuyers'] ?? 0) ?></span></p>
                <p>Sellers: <span class="value"><?= number_format($stats['totalSellers'] ?? 0) ?></span></p>
            </div>
        </div>

        <!-- Orders Card -->
        <div class="dashboard-card stat-card">
            <div class="card-header">
                <div class="card-title">
                    <p>Orders</p>
                    <h1><?= number_format($stats['totalOrders'] ?? 0) ?></h1>
                </div>
                <div class="card-icon blue"><i class="fa-solid fa-cart-shopping"></i></div>
            </div>
            <div class="card-body">
                <p>Completed: <span class="value"><?= number_format($stats['complectedOrders'] ?? 0) ?></span></p>
                <p>Cancelled: <span class="value"><?= number_format($stats['cancelledOrders'] ?? 0) ?></span></p>
            </div>
        </div>
    </div>

    <!-- Growth Overview -->
    <div class="dashboard-card chart-card full-width">
        <h2>Growth Overview</h2>
        <div class="chart-grid">
            <div class="chart-box">
                <h3>Platform Income
                    <span class="trend-label" id="incomeTrendLabel"></span>
                </h3>
                <div id="incomeChartContainer">
                    <?php if (!empty($growth['incomeGrowth'])): ?>
                        <canvas id="incomeChart"></canvas>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-money-bill-wave"></i>
                            <p>No income data available</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="chart-legend">
                    <span class="legend-item"><i class="fas fa-square" style="color: #4a6cf7;"></i> Order Commissions</span>
                    <span class="legend-item"><i class="fas fa-square" style="color: #28a745;"></i> Subscription Fees</span>
                </div>
            </div>

            <div class="chart-box">
                <h3>Orders Analytics
                    <span class="trend-label" id="ordersTrendLabel"></span>
                </h3>
                <div id="ordersChartContainer">
                    <?php if (!empty($growth['orderGrowth'])): ?>
                        <canvas id="ordersChart"></canvas>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-shopping-cart"></i>
                            <p>No order data available</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="chart-legend">
                    <span class="legend-item"><i class="fas fa-square" style="color: #4a6cf7;"></i> Received</span>
                    <span class="legend-item"><i class="fas fa-square" style="color: #28a745;"></i> Completed</span>
                    <span class="legend-item"><i class="fas fa-square" style="color: #dc3545;"></i> Canceled</span>
                </div>
            </div>

            <div class="chart-box">
                <h3>User Growth
                    <span class="trend-label" id="usersTrendLabel"></span>
                </h3>
                <div id="usersChartContainer">
                    <?php if (!empty($growth['userGrowth'])): ?>
                        <canvas id="usersChart"></canvas>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-users"></i>
                            <p>No user data available</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="chart-legend">
                    <span class="legend-item"><i class="fas fa-square" style="color: #4a6cf7;"></i> Buyers</span>
                    <span class="legend-item"><i class="fas fa-square" style="color: #28a745;"></i> Sellers</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Section -->
    <div class="footer-grid">
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div class="top-seller">
                <div class="dashboard-card">
                    <div class="" style="display: flex; flex-direction: row; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h2>Top Sellers</h2>
                        <span><?= $currentMonth ?></span>
                    </div>
                    <div id="topSellersContainer">
                        <?php if (!empty($stats['topSellers'])): ?>
                            <div class="sellers-list">
                                <?php foreach ($stats['topSellers'] as $index => $seller): ?>
                                    <div class="seller-item">
                                        <div class="seller-rank">#<?= $index + 1 ?></div>
                                        <div class="seller-image">
                                            <?php if ($seller['COVER_IMAGE']): ?>
                                                <img src="/uploads/kitchen/<?= htmlspecialchars($seller['COVER_IMAGE']) ?>"
                                                    alt="<?= htmlspecialchars($seller['KITCHEN_NAME']) ?>">
                                            <?php else: ?>
                                                <i class="fa-solid fa-store"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="seller-info">
                                            <h4 class="seller-name"><?= htmlspecialchars($seller['KITCHEN_NAME']) ?></h4>
                                            <p class="owner-name">By <?= htmlspecialchars($seller['OWNER_NAME']) ?></p>
                                            <div class="seller-stats">
                                                <div class="stat">
                                                    <i class="fas fa-shopping-cart"></i>
                                                    <span><?= number_format($seller['TOTAL_ORDERS']) ?> orders</span>
                                                </div>
                                                <div class="stat">
                                                    <i class="fas fa-money-bill-wave"></i>
                                                    <span>৳<?= number_format($seller['TOTAL_REVENUE']) ?></span>
                                                </div>
                                                <?php if ($seller['AVG_RATING'] > 0): ?>
                                                    <div class="stat">
                                                        <i class="fas fa-star"></i>
                                                        <span><?= $seller['AVG_RATING'] ?> (<?= number_format($seller['TOTAL_REVIEWS']) ?>)</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-store"></i>
                                <p>No seller data available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="popular-item">
                <div class="dashboard-card">
                    <div class="" style="display: flex; flex-direction: row; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h2>Popular Items</h2>
                        <span><?= $currentMonth ?></span>
                    </div>
                    <div id="popularItemsContainer">
                        <?php if (!empty($stats['popularItems'])): ?>
                            <div class="items-list">
                                <?php foreach ($stats['popularItems'] as $index => $item): ?>
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
                                            <div class="">
                                                <h4 class="item-name"><?= htmlspecialchars($item['ITEM_NAME']) ?></h4>
                                                <p class="kitchen-name">By <?= htmlspecialchars($item['KITCHEN_NAME']) ?></p>
                                                <div class="item-price">৳<?= number_format($item['PRICE'], 2) ?></div>
                                            </div>
                                            <div class="item-stats">
                                                <div class="stat">
                                                    <i class="fas fa-shopping-basket"></i>
                                                    <span><?= number_format($item['TOTAL_QUANTITY']) ?> sold</span>
                                                </div>
                                                <div class="stat">
                                                    <i class="fas fa-receipt"></i>
                                                    <span><?= number_format($item['TOTAL_ORDERS']) ?> orders</span>
                                                </div>
                                                <?php if ($item['AVG_RATING'] > 0): ?>
                                                    <div class="stat">
                                                        <i class="fas fa-star"></i>
                                                        <span><?= $item['AVG_RATING'] ?> (<?= number_format($item['TOTAL_REVIEWS']) ?>)</span>
                                                    </div>
                                                <?php endif; ?>
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
            </div>
        </div>

        <div class="activities-section">
            <div class="dashboard-card activities">
                <h2>Recent Activities</h2>
                <ul>
                    <?php if (!empty($activities)): ?>
                        <?php foreach ($activities as $activity): ?>
                            <li>
                                <?php
                                $iconClass = 'fas fa-bell';
                                $iconColor = 'orange';

                                switch ($activity['type']) {
                                    case 'order':
                                        $iconClass = 'fas fa-shopping-cart';
                                        $iconColor = 'blue';
                                        break;
                                    case 'payment_in':
                                        $iconClass = 'fas fa-circle-arrow-down';
                                        $iconColor = 'green';
                                        break;
                                    case 'payment_out':
                                        $iconClass = 'fas fa-circle-arrow-up';
                                        $iconColor = 'orange';
                                        break;
                                    case 'refund_request':
                                        $iconClass = 'fas fa-undo-alt';
                                        $iconColor = 'red';
                                        break;
                                    case 'withdrawal_request':
                                        $iconClass = 'fas fa-hand-holding-usd';
                                        $iconColor = 'orange';
                                        break;
                                    case 'subscription':
                                        $iconClass = 'fas fa-crown';
                                        $iconColor = 'blue';
                                        break;
                                    case 'kitchen':
                                        $iconClass = 'fas fa-store';
                                        $iconColor = 'blue';
                                        break;
                                    case 'user':
                                        $iconClass = 'fas fa-user-plus';
                                        $iconColor = 'green';
                                        break;
                                    case 'review':
                                        $iconClass = 'fas fa-star';
                                        $iconColor = 'orange';
                                        break;
                                    case 'warning':
                                        $iconClass = 'fas fa-exclamation-triangle';
                                        $iconColor = 'red';
                                        break;
                                    case 'info':
                                        $iconClass = 'fas fa-info-circle';
                                        $iconColor = 'blue';
                                        break;
                                    case 'success':
                                        $iconClass = 'fas fa-check-circle';
                                        $iconColor = 'green';
                                        break;
                                }
                                ?>
                                <div class="card-icon <?= $iconColor ?>">
                                    <i class="<?= $iconClass ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <?= htmlspecialchars($activity['description']) ?>
                                    <div class="activity-time">
                                        <?= htmlspecialchars($activity['user_relation'] ?? ''); ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="no-activity">No recent activities found</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const incomeData = <?= json_encode($growth['incomeGrowth'] ?? []) ?>;
    const orderData = <?= json_encode($growth['orderGrowth'] ?? []) ?>;
    const userData = <?= json_encode($growth['userGrowth'] ?? []) ?>;

    // Format month name
    function formatMonthName(dateString) {
        const date = new Date(dateString + '-01');
        return date.toLocaleDateString('en-US', {
            month: 'short',
            year: 'numeric'
        });
    }

    // fill missing months for the labels
    function fillMissingMonths(data, months, defaultData = {}) {
        const filledData = [];
        const dataMap = new Map(data.map(item => [item.MONTH, item]));

        months.forEach(month => {
            if (dataMap.has(month)) {
                const item = dataMap.get(month);
                // Ensure consistent month name format
                item.MONTH_NAME = formatMonthName(month);
                filledData.push(item);
            } else {
                filledData.push({
                    MONTH: month,
                    MONTH_NAME: formatMonthName(month),
                    ...defaultData
                });
            }
        });

        return filledData;
    }

    function getLast12Months() {
        const months = [];
        for (let i = 0; i < 12; i++) {
            const date = new Date();
            date.setMonth(date.getMonth() - i);
            months.unshift(date.toISOString().slice(0, 7));
        }
        return months;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const last12Months = getLast12Months();

        // Process and fill missing months
        const processedIncomeData = fillMissingMonths(incomeData, last12Months, {
            ORDER_COMMISSIONS: 0,
            SUBSCRIPTION_FEES: 0
        });

        const processedOrderData = fillMissingMonths(orderData, last12Months, {
            TOTAL_ORDERS: 0,
            COMPLETED_ORDERS: 0,
            CANCELLED_ORDERS: 0
        });

        const processedUserData = fillMissingMonths(userData, last12Months, {
            NEW_BUYERS: 0,
            NEW_SELLERS: 0
        });

        // Income Chart
        if (processedIncomeData.length > 0) {
            new Chart(document.getElementById('incomeChart'), {
                type: 'bar',
                data: {
                    labels: processedIncomeData.map(r => r.MONTH_NAME),
                    datasets: [{
                            label: 'Order Commissions',
                            data: processedIncomeData.map(r => r.ORDER_COMMISSIONS),
                            backgroundColor: '#4a6cf7'
                        },
                        {
                            label: 'Subscription Fees',
                            data: processedIncomeData.map(r => r.SUBSCRIPTION_FEES),
                            backgroundColor: '#28a745'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '৳' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }

        // Orders Chart
        if (processedOrderData.length > 0) {
            new Chart(document.getElementById('ordersChart'), {
                type: 'bar',
                data: {
                    labels: processedOrderData.map(r => r.MONTH_NAME),
                    datasets: [{
                            label: 'Orders Received',
                            data: processedOrderData.map(r => r.TOTAL_ORDERS),
                            backgroundColor: '#4a6cf7'
                        },
                        {
                            label: 'Orders Completed',
                            data: processedOrderData.map(r => r.COMPLETED_ORDERS),
                            backgroundColor: '#28a745'
                        },
                        {
                            label: 'Orders Canceled',
                            data: processedOrderData.map(r => r.CANCELLED_ORDERS),
                            backgroundColor: '#dc3545'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Users Chart
        if (processedUserData.length > 0) {
            new Chart(document.getElementById('usersChart'), {
                type: 'line',
                data: {
                    labels: processedUserData.map(r => r.MONTH_NAME),
                    datasets: [{
                            label: 'Buyers',
                            data: processedUserData.map(r => r.NEW_BUYERS),
                            borderColor: '#4a6cf7',
                            backgroundColor: 'rgba(74, 108, 247, 0.1)',
                            fill: true,
                            tension: 0.3
                        },
                        {
                            label: 'Sellers',
                            data: processedUserData.map(r => r.NEW_SELLERS),
                            borderColor: '#28a745',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            fill: true,
                            tension: 0.3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    });
</script>