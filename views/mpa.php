<?php
// views/mpa.php - Динамический Месячный Анализ
?>

<div class="fade-in">
    <div class="d-flex align-items-center mb-4 gap-3">
        <div class="mpa-page-icon">M</div>
        <h2 class="m-0 page-title-text">Monthly Performance Analysis</h2>
    </div>

    <div class="mpa-toolbar">
        <div class="d-flex gap-3 align-items-center">
            <select id="mpa-year-select" class="form-select form-select-sm" style="width: 100px; background: rgba(255,255,255,0.1); border:none; color:white;">
                </select>

            <div class="mpa-filter-group">
                <button class="mpa-filter-btn active"><i class="fas fa-th-large"></i> By Quarter</button>
                </div>
        </div>

    </div>

    <div id="mpa-dynamic-container">
        <div class="text-center py-5">
            <div class="loading-spinner"></div> Loading Analysis...
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const yearSelect = document.getElementById('mpa-year-select');
    const quarterButtons = document.querySelectorAll('.mpa-filter-btn');

    // Populate years
    for (let i = new Date().getFullYear(); i >= 2020; i--) {
        const option = document.createElement('option');
        option.value = i;
        option.textContent = i;
        yearSelect.appendChild(option);
    }

    // Add event listeners to quarter buttons
    quarterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const selectedYear = yearSelect.value;
            const selectedQuarter = this.textContent.split(' ')[1];
            loadAnalysis(selectedYear, selectedQuarter);
        });
    });

    function loadAnalysis(year, quarter) {
        // Placeholder for loading analysis
        document.getElementById('mpa-dynamic-container').innerHTML = `<div class="text-center py-5">Loading Analysis for ${year} Q${quarter}...</div>`;
    }
});
</script>
