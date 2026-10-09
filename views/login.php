<?php
// views/login.php
?>

<div class="login-box fade-in">

    <div class="auth-brand">
        <img src="assets/logo.png" alt="TradeOS" class="auth-logo">
        <span class="auth-brand-name">TradeOS</span>
    </div>

    <h1 class="auth-title"><?= $lang['sign_in_tos'] ?></h1>
    <p class="auth-subtitle"><?= $lang['welcome_back'] ?></p>

    <form id="loginForm" onsubmit="handleLoginSubmit(event)" novalidate>

        <div class="auth-field">
            <label for="login-email" class="auth-label"><?= $lang['username_or_email'] ?></label>
            <div class="auth-input-wrap">
                <i class="fas fa-user auth-input-icon"></i>
                <input
                    type="text"
                    id="login-email"
                    name="email"
                    class="auth-input"
                    required
                    autofocus
                    placeholder="<?= $lang['enter_username_or_email'] ?>"
                    autocomplete="username"
                >
            </div>
        </div>

        <div class="auth-field">
            <div class="auth-label-row">
                <label for="login-password" class="auth-label"><?= $lang['password'] ?></label>
                <a href="index.php?view=forgot_password" class="auth-forgot-link"><?= $lang['forgot_password_q'] ?></a>
            </div>
            <div class="auth-input-wrap">
                <i class="fas fa-lock auth-input-icon"></i>
                <input
                    type="password"
                    id="login-password"
                    name="password"
                    class="auth-input"
                    required
                    placeholder="<?= $lang['enter_password'] ?>"
                    autocomplete="current-password"
                >
                <button type="button" class="auth-eye-btn" data-toggle-password="login-password" aria-label="Toggle password">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>

        <div id="login-error" class="auth-message" style="display:none;"></div>

        <button type="submit" id="login-submit-btn" class="auth-submit-btn">
            <?= $lang['sign_in_btn'] ?>
        </button>

        <div class="auth-switch-link">
            <?= $lang['dont_have_account'] ?> <a href="index.php?view=register"><?= $lang['sign_up'] ?></a>
        </div>
    </form>
</div>