<?php
// views/plan_create.php
// Форма для создания ИЛИ редактирования плана

// Проверяем, есть ли ID в URL. Если есть - мы в режиме РЕДАКТИРОВАНИЯ.
$plan_id = $_GET['id'] ?? null;
$is_edit_mode = !empty($plan_id);

// Заголовок и текст кнопки зависят от режима
$page_title = $is_edit_mode ? 'Edit plan' : 'Create new plan';
$submit_btn_text = $is_edit_mode ? 'Update plan' : 'Save plan';

// Текущая дата для режима создания
$default_date = (!$is_edit_mode) ? date('Y-m-d') : '';
?>

<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0" id="form-page-title" style="font-weight: 600;"><?= $page_title ?></h2>
    </div>

    <div class="card glass-panel mb-4 border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="card-body" style="padding: 40px;">
            
            <?php if ($is_edit_mode): ?>
                <input type="hidden" id="edit-plan-id" value="<?= htmlspecialchars($plan_id) ?>">
            <?php endif; ?>

            <form id="plan-form">
                
                <div id="hidden-plan-id-container"></div>

                <div class="form-section-grid">
                    <div class="form-group">
                        <label for="plan-pair" class="form-label">Pair</label>
                        <select class="select-field" id="plan-pair" name="pair_id" required>
                            <option value="">--- Loading... ---</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="plan-bias" class="form-label">Bias</label>
                        <select class="select-field" id="plan-bias" name="bias" required>
                            <option value="Neutral" selected>Neutral</option>
                            <option value="Bullish">Bullish</option>
                            <option value="Bearish">Bearish</option>
                        </select>
                    </div>
                </div>

                <div class="form-section-grid">
                    <div class="form-group">
                        <label for="plan-type" class="form-label">Plan type</label>
                        <select class="select-field" id="plan-type" name="type" required>
                            <option value="Weekly" selected>Weekly</option>
                            <option value="Daily">Daily</option>
                            <option value="Monthly">Monthly</option>
                            <option value="Long Term">Long Term</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="plan-date" class="form-label">Date</label>
                        <input type="date" class="input-field" id="plan-date" name="date" value="<?= $default_date ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="plan-title" class="form-label">Plan title</label>
                    <input type="text" class="input-field" id="plan-title" name="title"
                           placeholder="Example: Weekly Plan 17-21 Nov 2025" required>
                </div>
				
				<div class="form-group">
                    <label for="plan-note" class="form-label">Link Note</label>
                    <select class="select-field" id="plan-note" name="note_id">
                         <option value="">--- No Note ---</option>
                    </select>
                </div>

                <h2 style="font-size: 16px; color: var(--text-secondary); margin-top: 40px; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px;">Timeframe analysis</h2>

                <div id="timeframes-container">
                </div>

                <button type="button" class="btn btn-secondary" style="width: 100%; margin-top: 15px; padding: 15px; border-style: dashed;" onclick="addTimeframe()">
                    <span>+</span> Add timeframe
                </button>

                <div style="display: flex; gap: 15px; margin-top: 40px; padding-top: 20px; border-top: 1px solid var(--glass-border);">
                    <button type="button" class="btn btn-outline" style="flex-grow: 1;" onclick="window.history.back()">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="flex-grow: 2; font-size: 16px; padding: 15px;">
                        <span id="submit-btn-icon">💾</span> <span id="submit-btn-text"><?= $submit_btn_text ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>