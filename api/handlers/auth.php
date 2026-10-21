<?php
// api/handlers/auth.php — Авторизация, регистрация, сброс пароля

require_once __DIR__ . '/../mailer.php';

function getClientIp(): string
{
    $headers = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_CLIENT_IP',
        'REMOTE_ADDR'
    ];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ipList = explode(',', $_SERVER[$header]);
            $ip = trim($ipList[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

function checkRateLimit(PDO $pdo, string $key, int $maxAttempts = 5, int $windowSec = 900): bool
{
    $ip = getClientIp();
    $fullKey = substr("{$key}_{$ip}", 0, 190);
    $now = time();

    // Периодическая очистка устаревших записей (1% вероятность)
    if (mt_rand(1, 100) === 1) {
        try {
            $pdo->query("DELETE FROM rate_limits WHERE reset_at < UNIX_TIMESTAMP()");
        } catch (Exception $e) {
            // Игнорируем ошибку сборщика мусора
        }
    }

    try {
        $stmt = $pdo->prepare("SELECT attempts, reset_at FROM rate_limits WHERE rate_key = ?");
        $stmt->execute([$fullKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || (int)$row['reset_at'] <= $now) {
            $resetAt = $now + $windowSec;
            $upsert = $pdo->prepare("REPLACE INTO rate_limits (rate_key, attempts, reset_at) VALUES (?, 1, ?)");
            $upsert->execute([$fullKey, $resetAt]);
            return true;
        }

        $attempts = (int)$row['attempts'] + 1;
        $update = $pdo->prepare("UPDATE rate_limits SET attempts = ? WHERE rate_key = ?");
        $update->execute([$attempts, $fullKey]);

        if ($attempts > $maxAttempts) {
            $remaining = max(1, (int)$row['reset_at'] - $now);
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'message' => 'Слишком много попыток. Подождите ' . ceil($remaining / 60) . ' мин.',
            ]);
            return false;
        }

        return true;
    } catch (Exception $e) {
        error_log("[RateLimit Error] " . $e->getMessage());
        return true;
    }
}

function resetRateLimit(PDO $pdo, string $key): void
{
    $ip = getClientIp();
    $fullKey = substr("{$key}_{$ip}", 0, 190);
    try {
        $pdo->prepare("DELETE FROM rate_limits WHERE rate_key = ?")->execute([$fullKey]);
    } catch (Exception $e) {
        error_log("[RateLimit Reset Error] " . $e->getMessage());
    }
}

function validatePasswordStrength(string $password): ?string
{
    if (strlen($password) < 8) return 'Password must be at least 8 characters.';
    if (strlen($password) > 72) return 'Password must not exceed 72 characters.';
    if (!preg_match('/[A-Z]/', $password)) return 'Password must contain at least one uppercase letter.';
    if (!preg_match('/[0-9]/', $password)) return 'Password must contain at least one number.';
    return null;
}

function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(array $data): bool
{
    $token = $data['csrf_token'] ?? '';
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function handleRegister($pdo)
{
    $data     = json_decode(file_get_contents('php://input'), true) ?? [];
    $username = trim($data['username'] ?? '');
    $email    = trim($data['email']    ?? '');
    $password = $data['password']      ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        return;
    }

    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        echo json_encode(['success' => false, 'message' => 'Username: 3–30 chars, letters, digits, underscore only.']);
        return;
    }

    $pwError = validatePasswordStrength($password);
    if ($pwError) {
        echo json_encode(['success' => false, 'message' => $pwError]);
        return;
    }

    if (!checkRateLimit($pdo, 'register', 5, 900)) return;

    // Проверяем существующего пользователя
    $stmt = $pdo->prepare("SELECT id, email, username, is_verified FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        if (strcasecmp($existing['username'], $username) === 0) {
            echo json_encode(['success' => false, 'message' => 'Username already taken.']);
            return;
        }
        if (strcasecmp($existing['email'], $email) === 0) {
            if (!empty($existing['is_verified'])) {
                echo json_encode(['success' => false, 'message' => 'Email already registered. Please sign in.']);
                return;
            } else {
                // Если аккаунт уже создан, но не подтвержден — обновляем пароль и высылаем ссылку повторно
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $existing['id']]);
                sendVerificationLink($pdo, (int)$existing['id'], $email, $username);
                echo json_encode([
                    'success'               => true,
                    'requires_verification' => true,
                    'email'                 => $email,
                    'message'               => 'Account pending verification. A new activation email has been sent.'
                ]);
                return;
            }
        }
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, is_verified) VALUES (?, ?, ?, 0)");

    if ($stmt->execute([$username, $email, $hash])) {
        $userId = (int)$pdo->lastInsertId();
        resetRateLimit($pdo, 'register');

        sendVerificationLink($pdo, $userId, $email, $username);

        echo json_encode([
            'success'               => true,
            'requires_verification' => true,
            'email'                 => $email,
            'message'               => 'Registration successful! Please check your email to activate your account.'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed. Try again.']);
    }
}

function handleLogin($pdo)
{
    $data = !empty($_POST) ? $_POST : (json_decode(file_get_contents('php://input'), true) ?? []);

    $login    = trim($data['email'] ?? $data['username'] ?? '');
    $password = $data['password'] ?? '';

    if (empty($login) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please enter your credentials.']);
        return;
    }

    $cleanLogin = preg_replace('/[^a-z0-9@._-]/', '', strtolower($login));
    $rlKey = 'login_' . $cleanLogin;
    if (!checkRateLimit($pdo, $rlKey, 5, 900)) return;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Валидный bcrypt хэш (cost=12) для защиты от Timing Attacks
    $dummyHash  = '$2y$12$8eit0/aMvil7Ah3c2gI3O.DJUjxDAWQz2mAFKeVQ5TssmKOWbJzX2';
    $dbPassword = ($user && !empty($user['password_hash'])) ? $user['password_hash'] : $dummyHash;

    $isCorrect = password_verify($password, $dbPassword);

    if ($isCorrect && $user) {
        // Проверка верификации email
        if (isset($user['is_verified']) && (int)$user['is_verified'] === 0) {
            echo json_encode([
                'success'    => false,
                'unverified' => true,
                'email'      => $user['email'],
                'message'    => 'Please verify your email address before logging in.',
            ]);
            return;
        }

        // Защита от Session Fixation
        session_regenerate_id(true);

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['user_lang'] = $user['language'] ?? 'en';

        resetRateLimit($pdo, $rlKey);

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
    }
}

function handleForgotPassword($pdo)
{
    $data  = json_decode(file_get_contents('php://input'), true) ?? [];
    $email = trim($data['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email.']);
        return;
    }

    if (!checkRateLimit($pdo, 'forgot_' . md5($email), 3, 300)) return;

    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        // Единый ответ для предотвращения раскрытия наличия email
        echo json_encode(['success' => true, 'message' => 'If this email exists, a reset link has been sent.']);
        return;
    }

    $rawToken    = bin2hex(random_bytes(32));
    $hashedToken = hash('sha256', $rawToken);
    $expires     = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
    $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");

    if (!$stmt->execute([$email, $hashedToken, $expires])) {
        echo json_encode(['success' => false, 'message' => 'Database error. Try again later.']);
        return;
    }

    $baseUrl   = rtrim(defined('APP_URL') && APP_URL ? APP_URL : ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/');
    $resetLink = $baseUrl . '/index.php?view=reset_password&token=' . $rawToken;
    $username  = $user['username'] ?? 'Trader';

    $mailResult = sendMail(
        $email,
        $username,
        'Reset your TradeOS password',
        buildResetPasswordEmail($resetLink, $username)
    );

    if ($mailResult['simulated']) {
        // Безопасность: никогда не отдаем resetLink в ответе клиенту
        error_log("[TradeOS Security] Dev simulation reset link for {$email}: {$resetLink}");
        echo json_encode([
            'success'   => true,
            'simulated' => false,
            'message'   => 'If this email exists, a reset link has been sent.',
        ]);
    } elseif ($mailResult['sent']) {
        echo json_encode(['success' => true, 'simulated' => false, 'message' => 'Reset link sent to ' . $email]);
    } else {
        resetRateLimit($pdo, 'forgot_' . md5($email));
        echo json_encode(['success' => false, 'message' => $mailResult['error'] ?? 'Failed to send email.']);
    }
}

function handleResetPassword($pdo)
{
    $data    = json_decode(file_get_contents('php://input'), true) ?? [];
    $token   = trim($data['token']    ?? '');
    $newPass = $data['password']      ?? '';

    if (empty($token) || empty($newPass)) {
        echo json_encode(['success' => false, 'message' => 'Missing data.']);
        return;
    }

    if (!checkRateLimit($pdo, 'reset_pw', 10, 300)) return;

    $pwError = validatePasswordStrength($newPass);
    if ($pwError) {
        echo json_encode(['success' => false, 'message' => $pwError]);
        return;
    }

    $hashedToken = hash('sha256', $token);
    $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$hashedToken]);
    $resetRequest = $stmt->fetch();

    if (!$resetRequest) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired reset link.']);
        return;
    }

    $hash   = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
    $update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");

    if ($update->execute([$hash, $resetRequest['email']])) {
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$resetRequest['email']]);

        // Завершаем сессию и сбрасываем данные
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update password.']);
    }
}

function handleLogout()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }

    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    echo json_encode(['success' => true]);
}

function sendVerificationLink(PDO $pdo, int $userId, string $email, string $username): bool
{
    $rawToken    = bin2hex(random_bytes(32));
    $hashedToken = hash('sha256', $rawToken);
    $expires     = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $pdo->prepare("DELETE FROM email_verifications WHERE user_id = ? OR email = ?")->execute([$userId, $email]);
    $stmt = $pdo->prepare("INSERT INTO email_verifications (user_id, email, token, expires_at) VALUES (?, ?, ?, ?)");

    if (!$stmt->execute([$userId, $email, $hashedToken, $expires])) {
        return false;
    }

    $baseUrl    = rtrim(defined('APP_URL') && APP_URL ? APP_URL : ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/');
    $verifyLink = $baseUrl . '/index.php?view=verify_email&token=' . $rawToken;

    $mailResult = sendMail(
        $email,
        $username,
        'Verify your TradeOS account',
        buildVerificationEmail($verifyLink, $username)
    );

    if (!empty($mailResult['simulated'])) {
        error_log("[TradeOS Security] Dev simulation verification link for {$email}: {$verifyLink}");
    }
    if (empty($mailResult['sent'])) {
        error_log("[TradeOS Auth] Verification email was not sent for {$email}: " . ($mailResult['error'] ?? 'unknown error'));
    }

    return (bool)($mailResult['sent'] ?? false);
}

function handleVerifyEmail($pdo)
{
    $token = trim($_GET['token'] ?? $_POST['token'] ?? '');
    if (empty($token)) {
        $data  = json_decode(file_get_contents('php://input'), true) ?? [];
        $token = trim($data['token'] ?? '');
    }

    if (empty($token)) {
        echo json_encode(['success' => false, 'message' => 'Verification token is required.']);
        return;
    }

    $hashedToken = hash('sha256', $token);
    $stmt = $pdo->prepare("SELECT user_id, email FROM email_verifications WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$hashedToken]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired verification link.']);
        return;
    }

    $userId = (int)$request['user_id'];
    $stmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
    if ($stmt->execute([$userId])) {
        $pdo->prepare("DELETE FROM email_verifications WHERE user_id = ?")->execute([$userId]);
        echo json_encode(['success' => true, 'message' => 'Email successfully verified! You can now log in.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error while verifying email.']);
    }
}

function handleResendVerification($pdo)
{
    $data  = json_decode(file_get_contents('php://input'), true) ?? [];
    $email = trim($data['email'] ?? $_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
        return;
    }

    if (!checkRateLimit($pdo, 'resend_v_' . md5($email), 3, 300)) return;

    $stmt = $pdo->prepare("SELECT id, username, is_verified FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && empty($user['is_verified'])) {
        sendVerificationLink($pdo, (int)$user['id'], $email, $user['username']);
    }

    echo json_encode([
        'success' => true,
        'message' => 'If an unverified account with this email exists, a new activation link has been sent.'
    ]);
}

function handleChangePassword($pdo)
{
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authorization required.']);
        return;
    }

    $data = !empty($_POST) ? $_POST : (json_decode(file_get_contents('php://input'), true) ?? []);
    $currentPassword = $data['current_password'] ?? '';
    $newPassword     = $data['new_password'] ?? '';
    $confirmPassword = $data['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        echo json_encode(['success' => false, 'message' => 'All password fields are required.']);
        return;
    }

    if ($newPassword !== $confirmPassword) {
        echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
        return;
    }

    $pwError = validatePasswordStrength($newPassword);
    if ($pwError) {
        echo json_encode(['success' => false, 'message' => $pwError]);
        return;
    }

    if (!checkRateLimit($pdo, 'change_pw_' . $userId, 5, 300)) return;

    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Incorrect current password.']);
        return;
    }

    if (password_verify($newPassword, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'New password must be different from current password.']);
        return;
    }

    $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    $update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    if ($update->execute([$newHash, $userId])) {
        resetRateLimit($pdo, 'change_pw_' . $userId);
        echo json_encode(['success' => true, 'message' => 'Password changed successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error updating password.']);
    }
}

function handleChangeEmail($pdo)
{
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authorization required.']);
        return;
    }

    $data = !empty($_POST) ? $_POST : (json_decode(file_get_contents('php://input'), true) ?? []);
    $newEmail = trim($data['new_email'] ?? '');
    $password = $data['password'] ?? '';

    if (empty($newEmail) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'New email and current password are required.']);
        return;
    }

    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        return;
    }

    if (!checkRateLimit($pdo, 'change_email_' . $userId, 5, 300)) return;

    $stmt = $pdo->prepare("SELECT email, password_hash FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Incorrect current password.']);
        return;
    }

    if (strcasecmp($user['email'], $newEmail) === 0) {
        echo json_encode(['success' => false, 'message' => 'New email must be different from current email.']);
        return;
    }

    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $checkStmt->execute([$newEmail, $userId]);
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'This email is already in use by another account.']);
        return;
    }

    $update = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
    if ($update->execute([$newEmail, $userId])) {
        resetRateLimit($pdo, 'change_email_' . $userId);
        echo json_encode([
            'success'   => true,
            'message'   => 'Email changed successfully!',
            'new_email' => $newEmail
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error updating email.']);
    }
}
?>