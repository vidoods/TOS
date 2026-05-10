<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="m-0 fw-bold display-6"><?= $lang['quarterly_analysis'] ?></h2>
            <div class="text-muted mt-1"><?= $lang['perf_review_quarters'] ?></div>
        </div>
        
        <div class="d-flex align-items-center gap-3">
            <select id="qpa-year-select" class="form-select bg-dark text-white border-secondary" style="width: 120px;">
                </select>
        </div>
    </div>

    <div class="qpa-grid" id="qpa-list-container">
        <div class="text-center text-muted py-5" style="grid-column: 1 / -1;">
            <?= $lang['loading'] ?>
        </div>
    </div>
</div>