<?php
$refunds = $data['refunds'] ?? [];
$stats = $data['stats'] ?? [];
$statusFilter = $data['statusFilter'] ?? 'all';
$searchTerm = $data['searchTerm'] ?? '';
$currentPage = $data['currentPage'] ?? 1;
$totalPages = $data['totalPages'] ?? 1;
$totalItems = $data['totalItems'] ?? 0;

function getRefundStatusBadge($status)
{
    $statusClasses = [
        'PENDING' => 'status-pending',
        'APPROVED' => 'status-approved',
        'REJECTED' => 'status-rejected',
        'PROCESSED' => 'status-processed'
    ];

    $statusIcons = [
        'PENDING' => 'fa-hourglass-half',
        'APPROVED' => 'fa-check-circle',
        'REJECTED' => 'fa-times-circle',
        'PROCESSED' => 'fa-check-double'
    ];

    $class = $statusClasses[$status] ?? 'status-pending';
    $icon = $statusIcons[$status] ?? 'fa-clock';

    return "<span class='status-badge {$class}'><i class='fas {$icon}'></i> {$status}</span>";
}

function formatDate($dateString, $format = 'M j, Y g:i A')
{
    if (!$dateString) return '';

    $cleanDate = preg_replace('/\.\d{6}/', '', $dateString);

    $cleanDate = str_replace('.', ':', $cleanDate);

    try {
        $date = new DateTime($cleanDate);
        return $date->format($format);
    } catch (Exception $e) {
        return htmlspecialchars($dateString);
    }
}

include BASE_PATH . '/src/views/components/flash-popup.php';
?>

<main class="refunds-page">
    <section class="refunds-section">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">My Refund Requests</h1>
                <p class="page-subtitle">Track your refund request status and history</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-value">৳<?= number_format($stats['total']['amount'], 2) ?></h3>
                        <p class="stat-label">Total Refund Amount</p>
                    </div>
                </div>

                <div class="stat-card pending">
                    <div class="stat-icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-value"><?= $stats['PENDING']['count'] ?></h3>
                        <p class="stat-label">Pending Requests</p>
                    </div>
                </div>

                <div class="stat-card approved">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-value"><?= $stats['APPROVED']['count'] ?></h3>
                        <p class="stat-label">Approved</p>
                    </div>
                </div>

                <div class="stat-card processed">
                    <div class="stat-icon">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-value"><?= $stats['PROCESSED']['count'] ?></h3>
                        <p class="stat-label">Processed</p>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filters-container">
                <div class="filter-row">
                    <!-- Search Box -->
                    <div class="search-filter" style="flex: 2;">
                        <label class="filter-label">Search Refunds</label>
                        <form method="GET" action="/refunds" class="search-form">
                            <div class="search-input-wrapper">
                                <div class="search-icon">
                                    <i class="fas fa-search"></i>
                                </div>
                                <input type="text"
                                    name="search"
                                    placeholder="Search by order ID, kitchen, reason..."
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
                        <form method="GET" action="/refunds" id="statusForm">
                            <select name="status" class="filter-select" onchange="this.form.submit()">
                                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Status</option>
                                <option value="PENDING" <?= $statusFilter === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                                <option value="APPROVED" <?= $statusFilter === 'APPROVED' ? 'selected' : '' ?>>Approved</option>
                                <option value="REJECTED" <?= $statusFilter === 'REJECTED' ? 'selected' : '' ?>>Rejected</option>
                                <option value="PROCESSED" <?= $statusFilter === 'PROCESSED' ? 'selected' : '' ?>>Processed</option>
                            </select>
                            <input type="hidden" name="search" value="<?= htmlspecialchars($searchTerm) ?>">
                            <input type="hidden" name="page" value="1">
                        </form>
                    </div>

                    <!-- Results Count -->
                    <div class="results-count">
                        <span id="resultsCount"><?= $totalItems ?> refund requests</span>
                    </div>

                    <!-- Clear Filters Button -->
                    <div class="clear-filter">
                        <a href="/refunds" class="clear-btn">
                            Clear Filters
                        </a>
                    </div>
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

            <div class="refunds-container">
                <?php if (empty($refunds)): ?>
                    <div class="empty-state">
                        <div class="empty-state-content">
                            <div class="empty-state-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <h2>No Refund Requests Found</h2>
                            <p>You haven't submitted any refund requests yet</p>
                            <a href="/orders" class="btn btn-primary btn-lg">
                                <i class="fas fa-receipt"></i> View My Orders
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Refunds Grid -->
                    <div class="refunds-grid">
                        <?php foreach ($refunds as $refund): ?>
                            <div class="refund-card">
                                <!-- Refund Header -->
                                <div class="refund-header">
                                    <div class="refund-basic-info">
                                        <div class="kitchen-avatar-mini">
                                            <?php if (!empty($refund['COVER_IMAGE'])): ?>
                                                <img
                                                    src="/uploads/kitchen/<?= htmlspecialchars($refund['COVER_IMAGE']) ?>"
                                                    alt="<?= htmlspecialchars($refund['KITCHEN_NAME']) ?>">
                                            <?php else: ?>
                                                <div class="default-avatar-mini">
                                                    <i class="fas fa-utensils"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="refund-meta">
                                            <h4 class="kitchen-name"><?= htmlspecialchars($refund['KITCHEN_NAME']) ?></h4>
                                            <div class="refund-id-date">
                                                <span class="refund-id">Refund #<?= $refund['REFUND_ID'] ?></span>
                                                <span class="order-id">• Order #<?= $refund['ORDER_ID'] ?></span>
                                                <span class="refund-date">• <?= formatDate($refund['CREATED_AT']) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="refund-status-compact">
                                        <?= getRefundStatusBadge($refund['STATUS']) ?>
                                    </div>
                                </div>

                                <!-- Refund Details -->
                                <div class="refund-details">
                                    <div class="detail-row">
                                        <span class="detail-label">Reason:</span>
                                        <span class="detail-value"><?= htmlspecialchars($refund['REASON']) ?></span>
                                    </div>

                                    <div class="detail-row">
                                        <span class="detail-label">Payment Method:</span>
                                        <span class="detail-value"><?= htmlspecialchars($refund['METHOD']) ?></span>
                                    </div>

                                    <div class="detail-row">
                                        <span class="detail-label">Account Details:</span>
                                        <span class="detail-value"><?= htmlspecialchars($refund['ACCOUNT_DETAILS']) ?></span>
                                    </div>
                                </div>

                                <!-- Refund Amount -->
                                <div class="refund-amount-section">
                                    <div class="amount-display" style="justify-content: space-between;">
                                        <span class="amount-label">Refund Amount:</span>
                                        <div style="display: flex; flex-direction: column;">
                                            <span class="amount-value">৳<?= number_format($refund['AMOUNT'], 2) ?></span>
                                            <?php if (isset($refund['REFUND_PERCENTAGE'])): ?>
                                                <span class="amount-percentage">(<?= $refund['REFUND_PERCENTAGE'] ?>% of order total)</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <?php if ($refund['STATUS'] === 'REJECTED' && !empty($refund['ADMIN_NOTES'])): ?>
                                        <div class="rejection-reason">
                                            <span class="rejection-label">Rejection Reason:</span>
                                            <span class="rejection-value"><?= htmlspecialchars($refund['ADMIN_NOTES']) ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($refund['STATUS'] === 'PROCESSED' && !empty($refund['UPDATED_AT'])): ?>
                                        <div class="processed-date">
                                            <span class="processed-label">Processed on:</span>
                                            <span class="processed-value"><?= formatDate($refund['UPDATED_AT']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Refund Actions -->
                                <div class="refund-actions">
                                    <button class="btn btn-outline btn-primary btn-sm"
                                        onclick="openModal('refund-modal-<?= $refund['REFUND_ID'] ?>')">
                                        <i class="fas fa-eye"></i> View Details
                                    </button>

                                    <?php if ($refund['STATUS'] === 'PENDING'): ?>
                                        <button class="btn btn-outline btn-warning btn-sm"
                                            onclick="openModal('cancel-refund-modal-<?= $refund['REFUND_ID'] ?>')">
                                            <i class="fas fa-times"></i> Cancel Request
                                        </button>
                                    <?php endif; ?>
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
</main>

<!-- Refund Details Modals -->
<?php foreach ($refunds as $refund): ?>
    <div class="modal-overlay" id="refund-modal-<?= $refund['REFUND_ID'] ?>">
        <div class="modal modal-large">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-money-bill-wave"></i>
                    Refund Request Details
                </h3>
                <button type="button" class="modal-close" onclick="closeModal('refund-modal-<?= $refund['REFUND_ID'] ?>')">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body">
                <div class="refund-details-modal">
                    <div class="refund-modal-header">
                        <div class="kitchen-info">
                            <div class="kitchen-avatar">
                                <?php if (!empty($refund['COVER_IMAGE'])): ?>
                                    <img src="/uploads/kitchen/<?= htmlspecialchars($refund['COVER_IMAGE']) ?>" alt="<?= htmlspecialchars($refund['KITCHEN_NAME']) ?>">
                                <?php else: ?>
                                    <div class="default-avatar-large">
                                        <i class="fas fa-utensils"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="kitchen-meta">
                                <h4 class="kitchen-name"><?= htmlspecialchars($refund['KITCHEN_NAME']) ?></h4>
                                <div class="refund-meta-info">
                                    <span class="refund-id">Refund #<?= $refund['REFUND_ID'] ?></span>
                                    <span class="order-id">• Order #<?= $refund['ORDER_ID'] ?></span>
                                    <span class="refund-date">• <?= formatDate($refund['CREATED_AT']) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="refund-status">
                            <?= getRefundStatusBadge($refund['STATUS']) ?>
                        </div>
                    </div>

                    <div class="refund-modal-body">
                        <div class="detail-section">
                            <h5 class="section-title">Refund Information</h5>
                            <div class="detail-grid">
                                <div class="detail-row">
                                    <span class="detail-label">Refund Reason:</span>
                                    <span class="detail-value"><?= htmlspecialchars($refund['REASON']) ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Payment Method:</span>
                                    <span class="detail-value"><?= htmlspecialchars($refund['METHOD']) ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Account Details:</span>
                                    <span class="detail-value"><?= htmlspecialchars($refund['ACCOUNT_DETAILS']) ?></span>
                                </div>

                                <?php if ($refund['STATUS'] === 'REJECTED' && !empty($refund['ADMIN_NOTES'])): ?>
                                    <div class="detail-row">
                                        <span class="detail-label">Admin Notes:</span>
                                        <span class="detail-value text-danger"><?= htmlspecialchars($refund['ADMIN_NOTES']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if ($refund['STATUS'] === 'PROCESSED' && !empty($refund['UPDATED_AT'])): ?>
                                    <div class="detail-row">
                                        <span class="detail-label">Processed On:</span>
                                        <span class="detail-value text-success"><?= formatDate($refund['UPDATED_AT']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="amount-section">
                            <div class="amount-display">
                                <div class="amount-info">
                                    <span class="amount-label">Refund Amount:</span>
                                    <div class="amount-value-wrapper">
                                        <span class="amount-value">৳<?= number_format($refund['AMOUNT'], 2) ?></span>
                                        <?php if (isset($refund['REFUND_PERCENTAGE'])): ?>
                                            <span class="amount-percentage">(<?= $refund['REFUND_PERCENTAGE'] ?>% of order total)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Transaction Information Section -->
                        <?php if (!empty($refund['TRANSACTION_ID'])): ?>
                            <div class="transaction-section">
                                <h5 class="section-title">
                                    <i class="fas fa-credit-card"></i>
                                    Transaction Details
                                </h5>
                                <div class="transaction-card">
                                    <div class="transaction-header">
                                        <span class="transaction-id">Transaction #<?= htmlspecialchars($refund['TRANSACTION_ID']) ?></span>
                                        <?php
                                        $transactionStatus = $refund['TRANSACTION_STATUS'] ?? '';
                                        $statusClass = '';
                                        $statusIcon = '';

                                        if (strtoupper($transactionStatus) === 'SUCCESS') {
                                            $statusClass = 'status-success';
                                            $statusIcon = 'fa-check-circle';
                                        } elseif (strtoupper($transactionStatus) === 'PENDING') {
                                            $statusClass = 'status-pending';
                                            $statusIcon = 'fa-clock';
                                        } elseif (strtoupper($transactionStatus) === 'FAILED') {
                                            $statusClass = 'status-failed';
                                            $statusIcon = 'fa-exclamation-circle';
                                        } else {
                                            $statusClass = 'status-info';
                                            $statusIcon = 'fa-info-circle';
                                        }
                                        ?>
                                        <span class="transaction-status <?= $statusClass ?>">
                                            <i class="fas <?= $statusIcon ?>"></i>
                                            <?= htmlspecialchars($transactionStatus ?: 'N/A') ?>
                                        </span>
                                    </div>

                                    <div class="transaction-details">
                                        <div class="transaction-row">
                                            <span class="transaction-label">
                                                <i class="fas fa-calendar"></i>
                                                Transaction Date:
                                            </span>
                                            <span class="transaction-value">
                                                <?= !empty($refund['TRANSACTION_CREATED_AT']) ? formatDate($refund['TRANSACTION_CREATED_AT']) : 'N/A' ?>
                                            </span>
                                        </div>

                                        <div class="transaction-row">
                                            <span class="transaction-label">
                                                <i class="fas fa-credit-card"></i>
                                                Payment Method:
                                            </span>
                                            <span class="transaction-value">
                                                <?= htmlspecialchars($refund['TRANSACTION_METHOD'] ?: $refund['METHOD']) ?>
                                            </span>
                                        </div>

                                        <?php if (!empty($refund['TRANSACTION_MESSAGE'])): ?>
                                            <div class="transaction-row">
                                                <span class="transaction-label">
                                                    <i class="fas fa-comment"></i>
                                                    Message:
                                                </span>
                                                <span class="transaction-value">
                                                    <?= htmlspecialchars($refund['TRANSACTION_MESSAGE']) ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>

                                        <div class="transaction-amount-row">
                                            <span class="transaction-label">Transaction Amount:</span>
                                            <span class="transaction-amount">৳<?= number_format($refund['AMOUNT'], 2) ?></span>
                                        </div>
                                    </div>

                                    <?php if (strtoupper($transactionStatus) === 'SUCCESS'): ?>
                                        <div class="transaction-success-note">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Your refund has been successfully processed to your account.</span>
                                        </div>
                                    <?php elseif (strtoupper($transactionStatus) === 'PENDING'): ?>
                                        <div class="transaction-pending-note">
                                            <i class="fas fa-clock"></i>
                                            <span>Your refund is being processed. This may take 3-5 business days.</span>
                                        </div>
                                    <?php elseif (strtoupper($transactionStatus) === 'FAILED'): ?>
                                        <div class="transaction-failed-note">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <span>Transaction failed. Please contact customer support.</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="transaction-section">
                                <h5 class="section-title">
                                    <i class="fas fa-credit-card"></i>
                                    Transaction Details
                                </h5>
                                <div class="transaction-card transaction-pending">
                                    <div class="transaction-empty-state">
                                        <i class="fas fa-hourglass-half"></i>
                                        <p>Transaction information will appear here once the refund is processed.</p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="status-timeline">
                            <h5 class="section-title">Status Timeline</h5>
                            <div class="timeline">
                                <div class="timeline-item <?= $refund['STATUS'] !== 'PENDING' ? 'completed' : 'current' ?>">
                                    <div class="timeline-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6>Request Submitted</h6>
                                        <span class="timeline-date"><?= formatDate($refund['CREATED_AT']) ?></span>
                                    </div>
                                </div>

                                <?php if ($refund['STATUS'] === 'PENDING'): ?>
                                    <div class="timeline-item current">
                                        <div class="timeline-icon">
                                            <i class="fas fa-hourglass-half"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h6>Under Review</h6>
                                            <span class="timeline-status">Currently being reviewed by admin</span>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (in_array($refund['STATUS'], ['APPROVED', 'PROCESSED', 'REJECTED'])): ?>
                                    <div class="timeline-item <?= $refund['STATUS'] === 'REJECTED' ? 'rejected' : 'completed' ?>">
                                        <div class="timeline-icon">
                                            <i class="<?= $refund['STATUS'] === 'REJECTED' ? 'fas fa-times' : 'fas fa-check' ?>"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h6><?= $refund['STATUS'] === 'REJECTED' ? 'Request Rejected' : 'Request Approved' ?></h6>
                                            <span class="timeline-status">
                                                <?= $refund['STATUS'] === 'REJECTED' ? 'Refund request was not approved' : 'Refund request has been approved' ?>
                                            </span>
                                            <?php if (!empty($refund['UPDATED_AT']) && $refund['STATUS'] !== 'PENDING'): ?>
                                                <span class="timeline-date"><?= formatDate($refund['UPDATED_AT']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($refund['STATUS'] === 'PROCESSED'): ?>
                                    <div class="timeline-item completed">
                                        <div class="timeline-icon">
                                            <i class="fas fa-check-double"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h6>Payment Processed</h6>
                                            <?php if (!empty($refund['UPDATED_AT'])): ?>
                                                <span class="timeline-date"><?= formatDate($refund['UPDATED_AT']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($refund['TRANSACTION_ID'])): ?>
                                                <span class="timeline-status">
                                                    Transaction completed successfully
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<!-- Cancel Refund Modals -->
<?php foreach ($refunds as $refund): ?>
    <?php if ($refund['STATUS'] === 'PENDING'): ?>
        <div class="modal-overlay" id="cancel-refund-modal-<?= $refund['REFUND_ID'] ?>">
            <div class="modal">
                <div class="modal-header">
                    <h3 class="modal-title">
                        <i class="fas fa-times-circle"></i>
                        Cancel Refund Request
                    </h3>
                    <button type="button" class="modal-close" onclick="closeModal('cancel-refund-modal-<?= $refund['REFUND_ID'] ?>')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form method="POST" action="/refunds/cancel">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data['csrfToken'] ?? '') ?>">
                    <input type="hidden" name="refund_id" value="<?= $refund['REFUND_ID'] ?>">

                    <div class="modal-body">
                        <div class="confirm-content">
                            <p class="confirm-message">Are you sure you want to cancel this refund request?</p>
                            <p class="confirm-details">This action cannot be undone. Once cancelled, you'll need to submit a new refund request if needed.</p>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('cancel-refund-modal-<?= $refund['REFUND_ID'] ?>')">
                            No, Keep Request
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times"></i> Yes, Cancel Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<script>
    // Modal functions
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

    // Close modal on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                closeModal(modal.id);
            });
        }
    });

    // Close modal when clicking outside
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });
</script>

<style>
    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
    }

    .stat-card.total {
        border-left: 4px solid #667eea;
    }

    .stat-card.pending {
        border-left: 4px solid #ffc107;
    }

    .stat-card.approved {
        border-left: 4px solid #28a745;
    }

    .stat-card.processed {
        border-left: 4px solid #17a2b8;
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .stat-card.total .stat-icon {
        background: #667eea;
        color: white;
    }

    .stat-card.pending .stat-icon {
        background: #fff3cd;
        color: #856404;
    }

    .stat-card.approved .stat-icon {
        background: #d4edda;
        color: #155724;
    }

    .stat-card.processed .stat-icon {
        background: #d1ecf1;
        color: #0c5460;
    }

    .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        margin: 0;
        color: #343a40;
    }

    .stat-label {
        margin: 0;
        color: #6c757d;
        font-size: 0.875rem;
    }

    /* Refunds Grid */
    .refunds-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .refund-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .refund-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
    }

    .refund-header {
        position: relative;
        padding: 1.5rem;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .refund-basic-info {
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    .kitchen-avatar-mini {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
    }

    .kitchen-avatar-mini img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .default-avatar-mini {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
    }

    .refund-meta {
        flex: 1;
    }

    .kitchen-name {
        margin: 0;
        font-size: 1.125rem;
        color: #212529;
    }

    .refund-id-date {
        display: flex;
        gap: 0.5rem;
        font-size: 0.875rem;
        color: #6c757d;
        margin-top: 0.25rem;
        flex-wrap: wrap;
    }

    .refund-id,
    .order-id {
        font-weight: 600;
    }

    .refund-status-compact {
        flex-shrink: 0;
        position: absolute;
        top: 5px;
        right: 5px;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.375rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-approved {
        background: #d4edda;
        color: #155724;
    }

    .status-rejected {
        background: #f8d7da;
        color: #721c24;
    }

    .status-processed {
        background: #cce5ff;
        color: #004085;
    }

    /* Refund Details */
    .refund-details {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e9ecef;
    }

    .detail-row {
        display: flex;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
    }

    .detail-row:last-child {
        margin-bottom: 0;
    }

    .detail-label {
        color: #6c757d;
        min-width: 120px;
        flex-shrink: 0;
    }

    .detail-value {
        color: #212529;
        flex: 1;
    }

    .evidence-link {
        color: #007bff;
        text-decoration: none;
    }

    .evidence-link:hover {
        text-decoration: underline;
    }

    /* Refund Amount Section */
    .refund-amount-section {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e9ecef;
    }

    .amount-display {
        display: flex;
        align-items: baseline;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }

    .amount-label {
        font-size: 0.875rem;
        font-weight: 500;
        color: #6c757d;
    }

    .amount-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: #28a745;
    }

    .amount-percentage {
        font-size: 0.75rem;
        color: #6c757d;
    }

    .rejection-reason,
    .processed-date {
        margin-top: 0.5rem;
        padding: 0.5rem;
        background: #f8f9fa;
        border-radius: 4px;
        font-size: 0.75rem;
    }

    .rejection-label,
    .processed-label {
        color: #dc3545;
        font-weight: 600;
    }

    .processed-label {
        color: #17a2b8;
    }

    /* Refund Actions */
    .refund-actions {
        padding: 1rem 1.5rem;
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    /* Refund Details Modal Specific */
    .refund-details-modal {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .refund-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
    }

    .kitchen-info {
        display: flex;
        gap: 1rem;
        /* align-items: center; */
        align-items: flex-start;
    }

    .kitchen-avatar {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
    }

    .kitchen-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .default-avatar-large {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
    }

    .kitchen-meta {
        flex: 1;
    }

    .refund-meta-info {
        display: flex;
        gap: 0.5rem;
        font-size: 0.875rem;
        color: #6c757d;
        margin-top: 0.25rem;
        flex-wrap: wrap;
    }

    .section-title {
        font-size: 1rem;
        color: #495057;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #e9ecef;
    }

    .detail-grid {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .amount-section {
        background: #f8f9fa;
        margin-top: 1.5rem;
        padding: 1.5rem;
        border: 1px solid #e9ecef;
        border-radius: 8px;
    }

    .amount-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
    }

    .amount-value-wrapper {
        display: flex;
        flex-direction: column;
        align-items: baseline;
        gap: 0.5rem;
    }

    .amount-value {
        font-size: 2rem;
        font-weight: 700;
        color: #28a745;
    }

    /* Status Timeline */
    .status-timeline {
        margin-top: 1.5rem;
    }

    .timeline {
        position: relative;
        padding-left: 2rem;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 1rem;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e9ecef;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 1.5rem;
    }

    .timeline-item:last-child {
        margin-bottom: 0;
    }

    .timeline-icon {
        position: absolute;
        left: -2rem;
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
        border: 2px solid #e9ecef;
    }

    .timeline-item.completed .timeline-icon {
        background: #28a745;
        border-color: #28a745;
        color: white;
    }

    .timeline-item.current .timeline-icon {
        background: #ffc107;
        border-color: #ffc107;
        color: white;
    }

    .timeline-item.rejected .timeline-icon {
        background: #dc3545;
        border-color: #dc3545;
        color: white;
    }

    .timeline-content {
        margin-left: 0.5rem;
    }

    .timeline-content h6 {
        margin: 0;
        font-size: 0.875rem;
        color: #495057;
    }

    .timeline-date,
    .timeline-status {
        font-size: 0.75rem;
        color: #6c757d;
        margin-top: 0.25rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .refunds-grid {
            grid-template-columns: 1fr;
        }

        .refund-header {
            flex-direction: column;
            gap: 1rem;
        }

        .refund-status-compact {
            align-self: flex-start;
        }

        .detail-row {
            flex-direction: column;
            gap: 0.25rem;
        }

        .detail-label {
            min-width: auto;
        }

        .refund-modal-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .amount-info {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .modal {
            margin: 0;
            max-height: 100vh;
            border-radius: 0;
        }
    }

    .text-danger {
        color: #dc3545;
    }

    .text-success {
        color: #28a745;
    }

    .transaction-section {
        margin: 20px 0;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 10px;
    }

    .transaction-card {
        background: white;
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        border-left: 4px solid #007bff;
    }

    .transaction-card.transaction-pending {
        border-left-color: #ffc107;
        background: #fff9e6;
    }

    .transaction-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e9ecef;
    }

    .transaction-id {
        font-weight: 600;
        color: #495057;
        font-size: 0.95rem;
    }

    .transaction-status {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .transaction-status.status-success {
        background: #d4edda;
        color: #155724;
    }

    .transaction-status.status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .transaction-status.status-failed {
        background: #f8d7da;
        color: #721c24;
    }

    .transaction-status.status-info {
        background: #e2e3e5;
        color: #383d41;
    }

    .transaction-details {
        margin-bottom: 15px;
    }

    .transaction-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px dashed #f1f1f1;
    }

    .transaction-row:last-child {
        border-bottom: none;
    }

    .transaction-label {
        color: #6c757d;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .transaction-label i {
        width: 18px;
        color: #007bff;
    }

    .transaction-value {
        color: #495057;
        font-weight: 500;
        font-size: 0.9rem;
    }

    .transaction-amount-row {
        margin-top: 10px;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 6px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 600;
    }

    .transaction-amount {
        color: #28a745;
        font-size: 1.2rem;
    }

    .transaction-success-note,
    .transaction-pending-note,
    .transaction-failed-note {
        margin-top: 15px;
        padding: 10px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.9rem;
    }

    .transaction-success-note {
        background: #d4edda;
        color: #155724;
    }

    .transaction-success-note i {
        color: #28a745;
    }

    .transaction-pending-note {
        background: #fff3cd;
        color: #856404;
    }

    .transaction-pending-note i {
        color: #ffc107;
    }

    .transaction-failed-note {
        background: #f8d7da;
        color: #721c24;
    }

    .transaction-failed-note i {
        color: #dc3545;
    }

    .transaction-empty-state {
        text-align: center;
        padding: 20px;
        color: #6c757d;
    }

    .transaction-empty-state i {
        font-size: 2rem;
        margin-bottom: 10px;
        color: #ffc107;
    }

    .transaction-empty-state p {
        margin: 0;
        font-size: 0.95rem;
    }

    /* Timeline enhancements */
    .timeline-item.completed .timeline-content h6 {
        color: #28a745;
    }

    .timeline-item.current .timeline-content h6 {
        color: #007bff;
        font-weight: 600;
    }

    .timeline-item.rejected .timeline-content h6 {
        color: #dc3545;
    }

    .timeline-date {
        font-size: 0.85rem;
        color: #6c757d;
        display: block;
        margin-top: 2px;
    }

    .timeline-status {
        font-size: 0.9rem;
        color: #6c757d;
    }
</style>