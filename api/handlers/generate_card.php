<?php
// Возвращаем настройки: отключаем вывод ошибок, чтобы не ломать картинку
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../db.php';

$token = $_GET['token'] ?? '';
if (!$token) die('Token required');

// УБРАЛИ u.name из запроса, оставили только u.username
$stmt = $pdo->prepare("
    SELECT t.*, up.symbol AS pair_symbol, u.username, u.currency
    FROM trades t
    LEFT JOIN user_pairs up ON t.pair_id = up.id
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.share_token = ?
");
$stmt->execute([$token]);
$trade = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trade) die('Trade not found');

// Имя пользователя (сначала ищем username, если нет - name, иначе стандартное)
$userName = '';
if (!empty($trade['username'])) {
    $userName = '@' . $trade['username'];
} elseif (!empty($trade['name'])) {
    $userName = '@' . $trade['name'];
} else {
    $userName = '@trader';
}

// --- БРОНЕБОЙНАЯ ПОДГОТОВКА ДАННЫХ ---
$pnl = isset($trade['pnl']) ? floatval($trade['pnl']) : 0.0;
$ticker = !empty($trade['pair_symbol']) ? (string)$trade['pair_symbol'] : 'UNKNOWN';
$direction = !empty($trade['direction']) ? strtoupper((string)$trade['direction']) : 'TRADE';
$isProfit = $pnl >= 0;

// Подтягиваем валюту (если в БД пусто - ставим USD по умолчанию)
$currency = !empty($trade['currency']) ? strtoupper((string)$trade['currency']) : 'USD';

// Формируем текст PnL, подставляя динамическую валюту
$pnlText = ($isProfit ? '+' : '') . number_format($pnl, 2) . ' ' . $currency;

// Имя пользователя (только username)
$userName = !empty($trade['username']) ? '@' . $trade['username'] : '@trader';

// Безопасная проверка RR
if (isset($trade['rr_achieved']) && is_numeric($trade['rr_achieved'])) {
    $rrText = 'RR: ' . number_format((float)$trade['rr_achieved'], 2);
} else {
    $rrText = 'RR: -';
}

// ИСПРАВЛЕНИЕ: Используем entry_date вместо created_at
if (!empty($trade['exit_date'])) {
    $dateText = "Closed: " . date('d M Y', strtotime($trade['exit_date']));
} elseif (!empty($trade['entry_date'])) {
    $dateText = "Opened: " . date('d M Y', strtotime($trade['entry_date']));
} else {
    $dateText = "Status: Active";
}

// Определяем домен (чтобы работало и на tos.test и на wrkspace.pro)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domain = $protocol . $_SERVER['HTTP_HOST'];
$publicUrl = $domain . "/index.php?view=shared_trade&token=" . $token;

// --- СОЗДАЕМ ХОЛСТ 1200x630 ---
$width = 1200;
$height = 630;
$image = imagecreatetruecolor($width, $height);

// Цвета
$bg_color = imagecolorallocate($image, 15, 23, 42);       // Темный фон (Slate 900)
$green_color = imagecolorallocate($image, 34, 197, 94);   // Прибыль
$red_color = imagecolorallocate($image, 239, 68, 68);     // Убыток
$white_color = imagecolorallocate($image, 255, 255, 255); // Белый
$gray_color = imagecolorallocate($image, 148, 163, 184);  // Серый

imagefill($image, 0, 0, $bg_color);

// Цвет акцента в зависимости от результата
$accent_color = $isProfit ? $green_color : $red_color;

imagerectangle($image, 10, 10, $width - 10, $height - 10, $accent_color);
imagerectangle($image, 11, 11, $width - 11, $height - 11, $accent_color);

$fontRegular = __DIR__ . '/../../assets/fonts/Montserrat-Regular.ttf';

// Проверяем, существует ли файл шрифта
if (!file_exists($fontRegular)) {
    die("Font file not found: " . $fontRegular);
}

imagettftext($image, 60, 0, 80, 150, $white_color, $fontRegular, $ticker);
imagettftext($image, 30, 0, 80, 220, $accent_color, $fontRegular, $direction);

// --- НОВОЕ: Имя пользователя (справа сверху) ---
// Вычисляем ширину текста, чтобы выровнять его ровно по правому краю
$bboxUser = imagettfbbox(30, 0, $fontRegular, $userName);
$userTextWidth = $bboxUser[2] - $bboxUser[0];
$userX = $width - $userTextWidth - 80; // 80 - это отступ справа (такой же, как слева у тикера)
imagettftext($image, 30, 0, $userX, 150, $gray_color, $fontRegular, $userName);
// -----------------------------------------------

// PnL по центру
$bbox = imagettfbbox(100, 0, $fontRegular, $pnlText);
$textWidth = $bbox[2] - $bbox[0];
$x = ($width - $textWidth) / 2;
imagettftext($image, 100, 0, $x, 380, $accent_color, $fontRegular, $pnlText);

// RR и дата
imagettftext($image, 30, 0, 80, 480, $gray_color, $fontRegular, $rrText);
imagettftext($image, 30, 0, 80, 530, $gray_color, $fontRegular, $dateText);

// Брендинг
imagettftext($image, 24, 0, 80, 590, $white_color, $fontRegular, "Verified via wrkspace.pro");

// Генерируем QR-код (подавляем ошибки через @)
$qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&format=png&margin=0&data=" . urlencode($publicUrl);
$qr_image = @imagecreatefrompng($qr_api_url);
if ($qr_image) {
    imagecopy($image, $qr_image, $width - 250, $height - 250, 0, 0, 200, 200);
    imagedestroy($qr_image);
}

// Вывод картинки
ob_clean(); // Очищаем буфер от случайных пробелов или BOM
header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);
?>