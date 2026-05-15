<?php
// api/handlers/lang.php

function get_user_language($pdo, $user_id) {
    // Используем уже готовое подключение $pdo
    $stmt = $pdo->prepare("SELECT language FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $lang = $stmt->fetchColumn(); // Получаем только одну колонку
    
    return $lang ? $lang : 'en';
}

function get_translation($key, $pdo, $user_id) {
    // Ключевое слово static сохранит словарь в памяти. 
    // При переводе следующих слов скрипт больше не будет дергать базу и файлы.
    static $translations = null;
    
    if ($translations === null) {
        $language = get_user_language($pdo, $user_id);
        
        // Исправленный путь (проверьте, правильное ли количество '../')
        $lang_file = __DIR__ . '/../../assets/lang/' . $language . '.php';
        
        if (file_exists($lang_file)) {
            // Так как ваши файлы возвращают массив (return [...]), 
            // мы присваиваем результат переменной
            $translations = require $lang_file;
        } else {
            // Фолбэк на английский, если файл не найден
            $translations = require __DIR__ . '/../../assets/lang/en.php';
        }
    }

    // Возвращаем перевод, а если его нет — возвращаем сам ключ
    return isset($translations[$key]) ? $translations[$key] : $key;
}
?>