<?php
// api/handlers/notes.php — CRUD операции с заметками

function getNotes($pdo) {
    $uid = $_SESSION['user_id'];
    try {
        $notes = $pdo->query("SELECT * FROM notes WHERE user_id = $uid ORDER BY created_at DESC")->fetchAll();
        foreach ($notes as &$note) {
            $nid  = $note['id'];
            $time = strtotime($note['created_at']);
            $note['date_formatted'] = date('d.m.y', $time);
            $note['day']  = date('l', $time);
            $note['week'] = 'Week #' . date('W', $time);

            $tr    = $pdo->query("SELECT COUNT(*) FROM note_to_trade WHERE note_id=$nid")->fetchColumn();
            $pl    = $pdo->query("SELECT COUNT(*) FROM note_to_plan WHERE note_id=$nid")->fetchColumn();
            $links = [];
            if ($tr > 0) $links[] = "$tr Trades";
            if ($pl > 0) $links[] = "$pl Plans";
            $note['relations'] = empty($links) ? 'No Links' : implode(' / ', $links);

            $lastTradeDate = $pdo->query("SELECT MAX(t.entry_date) FROM note_to_trade nt JOIN trades t ON nt.trade_id = t.id WHERE nt.note_id = $nid")->fetchColumn();
            $lastPlanDate  = $pdo->query("SELECT MAX(p.date) FROM note_to_plan np JOIN plans p ON np.plan_id = p.id WHERE np.note_id = $nid")->fetchColumn();

            $latestTimestamp = null;
            if ($lastTradeDate) $latestTimestamp = strtotime($lastTradeDate);
            if ($lastPlanDate) {
                $pTime = strtotime($lastPlanDate);
                if (!$latestTimestamp || $pTime > $latestTimestamp) $latestTimestamp = $pTime;
            }
            $note['latest_usage'] = $latestTimestamp ? date('d.m.y', $latestTimestamp) : 'Not Used';
        }
        echo json_encode(['success' => true, 'data' => $notes]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getNoteDetails($pdo) {
    $id  = $_GET['id'];
    $uid = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE id=? AND user_id=? LIMIT 1");
    $stmt->execute([$id, $uid]);
    $res = $stmt->fetch();

    if (!$res) { echo json_encode(['success' => false]); return; }

    $res['trade'] = $pdo->query("
        SELECT t.id, CONCAT(rp.symbol, ' (', UPPER(t.direction), ') ', DATE_FORMAT(t.entry_date, '%d.%m.%y')) as label
        FROM note_to_trade nt
        JOIN trades t ON nt.trade_id = t.id
        JOIN ref_pairs rp ON t.pair_id = rp.id
        WHERE nt.note_id = $id LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);

    $res['plan'] = $pdo->query("
        SELECT p.id, p.title as label
        FROM note_to_plan np
        JOIN plans p ON np.plan_id = p.id
        WHERE np.note_id = $id LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);

    $res['created_formatted'] = date('d F Y, H:i', strtotime($res['created_at']));

    echo json_encode(['success' => true, 'data' => $res]);
}

function saveNote($pdo) {
    try {
        $uid = $_SESSION['user_id'];
        $d   = json_decode(file_get_contents('php://input'), true);

        if (empty($d['title'])) throw new Exception('Title required');

        $id = $d['id'] ?? null;
        $pdo->beginTransaction();

        if ($id) {
            $pdo->prepare("UPDATE notes SET title = ?, content = ? WHERE id = ? AND user_id = ?")->execute([$d['title'], $d['content'] ?? '', $id, $uid]);
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
        $pdo->prepare("DELETE FROM note_to_trade WHERE note_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM note_to_plan WHERE note_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?")->execute([$id, $uid]);
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
