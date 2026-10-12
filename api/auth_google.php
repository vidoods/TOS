<?php
// api/auth_google.php — Обработка авторизации через Google OAuth 2.0

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/db.php';

// ============================================================================
// НАСТРОЙКИ GOOGLE OAUTH (Загружаются из .env)
// ============================================================================
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');
define('GOOGLE_REDIRECT_URI', getenv('GOOGLE_REDIRECT_URI') ?: 'https://wrkspace.pro/api/auth_google.php');

// Шаг 1: Если Google еще не прислал код авторизации — перенаправляем пользователя на страницу входа Google с CSRF state
if (!isset($_GET['code'])) {
    $state = bin2hex(random_bytes(32));
    $_SESSION['oauth_state'] = $state;

    $authParams = [
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'email profile',
        'access_type' => 'online',
        'state' => $state,
    ];
    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($authParams));
    exit;
}

// Защита от OAuth Login CSRF (проверка state)
$state = $_GET['state'] ?? '';
if (empty($state) || empty($_SESSION['oauth_state']) || !hash_equals($_SESSION['oauth_state'], $state)) {
    unset($_SESSION['oauth_state']);
    http_response_code(403);
    die('Ошибка валидации OAuth: неверный или просроченный токен безопасности (CSRF). Попробуйте снова.');
}
unset($_SESSION['oauth_state']);

// Шаг 2: Пользователь разрешил доступ, получаем временный 'code' и меняем его на Access Token
$code = $_GET['code'];
$tokenUrl = 'https://oauth2.googleapis.com/token';
$postData = [
    'code' => $code,
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code'
];

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
$response = curl_exec($ch);

$tokenData = json_decode($response, true);

if (!isset($tokenData['access_token'])) {
    error_log("[TradeOS Google Auth] Ошибка получения токена: " . $response);
    die('Ошибка авторизации через Google: не удалось получить токен доступа.');
}

// Шаг 3: Запрашиваем информацию о пользователе (email, имя, аватар) по Access Token
$userInfoUrl = 'https://www.googleapis.com/oauth2/v3/userinfo';
$ch = curl_init($userInfoUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $tokenData['access_token']]);
$userResponse = curl_exec($ch);

$googleUser = json_decode($userResponse, true);

if (empty($googleUser['email'])) {
    die('Ошибка авторизации через Google: не удалось получить email пользователя.');
}

if (isset($googleUser['email_verified']) && $googleUser['email_verified'] === false) {
    die('Ошибка авторизации: email пользователя не подтвержден сервисом Google.');
}

$email = trim($googleUser['email']);
$rawName = trim($googleUser['name'] ?? explode('@', $email)[0]);
$avatarUrl = $googleUser['picture'] ?? null;

// Шаг 4: Интеграция с базой данных TradeOS (Авторизация или Регистрация)
try {
    // Проверяем, существует ли пользователь в базе по email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Формируем безопасный уникальный username
        $cleanUsername = preg_replace('/[^a-zA-Z0-9_]/', '', $rawName);
        if (strlen($cleanUsername) < 3) {
            $cleanUsername = 'user_' . substr(md5($email), 0, 6);
        }
        $candidate = substr($cleanUsername, 0, 30);

        // Проверяем уникальность username
        $checkUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $checkUser->execute([$candidate]);
        if ($checkUser->fetch()) {
            $candidate = substr($cleanUsername, 0, 24) . '_' . mt_rand(1000, 9999);
        }

        // Генерируем случайный надежный хэш пароля
        $randomPasswordHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT, ['cost' => 12]);

        $insert = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        $insert->execute([$candidate, $email, $randomPasswordHash]);

        $userId = $pdo->lastInsertId();
        $username = $candidate;
        $userLang = 'en';
    } else {
        $userId = $user['id'];
        $username = $user['username'];
        $userLang = $user['language'] ?? 'en';
    }

    // Шаг 5: Инициализация сессии с защитой от Session Fixation
    session_regenerate_id(true);

    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['user_lang'] = $userLang;

    // Перенаправляем в защищенную зону платформы (Дашборд)
    header('Location: ../index.php?view=dashboard');
    exit;

} catch (Exception $e) {
    error_log("[TradeOS Google Auth] Database Error: " . $e->getMessage());
    die('Произошла ошибка базы данных при обработке входа через Google.');
}