<?php
// api/handlers/trades.php — CRUD операции со сделками

function getTrades($pdo) {
    try {
        $user_id = $_SESSION['user_id'];
        $filters = $_GET;

        $query  = "SELECT t.*, rp.symbol AS pair_symbol, a.name AS account_name, p.title AS plan_title
                   FROM trades t
                   JOIN user_pairs rp ON t.pair_id = rp.id
                   JOIN accounts a ON t.account_id = a.id
                   LEFT JOIN plans p ON t.plan_id = p.id
                   WHERE t.user_id = :user_id";
        $params = [':user_id' => $user_id];

        if (!empty($filters['pair_id']))    { $query .= " AND t.pair_id = :pair_id";                            $params[':pair_id']    = $filters['pair_id']; }
        if (!empty($filters['status']))     { $query .= " AND t.status = :status";                              $params[':status']     = $filters['status']; }
        if (!empty($filters['direction']))  { $query .= " AND t.direction = :direction";                        $params[':direction']  = $filters['direction']; }
        if (!empty($filters['month']))      { $query .= " AND DATE_FORMAT(t.entry_date, '%Y-%m') = :month";     $params[':month']      = $filters['month']; }
        if (!empty($filters['account_id'])) { $query .= " AND t.account_id = :account_id";                     $params[':account_id'] = $filters['account_id']; }

        $query .= " ORDER BY t.entry_date DESC, t.id DESC";
        $stmt   = $pdo->prepare($query);
        $stmt->execute($params);
        $trades = $stmt->fetchAll();

        $groupedTrades = [];
        foreach ($trades as $trade) {
            $monthKey = date('Y-m', strtotime($trade['entry_date']));
            if (!isset($groupedTrades[$monthKey])) {
                $groupedTrades[$monthKey] = [
                    'month_label'   => date('F Y', strtotime($trade['entry_date'])),
                    'trades'        => [],
                    'total_pnl'     => 0.0,
                    'total_rr'      => 0.0,
                    'total_percent' => 0.0
                ];
            }
            $groupedTrades[$monthKey]['trades'][]       = $trade;
            $groupedTrades[$monthKey]['total_pnl']      += (float)$trade['pnl'];
            $groupedTrades[$monthKey]['total_rr']       += (float)$trade['rr_achieved'];
            $groupedTrades[$monthKey]['total_percent']  += (float)$trade['rr_achieved'] * (float)$trade['risk_percent'];
        }
        echo json_encode(['success' => true, 'data' => array_values($groupedTrades)]);

    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Get trades error: ' . $e->getMessage()]);
    }
}

function getTradeDetails($pdo) {
    try {
        $trade_id = $_GET['id'] ?? null;
        if (!$trade_id) throw new Exception('ID not stated.');

        // 1. Основной запрос (уже был безопасным, оставляем как есть)
        $query = "SELECT t.*,
                         rp.symbol AS pair_symbol, rp.type AS pair_type,
                         a.name AS account_name, a.type AS account_type,
                         p.title AS plan_title, p.date AS plan_date,
                         rs.name AS style_name,
                         rm.name AS model_name
                  FROM trades t
                  JOIN user_pairs rp ON t.pair_id = rp.id
                  JOIN accounts a ON t.account_id = a.id
                  LEFT JOIN plans p ON t.plan_id = p.id
                  LEFT JOIN user_styles rs ON t.style_id = rs.id
                  LEFT JOIN user_models rm ON t.model_id = rm.id
                  WHERE t.id = ? AND t.user_id = ?";
        $stmt  = $pdo->prepare($query);
        $stmt->execute([$trade_id, $_SESSION['user_id']]);
        $trade = $stmt->fetch();

        if (!$trade) { 
            http_response_code(404); 
            throw new Exception('Trade not found.'); 
        }

        // 2. Запрос изображений (безопасный)
        $stmt_images = $pdo->prepare("SELECT id, image_url, notes, title FROM trade_analysis_images WHERE trade_id = ? AND is_plan_image = 0 ORDER BY id ASC");
        $stmt_images->execute([$trade_id]);
        $trade['trade_images'] = $stmt_images->fetchAll();

        // 3. ИСПРАВЛЕННЫЙ ЗАПРОС К ЗАМЕТКАМ (Теперь здесь тоже Prepare!)
        // Мы убрали прямую вставку $trade_id и заменили её на плейсхолдер ?
        $stmt_note = $pdo->prepare("SELECT n.id, n.title FROM note_to_trade nt JOIN notes n ON nt.note_id = n.id WHERE nt.trade_id = ? LIMIT 1");
        $stmt_note->execute([$trade_id]);
        $note = $stmt_note->fetch(PDO::FETCH_ASSOC);

        $trade['note_id']    = $note['id'] ?? null;
        $trade['note_title'] = $note['title'] ?? null;

        echo json_encode(['success' => true, 'data' => $trade]);

    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Trade details error: ' . $e->getMessage()]);
    }
}

function saveTrade($pdo) {
    try {
        $user_id = $_SESSION['user_id'];
        $data    = json_decode(file_get_contents('php://input'), true);

        foreach (['pair_id', 'account_id', 'entry_date', 'direction', 'risk_percent'] as $field) {
            if (empty($data[$field])) throw new Exception("Field $field required.");
        }

        $trade_id  = $data['id'] ?? null;
        $is_update = !empty($trade_id);

        $pdo->beginTransaction();

        $entry_date  = !empty($data['entry_date']) ? $data['entry_date'] : null;
        $exit_date   = !empty($data['exit_date'])  ? $data['exit_date']  : null;
        $rr_achieved = (isset($data['rr_achieved']) && $data['rr_achieved'] !== '') ? $data['rr_achieved'] : null;
        $pnl         = (isset($data['pnl']) && $data['pnl'] !== '') ? $data['pnl'] : null;

        $params = [
            $data['pair_id'],
            $data['account_id'],
            !empty($data['plan_id'])  ? $data['plan_id']  : null,
            !empty($data['style_id']) ? $data['style_id'] : null,
            !empty($data['model_id']) ? $data['model_id'] : null,
            $entry_date,
            $exit_date,
            $data['direction'],
            $data['risk_percent'],
            $rr_achieved,
            $pnl,
            $data['status']             ?? 'pending',
            $data['trade_conclusions']  ?? null,
            $data['key_lessons']        ?? null,
            $data['entry_timeframe']    ?? null,
            $data['notes']              ?? null,
            $data['tags']               ?? null,
            $data['mistakes_made']      ?? null,
            $data['emotional_state']    ?? null,
            $user_id
        ];

        if ($is_update) {
            $check = $pdo->prepare("SELECT id FROM trades WHERE id = ? AND user_id = ?");
            $check->execute([$trade_id, $user_id]);
            if (!$check->fetch()) throw new Exception('Trade not found or you do not have the permission.');

            $sql           = "UPDATE trades SET pair_id=?, account_id=?, plan_id=?, style_id=?, model_id=?, entry_date=?, exit_date=?, direction=?, risk_percent=?, rr_achieved=?, pnl=?, status=?, trade_conclusions=?, key_lessons=?, entry_tf=?, notes=?, tags=?, mistakes_made=?, emotional_state=? WHERE id=? AND user_id=?";
            $update_params = array_slice($params, 0, count($params) - 1);
            $update_params[] = $trade_id;
            $update_params[] = $user_id;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($update_params);
            $message = 'Trade updated!';
        } else {
            $sql           = "INSERT INTO trades (pair_id, account_id, plan_id, style_id, model_id, entry_date, exit_date, direction, risk_percent, rr_achieved, pnl, status, trade_conclusions, key_lessons, entry_tf, notes, tags, mistakes_made, emotional_state, user_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $insert_params = array_slice($params, 0, count($params) - 1);
            $insert_params[] = $user_id;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($insert_params);
            $trade_id = $pdo->lastInsertId();
            $message  = 'Trade created!';
        }

        $pdo->prepare("DELETE FROM note_to_trade WHERE trade_id = ?")->execute([$trade_id]);
        if (!empty($data['note_id'])) {
            $pdo->prepare("INSERT INTO note_to_trade (note_id, trade_id) VALUES (?, ?)")->execute([$data['note_id'], $trade_id]);
        }

        if (!empty($data['trade_images']) && is_array($data['trade_images'])) {
            if ($is_update) {
                $pdo->prepare("DELETE FROM trade_analysis_images WHERE trade_id = ? AND is_plan_image = 0")->execute([$trade_id]);
            }
            $img_stmt = $pdo->prepare("INSERT INTO trade_analysis_images (trade_id, image_url, notes, title, is_plan_image) VALUES (?, ?, ?, ?, 0)");
            foreach ($data['trade_images'] as $i => $img) {
                if (!empty($img['url'])) {
                    $img_stmt->execute([$trade_id, $img['url'], $img['notes'] ?? null, $img['title'] ?? ('Screenshot ' . ($i + 1))]);
                }
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => $message, 'id' => $trade_id]);

    } catch (\Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Saving error: ' . $e->getMessage()]);
    }
}

function deleteTrade($pdo) {
    try {
        $trade_id = $_POST['trade_id'] ?? null;
        if (!$trade_id) throw new Exception('ID not stated.');

        $user_id = $_SESSION['user_id'];
        $pdo->beginTransaction();

        $check = $pdo->prepare("SELECT id FROM trades WHERE id = ? AND user_id = ?");
        $check->execute([$trade_id, $user_id]);
        if (!$check->fetch()) throw new Exception('Trade not found or you do not have the permission.');

        $stmt_get_images = $pdo->prepare("SELECT image_url FROM trade_analysis_images WHERE trade_id = ? AND is_plan_image = 0");
        $stmt_get_images->execute([$trade_id]);
        $images = $stmt_get_images->fetchAll();

        $pdo->prepare("DELETE FROM trade_analysis_images WHERE trade_id = ? AND is_plan_image = 0")->execute([$trade_id]);
        $stmt = $pdo->prepare("DELETE FROM trades WHERE id = ? AND user_id = ?");
        $stmt->execute([$trade_id, $user_id]);

        if ($stmt->rowCount() > 0) {
            $pdo->commit();
            foreach ($images as $img) {
                $filePath = '../' . $img['image_url'];
                if (file_exists($filePath)) unlink($filePath);
            }
            echo json_encode(['success' => true, 'message' => 'Trade deleted.']);
        } else {
            throw new Exception('Trade delete error.');
        }

    } catch (\Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Trade delete error: ' . $e->getMessage()]);
    }
}
