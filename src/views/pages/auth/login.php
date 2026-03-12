<main class="auth-section">
    <section class="login-page auth-page">
        <div class="main-container">
    
            <div class="auth-card">
    
                <div class="auth-card-header">
                    <h1 class="auth-title">Welcome Back</h1>
                    <p class="auth-subtitle">Login to your TiffinCraft account</p>
                </div>
    
                <div class="auth-card-body">
                    <?php if ($msg = Session::flash('success')): ?>
                        <div class="flash flash-success"><?= htmlspecialchars($msg) ?></div>
                    <?php elseif ($msg = Session::flash('error')): ?>
                        <div class="flash flash-warning"><?= htmlspecialchars($msg) ?></div>
                    <?php elseif ($msg = Session::flash('warning')): ?>
                        <div class="flash flash-info">
                            <?= htmlspecialchars($msg) ?>
                            <button type="button" id="resendBtn" class="resend-link">
                                Resend Email
                            </button>
                        </div>
                    <?php endif; ?>
    
                    <form action="/login" method="POST" class="auth-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" name="email" id="email"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                placeholder="you@example.com" required>
                        </div>
    
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" name="password" id="password" placeholder="••••••••" required>
                            <div class="forgot-password">
                                <a href="/forgot-password">Forgot password?</a>
                            </div>
                        </div>
    
                        <button type="submit" class="btn-submit">Login</button>
                    </form>
    
                    <div class="auth-link">
                        Don't have an account? <a href="/register">Create one</a>
                    </div>
                </div>
    
            </div>
    
            <!-- Resend Verification Modal -->
            <div id="resendModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span id="closeModal" class="close">&times;</span>
    
                    <div class="modal-header">
                        <h3 class="modal-title">Verify Your Email</h3>
                        <p class="modal-subtitle">Enter your email address to resend the verification link.</p>
                    </div>
    
                    <form action="/resend-verification" method="POST" class="auth-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    
                        <div class="form-group">
                            <label for="resendEmail">Email Address</label>
                            <input type="email" id="resendEmail" name="email"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                placeholder="you@example.com" required>
                        </div>
    
                        <button type="submit" class="btn-submit">Resend Link</button>
                    </form>
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