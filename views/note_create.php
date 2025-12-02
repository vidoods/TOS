<?php
$note_id = $_GET['id'] ?? null;
$is_edit = !empty($note_id);
$page_title = $is_edit ? 'Редактировать Заметку' : 'Новая Заметка';
?>

<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0 fw-bold"><?= $page_title ?></h2>
        <div class="d-flex gap-2">
            <?php if($is_edit): ?>
            <button type="button" class="btn btn-danger" onclick="deleteNote(<?= $note_id ?>)">
                <i class="fas fa-trash"></i>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card glass-panel border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-body p-4">
            <input type="hidden" id="edit-note-id" value="<?= htmlspecialchars($note_id ?? '') ?>">

            <form id="note-form">
                <div class="mb-4">
                    <label class="form-label">Заголовок</label>
                    <input type="text" class="input-field" name="title" id="note-title" placeholder="Название заметки..." required style="font-size: 1.2rem; font-weight: 600;">
                </div>
                
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Привязать к Сделке (опционально)</label>
                        <select class="select-field" id="note-trade" name="trade_id">
                            <option value="">-- Выберите сделку --</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Привязать к Плану (опционально)</label>
                        <select class="select-field" id="note-plan" name="plan_id">
                            <option value="">-- Выберите план --</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Содержание</label>
                    <div id="editor-container" style="height: 400px; background: rgba(0,0,0,0.2); border-radius: 0 0 8px 8px;"></div>
                    <input type="hidden" name="content" id="note-content-hidden">
                </div>

                <div class="d-flex gap-2 pt-3 border-top border-secondary">
                    <button type="button" class="btn btn-outline" onclick="window.history.back()">Отмена</button>
                    <button type="submit" class="btn btn-primary px-4">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>