<?php
// views/profile.php
?>

<!-- Hidden inputs for JS -->
<input type="hidden" id="profile-display-username" value="">
<input type="hidden" id="profile-display-email" value="">
<input type="hidden" id="profile-join-date" value="">

<div class="fade-in" style="max-width: 900px; margin: 0 auto;">

    <div class="profile-tabs-nav">
        <button class="profile-tab-btn active" data-tab="overview">Overview</button>
        <button class="profile-tab-btn" data-tab="settings">Preferences</button>
    </div>

    <!-- Вкладка 1: Overview (Ваш текущий контент) -->
    <div id="tab-overview" class="profile-tab-content active">
    <!-- Page Header -->
    <div class="profile-page-header">
        <div class="profile-header-glow"></div>
        <div class="profile-avatar-wrap">
            <div class="profile-avatar-ring">
                <div class="profile-avatar-inner">
                    <i class="fas fa-user"></i>
                </div>
            </div>
            <div class="profile-avatar-status"></div>
        </div>
        <div class="profile-header-info">
            <h1 class="profile-username" id="profile-page-name"><?= $lang['loading'] ?></h1>
            <p class="profile-email" id="profile-page-email">...</p>
            <div class="profile-badge">
                <i class="fas fa-shield-alt me-1"></i>
                <span id="profile-page-role"><?= $lang['trader'] ?? 'Trader' ?></span>
            </div>

        </div>
    </div>

    <!-- Stats Strip -->
    <div class="profile-stats-strip">
        <div class="profile-stat-item">
            <i class="fas fa-calendar-check profile-stat-icon text-profit"></i>
            <div>
                <div class="profile-stat-label"><?= $lang['joined'] ?></div>
                <div class="profile-stat-value" id="profile-page-date">—</div>
            </div>
        </div>
        <div class="profile-stat-divider"></div>
        <div class="profile-stat-item">
            <i class="fas fa-chart-line profile-stat-icon" style="color: var(--accent-blue)"></i>
            <div>
                <div class="profile-stat-label"><?= $lang['total_trades'] ?></div>
                <div class="profile-stat-value" id="profile-stat-trades">—</div>
            </div>
        </div>
        <div class="profile-stat-divider"></div>
        <div class="profile-stat-item">
            <i class="fas fa-trophy profile-stat-icon" style="color: var(--accent-yellow, #f59e0b)"></i>
            <div>
                <div class="profile-stat-label"><?= $lang['winrate'] ?></div>
                <div class="profile-stat-value" id="profile-stat-winrate">—</div>
            </div>
        </div>
        <div class="profile-stat-divider"></div>
        <div class="profile-stat-item">
            <i class="fas fa-dollar-sign profile-stat-icon text-profit"></i>
            <div>
                <div class="profile-stat-label"><?= $lang['net_profit'] ?></div>
                <div class="profile-stat-value" id="profile-stat-pnl">—</div>
            </div>
        </div>
        <div class="profile-stat-divider"></div>
        <!-- Account filter -->
        <div class="profile-stat-item profile-acc-filter">
            <div class="profile-acc-top-row">
                <i class="fas fa-wallet profile-stat-icon" style="color: var(--text-secondary)"></i>
                <span class="profile-stat-label"><?= $lang['account'] ?? 'Account' ?></span>
            </div>
            <div class="profile-acc-select-wrapper">
                <select id="profile-account-select" class="profile-acc-select">
                    <option value=""><?= $lang['all_accounts'] ?? 'All accounts' ?></option>
                </select>
            </div>
        </div>
    </div>

    <!-- Settings Cards Grid -->
    <div class="profile-cards-grid">

        <!-- Language Card -->
        <div class="profile-card glass-panel">
            <div class="profile-card-header">
                <div class="profile-card-icon" style="background: linear-gradient(135deg, #667eea, #764ba2)">
                    <i class="fas fa-globe"></i>
                </div>
                <div>
                    <div class="profile-card-title"><?= $lang['language'] ?></div>
                    <div class="profile-card-subtitle"><?= $lang['interface_language'] ?? 'Interface language' ?></div>
                </div>
            </div>
            <div class="profile-card-body">
                <div class="lang-options">
                    <label class="lang-option" id="lang-opt-en">
                        <input type="radio" name="profile-lang" value="en" style="display:none">
                        <span class="lang-flag">🇬🇧</span>
                        <span class="lang-name"><?= $lang['english'] ?></span>
                        <i class="fas fa-check lang-check"></i>
                    </label>
                    <label class="lang-option" id="lang-opt-ru">
                        <input type="radio" name="profile-lang" value="ru" style="display:none">
                        <span class="lang-flag">🇷🇺</span>
                        <span class="lang-name"><?= $lang['russian'] ?></span>
                        <i class="fas fa-check lang-check"></i>
                    </label>
                </div>
                <!-- hidden select for compatibility with existing JS -->
                <select id="profile-language-select" style="display:none">
                    <option value="en"><?= $lang['english'] ?></option>
                    <option value="ru"><?= $lang['russian'] ?></option>
                </select>
            </div>
        </div>

        <!-- Account Info Card -->
        <div class="profile-card glass-panel">
            <div class="profile-card-header">
                <div class="profile-card-icon" style="background: linear-gradient(135deg, #0ea5e9, #06b6d4)">
                    <i class="fas fa-id-card"></i>
                </div>
                <div>
                    <div class="profile-card-title"><?= $lang['account_info'] ?? 'Account Info' ?></div>
                    <div class="profile-card-subtitle"><?= $lang['your_credentials'] ?? 'Your credentials' ?></div>
                </div>
            </div>
            <div class="profile-card-body">
                <div class="profile-info-row">
                    <span class="profile-info-label"><i
                            class="fas fa-user me-2 text-muted"></i><?= $lang['username'] ?? 'Username' ?></span>
                    <span class="profile-info-value" id="profile-info-name">—</span>
                </div>
                <div class="profile-info-row">
                    <span class="profile-info-label"><i
                            class="fas fa-envelope me-2 text-muted"></i><?= $lang['email'] ?? 'Email' ?></span>
                    <span class="profile-info-value" id="profile-info-email">—</span>
                </div>
                <div class="profile-info-row">
                    <span class="profile-info-label"><i
                            class="fas fa-clock me-2 text-muted"></i><?= $lang['member_since'] ?? 'Member since' ?></span>
                    <span class="profile-info-value" id="profile-info-date">—</span>
                </div>
            </div>
        </div>

    </div>
</div>
<div id="tab-settings" class="profile-tab-content">
    <div class="profile-cards-grid">

            <!-- Настройка Таймфреймов -->
            <div class="profile-card glass-panel">
                <div class="profile-card-header">
                    <div class="profile-card-icon" style="background: #6366f1;"><i class="fas fa-clock"></i></div>
                    <div class="profile-card-title">Timeframes</div>
                </div>
                <div class="profile-card-body">
                    <div class="setting-input-group">
                        <input type="text" id="new-tf-name" class="profile-input" placeholder="e.g. M15">
                        <button class="btn-add" onclick="addSetting('timeframe', 'new-tf-name')">+</button>
                    </div>
                    <ul id="list-timeframes" class="settings-list"></ul>
                </div>
            </div>

            <!-- Настройка Стилей -->
            <div class="profile-card glass-panel">
                <div class="profile-card-header">
                    <div class="profile-card-icon" style="background: #a855f7;"><i class="fas fa-paint-brush"></i></div>
                    <div class="profile-card-title">Styles</div>
                </div>
                <div class="profile-card-body">
                    <div class="setting-input-group">
                        <input type="text" id="new-style-name" class="profile-input" placeholder="e.g. Scalping">
                        <button class="btn-add" onclick="addSetting('style', 'new-style-name')">+</button>
                    </div>
                    <ul id="list-styles" class="settings-list"></ul>
                </div>
            </div>
            <!-- Новая карточка: Entry Models -->
            <div class="profile-card glass-panel">
                <div class="profile-card-header">
                    <div class="profile-card-icon" style="background: linear-gradient(135deg, #f87171, #ef4444);">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div class="profile-card-title">Entry Models</div>
                </div>
                <div class="profile-card-body">
                    <div class="setting-input-group">
                        <input type="text" id="new-model-name" class="profile-input" placeholder="e.g. FVG">
                        <button class="btn-add" onclick="addSetting('model', 'new-model-name')">+</button>
                    </div>
                    <ul id="list-models" class="settings-list"></ul>
                </div>
            </div>
    </div>

            <!-- Настройка Пар -->
            <div class="profile-card glass-panel" style="grid-column: 1 / -1; margin-top: 15px;">
                <div class="profile-card-header">
                    <div class="profile-card-icon" style="background: #06b6d4;"><i class="fas fa-coins"></i></div>
                    <div class="profile-card-title">Trading Pairs</div>
                </div>
                <div class="profile-card-body">
                    <div class="setting-input-group pair-inputs">
                        <input type="text" id="new-pair-symbol" class="profile-input" placeholder="BTCUSDT">
                        <select id="new-pair-type" class="profile-input">
                            <option value="Crypto">Crypto</option>
                            <option value="Forex">Forex</option>
                        </select>
                        <button class="btn-add" onclick="addPair()">+</button>
                    </div>
                    <ul id="list-pairs" class="settings-list"></ul>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    /* =================== PROFILE PAGE =================== */
    .profile-page-header {
        position: relative;
        display: flex;
        align-items: center;
        gap: 28px;
        padding: 36px 36px 32px;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.12) 0%, rgba(168, 85, 247, 0.08) 100%);
        border: 1px solid rgba(99, 102, 241, 0.2);
        border-radius: 20px;
        margin-bottom: 20px;
        overflow: hidden;
    }

    .profile-header-glow {
        position: absolute;
        top: -60px;
        left: -60px;
        width: 200px;
        height: 200px;
        background: radial-gradient(circle, rgba(99, 102, 241, 0.25) 0%, transparent 70%);
        pointer-events: none;
    }

    .profile-avatar-wrap {
        position: relative;
        flex-shrink: 0;
    }

    .profile-avatar-ring {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        padding: 3px;
        background: linear-gradient(135deg, #6366f1, #a855f7, #06b6d4);
        animation: spin-ring 8s linear infinite;
    }

    @keyframes spin-ring {
        from {
            filter: hue-rotate(0deg);
        }

        to {
            filter: hue-rotate(360deg);
        }
    }

/* Tabs Navigation */
.profile-tabs-nav {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    background: rgba(255, 255, 255, 0.05);
    padding: 5px;
    border-radius: 12px;
}

.profile-tab-btn {
    flex: 1;
    padding: 12px;
    border: none;
    background: transparent;
    color: var(--text-secondary);
    font-weight: 600;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.profile-tab-btn.active {
    background: rgba(99, 102, 241, 0.2);
    color: #fff;
    box-shadow: 0 0 15px rgba(99, 102, 241, 0.3);
}

.profile-tab-content {
    display: none;
}

.profile-tab-content.active {
    display: block;
    animation: fadeIn 0.4s ease;
}

/* Settings List & Inputs */
.setting-input-group {
    display: flex;
    gap: 8px;
    margin-bottom: 15px;
}

.pair-inputs {
    flex-wrap: wrap;
}

.profile-input {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 8px 12px;
    border-radius: 8px;
    color: #fff;
    flex: 1;
}

.btn-add {
    background: #6366f1;
    color: white;
    border: none;
    width: 35px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
}

.settings-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.settings-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.btn-del {
    color: #ef4444;
    cursor: pointer;
    opacity: 0.6;
}

.btn-del:hover { opacity: 1; }

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}


    .profile-avatar-inner {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: var(--bg-card, #1a1a2e);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.2rem;
        color: #6366f1;
    }

    .profile-avatar-status {
        position: absolute;
        bottom: 4px;
        right: 4px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #22c55e;
        border: 3px solid var(--bg-main, #121212);
        animation: pulse-status 2s ease-in-out infinite;
    }

    @keyframes pulse-status {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4);
        }

        50% {
            box-shadow: 0 0 0 6px rgba(34, 197, 94, 0);
        }
    }

    .profile-header-info {
        flex: 1;
        min-width: 0;
    }

    .profile-username {
        font-size: 1.9rem;
        font-weight: 800;
        color: #fff;
        margin: 0 0 4px;
        background: linear-gradient(135deg, #fff 60%, #a855f7);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        line-height: 1.2;
    }

    .profile-email {
        color: var(--text-secondary);
        font-size: 0.95rem;
        margin: 0 0 12px;
    }

    .profile-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 14px;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.25), rgba(168, 85, 247, 0.2));
        border: 1px solid rgba(99, 102, 241, 0.35);
        color: #a5b4fc;
    }

    /* Stats Strip */
    .profile-stats-strip {
        display: flex;
        align-items: center;
        gap: 0;
        background: var(--bg-card, #1a11a1a);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 16px;
        padding: 20px 15px; /* Уменьшили боковые отступы (было 28px) */
        margin-bottom: 20px;
        overflow: hidden; /* Чтобы ничего не вылезало за границы */
    }

    .profile-stat-item {
        display: flex;
        align-items: center;
        gap: 14px;
        flex: 1;
    }

    .profile-stat-icon {
        width: 40px;
        text-align: center;
        flex-shrink: 0;
    }

    .profile-stat-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--text-secondary);
        font-weight: 600;
        margin-bottom: 3px;
    }

    .profile-stat-value {
        font-size: 1.15rem;
        font-weight: 700;
        color: #fff;
    }

    .profile-stat-divider {
        width: 1px;
        height: 44px;
        background: rgba(255, 255, 255, 0.08);
        margin: 0 12px; /* Было 24px — это и вызывало переполнение */
        flex-shrink: 0;
    }

    /* Cards Grid */
    .profile-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 16px;
    }

    .profile-card {
        border-radius: 16px;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .profile-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
    }

    .profile-card-header {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 20px 22px 14px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .profile-card-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        color: #fff;
        flex-shrink: 0;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }

    .profile-card-title {
        font-weight: 700;
        font-size: 0.95rem;
        color: #fff;
    }

    .profile-card-subtitle {
        font-size: 0.78rem;
        color: var(--text-secondary);
        margin-top: 2px;
    }

    .profile-card-body {
        padding: 20px 22px;
    }

    /* Language options */
    .lang-options {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .lang-option {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 12px 16px;
        border-radius: 12px;
        border: 1.5px solid rgba(255, 255, 255, 0.07);
        background: rgba(255, 255, 255, 0.03);
        cursor: pointer;
        transition: all 0.2s ease;
        user-select: none;
    }

    .lang-option:hover {
        border-color: rgba(99, 102, 241, 0.4);
        background: rgba(99, 102, 241, 0.08);
    }

    .lang-option.active {
        border-color: #6366f1;
        background: rgba(99, 102, 241, 0.15);
    }

    .lang-flag {
        font-size: 1.4rem;
        line-height: 1;
    }

    .lang-name {
        flex: 1;
        font-weight: 600;
        font-size: 0.92rem;
        color: #fff;
    }

    .lang-check {
        color: #6366f1;
        opacity: 0;
        transition: opacity 0.2s;
        font-size: 0.85rem;
    }

    .lang-option.active .lang-check {
        opacity: 1;
    }

    /* Info rows */
    .profile-info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .profile-info-row:last-child {
        border-bottom: none;
    }

    .profile-info-label {
        font-size: 0.85rem;
        color: var(--text-secondary);
        display: flex;
        align-items: center;
    }

    .profile-info-value {
        font-size: 0.9rem;
        font-weight: 600;
        color: #fff;
        text-align: right;
        max-width: 55%;
        word-break: break-all;
    }

    /* Logout button */
    .profile-logout-btn {
        width: 100%;
        padding: 12px 20px;
        border: 1.5px solid rgba(239, 68, 68, 0.35);
        background: rgba(239, 68, 68, 0.08);
        color: #f87171;
        border-radius: 12px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .profile-logout-btn:hover {
        background: rgba(239, 68, 68, 0.18);
        border-color: rgba(239, 68, 68, 0.6);
        color: #fca5a5;
        transform: translateY(-1px);
    }

    /* Account filter in stats strip */
    .profile-acc-filter {
        flex-direction: column !important; /* Выстраиваем элементы вертикально */
        align-items: flex-start !important; /* Прижимаем всё к левому краю */
        gap: 8px; /* Расстояние между строкой с иконкой и селектом */
    }

    /* Верхняя строка: Иконка + Текст в один ряд */
    .profile-acc-top-row {
        display: flex;
        align-items: center; /* Центрируем иконку и текст по вертикали */
        gap: 10px; /* Расстояние между иконкой и текстом */
        width: 100%;
    }

    /* Убираем лишние отступы у иконки, чтобы она не раздувала строку */
    .profile-acc-top-row .profile-stat-icon {
        margin: 0;
        font-size: 1.2rem;
    }

    /* Контейнер для селекта, чтобы он занимал всю ширину */
    .profile-acc-select-wrapper {
        width: 100%;
    }

    .profile-acc-select {
        appearance: none;
        -webkit-appearance: none;
        background: rgba(255, 255, 255, 0.07) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%238    %23888'/%3E%3C/svg%3E") no-repeat right 10px center;
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 8px;
        color: #fff;
        font-size: 0.85rem;
        font-weight: 600;
        padding: 6px 30px 6px 10px; /* Немного увеличили высоту для удобства */
        cursor: pointer;
        width: 100%;
        transition: all 0.2s ease;
        outline: none;
    }

    .profile-acc-select:hover,
    .profile-acc-select:focus {
        border-color: rgba(99, 102, 241, 0.5);
        background-color: rgba(99, 102, 241, 0.1);
    }

    .profile-acc-select option {
        background: #1a1a2e;
        color: #fff;
    }

    @media (max-width: 768px) {

        .profile-page-header {
            flex-direction: column;
            text-align: center;
            padding: 28px 20px;
        }

        .profile-stats-strip {
            flex-wrap: wrap;
            gap: 16px;
        }

        .profile-stat-divider {
            display: none;
        }

        .profile-stat-item {
            flex: 1 1 40%;
        }

        .profile-username {
            font-size: 1.5rem;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- TAB SWITCHING LOGIC ---
    const tabBtns = document.querySelectorAll('.profile-tab-btn');
    const tabContents = document.querySelectorAll('.profile-tab-content');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetTab = btn.dataset.tab;

            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));

            btn.classList.add('active');
            document.getElementById(`tab-${targetTab}`).classList.add('active');

            if (targetTab === 'settings') loadUserSettings();
        });
    });

    // --- SETTINGS CRUD LOGIC ---

    function loadUserSettings() {
        fetch('api/api.php?action=get_user_settings')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    renderList('list-timeframes', data.timeframes, 'timeframe');
                    renderList('list-styles', data.styles, 'style');
                    if (data.models) {
                        renderList('list-models', data.models, 'model');
                    }
                    renderPairs(data.pairs);
                }
            });
    // 2. НОВОЕ: Загружаем список доступных ТИПОВ из базы данных для селекта
    fetch('api/api.php?action=get_pair_types')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('new-pair-type');
                // Очищаем старые опции, кроме первой (если нужно) или просто пересоздаем
                select.innerHTML = ''; 
                data.types.forEach(type => {
                    const opt = document.createElement('option');
                    opt.value = type;
                    opt.textContent = type;
                    select.appendChild(opt);
                });
            }
        })
        .catch(err => console.error("Error loading pair types:", err));
    }

    function renderList(elementId, items, type) {
        const container = document.getElementById(elementId);
        container.innerHTML = '';
        items.forEach(item => {
            const li = document.createElement('li');
            li.className = 'settings-item';
            li.innerHTML = `
                <span>${item.name}</span>
                <i class="fas fa-trash btn-del" onclick="deleteSetting('${type}', ${item.id})"></i>
            `;
            container.appendChild(li);
        });
    }

    function renderPairs(pairs) {
        const container = document.getElementById('list-pairs');
        container.innerHTML = '';
        pairs.forEach(p => {
            const li = document.createElement('li');
            li.className = 'settings-item';
            li.innerHTML = `
                <span>${p.symbol} <small class='text-muted'>(${p.type})</small></span>
                <i class="fas fa-trash btn-del" onclick="deleteSetting('pair', ${p.id}, '${p.symbol}')"></i>
            `;
            container.appendChild(li);
        });
    }

    // Add Timeframe or Style
    window.addSetting = function(type, inputId) {
        const input = document.getElementById(inputId);
        const val = input.value.trim();
        if (!val) return;

        const formData = new FormData();
        formData.append('action', 'add_user_setting');
        formData.append('type', type);
        formData.append('name', val);

        fetch('api/api.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    input.value = '';
                    loadUserSettings();
                }
            });
    };

    // Add Pair
    window.addPair = function() {
        const symbol = document.getElementById('new-pair-symbol').value.trim();
        const type = document.getElementById('new-pair-type').value;
        if (!symbol) return;

        const formData = new FormData();
        formData.append('action', 'add_user_setting');
        formData.append('type', 'pair');
        formData.append('symbol', symbol);
        formData.append('pair_type', type);

        fetch('api/api.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    document.getElementById('new-pair-symbol').value = '';
                    loadUserSettings();
                }
            });
    };

    // Delete Setting
    window.deleteSetting = function(type, id, extra = '') {
        const formData = new FormData();
        formData.append('action', 'delete_user_setting');
        formData.append('type', type);
        formData.append('id', id);
        if (extra) formData.append('extra', extra);

        fetch('api/api.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) loadUserSettings();
            });
    };
});


    document.addEventListener('DOMContentLoaded', function () {
        // Wire up language card to hidden select + existing handler
        const langOptions = document.querySelectorAll('.lang-option');
        const hiddenSelect = document.getElementById('profile-language-select');

        langOptions.forEach(opt => {
            opt.addEventListener('click', function () {
                const val = this.querySelector('input[type="radio"]').value;
                hiddenSelect.value = val;
                hiddenSelect.dispatchEvent(new Event('change'));
            });
        });

        // Sync visual state of language UI
        function syncLangUI(lang) {
            document.querySelectorAll('.lang-option').forEach(o => o.classList.remove('active'));
            const target = document.getElementById('lang-opt-' + lang);
            if (target) target.classList.add('active');
        }

        // Override syncLanguageSelect to also update the visual lang buttons
        const _origSync = window.syncLanguageSelect;
        window.syncLanguageSelect = function (lang) {
            if (_origSync) _origSync(lang);
            syncLangUI(lang);
        };

        // Load user info directly and populate all profile elements
        fetch('api/api.php?action=get_user_info')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Header
                    const nameEl = document.getElementById('profile-page-name');
                    if (nameEl) nameEl.textContent = data.username || '—';

                    const emailEl = document.getElementById('profile-page-email');
                    if (emailEl) emailEl.textContent = data.email || '—';

                    const roleEl = document.getElementById('profile-page-role');
                    if (roleEl && data.role) roleEl.textContent = data.role;

                    const dateEl = document.getElementById('profile-page-date');
                    if (dateEl) dateEl.textContent = data.created_at || '—';

                    // Info card rows
                    const infoName = document.getElementById('profile-info-name');
                    if (infoName) infoName.textContent = data.username || '—';

                    const infoEmail = document.getElementById('profile-info-email');
                    if (infoEmail) infoEmail.textContent = data.email || '—';

                    const infoDate = document.getElementById('profile-info-date');
                    if (infoDate) infoDate.textContent = data.created_at || '—';

                    // Sync lang UI
                    if (data.language) syncLangUI(data.language);
                }
            })
            .catch(() => { });

        // Logout button
        document.getElementById('profile-logout-btn')?.addEventListener('click', logout);

        // Populate accounts dropdown
        fetch('api/api.php?action=get_accounts_data')
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    const sel = document.getElementById('profile-account-select');
                    data.data.forEach(acc => {
                        const opt = document.createElement('option');
                        opt.value = acc.id;
                        opt.textContent = acc.name;
                        sel.appendChild(opt);
                    });
                }
            })
            .catch(() => { });

        // Load stats — optionally filtered by account
        function loadProfileStats(accountId) {
            const url = accountId
                ? `api/api.php?action=get_dashboard_metrics&account_id=${accountId}`
                : 'api/api.php?action=get_dashboard_metrics';

            ['profile-stat-trades', 'profile-stat-winrate', 'profile-stat-pnl'].forEach(id => {
                const el = document.getElementById(id);
                if (el) { el.textContent = '…'; el.style.color = ''; }
            });

            fetch(url)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const m = data.data || {};

                        const total = m.total_trades ?? null;
                        document.getElementById('profile-stat-trades').textContent = total != null ? total : '—';

                        const wr = m.win_rate != null ? parseFloat(m.win_rate).toFixed(1) + '%' : null;
                        document.getElementById('profile-stat-winrate').textContent = wr ?? '—';

                        const pnlRaw = m.total_pnl ?? null;
                        const pnlEl = document.getElementById('profile-stat-pnl');
                        if (pnlRaw != null) {
                            const pnlVal = parseFloat(pnlRaw);
                            pnlEl.textContent = (pnlVal >= 0 ? '+' : '') + pnlVal.toFixed(2) + '$';
                            pnlEl.style.color = pnlVal >= 0 ? 'var(--accent-green, #22c55e)' : 'var(--accent-red, #ef4444)';
                        } else {
                            pnlEl.textContent = '—';
                            pnlEl.style.color = '';
                        }
                    }
                })
                .catch(() => { });
        }

        // Wire account selector to reload stats
        const accSelect = document.getElementById('profile-account-select');
        accSelect?.addEventListener('change', () => loadProfileStats(accSelect.value || null));

        // Initial load
        loadProfileStats(null);
    });
</script>