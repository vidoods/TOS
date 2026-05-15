<?php
// api/handlers/lookups.php — Справочные данные для форм

function getLookups($pdo) {
    try {
        $user_id = $_SESSION['user_id'];
        $results = [];

        // ИСПРАВЛЕНО: Добавлен фильтр WHERE user_id = :user_id
        $stmtPairs = $pdo->prepare("SELECT id, symbol, type FROM user_pairs WHERE user_id = :user_id ORDER BY symbol ASC");
        $stmtPairs->execute(['user_id' => $user_id]);
        $results['pairs'] = $stmtPairs->fetchAll();
        
        $stmtAcc = $pdo->prepare("SELECT id, name, type, balance FROM accounts WHERE user_id = :user_id ORDER BY name ASC");
        $stmtAcc->execute(['user_id' => $user_id]);
        $results['accounts'] = $stmtAcc->fetchAll();

        // ИСПРАВЛЕНО: Добавлен фильтр WHERE user_id = :user_id
        $stmtStyles = $pdo->prepare("SELECT id, name FROM user_styles WHERE user_id = :user_id ORDER BY name ASC");
        $stmtStyles->execute(['user_id' => $user_id]);
        $results['styles'] = $stmtStyles->fetchAll();

        // ИСПРАВЛЕНО: Добавлен фильтр WHERE user_id = :user_id
        $stmtModels = $pdo->prepare("SELECT id, name FROM user_models WHERE user_id = :user_id ORDER BY name ASC");
        $stmtModels->execute(['user_id' => $user_id]);
        $results['models'] = $stmtModels->fetchAll();

        $stmtPlans = $pdo->prepare("SELECT id, title, date FROM plans WHERE user_id = :user_id ORDER BY date DESC");
        $stmtPlans->execute(['user_id' => $user_id]);
        $results['plans'] = $stmtPlans->fetchAll();

        // Заменил прямой запрос на подготовленный (для безопасности)
        $stmtNotes = $pdo->prepare("SELECT id, title FROM notes WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmtNotes->execute(['user_id' => $user_id]);
        $results['notes'] = $stmtNotes->fetchAll();

        $stmtTrades = $pdo->prepare("
            SELECT t.id,
                   CONCAT(DATE_FORMAT(t.entry_date, '%d.%m.%y'), ' - ', rp.symbol, ' (', UCASE(t.direction), ')') as display_name
            FROM trades t
            JOIN user_pairs rp ON t.pair_id = rp.id
            WHERE t.user_id = :user_id
            ORDER BY t.entry_date DESC
        ");
        $stmtTrades->execute(['user_id' => $user_id]);
        $results['trades'] = $stmtTrades->fetchAll();

        echo json_encode(['success' => true, 'data' => $results]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Lookups error: ' . $e->getMessage()]);
    }
}

function getModelsLookup($pdo) {
    try {
        // ИСПРАВЛЕНО
        $stmt = $pdo->prepare("SELECT id, name FROM user_models WHERE user_id = ? ORDER BY name");
        $stmt->execute([$_SESSION['user_id']]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Models error: ' . $e->getMessage()]);
    }
}

function getPairsLookup($pdo) {
    try {
        // ИСПРАВЛЕНО
        $stmt = $pdo->prepare("SELECT id, symbol, type FROM user_pairs WHERE user_id = ? ORDER BY symbol");
        $stmt->execute([$_SESSION['user_id']]);
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
        // ИСПРАВЛЕНО
        $stmt = $pdo->prepare("SELECT id, name FROM user_styles WHERE user_id = ? ORDER BY name");
        $stmt->execute([$_SESSION['user_id']]);
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
        echo json_encode(['success' => false, 'message' => 'Plans lookup error: ' . $e->getMessage()]);
    }
}