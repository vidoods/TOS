<?php
// views/mpa.php - Динамический Месячный Анализ
?>

<div class="fade-in">
    <div class="d-flex align-items-center mb-4 gap-3">
        <div class="mpa-page-icon">M</div>
        <h2 class="m-0 page-title-text">Monthly Performance Analysis</h2>
    </div>

    <div class="mpa-toolbar mb-4">
        <div class="d-flex flex-wrap gap-3 align-items-center">
            
            <div>
                <label class="small text-muted mb-1 d-block">Year</label>
                <select id="mpa-year-select" class="form-select form-select-sm" style="width: 120px; background: rgba(255,255,255,0.1); border:none; color:white;">
                    </select>
            </div>

            <div>
                <label class="small text-muted mb-1 d-block">Quarter</label>
                <select id="mpa-quarter-select" class="form-select form-select-sm" style="width: 120px; background: rgba(255,255,255,0.1); border:none; color:white;">
                    <option value="all">All Year</option>
                    <option value="1">Q1</option>
                    <option value="2">Q2</option>
                    <option value="3">Q3</option>
                    <option value="4">Q4</option>
                </select>
            </div>

        </div>
    </div>

    <div id="mpa-dynamic-container">
        <div class="text-center py-5">
            <div class="loading-spinner"></div> Loading Analysis...
        </div>
    </div>
</div>
