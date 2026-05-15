<?php
// api/handlers/auth.php — Авторизация, регистрация, сброс пароля

function handleRegister($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = trim($data['username'] ?? '');
    $email    = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        return;
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'User with this email or username already exists']);
        return;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
    if ($stmt->execute([$username, $email, $hash])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed']);
    }
}

function handleLogin($pdo) {
    if (!empty($_POST)) {
        $data = $_POST;
    } else {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
    }

    $login    = trim($data['email'] ?? $data['username'] ?? '');
    $password = $data['password'] ?? '';

    if (empty($login) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Empty email or password']);
        return;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        return;
    }

    $dbPassword     = !empty($user['password_hash']) ? $user['password_hash'] : ($user['password'] ?? '');
    $isCorrect      = false;
    $needsMigration = false;

    if (password_verify($password, $dbPassword)) {
        $isCorrect = true;
    } elseif ($password === $dbPassword) {
        $isCorrect      = true;
        $needsMigration = true;
    }

    if ($isCorrect) {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_lang'] = $user['language'] ?? 'en';

        if ($needsMigration) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $user['id']]);
            } catch (Exception $e) { /* ignore */ }
        }

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid password']);
    }
}

function handleForgotPassword($pdo) {
    $data  = json_decode(file_get_contents('php://input'), true);
    $email = trim($data['email'] ?? '');

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email not found']);
        return;
    }

    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

    $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    if ($stmt->execute([$email, $token, $expires])) {
        $debugLink = "index.php?view=reset_password&token=" . $token;
        echo json_encode(['success' => true, 'message' => 'Check your email (Simulated)', 'debug_link' => $debugLink]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function handleResetPassword($pdo) {
    $data    = json_decode(file_get_contents('php://input'), true);
    $token   = $data['token'] ?? '';
    $newPass = $data['password'] ?? '';

    if (empty($token) || empty($newPass)) {
        echo json_encode(['success' => false, 'message' => 'Missing data']);
        return;
    }

    $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $resetRequest = $stmt->fetch();

    if (!$resetRequest) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
        return;
    }

    $hash   = password_hash($newPass, PASSWORD_DEFAULT);
    $update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
    if ($update->execute([$hash, $resetRequest['email']])) {
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$resetRequest['email']]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update password']);
    }
}

function handleLogout() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    session_unset();
    session_destroy();
    echo json_encode(['success' => true]);
}
?>