<?php
// views/forgot_password.php
?>

<div class="login-box fade-in">

    <div class="auth-brand">
        <img src="assets/logo.png" alt="TradeOS" class="auth-logo">
        <span class="auth-brand-name">TradeOS</span>
    </div>

    <div class="auth-icon-wrap">
        <i class="fas fa-key auth-page-icon"></i>
    </div>

    <h1 class="auth-title"><?= $lang['reset_password_title'] ?></h1>
    <p class="auth-subtitle"><?= $lang['enter_email_reset'] ?></p>

    <form id="forgot-form" novalidate>
        <div class="auth-field">
            <label class="auth-label" for="forgot-email"><?= $lang['email_address'] ?></label>
            <div class="auth-input-wrap">
                <i class="fas fa-envelope auth-input-icon"></i>
                <input type="email" class="auth-input" id="forgot-email" required
                    placeholder="<?= $lang['email_placeholder'] ?>" autocomplete="email">
            </div>
        </div>

        <div id="forgot-message" class="auth-message" style="display:none;"></div>

        <button type="submit" id="forgot-submit-btn" class="auth-submit-btn">
            <?= $lang['send_reset_link'] ?>
        </button>
    </form>

    <div class="auth-switch-link" style="margin-top: 20px;">
        <a href="index.php?view=login" class="auth-back-link">
            <i class="fas fa-arrow-left"></i> <?= $lang['back_to_login'] ?>
        </a>
    </div>
</div>
