# 📖 Гайд: Как добавлять новые модули

## Структура проекта

```
/
├── index.php                  ← Главный файл (роутинг views + подключение JS/CSS)
├── api/
│   ├── api.php                ← PHP-роутер (только switch с case)
│   ├── db.php                 ← Подключение к базе данных
│   └── handlers/              ← PHP-обработчики по темам
│       ├── auth.php            login, register, logout
│       ├── lookups.php         get_lookups, get_ref_pairs, ...
│       ├── plans.php           savePlan, getPlans, ...
│       ├── trades.php          saveTrade, getTrades, ...
│       ├── notes.php           saveNote, getNotes, ...
│       ├── accounts.php        saveAccount, getAccountsData, ...
│       ├── payouts.php         savePayout, getPayouts, ...
│       ├── uploads.php         uploadImage, downloadImageFromUrl
│       ├── dashboard.php       getDashboardMetrics
│       ├── data_analysis.php   getDataAnalysis
│       ├── strategy.php        saveStrategy, getStrategies, ...
│       ├── mpa.php             getMPAAnalysis, getMPAMonthDetails, ...
│       └── qpa.php             getQPAList, getQPADetails, saveQPAReport
├── assets/
│   ├── style.css              ← Скомпилированный CSS (НЕ ИЗМЕНЯТЬ НАПРЯМУЮ, генерируется из SCSS)
│   ├── scss/                  ← Рабочие SCSS-стили
│   │   ├── style.scss          ГЛАВНЫЙ файл: импортирует все остальные
│   │   ├── _variables.scss     Цвета и переменные (CSS-переменные: :root)
│   │   ├── _layout.scss        Каркас, сайдбар, основной контент
│   │   ├── _cards.scss         Карточки планов, UI-элементы
│   │   └── ...                 Остальные разделенные файлы стилей
│   ├── app.js                 ← АРХИВ (не используется, можно удалить)
│   └── modules/               ← JS-модули
│       ├── core.js             Глобальные переменные, Quill, тултипы
│       ├── init.js             Главный DOMContentLoaded
│       └── ...                 Остальные JS файлы
└── views/                     ← PHP-шаблоны страниц
```

---

## ➕ Добавить новый PHP-обработчик (новая группа endpoints)

> **Пример:** добавляем модуль `goals` (цели трейдера)

### Шаг 1 — Создать файл обработчика

Создать `/api/handlers/goals.php`:

```php
<?php
// api/handlers/goals.php — Цели трейдера

function getGoals($pdo) {
    try {
        $uid  = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT * FROM goals WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$uid]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
```

### Шаг 2 — Подключить в api.php

В `/api/api.php` добавить две строки:

```php
// В блоке require обработчиков:
require __DIR__ . '/handlers/goals.php';  // ← добавить

// В switch($action):
case 'get_goals':    getGoals($conn); break;    // ← добавить
```

---

## ➕ Добавить новый JS-модуль

### Шаг 1 — Создать файл модуля `/assets/modules/goals.js`

```js
// assets/modules/goals.js
async function loadGoals() {
    // ...
}
```

### Шаг 2 — Подключить в `index.php`

В `/index.php` добавить строку **перед** `init.js`:

```html
<script src="assets/modules/goals.js?v=<?php echo time(); ?>"></script>
<script src="assets/modules/init.js?v=<?php echo time(); ?>"></script>  <!-- всегда последний -->
```

### Шаг 3 — Добавить роутинг в `init.js`

```js
if (view === 'goals') {
    loadGoals();
}
```

---

## ➕ Добавить новые CSS-стили (SCSS)

> **ВАЖНО:** Не меняй `assets/style.css` напрямую! Он собирается из папки `assets/scss/`.

### Шаг 1 — Создать новый парциал `_goals.scss`

Создай файл `/assets/scss/_goals.scss`:

```scss
/* =========================================
   ЦЕЛИ (Goals)
   ========================================= */
.goals-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.goals-card {
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: var(--border-radius-lg);
    padding: 20px;
    transition: all 0.2s ease;

    &:hover {
        background: var(--glass-bg-hover);
        border-color: var(--glass-border-hover);
        box-shadow: var(--glass-shadow);
    }
}
```

### Шаг 2 — Подключить `_goals.scss` в главный файл

Открой `/assets/scss/style.scss` и добавь строчку:

```scss
@use "goals";
```

### Шаг 3 — Скомпилировать SCSS (опционально/автоматически)

Убедись, что твой IDE (VSCode, PhpStorm) или сборщик умеет компилировать `style.scss` в `style.css`.
Обычно в редакторе стоит плагин `Live Sass Compiler` или что-то похожее.

---

## ➕ Добавить новую страницу (view)

### Шаг 1 — Создать PHP-шаблон `views/goals.php`

```php
<!-- views/goals.php -->
<div class="main-content-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title">Goals</h1>
    </div>
    <div id="goals-list"><!-- загружается через JS --></div>
</div>
```

### Шаг 2 — Добавить страницу в роутер в `index.php`

В `/index.php` найти блок `switch ($view)` и добавить пункт:

```php
case 'goals':
    include 'views/goals.php';
    break;
```

### Шаг 3 — Добавить пункт в меню (sidebar)

В `/index.php` в блоке `<nav class="nav-links">` добавить:

```html
<a href="index.php?view=goals" class="<?= ($view === 'goals') ? 'active' : '' ?>">
    <i class="fas fa-bullseye"></i> Goals
</a>
```

---

## 🔑 Ключевые переменные для цвета в стилях

Все важные цвета хранятся в `/assets/scss/_variables.scss` и должны использоваться через `var(--name)`:

- `var(--bg-dark)` - Основной фон страницы
- `var(--glass-bg)` - Фон карточек/элементов поверх фона
- `var(--glass-border)` - Рамки карточек
- `var(--text-main)` - Основной белый текст
- `var(--text-secondary)` - Приглушенный текст
- `var(--accent-blue)` - Основной голубой цвет проекта
- `var(--accent-green)` - Успех/прибыль
- `var(--accent-red)` - Ошибка/убыток
