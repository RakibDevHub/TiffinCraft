<?php
$user = $data['currentUser'];
$kitchen = $data['kitchen'];
$activeSubscription = $data['activeSubscription'];
$subscriptionHistory = $data['subscriptionHistory'];
$lastSubscription = $data['lastSubscription'];
$plans = $data['plans'];

$allSubscriptions = $subscriptionHistory;
if ($activeSubscription) {
    array_unshift($allSubscriptions, $activeSubscription);
}

function dateFormat($dateString, $format = 'M j, Y g:i A')
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

include BASE_PATH . '/src/views/components/flash-popup.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title"><?= htmlspecialchars(ucfirst($title)) ?></h1>
    <p class="page-subtitle">Manage your subscription plans and billing</p>
</div>

<!-- Subscription Plans Grid -->
<div class="dashboard-card" style="margin-bottom: 30px;">
    <div class="card-header">
        <h3>Available Subscription Plans</h3>
        <p>Choose the plan that best fits your business needs</p>
    </div>
    <div class="card-body">
        <div class="plans-grid">
            <?php if (empty($plans)): ?>
                <div class="no-plans">
                    <i class="fas fa-tags"></i>
                    <h4>No subscription plans available</h4>
                    <p>Please contact support for assistance.</p>
                </div>
            <?php else: ?>
                <?php foreach ($plans as $plan): ?>
                    <div class="plan-card <?= $activeSubscription && $plan['PLAN_ID'] == $activeSubscription['PLAN_ID'] ? 'current-plan' : '' ?>"
                        style="display: flex; flex-direction: column; justify-content: space-between;">
                        <div class="">
                            <?php if ($activeSubscription && $plan['PLAN_ID'] == $activeSubscription['PLAN_ID']): ?>
                                <div class="plan-badge">Current Plan</div>
                            <?php elseif ($plan['IS_HIGHLIGHT']): ?>
                                <div class="plan-badge">Most Popular</div>
                            <?php endif; ?>

                            <div class="plan-header">
                                <h4><?= htmlspecialchars($plan['PLAN_NAME']) ?></h4>
                                <div class="plan-price">
                                    <span class="price">৳<?= number_format($plan['MONTHLY_FEE'], 2) ?></span>
                                    <span class="period">/month</span>
                                </div>
                            </div>

                            <div class="plan-features">
                                <div class="feature-item">
                                    <i class="fas fa-check"></i>
                                    <span>Up to <?= $plan['MAX_ITEMS'] ?> menu items</span>
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-check"></i>
                                    <span><?= $plan['COMMISSION_RATE'] ?>% commission rate</span>
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-check"></i>
                                    <span>24/7 customer support</span>
                                </div>
                            </div>
                        </div>

                        <div class="plan-actions">
                            <?php if (!$activeSubscription && !$lastSubscription): ?>
                                <form method="POST" action="/business/dashboard/subscriptions">
                                    <input type="hidden" name="action" value="change_plan">
                                    <input type="hidden" name="plan_id" value="<?= $plan['PLAN_ID'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Subscribe
                                    </button>
                                </form>
                            <?php elseif (!$activeSubscription && $lastSubscription): ?>
                                <?php if ($plan['PLAN_ID'] == $lastSubscription['PLAN_ID']): ?>
                                    <form method="POST" action="/business/dashboard/subscriptions">
                                        <input type="hidden" name="action" value="change_plan">
                                        <input type="hidden" name="plan_id" value="<?= $plan['PLAN_ID'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-sync-alt"></i> Renew Plan
                                        </button>
                                    </form>
                                <?php elseif ($plan['MONTHLY_FEE'] > $lastSubscription['MONTHLY_FEE']): ?>
                                    <form method="POST" action="/business/dashboard/subscriptions">
                                        <input type="hidden" name="action" value="change_plan">
                                        <input type="hidden" name="plan_id" value="<?= $plan['PLAN_ID'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-arrow-up"></i> Upgrade Plan
                                        </button>
                                    </form>
                                <?php elseif ($plan['MONTHLY_FEE'] < $lastSubscription['MONTHLY_FEE']): ?>
                                    <form method="POST" action="/business/dashboard/subscriptions">
                                        <input type="hidden" name="action" value="change_plan">
                                        <input type="hidden" name="plan_id" value="<?= $plan['PLAN_ID'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <button type="submit" class="btn btn-outline-primary">
                                            <i class="fas fa-arrow-down"></i> Downgrade Plan
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php elseif ($activeSubscription): ?>
                                <?php if ($plan['PLAN_ID'] == $activeSubscription['PLAN_ID']): ?>
                                    <small class="text-muted d-block mt-1">
                                        Active until <?= date('M j, Y', strtotime($activeSubscription['END_DATE'])) ?>
                                    </small>
                                    <span class="btn btn-disabled">Current Plan</span>
                                <?php elseif ($plan['MONTHLY_FEE'] > $activeSubscription['MONTHLY_FEE']): ?>
                                    <form method="POST" action="/business/dashboard/subscriptions">
                                        <input type="hidden" name="action" value="change_plan">
                                        <input type="hidden" name="plan_id" value="<?= $plan['PLAN_ID'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-arrow-up"></i> Upgrade Plan
                                        </button>
                                    </form>
                                <?php elseif ($plan['MONTHLY_FEE'] < $activeSubscription['MONTHLY_FEE']): ?>
                                    <form method="POST" action="/business/dashboard/subscriptions">
                                        <input type="hidden" name="action" value="change_plan">
                                        <input type="hidden" name="plan_id" value="<?= $plan['PLAN_ID'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <button type="submit" class="btn btn-outline-primary">
                                            <i class="fas fa-arrow-down"></i> Downgrade Plan
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Enhanced Subscription History Table -->
<div class="dashboard-card">
    <div class="card-header">
        <div class="pagination-info">
            Showing <?= count($allSubscriptions) ?> subscription records
        </div>
        <div class="action-buttons-group">
            <a href="?export=subscription_history" class="btn btn-secondary">
                <i class="fas fa-download"></i> Export CSV
            </a>
        </div>
    </div>

    <div class="card-body">
        <?php if (empty($allSubscriptions)): ?>
            <div class="no-subscriptions">
                <i class="fas fa-history"></i>
                <h4>No subscription history</h4>
                <p>You don't have any subscription records yet.</p>
            </div>
        <?php else: ?>
            <div class="subscription-table-container">
                <table class="subscription-table">
                    <thead>
                        <tr>
                            <th>Plan Details</th>
                            <th>Payment Info</th>
                            <th>Status</th>
                            <th>Type</th>
                            <th>Period</th>
                            <th>Features</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allSubscriptions as $subscription): ?>
                            <?php
                            $startDate = strtotime($subscription['START_DATE']);
                            $endDate = strtotime($subscription['END_DATE']);
                            $currentTime = time();
                            $isActive = $subscription['STATUS'] === 'ACTIVE' && $endDate >= $currentTime;
                            $isExpired = $subscription['STATUS'] === 'ACTIVE' && $endDate < $currentTime;
                            $status = $isActive ? 'ACTIVE' : ($isExpired ? 'EXPIRED' : $subscription['STATUS']);
                            ?>
                            <tr class="<?= $isActive ? 'active-subscription' : '' ?>">
                                <td>
                                    <div class="plan-info">
                                        <strong><?= htmlspecialchars($subscription['PLAN_NAME']) ?></strong>
                                        <div class="plan-price">৳<?= number_format($subscription['MONTHLY_FEE'], 2) ?>/month</div>
                                        <small class="text-muted">ID: <?= $subscription['PLAN_ID'] ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="payment-info" style="display: flex; flex-direction: column;">
                                        <div class="amount-paid">
                                            <strong>৳<?= number_format($subscription['PAYMENT_AMOUNT'] ?? $subscription['MONTHLY_FEE'], 2) ?></strong>
                                        </div>
                                        <?php if (!empty($subscription['PAYMENT_METHOD'])): ?>
                                            <small class="text-muted"><?= $subscription['PAYMENT_METHOD'] ?></small>
                                        <?php endif; ?>
                                        <?php if (!empty($subscription['TRANSACTION_ID'])): ?>
                                            <small class="text-muted">TXN: <?= $subscription['TRANSACTION_ID'] ?></small>
                                        <?php endif; ?>
                                        <?php if (!empty($subscription['PAYMENT_DATE'])): ?>
                                            <small class="text-muted">Paid: <?= dateFormat($subscription['PAYMENT_DATE']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($status) ?>">
                                        <?= $status ?>
                                    </span>
                                    <?php if ($isExpired): ?>
                                        <small class="text-muted d-block">Auto-expired</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="type-badge type-<?= strtolower($subscription['CHANGE_TYPE'] ?? 'primary') ?>">
                                        <?= $subscription['CHANGE_TYPE'] ?? 'N/A' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="period-info">
                                        <div class="start-date"><?= date('M j, Y', $startDate) ?></div>
                                        <div class="text-muted">to</div>
                                        <div class="end-date"><?= date('M j, Y', $endDate) ?></div>
                                        <div class="duration">
                                            <small class="text-muted">
                                                <?= round(($endDate - $startDate) / (60 * 60 * 24)) ?> days
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="features-list">
                                        <small>
                                            <i class="fas fa-utensils" style="margin-right: 5px;"></i> <?= $subscription['MAX_ITEMS'] ?> menu items<br>
                                            <i class="fas fa-percentage" style="margin-right: 5px;"></i> <?= $subscription['COMMISSION_RATE'] ?>% commission
                                            <i class="fas fa-map-marker-alt" style="margin-right: 5px;"></i> 24/7 customer support <br>
                                        </small>
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