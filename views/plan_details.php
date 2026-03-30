<?php
// views/plan_details.php - Страница деталей торгового плана

// Получаем ID плана из URL
$plan_id = $_GET['id'] ?? null;

// Если ID не передан, показываем сообщение об ошибке и кнопку возврата
if (!$plan_id) {
    echo '<div class="error-state glass-panel" style="padding: 30px; margin: 30px auto; max-width: 600px; text-align: center;">
            <h2 style="color: var(--accent-red); margin-bottom: 20px;">Error: Plan ID not stated!</h2>
            <p style="color: var(--text-secondary); margin-bottom: 30px;">Can not load plan details without plan ID.</p>
            <button class="btn btn-primary" onclick="window.location.href=\'index.php?view=plans\'">Back to plans list</button>
          </div>';
    // Важно: прекращаем выполнение скрипта, чтобы не отображать остальную часть страницы
    return; 
}
?>

<input type="hidden" id="current-plan-id" value="<?= htmlspecialchars($plan_id) ?>">

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 20px;">
    <h1 id="plan-details-title" class="page-title" style="margin: 0;">Loading plan...</h1>
    
    <div class="plan-actions" style="display: flex; gap: 10px;">
		<a href="index.php?view=plans" class="btn btn-outline">
            <i class="fas fa-arrow-left me-2"></i> Back
        </a>
        <button class="btn btn-secondary" onclick="window.location.href='index.php?view=plan_create&id=<?= $plan_id ?>'">
            <i class="fas fa-edit me-2"></i> Edit
        </button>
        <button class="btn btn-danger" onclick="deleteEntity(<?= $plan_id ?>, 'delete_plan', 'plans')">
            <i class="fas fa-trash-alt me-2"></i> Delete
        </button>
    </div>
</div>

<div id="plan-details-container" class="glass-panel" style="padding: 30px; position: relative; min-height: 300px;">
    
    <section class="plan-overview" style="margin-bottom: 40px; border-bottom: 1px solid var(--glass-border); padding-bottom: 30px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 25px; margin-bottom: 25px;">
            <div class="detail-item">
                <span class="detail-label">Plan type:</span>
                <span id="plan-type" class="detail-value">Loading...</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Pair:</span>
                <span id="plan-pair-symbol" class="detail-value">Loading...</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Plan date:</span>
                <span id="plan-date" class="detail-value">Loading...</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Bias:</span>
                <span id="plan-bias" class="detail-value plan-bias-tag" style="align-self: flex-start;">Loading...</span>
            </div>
        </div>
        <div class="detail-item" style="display: inline-flex;">
            <span class="detail-label">Created:</span>
            <span id="plan-created-at" class="detail-value" style="font-size: 0.9rem; color: var(--text-secondary);">Loading...</span>
        </div>
    </section>

    <section class="timeframes-section">
        <h2 style="margin-bottom: 25px; font-size: 1.5rem; color: var(--text-main); text-transform: uppercase; letter-spacing: 1px;">Timeframe analysis</h2>
        
        <div id="timeframes-list" style="display: flex; flex-direction: column; gap: 30px;">
            <div class="loading-spinner" style="text-align: center; padding: 50px; color: var(--text-secondary);">
                Loading timeframe analysis...
            </div>
        </div>
    </section>
</div>

<script>
    // Ждем полной загрузки структуры страницы (DOM)
    document.addEventListener('DOMContentLoaded', () => {
        // Проверяем, существует ли функция loadPlanDetails в глобальной области видимости (она должна быть в app.js)
        if (typeof loadPlanDetails === 'function') {
            // Вызываем функцию загрузки деталей плана
            loadPlanDetails();
        } else {
            // Если функция не найдена, выводим ошибку
            console.error('Function loadPlanDetails not found. Possible that assets/app.js not loaded or damaged.');
            document.getElementById('plan-details-container').innerHTML = 
                '<div class="error-state" style="padding: 30px; text-align: center; color: var(--accent-red);">' +
                'Error: Could not initialyse data loading. Please refresh the page or check console.' +
                '</div>';
        }
    });
</script>