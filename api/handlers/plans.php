<?php
// api/handlers/plans.php — CRUD операции с планами

function savePlan($pdo) {
    try {
        $user_id = $_SESSION['user_id'];
        $data    = json_decode(file_get_contents('php://input'), true);

        if (empty($data['title']) || empty($data['pair_id']) || empty($data['date'])) {
            throw new Exception("Пожалуйста, заполните обязательные поля: название, пара, дата.");
        }

        $plan_id   = $data['id'] ?? null;
        $is_update = !empty($plan_id);

        $pdo->beginTransaction();

        if ($is_update) {
            // БЕЗОПАСНОСТЬ: Проверяем, принадлежит ли план текущему пользователю перед обновлением
            $checkStmt = $pdo->prepare("SELECT id FROM plans WHERE id = ? AND user_id = ?");
            $checkStmt->execute([$plan_id, $user_id]);
            if (!$checkStmt->fetch()) throw new Exception('План не найден или у вас нет прав на его изменение.');

            // Обновляем данные плана
            $sql  = "UPDATE plans SET title=?, pair_id=?, date=?, bias=?, type=?, status=?, review_q1=?, review_q2=?, review_comments=? WHERE id=? AND user_id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['title'], $data['pair_id'], $data['date'],
                $data['bias'] ?? 'Neutral', $data['type'] ?? 'Weekly',
                $data['status'] ?? 'pending', $data['review_q1'] ?? 0,
                $data['review_q2'] ?? 0, $data['review_comments'] ?? null,
                $plan_id, $user_id
            ]);
            
            // Удаляем старые скриншоты из базы перед добавлением новых
            $pdo->prepare("DELETE FROM trade_analysis_images WHERE trade_id = ? AND is_plan_image = 1")->execute([$plan_id]);
            $message = 'План успешно обновлен!';
        } else {
            // Создаем новый план
            $sql  = "INSERT INTO plans (user_id, title, pair_id, date, bias, type, status, review_q1, review_q2, review_comments) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $user_id, $data['title'], $data['pair_id'], $data['date'],
                $data['bias'] ?? 'Neutral', $data['type'] ?? 'Weekly',
                $data['status'] ?? 'pending', $data['review_q1'] ?? 0,
                $data['review_q2'] ?? 0, $data['review_comments'] ?? null
            ]);
            $plan_id = $pdo->lastInsertId();
            $message = 'План успешно создан!';
        }

        // Обновляем связь с заметкой
        $pdo->prepare("DELETE FROM note_to_plan WHERE plan_id = ?")->execute([$plan_id]);
        if (!empty($data['note_id'])) {
            $pdo->prepare("INSERT INTO note_to_plan (note_id, plan_id) VALUES (?, ?)")->execute([$data['note_id'], $plan_id]);
        }

        // Сохраняем ссылки на новые скриншоты таймфреймов
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
        echo json_encode(['success' => false, 'message' => 'Ошибка сохранения плана: ' . $e->getMessage()]);
    }
}

function getPlans($pdo) {
    try {
        $user_id = $_SESSION['user_id'];
        
        // БЕЗОПАСНОСТЬ: Подгружаем планы строго для текущего пользователя (WHERE p.user_id = :user_id)
        $sql     = "SELECT p.*, rp.symbol as pair_symbol FROM plans p LEFT JOIN user_pairs rp ON p.pair_id = rp.id WHERE p.user_id = :user_id";
        $params  = ['user_id' => $user_id];

        // Применяем фильтры, если они переданы
        if (!empty($_GET['pair_id'])) { $sql .= " AND p.pair_id = :pair_id"; $params['pair_id'] = $_GET['pair_id']; }
        if (!empty($_GET['type']))    { $sql .= " AND p.type = :type";       $params['type']    = $_GET['type']; }
        if (!empty($_GET['bias']))    { $sql .= " AND p.bias = :bias";       $params['bias']    = $_GET['bias']; }
        if (!empty($_GET['status']))  { $sql .= " AND p.status = :status";   $params['status']  = $_GET['status']; }

        $sql .= " ORDER BY p.date DESC, p.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $plans = $stmt->fetchAll();

        // Группируем планы по месяцам для вывода на фронтенде
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
        echo json_encode(['success' => false, 'message' => 'Ошибка загрузки планов: ' . $e->getMessage()]);
    }
}

function getPlanDetails($pdo) {
    try {
        $plan_id = $_GET['id'] ?? null;
        if (!$plan_id) throw new Exception('ID плана не указан.');

        // БЕЗОПАСНОСТЬ: Ищем план только среди планов текущего пользователя
        $stmt = $pdo->prepare("SELECT p.*, rp.symbol as pair_symbol, rp.type as pair_type FROM plans p LEFT JOIN user_pairs rp ON p.pair_id = rp.id WHERE p.id = ? AND p.user_id = ?");
        $stmt->execute([$plan_id, $_SESSION['user_id']]);
        $plan = $stmt->fetch();

        if (!$plan) { http_response_code(404); throw new Exception('План не найден или доступ закрыт.'); }

        // Загружаем скриншоты плана
        $stmt_images = $pdo->prepare("SELECT id, image_url, notes, title FROM trade_analysis_images WHERE trade_id = ? AND is_plan_image = 1 ORDER BY id ASC");
        $stmt_images->execute([$plan_id]);
        $plan['timeframes'] = $stmt_images->fetchAll();

        $plan['formatted_date']       = date('d F Y', strtotime($plan['date']));
        $plan['formatted_created_at'] = date('d F Y H:i', strtotime($plan['created_at']));

        // ИСПРАВЛЕНА УЯЗВИМОСТЬ: Заменили прямую вставку $plan_id в query() на безопасный prepare()
        $stmtNote = $pdo->prepare("SELECT n.id, n.title FROM note_to_plan np JOIN notes n ON np.note_id = n.id WHERE np.plan_id = ? LIMIT 1");
        $stmtNote->execute([$plan_id]);
        $note = $stmtNote->fetch(PDO::FETCH_ASSOC);
        
        $plan['note_id']    = $note['id'] ?? null;
        $plan['note_title'] = $note['title'] ?? null;

        echo json_encode(['success' => true, 'data' => $plan]);

    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка загрузки деталей плана: ' . $e->getMessage()]);
    }
}

function deletePlan($pdo) {
    try {
        $plan_id = $_POST['plan_id'] ?? null;
        if (!$plan_id) throw new Exception('ID плана не указан.');

        $user_id = $_SESSION['user_id'];
        $pdo->beginTransaction();

        // БЕЗОПАСНОСТЬ: Проверяем, что план принадлежит пользователю, прежде чем что-то удалять
        $checkStmt = $pdo->prepare("SELECT id FROM plans WHERE id = ? AND user_id = ?");
        $checkStmt->execute([$plan_id, $user_id]);
        if (!$checkStmt->fetch()) throw new Exception('План не найден или у вас нет прав на удаление.');

        // Получаем пути к картинкам, чтобы удалить их с диска сервера
        $stmt_get_images = $pdo->prepare("SELECT image_url FROM trade_analysis_images WHERE trade_id = ? AND is_plan_image = 1");
        $stmt_get_images->execute([$plan_id]);
        $images = $stmt_get_images->fetchAll();

        // Удаляем картинки из БД и сам план
        $pdo->prepare("DELETE FROM trade_analysis_images WHERE trade_id = ? AND is_plan_image = 1")->execute([$plan_id]);
        $stmtPlan = $pdo->prepare("DELETE FROM plans WHERE id = ? AND user_id = ?");
        $stmtPlan->execute([$plan_id, $user_id]);

        if ($stmtPlan->rowCount() > 0) {
            $pdo->commit();
            // Физически удаляем файлы скриншотов с сервера
            foreach ($images as $img) {
                $filePath = '../' . $img['image_url'];
                if (file_exists($filePath)) unlink($filePath);
            }
            echo json_encode(['success' => true, 'message' => 'План успешно удален.']);
        } else {
            throw new Exception('Не удалось удалить план из базы данных.');
        }

    } catch (\Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка удаления плана: ' . $e->getMessage()]);
    }
}
?>