<?php
$user = $data['currentUser'];
$plan = $data['plan'];
$plans = $data['plans'];
$pendingSubscription = $data['pendingSubscription'];

$subType = $pendingSubscription['sub_type'] ?? 'NEW';
$previousPlan = $pendingSubscription['previous_plan'] ?? null;
$amountToPay = $pendingSubscription['amount'] ?? (float)$plan['MONTHLY_FEE'];

$previousPlanName = 'N/A';
$proratedCredit = 0;

if (!empty($previousPlan['plan_id']) && is_array($plans)) {
    foreach ($plans as $p) {
        if ($p['PLAN_ID'] == $previousPlan['plan_id']) {
            $previousPlanName = $p['PLAN_NAME'];

            if (in_array($subType, ['UPGRADE', 'RENEWAL'])) {
                $remainingDays = $previousPlan['remaining_days'] ?? 0;
                $monthlyFee = (float)$previousPlan['monthly_fee'];

                $startTimestamp = strtotime($previousPlan['start_date']);
                $endTimestamp = strtotime($previousPlan['end_date']);

                if ($startTimestamp && $endTimestamp) {
                    $totalDays = max(1, ($endTimestamp - $startTimestamp) / 86400);
                } else {
                    $totalDays = 30;
                }

                $proratedCredit = ($monthlyFee / $totalDays) * $remainingDays;
            }
            break;
        }
    }
}

include BASE_PATH . '/src/views/components/flash-popup.php';

?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title"><?= htmlspecialchars(ucfirst($title)) ?></h1>
    <p class="page-subtitle">Complete your subscription payment to activate your kitchen</p>
</div>



<div class="payment-container">
    <!-- SUbscription Summary -->
    <div class="dashboard-card order-summary-card">
        <div class="card-header">
            <div class="subtype-label">
                <?php if ($subType === 'RENEWAL'): ?>
                    <h3 class="badge badge-success">Renewal</h3>
                <?php elseif ($subType === 'UPGRADE'): ?>
                    <h3 class="badge badge-warning">Upgrade</h3>
                <?php elseif ($subType === 'DOWNGRADE'): ?>
                    <h3 class="badge badge-info">Downgrade</h3>
                <?php else: ?>
                    <h3 class="badge badge-primary">New Subscription</h3>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (in_array($subType, ['UPGRADE', 'RENEWAL']) && $previousPlan): ?>
                <table class="table comparison-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Plan Name</th>
                            <th>Monthly Fee (৳)</th>
                            <th>Remaining Days</th>
                            <th>Prorated Credit (৳)</th>
                            <th>Amount to Pay (৳)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Current Plan</td>
                            <td><?= htmlspecialchars($previousPlanName) ?></td>
                            <td><?= number_format($previousPlan['monthly_fee'], 2) ?></td>
                            <td><?= $previousPlan['remaining_days'] ?? 0 ?></td>
                            <td><?= number_format($proratedCredit, 2) ?></td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td>New Plan</td>
                            <td><?= htmlspecialchars($plan['PLAN_NAME']) ?></td>
                            <td><?= number_format($plan['MONTHLY_FEE'], 2) ?></td>
                            <td>-</td>
                            <td>-</td>
                            <td><?= number_format($amountToPay, 2) ?></td>
                        </tr>
                    </tbody>
                </table>

                <div class="order-note">
                    <i class="fas fa-info-circle"></i>
                    <p>Amount to Pay = New Plan Monthly Fee − Prorated Credit from Current Plan</p>
                </div>
            <?php else: ?>
                <div class="order-summary">
                    <div class="summary-item">
                        <span class="label">Plan Name:</span>
                        <span class="value"><?= htmlspecialchars($plan['PLAN_NAME']) ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Billing Cycle:</span>
                        <span class="value">Monthly</span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Subscription Fee:</span>
                        <span class="value">৳<?= number_format($amountToPay, 2) ?></span>
                    </div>
                    <div class="summary-item total">
                        <span class="label">Total Amount:</span>
                        <span class="value">৳<?= number_format($amountToPay, 2) ?></span>
                    </div>
                </div>
                <div class="order-note">
                    <i class="fas fa-info-circle"></i>
                    <p>Your kitchen will be activated immediately after successful payment</p>
                </div>
            <?php endif; ?>

        </div>
    </div>


    <!-- SSLCommerz Payment -->
    <div class="dashboard-card payment-card">
        <div class="card-header">
            <h3>Secure Payment</h3>
            <p>Pay securely through SSLCommerz</p>
        </div>

        <div class="card-body">
            <form method="POST" action="/business/dashboard/subscription/payment" id="paymentForm">
                <input type="hidden" name="plan_id" value="<?= $plan['PLAN_ID'] ?>">
                <input type="hidden" name="amount" value="<?= $amountToPay ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <!-- Customer Information -->
                <div class="form-section">
                    <h4 class="section-title">
                        <i class="fas fa-user"></i>
                        Customer Information
                    </h4>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-control" name="customer_name"
                                value="<?= htmlspecialchars($user['NAME']) ?>" required readonly>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email Address *</label>
                            <input type="email" class="form-control" name="customer_email"
                                value="<?= htmlspecialchars($user['EMAIL']) ?>" required readonly>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Phone Number *</label>
                            <input type="tel" class="form-control" name="customer_phone"
                                value="<?= htmlspecialchars($user['PHONE']) ?>" required readonly>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Address *</label>
                            <input type="text" class="form-control" name="customer_address"
                                value="<?= htmlspecialchars($user['ADDRESS'] ?? 'Bangladesh') ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Terms and Conditions -->
                <div class="form-group terms-section">
                    <label class="form-checkbox" style="margin-bottom: 0px; padding: 0;">
                        <input type="checkbox" name="terms" value="1">
                        <span class="checkmark"></span>
                        I agree to the <a href="/terms" target="_blank">Terms of Service</a>,
                        <a href="/privacy" target="_blank">Privacy Policy</a>, and
                        <a href="/refund" target="_blank">Refund Policy</a>
                    </label>

                    <div id="termsWarning" class="terms-warning" style="display: none;">
                        <div class="warning-content">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>Please accept the terms and conditions to proceed with payment</span>
                        </div>
                    </div>
                </div>

                <!-- Payment Actions -->
                <div class="payment-actions">
                    <button type="submit" class="btn btn-primary btn-lg" id="payButton">
                        <i class="fas fa-lock"></i> Pay Now ৳<?= number_format($amountToPay, 2) ?>
                    </button>

                    <a href="/business/dashboard/select-plan" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Plans
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const paymentForm = document.getElementById('paymentForm');
        const payButton = document.getElementById('payButton');
        const termsCheckbox = document.querySelector('input[name="terms"]');
        const termsWarning = document.getElementById('termsWarning');

        paymentForm.addEventListener('submit', function(e) {
            const termsAccepted = termsCheckbox.checked;

            if (!termsAccepted) {
                e.preventDefault();

                // Show warning in the form
                showTermsWarning();
                return false;
            }

            payButton.disabled = true;
            payButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Redirecting to SSLCommerz...';
        });

        // Hide warning when user checks the box
        termsCheckbox.addEventListener('change', function() {
            if (this.checked) {
                hideTermsWarning();
            }
        });

        const addressField = document.querySelector('input[name="customer_address"]');
        if (addressField && !addressField.value) {
            addressField.focus();
        }
    });

    function showTermsWarning() {
        const termsWarning = document.getElementById('termsWarning');
        const termsSection = document.querySelector('.terms-section');

        // Show the warning message
        termsWarning.style.display = 'block';

        // Add visual highlight to the terms section
        termsSection.classList.add('terms-error');

        // Scroll to terms section
        termsSection.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });

        // Focus on the checkbox
        const termsCheckbox = document.querySelector('input[name="terms"]');
        termsCheckbox.focus();
    }

    function hideTermsWarning() {
        const termsWarning = document.getElementById('termsWarning');
        const termsSection = document.querySelector('.terms-section');

        // Hide the warning message
        termsWarning.style.display = 'none';

        // Remove visual highlight
        termsSection.classList.remove('terms-error');
    }
</script>