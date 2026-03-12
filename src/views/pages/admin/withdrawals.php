<?php
$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 50;
$totalWithdrawals = $data['totalWithdrawals'] ?? 0;
$totalPages = ceil($totalWithdrawals / $limit);
$withdrawals = $data['withdrawals'] ?? [];
$statusFilter = $data['statusFilter'] ?? 'PENDING';
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
        'PROCESSED' => 'status-success',
        'REJECTED' => 'status-failed',
        'APPROVED' => 'status-success'
    ];

    $statusIcons = [
        'PENDING' => 'fa-clock',
        'PROCESSED' => 'fa-check-circle',
        'REJECTED' => 'fa-times-circle',
        'APPROVED' => 'fa-check'
    ];

    $class = $statusClasses[$status] ?? 'status-pending';
    $icon = $statusIcons[$status] ?? 'fa-clock';

    return "<span class='status-badge {$class}'><i class='fas {$icon}'></i> {$status}</span>";
}

include BASE_PATH . '/src/views/components/flash-popup.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Withdrawal Requests</h1>
    <p class="page-subtitle">Manage seller withdrawal requests</p>
</div>

<!-- Filters and Search -->
<div class="filters-container">
    <!-- Search Box -->
    <div class="search-filter search-box" style="flex: 2;">
        <form method="GET" action="/admin/dashboard/withdrawals" class="search-form">
            <div class="search-input-wrapper">
                <div class="search-icon">
                    <i class="fas fa-search"></i>
                </div>
                <input type="text"
                    name="search"
                    placeholder="Search by seller name, email, request ID..."
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
            <form method="GET" action="/admin/dashboard/withdrawals" id="statusForm">
                <select name="status" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="PENDING" <?= ($filters['status'] ?? '') === 'PENDING' ? 'selected' : '' ?>>Pending</option>
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
        <a href="/admin/dashboard/withdrawals" class="clear-btn">
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

<!-- Withdrawals Table -->
<div class="dashboard-card">
    <!-- Top Pagination -->
    <div class="card-header">
        <div class="pagination-info">
            Showing <?= ($page - 1) * $limit + 1 ?> to <?= min($page * $limit, $totalWithdrawals) ?> of <?= $totalWithdrawals ?> requests
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
            <table class="withdrawals-table">
                <thead>
                    <tr>
                        <th>Request Info</th>
                        <th>Seller</th>
                        <th>Amount</th>
                        <th>Payment Details</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($withdrawals)): ?>
                        <tr>
                            <td colspan="6" class="no-data">
                                <i class="fas fa-money-bill-wave"></i>
                                <p>No withdrawal requests found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($withdrawals as $withdrawal): ?>
                            <tr data-withdraw-id="<?= $withdrawal['WITHDRAW_ID'] ?>">
                                <!-- REQUEST INFO -->
                                <td>
                                    <div class="date-info" style="min-width: 60px;">
                                        <span class="date">
                                            <?= dateFormat($withdrawal['CREATED_AT'], 'M j, Y') ?>
                                        </span>
                                        <span class="time">
                                            <?= dateFormat($withdrawal['CREATED_AT'], 'h:i A') ?>
                                        </span>
                                    </div>
                                    <div class="request-id">WD-<?= str_pad($withdrawal['WITHDRAW_ID'], 6, '0', STR_PAD_LEFT) ?></div>
                                </td>

                                <!-- SELLER -->
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($withdrawal['SELLER_NAME'] ?? '') ?>&background=4a6cf7&color=fff"
                                                class="user-avatar"
                                                alt="<?= htmlspecialchars($withdrawal['SELLER_NAME'] ?? '') ?>">
                                        </div>
                                        <div class="user-details">
                                            <h4><?= htmlspecialchars($withdrawal['SELLER_NAME'] ?? 'N/A') ?></h4>
                                            <small><?= htmlspecialchars($withdrawal['EMAIL'] ?? '') ?></small>
                                        </div>
                                    </div>
                                </td>

                                <!-- AMOUNT -->
                                <td>
                                    <div class="amount">
                                        <span class="amount-value">
                                            ৳<?= number_format($withdrawal['AMOUNT'], 2) ?>
                                        </span>
                                        <div class="available-balance">
                                            Available: ৳<?= number_format($withdrawal['AVAILABLE_BALANCE'] ?? 0, 2) ?>
                                        </div>
                                    </div>
                                </td>

                                <!-- PAYMENT DETAILS -->
                                <td>
                                    <div class="payment-info">
                                        <div class="payment-method">
                                            <strong><?= htmlspecialchars($withdrawal['METHOD']) ?></strong>
                                        </div>
                                        <div class="account-details">
                                            <?= htmlspecialchars($withdrawal['ACCOUNT_DETAILS']) ?>
                                        </div>
                                    </div>
                                </td>

                                <!-- STATUS -->
                                <td>
                                    <?= getStatusBadge($withdrawal['STATUS']) ?>
                                </td>

                                <!-- ACTIONS -->
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($withdrawal['STATUS'] === 'PENDING'): ?>
                                            <button class="btn-action btn-success"
                                                onclick="approveWithdrawal(<?= $withdrawal['WITHDRAW_ID'] ?>, '<?= htmlspecialchars(addslashes($withdrawal['SELLER_NAME'])) ?>', '৳<?= number_format($withdrawal['AMOUNT'], 2) ?>')"
                                                title="Approve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn-action btn-danger"
                                                onclick="rejectWithdrawal(<?= $withdrawal['WITHDRAW_ID'] ?>, '<?= htmlspecialchars(addslashes($withdrawal['SELLER_NAME'])) ?>', '৳<?= number_format($withdrawal['AMOUNT'], 2) ?>')"
                                                title="Reject">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php elseif ($withdrawal['STATUS'] === 'APPROVED'): ?>
                                            <button class="btn-action btn-primary"
                                                onclick="markAsProcessed(<?= $withdrawal['WITHDRAW_ID'] ?>, '<?= htmlspecialchars(addslashes($withdrawal['SELLER_NAME'])) ?>', '৳<?= number_format($withdrawal['AMOUNT'], 2) ?>')"
                                                title="Mark as Processed">
                                                <i class="fas fa-check-double"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn-action btn-view"
                                            onclick="viewWithdrawalDetails(<?= $withdrawal['WITHDRAW_ID'] ?>)"
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
                    Showing <?= ($page - 1) * $limit + 1 ?> to <?= min($page * $limit, $totalWithdrawals) ?> of <?= $totalWithdrawals ?> requests
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

<!-- Withdrawal Details Modal -->
<div class="modal-overlay" id="withdrawalDetailsModal">
    <div class="modal modal-large">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-money-bill-wave"></i>
                Withdrawal Details
            </h2>
            <button class="modal-close" onclick="closeModal('withdrawalDetailsModal')">&times;</button>
        </div>
        <div class="modal-body">
            <?php foreach ($withdrawals as $withdrawal): ?>
                <div class="withdrawal-details-content" id="withdrawal-details-<?= $withdrawal['WITHDRAW_ID'] ?>" style="display: none;">
                    <div class="withdrawal-details-container">
                        <div class="withdrawal-header">
                            <h3>
                                Withdrawal Request - WD-<?= str_pad($withdrawal['WITHDRAW_ID'], 6, '0', STR_PAD_LEFT) ?>
                            </h3>
                            <?= getStatusBadge($withdrawal['STATUS']) ?>
                        </div>

                        <div class="details-grid">
                            <!-- Withdrawal Information -->
                            <div class="detail-section">
                                <h4><i class="fas fa-info-circle"></i> Withdrawal Information</h4>
                                <div class="detail-row">
                                    <div class="detail-label">Request ID</div>
                                    <div class="detail-value">WD-<?= str_pad($withdrawal['WITHDRAW_ID'], 6, '0', STR_PAD_LEFT) ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Amount</div>
                                    <div class="detail-value">৳<?= number_format($withdrawal['AMOUNT'], 2) ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Available Balance</div>
                                    <div class="detail-value">৳<?= number_format($withdrawal['AVAILABLE_BALANCE'] ?? 0, 2) ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Request Date</div>
                                    <div class="detail-value"><?= dateFormat($withdrawal['CREATED_AT']) ?></div>
                                </div>
                            </div>

                            <!-- Seller Information -->
                            <div class="detail-section">
                                <h4><i class="fas fa-user"></i> Seller Information</h4>
                                <div class="detail-row">
                                    <div class="detail-label">Seller Name</div>
                                    <div class="detail-value"><?= htmlspecialchars($withdrawal['SELLER_NAME'] ?? 'N/A') ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Email</div>
                                    <div class="detail-value"><?= htmlspecialchars($withdrawal['EMAIL'] ?? 'N/A') ?></div>
                                </div>
                            </div>

                            <!-- Payment Information -->
                            <div class="detail-section">
                                <h4><i class="fas fa-credit-card"></i> Payment Information</h4>
                                <div class="detail-row">
                                    <div class="detail-label">Payment Method</div>
                                    <div class="detail-value"><?= htmlspecialchars($withdrawal['METHOD']) ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Account Details</div>
                                    <div class="detail-value"><?= htmlspecialchars($withdrawal['ACCOUNT_DETAILS']) ?></div>
                                </div>
                            </div>

                            <!-- Status Information -->
                            <div class="detail-section">
                                <h4><i class="fas fa-history"></i> Status Information</h4>
                                <div class="detail-row">
                                    <div class="detail-label">Current Status</div>
                                    <div class="detail-value"><?= getStatusBadge($withdrawal['STATUS']) ?></div>
                                </div>
                                <?php if (!empty($withdrawal['PROCESSED_DATE'])): ?>
                                    <div class="detail-row">
                                        <div class="detail-label">Processed Date</div>
                                        <div class="detail-value"><?= dateFormat($withdrawal['PROCESSED_DATE']) ?></div>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($withdrawal['ADMIN_NOTES'])): ?>
                                    <div class="detail-row">
                                        <div class="detail-label">Admin Notes</div>
                                        <div class="detail-value"><?= htmlspecialchars($withdrawal['ADMIN_NOTES']) ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('withdrawalDetailsModal')">Close</button>
        </div>
    </div>
</div>

<!-- Approve Withdrawal Modal -->
<div class="modal-overlay" id="approveWithdrawalModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-check-circle"></i>
                Approve Withdrawal
            </h2>
            <button class="modal-close" onclick="closeModal('approveWithdrawalModal')">&times;</button>
        </div>
        <form method="POST" action="/admin/dashboard/withdrawals" id="approveWithdrawalForm">
            <input type="hidden" name="action" value="approve_withdraw">
            <input type="hidden" name="withdraw_id" id="approveWithdrawalId">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-body">
                <div class="confirmation-message">
                    <i class="fas fa-question-circle"></i>
                    <h3>Confirm Withdrawal Approval</h3>
                    <p>You are about to approve the withdrawal request for:</p>
                    <div class="transaction-summary">
                        <p><strong>Seller:</strong> <span id="approveWithdrawalUser"></span></p>
                        <p><strong>Amount:</strong> <span id="approveWithdrawalAmount"></span></p>
                    </div>
                    <p>This action will mark the withdrawal as approved and ready for processing.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('approveWithdrawalModal')">Cancel</button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check"></i> Approve Withdrawal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Withdrawal Modal -->
<div class="modal-overlay" id="rejectWithdrawalModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-times-circle"></i>
                Reject Withdrawal
            </h2>
            <button class="modal-close" onclick="closeModal('rejectWithdrawalModal')">&times;</button>
        </div>
        <form method="POST" action="/admin/dashboard/withdrawals" id="rejectWithdrawalForm">
            <input type="hidden" name="action" value="reject_withdraw">
            <input type="hidden" name="withdraw_id" id="rejectWithdrawalId">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-body">
                <div class="confirmation-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Confirm Withdrawal Rejection</h3>
                    <p>You are about to reject the withdrawal request for:</p>
                    <div class="transaction-summary">
                        <p><strong>Seller:</strong> <span id="rejectWithdrawalUser"></span></p>
                        <p><strong>Amount:</strong> <span id="rejectWithdrawalAmount"></span></p>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Reason for Rejection *</label>
                    <textarea class="form-control" name="admin_notes" rows="4"
                        placeholder="Please provide the reason for rejecting this withdrawal request..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('rejectWithdrawalModal')">Cancel</button>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-times"></i> Reject Withdrawal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Mark as Processed Modal -->
<div class="modal-overlay" id="processWithdrawalModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-check-double"></i>
                Mark as Processed
            </h2>
            <button class="modal-close" onclick="closeModal('processWithdrawalModal')">&times;</button>
        </div>
        <form method="POST" action="/admin/dashboard/withdrawals" id="processWithdrawalForm">
            <input type="hidden" name="action" value="process_withdraw">
            <input type="hidden" name="withdraw_id" id="processWithdrawalId">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-body">
                <div class="confirmation-message">
                    <i class="fas fa-question-circle"></i>
                    <h3>Confirm Withdrawal Processing</h3>
                    <p>You are about to mark this withdrawal as processed:</p>
                    <div class="transaction-summary">
                        <p><strong>Seller:</strong> <span id="processWithdrawalUser"></span></p>
                        <p><strong>Amount:</strong> <span id="processWithdrawalAmount"></span></p>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Bkash Transaction ID *</label>
                    <input type="text" class="form-control" name="bkash_trxid"
                        placeholder="Enter the bKash TrxID" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Admin Notes (Optional)</label>
                    <textarea class="form-control" name="admin_notes" rows="3"
                        placeholder="Add any notes about this processing..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('processWithdrawalModal')">Cancel</button>
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

    function viewWithdrawalDetails(withdrawId) {
        // Hide all withdrawal details first
        document.querySelectorAll('.withdrawal-details-content').forEach(el => {
            el.style.display = 'none';
        });

        // Show the specific withdrawal details
        const detailsEl = document.getElementById('withdrawal-details-' + withdrawId);
        if (detailsEl) {
            detailsEl.style.display = 'block';

            // Show modal
            const modal = document.getElementById('withdrawalDetailsModal');
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function approveWithdrawal(withdrawId, userName, amount) {
        document.getElementById('approveWithdrawalId').value = withdrawId;
        document.getElementById('approveWithdrawalUser').textContent = userName;
        document.getElementById('approveWithdrawalAmount').textContent = amount;
        document.getElementById('approveWithdrawalModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function markAsProcessed(withdrawId, userName, amount) {
        document.getElementById('processWithdrawalId').value = withdrawId;
        document.getElementById('processWithdrawalUser').textContent = userName;
        document.getElementById('processWithdrawalAmount').textContent = amount;
        document.getElementById('processWithdrawalModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function rejectWithdrawal(withdrawId, userName, amount) {
        document.getElementById('rejectWithdrawalId').value = withdrawId;
        document.getElementById('rejectWithdrawalUser').textContent = userName;
        document.getElementById('rejectWithdrawalAmount').textContent = amount;
        document.getElementById('rejectWithdrawalModal').classList.add('active');
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
            modal.classList.remove('active');
        });
        document.body.style.overflow = 'auto';
    }

    // Initialize event listeners
    document.addEventListener('DOMContentLoaded', function() {

        // Modal overlay close
        document.querySelectorAll('.modal-overlay').forEach((overlay) => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    overlay.classList.remove('active');
                    document.body.style.overflow = 'auto';
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