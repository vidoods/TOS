<?php
// views/forgot_password.php
?>

<div class="login-box fade-in">
        <div class="text-center mb-4">
            <h1 style="text-align: center; margin-bottom: 30px; font-size: 24px;"><?= $lang['reset_password_title'] ?></h3>
            <p class="text-muted small"><?= $lang['enter_email_reset'] ?></p>
        </div>
        
        <form id="forgot-form">
            <div class="mb-4">
                <label class="form-label text-white-50"><?= $lang['email_address'] ?></label>
                <input type="email" class="form-control bg-dark text-white border-secondary" id="forgot-email" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2"><?= $lang['send_reset_link'] ?></button>
        </form>
        
        <div class="mt-4 text-center">
            <a href="index.php?view=login" class="text-decoration-none text-white-50"><?= $lang['back_to_login'] ?></a>
        </div>
</div>