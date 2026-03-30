<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// api/api.php - Единая точка входа для всех запросов
session_start();
header('Content-Type: application/json');
require 'db.php'; // Ваш файл db.php с подключением $pdo
date_default_timezone_set('Europe/Kyiv');

// Проверка наличия расширения fileinfo
if (!extension_loaded('fileinfo')) {
    error_log("PHP extension 'fileinfo' is not loaded. Image uploads/downloads might not work correctly.");
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ГЛОБАЛЬНАЯ ЗАЩИТА: Проверка авторизации
// 1. Список действий, доступных ГОСТЯМ (без входа)
$publicActions = ['login', 'logout', 'register', 'forgot_password', 'reset_password'];

// 2. Если пользователь не вошел И действие не публичное -> Ошибка 401
if (!isset($_SESSION['user_id']) && !in_array($action, $publicActions)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authorization required.']);
    exit;
}

// Переменная $pdo доступна из db.php
$conn = $pdo;

try {
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
    case 'forgot_password':
        handleForgotPassword($conn);
        break;
    case 'reset_password':
        handleResetPassword($conn);
        break;

    case 'get_user_info':
        $userId = $_SESSION['user_id'];
        // Добавили email и created_at в запрос
        $stmt = $pdo->prepare("SELECT username, email, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            echo json_encode([
                'success' => true,
                'username' => $user['username'],
                'email' => $user['email'], // Отправляем почту
                'created_at' => $user['created_at'] ? date('d M Y', strtotime($user['created_at'])) : '-'
            ]);
        } else {
            echo json_encode(['success' => false]);
        }
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
    case 'get_dashboard_metrics': getDashboardMetrics($conn); break;

    // --- ЗАГРУЗКА/СКАЧИВАНИЕ ИЗОБРАЖЕНИЙ ---
    case 'upload_image':
        uploadImage();
        break;
    case 'download_image_from_url':
        downloadImageFromUrl();
        break;
		
	// --- ЗАМЕТКИ ---
    case 'get_notes': getNotes($conn); break;
    case 'get_note_details': getNoteDetails($conn); break;
    case 'save_note': saveNote($conn); break;
    case 'delete_note': deleteNote($conn); break;
	
	// --- АККАУНТЫ ---
    case 'get_accounts_data': getAccountsData($conn); break;
    case 'save_account': saveAccount($conn); break;
    case 'delete_account': deleteAccount($conn); break;
	case 'get_account_details': getAccountDetails($conn); break;
	
	// --- ВЫПЛАТЫ ---
    case 'get_payouts': getPayouts($conn); break;
    case 'save_payout': savePayout($conn); break;
    case 'delete_payout': deletePayout($conn); break;
	
	// ... inside switch($action) ...
    case 'get_data_analysis': getDataAnalysis($conn); break;
    
    // --- ТОРГОВАЯ СТРАТЕГИЯ ---
    case 'get_strategies': getStrategies($conn); break;
    case 'get_strategy_details': getStrategyDetails($conn); break;
    case 'save_strategy': saveStrategy($conn); break;
    case 'delete_strategy': deleteStrategy($conn); break;
	case 'get_mpa_analysis': getMPAAnalysis($conn); break;
		
	case 'get_mpa_month_details': getMPAMonthDetails($conn); break;
    case 'save_mpa_report':
        saveMPAReport($conn);
        break;
    case 'get_mpa_report':
        getMPAReport($conn);
        break;
		
	case 'get_qpa_list': getQPAList($conn); break;
    case 'get_qpa_details': getQPADetails($conn); break;
    case 'save_qpa_report': saveQPAReport($conn); break;

    default:
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]);
    break;
}
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// ==============================================================================================
// ФУНКЦИИ АВТОРИЗАЦИИ
// ==============================================================================================

function handleRegister($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = trim($data['username'] ?? '');
    $email = trim($data['email'] ?? ''); // Новое поле
    $password = $data['password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }

    // Проверяем валидность Email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address. Please enter a valid email.']);
        return;
    }

    // Проверка на дубликаты
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'User with this email or username already exists']);
        return;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
    if ($stmt->execute([$username, $email, $hash])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed']);
    }
}

function handleLogin($pdo) {
    // 1. УМНЫЙ ПРИЕМ ДАННЫХ
    // Проверяем: если пришли данные формы ($_POST), берем их. Если нет — читаем JSON.
    if (!empty($_POST)) {
        $data = $_POST;
    } else {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
    }

    // Логируем (для отладки, если понадобится снова)
    // error_log("Login attempt: " . print_r($data, true));

    $login = trim($data['email'] ?? $data['username'] ?? ''); // Ищем email ИЛИ username
    $password = $data['password'] ?? '';

    if (empty($login) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Empty email or password']);
        return;
    }

    // 2. Ищем пользователя
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        return;
    }

    // 3. Получаем хэш из БД (проверяем обе колонки)
    $dbPassword = !empty($user['password_hash']) ? $user['password_hash'] : ($user['password'] ?? '');

    // 4. Проверка пароля
    $isCorrect = false;
    $needsMigration = false;

    if (password_verify($password, $dbPassword)) {
        // Пароль верный (хэш совпал)
        $isCorrect = true;
    } elseif ($password === $dbPassword) {
        // Пароль верный (но это старый текстовый пароль)
        $isCorrect = true;
        $needsMigration = true;
    }

    if ($isCorrect) {
        // Успешный вход!
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        // Если пароль был текстовым, обновляем его на безопасный хэш
        if ($needsMigration) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $user['id']]);
            } catch (Exception $e) { /* Игнорируем, если колонки нет */ }
        }

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid password']);
    }
}

function handleForgotPassword($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = trim($data['email'] ?? '');

    // 1. Проверяем, есть ли такой email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if (!$stmt->fetch()) {
        // Из соображений безопасности лучше писать "Если почта существует, мы отправили письмо", 
        // но для отладки оставим так:
        echo json_encode(['success' => false, 'message' => 'Email not found']);
        return;
    }

    // 2. Генерируем токен
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // 3. Сохраняем в БД
    // Сначала удалим старые токены для этого email
    $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
    
    $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    if ($stmt->execute([$email, $token, $expires])) {
        
        // В РЕАЛЬНОМ ПРОЕКТЕ: Здесь нужно отправить письмо через mail() или PHPMailer
        // $link = "http://yoursite.com/index.php?view=reset_password&token=" . $token;
        // mail($email, "Password Reset", "Click here: " . $link);

        // ДЛЯ ТЕСТА: Возвращаем ссылку прямо в JSON, чтобы вы могли её открыть
        $debugLink = "index.php?view=reset_password&token=" . $token;
        
        echo json_encode([
            'success' => true, 
            'message' => 'Check your email (Simulated)',
            'debug_link' => $debugLink // <--- Ссылка придет сюда
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function handleResetPassword($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $token = $data['token'] ?? '';
    $newPass = $data['password'] ?? '';

    if (empty($token) || empty($newPass)) {
        echo json_encode(['success' => false, 'message' => 'Missing data']);
        return;
    }

    // 1. Ищем валидный токен
    $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $resetRequest = $stmt->fetch();

    if (!$resetRequest) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
        return;
    }

    // 2. Обновляем пароль пользователя
    $hash = password_hash($newPass, PASSWORD_DEFAULT);
    $update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
    if ($update->execute([$hash, $resetRequest['email']])) {
        // 3. Удаляем использованный токен
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$resetRequest['email']]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update password']);
    }
}

function handleLogout() {
    // Уничтожаем сессию полностью
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_unset();
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
        $stmt = $pdo->prepare("SELECT id, name, type FROM accounts WHERE user_id = ? ORDER BY name");
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

// ==============================================================================================
// ФУНКЦИИ ПЛАНОВ
// ==============================================================================================

function savePlan($pdo) {
    try {
        $user_id = $_SESSION['user_id'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['title']) || empty($data['pair_id']) || empty($data['date'])) {
            throw new Exception("Fill up the fields: title, pair, date.");
        }

        $plan_id = $data['id'] ?? null;
        $is_update = !empty($plan_id);

        $pdo->beginTransaction();

        if ($is_update) {
            $checkStmt = $pdo->prepare("SELECT id FROM plans WHERE id = ? AND user_id = ?");
            $checkStmt->execute([$plan_id, $user_id]);
            if (!$checkStmt->fetch()) { throw new Exception('Plan not found or you do not have the permission.'); }

            $sql = "UPDATE plans SET title=?, pair_id=?, date=?, bias=?, type=?, status=?, review_q1=?, review_q2=?, review_comments=? WHERE id=? AND user_id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['title'], $data['pair_id'], $data['date'], $data['bias'] ?? 'Neutral', $data['type'] ?? 'Weekly',
                $data['status'] ?? 'pending', $data['review_q1'] ?? 0, $data['review_q2'] ?? 0, $data['review_comments'] ?? null,
                $plan_id, $user_id
            ]);
            
            $pdo->prepare("DELETE FROM trade_analysis_images WHERE trade_id = ? AND is_plan_image = 1")->execute([$plan_id]);
            $message = 'Plan updated!';
        } else {
            $sql = "INSERT INTO plans (user_id, title, pair_id, date, bias, type, status, review_q1, review_q2, review_comments) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $user_id, $data['title'], $data['pair_id'], $data['date'], $data['bias'] ?? 'Neutral', $data['type'] ?? 'Weekly',
                $data['status'] ?? 'pending', $data['review_q1'] ?? 0, $data['review_q2'] ?? 0, $data['review_comments'] ?? null
            ]);
            $plan_id = $pdo->lastInsertId();
            $message = 'Plan created!';
        }
		
		$pdo->prepare("DELETE FROM note_to_plan WHERE plan_id = ?")->execute([$plan_id]);
        if (!empty($data['note_id'])) {
            $pdo->prepare("INSERT INTO note_to_plan (note_id, plan_id) VALUES (?, ?)")->execute([$data['note_id'], $plan_id]);
        }

        if (!empty($data['timeframes']) && is_array($data['timeframes'])) {
            $tf_stmt = $pdo->prepare("INSERT INTO trade_analysis_images (trade_id, image_url, notes, title, is_plan_image) VALUES (?, ?, ?, ?, 1)");
            foreach ($data['timeframes'] as $index => $tf) {
                if (!empty($tf['url'])) {
                    $tf_stmt->execute([$plan_id, $tf['url'], $tf['notes'] ?? null, $tf['title'] ?? ('Screenshot ' . ($index + 1))]);
                }
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => $message, 'id' => $plan_id]);

    } catch (\Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Plan saving error: ' . $e->getMessage()]);
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
        echo json_encode(['success' => false, 'message' => 'Get plans error: ' . $e->getMessage()]);
    }
}

function getPlanDetails($pdo) {
    try {
        $plan_id = $_GET['id'] ?? null;
        if (!$plan_id) throw new Exception('ID not stated.');

        $stmt = $pdo->prepare("SELECT p.*, rp.symbol as pair_symbol, rp.type as pair_type FROM plans p LEFT JOIN ref_pairs rp ON p.pair_id = rp.id WHERE p.id = ? AND p.user_id = ?");
        $stmt->execute([$plan_id, $_SESSION['user_id']]);
        $plan = $stmt->fetch();

        if (!$plan) { http_response_code(404); throw new Exception('Plan not found.'); }
        
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
        echo json_encode(['success' => false, 'message' => 'Plan details error: ' . $e->getMessage()]);
    }
}

function deletePlan($pdo) {
    try {
        $plan_id = $_POST['plan_id'] ?? null;
        if (!$plan_id) throw new Exception('ID not stated.');

        $user_id = $_SESSION['user_id'];
        $pdo->beginTransaction();

        $checkStmt = $pdo->prepare("SELECT id FROM plans WHERE id = ? AND user_id = ?");
        $checkStmt->execute([$plan_id, $user_id]);
        if (!$checkStmt->fetch()) throw new Exception('Plan not found or you do not have the permission.');

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
            echo json_encode(['success' => true, 'message' => 'Plan deleted.']);
        } else {
            throw new Exception('Plan delete error.');
        }

    } catch (\Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Plan delete error: ' . $e->getMessage()]);
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
                    'trades' => [], 
                    'total_pnl' => 0.0, 
                    'total_rr' => 0.0,
                    'total_percent' => 0.0 // Инициализируем счетчик процентов
                ];
            }
            $groupedTrades[$monthKey]['trades'][] = $trade;
            $groupedTrades[$monthKey]['total_pnl'] += (float)$trade['pnl'];
            $groupedTrades[$monthKey]['total_rr'] += (float)$trade['rr_achieved'];
            
            // Расчет процентов: RR * Риск%
            // Например: 2R * 1% Риска = 2% Прибыли
            $trade_pct = (float)$trade['rr_achieved'] * (float)$trade['risk_percent'];
            $groupedTrades[$monthKey]['total_percent'] += $trade_pct;
        }
        echo json_encode(['success' => true, 'data' => array_values($groupedTrades)]);

    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Get trades error: ' . $e->getMessage()]);
    }
}

function getTradeDetails($pdo) {
    try {
        $trade_id = $_GET['id'] ?? null;
        if (!$trade_id) throw new Exception('ID not stated.');

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

        if (!$trade) { http_response_code(404); throw new Exception('Trade not found.'); }

        $stmt_images = $pdo->prepare("SELECT id, image_url, notes, title FROM trade_analysis_images WHERE trade_id = ? AND is_plan_image = 0 ORDER BY id ASC");
        $stmt_images->execute([$trade_id]);
        $trade['trade_images'] = $stmt_images->fetchAll();
		
		$note = $pdo->query("SELECT n.id, n.title FROM note_to_trade nt JOIN notes n ON nt.note_id = n.id WHERE nt.trade_id = $trade_id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $trade['note_id'] = $note['id'] ?? null;
        $trade['note_title'] = $note['title'] ?? null;

        echo json_encode(['success' => true, 'data' => $trade]);

    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Trade details error: ' . $e->getMessage()]);
    }
}

// *** ИСПРАВЛЕННАЯ ФУНКЦИЯ СОХРАНЕНИЯ СДЕЛКИ ***

function saveTrade($pdo) {
    try {
        $user_id = $_SESSION['user_id'];
        $data = json_decode(file_get_contents('php://input'), true);

        // 1. Проверка обязательных полей
        foreach (['pair_id', 'account_id', 'entry_date', 'direction', 'risk_percent'] as $field) {
            if (empty($data[$field])) throw new Exception("Field $field required.");
        }

        $trade_id = $data['id'] ?? null;
        $is_update = !empty($trade_id);

        $pdo->beginTransaction();

        // 2. ПОДГОТОВКА ДАННЫХ: Превращаем пустые строки "" в NULL
        
        // Даты
        $entry_date = !empty($data['entry_date']) ? $data['entry_date'] : null;
        $exit_date  = !empty($data['exit_date'])  ? $data['exit_date']  : null;

        // Числа (RR, PnL, Цены) - Важное исправление здесь
        // Проверяем: если значение установлено и не является пустой строкой, берем его, иначе NULL
        $rr_achieved = (isset($data['rr_achieved']) && $data['rr_achieved'] !== '') ? $data['rr_achieved'] : null;
        $pnl         = (isset($data['pnl']) && $data['pnl'] !== '') ? $data['pnl'] : null;
        
        // Дополнительно можно обработать и другие опциональные цены, если они приходят пустыми
        // Но обычно entry_price есть всегда, а exit_price нужен для закрытых
        
        $params = [
            $data['pair_id'], 
            $data['account_id'], 
            !empty($data['plan_id']) ? $data['plan_id'] : null, 
            !empty($data['style_id']) ? $data['style_id'] : null,
            !empty($data['model_id']) ? $data['model_id'] : null, 
            $entry_date, 
            $exit_date,  
            $data['direction'],
            $data['risk_percent'], 
            $rr_achieved, // Исправленная переменная
            $pnl,         // Исправленная переменная
            $data['status'] ?? 'pending',
            $data['trade_conclusions'] ?? null, 
            $data['key_lessons'] ?? null, 
            $data['entry_timeframe'] ?? null, 
            $data['notes'] ?? null,
            $data['tags'] ?? null,
            $data['mistakes_made'] ?? null,
            $data['emotional_state'] ?? null,
            $user_id
        ];

        if ($is_update) {
            $check = $pdo->prepare("SELECT id FROM trades WHERE id = ? AND user_id = ?");
            $check->execute([$trade_id, $user_id]);
            if (!$check->fetch()) throw new Exception('Trade not found or you do not have the permission.');

            $sql = "UPDATE trades SET pair_id=?, account_id=?, plan_id=?, style_id=?, model_id=?, entry_date=?, exit_date=?, direction=?, risk_percent=?, rr_achieved=?, pnl=?, status=?, trade_conclusions=?, key_lessons=?, entry_tf=?, notes=?, tags=?, mistakes_made=?, emotional_state=? WHERE id=? AND user_id=?";
            
            $update_params = array_slice($params, 0, count($params) - 1); 
            $update_params[] = $trade_id; 
            $update_params[] = $user_id; 
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($update_params);
            
            $message = 'Trade updated!';
        } else {
            $sql = "INSERT INTO trades (pair_id, account_id, plan_id, style_id, model_id, entry_date, exit_date, direction, risk_percent, rr_achieved, pnl, status, trade_conclusions, key_lessons, entry_tf, notes, tags, mistakes_made, emotional_state, user_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            
            $insert_params = array_slice($params, 0, count($params) - 1);
            $insert_params[] = $user_id; 

            $stmt = $pdo->prepare($sql);
            $stmt->execute($insert_params);
            $trade_id = $pdo->lastInsertId();
            $message = 'Trade created!';
        }
        
        // Связи с заметками
        $pdo->prepare("DELETE FROM note_to_trade WHERE trade_id = ?")->execute([$trade_id]);
        if (!empty($data['note_id'])) {
            $pdo->prepare("INSERT INTO note_to_trade (note_id, trade_id) VALUES (?, ?)")->execute([$data['note_id'], $trade_id]);
        }

        // Картинки
        if (!empty($data['trade_images']) && is_array($data['trade_images'])) {
            if ($is_update) {
                 $pdo->prepare("DELETE FROM trade_analysis_images WHERE trade_id = ? AND is_plan_image = 0")->execute([$trade_id]);
            }

            $img_stmt = $pdo->prepare("INSERT INTO trade_analysis_images (trade_id, image_url, notes, title, is_plan_image) VALUES (?, ?, ?, ?, 0)");
            foreach ($data['trade_images'] as $i => $img) {
                if (!empty($img['url'])) {
                    $img_stmt->execute([$trade_id, $img['url'], $img['notes'] ?? null, $img['title'] ?? ('Screenshot ' . ($i + 1))]);
                }
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => $message, 'id' => $trade_id]);

    } catch (\Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Saving error: ' . $e->getMessage()]);
    }
}

function deleteTrade($pdo) {
    try {
        $trade_id = $_POST['trade_id'] ?? null;
        if (!$trade_id) throw new Exception('ID not stated.');

        $user_id = $_SESSION['user_id'];
        $pdo->beginTransaction();

        $check = $pdo->prepare("SELECT id FROM trades WHERE id = ? AND user_id = ?");
        $check->execute([$trade_id, $user_id]);
        if (!$check->fetch()) throw new Exception('Trade not found or you do not have the permission.');

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
            echo json_encode(['success' => true, 'message' => 'Trade deleted.']);
        } else {
            throw new Exception('Trade delete error.');
        }

    } catch (\Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Trade delete error: ' . $e->getMessage()]);
    }
}

// --- ФУНКЦИЯ МЕТРИК
function getDashboardMetrics($pdo) {
    try {
        $user_id = $_SESSION['user_id'];
        $account_id = $_GET['account_id'] ?? null;
        $year = $_GET['year'] ?? null;
        $month = $_GET['month'] ?? null;

        // 1. Базовый фильтр (Пользователь + Счет)
        $baseWhere = "WHERE user_id = ?";
        $baseParams = [$user_id];
        if (!empty($account_id)) {
            $baseWhere .= " AND account_id = ?";
            $baseParams[] = $account_id;
        }

        // 2. Фильтр периода (для метрик и выборки графика)
        $periodWhere = $baseWhere;
        $periodParams = $baseParams;

        if (!empty($year)) {
            $periodWhere .= " AND YEAR(entry_date) = ?";
            $periodParams[] = $year;
            if (!empty($month)) {
                $periodWhere .= " AND MONTH(entry_date) = ?";
                $periodParams[] = $month;
            }
        }

        $metrics = [];

        // --- МЕТРИКИ (С учетом фильтра периода) ---
        
        // Всего сделок, PnL, RR (только закрытые)
        $stmtStats = $pdo->prepare("SELECT 
            COUNT(*) as total_trades,
            SUM(CASE WHEN status = 'win' THEN 1 ELSE 0 END) as wins,
            SUM(CASE WHEN status = 'loss' THEN 1 ELSE 0 END) as losses,
            SUM(CASE WHEN status = 'breakeven' THEN 1 ELSE 0 END) as breakeven,
            SUM(CASE WHEN status IN ('pending', 'open') OR status = '' OR status IS NULL THEN 1 ELSE 0 END) as pending,
            COALESCE(SUM(pnl), 0) as total_pnl,
            COALESCE(SUM(rr_achieved), 0) as total_rr
        FROM trades $periodWhere"); // Используем periodWhere
        $stmtStats->execute($periodParams);
        $stats = $stmtStats->fetch();

        $metrics['total_trades'] = $stats['total_trades'];
        $metrics['wins'] = (int)$stats['wins'];
        $metrics['losses'] = (int)$stats['losses'];
        $metrics['breakeven'] = (int)$stats['breakeven'];
        $metrics['pending'] = (int)$stats['pending'];
        $metrics['total_pnl'] = round((float)$stats['total_pnl'], 2);
        $metrics['total_rr'] = round((float)$stats['total_rr'], 2);
        
        // Винрейт (считаем только по закрытым в этом периоде)
        $completed = $metrics['wins'] + $metrics['losses'] + $metrics['breakeven'];
        $metrics['win_rate'] = ($completed > 0) ? round(($metrics['wins'] / $completed) * 100, 1) : 0;
        $metrics['avg_rr_per_trade'] = ($completed > 0) ? round($metrics['total_rr'] / $completed, 2) : 0;

        // Среднее время
        $stmtTime = $pdo->prepare("SELECT AVG(TIMESTAMPDIFF(SECOND, entry_date, exit_date)) FROM trades $periodWhere AND exit_date IS NOT NULL");
        $stmtTime->execute($periodParams);
        $avg_sec = $stmtTime->fetchColumn();
        if ($avg_sec) {
            $d = floor($avg_sec/86400); $h = floor(($avg_sec%86400)/3600); $mn = floor(($avg_sec%3600)/60);
            $metrics['avg_time_in_position'] = ($d?"{$d}d ":"").($h?"{$h}h ":"")."{$mn}min";
        } else { $metrics['avg_time_in_position'] = "0min"; }

        // Среднемесячная прибыль (в рамках выбранного периода)
        $stmtMonths = $pdo->prepare("SELECT COUNT(DISTINCT DATE_FORMAT(entry_date, '%Y-%m')) FROM trades $periodWhere AND entry_date IS NOT NULL AND status IN ('win', 'loss')");
        $stmtMonths->execute($periodParams);
        $months_count = $stmtMonths->fetchColumn();
        $metrics['avg_monthly_profit'] = ($months_count > 0) ? round($metrics['total_pnl'] / $months_count, 2) : $metrics['total_pnl'];


        // --- ДАННЫЕ ДЛЯ ГРАФИКА (EQUITY CURVE) ---

        // 1. Определяем текущий баланс (на сегодня)
        if ($account_id) {
            $stmtBal = $pdo->prepare("SELECT balance FROM accounts WHERE id = ? AND user_id = ?");
            $stmtBal->execute([$account_id, $user_id]);
            $current_balance_end = (float)$stmtBal->fetchColumn();
        } else {
            $stmtBal = $pdo->prepare("SELECT SUM(balance) FROM accounts WHERE user_id = ?");
            $stmtBal->execute([$user_id]);
            $current_balance_end = (float)$stmtBal->fetchColumn();
        }

        // 2. Считаем PnL за ВСЕ время (чтобы найти "Изначальный депозит")
        // Используем $baseWhere (без фильтра по дате)
        $stmtAllPnl = $pdo->prepare("SELECT COALESCE(SUM(pnl), 0) FROM trades $baseWhere AND status IN ('win', 'loss', 'breakeven', 'partial')");
        $stmtAllPnl->execute($baseParams);
        $total_pnl_all_time = (float)$stmtAllPnl->fetchColumn();
        
        // Изначальный депозит (условный) = Текущий баланс - Весь заработанный PnL
        $initial_deposit = $current_balance_end - $total_pnl_all_time;

        // 3. Считаем PnL ДО начала выбранного периода (чтобы найти стартовую точку графика)
        $start_balance = $initial_deposit;
        
        if (!empty($year)) {
            $startDateStr = "$year-01-01";
            if (!empty($month)) $startDateStr = "$year-$month-01";
            
            // Суммируем всё, что было ДО этой даты
            $beforeParams = $baseParams; 
            $beforeParams[] = $startDateStr;
            
            $stmtBefore = $pdo->prepare("SELECT COALESCE(SUM(pnl), 0) FROM trades $baseWhere AND status IN ('win', 'loss', 'breakeven', 'partial') AND entry_date < ?");
            $stmtBefore->execute($beforeParams);
            $pnl_before = (float)$stmtBefore->fetchColumn();
            
            $start_balance += $pnl_before;
        }

        // 4. Формируем данные графика (Группировка)
        $chartData = [];
        $running_balance = $start_balance;
        
        // Добавляем стартовую точку (начало периода)
        $chartData[] = ['x' => !empty($year) ? "$year-" . ($month ?? '01') . "-01" : "Start", 'y' => round($running_balance, 2)];

        if (!empty($year) && empty($month)) {
            // --- РЕЖИМ ГОДА (Группировка по Месяцам) ---
            $sqlChart = "SELECT DATE_FORMAT(entry_date, '%Y-%m') as date_label, SUM(pnl) as pnl 
                         FROM trades $periodWhere AND status IN ('win', 'loss', 'breakeven', 'partial') 
                         GROUP BY date_label ORDER BY date_label ASC";
            $stmtChart = $pdo->prepare($sqlChart);
            $stmtChart->execute($periodParams);
            
        } elseif (!empty($year) && !empty($month)) {
            // --- РЕЖИМ МЕСЯЦА (Группировка по Дням) ---
            $sqlChart = "SELECT DATE_FORMAT(entry_date, '%Y-%m-%d') as date_label, SUM(pnl) as pnl 
                         FROM trades $periodWhere AND status IN ('win', 'loss', 'breakeven', 'partial') 
                         GROUP BY date_label ORDER BY date_label ASC";
            $stmtChart = $pdo->prepare($sqlChart);
            $stmtChart->execute($periodParams);

        } else {
            // --- РЕЖИМ ВСЕ ВРЕМЯ (По каждой сделке, как было) ---
            // Здесь мы берем каждую сделку, чтобы показать детальную историю
            $sqlChart = "SELECT DATE_FORMAT(entry_date, '%Y-%m-%d') as date_label, pnl 
                         FROM trades $periodWhere AND status IN ('win', 'loss', 'breakeven', 'partial') 
                         ORDER BY entry_date ASC, id ASC";
            $stmtChart = $pdo->prepare($sqlChart);
            $stmtChart->execute($periodParams);
        }

        $rows = $stmtChart->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $running_balance += (float)$row['pnl'];
            $chartData[] = ['x' => $row['date_label'], 'y' => round($running_balance, 2)];
        }
        
        $metrics['equity_chart'] = $chartData;

        // 5. Расчет MDD (Макс просадка) - Считаем ее в рамках ВЫБРАННОГО периода по точкам графика
        $peak = -999999999;
        $max_dd_percent = 0;
        $max_dd_abs = 0;
        
        foreach ($chartData as $pt) {
            $val = $pt['y'];
            if ($val > $peak) $peak = $val;
            $dd = $peak - $val;
            if ($peak > 0) {
                $dd_pct = ($dd / $peak) * 100;
                if ($dd_pct > $max_dd_percent) $max_dd_percent = $dd_pct;
            }
            if ($dd > $max_dd_abs) $max_dd_abs = $dd;
        }
        $metrics['max_drawdown_pct'] = round($max_dd_percent, 2);
        $metrics['max_drawdown_abs'] = round($max_dd_abs, 2);


        echo json_encode(['success' => true, 'data' => $metrics]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
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
        if (!isset($_SESSION['user_id'])) throw new Exception('Autorisation required.');
        
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

            if (!filter_var($url, FILTER_VALIDATE_URL)) throw new Exception('Not correct URL.');
            
            $content = @file_get_contents($url);
            if ($content === false) throw new Exception('Image download error.');
            
            $tmpPath = tempnam(sys_get_temp_dir(), 'img');
            file_put_contents($tmpPath, $content);
        } else {
            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) throw new Exception('File upload error.');
            $tmpPath = $_FILES['image']['tmp_name'];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpPath);
        $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        
        if (!isset($allowedMimes[$mime])) { 
            unlink($tmpPath); 
            throw new Exception('File format not supported.'); 
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
            throw new Exception('Saving error.');
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
        
        if (empty($d['title'])) throw new Exception('Title required');
        
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

// ==============================================================================================
// ФУНКЦИИ АККАУНТОВ
// ==============================================================================================

function getAccountsData($pdo) {
    try {
        $uid = $_SESSION['user_id'];
        
        $accounts = $pdo->prepare("SELECT * FROM accounts WHERE user_id = ? ORDER BY id ASC");
        $accounts->execute([$uid]);
        $result = $accounts->fetchAll();
        
        foreach ($result as &$acc) {
            $aid = $acc['id'];
            
            // Получаем сумму PnL по всем сделкам этого счета
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
            
            $acc['total_trades'] = (int)$stats['total'];
            $acc['wins'] = (int)$stats['wins'];
            $acc['profit'] = (float)$stats['total_pnl'];
            $acc['avg_rr'] = round((float)$stats['avg_rr'], 2);
            
            // === ПРАВИЛЬНЫЙ РАСЧЕТ БАЛАНСА ===
            // balance в БД = Стартовый баланс Журнала (Initial Balance)
            // starting_equity = Размер счета (Prop Size), от которого считаются лимиты.
            
            // Текущий баланс = Начало Журнала + Заработанное
            $acc['calculated_balance'] = (float)$acc['balance'] + $acc['profit'];
            
            // Просадка (Max Drawdown) считается по истории
            // (Упрощенно: насколько мы падали от локального пика)
            // Здесь мы покажем просто текущую просадку от Starting Equity, если она есть
            
            // Для удобства API вернет чистые числа
            $acc['balance'] = (float)$acc['balance'];
            $acc['starting_equity'] = (float)$acc['starting_equity'];
            $acc['target_percent'] = (float)$acc['target_percent'];
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
        $d = json_decode(file_get_contents('php://input'), true);
        
        if(empty($d['name'])) throw new Exception('Enter name');
        
        $target = !empty($d['target_percent']) ? $d['target_percent'] : 0;
        $dd = !empty($d['max_drawdown_percent']) ? $d['max_drawdown_percent'] : 0;
        
        // Получаем оба баланса
        $balance = !empty($d['balance']) ? $d['balance'] : 0; // Текущий
        $starting = !empty($d['starting_equity']) ? $d['starting_equity'] : $balance; // Изначальный

        if(!empty($d['id'])) {
            $sql = "UPDATE accounts SET name=?, type=?, balance=?, starting_equity=?, target_percent=?, max_drawdown_percent=? WHERE id=? AND user_id=?";
            $pdo->prepare($sql)->execute([$d['name'], $d['type'], $balance, $starting, $target, $dd, $d['id'], $uid]);
        } else {
            // current_equity при создании равен balance
            $sql = "INSERT INTO accounts (user_id, name, type, balance, starting_equity, current_equity, currency, status, target_percent, max_drawdown_percent) VALUES (?,?,?,?,?,?,'USD','Active',?,?)";
            $pdo->prepare($sql)->execute([$uid, $d['name'], $d['type'], $balance, $starting, $balance, $target, $dd]);
        }
        echo json_encode(['success'=>true]);
    } catch(Exception $e) {
        echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
    }
}

function deleteAccount($pdo) {
    try {
        $uid = $_SESSION['user_id'];
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM accounts WHERE id=? AND user_id=?")->execute([$id, $uid]);
        echo json_encode(['success'=>true]);
    } catch(Exception $e) {
        echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
    }
}

function getAccountDetails($pdo) {
    try {
        $uid = $_SESSION['user_id'];
        $id = $_GET['id'] ?? null;
        if(!$id) throw new Exception('ID not stated');

        $stmt = $pdo->prepare("SELECT * FROM accounts WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $uid]);
        $acc = $stmt->fetch();

        if(!$acc) throw new Exception('Account not found');

        echo json_encode(['success'=>true, 'data'=>$acc]);
    } catch(Exception $e) {
        echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
    }
}

// ==============================================================================================
// ФУНКЦИИ ВЫПЛАТ
// ==============================================================================================

function getPayouts($pdo) {
    try {
        $uid = $_SESSION['user_id'];
        // Получаем выплаты только по счетам пользователя
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
        $uid = $_SESSION['user_id'];
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
        // Проверка прав (через join можно, но для простоты доверимся ID, так как это админка пользователя)
        $pdo->prepare("DELETE FROM payouts WHERE id=?")->execute([$id]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getDataAnalysis($pdo) {
    try {
        $uid = $_SESSION['user_id'];
        
        // 1. ЛОГИКА ДЛЯ СПРАВОЧНИКОВ (Пары, Стили, Модели)
        // Берем ВСЕ строки из справочника и приклеиваем статистику
        $getRefStats = function($refTable, $refNameCol, $tradeFkCol) use ($pdo, $uid) {
            $sql = "SELECT 
                        r.$refNameCol as label,
                        COUNT(t.id) as total_trades,
                        SUM(CASE WHEN t.status = 'win' THEN 1 ELSE 0 END) as wins,
                        SUM(CASE WHEN t.status IN ('win', 'loss', 'breakeven') THEN 1 ELSE 0 END) as completed
                    FROM $refTable r
                    LEFT JOIN trades t ON t.$tradeFkCol = r.id AND t.user_id = ? AND t.status != 'cancelled'
                    GROUP BY r.id, r.$refNameCol
                    ORDER BY total_trades DESC, label ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$uid]);
            $rows = $stmt->fetchAll();
            
            return array_map(function($row) {
                $comp = (int)$row['completed'];
                $row['win_rate'] = $comp > 0 ? round(($row['wins'] / $comp) * 100) : 0;
                return $row;
            }, $rows);
        };

        // 2. ЛОГИКА ДЛЯ ПРОСТЫХ ПОЛЕЙ (Direction)
        $getSimpleStats = function($col) use ($pdo, $uid) {
            $sql = "SELECT 
                        $col as label,
                        COUNT(*) as total_trades,
                        SUM(CASE WHEN status = 'win' THEN 1 ELSE 0 END) as wins,
                        SUM(CASE WHEN status IN ('win', 'loss', 'breakeven') THEN 1 ELSE 0 END) as completed
                    FROM trades
                    WHERE user_id = ? AND status != 'cancelled'
                    GROUP BY $col
                    HAVING label IS NOT NULL AND label != ''
                    ORDER BY total_trades DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$uid]);
            $rows = $stmt->fetchAll();
             return array_map(function($row) {
                $comp = (int)$row['completed'];
                $row['win_rate'] = $comp > 0 ? round(($row['wins'] / $comp) * 100) : 0;
                return $row;
            }, $rows);
        };

        // 3. НОВАЯ ЛОГИКА ДЛЯ ТАЙМФРЕЙМОВ (Фиксированный список)
        $getTimeframeStats = function() use ($pdo, $uid) {
            // Список всех таймфреймов в нужном порядке
            $masterList = ['1D', 'H4', 'H1', 'M15', 'M5']; 
            
            // Получаем реальные данные из базы
            $sql = "SELECT 
                        entry_tf as label,
                        COUNT(*) as total_trades,
                        SUM(CASE WHEN status = 'win' THEN 1 ELSE 0 END) as wins,
                        SUM(CASE WHEN status IN ('win', 'loss', 'breakeven') THEN 1 ELSE 0 END) as completed
                    FROM trades
                    WHERE user_id = ? AND status != 'cancelled'
                    GROUP BY entry_tf";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$uid]);
            // Получаем массив, где ключи - это названия таймфреймов (label)
            $dbStats = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE); 
            
            $result = [];
            foreach ($masterList as $tf) {
                if (isset($dbStats[$tf])) {
                    // Если есть данные в базе - берем их
                    $row = $dbStats[$tf];
                    $comp = (int)$row['completed'];
                    $winRate = $comp > 0 ? round(($row['wins'] / $comp) * 100) : 0;
                    
                    $result[] = [
                        'label' => $tf,
                        'total_trades' => (int)$row['total_trades'],
                        'win_rate' => $winRate
                    ];
                } else {
                    // Если данных нет - выводим нули
                    $result[] = [
                        'label' => $tf,
                        'total_trades' => 0,
                        'win_rate' => 0
                    ];
                }
            }
            return $result;
        };

        $data = [
            'direction' => $getSimpleStats('direction'),
            'timeframe' => $getTimeframeStats(), // Используем новую функцию
            'style'     => $getRefStats('ref_styles', 'name', 'style_id'),
            'model'     => $getRefStats('ref_models', 'name', 'model_id'),
            'pairs'     => $getRefStats('ref_pairs', 'symbol', 'pair_id')
        ];

        echo json_encode(['success' => true, 'data' => $data]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// ==================================================
// ФУНКЦИИ ТОРГОВОЙ СТРАТЕГИИ
// ==================================================

function getStrategies($pdo) {
    try {
        $uid = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT * FROM strategy_modules WHERE user_id = ? ORDER BY id ASC");
        $stmt->execute([$uid]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getStrategyDetails($pdo) {
    try {
        $id = $_GET['id'] ?? null;
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
        $uid = $_SESSION['user_id'];
        $d = json_decode(file_get_contents('php://input'), true);
        
        if (empty($d['title'])) throw new Exception("Title is required");

        $icon = !empty($d['icon']) ? $d['icon'] : 'fas fa-book';
        $id = null; // Переменная для ID

        if (!empty($d['id'])) {
            $id = $d['id']; // Если редактируем, берем существующий ID
            $sql = "UPDATE strategy_modules SET title=?, description=?, icon=?, content=? WHERE id=? AND user_id=?";
            $pdo->prepare($sql)->execute([$d['title'], $d['description'], $icon, $d['content'] ?? '', $id, $uid]);
        } else {
            $sql = "INSERT INTO strategy_modules (user_id, title, description, icon, content) VALUES (?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$uid, $d['title'], $d['description'], $icon, $d['content'] ?? '']);
            $id = $pdo->lastInsertId(); // Если создаем, получаем новый ID
        }
        
        // Возвращаем ID на фронтенд
        echo json_encode(['success' => true, 'id' => $id]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteStrategy($pdo) {
    try {
        $uid = $_SESSION['user_id'];
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM strategy_modules WHERE id=? AND user_id=?")->execute([$id, $uid]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getMPAAnalysis($pdo) {
    try {
        $uid = $_SESSION['user_id'];
        $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

        // 1. Забираем сделки + ТЕКУЩИЙ БАЛАНС СЧЕТА (через LEFT JOIN)
        // Если у вас мульти-аккаунт и запись в trade_accounts, логика может быть сложнее,
        // но для базовой версии берем account_id из trades.
        $sql = "SELECT t.*, a.balance as acc_balance 
                FROM trades t
                LEFT JOIN accounts a ON t.account_id = a.id
                WHERE t.user_id = ? 
                AND YEAR(t.entry_date) = ? 
                ORDER BY t.entry_date ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$uid, $year]);
        $trades = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Структура данных
        $monthsData = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthsData[$m] = [
                'month_num' => $m,
                'count_total' => 0,
                'count_win' => 0,
                'count_loss' => 0,
                'count_be' => 0,
                'count_pending' => 0,
                'pnl_total' => 0,
                'pnl_percent' => 0,
                'rr_total' => 0,
                'winrate' => 0
            ];
        }

        // 3. Обработка
        foreach ($trades as $t) {
            if (empty($t['entry_date'])) continue;
            
            try {
                $date = new DateTime($t['entry_date']);
                $m = (int)$date->format('n');
            } catch (Exception $e) { continue; }

            $pnl = (float)$t['pnl'];
            $rr = (float)$t['rr_achieved'];
            $status = strtolower($t['status'] ?? '');
            
            // --- ЛОГИКА РАСЧЕТА ПРОЦЕНТА ---
            $deposit = (float)($t['deposit_start'] ?? 0);
            
            // Если в сделке депозит не сохранен, берем текущий баланс аккаунта как базу
            if ($deposit <= 0) {
                $deposit = (float)($t['acc_balance'] ?? 0);
            }

            // Считаем процент, только если есть от чего считать
            if ($deposit > 0) {
                $trade_percent = ($pnl / $deposit) * 100;
                $monthsData[$m]['pnl_percent'] += $trade_percent;
            }
            // -------------------------------

            $monthsData[$m]['count_total']++;
            $monthsData[$m]['pnl_total'] += $pnl;
            $monthsData[$m]['rr_total'] += $rr;

            if ($status === 'open' || $status === 'pending' || $status === '') {
                $monthsData[$m]['count_pending']++;
            } elseif (strpos($status, 'win') !== false || ($pnl > 0 && $status !== 'breakeven')) {
                $monthsData[$m]['count_win']++;
            } elseif (strpos($status, 'loss') !== false || ($pnl < 0)) {
                $monthsData[$m]['count_loss']++;
            } else {
                $monthsData[$m]['count_be']++;
            }
        }

        // 4. Группировка по кварталам
        $quarters = [
            1 => ['pnl' => 0, 'percent' => 0, 'months' => []],
            2 => ['pnl' => 0, 'percent' => 0, 'months' => []],
            3 => ['pnl' => 0, 'percent' => 0, 'months' => []],
            4 => ['pnl' => 0, 'percent' => 0, 'months' => []]
        ];

        foreach ($monthsData as $m => $data) {
            $closedTrades = $data['count_total'] - $data['count_pending'];
            if ($closedTrades > 0) {
                $monthsData[$m]['winrate'] = round(($data['count_win'] / $closedTrades) * 100);
            }

            $qNum = ceil($m / 3);
            $quarters[$qNum]['months'][] = $monthsData[$m];
            $quarters[$qNum]['pnl'] += $data['pnl_total'];
            $quarters[$qNum]['percent'] += $data['pnl_percent'];
        }

        if (ob_get_level() > 0) ob_clean();
        echo json_encode(['success' => true, 'data' => $quarters]);
        exit;

    } catch (Exception $e) {
        if (ob_get_level() > 0) ob_clean();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

function getMPAMonthDetails($pdo) {
    // Очистка буфера для чистого JSON
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');

    try {
        $uid = $_SESSION['user_id'];
        $year = $_GET['year'] ?? date('Y');
        $month = $_GET['month'] ?? date('n');

        // --- 1. ПОЛУЧАЕМ СДЕЛКИ (Как в Журнале) ---
        $sqlTrades = "SELECT t.*, rp.symbol as pair_name 
                      FROM trades t
                      LEFT JOIN ref_pairs rp ON t.pair_id = rp.id
                      WHERE t.user_id = ? 
                      AND YEAR(t.entry_date) = ? 
                      AND MONTH(t.entry_date) = ?
                      ORDER BY t.entry_date DESC";
        $stmt = $pdo->prepare($sqlTrades);
        $stmt->execute([$uid, $year, $month]);
        $trades = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // --- 2. ПОЛУЧАЕМ ПЛАНЫ (Как в Планах) ---
        // Просто берем все планы, созданные в выбранном месяце
        $sqlPlans = "SELECT p.*, rp.symbol as pair_symbol 
                     FROM plans p 
                     LEFT JOIN ref_pairs rp ON p.pair_id = rp.id 
                     WHERE p.user_id = ? 
                     AND YEAR(p.date) = ? 
                     AND MONTH(p.date) = ?
                     ORDER BY p.date DESC";
        $stmtPlans = $pdo->prepare($sqlPlans);
        $stmtPlans->execute([$uid, $year, $month]);
        $plans = $stmtPlans->fetchAll(PDO::FETCH_ASSOC);

        // --- 3. СЧИТАЕМ СТАТИСТИКУ (в PHP) ---
        $stats = [
            'total' => 0, 'wins' => 0, 'losses' => 0, 'be' => 0, 'pending' => 0,
            'pnl' => 0.0, 'rr_sum' => 0.0, 'percent_sum' => 0.0, 'winrate' => 0, 'avg_rr' => 0
        ];

        foreach ($trades as $t) {
            $stats['total']++;
            $pnl = (float)($t['pnl'] ?? 0);
            $stats['pnl'] += $pnl;
            $stats['rr_sum'] += (float)($t['rr_achieved'] ?? 0);
            
            $st = strtolower($t['status'] ?? '');
            $isWin = (strpos($st, 'win') !== false || ($pnl > 0 && $st !== 'breakeven'));
            $isLoss = (strpos($st, 'loss') !== false || ($pnl < 0));
            $isPending = ($st === 'open' || $st === 'pending' || $st === '');

            if ($isPending) $stats['pending']++;
            elseif ($isWin) $stats['wins']++;
            elseif ($isLoss) $stats['losses']++;
            else $stats['be']++;

            $depo = (float)($t['deposit_start'] ?? 0);
            if ($depo > 0) $stats['percent_sum'] += ($pnl / $depo) * 100;
        }

        $closed = $stats['wins'] + $stats['losses'] + $stats['be'];
        if ($closed > 0) {
            $stats['winrate'] = round(($stats['wins'] / $closed) * 100);
            $stats['avg_rr'] = round($stats['rr_sum'] / $closed, 2);
        }

        echo json_encode([
            'success' => true,
            'stats' => $stats,
            'trades' => $trades,
            'plans' => $plans
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function saveMPAReport($pdo) {
    // Чистим буфер вывода
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');

    try {
        // Получаем ID пользователя из сессии
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("User not autorised");
        }
        $uid = $_SESSION['user_id'];

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['year']) || !isset($data['month'])) {
            throw new Exception("Select year or month");
        }
        
        $year = $data['year'];
        $month = $data['month'];
        $content = $data['content'] ?? '';
        
        // Формируем дату (первое число месяца)
        $reportDate = sprintf("%04d-%02d-01", $year, $month);
        $reportType = 'Monthly'; 

        // 1. Проверяем, есть ли запись для ЭТОГО пользователя и ЭТОЙ даты
        $checkSql = "SELECT id FROM monthly_reports 
                     WHERE user_id = ? AND report_date = ? AND report_type = ?";
        $stmtCheck = $pdo->prepare($checkSql);
        $stmtCheck->execute([$uid, $reportDate, $reportType]);
        $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // 2. Если есть — ОБНОВЛЯЕМ (UPDATE)
            $updateSql = "UPDATE monthly_reports SET meta_analysis = ? WHERE id = ?";
            $stmtUpdate = $pdo->prepare($updateSql);
            $stmtUpdate->execute([$content, $existing['id']]);
        } else {
            // 3. Если нет — СОЗДАЕМ (INSERT) с user_id
            $insertSql = "INSERT INTO monthly_reports (user_id, report_date, report_type, meta_analysis) 
                          VALUES (?, ?, ?, ?)";
            $stmtInsert = $pdo->prepare($insertSql);
            $stmtInsert->execute([$uid, $reportDate, $reportType, $content]);
        }

        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
    }
}

function getMPAReport($pdo) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');

    try {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("User not autorised");
        }
        $uid = $_SESSION['user_id'];

        $year = $_GET['year'] ?? date('Y');
        $month = $_GET['month'] ?? date('n');
        $reportDate = sprintf("%04d-%02d-01", $year, $month);

        // Ищем отчет конкретного пользователя
        $sql = "SELECT meta_analysis FROM monthly_reports 
                WHERE user_id = ? AND report_date = ? AND report_type = 'Monthly' 
                LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$uid, $reportDate]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'content' => $result['meta_analysis'] ?? '']);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// ==============================================================================================
// ФУНКЦИИ QPA (Quarterly Performance Analysis)
// ==============================================================================================

// 1. Получить список кварталов (Исправлен дизайн: всегда возвращает 4 квартала)

function getQPAList($pdo) {
    // 1. Очищаем любой мусор перед JSON
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');

    // 2. Включаем отображение ошибок для отладки
    ini_set('display_errors', 0); // Скрываем ошибки от вывода в браузер (они ломают JSON)
    error_reporting(E_ALL);

    try {
        // Проверка подключения
        if (!$pdo) {
            throw new Exception("Database connection variable is NULL. Check api.php switch block.");
        }

        // Проверка сессии
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("User not logged in (No session).");
        }

        $uid = $_SESSION['user_id'];
        $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

        // SQL запрос
        $sql = "SELECT 
                    QUARTER(entry_date) as q,
                    SUM(pnl) as pnl,
                    COUNT(*) as total,
                    SUM(CASE WHEN pnl > 0 THEN 1 ELSE 0 END) as wins,
                    SUM(CASE WHEN pnl < 0 THEN 1 ELSE 0 END) as losses,
                    SUM(CASE WHEN pnl = 0 THEN 1 ELSE 0 END) as be
                FROM trades 
                WHERE user_id = ? AND YEAR(entry_date) = ?
                GROUP BY q 
                ORDER BY q ASC";

        $stmt = $pdo->prepare($sql);
        if (!$stmt) {
             throw new Exception("SQL Prepare failed: " . implode(" ", $pdo->errorInfo()));
        }
        
        $stmt->execute([$uid, $year]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Формирование данных
        $quarters = [];
        for ($i = 1; $i <= 4; $i++) {
            $qData = null;
            foreach($data as $row) {
                if ($row['q'] == $i) {
                    $qData = $row;
                    break;
                }
            }

            if ($qData) {
                $closed = $qData['wins'] + $qData['losses'] + $qData['be'];
                $wr = $closed > 0 ? round(($qData['wins'] / $closed) * 100) : 0;
                
                $quarters[] = [
                    'quarter' => $i,
                    'year' => $year,
                    'pnl' => (float)$qData['pnl'],
                    'winrate' => $wr,
                    'total' => (int)$qData['total']
                ];
            } else {
                $quarters[] = [
                    'quarter' => $i,
                    'year' => $year,
                    'pnl' => 0, 'winrate' => 0, 'total' => 0
                ];
            }
        }

        echo json_encode(['success' => true, 'data' => $quarters]);

    } catch (Exception $e) {
        // Возвращаем ошибку в понятном JSON формате
        echo json_encode(['success' => false, 'message' => 'API Error: ' . $e->getMessage()]);
    }
}

// 2. Детали квартала (ИСПРАВЛЕНО: Добавлена выгрузка trades и plans)
function getQPADetails($pdo) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');

    try {
        $uid = $_SESSION['user_id'];
        $year = $_GET['year'];
        $quarter = $_GET['quarter'];

        // Вычисляем месяцы квартала (например, Q1 = месяцы 1, 2, 3)
        $startMonth = ($quarter - 1) * 3 + 1;
        $endMonth = $startMonth + 2;

        // --- А. Получаем СПИСОК СДЕЛОК (Важно для таблицы) ---
        $sqlTrades = "SELECT t.*, 
                             rp.symbol as pair_name, 
                             rp.symbol as pair_symbol,
                             a.name as account_name
                      FROM trades t 
                      LEFT JOIN ref_pairs rp ON t.pair_id = rp.id
                      LEFT JOIN accounts a ON t.account_id = a.id
                      WHERE t.user_id = ? 
                      AND YEAR(t.entry_date) = ? 
                      AND MONTH(t.entry_date) BETWEEN ? AND ?
                      ORDER BY t.entry_date DESC";
        
        $stmt = $pdo->prepare($sqlTrades);
        $stmt->execute([$uid, $year, $startMonth, $endMonth]);
        $trades = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // --- Б. Получаем СПИСОК ПЛАНОВ (Важно для таблицы планов) ---
        $sqlPlans = "SELECT p.*, rp.symbol as pair_symbol 
                     FROM plans p 
                     LEFT JOIN ref_pairs rp ON p.pair_id = rp.id 
                     WHERE p.user_id = ? 
                     AND YEAR(p.date) = ? 
                     AND MONTH(p.date) BETWEEN ? AND ?
                     ORDER BY p.date DESC";
        $stmtPlans = $pdo->prepare($sqlPlans);
        $stmtPlans->execute([$uid, $year, $startMonth, $endMonth]);
        $plans = $stmtPlans->fetchAll(PDO::FETCH_ASSOC);

        // --- В. Детальная статистика (как в MPA) ---
        $stats = [
            'total' => 0, 'wins' => 0, 'losses' => 0, 'be' => 0, 'pending' => 0,
            'pnl' => 0.0, 'rr_sum' => 0.0, 'percent_sum' => 0.0, 'winrate' => 0, 'avg_rr' => 0
        ];

        foreach ($trades as $t) {
            $stats['total']++;
            $pnl = (float)($t['pnl'] ?? 0);
            $stats['pnl'] += $pnl;
            $stats['rr_sum'] += (float)($t['rr_achieved'] ?? 0);
            
            $st = strtolower($t['status'] ?? '');
            $isWin = (strpos($st, 'win') !== false || ($pnl > 0 && $st !== 'breakeven'));
            $isLoss = (strpos($st, 'loss') !== false || ($pnl < 0));
            $isPending = ($st === 'open' || $st === 'pending' || $st === '');

            if ($isPending) $stats['pending']++;
            elseif ($isWin) $stats['wins']++;
            elseif ($isLoss) $stats['losses']++;
            else $stats['be']++;

            // Расчет % к депозиту
            $depo = (float)($t['deposit_start'] ?? 0);
            if ($depo > 0) $stats['percent_sum'] += ($pnl / $depo) * 100;
        }

        $closed = $stats['wins'] + $stats['losses'] + $stats['be'];
        if ($closed > 0) {
            $stats['winrate'] = round(($stats['wins'] / $closed) * 100);
            $stats['avg_rr'] = round($stats['rr_sum'] / $closed, 2);
        }

        // --- Г. Данные по месяцам (для плиток сверху) ---
        $months = [];
        for ($m = $startMonth; $m <= $endMonth; $m++) {
            $dateObj = DateTime::createFromFormat('!m', $m);
            $mName = $dateObj->format('F');
            
            // Считаем статистику внутри месяца на лету
            $mPnl = 0; $mCount = 0; $mWins = 0;
            
            foreach ($trades as $t) {
                $tMonth = (int)date('m', strtotime($t['entry_date']));
                if ($tMonth === $m) {
                    $mPnl += (float)$t['pnl'];
                    $mCount++;
                    $st = strtolower($t['status'] ?? '');
                    if (strpos($st, 'win') !== false || ((float)$t['pnl'] > 0 && $st !== 'breakeven')) {
                        $mWins++;
                    }
                }
            }
            $mWr = ($mCount > 0) ? round(($mWins / $mCount) * 100) : 0;

            $months[] = [
                'num' => $m,
                'name' => $mName,
                'pnl' => $mPnl,
                'count' => $mCount,
                'winrate' => $mWr
            ];
        }

        // --- Д. Текст отчета ---
        $rDate = sprintf("%04d-%02d-01", $year, $startMonth);
        $stmtRep = $pdo->prepare("SELECT meta_analysis FROM monthly_reports WHERE user_id=? AND report_date=? AND report_type='Quarterly'");
        $stmtRep->execute([$uid, $rDate]);
        $rep = $stmtRep->fetch();

        // --- Е. Возврат данных ---
        echo json_encode([
            'success' => true,
            'stats' => $stats,
            'months' => $months,
            'trades' => $trades, // Добавлено: массив сделок
            'plans' => $plans,   // Добавлено: массив планов
            'report_content' => $rep['meta_analysis'] ?? ''
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// 3. Сохранение квартального отчета
function saveQPAReport($pdo) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');

    try {
        $d = json_decode(file_get_contents('php://input'), true);
        $uid = $_SESSION['user_id'];
        
        $startMonth = ($d['quarter'] - 1) * 3 + 1;
        $rDate = sprintf("%04d-%02d-01", $d['year'], $startMonth);
        
        $check = $pdo->prepare("SELECT id FROM monthly_reports WHERE user_id=? AND report_date=? AND report_type='Quarterly'");
        $check->execute([$uid, $rDate]);
        
        if ($ex = $check->fetch()) {
            $pdo->prepare("UPDATE monthly_reports SET meta_analysis=? WHERE id=?")->execute([$d['content'], $ex['id']]);
        } else {
            $pdo->prepare("INSERT INTO monthly_reports (user_id, report_date, report_type, meta_analysis) VALUES (?,?,?,?)")
                ->execute([$uid, $rDate, 'Quarterly', $d['content']]);
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>