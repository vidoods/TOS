<?php
// views/register.php
// Страница регистрации
?>

<div class="login-box fade-in">

    <div class="auth-brand">
        <img src="assets/logo.png" alt="TradeOS" class="auth-logo">
        <span class="auth-brand-name">TradeOS</span>
    </div>

    <h1 class="auth-title"><?= $lang['sign_up'] ?></h1>
    <p class="auth-subtitle"><?= $lang['create_your_free_trading_journal'] ?></p>

    <form id="registerForm" onsubmit="handleRegisterSubmit(event)" novalidate>

        <div class="auth-field">
            <label for="reg-username" class="auth-label"><?= $lang['create_login'] ?></label>
            <div class="auth-input-wrap">
                <i class="fas fa-at auth-input-icon"></i>
                <input type="text" id="reg-username" name="username" class="auth-input" required autofocus
                    placeholder="<?= $lang['login_placeholder'] ?>" autocomplete="username" minlength="3" maxlength="30" pattern="[a-zA-Z0-9_]+">
            </div>
        </div>

        <div class="auth-field">
            <label for="reg-email" class="auth-label"><?= $lang['email_address'] ?></label>
            <div class="auth-input-wrap">
                <i class="fas fa-envelope auth-input-icon"></i>
                <input type="email" id="reg-email" name="email" class="auth-input" required
                    placeholder="<?= $lang['email_placeholder'] ?>" autocomplete="email">
            </div>
        </div>

        <div class="auth-field">
            <label for="reg-password" class="auth-label"><?= $lang['create_password'] ?></label>
            <div class="auth-input-wrap">
                <i class="fas fa-lock auth-input-icon"></i>
                <input type="password" id="reg-password" name="password" class="auth-input" required
                    placeholder="<?= $lang['password'] ?>" autocomplete="new-password" minlength="8">
                <button type="button" class="auth-eye-btn" data-toggle-password="reg-password" aria-label="Toggle password">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <div class="password-strength-wrap">
                <div class="password-strength-track">
                    <div class="password-strength-bar" id="reg-strength-bar"></div>
                </div>
                <span class="strength-label"></span>
            </div>
        </div>

        <div class="auth-field">
            <label for="reg-password-confirm" class="auth-label"><?= $lang['repeat_password'] ?></label>
            <div class="auth-input-wrap">
                <i class="fas fa-shield-alt auth-input-icon"></i>
                <input type="password" id="reg-password-confirm" name="password_confirm" class="auth-input" required
                    placeholder="<?= $lang['password'] ?>" autocomplete="new-password">
                <button type="button" class="auth-eye-btn" data-toggle-password="reg-password-confirm" aria-label="Toggle password">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>

        <div id="register-error" class="auth-message" style="display:none;"></div>

        <button type="submit" id="register-submit-btn" class="auth-submit-btn">
            <?= $lang['sign_up'] ?>
        </button>

        <div class="auth-switch-link">
            <?= $lang['have_account_already'] ?> <a href="index.php?view=login"><?= $lang['sign_in_btn'] ?></a>
        </div>
    </form>
</div>
