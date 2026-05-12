<?php
// api/handlers/settings.php

/**
 * Получить все настройки текущего пользователя (Timeframes, Styles, Pairs)
 */
function getUserSettings($conn) {
    $userId = $_SESSION['user_int'] ?? $_SESSION['user_id'];
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
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
    $userId = $_SESSION['user_id'];
    $type = $_POST['type'] ?? ''; // 'timeframe', 'style', или 'pair'

    try {
        if ($type === 'timeframe') {
            $name = $_POST['name'];
            $stmt = $conn->prepare("INSERT INTO user_timeframes (user_id, name) VALUES (?, ?)");
            $stmt->execute([$userId, $name]);
        } 
        elseif ($type === 'style') {
            $name = $_POST['name'];
            $stmt = $conn->prepare("INSERT INTO user_styles (user_id, name) VALUES (?, ?)");
            $stmt->execute([$userId, $name]);
        } 
        elseif ($type === 'pair') {
            $symbol = $_POST['symbol'];
            $pairType = $_POST['pair_type'] ?? 'Crypto';
            $stmt = $conn->prepare("INSERT INTO user_pairs (user_id, symbol, type) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $symbol, $pairType]);
        }
        elseif ($type === 'model') {
            $name = $_POST['name'];
            $stmt = $conn->prepare("INSERT INTO user_models (user_id, name) VALUES (?, ?)");
            $stmt->execute([$userId, $name]);
        } 
        else {
            throw new Exception("Invalid setting type");
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
    $userId = $_SESSION['user_php_id'] ?? $_SESSION['user_id'];
    $type = $_POST['type'];
    $id = $_POST['id'];
    $extra = $_POST['extra'] ?? null; // Для пар может понадобиться доп. инфо

    try {
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
        // Запрос к INFORMATION_SCHEMA, чтобы вытащить значения ENUM для колонки 'type'
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
            $types = $matches[1]; // Это массив ['Forex', 'Crypto', ...]

            echo json_encode(['success' => true, 'types' => $types]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Could not fetch types']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
