<?php
// views/plan_details.php - Страница деталей торгового плана

// Получаем ID плана из URL
$plan_id = $_GET['id'] ?? null;

// Если ID не передан, показываем сообщение об ошибке и кнопку возврата
if (!$plan_id) {
    echo '<div class="error-state glass-panel" style="padding: 30px; margin: 30px auto; max-width: 600px; text-align: center;">
            <h2 style="color: var(--accent-red); margin-bottom: 20px;">Ошибка: ID плана не указан!</h2>
            <p style="color: var(--text-secondary); margin-bottom: 30px;">Невозможно загрузить детали плана без его идентификатора.</p>
            <button class="btn btn-primary" onclick="window.location.href=\'index.php?view=plans\'">Вернуться к списку планов</button>
          </div>';
    // Важно: прекращаем выполнение скрипта, чтобы не отображать остальную часть страницы
    return; 
}
?>

<input type="hidden" id="current-plan-id" value="<?= htmlspecialchars($plan_id) ?>">

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 20px;">
    <h1 id="plan-details-title" class="page-title" style="margin: 0;">Загрузка плана...</h1>
    <div class="plan-actions" style="display: flex; gap: 10px;">
        <button class="btn btn-secondary" onclick="alert('Функция редактирования находится в разработке.')">✏️ Редактировать</button>
        <button class="btn btn-danger" onclick="alert('Функция удаления находится в разработке.')">🗑️ Удалить</button>
        <button class="btn btn-outline" onclick="window.location.href='index.php?view=plans'">← Назад к списку</button>
    </div>
</div>

<div id="plan-details-container" class="glass-panel" style="padding: 30px; position: relative; min-height: 300px;">
    
    <section class="plan-overview" style="margin-bottom: 40px; border-bottom: 1px solid var(--glass-border); padding-bottom: 30px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 25px; margin-bottom: 25px;">
            <div class="detail-item">
                <span class="detail-label">Тип плана:</span>
                <span id="plan-type" class="detail-value">Загрузка...</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Инструмент (Pair):</span>
                <span id="plan-pair-symbol" class="detail-value">Загрузка...</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Дата плана:</span>
                <span id="plan-date" class="detail-value">Загрузка...</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Нарратив (Bias):</span>
                <span id="plan-bias" class="detail-value plan-bias-tag" style="align-self: flex-start;">Загрузка...</span>
            </div>
        </div>
        <div class="detail-item" style="display: inline-flex;">
            <span class="detail-label">Создан:</span>
            <span id="plan-created-at" class="detail-value" style="font-size: 0.9rem; color: var(--text-secondary);">Загрузка...</span>
        </div>
    </section>

    <section class="timeframes-section">
        <h2 style="margin-bottom: 25px; font-size: 1.5rem; color: var(--text-main); text-transform: uppercase; letter-spacing: 1px;">Анализ Таймфреймов</h2>
        
        <div id="timeframes-list" style="display: flex; flex-direction: column; gap: 30px;">
            <div class="loading-spinner" style="text-align: center; padding: 50px; color: var(--text-secondary);">
                Загрузка анализа таймфреймов...
            </div>
        </div>
    </section>
</div>

<style>
    /* Стили для блоков с деталями (Тип, Пара и т.д.) */
    .detail-item {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .detail-label {
        font-size: 0.85rem;
        color: var(--text-secondary);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .detail-value {
        font-size: 1.1rem;
        color: var(--text-main);
        font-weight: 600;
    }
    /* Базовый стиль для тега Bias (цвета добавляются через JS классы из style.css) */
    .plan-bias-tag {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        display: inline-block;
    }
    /* Стили для карточки отдельного таймфрейма */
    .timeframe-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid var(--glass-border);
        border-radius: 12px;
        padding: 25px;
        transition: all 0.3s ease;
    }
    .timeframe-card:hover {
        border-color: var(--glass-border-hover);
        background: rgba(255, 255, 255, 0.05);
    }
    /* Заголовок таймфрейма (например, "Daily") */
    .timeframe-card h3 {
        font-size: 1.25rem;
        color: var(--text-main);
        margin-top: 0;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--glass-border);
    }
    /* Стили для изображения внутри таймфрейма */
    .timeframe-card img {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        margin-bottom: 25px;
        border: 1px solid var(--glass-border);
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        display: block; /* Убираем лишний отступ снизу */
    }
    /* Контейнер для заметок */
    .notes-container {
        background: rgba(0, 0, 0, 0.2);
        border-radius: 8px;
        padding: 20px;
        border-left: 4px solid var(--accent-blue); /* Акцентная полоса слева */
    }
    .notes-label {
        display: block;
        font-size: 0.85rem;
        color: var(--text-secondary);
        margin-bottom: 10px;
        font-weight: 500;
        text-transform: uppercase;
    }
    /* Сами заметки */
    .notes-content {
        font-size: 1rem;
        color: var(--text-main);
        line-height: 1.6;
        white-space: pre-wrap; /* ВАЖНО: Сохраняет переносы строк и пробелы, введенные в textarea */
        font-family: 'Inter', sans-serif;
    }
    /* Адаптация для мобильных устройств */
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }
        .plan-actions {
            width: 100%;
            flex-wrap: wrap;
        }
        .plan-actions .btn {
            flex-grow: 1; /* Кнопки растягиваются на всю ширину */
        }
    }
</style>

<script>
    // Ждем полной загрузки структуры страницы (DOM)
    document.addEventListener('DOMContentLoaded', () => {
        // Проверяем, существует ли функция loadPlanDetails в глобальной области видимости (она должна быть в app.js)
        if (typeof loadPlanDetails === 'function') {
            // Вызываем функцию загрузки деталей плана
            loadPlanDetails();
        } else {
            // Если функция не найдена, выводим ошибку
            console.error('Функция loadPlanDetails не найдена. Возможно, файл assets/app.js не загружен или поврежден.');
            document.getElementById('plan-details-container').innerHTML = 
                '<div class="error-state" style="padding: 30px; text-align: center; color: var(--accent-red);">' +
                'Ошибка: Не удалось инициализировать загрузку данных. Пожалуйста, обновите страницу или проверьте консоль браузера.' +
                '</div>';
        }
    });
</script>
