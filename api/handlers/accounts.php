<?php
// api/handlers/accounts.php — CRUD операции с аккаунтами

function getAccountsData($pdo) {
    try {
        $uid = $_SESSION['user_id'];

        $accounts = $pdo->prepare("SELECT * FROM accounts WHERE user_id = ? ORDER BY id ASC");
        $accounts->execute([$uid]);
        $result = $accounts->fetchAll();

        foreach ($result as &$acc) {
            $aid   = $acc['id'];
            $stats = $pdo->query("
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status='win' THEN 1 ELSE 0 END) as wins,
                    SUM(CASE WHEN status='loss' THEN 1 ELSE 0 END) as losses,
                    COALESCE(SUM(pnl), 0) as total_pnl,
                    COALESCE(AVG(rr_achieved), 0) as avg_rr
                FROM trades
                WHERE account_id = $aid AND user_id = $uid
            ")->fetch();

            $acc['total_trades']         = (int)$stats['total'];
            $acc['wins']                 = (int)$stats['wins'];
            $acc['profit']               = (float)$stats['total_pnl'];
            $acc['avg_rr']               = round((float)$stats['avg_rr'], 2);
            $acc['calculated_balance']   = (float)$acc['balance'] + $acc['profit'];
            $acc['balance']              = (float)$acc['balance'];
            $acc['starting_equity']      = (float)$acc['starting_equity'];
            $acc['target_percent']       = (float)$acc['target_percent'];
            $acc['max_drawdown_percent'] = (float)$acc['max_drawdown_percent'];
        }

        echo json_encode(['success' => true, 'data' => $result]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function saveAccount($pdo) {
    try {
        $uid = $_SESSION['user_id'];
        $d   = json_decode(file_get_contents('php://input'), true);

        if (empty($d['name'])) throw new Exception('Enter name');

        $target   = !empty($d['target_percent'])       ? $d['target_percent']       : 0;
        $dd       = !empty($d['max_drawdown_percent'])  ? $d['max_drawdown_percent'] : 0;
        $balance  = !empty($d['balance'])               ? $d['balance']              : 0;
        $starting = !empty($d['starting_equity'])       ? $d['starting_equity']      : $balance;

        if (!empty($d['id'])) {
            $sql = "UPDATE accounts SET name=?, type=?, balance=?, starting_equity=?, target_percent=?, max_drawdown_percent=? WHERE id=? AND user_id=?";
            $pdo->prepare($sql)->execute([$d['name'], $d['type'], $balance, $starting, $target, $dd, $d['id'], $uid]);
        } else {
            $sql = "INSERT INTO accounts (user_id, name, type, balance, starting_equity, current_equity, currency, status, target_percent, max_drawdown_percent) VALUES (?,?,?,?,?,?,'USD','Active',?,?)";
            $pdo->prepare($sql)->execute([$uid, $d['name'], $d['type'], $balance, $starting, $balance, $target, $dd]);
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteAccount($pdo) {
    try {
        $uid = $_SESSION['user_id'];
        $id  = $_POST['id'];
        $pdo->prepare("DELETE FROM accounts WHERE id=? AND user_id=?")->execute([$id, $uid]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getAccountDetails($pdo) {
    try {
        $uid = $_SESSION['user_id'];
        $id  = $_GET['id'] ?? null;
        if (!$id) throw new Exception('ID not stated');

        $stmt = $pdo->prepare("SELECT * FROM accounts WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $uid]);
        $acc = $stmt->fetch();

        if (!$acc) throw new Exception('Account not found');

        echo json_encode(['success' => true, 'data' => $acc]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
