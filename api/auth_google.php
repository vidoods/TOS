<?php
// api/auth_google.php — Обработка авторизации через Google OAuth 2.0
session_start();
require_once __DIR__ . '/db.php'; // Подключение к базе данных (твое текущее)

// ============================================================================
// НАСТРОЙКИ GOOGLE OAUTH
// Вставь сюда данные, полученные в Google Cloud Console
// ============================================================================
define('GOOGLE_CLIENT_ID',     getenv('GOOGLE_CLIENT_ID')     ?: '');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');
define('GOOGLE_REDIRECT_URI',  getenv('GOOGLE_REDIRECT_URI')  ?: 'https://wrkspace.pro/api/auth_google.php');

// Шаг 1: Если Google еще не прислал код авторизации — перенаправляем пользователя на страницу входа Google
if (!isset($_GET['code'])) {
    $authParams = [
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => 'email profile',
        'access_type'   => 'online'
    ];
    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($authParams));
    exit;
}

// Шаг 2: Пользователь разрешил доступ, получаем временный 'code' и меняем его на Access Token
$code = $_GET['code'];
$tokenUrl = 'https://oauth2.googleapis.com/token';
$postData = [
    'code'          => $code,
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code'
];

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
$response = curl_exec($ch);
curl_close($ch);

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
curl_close($ch);

$googleUser = json_decode($userResponse, true);

if (!isset($googleUser['email'])) {
    die('Ошибка авторизации через Google: не удалось получить email пользователя.');
}

$email     = trim($googleUser['email']);
$name      = trim($googleUser['name'] ?? explode('@', $email)[0]);
$avatarUrl = $googleUser['picture'] ?? null;

// Шаг 4: Интеграция с базой данных TradeOS (Авторизация или Регистрация)
try {
    // Проверяем, существует ли пользователь в базе по email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Если пользователя нет — автоматически регистрируем его
        // Генерируем случайный надежный хэш пароля, так как вход идет через Google
        $randomPasswordHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
        
        $insert = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        $insert->execute([$name, $email, $randomPasswordHash]);
        
        $userId = $pdo->lastInsertId();
        $username = $name;
    } else {
        // Пользователь найден
        $userId = $user['id'];
        $username = $user['username'];
    }

    // Шаг 5: Инициализация сессии TradeOS (точно так же, как в обычном логине)
    $_SESSION['user_id']   = $userId;
    $_SESSION['username']  = $username;
    $_SESSION['user_lang'] = $user['language'] ?? 'en';

    // Перенаправляем в защищенную зону платформы (Дашборд)
    header('Location: ../index.php?view=dashboard');
    exit;

} catch (Exception $e) {
    error_log("[TradeOS Google Auth] Database Error: " . $e->getMessage());
    die('Произошла ошибка базы данных при обработке входа через Google.');
}