<?php
// index.php - Главная точка входа

// 1. Инициализация сессии (ДОЛЖНО БЫТЬ В САМОМ НАЧАЛЕ ФАЙЛА)
session_start();

// 2. Определение текущего представления (view)
$view = $_GET['view'] ?? 'dashboard';

// 3. ПРОВЕРКА АВТОРИЗАЦИИ
// Если пользователь НЕ авторизован И пытается зайти не на страницу входа или регистрации
if (!isset($_SESSION['user_id']) && $view !== 'login' && $view !== 'register') {
    header('Location: index.php?view=login');
    exit;
}

// Если пользователь уже авторизован, но пытается зайти на страницу входа или регистрации
if (isset($_SESSION['user_id']) && ($view === 'login' || $view === 'register')) {
    header('Location: index.php?view=dashboard');
    exit;
}

// 4. Определение пути к файлу представления
// Предполагаем, что все view лежат в папке views/
$view_path = __DIR__ . '/views/' . $view . '.php';

if (!file_exists($view_path)) {
    // Если файл не найден, можно показать 404 или перенаправить на дашборд
    // Для простоты пока перенаправим на дашборд, если это не он сам
    if ($view !== 'dashboard') {
        header('Location: index.php?view=dashboard');
        exit;
    } else {
        // Если даже дашборда нет, выводим простую ошибку
        die('Ошибка: Файл представления не найден.');
    }
}

// Карта заголовков страниц (опционально, для красоты)
$pageTitles = [
    'dashboard' => 'Дашборд',
    'plans' => 'Торговые Планы',
    'plan_create' => 'Создание Плана',
    'plan_details' => 'Детали Плана',
    'journal' => 'Журнал Сделок',
    'trade_create' => 'Новая Сделка',
    'trade_details' => 'Детали Сделки',
	'note_details' => 'Просмотр Заметки',
    'login' => 'Вход',
    'register' => 'Регистрация'
];
$currentTitle = $pageTitles[$view] ?? 'TOS - Trading Operating System';

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($currentTitle); ?> | TOS</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=<?php echo time(); ?>">
</head>
<body>

    <?php if ($view === 'login' || $view === 'register'): ?>
        <div class="login-container">
            <?php include $view_path; ?>
        </div>

    <?php else: ?>
        <div class="app-container">
            <aside class="sidebar" id="sidebar">
                <button id="sidebar-close-btn" onclick="closeMenu()">✕</button>
                <div class="logo">
                    <a href="index.php?view=dashboard" title="На главную">TOS</a>
                </div>
                
                <nav class="nav-menu">
                    <h3>ROUTINE</h3>
                    <div class="nav-links">
                        <a href="index.php?view=dashboard" class="<?= $view === 'dashboard' ? 'active' : '' ?>">📊 Dashboard</a>
                        <a href="index.php?view=plans" class="<?= strpos($view, 'plan') !== false ? 'active' : '' ?>">📄 Trading Plan</a>
                        <a href="index.php?view=journal" class="<?= strpos($view, 'trade') !== false || $view === 'journal' ? 'active' : '' ?>"><i class="fas fa-book-open"></i> Trading Journal</a>
                        <a href="index.php?view=notes" class="<?= $view === 'notes' ? 'active' : '' ?>">🗒️ Notes</a>
                        <a href="index.php?view=strategy" class="<?= $view === 'strategy' ? 'active' : '' ?>">⚙️ Trading Strategy</a>
                    </div>

                    <h3>PERFORMANCE</h3>
                    <div class="nav-links">
                        <a href="index.php?view=mpa" class="<?= $view === 'mpa' ? 'active' : '' ?>">🗓️ MPA</a>
                        <a href="index.php?view=qpa" class="<?= $view === 'qpa' ? 'active' : '' ?>">📈 QPA</a>
                    </div>

                    <h3>DATA</h3>
                    <div class="nav-links">
                        <a href="index.php?view=accounts" class="<?= $view === 'accounts' ? 'active' : '' ?>">👤 Accounts</a>
                    </div>
                    
                    <div class="nav-links mt-auto" style="margin-top: 40px;">
                        <button id="logout-btn" class="btn btn-danger w-100">
    						<i class="fas fa-sign-out-alt me-2"></i> Выход
						</button>
                    </div>
                </nav>
            </aside>

            <main class="content-area">
                <button id="mobile-menu-toggle">☰</button>

                <header class="mb-4 d-flex justify-content-between align-items-center"> 
                    </header>

                <div class="main-content-wrapper fade-in">
                    <?php include $view_path; ?>
                </div>
                
            </main>
        </div>
    <?php endif; ?>

    <div id="image-modal" class="modal image-modal">
		<span class="modal-close">&times;</span>
		<img class="modal-content" id="modal-image">
		<div id="modal-caption"></div>
	</div>
	<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/app.js?v=<?php echo time(); ?>"></script>

</body>
</html>