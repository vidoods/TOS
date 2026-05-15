<?php
// api/handlers/notes.php — CRUD операции с заметками

function getNotes($pdo) {
    $uid = $_SESSION['user_id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM notes WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$uid]);
        $notes = $stmt->fetchAll();
        
        foreach ($notes as &$note) {
            $nid  = $note['id'];
            $time = strtotime($note['created_at']);
            $note['date_formatted'] = date('d.m.y', $time);
            $note['day']  = date('l', $time);
            $note['week'] = 'Week #' . date('W', $time);

            $stmtTr = $pdo->prepare("SELECT COUNT(*) FROM note_to_trade WHERE note_id = ?");
            $stmtTr->execute([$nid]);
            $tr = $stmtTr->fetchColumn();

            $stmtPl = $pdo->prepare("SELECT COUNT(*) FROM note_to_plan WHERE note_id = ?");
            $stmtPl->execute([$nid]);
            $pl = $stmtPl->fetchColumn();

            $links = [];
            if ($tr > 0) $links[] = "$tr Trades";
            if ($pl > 0) $links[] = "$pl Plans";
            $note['relations'] = empty($links) ? 'No Links' : implode(' / ', $links);

            $stmtLastTr = $pdo->prepare("SELECT MAX(t.entry_date) FROM note_to_trade nt JOIN trades t ON nt.trade_id = t.id WHERE nt.note_id = ?");
            $stmtLastTr->execute([$nid]);
            $lastTradeDate = $stmtLastTr->fetchColumn();

            $stmtLastPl = $pdo->prepare("SELECT MAX(p.date) FROM note_to_plan np JOIN plans p ON np.plan_id = p.id WHERE np.note_id = ?");
            $stmtLastPl->execute([$nid]);
            $lastPlanDate = $stmtLastPl->fetchColumn();

            $dates = [];
            if ($lastTradeDate) $dates[] = strtotime($lastTradeDate);
            if ($lastPlanDate)  $dates[] = strtotime($lastPlanDate);

            if (!empty($dates)) {
                $note['latest_usage'] = date('d.m.y', max($dates));
            } else {
                $note['latest_usage'] = 'Not Used';
            }
        }
        echo json_encode(['success' => true, 'data' => $notes]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function saveNote($pdo) {
    try {
        $raw = file_get_contents('php://input');
        $d = json_decode($raw, true);
        if (!$d) {
            $d = $_POST;
        }

        $uid = $_SESSION['user_id'];
        $id  = $d['id'] ?? null;

        $pdo->beginTransaction();

        if ($id) {
            $stmtCheck = $pdo->prepare("SELECT id FROM notes WHERE id = ? AND user_id = ?");
            $stmtCheck->execute([$id, $uid]);
            if (!$stmtCheck->fetch()) throw new Exception("Access denied");

            $pdo->prepare("UPDATE notes SET title = ?, content = ? WHERE id = ?")->execute([$d['title'], $d['content'] ?? '', $id]);
            $pdo->prepare("DELETE FROM note_to_trade WHERE note_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM note_to_plan WHERE note_id = ?")->execute([$id]);
        } else {
            $pdo->prepare("INSERT INTO notes (user_id, title, content) VALUES (?, ?, ?)")->execute([$uid, $d['title'], $d['content'] ?? '']);
            $id = $pdo->lastInsertId();
        }

        if (!empty($d['trade_id'])) $pdo->prepare("INSERT INTO note_to_trade (note_id, trade_id) VALUES (?, ?)")->execute([$id, $d['trade_id']]);
        if (!empty($d['plan_id']))  $pdo->prepare("INSERT INTO note_to_plan (note_id, plan_id) VALUES (?, ?)")->execute([$id, $d['plan_id']]);

        $pdo->commit();
        echo json_encode(['success' => true, 'id' => $id]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteNote($pdo) {
    try {
        $id  = $_POST['id'] ?? null;
        $uid = $_SESSION['user_id'];
        $pdo->beginTransaction();
        
        $check = $pdo->prepare("SELECT id FROM notes WHERE id = ? AND user_id = ?");
        $check->execute([$id, $uid]);
        if ($check->fetch()) {
            $pdo->prepare("DELETE FROM note_to_trade WHERE note_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM note_to_plan WHERE note_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM notes WHERE id = ?")->execute([$id]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getNoteDetails($pdo) {
    try {
        $id  = $_GET['id'] ?? null;
        $uid = $_SESSION['user_id'];

        if (!$id) throw new Exception("No ID provided");

        $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $uid]);
        $note = $stmt->fetch();

        if (!$note) throw new Exception("Note not found");

        $trStmt = $pdo->prepare("SELECT trade_id FROM note_to_trade WHERE note_id = ? LIMIT 1");
        $trStmt->execute([$id]);
        $tradeId = $trStmt->fetchColumn();

        if ($tradeId) {
            // ИСПРАВЛЕНИЕ: Теперь джойним таблицу user_pairs, чтобы получить символ пары
            $tStmt = $pdo->prepare("
                SELECT t.id, 
                       CONCAT(DATE_FORMAT(t.entry_date, '%d.%m.%y'), ' (', IFNULL(up.symbol, 'N/A'), ')') as label 
                FROM trades t
                LEFT JOIN user_pairs up ON t.pair_id = up.id
                WHERE t.id = ?
            ");
            $tStmt->execute([$tradeId]);
            $trade = $tStmt->fetch();
            $note['trade'] = $trade ?: null;
            $note['trade_id'] = $tradeId;
        }

        $plStmt = $pdo->prepare("SELECT plan_id FROM note_to_plan WHERE note_id = ? LIMIT 1");
        $plStmt->execute([$id]);
        $planId = $plStmt->fetchColumn();

        if ($planId) {
            $pStmt = $pdo->prepare("SELECT id, title as label FROM plans WHERE id = ?");
            $pStmt->execute([$planId]);
            $plan = $pStmt->fetch();
            $note['plan'] = $plan ?: null;
            $note['plan_id'] = $planId;
        }

        echo json_encode(['success' => true, 'data' => $note]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>