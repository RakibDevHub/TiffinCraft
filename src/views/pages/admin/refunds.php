<?php
$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 50;
$totalRefunds = $data['totalRefunds'] ?? 0;
$totalPages = ceil($totalRefunds / $limit);
$refunds = $data['refunds'] ?? [];
$filters = $data['filters'] ?? [];

function dateFormat($dateString, $format = 'M j, Y g:i A')
{
    if (!$dateString) return '';
    $date = DateTime::createFromFormat('d-M-y h.i.s.u A', $dateString);
    return $date ? $date->format($format) : htmlspecialchars((string)$dateString);
}

function getStatusBadge($status)
{
    $status = strtoupper($status);
    $statusClasses = [
        'PENDING' => 'status-pending',
        'APPROVED' => 'status-warning',
        'PROCESSED' => 'status-success',
        'REJECTED' => 'status-failed',
        'CANCELLED' => 'status-cancelled'
    ];

    $statusIcons = [
        'PENDING' => 'fa-clock',
        'APPROVED' => 'fa-check',
        'PROCESSED' => 'fa-check-double',
        'REJECTED' => 'fa-times-circle',
        'CANCELLED' => 'fa-ban'
    ];

    $class = $statusClasses[$status] ?? 'status-pending';
    $icon = $statusIcons[$status] ?? 'fa-clock';

    return "<span class='status-badge {$class}'><i class='fas {$icon}'></i> {$status}</span>";
}

include BASE_PATH . '/src/views/components/flash-popup.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Refund Requests</h1>
    <p class="page-subtitle">Manage customer refund requests</p>
</div>

<!-- Filters and Search -->
<div class="filters-container">
    <!-- Search Box -->
    <div class="search-filter search-box" style="flex: 2;">
        <form method="GET" action="/admin/dashboard/refunds" class="search-form">
            <div class="search-input-wrapper">
                <div class="search-icon">
                    <i class="fas fa-search"></i>
                </div>
                <input type="text"
                    name="search"
                    placeholder="Search by buyer name, email, order ID..."
                    value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                    class="search-input"
                    onkeypress="if(event.key === 'Enter') this.form.submit()">
            </div>
            <input type="hidden" name="status" value="<?= htmlspecialchars($filters['status'] ?? '') ?>">
            <input type="hidden" name="page" value="1">
        </form>
    </div>

    <!-- Status Filter -->
    <div class="filter-group">
        <div class="status-filter">
            <form method="GET" action="/admin/dashboard/refunds" id="statusForm">
                <select name="status" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="PENDING" <?= ($filters['status'] ?? '') === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                    <option value="APPROVED" <?= ($filters['status'] ?? '') === 'APPROVED' ? 'selected' : '' ?>>Approved</option>
                    <option value="PROCESSED" <?= ($filters['status'] ?? '') === 'PROCESSED' ? 'selected' : '' ?>>Processed</option>
                    <option value="REJECTED" <?= ($filters['status'] ?? '') === 'REJECTED' ? 'selected' : '' ?>>Rejected</option>
                </select>
                <input type="hidden" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                <input type="hidden" name="page" value="1">
            </form>
        </div>
    </div>

    <!-- Clear Filters Button -->
    <div class="clear-filter">
        <a href="/admin/dashboard/refunds" class="clear-btn">
            Clear Filters
        </a>
    </div>
</div>

<!-- Active Filters Display -->
<?php if (!empty($filters['search']) || !empty($filters['status'])): ?>
    <div class="active-filters">
        <?php if (!empty($filters['search'])): ?>
            <span class="active-filter-tag">
                Search: <?= htmlspecialchars($filters['search']) ?>
                <a href="?<?= http_build_query(array_filter([
                                'status' => $filters['status'] ?? '',
                                'limit' => $limit
                            ])) ?>" class="remove-filter">
                    <i class="fas fa-times"></i>
                </a>
            </span>
        <?php endif; ?>

        <?php if (!empty($filters['status'])): ?>
            <span class="active-filter-tag">
                Status: <?= htmlspecialchars($filters['status']) ?>
                <a href="?<?= http_build_query(array_filter([
                                'search' => $filters['search'] ?? '',
                                'limit' => $limit
                            ])) ?>" class="remove-filter">
                    <i class="fas fa-times"></i>
                </a>
            </span>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Refunds Table -->
<div class="dashboard-card">
    <!-- Top Pagination -->
    <div class="card-header">
        <div class="pagination-info">
            Showing <?= ($page - 1) * $limit + 1 ?> to <?= min($page * $limit, $totalRefunds) ?> of <?= $totalRefunds ?> requests
        </div>
        <?php if ($totalPages > 1): ?>
            <div class="pagination-controls">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&limit=<?= $limit ?>&search=<?= htmlspecialchars($filters['search'] ?? '') ?>&status=<?= htmlspecialchars($filters['status'] ?? '') ?>" class="pagination-btn">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>

                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);

                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                    <a href="?page=<?= $i ?>&limit=<?= $limit ?>&search=<?= htmlspecialchars($filters['search'] ?? '') ?>&status=<?= htmlspecialchars($filters['status'] ?? '') ?>"
                        class="pagination-btn <?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&limit=<?= $limit ?>&search=<?= htmlspecialchars($filters['search'] ?? '') ?>&status=<?= htmlspecialchars($filters['status'] ?? '') ?>" class="pagination-btn">
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

    <div class="card-body" style="overflow-x: auto;">
        <div class="table-responsive">
            <table class="refunds-table">
                <thead>
                    <tr>
                        <th>Request Info</th>
                        <th>Buyer & Order</th>
                        <th>Amount Details</th>
                        <th>Payment Details</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($refunds)): ?>
                        <tr>
                            <td colspan="6" class="no-data">
                                <i class="fas fa-undo"></i>
                                <p>No refund requests found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($refunds as $refund): ?>
                            <tr data-refund-id="<?= $refund['REFUND_ID'] ?>">
                                <!-- REQUEST INFO -->
                                <td>
                                    <div class="date-info" style="min-width: 60px;">
                                        <span class="date">
                                            <?= dateFormat($refund['CREATED_AT'], 'M j, Y') ?>
                                        </span>
                                        <span class="time">
                                            <?= dateFormat($refund['CREATED_AT'], 'h:i A') ?>
                                        </span>
                                    </div>
                                    <div class="request-id">RF-<?= str_pad($refund['REFUND_ID'], 6, '0', STR_PAD_LEFT) ?></div>
                                </td>

                                <!-- BUYER & ORDER -->
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($refund['BUYER_NAME'] ?? '') ?>&background=4a6cf7&color=fff"
                                                class="user-avatar"
                                                alt="<?= htmlspecialchars($refund['BUYER_NAME'] ?? '') ?>">
                                        </div>
                                        <div class="user-details">
                                            <h4><?= htmlspecialchars($refund['BUYER_NAME'] ?? 'N/A') ?></h4>
                                            <small><?= htmlspecialchars($refund['EMAIL'] ?? '') ?></small>
                                        </div>
                                    </div>
                                    <div class="order-info">
                                        <strong>Order #<?= $refund['ORDER_ID'] ?></strong>
                                        <small><?= htmlspecialchars($refund['KITCHEN_NAME'] ?? '') ?></small>
                                    </div>
                                </td>

                                <!-- AMOUNT DETAILS -->
                                <td>
                                    <div class="amount">
                                        <span class="amount-value">
                                            ৳<?= number_format($refund['AMOUNT'], 2) ?>
                                        </span>
                                        <div class="refund-details">
                                            <span>Order Total: ৳<?= number_format($refund['ORDER_TOTAL'], 2) ?></span>
                                            <span>Refund: <?= number_format($refund['REFUND_PERCENTAGE'], 1) ?>%</span>
                                        </div>
                                        <?php if (!empty($refund['REASON'])): ?>
                                            <div class="refund-reason">
                                                <em><?= htmlspecialchars($refund['REASON']) ?></em>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- PAYMENT DETAILS -->
                                <td>
                                    <div class="payment-info">
                                        <div class="payment-method">
                                            <strong><?= htmlspecialchars($refund['METHOD']) ?></strong>
                                        </div>
                                        <div class="account-details">
                                            <?= htmlspecialchars($refund['ACCOUNT_DETAILS']) ?>
                                        </div>
                                    </div>
                                </td>

                                <!-- STATUS -->
                                <td>
                                    <?= getStatusBadge($refund['STATUS']) ?>
                                </td>

                                <!-- ACTIONS -->
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($refund['STATUS'] === 'PENDING'): ?>
                                            <button class="btn-action btn-success"
                                                onclick="approveRefund(<?= $refund['REFUND_ID'] ?>, '<?= htmlspecialchars(addslashes($refund['BUYER_NAME'])) ?>', '৳<?= number_format($refund['AMOUNT'], 2) ?>')"
                                                title="Approve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn-action btn-danger"
                                                onclick="rejectRefund(<?= $refund['REFUND_ID'] ?>, '<?= htmlspecialchars(addslashes($refund['BUYER_NAME'])) ?>', '৳<?= number_format($refund['AMOUNT'], 2) ?>')"
                                                title="Reject">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php elseif ($refund['STATUS'] === 'APPROVED'): ?>
                                            <button class="btn-action btn-primary"
                                                onclick="markRefundAsProcessed(<?= $refund['REFUND_ID'] ?>, '<?= htmlspecialchars(addslashes($refund['BUYER_NAME'])) ?>', '৳<?= number_format($refund['AMOUNT'], 2) ?>')"
                                                title="Mark as Processed">
                                                <i class="fas fa-check-double"></i>
                                            </button>
                                        <?php elseif ($refund['STATUS'] === 'CANCELLED'): ?>
                                            <span class="text-muted">Cancelled by buyer</span>
                                        <?php endif; ?>
                                        <button class="btn-action btn-view"
                                            onclick="viewRefundDetails(<?= $refund['REFUND_ID'] ?>)"
                                            title="View Details">
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
                    Showing <?= ($page - 1) * $limit + 1 ?> to <?= min($page * $limit, $totalRefunds) ?> of <?= $totalRefunds ?> requests
                </div>
                <div class="pagination-controls">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&limit=<?= $limit ?>&search=<?= htmlspecialchars($filters['search'] ?? '') ?>&status=<?= htmlspecialchars($filters['status'] ?? '') ?>" class="pagination-btn">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);

                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <a href="?page=<?= $i ?>&limit=<?= $limit ?>&search=<?= htmlspecialchars($filters['search'] ?? '') ?>&status=<?= htmlspecialchars($filters['status'] ?? '') ?>"
                            class="pagination-btn <?= $i == $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&limit=<?= $limit ?>&search=<?= htmlspecialchars($filters['search'] ?? '') ?>&status=<?= htmlspecialchars($filters['status'] ?? '') ?>" class="pagination-btn">
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

<!-- Refund Details Modal -->
<div class="modal-overlay" id="refundDetailsModal">
    <div class="modal modal-large">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-undo"></i>
                Refund Details
            </h2>
            <button class="modal-close" onclick="closeModal('refundDetailsModal')">&times;</button>
        </div>
        <div class="modal-body">
            <?php foreach ($refunds as $refund): ?>
                <div class="refund-details-content" id="refund-details-<?= $refund['REFUND_ID'] ?>" style="display: none;">
                    <div class="refund-details-container">
                        <div class="refund-header">
                            <h3>
                                Refund Request - RF-<?= str_pad($refund['REFUND_ID'], 6, '0', STR_PAD_LEFT) ?>
                            </h3>
                            <?= getStatusBadge($refund['STATUS']) ?>
                        </div>

                        <div class="details-grid">
                            <!-- Refund Information -->
                            <div class="detail-section">
                                <h4><i class="fas fa-info-circle"></i> Refund Information</h4>
                                <div class="detail-row">
                                    <div class="detail-label">Refund ID</div>
                                    <div class="detail-value">RF-<?= str_pad($refund['REFUND_ID'], 6, '0', STR_PAD_LEFT) ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Order ID</div>
                                    <div class="detail-value">#<?= $refund['ORDER_ID'] ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Refund Amount</div>
                                    <div class="detail-value">৳<?= number_format($refund['AMOUNT'], 2) ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Order Total</div>
                                    <div class="detail-value">৳<?= number_format($refund['ORDER_TOTAL'], 2) ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Refund Percentage</div>
                                    <div class="detail-value"><?= number_format($refund['REFUND_PERCENTAGE'], 1) ?>%</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Request Date</div>
                                    <div class="detail-value"><?= dateFormat($refund['CREATED_AT']) ?></div>
                                </div>
                                <?php if (!empty($refund['REASON'])): ?>
                                    <div class="detail-row">
                                        <div class="detail-label">Reason</div>
                                        <div class="detail-value"><?= htmlspecialchars($refund['REASON']) ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Buyer Information -->
                            <div class="detail-section">
                                <h4><i class="fas fa-user"></i> Buyer Information</h4>
                                <div class="detail-row">
                                    <div class="detail-label">Buyer Name</div>
                                    <div class="detail-value"><?= htmlspecialchars($refund['BUYER_NAME'] ?? 'N/A') ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Email</div>
                                    <div class="detail-value"><?= htmlspecialchars($refund['EMAIL'] ?? 'N/A') ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Phone</div>
                                    <div class="detail-value"><?= htmlspecialchars($refund['PHONE'] ?? 'N/A') ?></div>
                                </div>
                            </div>

                            <!-- Order Information -->
                            <div class="detail-section">
                                <h4><i class="fas fa-shopping-cart"></i> Order Information</h4>
                                <div class="detail-row">
                                    <div class="detail-label">Kitchen</div>
                                    <div class="detail-value"><?= htmlspecialchars($refund['KITCHEN_NAME'] ?? 'N/A') ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Order Status</div>
                                    <div class="detail-value"><?= htmlspecialchars($refund['ORDER_STATUS'] ?? 'N/A') ?></div>
                                </div>
                                <?php if (!empty($refund['CANCEL_BY'])): ?>
                                    <div class="detail-row">
                                        <div class="detail-label">Cancelled By</div>
                                        <div class="detail-value"><?= htmlspecialchars($refund['CANCEL_BY'] ?? 'N/A') ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Payment Information -->
                            <div class="detail-section">
                                <h4><i class="fas fa-credit-card"></i> Payment Information</h4>
                                <div class="detail-row">
                                    <div class="detail-label">Payment Method</div>
                                    <div class="detail-value"><?= htmlspecialchars($refund['METHOD']) ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Account Details</div>
                                    <div class="detail-value"><?= htmlspecialchars($refund['ACCOUNT_DETAILS']) ?></div>
                                </div>
                            </div>

                            <!-- Status Information -->
                            <div class="detail-section">
                                <h4><i class="fas fa-history"></i> Status Information</h4>
                                <div class="detail-row">
                                    <div class="detail-label">Current Status</div>
                                    <div class="detail-value"><?= getStatusBadge($refund['STATUS']) ?></div>
                                </div>
                                <?php if (!empty($refund['UPDATED_AT'])): ?>
                                    <div class="detail-row">
                                        <div class="detail-label">Processed Date</div>
                                        <div class="detail-value"><?= dateFormat($refund['UPDATED_AT']) ?></div>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($refund['ADMIN_NOTES'])): ?>
                                    <div class="detail-row">
                                        <div class="detail-label">Admin Notes</div>
                                        <div class="detail-value"><?= htmlspecialchars($refund['ADMIN_NOTES']) ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('refundDetailsModal')">Close</button>
        </div>
    </div>
</div>

<!-- Approve Refund Modal -->
<div class="modal-overlay" id="approveRefundModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-check-circle"></i>
                Approve Refund
            </h2>
            <button class="modal-close" onclick="closeModal('approveRefundModal')">&times;</button>
        </div>
        <form method="POST" action="/admin/dashboard/refunds" id="approveRefundForm">
            <input type="hidden" name="action" value="approve_refund">
            <input type="hidden" name="refund_id" id="approveRefundId">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-body">
                <div class="confirmation-message">
                    <i class="fas fa-question-circle"></i>
                    <h3>Confirm Refund Approval</h3>
                    <p>You are about to approve the refund request for:</p>
                    <div class="transaction-summary">
                        <p><strong>Buyer:</strong> <span id="approveRefundUser"></span></p>
                        <p><strong>Amount:</strong> <span id="approveRefundAmount"></span></p>
                        <p><strong>Order:</strong> #<span id="approveRefundOrder"></span></p>
                    </div>
                    <p>This action will mark the refund as approved and ready for processing.</p>
                </div>
                <div class="form-group">
                    <label class="form-label">Admin Notes (Optional)</label>
                    <textarea class="form-control" name="admin_notes" rows="3"
                        placeholder="Add any notes about this approval..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('approveRefundModal')">Cancel</button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check"></i> Approve Refund
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Refund Modal -->
<div class="modal-overlay" id="rejectRefundModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-times-circle"></i>
                Reject Refund
            </h2>
            <button class="modal-close" onclick="closeModal('rejectRefundModal')">&times;</button>
        </div>
        <form method="POST" action="/admin/dashboard/refunds" id="rejectRefundForm">
            <input type="hidden" name="action" value="reject_refund">
            <input type="hidden" name="refund_id" id="rejectRefundId">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-body">
                <div class="confirmation-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Confirm Refund Rejection</h3>
                    <p>You are about to reject the refund request for:</p>
                    <div class="transaction-summary">
                        <p><strong>Buyer:</strong> <span id="rejectRefundUser"></span></p>
                        <p><strong>Amount:</strong> <span id="rejectRefundAmount"></span></p>
                        <p><strong>Order:</strong> #<span id="rejectRefundOrder"></span></p>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Reason for Rejection *</label>
                    <textarea class="form-control" name="admin_notes" rows="4"
                        placeholder="Please provide the reason for rejecting this refund request..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('rejectRefundModal')">Cancel</button>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-times"></i> Reject Refund
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Mark Refund as Processed Modal -->
<div class="modal-overlay" id="processRefundModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-check-double"></i>
                Mark as Processed
            </h2>
            <button class="modal-close" onclick="closeModal('processRefundModal')">&times;</button>
        </div>
        <form method="POST" action="/admin/dashboard/refunds" id="processRefundForm">
            <input type="hidden" name="action" value="process_refund">
            <input type="hidden" name="refund_id" id="processRefundId">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-body">
                <div class="confirmation-message">
                    <i class="fas fa-question-circle"></i>
                    <h3>Confirm Refund Processing</h3>
                    <p>You are about to mark this refund as processed:</p>
                    <div class="transaction-summary">
                        <p><strong>Buyer:</strong> <span id="processRefundUser"></span></p>
                        <p><strong>Amount:</strong> <span id="processRefundAmount"></span></p>
                        <p><strong>Order:</strong> #<span id="processRefundOrder"></span></p>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Transaction ID *</label>
                    <input type="text" class="form-control" name="transaction_id"
                        placeholder="Enter the transaction ID (bKash/Nagad/Bank Reference)" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Admin Notes (Optional)</label>
                    <textarea class="form-control" name="admin_notes" rows="3"
                        placeholder="Add any notes about this processing..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('processRefundModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check-double"></i> Mark as Processed
                </button>
            </div>
        </form>
    </div>
</div>


<script>
    function changeLimit(newLimit) {
        const params = new URLSearchParams(window.location.search);
        params.set('limit', newLimit);
        params.set('page', 1);
        window.location.search = params.toString();
    }

    function viewRefundDetails(refundId) {
        // Hide all refund details first
        document.querySelectorAll('.refund-details-content').forEach(el => {
            el.style.display = 'none';
        });

        // Show the specific refund details
        const detailsEl = document.getElementById('refund-details-' + refundId);
        if (detailsEl) {
            detailsEl.style.display = 'block';

            // Show modal
            const modal = document.getElementById('refundDetailsModal');
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function approveRefund(refundId, userName, amount) {
        document.getElementById('approveRefundId').value = refundId;
        document.getElementById('approveRefundUser').textContent = userName;
        document.getElementById('approveRefundAmount').textContent = amount;

        // Get order ID from the table row
        const row = document.querySelector(`[data-refund-id="${refundId}"]`);
        const orderId = row.querySelector('.order-info strong')?.textContent?.replace('Order #', '') || '';
        document.getElementById('approveRefundOrder').textContent = orderId;

        document.getElementById('approveRefundModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function markRefundAsProcessed(refundId, userName, amount) {
        document.getElementById('processRefundId').value = refundId;
        document.getElementById('processRefundUser').textContent = userName;
        document.getElementById('processRefundAmount').textContent = amount;

        // Get order ID from the table row
        const row = document.querySelector(`[data-refund-id="${refundId}"]`);
        const orderId = row.querySelector('.order-info strong')?.textContent?.replace('Order #', '') || '';
        document.getElementById('processRefundOrder').textContent = orderId;

        document.getElementById('processRefundModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function rejectRefund(refundId, userName, amount) {
        document.getElementById('rejectRefundId').value = refundId;
        document.getElementById('rejectRefundUser').textContent = userName;
        document.getElementById('rejectRefundAmount').textContent = amount;

        // Get order ID from the table row
        const row = document.querySelector(`[data-refund-id="${refundId}"]`);
        const orderId = row.querySelector('.order-info strong')?.textContent?.replace('Order #', '') || '';
        document.getElementById('rejectRefundOrder').textContent = orderId;

        document.getElementById('rejectRefundModal').classList.add('active');
        document.body.style.overflow = 'hidden';
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
            closeModal(modal.id);
        });
    }

    // Initialize event listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Modal overlay close
        document.querySelectorAll('.modal-overlay').forEach((overlay) => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    closeModal(overlay.id);
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
</script>