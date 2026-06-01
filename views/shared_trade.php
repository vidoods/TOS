<?php
$token = $_GET['token'] ?? '';
if (!$token) {
    echo "<h1>Сделка не найдена</h1>";
    exit;
}

// Запрашиваем минимальную инфу (чтобы вписать в мета-теги)
$stmt = $pdo->prepare("
    SELECT t.pnl, up.symbol AS pair_symbol 
    FROM trades t
    LEFT JOIN user_pairs up ON t.pair_id = up.id
    WHERE t.share_token = ?
");
$stmt->execute([$token]);
$trade = $stmt->fetch();

if (!$trade) {
    echo "<h1>Сделка не найдена или доступ закрыт</h1>";
    exit;
}

$pnl = floatval($trade['pnl']);
$resultText = ($pnl >= 0 ? 'Прибыль' : 'Убыток') . ' ' . number_format($pnl, 2) . '$';
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domain = $protocol . $_SERVER['HTTP_HOST'];
$imageUrl = $domain . "/api/handlers/generate_card.php?token=" . $token;
$pageUrl = $domain . "/index.php?view=shared_trade&token=" . $token;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сделка <?= htmlspecialchars($trade['pair_symbol']) ?> | wrkspace.pro</title>

    <meta property="og:title" content="Результат сделки: <?= $resultText ?>">
    <meta property="og:description" content="Торговая статистика трейдера на платформе wrkspace.pro. Посмотрите полный отчет!">
    <meta property="og:image" content="<?= $imageUrl ?>">
    <meta property="og:url" content="<?= $pageUrl ?>">
    <meta property="og:type" content="website">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Сделка <?= htmlspecialchars($trade['pair_symbol']) ?>">
    <meta name="twitter:description" content="Результат: <?= $resultText ?>">
    <meta name="twitter:image" content="<?= $imageUrl ?>">

    <style>
        body { background: #0F172A; color: white; font-family: sans-serif; text-align: center; padding: 50px; }
        .card { background: rgba(255,255,255,0.05); padding: 30px; border-radius: 15px; max-width: 600px; margin: 0 auto; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .btn { background: #3b82f6; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; display: inline-block; margin-top: 20px; }
        img { max-width: 100%; border-radius: 10px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="card">
        <img src="<?= $domain ?>/assets/logo.png" alt="wrkspace.pro" style="height: 50px; margin-bottom: 20px;">
        <h2>Сделка <?= htmlspecialchars($trade['pair_symbol']) ?></h2>
        <h1 style="color: <?= $pnl >= 0 ? '#22c55e' : '#ef4444' ?>;"><?= $resultText ?></h1>
        
        <img src="<?= $imageUrl ?>" alt="Trade Card">

        <br>
        <a href="<?= $domain ?>/?view=register" class="btn">Создать свой бесплатный дневник трейдера</a>
    </div>
</body>
</html>