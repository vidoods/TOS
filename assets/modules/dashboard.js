// assets/modules/dashboard.js
// ==================================================
// ДАШБОРД: МЕТРИКИ, ГРАФИК, ФИЛЬТРЫ, ЛАЙТБОКС
// ==================================================

function setupFiltersModal(loadFunction) {
    const modal = document.getElementById('filters-modal');
    const openBtn = document.getElementById('show-filters-btn');
    const closeBtn = document.getElementById('filters-close-btn');
    const form = document.getElementById('filters-form');
    const resetBtn = document.getElementById('reset-filters-btn');

    if (!modal || !openBtn || !form) return;

    openBtn.addEventListener('click', async () => {
        modal.style.display = "block";
        const filterPairSelect = document.getElementById('filter-pair');
        if (filterPairSelect && filterPairSelect.options.length <= 1) {
            await loadLookups();
        }
    });

    const close = () => modal.style.display = "none";
    if (closeBtn) closeBtn.addEventListener('click', close);
    window.onclick = e => { if (e.target === modal) close(); };

    form.addEventListener('submit', e => {
        e.preventDefault();
        const filters = {};
        form.querySelectorAll('select, input').forEach(input => {
            if (input.value) {
                const paramName = input.id.replace('filter-', '').replace('pair', 'pair_id');
                filters[paramName] = input.value;
            }
        });
        loadFunction(filters);
        close();
    });

    if (resetBtn) resetBtn.addEventListener('click', () => { form.reset(); loadFunction({}); close(); });
}

function setupLightbox() {
    const modal = document.getElementById('image-modal');
    if (!modal) return;
    const modalImg = document.getElementById('modal-image');
    const closeBtn = modal.querySelector('.modal-close');

    document.addEventListener('click', e => {
        const isNoteImage = e.target.tagName === 'IMG' && e.target.closest('#note-content-display');

        if (e.target.classList.contains('lightbox-trigger') || isNoteImage) {
            modal.style.display = "flex";
            modalImg.src = e.target.src;
            document.body.style.overflow = 'hidden';
        }
    });

    const close = () => {
        modal.style.display = "none";
        document.body.style.overflow = '';
    };

    if (closeBtn) closeBtn.addEventListener('click', close);
    modal.onclick = e => { if (e.target === modal) close(); };
}

async function loadDashboardMetrics(overrideAccountId = null, isDetailedView = false) {
    try {
        let accountId, year, month;

        if (isDetailedView && overrideAccountId) {
            accountId = overrideAccountId;
            year = '';
            month = '';
        } else {
            accountId = document.getElementById('dashboard-account-select')?.value || '';
            year = document.getElementById('dashboard-year-select')?.value || '';
            month = document.getElementById('dashboard-month-select')?.value || '';
        }

        // idMap: привязка ключей метрик к ID элементов страницы
        const idMap = isDetailedView ? {
            total_trades: 'ad-total-trades',
            breakdown: 'ad-trades-breakdown',
            win_rate: 'ad-winrate',
            winRateBarId: 'ad-winrate-bar',       // прогресс-бар винрейта на странице аккаунта
            avg_rr: 'ad-avg-rr',
            pnl: 'ad-pnl-value',
            chartId: 'accountEquityChart'          // холст графика на странице аккаунта
        } : {
            total_trades: 'total-trades-value',
            breakdown: 'total-trades-breakdown',
            win_rate: 'winning-ratio-value',
            winRateBarId: 'winning-ratio-progress', // прогресс-бар винрейта на дашборде
            avg_rr: 'average-rr-value',
            pnl: 'net-profit-value',
            monthly: 'avg-monthly-profit',
            mdd: 'max-drawdown-value',
            avg_time: 'avg-time-in-position-value',
            chartId: 'equityChart'                  // холст главного графика на дашборде
        };

        const showSkel = (id, type) => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = getSkeletonHtml(type);
        };

        showSkel(idMap.total_trades, 'metric');
        showSkel(idMap.breakdown, 'text-line');
        showSkel(idMap.win_rate, 'metric');
        showSkel(idMap.avg_rr, 'metric');
        showSkel(idMap.pnl, 'metric');

        if (!isDetailedView) {
            showSkel(idMap.monthly, 'text-line');
            showSkel(idMap.mdd, 'metric');
            showSkel(idMap.avg_time, 'metric');
        }

        const params = new URLSearchParams({
            action: 'get_dashboard_metrics',
            account_id: accountId,
            year: year,
            month: month
        });

        const response = await fetch(`api/api.php?${params}`);
        const result = await response.json();

        if (result.success) {
            const m = result.data;

            updateMetric(idMap.total_trades, m.total_trades);
            updateBreakdown(idMap.breakdown, m.wins, m.losses, m.breakeven, m.pending || 0);
            updateMetric(idMap.win_rate, `${m.win_rate}%`);
            updateProgressBar(idMap.winRateBarId, m.win_rate);
            updateMetric(idMap.avg_rr, `${m.avg_rr_per_trade} R`);
            updatePnl(idMap.pnl, m.total_pnl);

            if (!isDetailedView) {
                updateMonthlyProfit(idMap.monthly, m.avg_monthly_profit);
                updateMetric(idMap.avg_time, m.avg_time_in_position);
                updateMaxDrawdown(idMap.mdd, m.max_drawdown_pct, m.max_drawdown_abs);
            }

            renderEquityChart(m.equity_chart, idMap.chartId);
        }
    } catch (e) { console.error(e); }
}

function populateDateFilters() {
    const yearSelect = document.getElementById('dashboard-year-select');
    if (!yearSelect) return;

    const currentYear = new Date().getFullYear();
    while (yearSelect.options.length > 1) yearSelect.remove(1);

    for (let y = currentYear; y >= 2020; y--) {
        const opt = document.createElement('option');
        opt.value = y;
        opt.textContent = y;
        yearSelect.appendChild(opt);
    }
}

function renderEquityChart(dataPoints, canvasId = 'equityChart') {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;

    const chartInstanceName = canvasId + 'Instance';
    if (window[chartInstanceName]) {
        window[chartInstanceName].destroy();
    }

    const labels = dataPoints.map(pt => pt.x);
    const data = dataPoints.map(pt => pt.y);

    const startBalance = data.length > 0 ? data[0] : 0;
    const currentBalance = data.length > 0 ? data[data.length - 1] : 0;
    const lineColor = currentBalance >= startBalance ? '#00d66f' : '#ff453a';
    const areaColor = currentBalance >= startBalance ? 'rgba(0, 214, 111, 0.1)' : 'rgba(255, 69, 58, 0.1)';

    window[chartInstanceName] = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: window.lang['balance'],
                data: data,
                borderColor: lineColor,
                backgroundColor: areaColor,
                borderWidth: 2,
                pointRadius: 0,
                pointHoverRadius: 4,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(9, 12, 20, 0.9)',
                    titleColor: '#9ca3af',
                    bodyColor: '#fff',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1,
                    displayColors: false,
                    callbacks: {
                        label: function (context) {
                            return window.lang['balance'] + ': ' + context.parsed.y.toFixed(2) + ' $';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: { color: '#6b7280', maxTicksLimit: 6, maxRotation: 0 }
                },
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)', drawBorder: false },
                    ticks: { color: '#6b7280' }
                }
            },
            interaction: { mode: 'nearest', axis: 'x', intersect: false }
        }
    });
}

function updateMetric(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
}

function updateBreakdown(id, wins, losses, breakeven, pending) {
    const el = document.getElementById(id);
    if (el) {
        el.innerHTML = `
            <span class="text-profit">${wins} W</span> /
            <span class="text-loss">${losses} L</span> /
            <span class="text-warning">${breakeven} B</span> /
            <span class="text-info">${pending} P</span>
        `;
    }
}

function updateProgressBar(id, value) {
    const el = document.getElementById(id);
    if (el) el.style.width = `${value}%`;
}

function updatePnl(id, value) {
    const el = document.getElementById(id);
    if (el) {
        const text = (value >= 0 ? '+ ' : '') + value.toFixed(2);
        el.innerHTML = `${text} $`;
        el.classList.remove('text-profit', 'text-loss');
        el.classList.add(value >= 0 ? 'text-profit' : 'text-loss');
    }
}

function updateMonthlyProfit(id, value) {
    const el = document.getElementById(id);
    if (el) el.innerHTML = `${window.lang['monthly_average']}: ${value} $`;
}

function updateMaxDrawdown(id, pct, abs) {
    const el = document.getElementById(id);
    if (el) {
        el.innerHTML = `-${pct}% (-${abs} $)`;
        el.className = 'metric-value text-loss';
    }
}
