<?php
// views/login.php
?>

<div class="login-box fade-in">

    <div class="auth-brand">
        <img src="assets/logo.png" alt="TradeOS" class="auth-logo">
        <span class="auth-brand-name">TradeOS</span>
    </div>

    <h1 class="auth-title"><?= $lang['sign_in_tos'] ?></h1>

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
        <!-- Разделитель -->
<div style="display: flex; align-items: center; text-align: center; margin: 24px 0; color: var(--text-secondary, #94a3b8);">
    <div style="flex: 1; border-bottom: 1px solid rgba(255, 255, 255, 0.08);"></div>
    <span style="padding: 0 12px; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px;">or</span>
    <div style="flex: 1; border-bottom: 1px solid rgba(255, 255, 255, 0.08);"></div>
</div>

<!-- Кнопка Google Auth -->
<a href="api/auth_google.php" class="auth-google-btn" style="display: flex; align-items: center; justify-content: center; gap: 12px; width: 100%; padding: 12px 20px; background: rgba(255, 255, 255, 0.04); border: 1.5px solid var(--glass-border, rgba(255,255,255,0.08)); border-radius: var(--border-radius-sm, 12px); color: var(--text-main, #f8fafc); text-decoration: none; font-size: 0.95rem; font-weight: 600; transition: all 0.2s ease;">
    <svg width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.66-5.17 3.66-9.17z"/><path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.13 0-5.78-2.11-6.73-4.96H1.18v3.14C3.15 21.32 7.22 24 12 24z"/><path fill="#FBBC05" d="M5.27 14.24c-.25-.72-.39-1.49-.39-2.24s.14-1.52.39-2.24V6.62H1.18C.43 8.13 0 9.83 0 12s.43 3.87 1.18 5.38l4.09-3.14z"/><path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.22 0 3.15 2.68 1.18 6.62l4.09 3.14c.95-2.85 3.6-4.96 6.73-4.96z"/></svg>
    Войти через Google
</a>
    </form>
</div>