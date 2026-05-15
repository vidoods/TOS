<?php
// api/handlers/settings.php

/**
 * Получить все настройки текущего пользователя (Timeframes, Styles, Pairs, Models)
 */
function getUserSettings($conn) {
    // ИСПРАВЛЕНИЕ: Привели получение ID к единому стандарту
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'Ошибка авторизации']);
        return;
    }

    try {
        // 1. Получаем таймфреймы
        $stmt = $conn->prepare("SELECT id, name FROM user_timeframes WHERE user_id = ?");
        $stmt->execute([$userId]);
        $timeframes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Получаем стили
        $stmt = $conn->prepare("SELECT id, name FROM user_styles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $styles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Получаем пары
        $stmt = $conn->prepare("SELECT id, symbol, type FROM user_pairs WHERE user_id = ?");
        $stmt->execute([$userId]);
        $pairs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 4. Получаем модели
        $stmt = $conn->prepare("SELECT id, name FROM user_models WHERE user_id = ?");
        $stmt->execute([$userId]);
        $models = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'timeframes' => $timeframes,
            'styles' => $styles,
            'pairs' => $pairs,
            'models' => $models
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Добавление новой настройки (универсальный метод)
 */
function addUserSettings($conn) {
    $userId = $_SESSION['user_id'] ?? null;
    $type = $_POST['type'] ?? ''; // 'timeframe', 'style', 'pair' или 'model'

    try {
        if (!$userId) throw new Exception("Ошибка авторизации");

        if ($type === 'timeframe') {
            $name = trim($_POST['name'] ?? '');
            if (empty($name)) throw new Exception("Название не может быть пустым");
            
            $stmt = $conn->prepare("INSERT INTO user_timeframes (user_id, name) VALUES (?, ?)");
            $stmt->execute([$userId, $name]);
        } 
        elseif ($type === 'style') {
            $name = trim($_POST['name'] ?? '');
            if (empty($name)) throw new Exception("Название не может быть пустым");

            $stmt = $conn->prepare("INSERT INTO user_styles (user_id, name) VALUES (?, ?)");
            $stmt->execute([$userId, $name]);
        } 
        elseif ($type === 'pair') {
            $symbol = trim($_POST['symbol'] ?? '');
            if (empty($symbol)) throw new Exception("Символ пары не может быть пустым");

            $pairType = $_POST['pair_type'] ?? 'Crypto';
            $stmt = $conn->prepare("INSERT INTO user_pairs (user_id, symbol, type) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $symbol, $pairType]);
        }
        elseif ($type === 'model') {
            $name = trim($_POST['name'] ?? '');
            if (empty($name)) throw new Exception("Название не может быть пустым");

            $stmt = $conn->prepare("INSERT INTO user_models (user_id, name) VALUES (?, ?)");
            $stmt->execute([$userId, $name]);
        } 
        else {
            throw new Exception("Неизвестный тип настройки");
        }

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Удаление настройки
 */
function deleteUserSettings($conn) {
    // ИСПРАВЛЕНИЕ: Убрали опечатку $_SESSION['user_php_id']
    $userId = $_SESSION['user_id'] ?? null;
    $type = $_POST['type'] ?? '';
    $id = $_POST['id'] ?? null;

    try {
        if (!$userId || !$id) throw new Exception("Некорректные данные для удаления");

        // БЕЗОПАСНОСТЬ: Везде проверяется принадлежность записи текущему пользователю
        if ($type === 'timeframe') {
            $stmt = $conn->prepare("DELETE FROM user_timeframes WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
        } 
        elseif ($type === 'style') {
            $stmt = $conn->prepare("DELETE FROM user_styles WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
        } 
        elseif ($type === 'pair') {
            $stmt = $conn->prepare("DELETE FROM user_pairs WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
        }
        elseif ($type === 'model') {
            $stmt = $conn->prepare("DELETE FROM user_models WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
        } else {
            throw new Exception("Неизвестный тип настройки");
        }

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Получить список всех возможных типов пар из структуры базы данных
 */
function getAvailablePairTypes($conn) {
    try {
        // Умный запрос к базе, чтобы вытащить значения ENUM для колонки 'type'
        $sql = "SELECT COLUMN_TYPE 
                FROM information_schema.columns 
                WHERE TABLE_NAME = 'user_pairs' 
                AND COLUMN_NAME = 'type' 
                AND TABLE_SCHEMA = DATABASE()";
        
        $stmt = $conn->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            // Извлекаем значения из строки вида: enum('Forex','Crypto','Indices','Metals','Energy')
            preg_match_all("/'([^']+)'/", $result['COLUMN_TYPE'], $matches);
            $types = $matches[1]; // Получаем чистый массив ['Forex', 'Crypto', ...]

            echo json_encode(['success' => true, 'types' => $types]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Не удалось загрузить типы пар']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}