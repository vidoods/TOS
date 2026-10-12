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

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email or username already taken.']);
        return;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");

    if ($stmt->execute([$username, $email, $hash])) {
        resetRateLimit($pdo, 'register');
        echo json_encode(['success' => true]);
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
?>