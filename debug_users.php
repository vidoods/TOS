<?php
// debug_users.php
require 'api/db.php';

echo "<h1>Database Diagnostics</h1>";

try {
    // 1. Проверяем структуру таблицы (есть ли вообще колонка email?)
    echo "<h3>1. Table Structure ('users')</h3>";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('email', $columns)) {
        echo "<div style='color:green'>OK: Column 'email' exists.</div>";
    } else {
        echo "<div style='color:red; font-weight:bold;'>CRITICAL: Column 'email' is MISSING!</div>";
        echo "You need to run the SQL command to add the email column.";
    }

    if (in_array('password_hash', $columns)) {
        echo "<div style='color:green'>OK: Column 'password_hash' exists.</div>";
    } else {
        echo "<div style='color:orange'>WARNING: Column 'password_hash' is missing (using 'password'?).</div>";
    }

    // 2. Выводим список всех пользователей
    echo "<h3>2. Registered Users List</h3>";
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($users) === 0) {
        echo "<div style='color:red'>Table is EMPTY. Registration failed silently.</div>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr style='background:#eee'><th>ID</th><th>Username</th><th>Email (Length)</th><th>Password Hash (Start)</th></tr>";
        
        foreach ($users as $u) {
            $emailVal = $u['email'] ?? 'NULL';
            $emailLen = strlen($emailVal);
            
            // Проверяем, где лежит пароль
            $passHash = $u['password_hash'] ?? $u['password'] ?? 'EMPTY';
            $passPreview = substr($passHash, 0, 15) . '...';

            // Подсветка ошибок
            $emailStyle = ($emailLen < 3) ? 'color:red; font-weight:bold' : 'color:green';

            echo "<tr>";
            echo "<td>{$u['id']}</td>";
            echo "<td>" . htmlspecialchars($u['username']) . "</td>";
            echo "<td style='$emailStyle'>" . htmlspecialchars($emailVal) . " (Len: $emailLen)</td>";
            echo "<td>" . htmlspecialchars($passPreview) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>