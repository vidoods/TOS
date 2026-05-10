<?php
// views/login.php
?>

<div class="login-box fade-in">
    <h1 style="text-align: center; margin-bottom: 30px; font-size: 24px;"><?= $lang['sign_in_tos'] ?></h1>
    
    <form id="loginForm" onsubmit="handleLoginSubmit(event)">
        <div class="form-group">
            <label for="login-email" class="form-label"><?= $lang['username_or_email'] ?></label>
            <input type="text" id="login-email" name="email" class="input-field" required autofocus placeholder="<?= $lang['enter_username_or_email'] ?>" autocomplete="email">
        </div>
        
        <div class="form-group" style="margin-bottom: 30px;">
            <label for="login-password" class="form-label"><?= $lang['password'] ?></label>
            <input type="password" id="login-password" name="password" class="input-field" required placeholder="<?= $lang['enter_password'] ?>" autocomplete="current-password">
        </div>

        <div class="mb-4 text-end">
            <a href="index.php?view=forgot_password" class="small text-muted text-decoration-none"><?= $lang['forgot_password_q'] ?></a>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 16px; justify-content: center;">
            <?= $lang['sign_in_btn'] ?>
        </button>

        <div class="auth-switch-link">
            <?= $lang['dont_have_account'] ?> <a href="index.php?view=register"><?= $lang['sign_up'] ?></a>
        </div>
    </form>
    
    <div id="login-error" style="color: var(--accent-red); text-align: center; margin-top: 20px; min-height: 20px; font-size: 14px;"></div>
</div>