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
        if (!isset($_SESSION['user_id'])) throw new Exception('Autorisation required.');

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

            if (!filter_var($url, FILTER_VALIDATE_URL)) throw new Exception('Not correct URL.');
            $content = @file_get_contents($url);
            if ($content === false) throw new Exception('Image download error.');

            $tmpPath = tempnam(sys_get_temp_dir(), 'img');
            file_put_contents($tmpPath, $content);
        } else {
            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) throw new Exception('File upload error.');
            $tmpPath = $_FILES['image']['tmp_name'];
        }

        $finfo        = new finfo(FILEINFO_MIME_TYPE);
        $mime         = $finfo->file($tmpPath);
        $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];

        if (!isset($allowedMimes[$mime])) {
            unlink($tmpPath);
            throw new Exception('File format not supported.');
        }

        $filename = uniqid('img_') . '.' . $allowedMimes[$mime];
        $dest     = $uploadDir . $filename;

        if (compressAndSaveImage($tmpPath, $dest, $mime)) {
            if ($isDownload) unlink($tmpPath);
            $webPath = str_replace('../', '', $dest);
            echo json_encode(['success' => true, 'url' => $webPath]);
        } else {
            unlink($tmpPath);
            throw new Exception('Saving error.');
        }

    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function compressAndSaveImage($source, $dest, $mime, $quality = 80) {
    if (!extension_loaded('gd')) return copy($source, $dest);

    $img = match ($mime) {
        'image/jpeg' => imagecreatefromjpeg($source),
        'image/png'  => imagecreatefrompng($source),
        'image/gif'  => imagecreatefromgif($source),
        'image/webp' => imagecreatefromwebp($source),
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
    if (!isset($_FILES['avatar'])) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded']);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $file = $_FILES['avatar'];
    
    // Проверка типа
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file format']);
        exit;
    }

    // Создаем папку, если ее нет (обратите внимание на путь, я добавил /images/ чтобы было как у вас везде)
    $uploadDir = '../assets/uploads/images/avatars/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    // Генерируем уникальное имя
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'avatar_' . $userId . '_' . time() . '.' . $ext;
    $filePath = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $filePath)) {
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
        echo json_encode(['success' => false, 'message' => 'Upload failed. Check folder permissions.']);
    }
}