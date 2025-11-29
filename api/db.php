<?php
// api/db.php

$host = 'localhost'; // Ваш хост базы данных
$db   = 'b10_40483036_tos';     // Имя вашей базы данных
$user = 'root';         // Имя пользователя базы данных
$pass = '';         // Пароль пользователя базы данных

// ИСПРАВЛЕНИЕ: Убираем charset из DSN. Оставляем только host и db.
$dsn = "mysql:host=$host;dbname=$db";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,      
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,           
    PDO::ATTR_EMULATE_PREPARES   => false,                      
    
    // КЛЮЧЕВАЯ КОМАНДА: Принудительно устанавливаем кодировку UTF-8 (utf8mb4)
    // для всех данных, которыми обмениваются PHP и MySQL.
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci" 
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // В случае ошибки подключения к БД, выдаем JSON-ответ и завершаем работу
    header('Content-Type: application/json');
    http_response_code(500);
    // Выводим только сообщение, чтобы не раскрывать критическую информацию о БД
    echo json_encode(['success' => false, 'message' => 'Ошибка подключения к базе данных.']);
    exit;
}
