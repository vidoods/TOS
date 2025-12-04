<?php
// views/login.php
// Страница авторизации
?>

<div class="login-box fade-in">
    <h1 style="text-align: center; margin-bottom: 30px; font-size: 24px;">Вход в TOS</h1>
    
    <form id="loginForm" onsubmit="handleLoginSubmit(event)">
        <div class="form-group">
            <label for="login-username" class="form-label">Логин</label>
            <input type="text" id="login-username" name="username" class="input-field" required autofocus placeholder="Введите логин">
        </div>
        
        <div class="form-group" style="margin-bottom: 30px;">
            <label for="login-password" class="form-label">Пароль</label>
            <input type="password" id="login-password" name="password" class="input-field" required placeholder="Введите пароль">
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 16px; justify-content: center;">
            Войти
        </button>

        <div class="auth-switch-link">
            Нет аккаунта? <a href="index.php?view=register">Зарегистрироваться</a>
        </div>
    </form>
    
    <div id="login-error" style="color: var(--accent-red); text-align: center; margin-top: 20px; min-height: 20px; font-size: 14px;"></div>
</div>