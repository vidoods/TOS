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

            <select id="mpa-quarter-select" class="form-select form-select-sm" style="width: 100px; background: rgba(255,255,255,0.1); border:none; color:white;">
                <option value="all">All</option>
                <option value="1">Q1</option>
                <option value="2">Q2</option>
                <option value="3">Q3</option>
                <option value="4">Q4</option>
            </select>

            <div class="mpa-filter-group">
                <button id="by-quarter-btn" class="mpa-filter-btn active"><i class="fas fa-th-large"></i> By Quarter</button>
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
document.getElementById('by-quarter-btn').addEventListener('click', function() {
    const year = document.getElementById('mpa-year-select').value;
    const quarter = document.getElementById('mpa-quarter-select').value;

    // Fetch the data from the server with query parameters
    fetch(`/api/get_analysis_data?year=${year}&quarter=${quarter}`)
        .then(response => response.json())
        .then(data => {
            // Sort the data by quarters if needed
            const sortedData = sortDataByQuarter(data);
            // Update the UI with the sorted data
            updateUI(sortedData);
        })
        .catch(error => console.error('Error fetching analysis data:', error));
});

function sortDataByQuarter(data) {
    return data.sort((a, b) => {
        const quarterA = getQuarter(a.month);
        const quarterB = getQuarter(b.month);
        if (quarterA === quarterB) {
            return a.month - b.month;
        }
        return quarterA - quarterB;
    });
}

function getQuarter(month) {
    if (month >= 1 && month <= 3) return 1;
    if (month >= 4 && month <= 6) return 2;
    if (month >= 7 && month <= 9) return 3;
    if (month >= 10 && month <= 12) return 4;
}

function updateUI(data) {
    const container = document.getElementById('mpa-dynamic-container');
    container.innerHTML = ''; // Clear the existing content

    data.forEach(item => {
        const div = document.createElement('div');
        div.textContent = `Month: ${item.month}, Value: ${item.value}`;
        container.appendChild(div);
    });
}
</script>
