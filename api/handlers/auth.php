<?php
// api/handlers/auth.php — Авторизация, регистрация, сброс пароля
// Оптимизирован: rate limiting, валидация пароля, email via PHPMailer, no debug_link

require_once __DIR__ . '/../mailer.php';

// -------------------------------------------------------
// Rate Limiting — защита от брутфорса
// Хранит количество попыток в сессии, сбрасывает через 15 мин
// -------------------------------------------------------
function checkRateLimit(string $key, int $maxAttempts = 5, int $windowSec = 900): bool
{
    $sessionKey  = "rl_{$key}";
    $tsKey       = "rl_ts_{$key}";
    $now         = time();

    if (!isset($_SESSION[$tsKey]) || ($now - $_SESSION[$tsKey]) > $windowSec) {
        $_SESSION[$sessionKey] = 0;
        $_SESSION[$tsKey]      = $now;
    }

    $_SESSION[$sessionKey]++;

    if ($_SESSION[$sessionKey] > $maxAttempts) {
        $remaining = $windowSec - ($now - $_SESSION[$tsKey]);
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Too many attempts. Please wait ' . ceil($remaining / 60) . ' min.',
        ]);
        return false;
    }

    return true;
}

function resetRateLimit(string $key): void
{
    unset($_SESSION["rl_{$key}"], $_SESSION["rl_ts_{$key}"]);
}

// -------------------------------------------------------
// Валидация надёжности пароля
// -------------------------------------------------------
function validatePasswordStrength(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return 'Password must contain at least one uppercase letter.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        return 'Password must contain at least one number.';
    }
    return null;
}

// -------------------------------------------------------
// CSRF — генерация и проверка токена
// -------------------------------------------------------
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

// -------------------------------------------------------
// HANDLERS
// -------------------------------------------------------

function handleRegister($pdo)
{
    $data     = json_decode(file_get_contents('php://input'), true) ?? [];
    $username = trim($data['username'] ?? '');
    $email    = trim($data['email']    ?? '');
    $password = $data['password']      ?? '';

    // Базовые проверки
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

    // Валидация пароля
    $pwError = validatePasswordStrength($password);
    if ($pwError) {
        echo json_encode(['success' => false, 'message' => $pwError]);
        return;
    }

    // Rate limiting
    if (!checkRateLimit('register', 5, 900)) return;

    // Проверка дублей
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email or username already taken.']);
        return;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");

    if ($stmt->execute([$username, $email, $hash])) {
        resetRateLimit('register');
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed. Try again.']);
    }
}

function handleLogin($pdo)
{
    if (!empty($_POST)) {
        $data = $_POST;
    } else {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
    }

    $login    = trim($data['email'] ?? $data['username'] ?? '');
    $password = $data['password'] ?? '';

    if (empty($login) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please enter your credentials.']);
        return;
    }

    // Rate limiting (по логину)
    $rlKey = 'login_' . preg_replace('/[^a-z0-9@._-]/', '', strtolower($login));
    if (!checkRateLimit($rlKey, 5, 900)) return;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Timing-safe: всегда проверяем хэш, даже если пользователь не найден
    $dummyHash   = '$2y$12$invaliddummyhashtopreventtimingattacks.aaaaaaaaaa';
    $dbPassword  = $user ? (!empty($user['password_hash']) ? $user['password_hash'] : ($user['password'] ?? $dummyHash)) : $dummyHash;

    $isCorrect      = false;
    $needsMigration = false;

    if (password_verify($password, $dbPassword)) {
        $isCorrect = (bool)$user;
    } elseif ($user && $password === $dbPassword) {
        $isCorrect      = true;
        $needsMigration = true;
    }

    if ($isCorrect && $user) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['user_lang'] = $user['language'] ?? 'en';

        resetRateLimit($rlKey);

        if ($needsMigration) {
            try {
                $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $user['id']]);
            } catch (Exception $e) { /* ignore */ }
        }

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

    // Rate limiting
    if (!checkRateLimit('forgot_' . md5($email), 3, 900)) return;

    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Не раскрываем, существует ли email (anti-enumeration)
    if (!$user) {
        echo json_encode(['success' => true, 'message' => 'If this email exists, a reset link has been sent.']);
        return;
    }

    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
    $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");

    if (!$stmt->execute([$email, $token, $expires])) {
        echo json_encode(['success' => false, 'message' => 'Database error. Try again later.']);
        return;
    }

    $baseUrl   = rtrim(APP_URL ?: (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/');
    $resetLink = $baseUrl . '/index.php?view=reset_password&token=' . $token;
    $username  = $user['username'] ?? 'Trader';

    $mailResult = sendMail(
        $email,
        $username,
        'Reset your TradeOS password',
        buildResetPasswordEmail($resetLink, $username)
    );

    if ($mailResult['simulated']) {
        // Только в dev-режиме (SMTP не настроен) — вернём ссылку для отладки
        echo json_encode([
            'success'    => true,
            'simulated'  => true,
            'debug_link' => $resetLink,
            'message'    => 'SMTP not configured. Debug link generated.',
        ]);
    } elseif ($mailResult['sent']) {
        echo json_encode(['success' => true, 'message' => 'Reset link sent to ' . $email]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send email. Try again later.']);
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

    $pwError = validatePasswordStrength($newPass);
    if ($pwError) {
        echo json_encode(['success' => false, 'message' => $pwError]);
        return;
    }

    $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $resetRequest = $stmt->fetch();

    if (!$resetRequest) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired reset link.']);
        return;
    }

    $hash   = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
    $update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");

    if ($update->execute([$hash, $resetRequest['email']])) {
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$resetRequest['email']]);
        // Инвалидируем сессию если пользователь был залогинен
        unset($_SESSION['user_id']);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update password.']);
    }
}

function handleLogout()
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    session_unset();
    session_destroy();
    echo json_encode(['success' => true]);
}
?>