<?php
$st_id = $_GET['id'] ?? null;
$is_edit = !empty($st_id);
$page_title = $is_edit ? 'Edit Module' : 'New Strategy Module';
?>

<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0 fw-bold"><?= $page_title ?></h2>
        <div class="d-flex gap-2">
            <?php if($is_edit): ?>
            <button type="button" class="btn btn-danger" onclick="deleteStrategy(<?= $st_id ?>)">
                <i class="fas fa-trash"></i>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card glass-panel border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-body p-4">
            <input type="hidden" id="edit-strategy-id" value="<?= htmlspecialchars($st_id ?? '') ?>">

            <form id="strategy-form">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Module Title</label>
                        <input type="text" class="input-field" name="title" id="st-title" placeholder="e.g. Risk Management" required style="font-size: 1.1rem; font-weight: 600;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Icon (FontAwesome Class)</label>
                        <input type="text" class="input-field" name="icon" id="st-icon" placeholder="e.g. fas fa-chart-line">
                        <div class="form-text"><a href="https://fontawesome.com/v5/search?m=free" target="_blank" style="color: var(--accent-blue);">Find icons here</a></div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Subtitle / Short Description</label>
                    <input type="text" class="input-field" name="description" id="st-desc" placeholder="e.g. Limits | Adding | Management">
                </div>

                <div class="mb-4">
                    <label class="form-label">Detailed Content</label>
                    <div id="editor-container" style="height: 400px; background: rgba(0,0,0,0.2); border-radius: 0 0 8px 8px;"></div>
                    <input type="hidden" name="content" id="st-content-hidden">
                </div>

                <div class="d-flex gap-2 pt-3 border-top border-secondary">
                    <button type="button" class="btn btn-outline" onclick="window.history.back()">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save Module</button>
                </div>
            </form>
        </div>
    </div>
</div>