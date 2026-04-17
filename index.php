<?php
// index.php - Главная точка входа

// 1. Инициализация сессии (ДОЛЖНО БЫТЬ В САМОМ НАЧАЛЕ ФАЙЛА)
session_start();

// 2. Определение текущего представления (view)
$view = $_GET['view'] ?? 'dashboard';

// Список страниц, доступных БЕЗ авторизации (Guest Pages)
$guestPages = ['login', 'register', 'forgot_password', 'reset_password'];

// 3. ПРОВЕРКА АВТОРИЗАЦИИ
// Если пользователь НЕ авторизован И пытается зайти на защищенную страницу
if (!isset($_SESSION['user_id']) && !in_array($view, $guestPages)) {
    header('Location: index.php?view=login');
    exit;
}

// Если пользователь уже авторизован, но пытается зайти на страницы входа/регистрации
if (isset($_SESSION['user_id']) && in_array($view, $guestPages)) {
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
        die('Error: File not found.');
    }
}

// Карта заголовков страниц (опционально, для красоты)
$pageTitles = [
    'dashboard' => 'Dashboard',
    'plans' => 'Trading Plans',
    'plan_create' => 'Create Plan',
    'plan_details' => 'Plan Details',
    'journal' => 'Trading Journal',
    'trade_create' => 'New Trade',
    'trade_details' => 'Trade Details',
	'note_details' => 'Note Details',
    'login' => 'Login',
    'register' => 'Register',
    'forgot_password' => 'Reset Password',
    'reset_password' => 'New Password',
    'mpa' => 'Monthly Analysis (MPA)',
    'qpa' => 'Quarterly Analysis (QPA)',
    'accounts' => 'Accounts',
    'data' => 'Data Analysis',
    'notes' => 'Notes',
    'strategy' => 'Strategy',
	'mpa_details' => 'Month Details',
];
$currentTitle = $pageTitles[$view] ?? 'TradeOS - Trading Operating System';

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"> <title><?php echo htmlspecialchars($currentTitle); ?> | TradeOS</title>
    
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#121212">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="assets/logo.png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/all.min.css">
    <link href="assets/quill.snow.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="assets/toastify.min.css">
    <link rel="stylesheet" href="assets/style.css?v=<?php echo time(); ?>">
</head>
<body>

    <?php if (in_array($view, $guestPages)): ?>
        <div class="login-container">
            <?php include $view_path; ?>
        </div>

    <?php else: ?>
        <div class="app-container">
            <aside class="sidebar" id="sidebar">
                <button id="sidebar-close-btn" onclick="closeMenu()">✕</button>
                <div class="logo">
                    <a href="index.php?view=dashboard" title="Main page">TOS</a>
                </div>
				
				<a href="index.php?view=profile" class="user-profile-widget text-reset" style="text-decoration:none;">
                    <div class="user-avatar">
                        <i class="fas fa-user" id="user-avatar"></i>
                    </div>
                    <div class="user-info">
                        <span class="user-welcome">Welcome back,</span>
                        <span class="user-name" id="sidebar-username">Loading...</span>
                    </div>
                </a>
                
                <nav class="nav-menu">
                    <h3>ROUTINE</h3>
                    <div class="nav-links">
                        <a href="index.php?view=dashboard" class="<?= $view === 'dashboard' ? 'active' : '' ?>">
                            <i class="fas fa-th-large"></i> Dashboard
                        </a>
                        <a href="index.php?view=plans" class="<?= strpos($view, 'plan') !== false ? 'active' : '' ?>">
                            <i class="fas fa-file-contract"></i> Trading Plan
                        </a>
                        <a href="index.php?view=journal" class="<?= strpos($view, 'trade') !== false || $view === 'journal' ? 'active' : '' ?>">
                            <i class="fas fa-book"></i> Trading Journal
                        </a>
                        <a href="index.php?view=notes" class="<?= $view === 'notes' ? 'active' : '' ?>">
                            <i class="fas fa-sticky-note"></i> Notes
                        </a>
                        <a href="index.php?view=strategy" class="<?= $view === 'strategy' ? 'active' : '' ?>">
                            <i class="fas fa-chess-knight"></i> Trading Strategy
                        </a>
                    </div>

                    <h3>PERFORMANCE</h3>
                    <div class="nav-links">
                        <a href="index.php?view=mpa" class="<?= $view === 'mpa' ? 'active' : '' ?>">
                            <i class="fas fa-calendar-check"></i> MPA
                        </a>
                        <a href="index.php?view=qpa" class="<?= $view === 'qpa' ? 'active' : '' ?>">
                            <i class="fas fa-chart-line"></i> QPA
                        </a>
                    </div>

                    <h3>DATA</h3>
                    <div class="nav-links">
                        <a href="index.php?view=accounts" class="<?= $view === 'accounts' ? 'active' : '' ?>">
                            <i class="fas fa-wallet"></i> Accounts
                        </a>
						<a href="index.php?view=data" class="<?= $view === 'data' ? 'active' : '' ?>">
                            <i class="fas fa-database"></i> Data Analysis
                        </a>
                    </div>
                    
                    <div class="nav-links mt-auto" style="margin-top: 40px;">
                        <button id="logout-btn" class="btn btn-danger w-100">
    						<i class="fas fa-sign-out-alt me-2"></i> Logout
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

    <button class="mobile-fab border-0" data-bs-toggle="modal" data-bs-target="#quickAddModal" onclick="initQuickTrade()" title="Quick Add Trade">
        <i class="fas fa-plus"></i>
    </button>
    
	<link href="assets/quill.snow.css" rel="stylesheet">
    <script src="assets/bootstrap.bundle.min.js"></script>
	<script src="assets/quill.js"></script>
	<script src="assets/chart.js"></script>
    <script type="text/javascript" src="assets/toastify.js"></script>
    <script src="assets/modules/core.js?v=<?php echo time(); ?>"></script>
    <script src="assets/modules/ui.js?v=<?php echo time(); ?>"></script>
    <script src="assets/modules/auth.js?v=<?php echo time(); ?>"></script>
    <script src="assets/modules/shared_ui.js?v=<?php echo time(); ?>"></script>
    <script src="assets/modules/forms.js?v=<?php echo time(); ?>"></script>
    <script src="assets/modules/notes.js?v=<?php echo time(); ?>"></script>
    <script src="assets/modules/plans.js?v=<?php echo time(); ?>"></script>
    <script src="assets/modules/trades.js?v=<?php echo time(); ?>"></script>
    <script src="assets/modules/dashboard.js?v=<?php echo time(); ?>"></script>
    <script src="assets/modules/accounts.js?v=<?php echo time(); ?>"></script>
    <script src="assets/modules/data_analysis.js?v=<?php echo time(); ?>"></script>
    <script src="assets/modules/strategy.js?v=<?php echo time(); ?>"></script>
    <script src="assets/modules/mpa.js?v=<?php echo time(); ?>"></script>
    <script src="assets/modules/qpa.js?v=<?php echo time(); ?>"></script>
    <script src="assets/modules/quick_trade.js?v=<?php echo time(); ?>"></script>
    <script src="assets/modules/init.js?v=<?php echo time(); ?>"></script>

    <div class="modal fade" id="quickAddModal" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(5px);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel border-0 text-white" style="background: rgba(18, 18, 18, 0.95); box-shadow: 0 0 20px rgba(0,0,0,0.8);">
            
            <div class="modal-header border-secondary border-opacity-25">
                <h5 class="modal-title fw-bold"><i class="fas fa-bolt text-warning me-2"></i>Quick Trade</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body">
                <form id="quick-trade-form">
                    <input type="hidden" name="status" value="pending">
                    <input type="hidden" name="risk_percent" value="1"> 
                    
                    <div class="mb-3">
                        <label class="form-label text-muted small text-uppercase fw-bold">Pair / Asset</label>
                        <select class="form-select bg-dark text-white border-secondary" id="trade-pair" name="pair_id" required>
                            <option value="">Select</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label text-muted small text-uppercase fw-bold">Direction</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="direction" id="q-long" value="Long" checked>
                                <label class="btn btn-outline-success btn-sm py-2" for="q-long">LONG</label>

                                <input type="radio" class="btn-check" name="direction" id="q-short" value="Short">
                                <label class="btn btn-outline-danger btn-sm py-2" for="q-short">SHORT</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small text-uppercase fw-bold">Timeframe</label>
                            <select class="form-select bg-dark text-white border-secondary" name="entry_timeframe">
                                <option value="M5">M5</option>
                                <option value="M15">M15</option>
                                <option value="H1">H1</option>
                                <option value="H4">H4</option>
                                <option value="1D">1D</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small text-uppercase fw-bold">Entry Date</label>
                        <input type="datetime-local" class="form-control bg-dark text-white border-secondary mb-2" id="quick-date" name="entry_date" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-muted small text-uppercase fw-bold">Account</label>
                        <select class="form-select bg-dark text-white border-secondary" id="trade-account" name="account_id" required>
                            <option value="">Select</option>
                        </select>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary fw-bold py-3">
                            SAVE TRADE
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>