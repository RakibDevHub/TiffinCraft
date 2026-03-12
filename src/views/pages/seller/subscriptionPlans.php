<?php
$kitchen = $data['kitchen'];
$plans = $data['plans'];

include BASE_PATH . '/src/views/components/flash-popup.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title"><?= htmlspecialchars(ucfirst($title)) ?></h1>
    <p class="page-subtitle">Choose a subscription plan to activate your kitchen</p>
</div>

<!-- Progress Steps -->
<div class="progress-steps">
    <div class="step completed">
        <div class="step-number">
            <i class="fas fa-check"></i>
        </div>
        <div class="step-label">Kitchen Setup</div>
    </div>
    <div class="step active">
        <div class="step-number">2</div>
        <div class="step-label">Subscription</div>
    </div>
    <div class="step">
        <div class="step-number">3</div>
        <div class="step-label">Payment</div>
    </div>
</div>

<!-- Kitchen Info Banner -->
<div class="info-banner">
    <div class="banner-content">
        <i class="fas fa-store"></i>
        <div>
            <h4><?= htmlspecialchars($kitchen['NAME']) ?></h4>
            <p>Ready to go live! Choose a plan that fits your business needs.</p>
        </div>
    </div>
</div>

<!-- Subscription Plans -->
<div class="subscription-plans-container">
    <div class="plans-header">
        <h2>Available Plans</h2>
        <p>All plans include kitchen activation, order management, and customer support</p>
    </div>

    <div class="plans-grid">
        <?php if (empty($plans)): ?>
            <div class="no-plans">
                <i class="fas fa-tags"></i>
                <h3>No subscription plans available</h3>
                <p>Please contact support for assistance.</p>
            </div>
        <?php else: ?>
            <?php foreach ($plans as $plan): ?>
                <div class="plan-card <?= $plan['IS_HIGHLIGHT'] ? 'featured' : '' ?>">
                    <?php if ($plan['IS_HIGHLIGHT']): ?>
                        <div class="plan-badge">Most Popular</div>
                    <?php endif; ?>

                    <div class="plan-header">
                        <h3><?= htmlspecialchars($plan['PLAN_NAME']) ?></h3>
                        <div class="plan-price">
                            <span class="price">৳<?= number_format($plan['MONTHLY_FEE'], 2) ?></span>
                            <span class="period">/month</span>
                        </div>
                        <p class="plan-description">
                            <?= !empty($plan['DESCRIPTION']) ? htmlspecialchars($plan['DESCRIPTION']) : 'Perfect for growing your food business' ?>
                        </p>
                    </div>

                    <div class="plan-features">
                        <div class="feature-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Up to <?= $plan['MAX_ITEMS'] ?> menu items</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check-circle"></i>
                            <span><?= $plan['COMMISSION_RATE'] ?>% commission rate</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check-circle"></i>
                            <span>24/7 customer support</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Order management system</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Sales analytics dashboard</span>
                        </div>
                    </div>

                    <form method="POST" action="/business/dashboard/select-plan" class="plan-actions">
                        <input type="hidden" name="plan_id" value="<?= $plan['PLAN_ID'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                        <button type="submit" class="btn <?= $plan['IS_HIGHLIGHT'] ? 'btn-primary' : 'btn-secondary' ?>">
                            <?= $plan['IS_HIGHLIGHT'] ? 'Get Started' : 'Choose Plan' ?>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- FAQ Section -->
<div class="dashboard-card" style="margin-top: 40px;">
    <div class="card-header">
        <h3>Frequently Asked Questions</h3>
    </div>
    <div class="card-body">
        <div class="faq-list">
            <div class="faq-item">
                <h4>When will my kitchen be activated?</h4>
                <p>Your kitchen will be activated immediately after successful payment. The approval process usually takes less than 24 hours.</p>
            </div>
            <div class="faq-item">
                <h4>Can I change my plan later?</h4>
                <p>Yes, you can upgrade or downgrade your plan at any time. Changes will be applied at the start of your next billing cycle.</p>
            </div>
            <div class="faq-item">
                <h4>What payment methods are accepted?</h4>
                <p>We accept bKash, Nagad, bank transfers, and credit/debit cards.</p>
            </div>
            <div class="faq-item">
                <h4>Is there a setup fee?</h4>
                <p>No, there are no setup fees. You only pay the monthly subscription fee.</p>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add loading state to subscription buttons
        const forms = document.querySelectorAll('.plan-actions form');

        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const button = this.querySelector('button[type="submit"]');
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            });
        });
    });
</script>