<?php
// views/mpa.php - Динамический Месячный Анализ
?>

<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <h2 class="m-0 page-title" style="font-weight: 600;"><?= $lang['monthly_analysis'] ?></h2>

        <div class="d-flex align-items-center gap-2">
            <select id="mpa-year-select" class="select-field" style="min-width: 110px; cursor: pointer;">
            </select>
            <select id="mpa-quarter-select" class="select-field" style="min-width: 130px; cursor: pointer;">
                <option value="all"><?= $lang['all_year'] ?></option>
                <option value="1">Q1</option>
                <option value="2">Q2</option>
                <option value="3">Q3</option>
                <option value="4">Q4</option>
            </select>
        </div>
    </div>

    <div id="mpa-dynamic-container">
        <div class="text-center py-5">
            <div class="loading-spinner"></div> <?= $lang['loading_analysis'] ?>
        </div>
    </div>
</div>
