<?php
// api/handlers/dashboard.php — Метрики дашборда и equity curve

function getDashboardMetrics($pdo) {
    try {
        $user_id    = $_SESSION['user_id'];
        $account_id = $_GET['account_id'] ?? null;
        $year       = $_GET['year'] ?? null;
        $month      = $_GET['month'] ?? null;

        $baseWhere  = "WHERE user_id = ?";
        $baseParams = [$user_id];
        if (!empty($account_id)) { $baseWhere .= " AND account_id = ?"; $baseParams[] = $account_id; }

        $periodWhere  = $baseWhere;
        $periodParams = $baseParams;
        if (!empty($year)) {
            $periodWhere    .= " AND YEAR(entry_date) = ?"; $periodParams[] = $year;
            if (!empty($month)) { $periodWhere .= " AND MONTH(entry_date) = ?"; $periodParams[] = $month; }
        }

        $metrics = [];

        $stmtStats = $pdo->prepare("SELECT
            COUNT(*) as total_trades,
            SUM(CASE WHEN status = 'win' THEN 1 ELSE 0 END) as wins,
            SUM(CASE WHEN status = 'loss' THEN 1 ELSE 0 END) as losses,
            SUM(CASE WHEN status = 'breakeven' THEN 1 ELSE 0 END) as breakeven,
            SUM(CASE WHEN status IN ('pending', 'open') OR status = '' OR status IS NULL THEN 1 ELSE 0 END) as pending,
            COALESCE(SUM(pnl), 0) as total_pnl,
            COALESCE(SUM(rr_achieved), 0) as total_rr
        FROM trades $periodWhere");
        $stmtStats->execute($periodParams);
        $stats = $stmtStats->fetch();

        $metrics['total_trades'] = $stats['total_trades'];
        $metrics['wins']         = (int)$stats['wins'];
        $metrics['losses']       = (int)$stats['losses'];
        $metrics['breakeven']    = (int)$stats['breakeven'];
        $metrics['pending']      = (int)$stats['pending'];
        $metrics['total_pnl']    = round((float)$stats['total_pnl'], 2);
        $metrics['total_rr']     = round((float)$stats['total_rr'], 2);

        $completed = $metrics['wins'] + $metrics['losses'] + $metrics['breakeven'];
        $metrics['win_rate']         = $completed > 0 ? round(($metrics['wins'] / $completed) * 100, 1) : 0;
        $metrics['avg_rr_per_trade'] = $completed > 0 ? round($metrics['total_rr'] / $completed, 2) : 0;

        $stmtTime = $pdo->prepare("SELECT AVG(TIMESTAMPDIFF(SECOND, entry_date, exit_date)) FROM trades $periodWhere AND exit_date IS NOT NULL");
        $stmtTime->execute($periodParams);
        $avg_sec = $stmtTime->fetchColumn();
        if ($avg_sec) {
            $d = floor($avg_sec / 86400); $h = floor(($avg_sec % 86400) / 3600); $mn = floor(($avg_sec % 3600) / 60);
            $metrics['avg_time_in_position'] = ($d ? "{$d}d " : "") . ($h ? "{$h}h " : "") . "{$mn}min";
        } else { $metrics['avg_time_in_position'] = "0min"; }

        $stmtMonths = $pdo->prepare("SELECT COUNT(DISTINCT DATE_FORMAT(entry_date, '%Y-%m')) FROM trades $periodWhere AND entry_date IS NOT NULL AND status IN ('win', 'loss')");
        $stmtMonths->execute($periodParams);
        $months_count = $stmtMonths->fetchColumn();
        $metrics['avg_monthly_profit'] = $months_count > 0 ? round($metrics['total_pnl'] / $months_count, 2) : $metrics['total_pnl'];

        // Equity curve
        if ($account_id) {
            $stmtBal = $pdo->prepare("SELECT balance FROM accounts WHERE id = ? AND user_id = ?");
            $stmtBal->execute([$account_id, $user_id]);
        } else {
            $stmtBal = $pdo->prepare("SELECT SUM(balance) FROM accounts WHERE user_id = ?");
            $stmtBal->execute([$user_id]);
        }
        $current_balance_end = (float)$stmtBal->fetchColumn();

        $stmtAllPnl = $pdo->prepare("SELECT COALESCE(SUM(pnl), 0) FROM trades $baseWhere AND status IN ('win', 'loss', 'breakeven', 'pending')");
        $stmtAllPnl->execute($baseParams);
        $total_pnl_all_time = (float)$stmtAllPnl->fetchColumn();
        $initial_deposit    = $current_balance_end - $total_pnl_all_time;
        $start_balance      = $initial_deposit;

        if (!empty($year)) {
            $startDateStr = "$year-01-01";
            if (!empty($month)) $startDateStr = "$year-$month-01";
            $beforeParams   = $baseParams;
            $beforeParams[] = $startDateStr;
            $stmtBefore = $pdo->prepare("SELECT COALESCE(SUM(pnl), 0) FROM trades $baseWhere AND status IN ('win', 'loss', 'breakeven', 'pending') AND entry_date < ?");
            $stmtBefore->execute($beforeParams);
            $start_balance += (float)$stmtBefore->fetchColumn();
        }

        $chartData       = [];
        $running_balance = $start_balance;
        $chartData[]     = ['x' => !empty($year) ? "$year-" . ($month ?? '01') . "-01" : "Start", 'y' => round($running_balance, 2)];

        if (!empty($year) && empty($month)) {
            $sqlChart = "SELECT DATE_FORMAT(entry_date, '%Y-%m') as date_label, SUM(pnl) as pnl FROM trades $periodWhere AND status IN ('win', 'loss', 'breakeven', 'pending') GROUP BY date_label ORDER BY date_label ASC";
        } elseif (!empty($year) && !empty($month)) {
            $sqlChart = "SELECT DATE_FORMAT(entry_date, '%Y-%m-%d') as date_label, SUM(pnl) as pnl FROM trades $periodWhere AND status IN ('win', 'loss', 'breakeven', 'pending') GROUP BY date_label ORDER BY date_label ASC";
        } else {
            $sqlChart = "SELECT DATE_FORMAT(entry_date, '%Y-%m-%d') as date_label, pnl FROM trades $periodWhere AND status IN ('win', 'loss', 'breakeven', 'pending') ORDER BY entry_date ASC, id ASC";
        }
        $stmtChart = $pdo->prepare($sqlChart);
        $stmtChart->execute($periodParams);

        foreach ($stmtChart->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $running_balance += (float)$row['pnl'];
            $chartData[]      = ['x' => $row['date_label'], 'y' => round($running_balance, 2)];
        }
        $metrics['equity_chart'] = $chartData;

        $peak = -999999999; $max_dd_percent = 0; $max_dd_abs = 0;
        foreach ($chartData as $pt) {
            $val = $pt['y'];
            if ($val > $peak) $peak = $val;
            $dd = $peak - $val;
            if ($peak > 0) { $dd_pct = ($dd / $peak) * 100; if ($dd_pct > $max_dd_percent) $max_dd_percent = $dd_pct; }
            if ($dd > $max_dd_abs) $max_dd_abs = $dd;
        }
        $metrics['max_drawdown_pct'] = round($max_dd_percent, 2);
        $metrics['max_drawdown_abs'] = round($max_dd_abs, 2);

        echo json_encode(['success' => true, 'data' => $metrics]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>