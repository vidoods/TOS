<?php
// views/reset_password.php
$token = $_GET['token'] ?? '';
if (!$token) {
    die('<div class="login-box fade-in text-center" style="padding:60px 40px;"><i class="fas fa-times-circle" style="font-size:3rem;color:var(--accent-red);margin-bottom:20px;display:block;"></i><h2 style="color:var(--text-main);">Invalid Token</h2><p style="color:var(--text-secondary);">' . ($lang['invalid_token'] ?? 'This link is invalid or expired.') . '</p><a href="index.php?view=forgot_password" class="auth-submit-btn" style="display:inline-block;margin-top:20px;text-decoration:none;">' . ($lang['send_reset_link'] ?? 'Request new link') . '</a></div>');
}
?>

<div class="login-box fade-in">

    <div class="auth-brand">
        <img src="assets/logo.png" alt="TradeOS" class="auth-logo">
        <span class="auth-brand-name">TradeOS</span>
    </div>

    <div class="auth-icon-wrap">
        <i class="fas fa-lock-open auth-page-icon"></i>
    </div>

    <h1 class="auth-title"><?= $lang['new_password'] ?></h1>
    <p class="auth-subtitle"><?= $lang['enter_new_password'] ?></p>

    <form id="reset-form" novalidate>
        <input type="hidden" id="reset-token" value="<?= htmlspecialchars($token) ?>">

        <div class="auth-field">
            <label class="auth-label" for="reset-password"><?= $lang['enter_new_password'] ?></label>
            <div class="auth-input-wrap">
                <i class="fas fa-lock auth-input-icon"></i>
                <input type="password" class="auth-input" id="reset-password" required minlength="8"
                    placeholder="<?= $lang['password'] ?>" autocomplete="new-password">
                <button type="button" class="auth-eye-btn" data-toggle-password="reset-password" aria-label="Toggle password">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <div class="password-strength-wrap">
                <div class="password-strength-track">
                    <div class="password-strength-bar" id="reset-strength-bar"></div>
                </div>
                <span class="strength-label"></span>
            </div>
        </div>

        <div class="auth-field">
            <label class="auth-label" for="reset-password-confirm"><?= $lang['repeat_password'] ?></label>
            <div class="auth-input-wrap">
                <i class="fas fa-shield-alt auth-input-icon"></i>
                <input type="password" class="auth-input" id="reset-password-confirm" required minlength="8"
                    placeholder="<?= $lang['password'] ?>" autocomplete="new-password">
                <button type="button" class="auth-eye-btn" data-toggle-password="reset-password-confirm" aria-label="Toggle password">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>

        <div id="reset-message" class="auth-message" style="display:none;"></div>

        <button type="submit" id="reset-submit-btn" class="auth-submit-btn">
            <?= $lang['change_password'] ?>
        </button>
    </form>
</div>
