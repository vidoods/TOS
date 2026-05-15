<?php
// api/handlers/uploads.php — Загрузка и скачивание изображений

function uploadImage() {
    uploadOrDownloadImage(false);
}

function downloadImageFromUrl() {
    uploadOrDownloadImage(true);
}

function uploadOrDownloadImage($isDownload) {
    try {
        if (!isset($_SESSION['user_id'])) throw new Exception('Требуется авторизация.');

        $allowedTypes = ['general', 'notes', 'trades', 'plans'];
        $type         = $_POST['type'] ?? 'general';
        if (!in_array($type, $allowedTypes)) $type = 'general';

        $uploadDir = "../assets/uploads/images/$type/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

        if ($isDownload) {
            $data = json_decode(file_get_contents('php://input'), true);
            $url  = $data['image_url'] ?? '';

            if (isset($data['type']) && in_array($data['type'], $allowedTypes)) {
                $uploadDir = "../assets/uploads/images/" . $data['type'] . "/";
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
            }

            if (!filter_var($url, FILTER_VALIDATE_URL)) throw new Exception('Некорректный URL-адрес.');
            
            // БЕЗОПАСНОСТЬ: Разрешаем только HTTP и HTTPS
            $parsed = parse_url($url);
            if (!isset($parsed['scheme']) || !in_array(strtolower($parsed['scheme']), ['http', 'https'])) {
                throw new Exception('Поддерживаются только HTTP и HTTPS ссылки.');
            }

            // БЕЗОПАСНОСТЬ: Ставим таймаут, чтобы сервер не завис
            $context = stream_context_create(['http' => ['timeout' => 10]]);
            $content = @file_get_contents($url, false, $context);
            if ($content === false) throw new Exception('Ошибка скачивания изображения. Сервер недоступен.');

            $tmpPath = tempnam(sys_get_temp_dir(), 'img');
            file_put_contents($tmpPath, $content);
        } else {
            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Ошибка загрузки файла.');
            }
            $tmpPath = $_FILES['image']['tmp_name'];
        }

        $finfo        = new finfo(FILEINFO_MIME_TYPE);
        $mime         = $finfo->file($tmpPath);
        $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];

        if (!isset($allowedMimes[$mime])) {
            if (file_exists($tmpPath)) unlink($tmpPath);
            throw new Exception('Формат файла не поддерживается. Разрешены JPG, PNG, GIF, WEBP.');
        }

        $filename = uniqid('img_') . '.' . $allowedMimes[$mime];
        $dest     = $uploadDir . $filename;

        if (compressAndSaveImage($tmpPath, $dest, $mime)) {
            if ($isDownload && file_exists($tmpPath)) unlink($tmpPath);
            $webPath = str_replace('../', '', $dest);
            echo json_encode(['success' => true, 'url' => $webPath]);
        } else {
            if (file_exists($tmpPath)) unlink($tmpPath);
            throw new Exception('Ошибка при сохранении изображения.');
        }

    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function compressAndSaveImage($source, $dest, $mime, $quality = 80) {
    if (!extension_loaded('gd')) return copy($source, $dest);

    // Добавлена "собачка" @ для подавления ворнингов, если картинка битая
    $img = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($source),
        'image/png'  => @imagecreatefrompng($source),
        'image/gif'  => @imagecreatefromgif($source),
        'image/webp' => @imagecreatefromwebp($source),
        default      => null
    };

    if (!$img) return false;

    $res = match ($mime) {
        'image/png'  => (imagealphablending($img, false) && imagesavealpha($img, true) && imagepng($img, $dest, 9 - round($quality / 10))),
        'image/gif'  => imagegif($img, $dest),
        'image/webp' => imagewebp($img, $dest, $quality),
        default      => imagejpeg($img, $dest, $quality)
    };

    imagedestroy($img);
    return $res;
}

function uploadAvatar() {
    global $pdo;
    try {
        if (!isset($_SESSION['user_id'])) throw new Exception('Требуется авторизация.');
        
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Файл не был загружен.');
        }

        $userId = $_SESSION['user_id'];
        $file = $_FILES['avatar'];
        
        // БЕЗОПАСНОСТЬ: Жестко проверяем тип файла через finfo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        
        $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($allowedMimes[$mime])) {
            throw new Exception('Неверный формат файла. Разрешены JPG, PNG и WEBP.');
        }

        $uploadDir = '../assets/uploads/images/avatars/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        // БЕЗОПАСНОСТЬ: Имя файла формируется с безопасным расширением
        $ext = $allowedMimes[$mime];
        $fileName = 'avatar_' . $userId . '_' . time() . '.' . $ext;
        $filePath = $uploadDir . $fileName;

        // БЕЗОПАСНОСТЬ: Сжимаем аватарку и удаляем вредоносный код (перерисовывая её)
        if (compressAndSaveImage($file['tmp_name'], $filePath, $mime)) {
            $dbPath = 'assets/uploads/images/avatars/' . $fileName;

            // Удаляем старый аватар
            $stmt = $pdo->prepare("SELECT avatar_url FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $oldAvatar = $stmt->fetchColumn();
            if ($oldAvatar && file_exists('../' . $oldAvatar)) {
                unlink('../' . $oldAvatar);
            }

            // Обновляем базу данных
            $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?")->execute([$dbPath, $userId]);

            echo json_encode(['success' => true, 'avatar_url' => $dbPath]);
        } else {
            throw new Exception('Ошибка загрузки. Проверьте права доступа к папке.');
        }
    } catch (\Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>