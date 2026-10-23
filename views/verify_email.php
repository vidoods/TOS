<?php
// views/verify_email.php — Email verification page

$token = trim($_GET['token'] ?? '');
$isVerified = false;
$errorMessage = '';

if (!empty($token)) {
    $hashedToken = hash('sha256', $token);
    $stmt = $pdo->prepare("SELECT user_id, email FROM email_verifications WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$hashedToken]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($request) {
        $userId = (int)$request['user_id'];
        $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ?")->execute([$userId]);
        $pdo->prepare("DELETE FROM email_verifications WHERE user_id = ?")->execute([$userId]);
        $isVerified = true;
    } else {
        $errorMessage = 'The link is invalid or has expired.';
    }
} else {
    $errorMessage = 'Verification token is missing.';
}
?>

<div class="login-box fade-in" style="padding: 48px 36px; text-align: center;">

    <div class="auth-brand" style="margin-bottom: 28px; justify-content: center;">
        <img src="assets/logo.png" alt="<?= $lang['brand_name'] ?? 'TradeOS' ?>" class="auth-logo">
        <span class="auth-brand-name"><?= $lang['brand_name'] ?? 'TradeOS' ?></span>
    </div>

    <?php if ($isVerified): ?>
        <div style="font-size: 3.5rem; color: #10b981; margin-bottom: 20px;">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1 class="auth-title" style="margin-bottom: 12px; font-size: 1.6rem;">
            Email Verified!
        </h1>
        <p style="color: var(--text-secondary, #94a3b8); font-size: 0.95rem; line-height: 1.6; margin-bottom: 28px;">
            Your account has been successfully activated. You can now log in.
        </p>
        <a href="index.php?view=login" class="auth-submit-btn" style="display: block; text-decoration: none; padding: 14px 20px; font-weight: 600; text-align: center;">
            Sign in to your account
        </a>
    <?php else: ?>
        <div style="font-size: 3.5rem; color: #ef4444; margin-bottom: 20px;">
            <i class="fas fa-times-circle"></i>
        </div>
        <h1 class="auth-title" style="margin-bottom: 12px; font-size: 1.6rem;">
            Verification Failed
        </h1>
        <p style="color: var(--text-secondary, #94a3b8); font-size: 0.95rem; line-height: 1.6; margin-bottom: 28px;">
            <?= htmlspecialchars($errorMessage) ?>
        </p>

        <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 12px; padding: 20px; margin-bottom: 24px; text-align: left;">
            <label class="auth-label" for="resend-email-input" style="font-size: 0.85rem; margin-bottom: 8px; display: block;">
                Resend verification link
            </label>
            <div class="auth-input-wrap" style="margin-bottom: 12px;">
                <i class="fas fa-envelope auth-input-icon"></i>
                <input type="email" id="resend-email-input" class="auth-input" placeholder="name@example.com">
            </div>
            <button type="button" id="resend-verify-btn" onclick="resendVerificationEmail()" class="auth-submit-btn" style="padding: 10px; font-size: 0.9rem;">
                Resend Link
            </button>
            <div id="resend-message" class="auth-message" style="display: none; margin-top: 10px;"></div>
        </div>

        <div class="auth-switch-link" style="margin-top: 16px;">
            <a href="index.php?view=login" class="auth-back-link">
                <i class="fas fa-arrow-left"></i> Back to login
            </a>
        </div>
    <?php endif; ?>

</div>
