<?php
// api/db.php — Подключение к базе данных

require_once __DIR__ . '/env.php';

$host = getenv('DB_HOST') ?: '127.0.0.1';
$db   = getenv('DB_NAME') ?: 'b10_40483036_tos';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

// ВАЖНО: Используем charset=utf8 в DSN для максимальной совместимости.
$dsn = "mysql:host=$host;dbname=$db;charset=utf8";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,      
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,           
    PDO::ATTR_EMULATE_PREPARES   => false,                      
    
    // КЛЮЧЕВАЯ КОМАНДА: Принудительно устанавливаем кодировку UTF8MB4
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci" 
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error connecting to database.']);
    exit;
}