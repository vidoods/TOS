<?php
// api/handlers/payouts.php — Выплаты

function getPayouts($pdo) {
    try {
        $uid = $_SESSION['user_id'];
        
        // Выбираем выплаты строго для текущего пользователя (по p.user_id)
        $sql = "SELECT p.*, a.name as account_name, a.currency
                FROM payouts p
                JOIN accounts a ON p.account_id = a.id
                WHERE p.user_id = ?
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
        $uid = $_SESSION['user_id'];

        if (empty($d['account_id']) || empty($d['amount'])) {
            throw new Exception('Пожалуйста, заполните все обязательные поля');
        }

        // БЕЗОПАСНОСТЬ: Проверяем, принадлежит ли выбранный счет (account_id) текущему пользователю
        $stmtCheckAcc = $pdo->prepare("SELECT id FROM accounts WHERE id = ? AND user_id = ?");
        $stmtCheckAcc->execute([$d['account_id'], $uid]);
        if (!$stmtCheckAcc->fetch()) {
            throw new Exception("Доступ запрещен: Выбран чужой или несуществующий счет");
        }

        if (!empty($d['id'])) {
            // ОБНОВЛЕНИЕ: Добавляем жесткую проверку по user_id, чтобы никто не мог изменить чужую выплату
            $sql = "UPDATE payouts SET account_id=?, amount=?, payout_date=?, confirmation_status=? WHERE id=? AND user_id=?";
            $pdo->prepare($sql)->execute([$d['account_id'], $d['amount'], $d['payout_date'], $d['confirmation_status'], $d['id'], $uid]);
        } else {
            // СОЗДАНИЕ: Передаем $uid в колонку user_id (именно это исправляет ошибку SQL базы данных 1364)
            $sql = "INSERT INTO payouts (user_id, account_id, amount, payout_date, confirmation_status) VALUES (?,?,?,?,?)";
            $pdo->prepare($sql)->execute([$uid, $d['account_id'], $d['amount'], $d['payout_date'], $d['confirmation_status']]);
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deletePayout($pdo) {
    try {
        $id = $_POST['id'];
        $uid = $_SESSION['user_id'];

        // УДАЛЕНИЕ: Удаляем запись только в том случае, если она принадлежит текущему пользователю
        $sql = "DELETE FROM payouts WHERE id = ? AND user_id = ?";
        $pdo->prepare($sql)->execute([$id, $uid]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>