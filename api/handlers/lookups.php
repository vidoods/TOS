<?php
// api/handlers/lookups.php — Справочные данные для форм

function getLookups($pdo) {
    try {
        $user_id = $_SESSION['user_id'];
        $results = [];

        $results['pairs']   = $pdo->query("SELECT id, symbol, type FROM ref_pairs ORDER BY symbol ASC")->fetchAll();
        
        $stmt = $pdo->prepare("SELECT id, name, type, balance FROM accounts WHERE user_id = :user_id ORDER BY name ASC");
        $stmt->execute(['user_id' => $user_id]);
        $results['accounts'] = $stmt->fetchAll();

        $results['styles'] = $pdo->query("SELECT id, name FROM ref_styles ORDER BY name ASC")->fetchAll();
        $results['models'] = $pdo->query("SELECT id, name FROM ref_models ORDER BY name ASC")->fetchAll();

        $stmt = $pdo->prepare("SELECT id, title, date FROM plans WHERE user_id = :user_id ORDER BY date DESC");
        $stmt->execute(['user_id' => $user_id]);
        $results['plans'] = $stmt->fetchAll();

        $results['notes'] = $pdo->query("SELECT id, title FROM notes WHERE user_id = $user_id ORDER BY created_at DESC")->fetchAll();

        $stmt = $pdo->prepare("
            SELECT t.id,
                   CONCAT(DATE_FORMAT(t.entry_date, '%d.%m.%y'), ' - ', rp.symbol, ' (', UCASE(t.direction), ')') as display_name
            FROM trades t
            JOIN ref_pairs rp ON t.pair_id = rp.id
            WHERE t.user_id = :user_id
            ORDER BY t.entry_date DESC
            LIMIT 50
        ");
        $stmt->execute(['user_id' => $user_id]);
        $results['trades'] = $stmt->fetchAll();

        $results['trade_statuses']   = ['pending', 'win', 'loss', 'breakeven', 'partial', 'cancelled'];
        $results['trade_directions'] = ['long', 'short'];
        $results['plan_types']       = ['Daily', 'Weekly', 'Monthly', 'Long Term'];
        $results['plan_biases']      = ['Bullish', 'Bearish', 'Neutral'];
        $results['plan_statuses']    = ['pending', 'completed', 'cancelled'];
        $results['entry_timeframes'] = ['1m', '5m', '15m', '30m', '1h', '4h', '1D', '1W'];

        echo json_encode(['success' => true, 'data' => $results]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error loading data: ' . $e->getMessage()]);
    }
}

function getRefPairs($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, symbol, type FROM ref_pairs ORDER BY symbol");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Pairs error: ' . $e->getMessage()]);
    }
}

function getAccountsLookup($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT id, name, type, currency FROM accounts WHERE user_id = ? ORDER BY name");
        $stmt->execute([$_SESSION['user_id']]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Accounts error: ' . $e->getMessage()]);
    }
}

function getRefStyles($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, name FROM ref_styles ORDER BY name");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Styles error: ' . $e->getMessage()]);
    }
}

function getPlansForLookup($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT id, title, date FROM plans WHERE user_id = ? ORDER BY date DESC");
        $stmt->execute([$_SESSION['user_id']]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Plans error: ' . $e->getMessage()]);
    }
}
