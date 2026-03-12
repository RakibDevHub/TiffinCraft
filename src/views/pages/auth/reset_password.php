<main class="auth-section">
    <section class="auth-page">
        <div class="main-container">
            <div class="auth-card">
                <div class="auth-card-header">
                    <h1 class="auth-title">Reset Your Password</h1>
                    <p class="auth-subtitle">Choose a strong new password to secure your account.</p>
                </div>

                <div class="auth-card-body">
                    <?php if ($msg = Session::flash('success')): ?>
                        <div class="flash flash-success"><?= htmlspecialchars($msg) ?></div>
                    <?php elseif ($msg = Session::flash('error')): ?>
                        <div class="flash flash-warning"><?= htmlspecialchars($msg) ?></div>
                    <?php endif; ?>

                    <form action="<?= htmlspecialchars($formAction) ?>" method="POST" class="auth-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                        <div class="form-group">
                            <label for="newPassword">New Password</label>
                            <input type="password" name="password" id="newPassword" placeholder="••••••••" required>
                            <div class="password-requirements">
                                Must be at least 8 characters with uppercase, lowercase, number, and special character
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirmPassword">Confirm Password</label>
                            <input type="password" name="confirm_password" id="confirmPassword" placeholder="••••••••" required>
                        </div>

                        <button type="submit" class="btn-submit">Reset Password</button>
                    </form>

                    <div class="auth-link">
                        <a href="/login">← Back to Login</a>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <?php
    $fillColor = '#FFFBEB';
    $invert = true;
    // $offset = true;

    include BASE_PATH . '/src/views/components/divider-banner.php';
    ?>
</main>