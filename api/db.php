<?php
// api/db.php

$host = 'localhost'; // Ваш хост базы данных
$db   = 'b10_40483036_tos';     // Имя вашей базы данных
$user = 'root';         // Имя пользователя базы данных
$pass = '';         // Пароль пользователя базы данных

// Важно: Указываем кодировку UTF-8 (utf8mb4) прямо в DSN
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,      // Включаем вывод исключений при ошибках
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,            // По умолчанию получаем данные как ассоциативный массив
    PDO::ATTR_EMULATE_PREPARES   => false,                       // Отключаем эмуляцию подготовленных запросов (для лучшей безопасности и производительности)
    // !!! Ключевая строка для кодировки: !!!
    // Устанавливаем кодировку UTF-8 (utf8mb4) для обмена данными с MySQL сразу после подключения
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // В случае ошибки подключения к БД, выдаем JSON-ответ и завершаем работу
    // Это важно, так как db.php может быть вызван через API
    header('Content-Type: application/json'); // Убедимся, что ответ JSON
    echo json_encode(['success' => false, 'message' => 'Ошибка подключения к БД: ' . $e->getMessage()]);
    exit(); // Останавливаем выполнение скрипта
}
?>