<?php
// views/register.php
// Страница регистрации
?>

<div class="login-box fade-in">
    <h1 style="text-align: center; margin-bottom: 30px; font-size: 24px;"><?= $lang['sign_up'] ?></h1>
    
    <form id="registerForm" onsubmit="handleRegisterSubmit(event)">
        <div class="form-group">
            <label for="reg-username" class="form-label"><?= $lang['create_login'] ?></label>
            <input type="text" id="reg-username" name="username" class="input-field" required autofocus placeholder="<?= $lang['login_placeholder'] ?>">
        </div>

        <div class="form-group">
                <label for="reg-email" class="form-label"><?= $lang['email_address'] ?></label>
                <input type="email" id="reg-email" name="email" class="input-field" required placeholder="<?= $lang['email_placeholder'] ?>">
            </div>
        
        <div class="form-group">
            <label for="reg-password" class="form-label"><?= $lang['create_password'] ?></label>
            <input type="password" id="reg-password" name="password" class="input-field" required placeholder="<?= $lang['password'] ?>">
        </div>

        <div class="form-group" style="margin-bottom: 30px;">
            <label for="reg-password-confirm" class="form-label"><?= $lang['repeat_password'] ?></label>
            <input type="password" id="reg-password-confirm" name="password_confirm" class="input-field" required placeholder="<?= $lang['password'] ?>">
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 16px; justify-content: center;">
            <?= $lang['sign_up'] ?>
        </button>

        <div class="auth-switch-link">
            <?= $lang['have_account_already'] ?> <a href="index.php?view=login"><?= $lang['sign_in_btn'] ?></a>
        </div>
    </form>
    
    <div id="register-error" style="color: var(--accent-red); text-align: center; margin-top: 20px; min-height: 20px; font-size: 14px;"></div>
</div>