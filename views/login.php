<?php
// views/login.php
?>

<div class="login-box fade-in">
    <h1 style="text-align: center; margin-bottom: 30px; font-size: 24px;">Sign in TOS</h1>
    
    <form id="loginForm" onsubmit="handleLoginSubmit(event)">
        <div class="form-group">
            <label for="login-email" class="form-label">Username or Email</label>
            <input type="text" id="login-email" name="email" class="input-field" required autofocus placeholder="Enter Username or Email" autocomplete="email">
        </div>
        
        <div class="form-group" style="margin-bottom: 30px;">
            <label for="login-password" class="form-label">Password</label>
            <input type="password" id="login-password" name="password" class="input-field" required placeholder="Enter password" autocomplete="current-password">
        </div>

        <div class="mb-4 text-end">
            <a href="index.php?view=forgot_password" class="small text-muted text-decoration-none">Forgot Password?</a>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 16px; justify-content: center;">
            Sign in
        </button>

        <div class="auth-switch-link">
            Don`t have an account yet? <a href="index.php?view=register">Sign up</a>
        </div>
    </form>
    
    <div id="login-error" style="color: var(--accent-red); text-align: center; margin-top: 20px; min-height: 20px; font-size: 14px;"></div>
</div>