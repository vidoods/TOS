<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h2 class="m-0 page-title" style="font-weight: 600;"><?= $lang['quarterly_analysis'] ?></h2>
            <div class="text-secondary small mt-1"><?= $lang['perf_review_quarters'] ?></div>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <select id="qpa-year-select" class="select-field" style="min-width: 110px; cursor: pointer;">
            </select>
        </div>
    </div>

    <div class="qpa-grid" id="qpa-list-container">
        <div class="text-center text-muted py-5" style="grid-column: 1 / -1;">
            <?= $lang['loading'] ?>
        </div>
    </div>
</div>