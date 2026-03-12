<?php
$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 50;
$totalPlans = $data['totalPlans'] ?? 0;
$totalPages = ceil($totalPlans / $limit);
$plansData = $data['plansData'] ?? [];

include BASE_PATH . '/src/views/components/flash-popup.php';
?>

<!-- Page Header  -->
<div class="page-header">
    <h1 class="page-title"><?= htmlspecialchars(ucfirst($title)) ?></h1>
    <p class="page-subtitle">Manage subscription plans and track subscriber counts</p>
</div>

<!-- Filters and Search -->
<div class="filters-container">
    <div class="search-box">
        <i class="fas fa-search search-icon"></i>
        <input type="text" class="search-input" placeholder="Search plans by name..." id="planSearch">
    </div>

    <div class="filter-group">
        <select class="filter-select" id="statusFilter">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>

    <div class="action-buttons-group">
        <button class="btn btn-secondary" id="clearFilters">
            <i class="fas fa-times"></i> Clear
        </button>
        <button class="btn btn-primary" id="addPlanBtn">
            <i class="fas fa-plus"></i> Add Plan
        </button>
    </div>
</div>

<!-- Plans Table -->
<div class="dashboard-card">
    <!-- Top Pagination -->
    <div class="card-header">
        <div class="pagination-info">
            Showing <?= ($page - 1) * $limit + 1 ?> to <?= min($page * $limit, $totalPlans) ?> of <?= $totalPlans ?> plans
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
        <div class="table-responsive">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Plan Details</th>
                        <th>Pricing</th>
                        <th>Subscribers</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($plansData)): ?>
                        <tr>
                            <td colspan="5" class="no-data">
                                <i class="fas fa-credit-card"></i>
                                <p>No subscription plans found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($plansData as $plan): ?>
                            <tr data-plan-id="<?= htmlspecialchars($plan['PLAN_ID'] ?? '') ?>"
                                data-status="<?= ($plan['IS_ACTIVE'] ?? 0) ? 'active' : 'inactive' ?>">
                                <td>
                                    <div class="plan-info">
                                        <div class="plan-name">
                                            <h4><?= htmlspecialchars($plan['PLAN_NAME'] ?? '') ?></h4>
                                            <?php if ($plan['IS_HIGHLIGHT'] ?? 0): ?>
                                                <span class="highlight-badge">Featured</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="plan-description">
                                            <?= !empty($plan['DESCRIPTION']) ? htmlspecialchars($plan['DESCRIPTION']) : 'No description provided' ?>
                                        </div>
                                        <div class="plan-limits">
                                            <small class="text-muted">
                                                <?= htmlspecialchars($plan['MAX_ITEMS'] ?? '0') ?> items
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="pricing-info">
                                        <div class="price">৳<?= number_format($plan['MONTHLY_FEE'] ?? 0, 2) ?> /Month</div>
                                        <div class="commission">
                                            <?= htmlspecialchars($plan['COMMISSION_RATE'] ?? '0') ?>% commission
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="subscriber-stats">
                                        <div class="metric">
                                            <span class="metric-value"><?= htmlspecialchars($plan['TOTAL_SUBSCRIBERS'] ?? '0') ?></span>
                                            <span class="metric-label">Total</span>
                                        </div>
                                        <div class="metric">
                                            <span class="metric-value"><?= htmlspecialchars($plan['ACTIVE_SUBSCRIBERS'] ?? '0') ?></span>
                                            <span class="metric-label">Active</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= ($plan['IS_ACTIVE'] ?? 0) ? 'active' : 'inactive' ?>">
                                        <i class="fas <?= ($plan['IS_ACTIVE'] ?? 0) ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                                        <?= ($plan['IS_ACTIVE'] ?? 0) ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action btn-edit"
                                            onclick="openEditPlanModal(
                                                '<?= $plan['PLAN_ID'] ?>',
                                                '<?= htmlspecialchars(addslashes($plan['PLAN_NAME'] ?? '')) ?>',
                                                '<?= !empty($plan['DESCRIPTION']) ? htmlspecialchars(addslashes($plan['DESCRIPTION'])) : '' ?>',
                                                '<?= $plan['MONTHLY_FEE'] ?? 0 ?>',
                                                '<?= $plan['COMMISSION_RATE'] ?? 0 ?>',
                                                '<?= $plan['MAX_ITEMS'] ?? 0 ?>',
                                                '<?= $plan['IS_ACTIVE'] ?? 0 ?>',
                                                '<?= $plan['IS_HIGHLIGHT'] ?? 0 ?>'
                                            )"
                                            title="Edit Plan">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-action btn-delete"
                                            onclick="openDeletePlanModal(
                                                '<?= $plan['PLAN_ID'] ?>',
                                                '<?= htmlspecialchars(addslashes($plan['PLAN_NAME'] ?? '')) ?>'
                                            )"
                                            title="Delete Plan">
                                            <i class="fas fa-trash"></i>
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
                    Showing <?= ($page - 1) * $limit + 1 ?> to <?= min($page * $limit, $totalPlans) ?> of <?= $totalPlans ?> plans
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

<!-- Add Plan Modal -->
<div class="modal-overlay" id="addPlanModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-plus"></i>
                Add New Subscription Plan
            </h2>
            <button class="modal-close" onclick="closeModal('addPlanModal')">&times;</button>
        </div>

        <form method="POST" action="/admin/dashboard/subscriptions" id="addPlanForm">
            <input type="hidden" name="action" value="add_plan">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Plan Name *</label>
                    <input type="text" class="form-control" name="plan_name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="3" placeholder="Enter plan description..."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Monthly Fee (৳) *</label>
                    <input type="number" class="form-control" name="monthly_fee" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Commission Rate (%) *</label>
                    <input type="number" class="form-control" name="commission_rate" step="0.01" min="0" max="100" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Max Items</label>
                    <input type="number" class="form-control" name="max_items" min="1" value="3">
                </div>
                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <span class="checkmark"></span>
                        Active Plan
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="is_highlight" value="1">
                        <span class="checkmark"></span>
                        Highlighted Plan
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addPlanModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Plan</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Plan Modal -->
<div class="modal-overlay" id="editPlanModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-edit"></i>
                Edit Subscription Plan
            </h2>
            <button class="modal-close" onclick="closeModal('editPlanModal')">&times;</button>
        </div>
        <form method="POST" action="/admin/dashboard/subscriptions" id="editPlanForm">
            <input type="hidden" name="action" value="edit_plan">
            <input type="hidden" name="plan_id" id="editPlanId">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Plan Name *</label>
                    <input type="text" class="form-control" name="plan_name" id="editPlanName" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" id="editPlanDescription" rows="3" placeholder="Enter plan description..."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Monthly Fee (৳) *</label>
                    <input type="number" class="form-control" name="monthly_fee" id="editMonthlyFee" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Commission Rate (%) *</label>
                    <input type="number" class="form-control" name="commission_rate" id="editCommissionRate" step="0.01" min="0" max="100" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Max Items</label>
                    <input type="number" class="form-control" name="max_items" id="editMaxItems" min="1">
                </div>
                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="is_active" id="editIsActive" value="1">
                        <span class="checkmark"></span>
                        Active Plan
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="is_highlight" id="editIsHighlight" value="1">
                        <span class="checkmark"></span>
                        Highlighted Plan
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editPlanModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Plan</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Plan Modal -->
<div class="modal-overlay" id="deletePlanModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-trash"></i>
                Delete Subscription Plan
            </h2>
            <button class="modal-close" onclick="closeModal('deletePlanModal')">&times;</button>
        </div>
        <form method="POST" action="/admin/dashboard/subscriptions" id="deletePlanForm">
            <input type="hidden" name="action" value="delete_plan">
            <input type="hidden" name="plan_id" id="deletePlanId">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-body">
                <div class="confirmation-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Confirm Deletion</h3>
                    <p>Are you sure you want to delete the plan: <strong id="deletePlanName"></strong>?</p>
                    <p>This action cannot be undone. Any active subscriptions using this plan will need to be migrated to another plan first.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('deletePlanModal')">Cancel</button>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete Plan
                </button>
            </div>
        </form>
    </div>
</div>