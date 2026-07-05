<?php
// api/handlers/insights.php — CRUD операции с insights

function getInsightDetails($pdo) {
    try {
        $id  = $_GET['id'] ?? null;
        $uid = $_SESSION['user_id'];

        if (!$id) throw new Exception("No ID provided");

        $stmt = $pdo->prepare("SELECT * FROM asset_profiles WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $uid]);
        $insight = $stmt->fetch();

        if (!$insight) throw new Exception("Insight not found");

        echo json_encode(['success' => true, 'data' => $insight]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function saveInsight($pdo) {
    try {
        // 1. Capture everything for debugging
        $raw = file_get_contents('php://input');
        $d = json_decode($raw, true) ?: $_POST;
        
        // DEBUG: Let's see what the server actually received
        $debug_received_data = $d; 

        $uid = $_SESSION['user_id'];
        $id  = $d['id'] ?? null;
        $asset_id = $d['asset_id'] ?? null;
        $content = $d['content'] ?? '';

        if (!$asset_id) {
            throw new Exception("Asset ID is missing in request");
        }

        if ($id) {
            // UPDATE logic
            $stmtCheck = $pdo->prepare("SELECT id FROM asset_profiles WHERE id = ? AND user_id = ?");
            $stmtCheck->execute([$id, $uid]);
            if (!$stmtCheck->fetch()) {
                throw new Exception("Access denied or record not found");
            }

            $stmt = $pdo->prepare("UPDATE asset_profiles SET asset_id = ?, content =					?, created_by = ? WHERE id = ?");
            $stmt->execute([$asset_id, $content, $uid, $id]);
            $action_taken = "UPDATE";
        } else {
            // INSERT logic
            $stmt = $pdo->prepare("INSERT INTO asset_profiles (user_id, asset_id, content, created_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$uid, $asset_id, $content, $uid]);
            $id = $pdo->lastInsertId();
            $action_taken = "INSERT";
        }

        // 2. Return success WITH debug info
        echo json_encode([
            'success' => true, 
            'id' => $id,
            'debug' => [
                'action' => $action_taken,
                'received_asset_id' => $asset_id,
                'received_content_length' => strlen($content),
                'user_id_context' => $uid,
                'raw_input_preview' => substr($raw, 0, 100) // First 100 chars of raw input
            ]
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage(),
            'sql_error' => $pdo->errorInfo()
        ]);
    }
}

function deleteInsight($pdo) {
    try {
        $id  = $_POST['id'] ?? null;
        $uid = $_SESSION['user_id'];

        if (!$id) throw new Exception("No ID provided");

        $check = $pdo->prepare("SELECT id FROM asset_profiles WHERE id = ? AND user_id = ?");
        $check->execute([$id, $uid]);
        if ($check->fetch()) {
            $pdo->prepare("DELETE FROM asset_profiles WHERE id = ?")->execute([$id]);
        }

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>