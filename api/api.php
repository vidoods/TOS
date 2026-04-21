<?php
// api/api.php — Единая точка входа (ТОЛЬКО РОУТИНГ)
// Вся логика — в handlers/*.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');
require 'db.php';
date_default_timezone_set('Europe/Kyiv');

if (!extension_loaded('fileinfo')) {
    error_log("PHP extension 'fileinfo' is not loaded.");
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ГЛОБАЛЬНАЯ ЗАЩИТА: список публичных действий
$publicActions = ['login', 'logout', 'register', 'forgot_password', 'reset_password'];
if (!isset($_SESSION['user_id']) && !in_array($action, $publicActions)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authorization required.']);
    exit;
}

$conn = $pdo;

// Подключаем все обработчики
require __DIR__ . '/handlers/auth.php';
require __DIR__ . '/handlers/lookups.php';
require __DIR__ . '/handlers/plans.php';
require __DIR__ . '/handlers/trades.php';
require __DIR__ . '/handlers/notes.php';
require __DIR__ . '/handlers/accounts.php';
require __DIR__ . '/handlers/payouts.php';
require __DIR__ . '/handlers/uploads.php';
require __DIR__ . '/handlers/dashboard.php';
require __DIR__ . '/handlers/data_analysis.php';
require __DIR__ . '/handlers/strategy.php';
require __DIR__ . '/handlers/mpa.php';
require __DIR__ . '/handlers/qpa.php';

try {
    switch ($action) {
        // --- АВТОРИЗАЦИЯ ---
        case 'login':          handleLogin($conn); break;
        case 'logout':         handleLogout(); break;
        case 'register':       handleRegister($conn); break;
        case 'forgot_password': handleForgotPassword($conn); break;
        case 'reset_password': handleResetPassword($conn); break;
        case 'get_user_info':
            $userId = $_SESSION['user_id'];
            $stmt = $pdo->prepare("SELECT username, email, created_at, language FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                echo json_encode([
                    'success'    => true,
                    'username'   => $user['username'],
                    'email'      => $user['email'],
                    'language' => $user['language'] ?? 'en',
                    'created_at' => $user['created_at'] ? date('d M Y', strtotime($user['created_at'])) : '-'
                ]);
            } else {
                echo json_encode(['success' => false]);
            }
            break;

        case 'change_language':
        $userId = $_SESSION['user_id'];
        $newLang = $_POST['lang'] ?? 'en';
        
        // Защита от подделки: разрешаем только en и ru
        if (!in_array($newLang, ['en', 'ru'])) $newLang = 'en';

        $stmt = $pdo->prepare("UPDATE users SET language = ? WHERE id = ?");
        if ($stmt->execute([$newLang, $userId])) {
            // Обновляем сессию сразу!
            $_SESSION['user_lang'] = $newLang;
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;

        // --- СПРАВОЧНИКИ ---
        case 'get_lookups':         getLookups($conn); break;
        case 'get_ref_pairs':       getRefPairs($conn); break;
        case 'get_accounts_lookup': getAccountsLookup($conn); break;
        case 'get_ref_styles':      getRefStyles($conn); break;
        case 'get_plans_for_lookup': getPlansForLookup($conn); break;

        // --- ПЛАНЫ ---
        case 'save_plan':
        case 'create_plan':
        case 'update_plan':  savePlan($conn); break;
        case 'get_plans':        getPlans($conn); break;
        case 'get_plan_details': getPlanDetails($conn); break;
        case 'delete_plan':      deletePlan($conn); break;

        // --- СДЕЛКИ ---
        case 'get_trades':       getTrades($conn); break;
        case 'get_trade_details': getTradeDetails($conn); break;
        case 'save_trade':
        case 'create_trade':
        case 'update_trade':  saveTrade($conn); break;
        case 'delete_trade':     deleteTrade($conn); break;

        // --- ДАШБОРД ---
        case 'get_dashboard_metrics': getDashboardMetrics($conn); break;

        // --- ИЗОБРАЖЕНИЯ ---
        case 'upload_image':           uploadImage(); break;
        case 'download_image_from_url': downloadImageFromUrl(); break;

        // --- ЗАМЕТКИ ---
        case 'get_notes':       getNotes($conn); break;
        case 'get_note_details': getNoteDetails($conn); break;
        case 'save_note':       saveNote($conn); break;
        case 'delete_note':     deleteNote($conn); break;

        // --- АККАУНТЫ ---
        case 'get_accounts_data':  getAccountsData($conn); break;
        case 'save_account':       saveAccount($conn); break;
        case 'delete_account':     deleteAccount($conn); break;
        case 'get_account_details': getAccountDetails($conn); break;
        case 'get_accounts':       getAccountsLookup($conn); break;

        // --- ВЫПЛАТЫ ---
        case 'get_payouts':    getPayouts($conn); break;
        case 'save_payout':    savePayout($conn); break;
        case 'delete_payout':  deletePayout($conn); break;

        // --- DATA ANALYSIS ---
        case 'get_data_analysis': getDataAnalysis($conn); break;

        // --- СТРАТЕГИИ ---
        case 'get_strategies':      getStrategies($conn); break;
        case 'get_strategy_details': getStrategyDetails($conn); break;
        case 'save_strategy':       saveStrategy($conn); break;
        case 'delete_strategy':     deleteStrategy($conn); break;

        // --- MPA ---
        case 'get_mpa_analysis':      getMPAAnalysis($conn); break;
        case 'get_mpa_month_details': getMPAMonthDetails($conn); break;
        case 'save_mpa_report':       saveMPAReport($conn); break;
        case 'get_mpa_report':        getMPAReport($conn); break;

        // --- QPA ---
        case 'get_qpa_list':    getQPAList($conn); break;
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