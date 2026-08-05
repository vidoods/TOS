<?php
// Возвращаем настройки: отключаем вывод ошибок, чтобы не ломать картинку
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../db.php';

// Подключаем нашу локальную библиотеку для QR-кодов
$qrLibPath = __DIR__ . '/../../assets/phpqrcode/qrlib.php';
$useLocalQr = file_exists($qrLibPath);
if ($useLocalQr) {
    require_once $qrLibPath;
}

$token = $_GET['token'] ?? '';
if (!$token) die('Token required');

// Запрашиваем все необходимые данные, включая валюту пользователя
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

// --- БРОНЕБОЙНАЯ ПОДГОТОВКА ДАННЫХ ---
$pnl = isset($trade['pnl']) ? floatval($trade['pnl']) : 0.0;
$ticker = !empty($trade['pair_symbol']) ? (string)$trade['pair_symbol'] : 'UNKNOWN';
$direction = !empty($trade['direction']) ? strtoupper((string)$trade['direction']) : 'TRADE';
$isProfit = $pnl >= 0;

// Подтягиваем динамическую валюту (по умолчанию USD)
$currency = !empty($trade['currency']) ? strtoupper((string)$trade['currency']) : 'USD';
$pnlText = ($isProfit ? '+' : '') . number_format($pnl, 2) . ' ' . $currency;

// Имя пользователя
$userName = !empty($trade['username']) ? '@' . $trade['username'] : '@trader';

// Безопасная проверка RR
if (isset($trade['rr_achieved']) && is_numeric($trade['rr_achieved'])) {
    $rrText = 'RR: ' . number_format((float)$trade['rr_achieved'], 2);
} else {
    $rrText = 'RR: -';
}

// Дата сделки
if (!empty($trade['exit_date'])) {
    $dateText = "Closed: " . date('d M Y', strtotime($trade['exit_date']));
} elseif (!empty($trade['entry_date'])) {
    $dateText = "Opened: " . date('d M Y', strtotime($trade['entry_date']));
} else {
    $dateText = "Status: Active";
}

// Определяем публичный URL для QR-кода
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domain = $protocol . $_SERVER['HTTP_HOST'];
$publicUrl = $domain . "/index.php?view=shared_trade&token=" . $token;

// --- СОЗДАЕМ ХОЛСТ 1200x630 (Идеально для OG Image) ---
$width = 1200;
$height = 630;
$image = imagecreatetruecolor($width, $height);

// Включаем поддержку альфа-канала для прозрачности
imagealphablending($image, true);
imagesavealpha($image, true);

// Цветовая палитра
$bg_color = imagecolorallocate($image, 15, 23, 42);       // Slate 900
$green_color = imagecolorallocate($image, 34, 197, 94);   // Прибыль
$red_color = imagecolorallocate($image, 239, 68, 68);     // Убыток
$white_color = imagecolorallocate($image, 255, 255, 255); // Белый
$gray_color = imagecolorallocate($image, 148, 163, 184);  // Серый текста

// Заливаем базовым темным цветом
imagefill($image, 0, 0, $bg_color);

// --- ИНТЕГРАЦИЯ ФОНОВОГО ГРАФИКА (УНИВЕРСАЛЬНЫЙ ЗАГРУЗЧИК) ---
$bgPathJpg = __DIR__ . '/../../assets/share_card_bg.jpg';
$bgPathJpeg = __DIR__ . '/../../assets/share_card_bg.jpeg';
$bgPathPng = __DIR__ . '/../../assets/share_card_bg.png';

$chartImg = false;

// Проверяем наличие файла и используем правильную функцию GD
if (file_exists($bgPathJpg)) {
    $chartImg = @imagecreatefromjpeg($bgPathJpg);
} elseif (file_exists($bgPathJpeg)) {
    $chartImg = @imagecreatefromjpeg($bgPathJpeg);
} elseif (file_exists($bgPathPng)) {
    $chartImg = @imagecreatefrompng($bgPathPng);
}

if ($chartImg) {
    $chartW = imagesx($chartImg);
    $chartH = imagesy($chartImg);
    
    // Масштабируем график на весь холст
    imagecopyresampled($image, $chartImg, 0, 0, 0, 0, $width, $height, $chartW, $chartH);
    
    // Накладываем тонировку "темное стекло", чтобы текст идеально читался
    $overlay = imagecolorallocatealpha($image, 15, 23, 42, 50); 
    imagefilledrectangle($image, 0, 0, $width, $height, $overlay);
    
    imagedestroy($chartImg);
}

// Цвет акцента в зависимости от результата сделки
$accent_color = $isProfit ? $green_color : $red_color;

// Рисуем стильную рамку с отступом
imagerectangle($image, 10, 10, $width - 10, $height - 10, $accent_color);
imagerectangle($image, 11, 11, $width - 11, $height - 11, $accent_color);

// Путь к оригинальному шрифту
$fontRegular = __DIR__ . '/../../assets/fonts/Montserrat-Regular.ttf';
if (!file_exists($fontRegular)) {
    die("Font file not found: " . $fontRegular);
}

// --- ОТРИСОВКА ТЕКСТА ---
// Тикер и направление (Слева сверху)
imagettftext($image, 60, 0, 80, 150, $white_color, $fontRegular, $ticker);
$direction_color = ($direction === 'LONG') ? $green_color : $red_color;
imagettftext($image, 30, 0, 80, 220, $direction_color, $fontRegular, $direction);

// Имя пользователя (Выравнивание по правому краю)
$bboxUser = imagettfbbox(30, 0, $fontRegular, $userName);
$userTextWidth = $bboxUser[2] - $bboxUser[0];
$userX = $width - $userTextWidth - 80;
imagettftext($image, 30, 0, $userX, 150, $gray_color, $fontRegular, $userName);

// PnL строго по центру
$bbox = imagettfbbox(100, 0, $fontRegular, $pnlText);
$textWidth = $bbox[2] - $bbox[0];
$x = ($width - $textWidth) / 2;
imagettftext($image, 100, 0, $x, 380, $accent_color, $fontRegular, $pnlText);

// RR и дата (Слева снизу)
imagettftext($image, 30, 0, 80, 480, $gray_color, $fontRegular, $rrText);
imagettftext($image, 30, 0, 80, 530, $gray_color, $fontRegular, $dateText);

// Брендинг (Слева снизу)
imagettftext($image, 24, 0, 80, 590, $white_color, $fontRegular, "Verified via wrkspace.pro");

// --- ГЕНЕРАЦИЯ И ВСТАВКА ЛОКАЛЬНОГО QR-КОДА ---
if ($useLocalQr) {
    // Создаем временный файл для хранения сгенерированного QR-кода
    $tempQrFile = sys_get_temp_dir() . '/qr_' . md5($publicUrl) . '.png';
    
    // Генерируем QR-код (URL, путь, уровень коррекции ошибок 'L', размер пикселя 10, отступ 0)
    QRcode::png($publicUrl, $tempQrFile, 'L', 10, 0);
    
    $qr_image = @imagecreatefrompng($tempQrFile);
    if ($qr_image) {
        $qr_w = imagesx($qr_image);
        $qr_h = imagesy($qr_image);
        
        // Копируем с масштабированием, чтобы получить ровно 200x200 px
        imagecopyresampled($image, $qr_image, $width - 280, $height - 230, 0, 0, 190, 190, $qr_w, $qr_h);
        imagedestroy($qr_image);
    }
    
    // Удаляем временный файл, чтобы не занимать место на сервере
    if (file_exists($tempQrFile)) {
        unlink($tempQrFile);
    }
}

// --- ФИНАЛЬНЫЙ РЕНДЕР ---
ob_clean(); // Очищаем буфер от случайных пробелов
header('Content-Type: image/png');
header('Cache-Control: no-cache, no-store, must-revalidate'); // Запрещаем браузеру кэшировать
imagepng($image);
imagedestroy($image);
?>