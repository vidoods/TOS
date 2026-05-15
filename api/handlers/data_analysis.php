<?php
// api/handlers/data_analysis.php — Статистика по данным

function getDataAnalysis($pdo) {
    try {
        $uid = $_SESSION['user_id'];

        $getRefStats = function($refTable, $refNameCol, $tradeFkCol) use ($pdo, $uid) {
            $sql = "SELECT
                        r.$refNameCol as label,
                        COUNT(t.id) as total_trades,
                        SUM(CASE WHEN t.status = 'win' THEN 1 ELSE 0 END) as wins,
                        SUM(CASE WHEN t.status IN ('win', 'loss', 'breakeven') THEN 1 ELSE 0 END) as completed
                    FROM $refTable r
                    LEFT JOIN trades t ON t.$tradeFkCol = r.id AND t.user_id = ? AND t.status != 'cancelled'
                    WHERE r.user_id = ?
                    GROUP BY r.id, r.$refNameCol
                    ORDER BY total_trades DESC, label ASC";
            
            $stmt = $pdo->prepare($sql);
            // Передаем $uid дважды: один для JOIN (сделки), второй для WHERE (список моделей)
            $stmt->execute([$uid, $uid]);
            
            $rows = $stmt->fetchAll();
            return array_map(function($row) {
                $comp = (int)$row['completed'];
                $row['win_rate'] = $comp > 0 ? round(($row['wins'] / $comp) * 100) : 0;
                return $row;
            }, $rows);
        };

        $getSimpleStats = function($col) use ($pdo, $uid) {
            $sql = "SELECT
                        $col as label,
                        COUNT(*) as total_trades,
                        SUM(CASE WHEN status = 'win' THEN 1 ELSE 0 END) as wins,
                        SUM(CASE WHEN status IN ('win', 'loss', 'breakeven') THEN 1 ELSE 0 END) as completed
                    FROM trades
                    WHERE user_id = ? AND status != 'cancelled'
                    GROUP BY $col
                    HAVING label IS NOT NULL AND label != ''
                    ORDER BY total_trades DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$uid]);
            $rows = $stmt->fetchAll();
            return array_map(function($row) {
                $comp = (int)$row['completed'];
                $row['win_rate'] = $comp > 0 ? round(($row['wins'] / $comp) * 100) : 0;
                return $row;
            }, $rows);
        };

        $getTimeframeStats = function() use ($pdo, $uid) {
            $masterList = ['1D', 'H4', 'H1', 'M15', 'M5'];
            $sql = "SELECT
                        entry_tf as label,
                        COUNT(*) as total_trades,
                        SUM(CASE WHEN status = 'win' THEN 1 ELSE 0 END) as wins,
                        SUM(CASE WHEN status IN ('win', 'loss', 'breakeven') THEN 1 ELSE 0 END) as completed
                    FROM trades
                    WHERE user_id = ? AND status != 'cancelled'
                    GROUP BY entry_tf";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$uid]);
            $dbStats = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);

            $result = [];
            foreach ($masterList as $tf) {
                if (isset($dbStats[$tf])) {
                    $row      = $dbStats[$tf];
                    $comp     = (int)$row['completed'];
                    $winRate  = $comp > 0 ? round(($row['wins'] / $comp) * 100) : 0;
                    $result[] = ['label' => $tf, 'total_trades' => (int)$row['total_trades'], 'win_rate' => $winRate];
                } else {
                    $result[] = ['label' => $tf, 'total_trades' => 0, 'win_rate' => 0];
                }
            }
            return $result;
        };

        $data = [
            'direction' => $getSimpleStats('direction'),
            'timeframe' => $getTimeframeStats(),
            'style'     => $getRefStats('user_styles', 'name', 'style_id'),
            'model'     => $getRefStats('user_models', 'name', 'model_id'),
            'pairs'     => $getRefStats('user_pairs', 'symbol', 'pair_id')
        ];

        echo json_encode(['success' => true, 'data' => $data]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
