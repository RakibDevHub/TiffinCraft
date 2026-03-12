<?php
$user = $data['currentUser'];
$balanceInfo = $data['balanceInfo'];
$withdrawalHistory = $data['withdrawalHistory'] ?? [];
$hasPendingWithdrawals = $data['hasPendingWithdrawals'] ?? false;

function formatPrice($price)
{
    return '৳' . number_format($price, 2);
}

function formatDate($dateString, $format = 'M j, Y g:i A')
{
    if (!$dateString) return '';
    $date = DateTime::createFromFormat('d-M-y h.i.s.u A', $dateString);
    return $date ? $date->format($format) : htmlspecialchars($dateString);
}

function getStatusBadge($status)
{
    $statusClasses = [
        'PENDING' => 'status-pending',
        'APPROVED' => 'status-processing',
        'PROCESSED' => 'status-delivered',
        'REJECTED' => 'status-cancelled'
    ];

    $statusIcons = [
        'PENDING' => 'fa-clock',
        'APPROVED' => 'fa-check-circle',
        'PROCESSED' => 'fa-check-double',
        'REJECTED' => 'fa-times-circle'
    ];

    $class = $statusClasses[$status] ?? 'status-pending';
    $icon = $statusIcons[$status] ?? 'fa-clock';

    return "<span class='status-badge {$class}'><i class='fas {$icon}'></i> {$status}</span>";
}

// Function to format Withdrawal Method display
function formatPaymentMethod($method)
{
    $icons = [
        'Bank Transfer' => 'fa-university',
        'bKash Agent' => 'fa-mobile-alt',
        'bKash Personal' => 'fa-mobile-alt',
        'Nagad Agent' => 'fa-wallet',
        'Nagad Personal' => 'fa-wallet',
        'Rocket Agent' => 'fa-rocket',
        'Rocket Personal' => 'fa-rocket'
    ];

    $icon = $icons[$method] ?? 'fa-money-bill-wave';
    return "<i class='fas {$icon}'></i> " . htmlspecialchars($method);
}

include BASE_PATH . '/src/views/components/flash-popup.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title"><?= htmlspecialchars(ucfirst($data['title'])) ?></h1>
    <p class="page-subtitle">Manage your earnings and withdrawal requests</p>
</div>

<div class="dashboard-grid">
    <!-- Balance Stats -->
    <div class="stats-grid" style="margin-bottom: 30px;">
        <div class="dashboard-card stat-card">
            <div class="card-header">
                <div class="card-title">
                    <p>Available Balance</p>
                    <h1><?= formatPrice($balanceInfo['CURRENT_BALANCE'] ?? 0) ?></h1>
                </div>
                <div class="card-icon blue"><i class="fas fa-money-bill-wave"></i></div>
            </div>
        </div>
        <div class="dashboard-card stat-card">
            <div class="card-header">
                <div class="card-title">
                    <p>Pending Balance</p>
                    <h1><?= formatPrice($balanceInfo['PENDING_WITHDRAWALS'] ?? 0) ?></h1>
                </div>
                <div class="card-icon orange"><i class="fas fa-clock"></i></div>
            </div>
        </div>
        <div class="dashboard-card stat-card">
            <div class="card-header">
                <div class="card-title">
                    <p>Processed Withdrawals</p>
                    <h1><?= count(array_filter($withdrawalHistory, function ($w) {
                            return $w['STATUS'] === 'PROCESSED';
                        })) ?></h1>
                </div>
                <div class="card-icon green"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
        <div class="dashboard-card stat-card">
            <div class="card-header">
                <div class="card-title">
                    <p>Total Withdrawn</p>
                    <h1><?= formatPrice(array_sum(array_column(
                            array_filter($withdrawalHistory, function ($w) {
                                return $w['STATUS'] === 'PROCESSED';
                            }),
                            'AMOUNT'
                        ))) ?></h1>
                </div>
                <div class="card-icon"><i class="fas fa-money-bill-wave"></i></div>
            </div>
        </div>
    </div>

    <!-- Withdrawal Request Section -->
    <div class="dashboard-card" style="margin-bottom: 30px;">
        <div class="card-header">
            <h3>Request Withdrawal</h3>
        </div>
        <div class="card-body">
            <?php if (($balanceInfo['CURRENT_BALANCE'] ?? 0) >= 100 && !$hasPendingWithdrawals): ?>
                <form method="POST" id="withdrawalRequestForm">
                    <input type="hidden" name="action" value="request_withdrawal">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div class="form-row" style="margin-bottom: 0;">
                        <div class="form-group">
                            <label class="form-label">Available Balance</label>
                            <div class="input-with-icon">
                                <i class="fas fa-wallet"></i>
                                <input type="text" class="form-control" value="<?= formatPrice($balanceInfo['CURRENT_BALANCE'] ?? 0) ?>" readonly>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Withdrawal Amount *</label>
                            <div class="input-with-icon">
                                <i class="fas fa-money-bill-wave"></i>
                                <input type="number" class="form-control" name="amount" id="withdrawalAmount"
                                    min="100" max="<?= $balanceInfo['CURRENT_BALANCE'] ?? 0 ?>" step="0.01"
                                    placeholder="Enter amount to withdraw" required>
                            </div>
                            <small class="form-text">Minimum withdrawal amount: ৳100</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Withdrawal Method *</label>
                        <select class="form-control" name="method" id="withdrawalMethod" required>
                            <option value="">Select Payment Method</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="bKash Agent">bKash Agent</option>
                            <option value="bKash Personal">bKash Personal</option>
                            <option value="Nagad Agent">Nagad Agent</option>
                            <option value="Nagad Personal">Nagad Personal</option>
                            <option value="Rocket Agent">Rocket Agent</option>
                            <option value="Rocket Personal">Rocket Personal</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Account Details *</label>
                        <textarea class="form-control" name="account_details" placeholder="Enter your account details (account number, bank name, branch, etc.)" rows="3" required></textarea>
                        <small class="form-text">
                            For Bank Transfer: Include bank name, account holder name, account number, routing number, branch name<br>
                            For Mobile Banking: Include phone number, account type
                        </small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="submitWithdrawal">
                            <i class="fas fa-paper-plane"></i> Request Withdrawal
                        </button>
                    </div>
                </form>
            <?php elseif ($hasPendingWithdrawals): ?>
                <div class="no-data">
                    <i class="fas fa-clock"></i>
                    <p>Pending Withdrawal Exists</p>
                    <p class="text-muted" style="text-align: start; margin-top: 1rem;">You already have pending withdrawals. Please wait for them to be processed before making new requests.</p>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-wallet"></i>
                    <p>Insufficient Balance</p>
                    <p class="text-muted">You need at least ৳100 available balance to make a withdrawal request.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Withdrawal History Table-->
<div class="dashboard-card">
    <div class="card-header" style="margin: 0;">
        <h3>Withdrawal History</h3>
    </div>
    <!-- Filters and Search -->
    <div class="filters-container">
        <div class="search-box">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search-input" placeholder="Search by withdrawal ID or method..." id="withdrawalSearch">
        </div>

        <div class="filter-group">
            <select class="filter-select" id="statusFilter">
                <option value="">All Statuses</option>
                <option value="PENDING">Pending</option>
                <option value="APPROVED">Approved</option>
                <option value="PROCESSED">Processed</option>
                <option value="REJECTED">Rejected</option>
            </select>

            <select class="filter-select" id="dateRangeFilter">
                <option value="all">All Time</option>
                <option value="today">Today</option>
                <option value="yesterday">Yesterday</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="custom">Custom Range</option>
            </select>

            <div class="custom-date-range" id="customDateRange" style="display: none;">
                <input type="date" class="filter-select" id="dateFrom" placeholder="From Date">
                <input type="date" class="filter-select" id="dateTo" placeholder="To Date">
            </div>
        </div>

        <div class="action-buttons-group">
            <button class="btn btn-secondary" id="clearFilters">
                <i class="fas fa-times"></i> Clear
            </button>
        </div>
    </div>


    <div class="card-body">
        <?php if (empty($withdrawalHistory)): ?>
            <div class="no-data">
                <i class="fas fa-history"></i>
                <p>No withdrawal history</p>
                <p class="text-muted">Your withdrawal requests will appear here</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Withdrawal ID</th>
                            <th>Amount</th>
                            <th>Withdrawal Method</th>
                            <th>Status</th>
                            <th>Request Date</th>
                            <th>Processed Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($withdrawalHistory as $withdrawal): ?>
                            <?php
                            $requestDateOnly = '';
                            if ($withdrawal['CREATED_AT']) {
                                $date = DateTime::createFromFormat('d-M-y h.i.s.u A', $withdrawal['CREATED_AT']);
                                if ($date) {
                                    $requestDateOnly = $date->format('Y-m-d');
                                }
                            }

                            $processedDateOnly = '';
                            if ($withdrawal['UPDATED_AT']) {
                                $date = DateTime::createFromFormat('d-M-y h.i.s.u A', $withdrawal['UPDATED_AT']);
                                if ($date) {
                                    $processedDateOnly = $date->format('Y-m-d');
                                }
                            }
                            ?>
                            <tr data-status="<?= strtolower($withdrawal['STATUS']) ?>"
                                data-request-date="<?= $requestDateOnly ?>"
                                data-processed-date="<?= $processedDateOnly ?>">
                                <td>
                                    <div class="transaction-info">
                                        <strong><?= 'WD-' . str_pad($withdrawal['WITHDRAW_ID'], 6, '0', STR_PAD_LEFT) ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <div class="amount-info">
                                        <strong><?= formatPrice($withdrawal['AMOUNT']) ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <div class="payment-method">
                                        <span class="method-badge method-<?= strtolower(str_replace(' ', '-', $withdrawal['METHOD'])) ?>">
                                            <?= formatPaymentMethod($withdrawal['METHOD']) ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <?= getStatusBadge($withdrawal['STATUS']) ?>
                                </td>
                                <td>
                                    <div class="date-info">
                                        <?= formatDate($withdrawal['CREATED_AT']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="date-info">
                                        <?= $withdrawal['UPDATED_AT'] ? formatDate($withdrawal['UPDATED_AT']) : '--' ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action btn-view" onclick="viewWithdrawalDetails(<?= $withdrawal['WITHDRAW_ID'] ?>)" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
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

<!-- Withdrawal Details Modal -->
<div class="modal-overlay" id="withdrawalDetailsModal">
    <div class="modal modal-large">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-receipt"></i>
                Withdrawal Details
            </h2>
            <button class="modal-close" onclick="closeModal('withdrawalDetailsModal')">&times;</button>
        </div>
        <div class="modal-body">
            <?php foreach ($withdrawalHistory as $withdrawal): ?>
                <div class="withdrawal-details-content" id="withdrawal-details-<?= $withdrawal['WITHDRAW_ID'] ?>" style="display: none;">
                    <div class="withdrawal-detail-section">
                        <h3>Transaction Information</h3>
                        <div class="detail-row">
                            <span class="detail-label">Withdrawal ID:</span>
                            <span class="detail-value"><?= 'WD-' . str_pad($withdrawal['WITHDRAW_ID'], 6, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Status:</span>
                            <span class="detail-value"><?= getStatusBadge($withdrawal['STATUS']) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Request Date:</span>
                            <span class="detail-value"><?= formatDate($withdrawal['CREATED_AT']) ?></span>
                        </div>
                        <?php if ($withdrawal['UPDATED_AT']): ?>
                            <div class="detail-row">
                                <span class="detail-label">Processed Date:</span>
                                <span class="detail-value"><?= formatDate($withdrawal['UPDATED_AT']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="withdrawal-detail-section">
                        <h3>Transfer Information</h3>
                        <div class="detail-row">
                            <span class="detail-label">Withdrawal Method:</span>
                            <span class="detail-value">
                                <span class="method-badge method-<?= strtolower(str_replace(' ', '-', $withdrawal['METHOD'])) ?>">
                                    <?= formatPaymentMethod($withdrawal['METHOD']) ?>
                                </span>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Account Details:</span>
                            <span class="detail-value"><?= htmlspecialchars($withdrawal['ACCOUNT_DETAILS']) ?></span>
                        </div>
                    </div>

                    <div class="withdrawal-detail-section">
                        <h3>Amount Details</h3>
                        <div class="detail-row">
                            <span class="detail-label">Requested Amount:</span>
                            <span class="detail-value"><?= formatPrice($withdrawal['AMOUNT']) ?></span>
                        </div>
                    </div>

                    <?php if ($withdrawal['ADMIN_NOTES']): ?>
                        <div class="withdrawal-detail-section" style="margin-bottom: 0; padding-bottom: 0; border: none;">
                            <h3>Admin Notes</h3>
                            <div class="notes-summary">
                                <?= htmlspecialchars(trim($withdrawal['ADMIN_NOTES'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('withdrawalDetailsModal')">Close</button>
        </div>
    </div>
</div>

<script>
    // View withdrawal details
    function viewWithdrawalDetails(withdrawalId) {
        // Hide all withdrawal detail sections first
        document.querySelectorAll(".withdrawal-details-content").forEach((detail) => {
            detail.style.display = "none";
        });

        // Show the specific withdrawal details
        const withdrawalDetail = document.getElementById("withdrawal-details-" + withdrawalId);
        if (withdrawalDetail) {
            withdrawalDetail.style.display = "block";
        }

        document.getElementById("withdrawalDetailsModal").classList.add("active");
    }

    // Close modal function
    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove("active");
    }

    // Format price for JavaScript
    function formatPrice(price) {
        return '৳' + parseFloat(price).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    // Filter functionality for withdrawals
    function filterWithdrawals() {
        const searchText = document.getElementById("withdrawalSearch").value.toLowerCase();
        const statusValue = document.getElementById("statusFilter").value.toLowerCase();
        const dateRange = document.getElementById("dateRangeFilter").value;
        const dateFrom = document.getElementById("dateFrom").value;
        const dateTo = document.getElementById("dateTo").value;
        const rows = document.querySelectorAll(".users-table tbody tr");

        // Get today's date for date calculations
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        rows.forEach((row) => {
            const withdrawalId = row.cells[0].textContent.toLowerCase();
            const withdrawalMethod = row.cells[2].textContent.toLowerCase();
            const status = row.dataset.status;
            const requestDate = row.dataset.requestDate;

            // Search and status filtering
            const searchMatch = !searchText ||
                withdrawalId.includes(searchText) ||
                withdrawalMethod.includes(searchText);
            const statusMatch = !statusValue || status === statusValue;

            // Date filtering
            let dateMatch = true;

            if (dateRange !== "all" && requestDate) {
                const requestDateObj = new Date(requestDate);
                requestDateObj.setHours(0, 0, 0, 0);

                switch (dateRange) {
                    case "today":
                        dateMatch = requestDateObj.getTime() === today.getTime();
                        break;

                    case "yesterday":
                        const yesterday = new Date(today);
                        yesterday.setDate(yesterday.getDate() - 1);
                        dateMatch = requestDateObj.getTime() === yesterday.getTime();
                        break;

                    case "week":
                        const startOfWeek = new Date(today);
                        startOfWeek.setDate(today.getDate() - today.getDay()); // Sunday
                        dateMatch = requestDateObj >= startOfWeek;
                        break;

                    case "month":
                        const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                        dateMatch = requestDateObj >= startOfMonth;
                        break;

                    case "custom":
                        if (dateFrom) {
                            dateMatch = dateMatch && requestDate >= dateFrom;
                        }
                        if (dateTo) {
                            dateMatch = dateMatch && requestDate <= dateTo;
                        }
                        break;
                }
            }

            row.style.display = searchMatch && statusMatch && dateMatch ? "" : "none";
        });
    }

    function clearWithdrawalFilters() {
        document.getElementById("withdrawalSearch").value = "";
        document.getElementById("statusFilter").value = "";
        document.getElementById("dateRangeFilter").value = "all";
        document.getElementById("dateFrom").value = "";
        document.getElementById("dateTo").value = "";
        document.getElementById("customDateRange").style.display = "none";
        filterWithdrawals();
    }

    function toggleWithdrawalCustomDateRange() {
        const dateRangeFilter = document.getElementById("dateRangeFilter");
        const customDateRange = document.getElementById("customDateRange");

        if (dateRangeFilter.value === "custom") {
            customDateRange.style.display = "flex";
            customDateRange.style.gap = "8px";
        } else {
            customDateRange.style.display = "none";
            document.getElementById("dateFrom").value = "";
            document.getElementById("dateTo").value = "";
        }
    }

    // Validate amount input
    document.getElementById('withdrawalAmount')?.addEventListener('input', function() {
        const amount = parseFloat(this.value);
        const availableBalance = <?= $balanceInfo['CURRENT_BALANCE'] ?? 0 ?>;
        const minAmount = 100;

        if (amount < minAmount) {
            this.setCustomValidity(`Minimum withdrawal amount is ${formatPrice(minAmount)}`);
        } else if (amount > availableBalance) {
            this.setCustomValidity(`Amount cannot exceed available balance of ${formatPrice(availableBalance)}`);
        } else {
            this.setCustomValidity('');
        }
    });

    // Initialize event listeners for withdrawals
    document.addEventListener("DOMContentLoaded", function() {
        // Real-time filtering for withdrawals
        const withdrawalSearch = document.getElementById("withdrawalSearch");
        const withdrawalStatusFilter = document.getElementById("statusFilter");
        const withdrawalDateRangeFilter = document.getElementById("dateRangeFilter");
        const withdrawalClearFilters = document.getElementById("clearFilters");

        if (withdrawalSearch) {
            withdrawalSearch.addEventListener("input", filterWithdrawals);
        }
        if (withdrawalStatusFilter) {
            withdrawalStatusFilter.addEventListener("change", filterWithdrawals);
        }
        if (withdrawalDateRangeFilter) {
            withdrawalDateRangeFilter.addEventListener("change", function() {
                toggleWithdrawalCustomDateRange();
                filterWithdrawals();
            });
        }

        const dateFrom = document.getElementById("dateFrom");
        const dateTo = document.getElementById("dateTo");

        if (dateFrom) dateFrom.addEventListener("change", filterWithdrawals);
        if (dateTo) dateTo.addEventListener("change", filterWithdrawals);

        // Clear filters button
        if (withdrawalClearFilters) {
            withdrawalClearFilters.addEventListener("click", clearWithdrawalFilters);
        }

        // Modal overlay close for withdrawal modal
        const withdrawalModal = document.getElementById("withdrawalDetailsModal");
        if (withdrawalModal) {
            withdrawalModal.addEventListener("click", (e) => {
                if (e.target === withdrawalModal) closeModal('withdrawalDetailsModal');
            });
        }

        // Initialize custom date range visibility
        toggleWithdrawalCustomDateRange();
    });
</script>