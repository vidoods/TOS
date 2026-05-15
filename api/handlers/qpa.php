<?php
// api/handlers/qpa.php — Quarterly Performance Analysis

function getQPAList($pdo) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    ini_set('display_errors', 0);
    error_reporting(E_ALL);

    try {
        if (!$pdo) throw new Exception("Database connection variable is NULL.");
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) throw new Exception("User not logged in.");

        $uid  = $_SESSION['user_id'];
        $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

        $sql  = "SELECT
                    QUARTER(entry_date) as q,
                    SUM(pnl) as pnl,
                    COUNT(*) as total,
                    SUM(CASE WHEN pnl > 0 THEN 1 ELSE 0 END) as wins,
                    SUM(CASE WHEN pnl < 0 THEN 1 ELSE 0 END) as losses,
                    SUM(CASE WHEN pnl = 0 THEN 1 ELSE 0 END) as be
                FROM trades
                WHERE user_id = ? AND YEAR(entry_date) = ?
                GROUP BY q ORDER BY q ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$uid, $year]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $quarters = [];
        for ($i = 1; $i <= 4; $i++) {
            $qData = null;
            foreach ($data as $row) { if ($row['q'] == $i) { $qData = $row; break; } }
            if ($qData) {
                $closed     = $qData['wins'] + $qData['losses'] + $qData['be'];
                $wr         = $closed > 0 ? round(($qData['wins'] / $closed) * 100) : 0;
                $quarters[] = ['quarter' => $i, 'year' => $year, 'pnl' => (float)$qData['pnl'], 'winrate' => $wr, 'total' => (int)$qData['total']];
            } else {
                $quarters[] = ['quarter' => $i, 'year' => $year, 'pnl' => 0, 'winrate' => 0, 'total' => 0];
            }
        }
        echo json_encode(['success' => true, 'data' => $quarters]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'API Error: ' . $e->getMessage()]);
    }
}

function getQPADetails($pdo) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');

    try {
        $uid        = $_SESSION['user_id'];
        $year       = $_GET['year'];
        $quarter    = $_GET['quarter'];
        $startMonth = ($quarter - 1) * 3 + 1;
        $endMonth   = $startMonth + 2;

        $sqlTrades = "SELECT t.*, rp.symbol as pair_name, rp.symbol as pair_symbol, a.name as account_name
                      FROM trades t
                      LEFT JOIN user_pairs rp ON t.pair_id = rp.id
                      LEFT JOIN accounts a ON t.account_id = a.id
                      WHERE t.user_id = ? AND YEAR(t.entry_date) = ? AND MONTH(t.entry_date) BETWEEN ? AND ?
                      ORDER BY t.entry_date DESC";
        $stmt = $pdo->prepare($sqlTrades);
        $stmt->execute([$uid, $year, $startMonth, $endMonth]);
        $trades = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sqlPlans = "SELECT p.*, rp.symbol as pair_symbol
                     FROM plans p
                     LEFT JOIN user_pairs rp ON p.pair_id = rp.id
                     WHERE p.user_id = ? AND YEAR(p.date) = ? AND MONTH(p.date) BETWEEN ? AND ?
                     ORDER BY p.date DESC";
        $stmtPlans = $pdo->prepare($sqlPlans);
        $stmtPlans->execute([$uid, $year, $startMonth, $endMonth]);
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

        $months = [];
        for ($m = $startMonth; $m <= $endMonth; $m++) {
            $dateObj = DateTime::createFromFormat('!m', $m);
            $mName   = $dateObj->format('F');
            $mPnl = 0; $mCount = 0; $mWins = 0;
            foreach ($trades as $t) {
                $tMonth = (int)date('m', strtotime($t['entry_date']));
                if ($tMonth === $m) {
                    $mPnl += (float)$t['pnl']; $mCount++;
                    $st    = strtolower($t['status'] ?? '');
                    if (strpos($st, 'win') !== false || ((float)$t['pnl'] > 0 && $st !== 'breakeven')) $mWins++;
                }
            }
            $months[] = ['num' => $m, 'name' => $mName, 'pnl' => $mPnl, 'count' => $mCount, 'winrate' => $mCount > 0 ? round(($mWins / $mCount) * 100) : 0];
        }

        $rDate   = sprintf("%04d-%02d-01", $year, $startMonth);
        $stmtRep = $pdo->prepare("SELECT meta_analysis FROM monthly_reports WHERE user_id=? AND report_date=? AND report_type='Quarterly'");
        $stmtRep->execute([$uid, $rDate]);
        $rep = $stmtRep->fetch();

        echo json_encode(['success' => true, 'stats' => $stats, 'months' => $months, 'trades' => $trades, 'plans' => $plans, 'report_content' => $rep['meta_analysis'] ?? '']);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function saveQPAReport($pdo) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    try {
        $d          = json_decode(file_get_contents('php://input'), true);
        $uid        = $_SESSION['user_id'];
        $startMonth = ($d['quarter'] - 1) * 3 + 1;
        $rDate      = sprintf("%04d-%02d-01", $d['year'], $startMonth);

        $check = $pdo->prepare("SELECT id FROM monthly_reports WHERE user_id=? AND report_date=? AND report_type='Quarterly'");
        $check->execute([$uid, $rDate]);
        if ($ex = $check->fetch()) {
            $pdo->prepare("UPDATE monthly_reports SET meta_analysis=? WHERE id=?")->execute([$d['content'], $ex['id']]);
        } else {
            $pdo->prepare("INSERT INTO monthly_reports (user_id, report_date, report_type, meta_analysis) VALUES (?,?,?,?)")->execute([$uid, $rDate, 'Quarterly', $d['content']]);
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
