<?php
// views/account_create.php
$acc_id = $_GET['id'] ?? null;
$is_edit = !empty($acc_id);
$page_title = $is_edit ? 'Редактировать Счет' : 'Новый Торговый Счет';
$btn_text = $is_edit ? 'Обновить Счет' : 'Создать Счет';
?>

<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0" id="form-page-title" style="font-weight: 600;"><?= $page_title ?></h2>
    </div>

    <div class="card glass-panel mb-4 border-0 shadow-sm" style="border-radius: 16px; overflow: hidden; max-width: 850px; margin: 0 auto;">
        <div class="card-body" style="padding: 40px;">
            
            <input type="hidden" id="edit-acc-id" value="<?= htmlspecialchars($acc_id ?? '') ?>">

            <form id="account-form">
                
                <div class="mb-5"> <h5 class="form-section-title">Основные данные</h5>
                    
                    <div class="form-group mb-4">
                        <label for="acc-name" class="form-label fw-bold">Название счета</label>
                        <input type="text" id="acc-name" name="name" class="input-field" style="font-size: 1.1rem; padding: 14px;" placeholder="Например: FTMO 100k Swing" required>
                    </div>

                    <div class="row g-4"> <div class="col-md-4">
                            <label for="acc-type" class="form-label fw-bold">Тип Счета</label>
                            <select id="acc-type" name="type" class="select-field" onchange="togglePropFields()">
                                <option value="Challenge" selected>Challenge</option>
                                <option value="Verification">Verification</option>
                                <option value="Funded">Funded</option>
                                <option value="Live">Live</option>
                                <option value="Demo">Demo</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="acc-starting" class="form-label fw-bold">Размер Счета (Starting)</label>
                            <input type="number" id="acc-starting" name="starting_equity" class="input-field" step="0.01" placeholder="100000" required oninput="syncBalanceField()">
                            <div class="form-text">Изначальный размер (для расчета целей)</div>
                        </div>
						</br>
                        <div class="col-md-4">
                            <label for="acc-balance" class="form-label fw-bold">Начало Журнала (Balance)</label>
                            <input type="number" id="acc-balance" name="balance" class="input-field" step="0.01" placeholder="100000" required>
                            <div class="form-text">Текущий баланс на момент добавления</div>
                        </div>
                    </div>
                </div>

                <div id="prop-settings" class="prop-settings-box">
                    <h5 class="form-section-title" style="margin-bottom: 25px; border-color: rgba(255,255,255,0.1);">
                        <i class="fas fa-chart-pie me-2"></i> Параметры Проп-фирмы
                    </h5>
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label for="acc-target" class="form-label" style="color: var(--accent-green); font-weight: 600;">
                                <i class="fas fa-bullseye me-1"></i> Цель прибыли (%)
                            </label>
                            <input type="number" id="acc-target" name="target_percent" class="input-field" step="0.1" placeholder="Например: 10">
                            <div class="form-text">Оставьте 0, если цели нет (Live/Funded).</div>
                        </div>
                        <div class="col-md-6">
                            <label for="acc-dd" class="form-label" style="color: var(--accent-red); font-weight: 600;">
                                <i class="fas fa-arrow-down me-1"></i> Макс. просадка (%)
                            </label>
                            <input type="number" id="acc-dd" name="max_drawdown_percent" class="input-field" step="0.1" placeholder="Например: 10">
                            <div class="form-text">Лимит просадки от начального размера.</div>
                        </div>
                    </div>
                </div>

                <div class="form-actions-footer">
                    <button type="button" class="btn btn-outline" style="min-width: 120px;" onclick="window.location.href='index.php?view=accounts'">
                        Отмена
                    </button>
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="fas fa-save me-2"></i> <?= $btn_text ?>
                    </button>
                </div>
                
            </form>
        </div>
    </div>
</div>

<script>
// Автоматически копируем Starting в Balance при вводе, если Balance пустой или равен Starting
function syncBalanceField() {
    const startInput = document.getElementById('acc-starting');
    const balInput = document.getElementById('acc-balance');
    // Если поле баланса еще не трогали или оно совпадает с предыдущим значением starting - обновляем
    if (document.activeElement === startInput) {
        balInput.value = startInput.value;
    }
}
</script>