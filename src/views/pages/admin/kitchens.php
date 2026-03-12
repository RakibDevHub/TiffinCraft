<?php
$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 50;
$totalKitchens = $data['totalKitchens'] ?? 0;
$totalPages = ceil($totalKitchens / $limit);
$kitchendata = $data['kitchendata'] ?? [];

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

include BASE_PATH . '/src/views/components/flash-popup.php';
?>

<!-- Page Header  -->
<div class="page-header">
    <h1 class="page-title"><?= htmlspecialchars(ucfirst($title)) ?></h1>
    <p class="page-subtitle">Manage kitchen accounts, view performance metrics, and suspend kitchens</p>
</div>

<!-- Filters and Search -->
<div class="filters-container">
    <div class="search-box">
        <i class="fas fa-search search-icon"></i>
        <input type="text" class="search-input" placeholder="Search kitchens by name or owner..." id="kitchenSearch">
    </div>

    <div class="filter-group">
        <select class="filter-select" id="approvalFilter">
            <option value="">All Approval Statuses</option>
            <option value="approved">Approved</option>
            <option value="pending">Pending</option>
            <option value="rejected">Rejected</option>
        </select>
        <select class="filter-select" id="statusFilter">
            <option value="">All Statuses</option>
            <option value="active">Active & Operating</option>
            <option value="suspended">Suspended</option>
            <option value="inactive">Inactive (No Active Subscription)</option>
            <option value="pending">Pending Approval</option>
            <option value="rejected">Rejected</option>
        </select>
    </div>

    <div class="action-buttons-group">
        <button class="btn btn-secondary" id="clearFilters">
            <i class="fas fa-times"></i> Clear
        </button>
    </div>
</div>

<!-- Kitchens Table -->
<div class="dashboard-card">
    <!-- Top Pagination -->
    <div class="card-header">
        <div class="pagination-info">
            Showing <?= ($page - 1) * $limit + 1 ?> to <?= min($page * $limit, $totalKitchens) ?> of <?= $totalKitchens ?> kitchens
        </div>
        <?php if ($totalPages > 1): ?>
            <div class="pagination-controls">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&limit=<?= $limit ?>" class="pagination-btn">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>

                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);

                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                    <a href="?page=<?= $i ?>&limit=<?= $limit ?>"
                        class="pagination-btn <?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&limit=<?= $limit ?>" class="pagination-btn">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="pagination-limit">
            <label>Show:</label>
            <select onchange="changeLimit(this.value)">
                <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100</option>
            </select>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive" style="overflow-x: auto;">
            <table class="users-table" style="line-height: 1;">
                <thead>
                    <tr>
                        <th>Kitchen</th>
                        <th>Owner</th>
                        <th>Subscription</th>
                        <th>Status</th>
                        <th>Performance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($kitchendata)): ?>
                        <tr>
                            <td colspan="6" class="no-data">
                                <i class="fas fa-utensils"></i>
                                <p>No kitchens found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($kitchendata as $kitchen): ?>
                            <?php
                            $approvalClass = 'status-badge status-' . strtolower($kitchen['APPROVAL_STATUS'] ?? 'pending');
                            $isKitchenSuspended = !empty($kitchen['IS_KITCHEN_SUSPENDED']) && $kitchen['IS_KITCHEN_SUSPENDED'] == 1;
                            $subscriptionStatus = $kitchen['SUBSCRIPTION_STATUS'] ?? 'none';
                            $subscriptionClass = 'status-badge status-' . ($subscriptionStatus === 'ACTIVE' ? 'active' : 'inactive');

                            $combinedStatus = 'unknown';
                            if ($isKitchenSuspended) {
                                $combinedStatus = 'suspended';
                            } elseif (($kitchen['APPROVAL_STATUS'] ?? '') === 'approved' && $subscriptionStatus === 'ACTIVE') {
                                $combinedStatus = 'active';
                            } elseif (($kitchen['APPROVAL_STATUS'] ?? '') === 'approved' && $subscriptionStatus !== 'ACTIVE') {
                                $combinedStatus = 'inactive';
                            } elseif (($kitchen['APPROVAL_STATUS'] ?? '') === 'pending') {
                                $combinedStatus = 'pending';
                            } elseif (($kitchen['APPROVAL_STATUS'] ?? '') === 'rejected') {
                                $combinedStatus = 'rejected';
                            }

                            ?>
                            <tr data-kitchen-id="<?= htmlspecialchars($kitchen['KITCHEN_ID'] ?? '') ?>"
                                data-approval="<?= strtolower($kitchen['APPROVAL_STATUS'] ?? 'pending') ?>"
                                data-combined-status="<?= $combinedStatus ?>">
                                <td style="width: auto;">
                                    <div class="user-info">
                                        <?php if (!empty($kitchen['COVER_IMAGE'])): ?>
                                            <img src="/uploads/kitchen/<?= htmlspecialchars($kitchen['COVER_IMAGE']) ?>" class="user-avatar" alt="<?= htmlspecialchars($kitchen['KITCHEN_NAME'] ?? '') ?>">
                                        <?php else: ?>
                                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($kitchen['KITCHEN_NAME'] ?? '') ?>&background=4a6cf7&color=fff" class="user-avatar" alt="<?= htmlspecialchars($kitchen['KITCHEN_NAME'] ?? '') ?>">
                                        <?php endif; ?>
                                        <div class="user-details" style="width: 100%;">
                                            <h4><?= htmlspecialchars($kitchen['KITCHEN_NAME'] ?? '') ?></h4>
                                            <p><?= htmlspecialchars($kitchen['KITCHEN_ADDRESS'] ?? '') ?></p>
                                            <?php if (!empty($kitchen['SIGNATURE_DISH'])): ?>
                                                <small class="text-muted">Signature: <?= htmlspecialchars($kitchen['SIGNATURE_DISH']) ?></small><br />
                                            <?php endif; ?>
                                            <?php if (!empty($kitchen['SERVICE_AREAS'])): ?>
                                                <small class="text-muted" title="<?= htmlspecialchars($kitchen['SERVICE_AREAS']) ?>">
                                                    <?= htmlspecialchars($kitchen['TOTAL_SERVICE_AREAS']) ?> service areas
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="user-details">
                                        <h4><?= htmlspecialchars($kitchen['OWNER_NAME'] ?? '') ?></h4>
                                        <p><?= htmlspecialchars($kitchen['OWNER_EMAIL'] ?? '') ?></p>
                                        <?php if (!empty($kitchen['OWNER_PHONE'])): ?>
                                            <small class="text-muted"><?= htmlspecialchars($kitchen['OWNER_PHONE']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($kitchen['PLAN_NAME'])): ?>
                                        <div class="subscription-info">
                                            <span class="subscription-name"><?= htmlspecialchars($kitchen['PLAN_NAME']) ?> - ৳<?= htmlspecialchars($kitchen['MONTHLY_FEE']) ?></span>
                                            <span class="<?= $subscriptionClass ?>">
                                                <i class="fas <?= $subscriptionStatus === 'ACTIVE' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                                                <?= htmlspecialchars($subscriptionStatus) ?>
                                            </span>
                                            <?php if (!empty($kitchen['SUBSCRIPTION_START'])): ?>
                                                <small class="text-muted">
                                                    <?= dateFormat($kitchen['SUBSCRIPTION_START']); ?> -
                                                    <?= dateFormat($kitchen['SUBSCRIPTION_END']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive">
                                            <i class="fas fa-exclamation-circle"></i> No Subscription
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="status-info">
                                        <?php if ($isKitchenSuspended): ?>
                                            <div class="status-with-details">
                                                <span class="status-badge status-suspended">
                                                    <i class="fas fa-ban"></i> Suspended
                                                </span>
                                                <?php if (!empty($kitchen['KITCHEN_SUSPENSION_REASON'])): ?>
                                                    <small class="text-muted" title="<?= htmlspecialchars($kitchen['KITCHEN_SUSPENSION_REASON']) ?>">
                                                        <?= htmlspecialchars(substr($kitchen['KITCHEN_SUSPENSION_REASON'], 0, 30)) ?>...
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="<?= $approvalClass ?>">
                                                <i class="fas <?= ($kitchen['APPROVAL_STATUS'] ?? '') === 'approved' ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                                                <?= htmlspecialchars($kitchen['APPROVAL_STATUS'] ?? 'Pending') ?>
                                            </span>
                                            <?php if (($kitchen['APPROVAL_STATUS'] ?? '') === 'approved'): ?>
                                                <small class="text-muted" style="margin-top: 0.5rem;">
                                                    <i class="fas <?= $subscriptionStatus === 'ACTIVE' ? 'fa-bolt text-success' : 'fa-power-off text-warning' ?>"></i>
                                                    <?= $subscriptionStatus === 'ACTIVE' ? 'Active Subscription' : 'No Active Subscription' ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="performance-metrics">
                                        <div class="metric">
                                            <span class="metric-value"><?= htmlspecialchars($kitchen['TOTAL_ORDERS'] ?? '0') ?></span>
                                            <span class="metric-label">Orders</span>
                                        </div>
                                        <div class="metric">
                                            <span class="metric-value"><?= htmlspecialchars($kitchen['COMPLETED_ORDERS'] ?? '0') ?></span>
                                            <span class="metric-label">Delivered</span>
                                        </div>
                                        <div class="metric">
                                            <span class="metric-value"><?= htmlspecialchars($kitchen['TOTAL_MENU_ITEMS'] ?? '0') ?></span>
                                            <span class="metric-label">Menu Items</span>
                                        </div>
                                        <div class="metric">
                                            <span class="metric-value">৳<?= number_format($kitchen['CURRENT_BALANCE'] ?? 0, 2) ?></span>
                                            <span class="metric-label">Balance</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons kitchen-table-btn">
                                        <?php if ($isKitchenSuspended): ?>
                                            <button class="btn-action btn-activate"
                                                onclick="openLiftSuspensionModal(
                                                    '<?= $kitchen['KITCHEN_ID'] ?>', 
                                                    '<?= htmlspecialchars(addslashes($kitchen['KITCHEN_NAME'])) ?>',
                                                    '<?= !empty($kitchen['KITCHEN_SUSPENSION_REASON']) ? htmlspecialchars(addslashes($kitchen['KITCHEN_SUSPENSION_REASON'])) : '' ?>',
                                                    '<?= !empty($kitchen['KITCHEN_SUSPENDED_UNTIL']) ? dateFormat($kitchen['KITCHEN_SUSPENDED_UNTIL'], 'M j, Y g:i A') : '' ?>'
                                                )"
                                                title="Lift Suspension">
                                                <i class="fas fa-unlock"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn-action btn-suspend"
                                                onclick="openSuspendModal('<?= $kitchen['KITCHEN_ID'] ?>','<?= htmlspecialchars(addslashes($kitchen['KITCHEN_NAME'])) ?>')"
                                                title="Suspend Kitchen">
                                                <i class="fa-solid fa-lock"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn-action btn-view" onclick="viewKitchenDetails('<?= $kitchen['KITCHEN_ID'] ?>')" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bottom Pagination -->
    <div class="card-footer">
        <?php if ($totalPages > 1): ?>
            <div class="table-pagination-bottom">
                <div class="pagination-info">
                    Showing <?= ($page - 1) * $limit + 1 ?> to <?= min($page * $limit, $totalKitchens) ?> of <?= $totalKitchens ?> kitchens
                </div>
                <div class="pagination-controls">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&limit=<?= $limit ?>" class="pagination-btn">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);

                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <a href="?page=<?= $i ?>&limit=<?= $limit ?>"
                            class="pagination-btn <?= $i == $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&limit=<?= $limit ?>" class="pagination-btn">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="pagination-limit">
                    <label>Show:</label>
                    <select onchange="changeLimit(this.value)">
                        <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                        <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Suspend Kitchen Modal -->
<div class="modal-overlay" id="suspendModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-ban"></i>
                Suspend Kitchen
            </h2>
            <button class="modal-close" onclick="closeModal('suspendModal')">&times;</button>
        </div>
        <form method="POST" action="/admin/dashboard/kitchens" id="suspendForm">
            <input type="hidden" name="action" value="suspend">
            <input type="hidden" name="kitchen_id" id="suspendKitchenId">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Kitchen</label>
                    <input type="text" class="form-control" id="suspendKitchenName" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">Suspension Period *</label>
                    <select class="form-control" name="period" id="suspendPeriod" required>
                        <option value="">Select period</option>
                        <option value="1">1 day</option>
                        <option value="3">3 days</option>
                        <option value="7">7 days</option>
                        <option value="14">14 days</option>
                        <option value="30">30 days</option>
                        <option value="permanent">Permanent</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Reason for Suspension *</label>
                    <textarea class="form-control" name="reason" id="suspendReason" rows="4" placeholder="Enter reason for suspension..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('suspendModal')">Cancel</button>
                <button type="submit" class="btn btn-danger">Suspend</button>
            </div>
        </form>
    </div>
</div>

<!-- Lift Suspension Modal -->
<div class="modal-overlay" id="liftSuspensionModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-unlock"></i>
                Lift Suspension
            </h2>
            <button class="modal-close" onclick="closeModal('liftSuspensionModal')">&times;</button>
        </div>
        <form method="POST" action="/admin/dashboard/kitchens" id="liftSuspensionForm">
            <input type="hidden" name="action" value="lift_suspension">
            <input type="hidden" name="kitchen_id" id="liftSuspensionKitchenId">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-body">
                <div class="confirmation-message">
                    <i class="fas fa-question-circle"></i>
                    <h3>Confirm Lifting Suspension</h3>
                    <p>You are about to lift the suspension for kitchen: <strong id="liftSuspensionKitchenName"></strong></p>
                    <p>This will restore their access to the system immediately.</p>
                </div>

                <div class="suspension-details" id="suspensionDetails" style="display: none;">
                    <h4>Suspension Details:</h4>
                    <div class="detail-row">
                        <span class="detail-label">Reason:</span>
                        <span class="detail-value" id="suspensionReason"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Suspended Until:</span>
                        <span class="detail-value" id="suspendedUntil"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('liftSuspensionModal')">Cancel</button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-unlock"></i> Lift Suspension
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Kitchen Details Modal -->
<div class="modal-overlay" id="kitchenDetailsModal">
    <div class="modal modal-large">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-utensils"></i>
                Kitchen Details & Analytics
            </h2>
            <button class="modal-close" onclick="closeModal('kitchenDetailsModal')">&times;</button>
        </div>
        <div class="modal-body">
            <?php foreach ($kitchendata as $kitchen): ?>
                <div class="kitchen-details-content" id="kitchen-details-<?= htmlspecialchars($kitchen['KITCHEN_ID'] ?? '') ?>" style="display: none;">
                    <div class="kitchen-details-container">
                        <div class="kitchen-profile-header">
                            <div class="kitchen-avatar-large">
                                <?php if (!empty($kitchen['COVER_IMAGE'])): ?>
                                    <img src="/uploads/kitchen/<?= htmlspecialchars($kitchen['COVER_IMAGE']) ?>" alt="<?= htmlspecialchars($kitchen['KITCHEN_NAME'] ?? '') ?>">
                                <?php else: ?>
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($kitchen['KITCHEN_NAME'] ?? '') ?>&background=4a6cf7&color=fff" alt="<?= htmlspecialchars($kitchen['KITCHEN_NAME'] ?? '') ?>">
                                <?php endif; ?>
                            </div>
                            <div class="kitchen-profile-info">
                                <h2><?= htmlspecialchars($kitchen['KITCHEN_NAME'] ?? 'N/A') ?></h2>
                                <p class="kitchen-address"><?= htmlspecialchars($kitchen['KITCHEN_ADDRESS'] ?? 'N/A') ?></p>
                                <div class="kitchen-badges">
                                    <?php
                                    $approvalClass = 'status-badge status-' . strtolower($kitchen['APPROVAL_STATUS'] ?? 'pending');
                                    $isKitchenSuspended = !empty($kitchen['IS_KITCHEN_SUSPENDED']) && $kitchen['IS_KITCHEN_SUSPENDED'] == 1;
                                    $statusClass = 'status-badge status-' . ($isKitchenSuspended ? 'suspended' : strtolower($kitchen['APPROVAL_STATUS'] ?? 'pending'));
                                    ?>
                                    <span class="<?= $approvalClass ?>">
                                        <i class="fas <?= ($kitchen['APPROVAL_STATUS'] ?? '') === 'approved' ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                                        <?= htmlspecialchars($kitchen['APPROVAL_STATUS'] ?? 'Pending') ?>
                                    </span>
                                    <?php if ($isKitchenSuspended): ?>
                                        <span class="status-badge status-suspended">
                                            <i class="fas fa-ban"></i> Suspended
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($kitchen['COMPLETION_RATE'])): ?>
                                        <span class="status-badge status-success">
                                            <i class="fas fa-trophy"></i> <?= htmlspecialchars($kitchen['COMPLETION_RATE']) ?>% Completion
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Analytics Overview Cards -->
                        <div class="analytics-overview">
                            <div class="analytics-card">
                                <div class="analytics-icon revenue">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div class="analytics-content">
                                    <h3>৳<?= number_format($kitchen['NET_EARNINGS'] ?? 0, 2) ?></h3>
                                    <p>Net Earnings</p>
                                    <small>Gross: ৳<?= number_format($kitchen['TOTAL_EARNINGS'] ?? 0, 2) ?></small>
                                </div>
                            </div>
                            <div class="analytics-card">
                                <div class="analytics-icon orders">
                                    <i class="fas fa-shopping-bag"></i>
                                </div>
                                <div class="analytics-content">
                                    <h3><?= number_format($kitchen['TOTAL_ORDERS'] ?? 0) ?></h3>
                                    <p>Total Orders</p>
                                    <small><?= number_format($kitchen['COMPLETED_ORDERS'] ?? 0) ?> delivered</small>
                                </div>
                            </div>
                            <div class="analytics-card">
                                <div class="analytics-icon commission">
                                    <i class="fas fa-percentage"></i>
                                </div>
                                <div class="analytics-content">
                                    <h3>৳<?= number_format($kitchen['TOTAL_COMMISSION'] ?? 0, 2) ?></h3>
                                    <p>Platform Commission</p>
                                    <small><?= !empty($kitchen['COMMISSION_RATE']) ? htmlspecialchars($kitchen['COMMISSION_RATE']) . '% rate' : 'N/A' ?></small>
                                </div>
                            </div>
                            <div class="analytics-card">
                                <div class="analytics-icon balance">
                                    <i class="fas fa-wallet"></i>
                                </div>
                                <div class="analytics-content">
                                    <h3>৳<?= number_format($kitchen['CURRENT_BALANCE'] ?? 0, 2) ?></h3>
                                    <p>Available Balance</p>
                                    <small>Ready for withdrawal</small>
                                </div>
                            </div>
                        </div>

                        <div class="kitchen-details-flex">
                            <!-- Kitchen Information -->
                            <div class="detail-section">
                                <h3><i class="fas fa-info-circle"></i> Kitchen Information</h3>
                                <div class="detail-row">
                                    <div class="detail-label">Kitchen Name</div>
                                    <div class="detail-value"><?= htmlspecialchars($kitchen['KITCHEN_NAME'] ?? 'N/A') ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Description</div>
                                    <div class="detail-value"><?= !empty($kitchen['DESCRIPTION']) ? htmlspecialchars($kitchen['DESCRIPTION']) : 'No description provided' ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Address</div>
                                    <div class="detail-value"><?= htmlspecialchars($kitchen['KITCHEN_ADDRESS'] ?? 'N/A') ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Signature Dish</div>
                                    <div class="detail-value"><?= !empty($kitchen['SIGNATURE_DISH']) ? htmlspecialchars($kitchen['SIGNATURE_DISH']) : 'Not specified' ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Experience</div>
                                    <div class="detail-value"><?= !empty($kitchen['YEARS_EXPERIENCE']) ? htmlspecialchars($kitchen['YEARS_EXPERIENCE']) . ' years' : 'Not specified' ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Average Prep Time</div>
                                    <div class="detail-value"><?= !empty($kitchen['AVG_PREP_TIME']) ? htmlspecialchars($kitchen['AVG_PREP_TIME']) . ' minutes' : 'Not specified' ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Joined TiffinCraft</div>
                                    <div class="detail-value"><?= dateFormat($kitchen['KITCHEN_CREATED_AT']); ?></div>
                                </div>
                            </div>

                            <!-- Owner Information -->
                            <div class="detail-section">
                                <h3><i class="fas fa-user"></i> Owner Information</h3>
                                <div class="detail-row">
                                    <div class="detail-label">Owner Name</div>
                                    <div class="detail-value"><?= htmlspecialchars($kitchen['OWNER_NAME'] ?? 'N/A') ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Email</div>
                                    <div class="detail-value"><?= htmlspecialchars($kitchen['OWNER_EMAIL'] ?? 'N/A') ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Phone</div>
                                    <div class="detail-value"><?= !empty($kitchen['OWNER_PHONE']) ? htmlspecialchars($kitchen['OWNER_PHONE']) : 'N/A' ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Owner Status</div>
                                    <div class="detail-value">
                                        <span class="status-badge status-<?= strtolower($kitchen['OWNER_STATUS'] ?? 'active') ?>">
                                            <?= htmlspecialchars($kitchen['OWNER_STATUS'] ?? 'Active') ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Service Areas -->
                            <div class="detail-section">
                                <h3><i class="fas fa-map-marker-alt"></i> Service Areas</h3>
                                <div class="detail-row">
                                    <div class="detail-label">Areas Served</div>
                                    <div class="detail-value">
                                        <?php if (!empty($kitchen['SERVICE_AREAS'])): ?>
                                            <?= htmlspecialchars($kitchen['SERVICE_AREAS']) ?>
                                            <small class="text-muted">(<?= htmlspecialchars($kitchen['TOTAL_SERVICE_AREAS']) ?> areas)</small>
                                        <?php else: ?>
                                            No service areas configured
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Subscription Information -->
                            <div class="detail-section">
                                <h3><i class="fas fa-credit-card"></i> Subscription Information</h3>
                                <?php if (!empty($kitchen['PLAN_NAME'])): ?>
                                    <div class="detail-row">
                                        <div class="detail-label">Plan Name</div>
                                        <div class="detail-value"><?= htmlspecialchars($kitchen['PLAN_NAME']) ?></div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Monthly Fee</div>
                                        <div class="detail-value">৳<?= !empty($kitchen['MONTHLY_FEE']) ? number_format($kitchen['MONTHLY_FEE'], 2) : '0.00' ?></div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Commission Rate</div>
                                        <div class="detail-value"><?= !empty($kitchen['COMMISSION_RATE']) ? htmlspecialchars($kitchen['COMMISSION_RATE']) . '%' : 'N/A' ?></div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Max Items</div>
                                        <div class="detail-value"><?= !empty($kitchen['MAX_ITEMS']) ? htmlspecialchars($kitchen['MAX_ITEMS']) : 'Unlimited' ?></div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Max Areas</div>
                                        <div class="detail-value"><?= !empty($kitchen['MAX_AREAS']) ? htmlspecialchars($kitchen['MAX_AREAS']) : 'Unlimited' ?></div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Status</div>
                                        <div class="detail-value">
                                            <span class="status-badge status-<?= strtolower($kitchen['SUBSCRIPTION_STATUS'] ?? 'inactive') ?>">
                                                <?= htmlspecialchars($kitchen['SUBSCRIPTION_STATUS'] ?? 'Inactive') ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Start Date</div>
                                        <div class="detail-value">
                                            <?php
                                            if (!empty($kitchen['SUBSCRIPTION_START'])) {
                                                echo dateFormat($kitchen['SUBSCRIPTION_START']);
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">End Date</div>
                                        <div class="detail-value">
                                            <?php
                                            if (!empty($kitchen['SUBSCRIPTION_END'])) {
                                                echo dateFormat($kitchen['SUBSCRIPTION_END']);
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="detail-row">
                                        <div class="detail-value">No active subscription</div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Performance Analytics -->
                            <div class="detail-section">
                                <h3><i class="fas fa-chart-line"></i> Performance Analytics</h3>
                                <div class="detail-row">
                                    <div class="detail-label">Total Orders</div>
                                    <div class="detail-value"><?= htmlspecialchars($kitchen['TOTAL_ORDERS'] ?? '0') ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Completed Orders</div>
                                    <div class="detail-value"><?= htmlspecialchars($kitchen['COMPLETED_ORDERS'] ?? '0') ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Pending Orders</div>
                                    <div class="detail-value"><?= htmlspecialchars($kitchen['PENDING_ORDERS'] ?? '0') ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Cancelled Orders</div>
                                    <div class="detail-value"><?= htmlspecialchars($kitchen['CANCELLED_ORDERS'] ?? '0') ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Order Completion Rate</div>
                                    <div class="detail-value">
                                        <span class="<?= ($kitchen['COMPLETION_RATE'] ?? 0) >= 80 ? 'text-success' : (($kitchen['COMPLETION_RATE'] ?? 0) >= 60 ? 'text-warning' : 'text-danger') ?>">
                                            <?= htmlspecialchars($kitchen['COMPLETION_RATE'] ?? '0') ?>%
                                        </span>
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Gross Revenue</div>
                                    <div class="detail-value">৳<?= number_format($kitchen['TOTAL_EARNINGS'] ?? 0, 2) ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Platform Commission</div>
                                    <div class="detail-value">৳<?= number_format($kitchen['TOTAL_COMMISSION'] ?? 0, 2) ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Net Revenue</div>
                                    <div class="detail-value text-success"><strong>৳<?= number_format($kitchen['NET_EARNINGS'] ?? 0, 2) ?></strong></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Available Balance</div>
                                    <div class="detail-value text-primary"><strong>৳<?= number_format($kitchen['CURRENT_BALANCE'] ?? 0, 2) ?></strong></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Weekly Earnings</div>
                                    <div class="detail-value">৳<?= number_format($kitchen['WEEKLY_EARNINGS'] ?? 0, 2) ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Monthly Earnings</div>
                                    <div class="detail-value">৳<?= number_format($kitchen['MONTHLY_EARNINGS'] ?? 0, 2) ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Last Order Date</div>
                                    <div class="detail-value">
                                        <?php
                                        if (!empty($kitchen['LAST_ORDER_DATE'])) {
                                            echo dateFormat($kitchen['LAST_ORDER_DATE'], 'M j, Y g:i A');
                                        } else {
                                            echo 'No orders yet';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Reviews & Menu -->
                            <div class="detail-section">
                                <h3><i class="fas fa-star"></i> Reviews & Menu</h3>
                                <div class="detail-row">
                                    <div class="detail-label">Average Rating</div>
                                    <div class="detail-value">
                                        <?php if (!empty($kitchen['AVERAGE_RATING'])): ?>
                                            <div class="rating-stars">
                                                <?php
                                                $rating = floatval($kitchen['AVERAGE_RATING']);
                                                $fullStars = floor($rating);
                                                $hasHalfStar = ($rating - $fullStars) >= 0.5;

                                                for ($i = 1; $i <= 5; $i++):
                                                    if ($i <= $fullStars):
                                                        echo '<i class="fas fa-star"></i>';
                                                    elseif ($i === $fullStars + 1 && $hasHalfStar):
                                                        echo '<i class="fas fa-star-half-alt"></i>';
                                                    else:
                                                        echo '<i class="far fa-star"></i>';
                                                    endif;
                                                endfor;
                                                ?>
                                                <span>(<?= htmlspecialchars($kitchen['AVERAGE_RATING']) ?>/5)</span>
                                            </div>
                                        <?php else: ?>
                                            No ratings yet
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Total Reviews</div>
                                    <div class="detail-value"><?= htmlspecialchars($kitchen['TOTAL_REVIEWS'] ?? '0') ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Total Menu Items</div>
                                    <div class="detail-value"><?= htmlspecialchars($kitchen['TOTAL_MENU_ITEMS'] ?? '0') ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Available Menu Items</div>
                                    <div class="detail-value"><?= htmlspecialchars($kitchen['AVAILABLE_MENU_ITEMS'] ?? '0') ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Menu Availability</div>
                                    <div class="detail-value">
                                        <?php
                                        $totalItems = $kitchen['TOTAL_MENU_ITEMS'] ?? 0;
                                        $availableItems = $kitchen['AVAILABLE_MENU_ITEMS'] ?? 0;
                                        $availabilityRate = $totalItems > 0 ? round(($availableItems / $totalItems) * 100, 2) : 0;
                                        ?>
                                        <span class="<?= $availabilityRate >= 80 ? 'text-success' : (($availabilityRate >= 50 ? 'text-warning' : 'text-danger')) ?>">
                                            <?= $availabilityRate ?>%
                                        </span>
                                        (<?= $availableItems ?>/<?= $totalItems ?> available)
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('kitchenDetailsModal')">Close</button>
            <button type="button" class="btn btn-primary" onclick="printKitchenDetails()">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>
    </div>
</div>

<script>
    function printKitchenDetails() {
        const activeKitchen = document.querySelector('.kitchen-details-content[style*="display: block"]');
        if (activeKitchen) {
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
            <html>
                <head>
                    <title>Kitchen Report - ${activeKitchen.querySelector('h2').textContent}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .kitchen-profile-header { display: flex; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 20px; }
                        .kitchen-avatar-large img { width: 80px; height: 80px; border-radius: 8px; margin-right: 20px; }
                        .kitchen-profile-info h2 { margin: 0; color: #333; }
                        .kitchen-badges { margin-top: 10px; }
                        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; margin-right: 8px; }
                        .analytics-overview { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin: 20px 0; }
                        .analytics-card { border: 1px solid #ddd; padding: 15px; border-radius: 8px; }
                        .kitchen-details-flex { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
                        .detail-section { margin-bottom: 20px; }
                        .detail-row { display: flex; justify-content: space-between; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid #eee; }
                        .detail-label { font-weight: bold; color: #555; }
                        @media print { body { margin: 0; } }
                    </style>
                </head>
                <body>
                    ${activeKitchen.innerHTML}
                </body>
            </html>
        `);
            printWindow.document.close();
            printWindow.print();
        }
    }
</script>