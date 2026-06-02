<?php
// views/shared_trade.php
$token = $_GET['token'] ?? '';
if (!$token) {
    echo "<h1 class='text-center'>" . ($lang['trade_not_found'] ?? 'Trade not found') . "</h1>";
    exit;
}

// 1. Расширенный запрос для получения всех данных сделки
$stmt = $pdo->prepare("
    SELECT 
        t.id,
        t.pnl, 
        t.direction,
        t.entry_date,
        t.exit_date,
        t.status,
        t.risk_percent,
        t.rr_achieved,
        t.entry_tf,
        up.symbol AS pair_symbol, 
        rs.name AS style_name,
        rm.name AS model_name,
        p.title AS plan_title
    FROM trades t
    JOIN user_pairs up ON t.pair_id = up.id
    JOIN accounts a ON t.account_id = a.id
    LEFT JOIN plans p ON t.plan_id = p.id
    LEFT JOIN user_styles rs ON t.style_id = rs.id
    LEFT JOIN user_models rm ON t.model_id = rm.id
    WHERE t.share_token = ?
");
$stmt->execute([$token]);
$trade = $stmt->fetch();

if (!$trade) {
    echo "<h1 class='text-center'>" . ($lang['trade_not_found_or_access_denied'] ?? 'Trade not found or access denied') . "</h1>";
    exit;
}

// 2. Загрузка изображений
$stmt_images = $pdo->prepare("SELECT image_url, title FROM trade_analysis_images WHERE trade_id = ? ORDER BY id ASC");
$stmt_images->execute([$trade['id']]);
$trade_images = $stmt_images->fetchAll();

// Подготовка данных для отображения
$pnl = floatval($trade['pnl']);
$statusClass = 'badge-neutral';
if (strtolower($trade['status'] ?? '') === 'win') $statusClass = 'badge-profit';
if (strtolower($trade['status'] ?? '') === 'loss') $statusClass = 'badge-loss';
if (strtolower($trade['status'] ?? '') === 'breakeven') $statusClass = 'badge-blue';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domain = $protocol . $_SERVER['HTTP_HOST'];
$registerUrl = $domain . "/index.php?view=register";

// Форматирование дат
function formatDate($dateStr) {
    if (!$dateStr) return '-';
    $date = new DateTime($dateStr);
    return $date->format('d.m.Y');
}
?>

<div class="fade-in">
    <!-- Top Button -->
    <div class="mb-4 text-center">
        <a href="<?= $registerUrl ?>" class="btn btn-primary w-100" style="max-width: 400px;">
            <i class="fas fa-rocket me-2"></i> <?= $lang['create_your_free_trading_journal'] ?? 'Create your free trading journal' ?>
        </a>
    </div>

    <div class="page-header" style="text-align: center; margin-bottom: 30px;">
        <h1 class="page-title" style="margin-bottom: 10px;">
            <?= htmlspecialchars($trade['pair_symbol'] ?? '') ?> 
        </h1>
    </div>

    <div class="glass-panel" style="padding: 30px; margin-bottom: 40px;">
        <section style="margin-bottom: 40px; padding-bottom: 30px; border-bottom: 1px solid var(--glass-border);">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 25px;">
                <div class="detail-item">
                    <span class="detail-label"><?= $lang['result_pnl'] ?? 'Result (PnL)' ?></span>
                    <span class="info-badge badge-neutral" style="color: <?= $pnl >= 0 ? '#22c55e' : '#ef4444'; ?>">
                        <?= $pnl >= 0 ? '+' : '' ?>
                        <?= number_format($pnl, 2) ?>$
                    </span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><?= $lang['rr'] ?? 'R:R' ?></span>
                    <span class="info-badge badge-neutral"><?= number_format((float)($trade['rr_achieved'] ?? 0), 2) ?>R</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><?= $lang['status'] ?? 'Status' ?></span>
                    <span class="info-badge <?= $statusClass ?>">
                        <?= strtoupper($trade['status'] ?? '') ?>
                    </span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><?= $lang['risk'] ?? 'Risk' ?></span>
                    <span class="info-badge badge-neutral"><?= $trade['risk_percent'] ?? 0 ?>%</span>
                </div>
            </div>
        </section>

        <section>
            <h3 class="section-title"><?= $lang['trade_parameters'] ?? 'Trade parameters' ?></h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px;">
                <div class="detail-item">
                    <span class="detail-label"><?= $lang['direction'] ?? 'Direction' ?></span>
                    <span class="info-badge <?php echo (strtolower($trade['direction'] ?? '') == 'long') ? 'badge-profit' : 'badge-loss'; ?>">
                        <?= strtoupper($trade['direction'] ?? '') ?>
                    </span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><?= $lang['timeframe'] ?? 'Timeframe' ?></span>
                    <span class="info-badge badge-neutral"><?= htmlspecialchars($trade['entry_tf'] ?? '') ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><?= $lang['trading_style'] ?? 'Trading style' ?></span>
                    <span class="info-badge badge-blue"><?= htmlspecialchars($trade['style_name'] ?? '') ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><?= $lang['entry_model'] ?? 'Entry model' ?></span>
                    <span class="info-badge badge-blue"><?= htmlspecialchars($trade['model_name'] ?? '') ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><?= $lang['entry'] ?? 'Entry' ?></span>
                    <span class="info-badge badge-neutral"><?= formatDate($trade['entry_date'] ?? '') ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><?= $lang['exit'] ?? 'Exit' ?></span>
                    <span class="info-badge badge-neutral"><?= formatDate($trade['exit_date'] ?? '') ?></span>
                </div>
            </div>
        </section>
    </div>

    <section>
        <h3 class="section-title"><?= $lang['screenshots'] ?? 'Screenshots' ?></h3>
        <div class="d-flex flex-wrap gap-3">
            <?php if (empty($trade_images)): ?>
                <div class="empty-state-small"><?= $lang['no_screenshots_trade'] ?? 'No screenshots for this trade.' ?></div>
            <?php else: ?>
                <?php foreach ($trade_images as $img): ?>
                    <div class="trade-image-item">
                        <img src="<?= htmlspecialchars($img['image_url'] ?? '') ?>" class="lightbox-trigger" style="border-radius: 8px; max-width: 100%; height: auto;">
                        <?php if (!empty($img['title'])): ?>
                            <div class="text-muted small mt-2"><?= htmlspecialchars($img['title'] ?? '') ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <div class="mb-4 text-center">
        <a href="<?= $registerUrl ?>" class="btn btn-primary w-100" style="max-width: 400px; margin-top: 30px;">
            <i class="fas fa-rocket me-2"></i> <?= $lang['create_your_free_trading_journal'] ?? 'Create your free trading journal' ?>
        </a>
    </div>
</div>