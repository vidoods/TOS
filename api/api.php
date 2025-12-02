<?php
// api/api.php - Единая точка входа для всех запросов
session_start();
header('Content-Type: application/json');
require 'db.php'; // Ваш файл db.php с подключением $pdo
date_default_timezone_set('Europe/Moscow');

// Включите это ТОЛЬКО для отладки. В продакшене закомментируйте.
ini_set('display_errors', 0); // Отключаем прямой вывод ошибок в HTML, они пойдут в JSON
error_reporting(E_ALL);

// Проверка наличия расширения fileinfo
if (!extension_loaded('fileinfo')) {
    error_log("PHP extension 'fileinfo' is not loaded. Image uploads/downloads might not work correctly.");
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Проверка авторизации для всех действий, кроме входа, выхода и регистрации
if (!isset($_SESSION['user_id']) && $action !== 'login' && $action !== 'logout' && $action !== 'register') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация.']);
    exit;
}

// Переменная $pdo доступна из db.php
$conn = $pdo;

switch ($action) {
    case 'login':
        handleLogin($conn);
        break;
    case 'logout':
        handleLogout();
        break;
    case 'register':
        handleRegister($conn);
        break;

    // --- ОБЩИЕ СПРАВОЧНЫЕ ДАННЫЕ ---
    case 'get_lookups':
        getLookups($conn);
        break;
    case 'get_ref_pairs':
        getRefPairs($conn);
        break;
    case 'get_accounts_lookup':
        getAccountsLookup($conn);
        break;
    case 'get_ref_styles':
        getRefStyles($conn);
        break;
    case 'get_plans_for_lookup':
        getPlansForLookup($conn);
        break;

    // --- ОПЕРАЦИИ С ПЛАНАМИ ---
    case 'save_plan':
    case 'create_plan':
    case 'update_plan':
        savePlan($conn);
        break;
    case 'get_plans':
        getPlans($conn);
        break;
    case 'get_plan_details':
        getPlanDetails($conn);
        break;
    case 'delete_plan':
        deletePlan($conn);
        break;

    // --- ОПЕРАЦИИ СО СДЕЛКАМИ ---
    case 'get_trades':
        getTrades($conn);
        break;
    case 'get_trade_details':
        getTradeDetails($conn);
        break;
    
    // Создание и обновление сделок
    case 'save_trade':
    case 'create_trade':
    case 'update_trade':
        saveTrade($conn);
        break;
        
    case 'delete_trade':
        deleteTrade($conn);
        break;
    case 'get_dashboard_metrics':
        getDashboardMetrics($conn);
        break;

    // --- ЗАГРУЗКА/СКАЧИВАНИЕ ИЗОБРАЖЕНИЙ ---
    case 'upload_image':
        uploadImage();
        break;
    case 'download_image_from_url':
        downloadImageFromUrl();
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Неизвестное действие: ' . $action]);
        break;
		
	// --- ЗАМЕТКИ ---
    case 'get_notes': getNotes($conn); break;
    case 'get_note_details': getNoteDetails($conn); break;
    case 'save_note': saveNote($conn); break;
    case 'delete_note': deleteNote($conn); break;
}

// ==============================================================================================
// ФУНКЦИИ АВТОРИЗАЦИИ
// ==============================================================================================

function handleRegister($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Логин и пароль обязательны.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Пользователь с таким логином уже существует.']);
            exit;
        }

        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
        $stmt->execute([$username, $password_hash]);

        $_SESSION['user_id'] = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'Регистрация успешна.']);

    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка регистрации: ' . $e->getMessage()]);
    }
}

function handleLogin($pdo) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Заполните логин и пароль.']);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Неверный логин или пароль.']);
        }
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка входа: ' . $e->getMessage()]);
    }
}

function handleLogout() {
    session_destroy();
    echo json_encode(['success' => true]);
}

// ==============================================================================================
// ФУНКЦИИ СПРАВОЧНЫХ ДАННЫХ
// ==============================================================================================

function getLookups($pdo) {
    try {
        $user_id = $_SESSION['user_id'];
        $results = [];

        $stmt_pairs = $pdo->query("SELECT id, symbol, type FROM ref_pairs ORDER BY symbol ASC");
        $results['pairs'] = $stmt_pairs->fetchAll();

        $stmt_accounts = $pdo->prepare("SELECT id, name, type, balance FROM accounts WHERE user_id = :user_id ORDER BY name ASC");
        $stmt_accounts->execute(['user_id' => $user_id]);
        $results['accounts'] = $stmt_accounts->fetchAll();

        $stmt_styles = $pdo->query("SELECT id, name FROM ref_styles ORDER BY name ASC");
        $results['styles'] = $stmt_styles->fetchAll();
        
        $stmt_models = $pdo->query("SELECT id, name FROM ref_models ORDER BY name ASC");
        $results['models'] = $stmt_models->fetchAll();

        $stmt_plans = $pdo->prepare("SELECT id, title, date FROM plans WHERE user_id = :user_id ORDER BY date DESC");
        $stmt_plans->execute(['user_id' => $user_id]);
        $results['plans'] = $stmt_plans->fetchAll();
		
		$results['notes'] = $pdo->query("SELECT id, title FROM notes WHERE user_id = $user_id ORDER BY created_at DESC")->fetchAll();

        // --- ДОБАВЛЕНО: Загрузка списка сделок для заметок ---
        // Формируем красивое название прямо в SQL: "ДД.ММ.ГГ - ПАРА (Направление)"
        $stmt_trades = $pdo->prepare("
            SELECT t.id, 
                   CONCAT(DATE_FORMAT(t.entry_date, '%d.%m.%y'), ' - ', rp.symbol, ' (', UCASE(t.direction), ')') as display_name 
            FROM trades t 
            JOIN ref_pairs rp ON t.pair_id = rp.id 
            WHERE t.user_id = :user_id 
            ORDER BY t.entry_date DESC 
            LIMIT 50
        ");
        $stmt_trades->execute(['user_id' => $user_id]);
        $results['trades'] = $stmt_trades->fetchAll();
        // ----------------------------------------------------

        $results['trade_statuses'] = ['pending', 'win', 'loss', 'breakeven', 'partial', 'cancelled'];
        $results['trade_directions'] = ['long', 'short'];
        $results['plan_types'] = ['Daily', 'Weekly', 'Monthly', 'Long Term'];
        $results['plan_biases'] = ['Bullish', 'Bearish', 'Neutral'];
        $results['plan_statuses'] = ['pending', 'completed', 'cancelled'];
        $results['entry_timeframes'] = ['1m', '5m', '15m', '30m', '1h', '4h', '1D', '1W'];

        echo json_encode(['success' => true, 'data' => $results]);

    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка получения справочников: ' . $e->getMessage()]);
    }
}

function getRefPairs($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, symbol, type FROM ref_pairs ORDER BY symbol");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка пар: ' . $e->getMessage()]);
    }
}

function getAccountsLookup($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT id, name, type FROM accounts WHERE user_id = ? ORDER BY name");
        $stmt->execute([$_SESSION['user_id']]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка счетов: ' . $e->getMessage()]);
    }
}

function getRefStyles($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, name FROM ref_styles ORDER BY name");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка стилей: ' . $e->getMessage()]);
    }
}

function getPlansForLookup($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT id, title, date FROM plans WHERE user_id = ? ORDER BY date DESC");
        $stmt->execute([$_SESSION['user_id']]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка планов: ' . $e->getMessage()]);
    }
}

// ==============================================================================================
// ФУНКЦИИ ПЛАНОВ
// ==============================================================================================

function savePlan($pdo) {
    try {
        $user_id = $_SESSION['user_id'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['title']) || empty($data['pair_id']) || empty($data['date'])) {
            throw new Exception("Заполните обязательные поля: название, пара, дата.");
        }

        $plan_id = $data['id'] ?? null;
        $is_update = !empty($plan_id);

        $pdo->beginTransaction();

        if ($is_update) {
            $checkStmt = $pdo->prepare("SELECT id FROM plans WHERE id = ? AND user_id = ?");
            $checkStmt->execute([$plan_id, $user_id]);
            if (!$checkStmt->fetch()) { throw new Exception('План не найден или нет прав.'); }

            $sql = "UPDATE plans SET title=?, pair_id=?, date=?, bias=?, type=?, status=?, review_q1=?, review_q2=?, review_comments=? WHERE id=? AND user_id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['title'], $data['pair_id'], $data['date'], $data['bias'] ?? 'Neutral', $data['type'] ?? 'Weekly',
                $data['status'] ?? 'pending', $data['review_q1'] ?? 0, $data['review_q2'] ?? 0, $data['review_comments'] ?? null,
                $plan_id, $user_id
            ]);
            
            $pdo->prepare("DELETE FROM trade_analysis_images WHERE trade_id = ? AND is_plan_image = 1")->execute([$plan_id]);
            $message = 'План обновлен!';
        } else {
            $sql = "INSERT INTO plans (user_id, title, pair_id, date, bias, type, status, review_q1, review_q2, review_comments) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $user_id, $data['title'], $data['pair_id'], $data['date'], $data['bias'] ?? 'Neutral', $data['type'] ?? 'Weekly',
                $data['status'] ?? 'pending', $data['review_q1'] ?? 0, $data['review_q2'] ?? 0, $data['review_comments'] ?? null
            ]);
            $plan_id = $pdo->lastInsertId();
            $message = 'План создан!';
        }
		
		$pdo->prepare("DELETE FROM note_to_plan WHERE plan_id = ?")->execute([$plan_id]);
        if (!empty($data['note_id'])) {
            $pdo->prepare("INSERT INTO note_to_plan (note_id, plan_id) VALUES (?, ?)")->execute([$data['note_id'], $plan_id]);
        }

        if (!empty($data['timeframes']) && is_array($data['timeframes'])) {
            $tf_stmt = $pdo->prepare("INSERT INTO trade_analysis_images (trade_id, image_url, notes, title, is_plan_image) VALUES (?, ?, ?, ?, 1)");
            foreach ($data['timeframes'] as $index => $tf) {
                if (!empty($tf['url'])) {
                    $tf_stmt->execute([$plan_id, $tf['url'], $tf['notes'] ?? null, $tf['title'] ?? ('Снимок ' . ($index + 1))]);
                }
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => $message, 'id' => $plan_id]);

    } catch (\Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка сохранения плана: ' . $e->getMessage()]);
    }
}

function getPlans($pdo) {
    try {
        $user_id = $_SESSION['user_id'];
        $sql = "SELECT p.*, rp.symbol as pair_symbol FROM plans p LEFT JOIN ref_pairs rp ON p.pair_id = rp.id WHERE p.user_id = :user_id";
        $params = ['user_id' => $user_id];

        if (!empty($_GET['pair_id'])) { $sql .= " AND p.pair_id = :pair_id"; $params['pair_id'] = $_GET['pair_id']; }
        if (!empty($_GET['type'])) { $sql .= " AND p.type = :type"; $params['type'] = $_GET['type']; }
        if (!empty($_GET['bias'])) { $sql .= " AND p.bias = :bias"; $params['bias'] = $_GET['bias']; }
        if (!empty($_GET['status'])) { $sql .= " AND p.status = :status"; $params['status'] = $_GET['status']; }

        $sql .= " ORDER BY p.date DESC, p.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $plans = $stmt->fetchAll();

        $groupedPlans = [];
        foreach ($plans as $plan) {
            $monthYear = date('Y-m', strtotime($plan['date']));
            if (!isset($groupedPlans[$monthYear])) {
                 $groupedPlans[$monthYear] = ['month_label' => date('F Y', strtotime($plan['date'])), 'plans' => []];
            }
            $groupedPlans[$monthYear]['plans'][] = $plan;
        }
        echo json_encode(['success' => true, 'data' => array_values($groupedPlans)]);

    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка получения планов: ' . $e->getMessage()]);
    }
}

function getPlanDetails($pdo) {
    try {
        $plan_id = $_GET['id'] ?? null;
        if (!$plan_id) throw new Exception('ID не указан.');

        $stmt = $pdo->prepare("SELECT p.*, rp.symbol as pair_symbol, rp.type as pair_type FROM plans p LEFT JOIN ref_pairs rp ON p.pair_id = rp.id WHERE p.id = ? AND p.user_id = ?");
        $stmt->execute([$plan_id, $_SESSION['user_id']]);
        $plan = $stmt->fetch();

        if (!$plan) { http_response_code(404); throw new Exception('План не найден.'); }
        
        $stmt_images = $pdo->prepare("SELECT id, image_url, notes, title FROM trade_analysis_images WHERE trade_id = ? AND is_plan_image = 1 ORDER BY id ASC");
        $stmt_images->execute([$plan_id]);
        $plan['timeframes'] = $stmt_images->fetchAll();
        
        $plan['formatted_date'] = date('d F Y', strtotime($plan['date']));
        $plan['formatted_created_at'] = date('d F Y H:i', strtotime($plan['created_at']));
		
		$note = $pdo->query("SELECT n.id, n.title FROM note_to_plan np JOIN notes n ON np.note_id = n.id WHERE np.plan_id = $plan_id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $plan['note_id'] = $note['id'] ?? null;
        $plan['note_title'] = $note['title'] ?? null;
            
        echo json_encode(['success' => true, 'data' => $plan]);

    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка деталей плана: ' . $e->getMessage()]);
    }
}

function deletePlan($pdo) {
    try {
        $plan_id = $_POST['plan_id'] ?? null;
        if (!$plan_id) throw new Exception('ID не указан.');

        $user_id = $_SESSION['user_id'];
        $pdo->beginTransaction();

        $checkStmt = $pdo->prepare("SELECT id FROM plans WHERE id = ? AND user_id = ?");
        $checkStmt->execute([$plan_id, $user_id]);
        if (!$checkStmt->fetch()) throw new Exception('План не найден или нет прав.');

        $stmt_get_images = $pdo->prepare("SELECT image_url FROM trade_analysis_images WHERE trade_id = ? AND is_plan_image = 1");
        $stmt_get_images->execute([$plan_id]);
        $images = $stmt_get_images->fetchAll();

        $pdo->prepare("DELETE FROM trade_analysis_images WHERE trade_id = ? AND is_plan_image = 1")->execute([$plan_id]);
        $stmtPlan = $pdo->prepare("DELETE FROM plans WHERE id = ? AND user_id = ?");
        $stmtPlan->execute([$plan_id, $user_id]);

        if ($stmtPlan->rowCount() > 0) {
            $pdo->commit();
            foreach ($images as $img) {
                $filePath = '../' . $img['image_url'];
                if (file_exists($filePath)) unlink($filePath);
            }
            echo json_encode(['success' => true, 'message' => 'План удален.']);
        } else {
            throw new Exception('Не удалось удалить план.');
        }

    } catch (\Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка удаления плана: ' . $e->getMessage()]);
    }
}

// ==============================================================================================
// ФУНКЦИИ СДЕЛОК
// ==============================================================================================

function getTrades($pdo) {
    try {
        $user_id = $_SESSION['user_id'];
        $filters = $_GET;

        $query = "SELECT t.*, rp.symbol AS pair_symbol, a.name AS account_name, p.title AS plan_title
                  FROM trades t
                  JOIN ref_pairs rp ON t.pair_id = rp.id
                  JOIN accounts a ON t.account_id = a.id
                  LEFT JOIN plans p ON t.plan_id = p.id
                  WHERE t.user_id = :user_id";
        $params = [':user_id' => $user_id];

        if (!empty($filters['pair_id'])) { $query .= " AND t.pair_id = :pair_id"; $params[':pair_id'] = $filters['pair_id']; }
        if (!empty($filters['status'])) { $query .= " AND t.status = :status"; $params[':status'] = $filters['status']; }
        if (!empty($filters['direction'])) { $query .= " AND t.direction = :direction"; $params[':direction'] = $filters['direction']; }
        if (!empty($filters['month'])) { $query .= " AND DATE_FORMAT(t.entry_date, '%Y-%m') = :month"; $params[':month'] = $filters['month']; }
        if (!empty($filters['account_id'])) { $query .= " AND t.account_id = :account_id"; $params[':account_id'] = $filters['account_id']; }

        $query .= " ORDER BY t.entry_date DESC, t.id DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $trades = $stmt->fetchAll();

        $groupedTrades = [];
        foreach ($trades as $trade) {
            $monthKey = date('Y-m', strtotime($trade['entry_date']));
            if (!isset($groupedTrades[$monthKey])) {
                $groupedTrades[$monthKey] = [
                    'month_label' => date('F Y', strtotime($trade['entry_date'])),
                    'trades' => [], 'total_pnl' => 0.0, 'total_rr' => 0.0
                ];
            }
            $groupedTrades[$monthKey]['trades'][] = $trade;
            $groupedTrades[$monthKey]['total_pnl'] += (float)$trade['pnl'];
            $groupedTrades[$monthKey]['total_rr'] += (float)$trade['rr_achieved'];
        }
        echo json_encode(['success' => true, 'data' => array_values($groupedTrades)]);

    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка получения сделок: ' . $e->getMessage()]);
    }
}

function getTradeDetails($pdo) {
    try {
        $trade_id = $_GET['id'] ?? null;
        if (!$trade_id) throw new Exception('ID не указан.');

        // ИСПРАВЛЕНО: Добавлен LEFT JOIN ref_models и выборка rm.name AS model_name
        $query = "SELECT t.*, 
                         rp.symbol AS pair_symbol, rp.type AS pair_type, 
                         a.name AS account_name, a.type AS account_type, 
                         p.title AS plan_title, p.date AS plan_date, 
                         rs.name AS style_name,
                         rm.name AS model_name  
                  FROM trades t
                  JOIN ref_pairs rp ON t.pair_id = rp.id
                  JOIN accounts a ON t.account_id = a.id
                  LEFT JOIN plans p ON t.plan_id = p.id
                  LEFT JOIN ref_styles rs ON t.style_id = rs.id
                  LEFT JOIN ref_models rm ON t.model_id = rm.id
                  WHERE t.id = ? AND t.user_id = ?";
                  
        $stmt = $pdo->prepare($query);
        $stmt->execute([$trade_id, $_SESSION['user_id']]);
        $trade = $stmt->fetch();

        if (!$trade) { http_response_code(404); throw new Exception('Сделка не найдена.'); }

        $stmt_images = $pdo->prepare("SELECT id, image_url, notes, title FROM trade_analysis_images WHERE trade_id = ? AND is_plan_image = 0 ORDER BY id ASC");
        $stmt_images->execute([$trade_id]);
        $trade['trade_images'] = $stmt_images->fetchAll();
		
		$note = $pdo->query("SELECT n.id, n.title FROM note_to_trade nt JOIN notes n ON nt.note_id = n.id WHERE nt.trade_id = $trade_id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $trade['note_id'] = $note['id'] ?? null;
        $trade['note_title'] = $note['title'] ?? null;

        echo json_encode(['success' => true, 'data' => $trade]);

    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка деталей сделки: ' . $e->getMessage()]);
    }
}

// *** ИСПРАВЛЕННАЯ ФУНКЦИЯ СОХРАНЕНИЯ СДЕЛКИ ***

function saveTrade($pdo) {
    try {
        $user_id = $_SESSION['user_id'];
        $data = json_decode(file_get_contents('php://input'), true);

        foreach (['pair_id', 'account_id', 'entry_date', 'direction', 'risk_percent'] as $field) {
            if (empty($data[$field])) throw new Exception("Поле $field обязательно.");
        }

        $trade_id = $data['id'] ?? null;
        $is_update = !empty($trade_id);

        $pdo->beginTransaction();

        $params = [
            $data['pair_id'], 
            $data['account_id'], 
            !empty($data['plan_id']) ? $data['plan_id'] : null, 
            !empty($data['style_id']) ? $data['style_id'] : null,
            !empty($data['model_id']) ? $data['model_id'] : null, 
            $data['entry_date'], 
            $data['exit_date'] ?? null, 
            $data['direction'],
            $data['risk_percent'], 
            $data['rr_achieved'] ?? null, 
            $data['pnl'] ?? null, 
            $data['status'] ?? 'pending',
            $data['trade_conclusions'] ?? null, 
            $data['key_lessons'] ?? null, 
            $data['entry_timeframe'] ?? null, 
            $data['notes'] ?? null,
            $data['tags'] ?? null,
            $data['mistakes_made'] ?? null,
            $data['emotional_state'] ?? null,
            // reason_for_entry УДАЛЕНО
            $user_id
        ];

        if ($is_update) {
            $check = $pdo->prepare("SELECT id FROM trades WHERE id = ? AND user_id = ?");
            $check->execute([$trade_id, $user_id]);
            if (!$check->fetch()) throw new Exception('Сделка не найдена или нет прав.');

            // Убрано reason_for_entry=? из SQL
            $sql = "UPDATE trades SET pair_id=?, account_id=?, plan_id=?, style_id=?, model_id=?, entry_date=?, exit_date=?, direction=?, risk_percent=?, rr_achieved=?, pnl=?, status=?, trade_conclusions=?, key_lessons=?, entry_tf=?, notes=?, tags=?, mistakes_made=?, emotional_state=? WHERE id=? AND user_id=?";
            
            $update_params = array_slice($params, 0, count($params) - 1); 
            $update_params[] = $trade_id; 
            $update_params[] = $user_id; 
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($update_params);
            
            $pdo->prepare("DELETE FROM trade_analysis_images WHERE trade_id = ? AND is_plan_image = 0")->execute([$trade_id]);
            $message = 'Сделка обновлена!';
        } else {
            // Убрано reason_for_entry из списка полей и один знак ? из VALUES
            $sql = "INSERT INTO trades (pair_id, account_id, plan_id, style_id, model_id, entry_date, exit_date, direction, risk_percent, rr_achieved, pnl, status, trade_conclusions, key_lessons, entry_tf, notes, tags, mistakes_made, emotional_state, user_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            
            $insert_params = array_slice($params, 0, count($params) - 1);
            $insert_params[] = $user_id; 

            $stmt = $pdo->prepare($sql);
            $stmt->execute($insert_params);
            $trade_id = $pdo->lastInsertId();
            $message = 'Сделка создана!';
        }
		
		$pdo->prepare("DELETE FROM note_to_trade WHERE trade_id = ?")->execute([$trade_id]);
        if (!empty($data['note_id'])) {
            $pdo->prepare("INSERT INTO note_to_trade (note_id, trade_id) VALUES (?, ?)")->execute([$data['note_id'], $trade_id]);
        }

        if (!empty($data['trade_images']) && is_array($data['trade_images'])) {
            $img_stmt = $pdo->prepare("INSERT INTO trade_analysis_images (trade_id, image_url, notes, title, is_plan_image) VALUES (?, ?, ?, ?, 0)");
            foreach ($data['trade_images'] as $i => $img) {
                if (!empty($img['url'])) {
                    $img_stmt->execute([$trade_id, $img['url'], $img['notes'] ?? null, $img['title'] ?? ('Снимок ' . ($i + 1))]);
                }
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => $message, 'id' => $trade_id]);

    } catch (\Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка сохранения сделки: ' . $e->getMessage()]);
    }
}

function deleteTrade($pdo) {
    try {
        $trade_id = $_POST['trade_id'] ?? null;
        if (!$trade_id) throw new Exception('ID не указан.');

        $user_id = $_SESSION['user_id'];
        $pdo->beginTransaction();

        $check = $pdo->prepare("SELECT id FROM trades WHERE id = ? AND user_id = ?");
        $check->execute([$trade_id, $user_id]);
        if (!$check->fetch()) throw new Exception('Сделка не найдена или нет прав.');

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
            echo json_encode(['success' => true, 'message' => 'Сделка удалена.']);
        } else {
            throw new Exception('Не удалось удалить сделку.');
        }

    } catch (\Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка удаления сделки: ' . $e->getMessage()]);
    }
}

function getDashboardMetrics($pdo) {
    try {
        $user_id = $_SESSION['user_id'];
        $metrics = [];

        $metrics['total_trades'] = $pdo->query("SELECT COUNT(id) FROM trades WHERE user_id = $user_id")->fetchColumn();
        $metrics['total_pnl'] = (float)$pdo->query("SELECT SUM(pnl) FROM trades WHERE user_id = $user_id AND status IN ('win', 'loss', 'breakeven', 'partial')")->fetchColumn();
        $metrics['total_rr'] = (float)$pdo->query("SELECT SUM(rr_achieved) FROM trades WHERE user_id = $user_id AND status IN ('win', 'loss', 'breakeven', 'partial')")->fetchColumn();
        
        $wins = $pdo->query("SELECT COUNT(id) FROM trades WHERE user_id = $user_id AND status = 'win'")->fetchColumn();
        $closed = $pdo->query("SELECT COUNT(id) FROM trades WHERE user_id = $user_id AND status IN ('win', 'loss', 'breakeven', 'partial')")->fetchColumn();
        $metrics['win_rate'] = ($closed > 0) ? round(($wins / $closed) * 100, 2) : 0;
        $metrics['avg_rr_per_trade'] = ($closed > 0) ? round($metrics['total_rr'] / $closed, 2) : 0;
        
        // --- РАСЧЕТ СРЕДНЕЙ ДЛИТЕЛЬНОСТИ ---
        // Рассчитываем сумму всех длительностей (в секундах) для закрытых сделок
        $stmt_duration = $pdo->prepare("
            SELECT AVG(TIMESTAMPDIFF(SECOND, entry_date, exit_date)) AS avg_duration_seconds,
                   COUNT(id) AS closed_count
            FROM trades 
            WHERE user_id = :user_id AND status IN ('win', 'loss', 'breakeven', 'partial') AND exit_date IS NOT NULL
        ");
        $stmt_duration->execute(['user_id' => $user_id]);
        $duration_data = $stmt_duration->fetch();
        
        $avg_seconds = round($duration_data['avg_duration_seconds'] ?? 0);
        
        // Преобразование секунд в формат "Xд Yч Zмин"
        $days = floor($avg_seconds / (3600 * 24));
        $hours = floor(($avg_seconds % (3600 * 24)) / 3600);
        $minutes = floor(($avg_seconds % 3600) / 60);
        
        $duration_text = '';
        if ($days > 0) $duration_text .= "{$days}д ";
        if ($hours > 0) $duration_text .= "{$hours}ч ";
        if ($minutes > 0) $duration_text .= "{$minutes}мин";
        
        $metrics['avg_time_in_position'] = trim($duration_text) ?: 'N/A';
        // --- КОНЕЦ РАСЧЕТА СРЕДНЕЙ ДЛИТЕЛЬНОСТИ ---

        echo json_encode(['success' => true, 'data' => $metrics]);

    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка метрик: ' . $e->getMessage()]);
    }
}

// ==============================================================================================
// ФУНКЦИИ ИЗОБРАЖЕНИЙ
// ==============================================================================================

function uploadImage() {
    uploadOrDownloadImage(false);
}

function downloadImageFromUrl() {
    uploadOrDownloadImage(true);
}

function uploadOrDownloadImage($isDownload) {
    try {
        if (!isset($_SESSION['user_id'])) throw new Exception('Нужна авторизация.');
        
        // 1. Получаем тип (папку) из запроса
        $type = $_POST['type'] ?? 'general';
        
        // 2. БЕЗОПАСНОСТЬ: Разрешаем только определенные папки
        $allowedTypes = ['general', 'notes', 'trades', 'plans'];
        if (!in_array($type, $allowedTypes)) {
            $type = 'general'; // Если прислали что-то левое, кидаем в general
        }

        // 3. Формируем путь
        $uploadDir = "../assets/uploads/images/$type/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        if ($isDownload) {
            $data = json_decode(file_get_contents('php://input'), true);
            $url = $data['image_url'] ?? ''; // Исправлено получение URL для JSON запроса
            
            // Если тип передали в JSON (для скачивания по URL)
            if (isset($data['type']) && in_array($data['type'], $allowedTypes)) {
                $uploadDir = "../assets/uploads/images/" . $data['type'] . "/";
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
            }

            if (!filter_var($url, FILTER_VALIDATE_URL)) throw new Exception('Некорректный URL.');
            
            $content = @file_get_contents($url);
            if ($content === false) throw new Exception('Не удалось скачать изображение.');
            
            $tmpPath = tempnam(sys_get_temp_dir(), 'img');
            file_put_contents($tmpPath, $content);
        } else {
            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) throw new Exception('Ошибка загрузки файла.');
            $tmpPath = $_FILES['image']['tmp_name'];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpPath);
        $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        
        if (!isset($allowedMimes[$mime])) { 
            unlink($tmpPath); 
            throw new Exception('Недопустимый тип файла.'); 
        }

        $filename = uniqid('img_') . '.' . $allowedMimes[$mime];
        $dest = $uploadDir . $filename;
        
        if (compressAndSaveImage($tmpPath, $dest, $mime)) {
            if ($isDownload) unlink($tmpPath);
            // Возвращаем путь без ../ для использования на фронтенде
            $webPath = str_replace('../', '', $dest);
            echo json_encode(['success' => true, 'url' => $webPath]);
        } else {
            unlink($tmpPath); 
            throw new Exception('Ошибка сохранения.');
        }

    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function compressAndSaveImage($source, $dest, $mime, $quality = 80) {
    if (!extension_loaded('gd')) return copy($source, $dest);
    
    $img = match ($mime) {
        'image/jpeg' => imagecreatefromjpeg($source),
        'image/png' => imagecreatefrompng($source),
        'image/gif' => imagecreatefromgif($source),
        'image/webp' => imagecreatefromwebp($source),
        default => null
    };
    
    if (!$img) return false;
    
    $res = match ($mime) {
        'image/png' => (imagealphablending($img, false) && imagesavealpha($img, true) && imagepng($img, $dest, 9 - round($quality/10))),
        'image/gif' => imagegif($img, $dest),
        'image/webp' => imagewebp($img, $dest, $quality),
        default => imagejpeg($img, $dest, $quality)
    };
    
    imagedestroy($img);
    return $res;
}

// ==============================================================================================
// ФУНКЦИИ ЗАМЕТОК
// ==============================================================================================

function getNotes($pdo) {
    $uid = $_SESSION['user_id'];
    try {
        $notes = $pdo->query("SELECT * FROM notes WHERE user_id = $uid ORDER BY created_at DESC")->fetchAll();
        foreach ($notes as &$note) {
            $nid = $note['id'];
            $time = strtotime($note['created_at']);
            $note['date_formatted'] = date('d.m.y', $time);
            $note['day'] = date('l', $time);
            $note['week'] = 'Week #' . date('W', $time);
            
            // Связи (Счетчики)
            $tr = $pdo->query("SELECT COUNT(*) FROM note_to_trade WHERE note_id=$nid")->fetchColumn();
            $pl = $pdo->query("SELECT COUNT(*) FROM note_to_plan WHERE note_id=$nid")->fetchColumn();
            $links = [];
            if ($tr > 0) $links[] = "$tr Trades";
            if ($pl > 0) $links[] = "$pl Plans";
            $note['relations'] = empty($links) ? 'No Links' : implode(' / ', $links);

            // --- НОВОЕ: Расчет Latest Usage ---
            // Получаем дату самой свежей связанной сделки
            $lastTradeDate = $pdo->query("SELECT MAX(t.entry_date) FROM note_to_trade nt JOIN trades t ON nt.trade_id = t.id WHERE nt.note_id = $nid")->fetchColumn();
            // Получаем дату самого свежего связанного плана
            $lastPlanDate = $pdo->query("SELECT MAX(p.date) FROM note_to_plan np JOIN plans p ON np.plan_id = p.id WHERE np.note_id = $nid")->fetchColumn();
            
            $latestTimestamp = null;
            
            if ($lastTradeDate) {
                $latestTimestamp = strtotime($lastTradeDate);
            }
            
            if ($lastPlanDate) {
                $pTime = strtotime($lastPlanDate);
                // Если дата плана свежее сделки (или сделки нет), берем её
                if (!$latestTimestamp || $pTime > $latestTimestamp) {
                    $latestTimestamp = $pTime;
                }
            }
            
            // Формируем строку
            $note['latest_usage'] = $latestTimestamp ? date('d.m.y', $latestTimestamp) : 'Not Used';
            // ----------------------------------
        }
        echo json_encode(['success' => true, 'data' => $notes]);
    } catch (Exception $e) { 
        http_response_code(500); 
        echo json_encode(['success'=>false, 'message'=>$e->getMessage()]); 
    }
}

function getNoteDetails($pdo) {
    $id = $_GET['id']; $uid = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE id=? AND user_id=? LIMIT 1");
    $stmt->execute([$id, $uid]);
    $res = $stmt->fetch();
    
    if (!$res) { echo json_encode(['success'=>false]); return; }
    
    // Получаем данные о связанных сущностях (ID и Название)
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
    
    // Форматируем дату создания
    $res['created_formatted'] = date('d F Y, H:i', strtotime($res['created_at']));
    
    echo json_encode(['success' => true, 'data' => $res]);
}

function saveNote($pdo) {
    try {
        $uid = $_SESSION['user_id'];
        $d = json_decode(file_get_contents('php://input'), true);
        
        if (empty($d['title'])) throw new Exception('Заголовок обязателен');
        
        $id = $d['id'] ?? null;
        $pdo->beginTransaction();

        if ($id) {
            $sql = "UPDATE notes SET title = ?, content = ? WHERE id = ? AND user_id = ?";
            $pdo->prepare($sql)->execute([$d['title'], $d['content'] ?? '', $id, $uid]);
            
            // Обновляем связи (удаляем старые, добавляем новые)
            $pdo->prepare("DELETE FROM note_to_trade WHERE note_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM note_to_plan WHERE note_id = ?")->execute([$id]);
        } else {
            $sql = "INSERT INTO notes (user_id, title, content) VALUES (?, ?, ?)";
            $pdo->prepare($sql)->execute([$uid, $d['title'], $d['content'] ?? '']);
            $id = $pdo->lastInsertId();
        }

        // Добавляем новые связи
        if (!empty($d['trade_id'])) {
            $pdo->prepare("INSERT INTO note_to_trade (note_id, trade_id) VALUES (?, ?)")->execute([$id, $d['trade_id']]);
        }
        if (!empty($d['plan_id'])) {
            $pdo->prepare("INSERT INTO note_to_plan (note_id, plan_id) VALUES (?, ?)")->execute([$id, $d['plan_id']]);
        }

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
        $id = $_POST['id'] ?? null;
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
?>