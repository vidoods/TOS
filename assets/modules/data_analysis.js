// assets/modules/data_analysis.js
// ==================================================
// DATA ANALYSIS FUNCTIONS
// ==================================================

async function loadDataAnalysis() {
    ['list-direction', 'list-style', 'list-timeframe', 'list-model'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = getSkeletonHtml('list-item', 4);
    });

    const listPairs = document.getElementById('list-pairs');
    if (listPairs) listPairs.innerHTML = getSkeletonHtml('list-item', 4);

    try {
        const res = await fetch('api/api.php?action=get_data_analysis');
        const json = await res.json();

        if (json.success) {
            const d = json.data;
            renderDataList('list-direction', d.direction);
            renderDataList('list-style', d.style);
            renderDataList('list-timeframe', d.timeframe);
            renderDataList('list-model', d.model);
            renderPairsGrid('list-pairs', d.pairs);
        }
    } catch (e) { console.error(e); }
}

function renderDataList(containerId, items) {
    const container = document.getElementById(containerId);
    if (!container) return;

    if (!items || items.length === 0) {
        container.innerHTML = '<div class="text-muted small">No data available</div>';
        return;
    }

    let html = '';
    items.forEach(item => {
        const label = item.label || 'N/A';
        const count = item.total_trades;
        const winrate = parseInt(item.win_rate);

        let color = '#6b7280';
        if (count > 0) {
            if (winrate >= 50) color = '#00d66f';
            else color = '#ff453a';
        }

        const strokeDash = `${winrate}, 100`;
        const tooltipText = `Winrate: ${winrate}%`;

        html += `
        <div class="data-row">
            <div class="data-label-group">
                ${getLabelIcon(label)}
                <span>${label}</span>
            </div>
            <div class="data-stats-group">
                <span class="data-count">${count} Trades</span>
                <div title="${tooltipText}" style="display: flex; align-items: center; gap: 6px; cursor: help;">
                    <span style="font-weight:700; font-size:0.8rem; color:${color}; width: 35px; text-align:right;">${winrate}%</span>
                    <svg viewBox="0 0 36 36" class="circular-chart">
                        <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="circle" stroke="${color}" stroke-dasharray="${strokeDash}" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                </div>
            </div>
        </div>`;
    });
    container.innerHTML = html;
}

function renderPairsGrid(containerId, items) {
    const container = document.getElementById(containerId);
    if (!container) return;

    if (!items || items.length === 0) {
        container.innerHTML = '<div class="text-muted small">No trades yet</div>';
        return;
    }

    let html = '';
    items.forEach(item => {
        const winrate = parseInt(item.win_rate);
        const count = item.total_trades;

        let color = '#6b7280';
        if (count > 0) {
            if (winrate >= 50) color = '#00d66f';
            else color = '#ff453a';
        }

        const strokeDash = `${winrate}, 100`;
        const tooltipText = `Winrate: ${winrate}%`;

        html += `
        <div class="pair-stat-card">
            <div style="font-weight:700; font-size: 1rem;">${item.label}</div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <span class="data-count">${count} T</span>
                <div title="${tooltipText}" style="display: flex; align-items: center; gap: 5px; cursor: help;">
                    <span style="font-weight:700; font-size:0.8rem; color:${color};">${winrate}%</span>
                    <svg viewBox="0 0 36 36" class="circular-chart">
                        <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="circle" stroke="${color}" stroke-dasharray="${strokeDash}" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                </div>
            </div>
        </div>`;
    });
    container.innerHTML = html;
}

function getLabelIcon(label) {
    const l = label.toLowerCase();
    if (l === 'long') return '<i class="fas fa-arrow-up text-profit" style="font-size: 0.8rem;"></i>';
    if (l === 'short') return '<i class="fas fa-arrow-down text-loss" style="font-size: 0.8rem;"></i>';
    return '';
}

function getIconForLabel(label) {
    const l = label.toLowerCase();
    if (l === 'long') return '<i class="fas fa-arrow-up text-profit small"></i>';
    if (l === 'short') return '<i class="fas fa-arrow-down text-loss small"></i>';
    if (l.includes('day')) return '<i class="fas fa-sun text-warning small"></i>';
    if (l.includes('swing')) return '<i class="fas fa-history text-info small"></i>';
    return '';
}
