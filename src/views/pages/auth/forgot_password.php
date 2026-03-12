<main class="auth-section">
    <section class="auth-page">
        <div class="main-container">
            <div class="auth-card">
                <div class="auth-card-header">
                    <h1 class="auth-title">Forgot Password?</h1>
                    <p class="auth-subtitle">Enter your registered email address and we’ll send you a reset link.</p>
                </div>

                <div class="auth-card-body">
                    <?php if ($msg = Session::flash('success')): ?>
                        <div class="flash flash-success"><?= htmlspecialchars($msg) ?></div>
                    <?php elseif ($msg = Session::flash('error')): ?>
                        <div class="flash flash-warning"><?= htmlspecialchars($msg) ?></div>
                    <?php endif; ?>

                    <form action="/forgot-password" method="POST" class="auth-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <div class="form-group">
                            <label for="resetEmail">Email Address</label>
                            <input type="email" name="email" id="resetEmail"
                                placeholder="you@example.com"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                        <button type="submit" class="btn-submit">Send Reset Link</button>
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