<?php
// views/insight_details.php
?>

<div class="fade-in">
    <input type="hidden" id="current-insight-id" value="<?php echo isset($_GET['id']) ? htmlspecialchars($_GET['id']) : ''; ?>">

    <div class="d-flex justify-content-between align-items-start mb-4 responsive-header">
        <div>
            <!-- Заголовок будет заполнен через JS (символ актива) -->
            <h2 class="m-0 fw-bold" id="insight-details-title" style="color: var(--text-primary);"><?= $lang['loading'] ?></h2>
        </div>
        <div class="d-flex gap-2">
             <a href="index.php?view=notes" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> <?= $lang['back'] ?>
            </a>
            <button class="btn btn-secondary" id="btn-edit-insight">
                <i class="fas fa-edit me-2"></i> <?= $lang['edit'] ?>
            </button>
            <button class="btn btn-danger" id="btn-delete-insight">
                <i class="fas fa-trash-alt me-2"></i> <?= $lang['delete'] ?>
            </button>
        </div>
    </div>
	
    <div class="row g-4">
        <!-- Боковая панель с метаданными -->
        <div class="col-lg-4">
             <div class="card glass-panel border-0 shadow-sm" style="border-radius: 12px; margin-bottom: 10px;">
                <div class="card-body p-4">
                    <h5 class="text-muted text-uppercase fw-bold mb-4" style="font-size: 0.9rem; letter-spacing: 1px;"><?= $lang['information'] ?? 'Information' ?></h5>
                    
                    <div class="mb-4">
                        <strong class="d-block mb-2" style="color: var(--text-secondary); font-size: 0.8rem;"><?= $lang['asset_label'] ?? 'Asset' ?></strong>
                        <div id="insight-asset-symbol" class="fw-bold text-primary">-</div>
                    </div>

                    <div class="mb-4">
                        <strong class="d-block mb-2" style="color: var(--text-secondary); font-size: 0.8rem;"><?= $lang['created_by'] ?></strong>
                        <div id="insight-creator">-</div>
                    </div>
                    
                    <hr class="my-4" style="opacity: 0.1;">
                    
                    <div class="d-flex justify-content-between">
                        <span class="text-muted"><?= $lang['created_at'] ?></span>
                        <span id="insight-created-at" class="text-end">-</span>
                    </div>
                </div>
             </div>
        </div>

        <!-- Основной контент инсайта -->
        <div class="col-lg-8">
             <div class="card glass-panel border-0 shadow-sm" style="border-radius: 12px; min-height: 400px;">
                <div class="card-body p-4">
                    <div id="insight-content-display" class="ql-editor" style="padding: 0; color: var(--text-main); font-size: 1.05rem;"></div>
                </div>
             </div>
        </div>
    </div>
</div>