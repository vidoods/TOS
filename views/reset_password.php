<?php
// Получаем токен из URL
$token = $_GET['token'] ?? '';
if (!$token) {
    die('<div class="text-white text-center mt-5">' . ($lang['invalid_token'] ?? 'Invalid token.') . ' <a href="index.php">' . ($lang['go_home'] ?? 'Go Home') . '</a></div>');
}
?>
<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="glass-panel p-5" style="max-width: 400px; width: 100%;">
        <div class="text-center mb-4">
            <h3 class="fw-bold text-white"><?= $lang['new_password'] ?></h3>
        </div>
        
        <form id="reset-form">
            <input type="hidden" id="reset-token" value="<?php echo htmlspecialchars($token); ?>">
            
            <div class="mb-4">
                <label class="form-label text-white-50"><?= $lang['enter_new_password'] ?></label>
                <input type="password" class="form-control bg-dark text-white border-secondary" id="reset-password" required minlength="6">
            </div>
            <button type="submit" class="btn btn-success w-100 py-2"><?= $lang['change_password'] ?></button>
        </form>
    </div>
</div>