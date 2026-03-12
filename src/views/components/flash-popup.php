<div class="flash-messages-container">
    <?php if ($msg = Session::flash('error')): ?>
        <div class="flash-message flash-error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?= htmlspecialchars($msg) ?></span>
            <button class="flash-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
    <?php elseif ($msg = Session::flash('warning')): ?>
        <div class="flash-message flash-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <span><?= htmlspecialchars($msg) ?></span>
            <button class="flash-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
    <?php elseif ($msg = Session::flash('success')): ?>
        <div class="flash-message flash-success">
            <i class="fas fa-check-circle"></i>
            <span><?= htmlspecialchars($msg) ?></span>
            <button class="flash-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
    <?php endif; ?>
</div>