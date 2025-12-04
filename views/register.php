<?php
// views/register.php
// Страница регистрации
?>

<div class="login-box fade-in">
    <h1 style="text-align: center; margin-bottom: 30px; font-size: 24px;">Регистрация</h1>
    
    <form id="registerForm" onsubmit="handleRegisterSubmit(event)">
        <div class="form-group">
            <label for="reg-username" class="form-label">Логин</label>
            <input type="text" id="reg-username" name="username" class="input-field" required autofocus placeholder="Придумайте логин">
        </div>
        
        <div class="form-group">
            <label for="reg-password" class="form-label">Пароль</label>
            <input type="password" id="reg-password" name="password" class="input-field" required placeholder="Придумайте пароль">
        </div>

        <div class="form-group" style="margin-bottom: 30px;">
            <label for="reg-password-confirm" class="form-label">Повторите пароль</label>
            <input type="password" id="reg-password-confirm" name="password_confirm" class="input-field" required placeholder="Повторите пароль">
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 16px; justify-content: center;">
            Зарегистрироваться
        </button>

        <div class="auth-switch-link">
            Уже есть аккаунт? <a href="index.php?view=login">Войти</a>
        </div>
    </form>
    
    <div id="register-error" style="color: var(--accent-red); text-align: center; margin-top: 20px; min-height: 20px; font-size: 14px;"></div>
</div>