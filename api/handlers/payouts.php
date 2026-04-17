<?php
// api/handlers/payouts.php — Выплаты

function getPayouts($pdo) {
    try {
        $uid = $_SESSION['user_id'];
        $sql = "SELECT p.*, a.name as account_name, a.currency
                FROM payouts p
                JOIN accounts a ON p.account_id = a.id
                WHERE a.user_id = ?
                ORDER BY p.payout_date DESC, p.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$uid]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function savePayout($pdo) {
    try {
        $d = json_decode(file_get_contents('php://input'), true);

        if (empty($d['account_id']) || empty($d['amount'])) throw new Exception('Enter required fields');

        if (!empty($d['id'])) {
            $sql = "UPDATE payouts SET account_id=?, amount=?, payout_date=?, confirmation_status=? WHERE id=?";
            $pdo->prepare($sql)->execute([$d['account_id'], $d['amount'], $d['payout_date'], $d['confirmation_status'], $d['id']]);
        } else {
            $sql = "INSERT INTO payouts (account_id, amount, payout_date, confirmation_status) VALUES (?,?,?,?)";
            $pdo->prepare($sql)->execute([$d['account_id'], $d['amount'], $d['payout_date'], $d['confirmation_status']]);
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deletePayout($pdo) {
    try {
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM payouts WHERE id=?")->execute([$id]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
