<?php
// api/handlers/mpa.php — Monthly Performance Analysis

function getMPAAnalysis($pdo) {
    try {
        $uid  = $_SESSION['user_id'];
        $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

        $sql  = "SELECT t.*, a.balance as acc_balance
                 FROM trades t
                 LEFT JOIN accounts a ON t.account_id = a.id
                 WHERE t.user_id = ? AND YEAR(t.entry_date) = ?
                 ORDER BY t.entry_date ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$uid, $year]);
        $trades = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $monthsData = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthsData[$m] = [
                'month_num'    => $m,
                'count_total'  => 0, 'count_win' => 0, 'count_loss' => 0,
                'count_be'     => 0, 'count_pending' => 0,
                'pnl_total'    => 0, 'pnl_percent' => 0,
                'rr_total'     => 0, 'winrate' => 0
            ];
        }

        foreach ($trades as $t) {
            if (empty($t['entry_date'])) continue;
            try { $date = new DateTime($t['entry_date']); $m = (int)$date->format('n'); } catch (Exception $e) { continue; }

            $pnl    = (float)$t['pnl'];
            $rr     = (float)$t['rr_achieved'];
            $status = strtolower($t['status'] ?? '');
            $deposit = (float)($t['deposit_start'] ?? 0);
            if ($deposit <= 0) $deposit = (float)($t['acc_balance'] ?? 0);
            if ($deposit > 0) $monthsData[$m]['pnl_percent'] += ($pnl / $deposit) * 100;

            $monthsData[$m]['count_total']++;
            $monthsData[$m]['pnl_total'] += $pnl;
            $monthsData[$m]['rr_total']  += $rr;

            if ($status === 'open' || $status === 'pending' || $status === '') {
                $monthsData[$m]['count_pending']++;
            } elseif (strpos($status, 'win') !== false || ($pnl > 0 && $status !== 'breakeven')) {
                $monthsData[$m]['count_win']++;
            } elseif (strpos($status, 'loss') !== false || $pnl < 0) {
                $monthsData[$m]['count_loss']++;
            } else {
                $monthsData[$m]['count_be']++;
            }
        }

        $quarters = [1 => ['pnl' => 0, 'percent' => 0, 'months' => []], 2 => ['pnl' => 0, 'percent' => 0, 'months' => []], 3 => ['pnl' => 0, 'percent' => 0, 'months' => []], 4 => ['pnl' => 0, 'percent' => 0, 'months' => []]];

        foreach ($monthsData as $m => $data) {
            $closedTrades = $data['count_total'] - $data['count_pending'];
            if ($closedTrades > 0) $monthsData[$m]['winrate'] = round(($data['count_win'] / $closedTrades) * 100);
            $qNum = ceil($m / 3);
            $quarters[$qNum]['months'][]  = $monthsData[$m];
            $quarters[$qNum]['pnl']      += $data['pnl_total'];
            $quarters[$qNum]['percent']  += $data['pnl_percent'];
        }

        if (ob_get_level() > 0) ob_clean();
        echo json_encode(['success' => true, 'data' => $quarters]);
        exit;

    } catch (Exception $e) {
        if (ob_get_level() > 0) ob_clean();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

function getMPAMonthDetails($pdo) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');

    try {
        $uid   = $_SESSION['user_id'];
        $year  = $_GET['year']  ?? date('Y');
        $month = $_GET['month'] ?? date('n');

        $sqlTrades = "SELECT t.*, rp.symbol as pair_name
                      FROM trades t
                      LEFT JOIN ref_pairs rp ON t.pair_id = rp.id
                      WHERE t.user_id = ? AND YEAR(t.entry_date) = ? AND MONTH(t.entry_date) = ?
                      ORDER BY t.entry_date DESC";
        $stmt = $pdo->prepare($sqlTrades);
        $stmt->execute([$uid, $year, $month]);
        $trades = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sqlPlans = "SELECT p.*, rp.symbol as pair_symbol
                     FROM plans p
                     LEFT JOIN ref_pairs rp ON p.pair_id = rp.id
                     WHERE p.user_id = ? AND YEAR(p.date) = ? AND MONTH(p.date) = ?
                     ORDER BY p.date DESC";
        $stmtPlans = $pdo->prepare($sqlPlans);
        $stmtPlans->execute([$uid, $year, $month]);
        $plans = $stmtPlans->fetchAll(PDO::FETCH_ASSOC);

        $stats = ['total' => 0, 'wins' => 0, 'losses' => 0, 'be' => 0, 'pending' => 0, 'pnl' => 0.0, 'rr_sum' => 0.0, 'percent_sum' => 0.0, 'winrate' => 0, 'avg_rr' => 0];
        foreach ($trades as $t) {
            $stats['total']++;
            $pnl = (float)($t['pnl'] ?? 0);
            $stats['pnl']    += $pnl;
            $stats['rr_sum'] += (float)($t['rr_achieved'] ?? 0);
            $st        = strtolower($t['status'] ?? '');
            $isWin     = strpos($st, 'win') !== false || ($pnl > 0 && $st !== 'breakeven');
            $isLoss    = strpos($st, 'loss') !== false || $pnl < 0;
            $isPending = ($st === 'open' || $st === 'pending' || $st === '');
            if ($isPending) $stats['pending']++;
            elseif ($isWin) $stats['wins']++;
            elseif ($isLoss) $stats['losses']++;
            else $stats['be']++;
            $depo = (float)($t['deposit_start'] ?? 0);
            if ($depo > 0) $stats['percent_sum'] += ($pnl / $depo) * 100;
        }
        $closed = $stats['wins'] + $stats['losses'] + $stats['be'];
        if ($closed > 0) { $stats['winrate'] = round(($stats['wins'] / $closed) * 100); $stats['avg_rr'] = round($stats['rr_sum'] / $closed, 2); }

        echo json_encode(['success' => true, 'stats' => $stats, 'trades' => $trades, 'plans' => $plans]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function saveMPAReport($pdo) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    try {
        if (!isset($_SESSION['user_id'])) throw new Exception("User not autorised");
        $uid  = $_SESSION['user_id'];
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['year']) || !isset($data['month'])) throw new Exception("Select year or month");

        $reportDate = sprintf("%04d-%02d-01", $data['year'], $data['month']);
        $content    = $data['content'] ?? '';

        $stmtCheck = $pdo->prepare("SELECT id FROM monthly_reports WHERE user_id = ? AND report_date = ? AND report_type = 'Monthly'");
        $stmtCheck->execute([$uid, $reportDate]);
        $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $pdo->prepare("UPDATE monthly_reports SET meta_analysis = ? WHERE id = ?")->execute([$content, $existing['id']]);
        } else {
            $pdo->prepare("INSERT INTO monthly_reports (user_id, report_date, report_type, meta_analysis) VALUES (?, ?, 'Monthly', ?)")->execute([$uid, $reportDate, $content]);
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getMPAReport($pdo) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    try {
        if (!isset($_SESSION['user_id'])) throw new Exception("User not autorised");
        $uid        = $_SESSION['user_id'];
        $reportDate = sprintf("%04d-%02d-01", $_GET['year'] ?? date('Y'), $_GET['month'] ?? date('n'));

        $stmt = $pdo->prepare("SELECT meta_analysis FROM monthly_reports WHERE user_id = ? AND report_date = ? AND report_type = 'Monthly' LIMIT 1");
        $stmt->execute([$uid, $reportDate]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'content' => $result['meta_analysis'] ?? '']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
