<?php
// api/handlers/strategy.php — CRUD операции со стратегиями

function getStrategies($pdo) {
    try {
        $uid  = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT * FROM strategy_modules WHERE user_id = ? ORDER BY id ASC");
        $stmt->execute([$uid]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getStrategyDetails($pdo) {
    try {
        $id  = $_GET['id'] ?? null;
        $uid = $_SESSION['user_id'];
        if (!$id) throw new Exception("ID required");

        $stmt = $pdo->prepare("SELECT * FROM strategy_modules WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $uid]);
        $data = $stmt->fetch();

        if (!$data) throw new Exception("Strategy module not found");

        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function saveStrategy($pdo) {
    try {
        $uid  = $_SESSION['user_id'];
        $d    = json_decode(file_get_contents('php://input'), true);

        if (empty($d['title'])) throw new Exception("Title is required");

        $icon = !empty($d['icon']) ? $d['icon'] : 'fas fa-book';
        $id   = null;

        if (!empty($d['id'])) {
            $id  = $d['id'];
            $sql = "UPDATE strategy_modules SET title=?, description=?, icon=?, content=? WHERE id=? AND user_id=?";
            $pdo->prepare($sql)->execute([$d['title'], $d['description'], $icon, $d['content'] ?? '', $id, $uid]);
        } else {
            $sql = "INSERT INTO strategy_modules (user_id, title, description, icon, content) VALUES (?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$uid, $d['title'], $d['description'], $icon, $d['content'] ?? '']);
            $id  = $pdo->lastInsertId();
        }

        echo json_encode(['success' => true, 'id' => $id]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteStrategy($pdo) {
    try {
        $uid = $_SESSION['user_id'];
        $id  = $_POST['id'];
        $pdo->prepare("DELETE FROM strategy_modules WHERE id=? AND user_id=?")->execute([$id, $uid]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>