// assets/modules/qpa.js
// ==================================================
// QPA DETAILS LOGIC
// ==================================================

function initQPADetails() {
    const qpaContainer = document.getElementById('qpa-editor-container');
    if (!qpaContainer) return;

    if (typeof Quill !== 'undefined') {
        qpaQuill = new Quill('#qpa-editor-container', {
            theme: 'snow',
            placeholder: window.lang['write_quarterly_analysis'],
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'color': [] }, { 'background': [] }],
                    ['clean']
                ]
            }
        });
    }

    loadQPADetails();

    const form = document.getElementById('qpa-report-form');
    if (form) {
        form.onsubmit = async (e) => {
            e.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i> ${window.lang['saving']}`;

            const year = document.getElementById('qpa-year').value;
            const quarter = document.getElementById('qpa-quarter').value;
            const content = qpaQuill ? qpaQuill.root.innerHTML : '';

            try {
                const res = await fetch('api/api.php?action=save_qpa_report', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ year, quarter, content })
                });
                const data = await res.json();
                if (data.success) {
                    showToast(window.lang['quarterly_report_saved'], 'success');
                } else {
                    showToast(window.lang['error'] + ': ' + data.message, 'error');
                }
            } catch (err) {
                console.error(err);
                showToast(window.lang['network_error'], 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        };
    }
}

async function loadQPADetails() {
    const yearElem = document.getElementById('qpa-year');
    const quarterElem = document.getElementById('qpa-quarter');

    if (!yearElem || !quarterElem) return;

    const year = yearElem.value;
    const quarter = quarterElem.value;

    const titleElem = document.getElementById('qpa-title');
    if (titleElem) titleElem.textContent = `Q${quarter} ${year} ${window.lang['review_short']}`;

    try {
        const res = await fetch(`api/api.php?action=get_qpa_details&year=${year}&quarter=${quarter}`);
        const data = await res.json();

        if (data.success) {
            renderQPAHeader(data.stats);
            renderQPAMonths(data.months);
            renderQPATrades(data.trades);
            renderQPAPlans(data.plans);

            if (qpaQuill && data.report_content) {
                qpaQuill.root.innerHTML = data.report_content;
            }
        } else {
            showToast(window.lang['failed_to_load_data'] + ': ' + data.message, 'error');
        }
    } catch (err) {
        console.error(err);
        showToast(window.lang['error_loading_qpa'], 'error');
    }
}

function renderQPAHeader(stats) {
    if (!stats) return;

    const pnlElem = document.getElementById('qpa-pnl');
    if (pnlElem) {
        const pnlValue = parseFloat(stats.pnl);
        // ИСПРАВЛЕНИЕ: Интегрировали CurrencyManager для вывода квартальной прибыли в хедере
        pnlElem.textContent = CurrencyManager.format(pnlValue);
        pnlElem.className = `fs-4 fw-bold ${pnlValue >= 0 ? 'text-success' : 'text-danger'}`;
    }

    const wrElem = document.getElementById('qpa-winrate');
    if (wrElem) wrElem.textContent = `${stats.winrate}%`;

    const rrElem = document.getElementById('qpa-rr');
    if (rrElem) rrElem.textContent = `${stats.avg_rr}R`;

    const totElem = document.getElementById('qpa-total');
    if (totElem) totElem.textContent = stats.total;
}

function renderQPAMonths(months) {
    const container = document.getElementById('qpa-months-container');
    if (!container) return;
    container.innerHTML = '';

    if (!months || months.length === 0) {
        container.innerHTML = `<div class="text-muted" style="grid-column: span 3;">${window.lang['no_data_available']}</div>`;
        return;
    }

    months.forEach(m => {
        const pnlValue = parseFloat(m.pnl);
        const isPositive = pnlValue >= 0;
        const colorClass = isPositive ? 'text-success' : 'text-danger';
        const iconClass = isPositive ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down';
        const iconColor = isPositive ? 'text-success' : 'text-danger';

        // ИСПРАВЛЕНИЕ: Форматируем PnL месяцев внутри квартала через валютный менеджер
        const html = `
        <div class="metric-card glass-panel h-100">
            <div class="metric-icon">
                <i class="fas ${iconClass} ${iconColor}"></i>
            </div>
            <div class="metric-content w-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="metric-label text-white opacity-75">${escapeHTML(m.name)}</div>
                    <span class="badge bg-secondary opacity-25 small">M${m.num}</span>
                </div>
                <div class="metric-value ${colorClass} mb-1">${CurrencyManager.format(pnlValue)}</div>
                <div class="d-flex gap-3 text-muted small" style="font-size: 0.8rem;">
                    <div><i class="fas fa-chart-pie me-1"></i>${m.winrate}%</div>
                    <div><i class="fas fa-list me-1"></i>${m.count}</div>
                </div>
            </div>
        </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    });
}

function renderQPATrades(trades) {
    const tbody = document.getElementById('qpa-trades-body');
    const badge = document.getElementById('trades-count-badge');

    if (badge) badge.textContent = trades ? trades.length : 0;
    if (!tbody) return;

    tbody.innerHTML = '';

    if (!trades || trades.length === 0) {
        tbody.innerHTML = `<div class="text-center py-4 text-muted">${window.lang['no_trades_quarter']}</div>`;
        return;
    }

    let html = '';
    trades.forEach(t => {
        html += getTradeRowHtml(t);
    });
    tbody.innerHTML = html;
}

function renderQPAPlans(plans) {
    const container = document.getElementById('qpa-plans-body');
    const badge = document.getElementById('plans-count-badge');

    if (badge) badge.textContent = plans ? plans.length : 0;
    if (!container) return;

    container.innerHTML = '';

    if (!plans || plans.length === 0) {
        container.innerHTML = `<div class="col-12 text-center py-4 text-muted" style="grid-column: 1 / -1;">${window.lang['no_plans_quarter']}</div>`;
        return;
    }

    let html = '';
    plans.forEach(p => {
        html += getPlanCardHtml(p);
    });
    container.innerHTML = html;
}

// --- QPA LIST ---

function loadQPAList() {
    const container = document.getElementById('qpa-list-container');
    const yearSelect = document.getElementById('qpa-year-select');

    if (!container) return;

    if (yearSelect && yearSelect.options.length === 0) {
        const currentYear = new Date().getFullYear();
        for (let y = currentYear; y >= currentYear - 3; y--) {
            const opt = document.createElement('option');
            opt.value = y;
            opt.textContent = y;
            yearSelect.appendChild(opt);
        }
        yearSelect.addEventListener('change', () => fetchQPAData(yearSelect.value));
    }

    const selectedYear = yearSelect ? yearSelect.value : new Date().getFullYear();
    fetchQPAData(selectedYear);
}

async function fetchQPAData(year) {
    const container = document.getElementById('qpa-list-container');
    container.innerHTML = getSkeletonHtml('card', 4);

    try {
        const res = await fetch(`api/api.php?action=get_qpa_list&year=${year}`);
        const json = await res.json();

        if (json.success) {
            renderQpaListGrid(json.data, container);
        } else {
            // ИСПРАВЛЕНИЕ: Безопасное экранирование вывода ошибок с сервера
            container.innerHTML = `<div class="col-12 text-center text-danger">${window.lang['error']}: ${escapeHTML(json.message)}</div>`;
        }
    } catch (e) {
        console.error(e);
        container.innerHTML = `<div class="col-12 text-center text-danger">${window.lang['network_error']}</div>`;
    }
}

function renderQpaListGrid(quarters, container) {
    container.innerHTML = '';

    if (!quarters || quarters.length === 0) {
        container.innerHTML = `<div class="text-muted text-center py-5" style="grid-column: 1 / -1;">${window.lang['no_data_available']}</div>`;
        return;
    }

    quarters.forEach(q => {
        const pnlVal = parseFloat(q.pnl);
        const pnlClass = pnlVal >= 0 ? 'text-profit' : 'text-loss';
        const winrateVal = parseFloat(q.winrate || 0);

        // ИСПРАВЛЕНИЕ: Единый стиль glass-panel без едких фоновых градиентов
        const html = `
        <div class="glass-panel qpa-card"
             onclick="window.location.href='index.php?view=qpa_details&year=${q.year}&quarter=${q.quarter}'">

            <div class="qpa-card-header">
                <div>
                    <h3 class="qpa-card-quarter">Q${q.quarter}</h3>
                    <div class="qpa-card-year">${q.year}</div>
                </div>
                <div class="qpa-card-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </div>

            <div class="qpa-card-metric">
                <div class="qpa-card-label">${window.lang['net_result']}</div>
                <div class="qpa-card-value ${pnlClass}">
                    ${CurrencyManager.format(pnlVal)}
                </div>
            </div>

            <div class="qpa-card-footer">
                <div class="qpa-stat-chip" title="${window.lang['winrate']}">
                    <i class="fas fa-chart-pie"></i>
                    <span>${winrateVal}%</span>
                </div>
                <div class="qpa-stat-chip" title="${window.lang['total_trades']}">
                    <i class="fas fa-list"></i>
                    <span>${q.total}</span>
                </div>
            </div>

            <div class="mpa-progress-bg mt-3">
                <div class="mpa-progress-fill" style="width: ${q.total > 0 ? winrateVal : 0}%; background: ${pnlVal < 0 ? 'var(--text-loss)' : 'var(--text-profit)'};"></div>
            </div>

        </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    });
}