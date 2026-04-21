<?php
function get_user_language($user_id) {
    // Подключение к базе данных (замените на вашу реальную логику)
    $conn = mysqli_connect('localhost', 'user', 'password', 'database');
    
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    
    // Получение языка пользователя из базы данных
    $stmt = $conn->prepare("SELECT language FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($language);
    $stmt->fetch();
    $stmt->close();
    
    // Закрытие соединения
    mysqli_close($conn);
    
    return $language ?: 'en'; // Возвращаем английский язык по умолчанию
}

function get_translation($key, $user_id) {
    // Получить язык пользователя из базы данных
    $language = get_user_language($user_id);
    
    $lang_file = __DIR__ . '/../../assets/lang/' . $language . '.php';
    if (file_exists($lang_file)) {
        require_once($lang_file);
        $translations = $lang;
    } else {
        die('Language file not found');
    }

    return isset($translations[$key]) ? $translations[$key] : '';
}
?>