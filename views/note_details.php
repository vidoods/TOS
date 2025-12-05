<?php
// views/note_details.php
?>

<div class="fade-in">
    <input type="hidden" id="current-note-id" value="<?php echo isset($_GET['id']) ? htmlspecialchars($_GET['id']) : ''; ?>">

    <div class="d-flex justify-content-between align-items-start mb-4 responsive-header">
        <div>
            <h2 class="m-0 fw-bold" id="note-details-title" style="color: var(--text-primary);">Загрузка...</h2>
            <div class="text-muted small mt-1" id="note-date-info">-</div>
        </div>
        <div class="d-flex gap-2">
             <a href="index.php?view=notes" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> Назад
            </a>
            <button class="btn btn-secondary" id="btn-edit-note">
                <i class="fas fa-edit me-2"></i> Редактировать
            </button>
            <button class="btn btn-danger" id="btn-delete-note">
                <i class="fas fa-trash-alt me-2"></i> Удалить
            </button>
        </div>
    </div>
	
	<div class="col-lg-4">
             <div class="card glass-panel border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-body p-4">
                    <h5 class="text-muted text-uppercase fw-bold mb-4" style="font-size: 0.9rem; letter-spacing: 1px;">Связи</h5>
                    
                    <div class="mb-4">
                        <strong class="d-block mb-2" style="color: var(--text-secondary); font-size: 0.8rem;">Сделка</strong>
                        <div id="note-linked-trade">-</div>
                    </div>

                    <div class="mb-4">
                        <strong class="d-block mb-2" style="color: var(--text-secondary); font-size: 0.8rem;">План</strong>
                        <div id="note-linked-plan">-</div>
                    </div>
                    
                    <hr class="my-4" style="opacity: 0.1;">
                    
                    <div>
                        <strong class="d-block mb-2" style="color: var(--text-secondary); font-size: 0.8rem;">Информация</strong>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Создано:</span>
                            <span id="note-created-at" class="text-end">-</span>
                        </div>
                    </div>

                </div>
             </div>
        </div>

    <div class="row g-4">
        <div class="col-lg-8">
             <div class="card glass-panel border-0 shadow-sm" style="border-radius: 12px; min-height: 400px;">
                <div class="card-body p-4">
                    <div id="note-content-display" class="ql-editor" style="padding: 0; color: var(--text-main); font-size: 1.05rem;"></div>
                </div>
             </div>
        </div>

    </div>
</div>