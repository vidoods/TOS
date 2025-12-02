<?php
// api/db.php

$host = 'localhost'; // Ваш хост базы данных
$db   = 'b10_40483036_tos';     // Имя вашей базы данных
$user = 'root';         // Имя пользователя базы данных
$pass = '';         // Пароль пользователя базы данных

// ВАЖНО: Используем charset=utf8 в DSN для максимальной совместимости.
$dsn = "mysql:host=$host;dbname=$db;charset=utf8";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,      
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,           
    PDO::ATTR_EMULATE_PREPARES   => false,                      
    
    // КЛЮЧЕВАЯ КОМАНДА: Принудительно устанавливаем кодировку UTF8MB4
    // Эта команда выполняется сразу после подключения и решает конфликт.
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci" 
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка подключения к базе данных.']);
    exit;
}