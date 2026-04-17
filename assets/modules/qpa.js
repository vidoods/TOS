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
            placeholder: 'Write your quarterly analysis here...',
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
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Saving...';

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
                    showToast('Quarterly report saved!', 'success');
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Network error', 'error');
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
    if (titleElem) titleElem.textContent = `Q${quarter} ${year} Review`;

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
            showToast('Failed to load data: ' + data.message, 'error');
        }
    } catch (err) {
        console.error(err);
        showToast('Error loading QPA details', 'error');
    }
}

function renderQPAHeader(stats) {
    if (!stats) return;

    const pnlElem = document.getElementById('qpa-pnl');
    if (pnlElem) {
        pnlElem.textContent = `$${parseFloat(stats.pnl).toFixed(2)}`;
        pnlElem.className = `fs-4 fw-bold ${parseFloat(stats.pnl) >= 0 ? 'text-success' : 'text-danger'}`;
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
        container.innerHTML = '<div class="text-muted" style="grid-column: span 3;">No data available</div>';
        return;
    }

    months.forEach(m => {
        const isPositive = parseFloat(m.pnl) >= 0;
        const colorClass = isPositive ? 'text-success' : 'text-danger';
        const iconClass = isPositive ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down';
        const iconColor = isPositive ? 'text-success' : 'text-danger';

        const html = `
        <div class="metric-card glass-panel h-100">
            <div class="metric-icon">
                <i class="fas ${iconClass} ${iconColor}"></i>
            </div>
            <div class="metric-content w-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="metric-label text-white opacity-75">${m.name}</div>
                    <span class="badge bg-secondary opacity-25 small">M${m.num}</span>
                </div>
                <div class="metric-value ${colorClass} mb-1">$${parseFloat(m.pnl).toFixed(2)}</div>
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
        tbody.innerHTML = '<div class="text-center py-4 text-muted">No trades found for this quarter</div>';
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
        container.innerHTML = '<div class="col-12 text-center py-4 text-muted" style="grid-column: 1 / -1;">No plans found for this quarter</div>';
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
            container.innerHTML = `<div class="col-12 text-center text-danger">Error: ${json.message}</div>`;
        }
    } catch (e) {
        console.error(e);
        container.innerHTML = '<div class="col-12 text-center text-danger">Network Error</div>';
    }
}

function renderQpaListGrid(quarters, container) {
    container.innerHTML = '';

    if (!quarters || quarters.length === 0) {
        container.innerHTML = '<div class="text-muted text-center py-5" style="grid-column: 1 / -1;">No data available</div>';
        return;
    }

    quarters.forEach(q => {
        const pnlVal = parseFloat(q.pnl);
        const pnlClass = pnlVal >= 0 ? 'text-success' : 'text-danger';
        const pnlSign = pnlVal > 0 ? '+' : '';

        const cardBg = pnlVal >= 0
            ? 'background: linear-gradient(160deg, rgba(25, 135, 84, 0.08) 0%, rgba(0,0,0,0) 100%);'
            : 'background: linear-gradient(160deg, rgba(220, 53, 69, 0.08) 0%, rgba(0,0,0,0) 100%);';

        const html = `
        <div class="glass-panel qpa-card p-4 h-100 d-flex flex-column justify-content-between"
             style="${cardBg}"
             onclick="window.location.href='index.php?view=qpa_details&year=${q.year}&quarter=${q.quarter}'">

            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <h3 class="fw-bold m-0 text-white" style="font-size: 1.8rem;">Q${q.quarter}</h3>
                    <div class="text-muted small text-uppercase" style="letter-spacing: 1px;">${q.year}</div>
                </div>
            </div>

            <div class="mb-4">
                <div class="small text-muted text-uppercase fw-bold mb-1" style="font-size: 0.75rem;">Net Result</div>
                <div class="display-6 fw-bold ${pnlClass}">
                    ${pnlSign}${pnlVal.toFixed(2)}<small class="fs-6 opacity-50 ms-1">$</small>
                </div>
            </div>

            <div class="d-flex align-items-center pt-3 border-top border-white-10 text-muted small">
                <div class="d-flex align-items-center me-4" title="Winrate">
                    <i class="fas fa-chart-pie me-2 opacity-50"></i>
                    <span class="fw-bold text-white">${q.winrate}%</span>
                </div>
                <div class="d-flex align-items-center me-auto" title="Total Trades">
                    <i class="fas fa-list me-2 opacity-50"></i>
                    <span class="fw-bold text-white">${q.total}</span>
                </div>
                <div class="text-white opacity-50">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </div>

        </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    });
}
