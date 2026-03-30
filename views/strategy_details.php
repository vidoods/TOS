<div class="fade-in">
    <input type="hidden" id="current-strategy-id" value="<?php echo $_GET['id'] ?? ''; ?>">

    <div class="page-header d-flex justify-content-between align-items-center mb-4 responsive-header">
        <div class="d-flex align-items-center gap-3">
            <div id="st-detail-icon" class="st-icon green" style="width: 48px; height: 48px; font-size: 1.5rem;">
                <i class="fas fa-spinner"></i>
            </div>
            <div>
                <h1 class="m-0 fw-bold" id="st-detail-title" style="font-size: 2rem;">Loading...</h1>
                <div class="text-muted" id="st-detail-desc">-</div>
            </div>
        </div>
        
        <div class="d-flex gap-2">
             <a href="index.php?view=strategy" class="btn btn-outline">
                <i class="fas fa-arrow-left me-2"></i> Back
            </a>
            <button class="btn btn-secondary" id="btn-edit-strategy">
                <i class="fas fa-edit me-2"></i> Edit
            </button>
			<button class="btn btn-danger" id="btn-delete-strategy">
                <i class="fas fa-trash-alt me-2"></i> Delete
            </button>
        </div>
    </div>

    <div class="card glass-panel border-0 shadow-sm" style="border-radius: 12px; min-height: 400px; padding:20px;">
        <div class="card-body p-5">
            <div id="st-content-display" class="ql-editor" style="padding: 0; color: var(--text-main); font-size: 1.1rem; line-height: 1.7;"></div>
        </div>
    </div>
</div>