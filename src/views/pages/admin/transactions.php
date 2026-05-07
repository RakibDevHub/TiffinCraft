<?php
$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 50;
$totalTransactions = $data['totalTransactions'] ?? 0;
$totalPages = ceil($totalTransactions / $limit);
$transactions = $data['transactions'] ?? [];
$pendingWithdrawals = $data['pendingWithdrawals'] ?? [];
$pendingRefunds = $data['pendingRefunds'] ?? [];
$filters = $data['filters'] ?? [];

function dateFormat($dateString, $format = 'M j, Y g:i A')
{
    if (!$dateString)
        return '';
    $date = DateTime::createFromFormat('d-M-y h.i.s.u A', $dateString);
    return $date ? $date->format($format) : htmlspecialchars((string) $dateString);
}

function getStatusBadge($status)
{
    $status = strtoupper($status);
    $statusClasses = [
        'PENDING' => 'status-pending',
        'SUCCESS' => 'status-success',
        'FAILED' => 'status-failed',
        'REFUNDED' => 'status-refunded',
        'CANCELLED' => 'status-cancelled',
        'APPROVED' => 'status-success',
        'REJECTED' => 'status-failed',
        'PROCESSED' => 'status-success'
    ];

    $statusIcons = [
        'PENDING' => 'fa-clock',
        'SUCCESS' => 'fa-check-circle',
        'FAILED' => 'fa-times-circle',
        'REFUNDED' => 'fa-undo',
        'CANCELLED' => 'fa-ban',
        'APPROVED' => 'fa-check',
        'REJECTED' => 'fa-times',
        'PROCESSED' => 'fa-check-double'
    ];

    $class = $statusClasses[$status] ?? 'status-pending';
    $icon = $statusIcons[$status] ?? 'fa-clock';

    return "<span class='status-badge {$class}'><i class='fas {$icon}'></i> {$status}</span>";
}

function getRecordTypeBadge($type)
{
    $typeClasses = [
        'PAYMENT' => 'type-payment',
        'PAYOUT' => 'type-withdrawal',
    ];

    $typeIcons = [
        'PAYMENT' => 'fa-credit-card',
        'PAYOUT' => 'fa-money-bill-wave',
    ];

    $typeText = [
        'PAYMENT' => 'PAYMENT',
        'PAYOUT' => 'PAYOUT',
    ];

    $class = $typeClasses[$type] ?? 'type-payment';
    $icon = $typeIcons[$type] ?? 'fa-credit-card';
    $text = $typeText[$type] ?? 'UNKNOWN';

    return "<span class='type-badge {$class}' style='display: flex; flex-direction: row; align-items: center; gap: 0.5rem; width: fit-content;'><i class='fas {$icon}'></i> {$text}</span>";
}

function getReferenceTypeBadge($type)
{
    $type = strtoupper($type);

    $typeClasses = [
        'ORDER' => 'type-order',
        'SUBSCRIPTION' => 'type-subscription',
        'WITHDRAWAL' => 'type-withdraw',
        'REFUND' => 'type-refund'
    ];

    $typeIcons = [
        'ORDER' => 'fa-shopping-cart',
        'SUBSCRIPTION' => 'fa-sync-alt',
        'WITHDRAWAL' => 'fa-money-bill-wave',
        'REFUND' => 'fa-undo'
    ];

    $typeText = [
        'ORDER' => 'ORDER',
        'SUBSCRIPTION' => 'SUBSCRIPTION',
        'WITHDRAWAL' => 'WITHDRAWAL',
        'REFUND' => 'REFUND'
    ];

    $class = $typeClasses[$type] ?? 'type-order';
    $icon = $typeIcons[$type] ?? 'fa-question-circle';
    $text = $typeText[$type] ?? 'UNKNOWN';

    return "<span class='type-badge {$class}' style='display: flex; flex-direction: row; align-items: center; gap: 0.5rem; width: fit-content;'><i class='fas {$icon}'></i> {$text}</span>";
}

include BASE_PATH . '/src/views/components/flash-popup.php';
?>

<!-- Page Header -->
<div class="page-header dashboard-overview">
    <h1 class="page-title" style="color: #fff;"><?= htmlspecialchars(ucfirst($title)) ?></h1>
    <p class="page-subtitle" style="color: #fff;">Comprehensive financial transactions management system</p>
</div>

<!-- Filters and Search -->
<div class="filters-container">
    <!-- Search Box -->
    <div class="search-filter search-box" style="flex: 2;">
        <form method="GET" action="/admin/dashboard/transactions" class="search-form" id="searchForm">
            <div class="search-input-wrapper">
                <div class="search-icon">
                    <i class="fas fa-search"></i>
                </div>
                <input type="text" name="search" placeholder="Search by transaction ID, user, email..."
                    value="<?= htmlspecialchars($filters['search'] ?? '') ?>" class="search-input">
            </div>
            <input type="hidden" name="status" value="<?= htmlspecialchars($filters['status'] ?? '') ?>">
            <input type="hidden" name="reference_type"
                value="<?= htmlspecialchars($filters['reference_type'] ?? '') ?>">
            <input type="hidden" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
            <input type="hidden" name="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
            <input type="hidden" name="page" value="1">
        </form>
    </div>

    <div class="filter-group">
        <!-- Status Filter -->
        <div class="status-filter">
            <form method="GET" action="/admin/dashboard/transactions" id="statusForm">
                <select name="status" class="filter-select">
                    <option value="">All Statuses</option>
                    <option value="PENDING" <?= ($filters['status'] ?? '') === 'PENDING' ? 'selected' : '' ?>>Pending
                    </option>
                    <option value="SUCCESS" <?= ($filters['status'] ?? '') === 'SUCCESS' ? 'selected' : '' ?>>Success
                    </option>
                    <option value="FAILED" <?= ($filters['status'] ?? '') === 'FAILED' ? 'selected' : '' ?>>Failed</option>
                    <option value="REFUNDED" <?= ($filters['status'] ?? '') === 'REFUNDED' ? 'selected' : '' ?>>Refunded
                    </option>
                    <option value="CANCELLED" <?= ($filters['status'] ?? '') === 'CANCELLED' ? 'selected' : '' ?>>Cancelled
                    </option>
                </select>
                <input type="hidden" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                <input type="hidden" name="reference_type"
                    value="<?= htmlspecialchars($filters['reference_type'] ?? '') ?>">
                <input type="hidden" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
                <input type="hidden" name="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
                <input type="hidden" name="page" value="1">
            </form>
        </div>

        <!-- Type Filter -->
        <div class="status-filter">
            <form method="GET" action="/admin/dashboard/transactions" id="typeForm">
                <select name="reference_type" class="filter-select">
                    <option value="">All Types</option>
                    <option value="ORDER" <?= ($filters['reference_type'] ?? '') === 'ORDER' ? 'selected' : '' ?>>Order
                    </option>
                    <option value="SUBSCRIPTION" <?= ($filters['reference_type'] ?? '') === 'SUBSCRIPTION' ? 'selected' : '' ?>>Subscription</option>
                    <option value="WITHDRAWAL" <?= ($filters['reference_type'] ?? '') === 'WITHDRAWAL' ? 'selected' : '' ?>>Withdrawal</option>
                    <option value="REFUND" <?= ($filters['reference_type'] ?? '') === 'REFUND' ? 'selected' : '' ?>>Refund
                    </option>
                </select>
                <input type="hidden" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                <input type="hidden" name="status" value="<?= htmlspecialchars($filters['status'] ?? '') ?>">
                <input type="hidden" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
                <input type="hidden" name="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
                <input type="hidden" name="page" value="1">
            </form>
        </div>
    </div>

    <!-- Clear Filters Button -->
    <div class="clear-filter">
        <a href="/admin/dashboard/transactions" class="clear-btn" id="clearFiltersBtn">
            Clear Filters
        </a>
    </div>
</div>

<!-- Transactions Table -->
<div class="dashboard-card">
    <!-- Pagination -->
    <div class="card-header pagination-header">
        <div class="pagination-info">
            Showing <?= ($page - 1) * $limit + 1 ?> to <?= min($page * $limit, $totalTransactions) ?> of
            <?= $totalTransactions ?> transactions
        </div>
        <?php if ($totalPages > 1): ?>
            <div class="pagination-controls">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&limit=<?= $limit ?>&search=<?= htmlspecialchars($filters['search'] ?? '') ?>&status=<?= htmlspecialchars($filters['status'] ?? '') ?>&reference_type=<?= htmlspecialchars($filters['reference_type'] ?? '') ?>&date_from=<?= htmlspecialchars($filters['date_from'] ?? '') ?>&date_to=<?= htmlspecialchars($filters['date_to'] ?? '') ?>"
                        class="pagination-btn">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>

                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);

                for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                    <a href="?page=<?= $i ?>&limit=<?= $limit ?>&search=<?= htmlspecialchars($filters['search'] ?? '') ?>&status=<?= htmlspecialchars($filters['status'] ?? '') ?>&reference_type=<?= htmlspecialchars($filters['reference_type'] ?? '') ?>&date_from=<?= htmlspecialchars($filters['date_from'] ?? '') ?>&date_to=<?= htmlspecialchars($filters['date_to'] ?? '') ?>"
                        class="pagination-btn <?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&limit=<?= $limit ?>&search=<?= htmlspecialchars($filters['search'] ?? '') ?>&status=<?= htmlspecialchars($filters['status'] ?? '') ?>&reference_type=<?= htmlspecialchars($filters['reference_type'] ?? '') ?>&date_from=<?= htmlspecialchars($filters['date_from'] ?? '') ?>&date_to=<?= htmlspecialchars($filters['date_to'] ?? '') ?>"
                        class="pagination-btn">
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
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Details</th>
                        <th>User</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="7" class="no-data">
                                <i class="fas fa-receipt"></i>
                                <p>No transactions found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $transaction): ?>
                            <?php
                            $transactionType = strtoupper($transaction['TRANSACTION_TYPE'] ?? '');
                            $referenceType = strtoupper($transaction['REFERENCE_TYPE'] ?? '');
                            $status = strtoupper($transaction['STATUS'] ?? '');
                            $gatewayData = json_decode($transaction['GATEWAY_RESPONSE'], true);
                            $trxId = (!empty($gatewayData['bank_tran_id'])) ? $gatewayData['bank_tran_id'] : ($gatewayData['trx_id'] ?? 'N/A');
                            ?>
                            <tr data-transaction-id="<?= $transaction['ID'] ?>">
                                <td>
                                    <?= getRecordTypeBadge($transactionType) ?>
                                    <div class="date-info" style="min-width: 60px;">
                                        <span class="date">
                                            <?= dateFormat($transaction['CREATED_AT'], 'M j, Y') ?>
                                        </span>
                                        <span class="time">
                                            <?= dateFormat($transaction['CREATED_AT'], 'h:i A') ?>
                                        </span>
                                    </div>
                                </td>

                                <td>
                                    <div class="transaction-info">
                                        <div class="transaction-id">
                                            #<?= htmlspecialchars($transaction['TRANSACTION_ID']) ?>
                                        </div>

                                        <?php if (!empty($transaction['PAYMENT_METHOD'])): ?>
                                            <div class="transaction-method">
                                                <?= htmlspecialchars($transaction['PAYMENT_METHOD']) ?>
                                            </div>
                                            <div class="transaction-method">
                                                bKash TrxID: <?= htmlspecialchars($trxId) ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($transaction['DESCRIPTION'])): ?>
                                            <div class="transaction-desc">
                                                <?= htmlspecialchars($transaction['DESCRIPTION']) ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($transaction['REFERENCE_ID'])): ?>
                                            <div class="transaction-ref">
                                                ID: #<?= htmlspecialchars($transaction['REFERENCE_ID']) ?>
                                                <?= getReferenceTypeBadge($referenceType) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($transaction['USER_NAME'] ?? '') ?>&background=4a6cf7&color=fff"
                                                class="user-avatar"
                                                alt="<?= htmlspecialchars($transaction['USER_NAME'] ?? '') ?>">
                                        </div>
                                        <div class="user-details">
                                            <h4><?= htmlspecialchars($transaction['USER_NAME'] ?? 'N/A') ?></h4>
                                            <small><?= htmlspecialchars($transaction['EMAIL'] ?? '') ?></small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="amount">
                                        <span
                                            class="amount-value amount-<?= htmlspecialchars(strtolower($transaction['STATUS'])) ?>">
                                            ৳<?= number_format($transaction['AMOUNT'] ?? 0, 2) ?>
                                        </span>
                                        <span class="currency">
                                            <?= htmlspecialchars($transaction['CURRENCY'] ?? '') ?>
                                        </span>
                                    </div>
                                </td>

                                <td>
                                    <?= getStatusBadge($status) ?>
                                </td>

                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action btn-view"
                                            onclick="viewTransactionModal('<?= htmlspecialchars($transaction['ID']) ?>')"
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
</div>

<!-- Transaction Details Modal -->
<div class="modal-overlay" id="transactionDetailsModal">
    <div class="modal modal-large">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-receipt"></i>
                Transaction Details
            </h2>
            <button class="modal-close" onclick="closeModal('transactionDetailsModal')">&times;</button>
        </div>
        <div class="modal-body">
            <?php foreach ($transactions as $transaction): ?>
                <?php
                $referenceType = strtoupper($transaction['REFERENCE_TYPE'] ?? '');
                $status = strtoupper($transaction['STATUS'] ?? '');
                $gatewayData = json_decode($transaction['GATEWAY_RESPONSE'], true);
                $metaData = json_decode($transaction['METADATA'], true);
                $trxId = (!empty($gatewayData['bank_tran_id'])) ? $gatewayData['bank_tran_id'] : ($gatewayData['trx_id'] ?? 'N/A');

                $userLabel = 'Payment By';
                if ($referenceType === 'WITHDRAWAL') {
                    $userLabel = 'Withdraw By';
                } elseif ($referenceType === 'REFUND') {
                    $userLabel = 'Refund For';
                } elseif ($transactionType === 'PAYOUT') {
                    $userLabel = 'Payment By';
                }
                ?>
                <div class="transaction-details-content"
                    id="transaction-details-<?= htmlspecialchars($transaction['ID'] ?? '') ?>" style="display: none;">
                    <div class="transaction-details-container">
                        <div class="transaction-header">
                            <h3>
                                <?= htmlspecialchars($transaction['REFERENCE_TYPE'] ?? '') ?> -
                                #<?= htmlspecialchars($transaction['TRANSACTION_ID'] ?? '') ?>
                            </h3>
                            <?= getStatusBadge($status) ?>
                        </div>

                        <div class="details-grid">
                            <!-- Transaction Information -->
                            <div class="detail-section">
                                <h4><i class="fas fa-info-circle"></i> Transaction Information</h4>
                                <div class="detail-row">
                                    <div class="detail-label">Transaction ID</div>
                                    <div class="detail-value">
                                        #<?= htmlspecialchars($transaction['TRANSACTION_ID'] ?? 'N/A') ?></div>
                                </div>
                                <?php if (!empty($transaction['PAYMENT_METHOD'])): ?>
                                    <div class="detail-row">
                                        <div class="detail-label">Payment Method</div>
                                        <div class="detail-value"><?= htmlspecialchars($transaction['PAYMENT_METHOD']) ?></div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">bKash TrxID</div>
                                        <div class="detail-value"><?= htmlspecialchars($trxId) ?></div>
                                    </div>
                                <?php endif; ?>
                                <div class="detail-row">
                                    <div class="detail-label">Amount</div>
                                    <div class="detail-value">৳<?= number_format($transaction['AMOUNT'] ?? 0, 2) ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label"><?= htmlspecialchars($userLabel) ?></div>
                                    <div class="detail-value"><?= htmlspecialchars($transaction['USER_NAME'] ?? 'N/A') ?>
                                    </div>
                                </div>

                                <div class="detail-row">
                                    <div class="detail-label">Contact</div>
                                    <div class="detail-value"><?= htmlspecialchars($transaction['EMAIL'] ?? 'N/A') ?></div>
                                </div>

                                <?php if (!empty($metaData['processed_by']) || !empty($metaData['processed_user_email'])): ?>
                                    <div class="detail-row">
                                        <div class="detail-label">Processed By</div>
                                        <div class="detail-value"><?= htmlspecialchars($metaData['processed_by'] ?? 'N/A') ?>
                                        </div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Processor Contact</div>
                                        <div class="detail-value">
                                            <?= htmlspecialchars($metaData['processed_user_email'] ?? 'N/A') ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="detail-row">
                                    <div class="detail-label">Date</div>
                                    <div class="detail-value"><?= dateFormat($transaction['CREATED_AT']) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary"
                onclick="closeModal('transactionDetailsModal')">Close</button>
            <button type="button" class="btn btn-primary" onclick="printTransactionDetails()">
                <i class="fas fa-print"></i> Print Receipt
            </button>
        </div>
    </div>
</div>