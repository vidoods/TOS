<?php
$st_id = $_GET['id'] ?? null;
$is_edit = !empty($st_id);
$page_title = $is_edit ? $lang['edit_module'] : $lang['new_strategy_module'];
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
                        <label class="form-label"><?= $lang['module_title'] ?></label>
                        <input type="text" class="input-field" name="title" id="st-title" placeholder="<?= $lang['module_title_placeholder'] ?>" required style="font-size: 1.1rem; font-weight: 600;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= $lang['icon_class'] ?></label>
                        <input type="text" class="input-field" name="icon" id="st-icon" placeholder="<?= $lang['icon_placeholder'] ?>">
                        <div class="form-text"><a href="https://fontawesome.com/v5/search?m=free" target="_blank" style="color: var(--accent-blue);"><?= $lang['find_icons'] ?></a></div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label"><?= $lang['subtitle_desc'] ?></label>
                    <input type="text" class="input-field" name="description" id="st-desc" placeholder="<?= $lang['subtitle_placeholder'] ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label"><?= $lang['detailed_content'] ?></label>
                    <div id="editor-container" style="height: 400px; background: rgba(0,0,0,0.2); border-radius: 0 0 8px 8px;"></div>
                    <input type="hidden" name="content" id="st-content-hidden">
                </div>

                <div class="d-flex gap-2 pt-3 border-top border-secondary">
                    <button type="button" class="btn btn-outline" onclick="window.history.back()"><?= $lang['cancel'] ?></button>
                    <button type="submit" class="btn btn-primary px-4"><?= $lang['save_module'] ?></button>
                </div>
            </form>
        </div>
    </div>
</div>