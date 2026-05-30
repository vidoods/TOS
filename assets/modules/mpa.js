// assets/modules/mpa.js
// ==================================================
// ЛОГИКА MPA (Monthly Performance Analysis)
// ==================================================

function initMPA() {
    const yearSelect = document.getElementById('mpa-year-select');
    const quarterSelect = document.getElementById('mpa-quarter-select');
    const currentYear = new Date().getFullYear();

    if (yearSelect) {
        yearSelect.innerHTML = '';
        for (let y = currentYear; y >= currentYear - 3; y--) {
            const opt = document.createElement('option');
            opt.value = y;
            opt.textContent = y;
            yearSelect.appendChild(opt);
        }
        yearSelect.addEventListener('change', (e) => loadMPAData(e.target.value));
    }

    if (quarterSelect) {
        quarterSelect.addEventListener('change', () => {
            if (currentMpaData && yearSelect) {
                renderMPAGrid(currentMpaData, document.getElementById('mpa-dynamic-container'), yearSelect.value);
            }
        });
    }

    loadMPAData(currentYear);
}

async function loadMPAData(year) {
    const container = document.getElementById('mpa-dynamic-container');
    container.innerHTML = `<div class="text-center py-5"><div class="loading-spinner"></div> ${window.lang['loading_analysis']}</div>`;

    try {
        const response = await fetch(`api/api.php?action=get_mpa_analysis&year=${year}`);
        const text = await response.text();

        let result;
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error("Server response:", text);
            // ИСПРАВЛЕНИЕ УЯЗВИМОСТИ XSS: Экранируем сырой ответ сервера перед выводом в DOM
            container.innerHTML = `<div class="alert alert-danger"><strong>${window.lang['server_error']}:</strong><br>${escapeHTML(text.substring(0, 300))}</div>`;
            return;
        }

        if (result.success) {
            currentMpaData = result.data;
            renderMPAGrid(result.data, container, year);
        } else {
            container.innerHTML = `<div class="alert alert-danger">${escapeHTML(result.message)}</div>`;
        }
    } catch (e) {
        console.error(e);
        container.innerHTML = `<div class="text-danger">${window.lang['network_error']}: ${escapeHTML(e.message)}</div>`;
    }
}

function renderMPAGrid(quartersData, container, year) {
    container.innerHTML = '';
    const monthNames = ["", window.lang['january'], window.lang['february'], window.lang['march'], window.lang['april'], window.lang['may'], window.lang['june'], window.lang['july'], window.lang['august'], window.lang['september'], window.lang['october'], window.lang['november'], window.lang['december']];

    const quarterSelect = document.getElementById('mpa-quarter-select');
    const selectedQ = quarterSelect ? quarterSelect.value : 'all';

    for (let q = 1; q <= 4; q++) {
        if (selectedQ !== 'all' && selectedQ != q) continue;

        const qData = quartersData[q];
        if (!qData || !qData.months) continue;

        const qPnl = parseFloat(qData.pnl || 0);
        const qPercent = parseFloat(qData.percent || 0);
        const pnlClass = qPnl >= 0 ? 'text-profit' : 'text-loss';

        const section = document.createElement('div');
        section.className = 'quarter-section';

        // ИСПРАВЛЕНИЕ: Применили CurrencyManager для вывода абсолютной прибыли за квартал
        section.innerHTML = `
            <div class="quarter-header" onclick="this.parentElement.classList.toggle('collapsed')">
                <i class="fas fa-chevron-down quarter-toggle-icon"></i>
                <h4 class="m-0 me-3">Q${q}</h4>
                <span class="${pnlClass}" style="font-weight: 500;">
                    ${qPercent.toFixed(1)}% <span class="text-muted ms-2" style="font-size: 0.9em">(${CurrencyManager.format(qPnl)})</span>
                </span>
            </div>
            <div class="quarter-grid"></div>
        `;

        container.appendChild(section);
        const grid = section.querySelector('.quarter-grid');

        qData.months.forEach(m => {
            const hasTrades = m.count_total > 0;
            const closedTrades = m.count_total - m.count_pending;
            const avgRR = closedTrades > 0 ? (m.rr_total / closedTrades).toFixed(2) : '0.00';
            const winRate = m.winrate || 0;
            const profitColorClass = m.pnl_total >= 0 ? 'text-profit' : 'text-loss';
            
            let progressColor = m.pnl_total < 0 ? '#f44336' : '#4caf50';
            if (!hasTrades) progressColor = 'rgba(255,255,255,0.1)';

            // ИСПРАВЛЕНИЕ: Форматируем PnL месяца через мультивалютный менеджер
            const cardHTML = `
                <div class="mpa-card" onclick="window.location.href='index.php?view=mpa_details&year=${year}&month=${m.month_num}'">
                    <div class="mpa-card-title">
                        <span>${monthNames[m.month_num]} ${year}</span>
                    </div>
                    <div class="mpa-stat-row text-muted" style="font-size: 0.9rem; letter-spacing: 0.5px; font-weight: 500;">
                        <span class="text-white">Q: ${m.count_total}</span> /
                        <span class="text-profit">W: ${m.count_win}</span> /
                        <span class="text-loss">L: ${m.count_loss}</span> /
                        <span class="text-warning">BE: ${m.count_be}</span> /
                        <span class="text-info">P: ${m.count_pending}</span>
                    </div>
                    <div class="mpa-stat-row mt-2">
                        <span>
                            ${window.lang['profit_label']}: <span class="${profitColorClass}" style="font-weight: 600;">${m.pnl_percent.toFixed(1)}%</span>
                            <span class="${profitColorClass}" style="font-size: 0.9em; opacity: 0.8;"> / ${CurrencyManager.format(parseFloat(m.pnl_total))}</span>
                        </span>
                        <span class="text-muted" style="font-size: 0.85em;">${window.lang['avg_label']}: <span class="text-white">${avgRR} RR</span></span>
                    </div>
                    <div class="mpa-stat-row mt-1 text-muted" style="font-size: 0.85em;">
                        ${winRate}% ${window.lang['winrate']}
                    </div>
                    <div class="mpa-progress-bg mt-3">
                        <div class="mpa-progress-fill" style="width: ${hasTrades ? winRate : 0}%; background: ${progressColor};"></div>
                    </div>
                </div>
            `;
            grid.insertAdjacentHTML('beforeend', cardHTML);
        });
    }
}

// --- MPA DETAILS ---

async function loadMPAMonthDetails() {
    const yearInput = document.getElementById('detail-year');
    const monthInput = document.getElementById('detail-month');

    if (!yearInput || !monthInput) return;

    const year = yearInput.value;
    const month = monthInput.value;
    const monthNames = ["", window.lang['january'], window.lang['february'], window.lang['march'], window.lang['april'], window.lang['may'], window.lang['june'], window.lang['july'], window.lang['august'], window.lang['september'], window.lang['october'], window.lang['november'], window.lang['december']];

    const titleEl = document.getElementById('month-title');
    if (titleEl) titleEl.textContent = `${monthNames[month]} ${year}`;

    const plansContainer = document.getElementById('month-plans-container');
    const tradesContainer = document.getElementById('month-trades-container');

    if (plansContainer) plansContainer.innerHTML = `<div class="row">${getSkeletonHtml('card', 3)}</div>`;
    if (tradesContainer) tradesContainer.innerHTML = `<div class="row">${getSkeletonHtml('card', 3)}</div>`;

    ['month-pnl', 'month-winrate', 'month-rr', 'month-trades-count'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = getSkeletonHtml('metric');
    });

    try {
        const response = await fetch(`api/api.php?action=get_mpa_month_details&year=${year}&month=${month}`);

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const result = await response.json();

        if (result.success) {
            const s = result.stats;
            const trades = result.trades;
            const plans = result.plans || [];

            const pnlEl = document.getElementById('month-pnl');
            if (pnlEl) {
                // ИСПРАВЛЕНИЕ: Выводим суммарный профит месяца в деталях через CurrencyManager
                pnlEl.textContent = CurrencyManager.format(parseFloat(s.pnl));
                pnlEl.className = `metric-value-pro ${s.pnl >= 0 ? 'text-success' : 'text-danger'}`;
            }
            if (document.getElementById('month-pnl-percent')) {
                document.getElementById('month-pnl-percent').textContent = `${s.percent_sum.toFixed(2)} ${window.lang['percent_return']}`;
                document.getElementById('month-pnl-percent').className = `metric-subtext-pro ${s.percent_sum >= 0 ? 'text-success' : 'text-danger'}`;
            }
            if (document.getElementById('month-winrate')) document.getElementById('month-winrate').textContent = `${s.winrate} %`;
            if (document.getElementById('month-rr')) document.getElementById('month-rr').textContent = `${s.avg_rr} R`;
            if (document.getElementById('month-trades-count')) document.getElementById('month-trades-count').textContent = s.total;

            const bd = document.getElementById('month-trades-breakdown');
            if (bd) {
                bd.innerHTML = `
                    <span class="badge bg-dark border border-success text-success">${s.wins} W</span>
                    <span class="badge bg-dark border border-danger text-danger">${s.losses} L</span>
                    <span class="badge bg-dark border border-warning text-warning">${s.be} B</span>
                    <span class="badge bg-dark border border-info text-info">${s.pending} P</span>
                `;
            }

            if (plansContainer) {
                plansContainer.innerHTML = '';
                if (plans.length === 0) {
                    plansContainer.innerHTML = `<div class="col-12 text-center text-muted py-4">${window.lang['no_plans_month']}</div>`;
                } else {
                    plans.forEach(plan => {
                        const dateObj = new Date(plan.date);
                        const day = dateObj.getDate();
                        const biasClass = `bias-${(plan.bias || '').toLowerCase()}`;
                        const pair = plan.pair_symbol || 'Unknown';
                        const typeChar = plan.type ? plan.type.charAt(0) : '?';

                        const col = document.createElement('div');
                        col.className = 'col-md-4 mb-3';
                        // БЕЗОПАСНОСТЬ: Добавили экранирование escapeHTML для защиты от XSS в названии планов и пар
                        col.innerHTML = `
                            <a href="index.php?view=plan_details&id=${plan.id}" class="plan-card glass-panel d-block text-decoration-none" style="position: relative; padding: 20px; min-height: 140px;">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                     <div class="plan-date-box text-white">
                                        <span style="font-size: 1.2rem; font-weight: bold;">${day}</span>
                                        <span class="plan-date-type text-muted ms-1" style="font-size: 0.8rem; text-transform: uppercase;">${escapeHTML(typeChar)}</span>
                                     </div>
                                     <div class="plan-bias-tag ${biasClass}">${escapeHTML(plan.bias)}</div>
                                </div>
                                <div class="plan-info mt-3">
                                    <div class="fw-bold text-white fs-5">${escapeHTML(pair)}</div>
                                    <div class="text-white-50 small text-truncate">${escapeHTML(plan.title)}</div>
                                </div>
                            </a>
                        `;
                        plansContainer.appendChild(col);
                    });
                }
            }

            if (tradesContainer) {
                tradesContainer.innerHTML = '';
                if (trades.length === 0) {
                    tradesContainer.innerHTML = `<div class="col-12 text-center text-muted py-5">${window.lang['no_trades']}</div>`;
                } else {
                    trades.forEach(t => {
                        const dateObj = new Date(t.entry_date);
                        const dateStr = dateObj.toLocaleDateString('ru-RU');
                        const pnlVal = parseFloat(t.pnl);
                        const rrVal = parseFloat(t.rr_achieved || 0).toFixed(2);
                        const pair = t.pair_name || t.pair_id || 'Unknown';

                        let badgeClass = 'secondary';
                        let st = (t.status || 'open').toUpperCase();
                        let pnlColor = 'text-white';
                        if (pnlVal > 0) pnlColor = 'text-success';
                        if (pnlVal < 0) pnlColor = 'text-danger';

                        if (st.includes('WIN')) badgeClass = 'success';
                        else if (st.includes('LOSS')) badgeClass = 'danger';
                        else if (st === 'BREAKEVEN') badgeClass = 'warning';
                        else if (st === 'OPEN' || st === 'PENDING') badgeClass = 'info';

                        const dirColor = t.direction === 'Long' ? 'text-success' : 'text-danger';

                        const col = document.createElement('div');
                        col.className = 'col-md-4 mb-3';
                        // ИСПРАВЛЕНИЕ: Форматируем PnL каждой сделки внутри детального MPA-просмотра через CurrencyManager.format
                        col.innerHTML = `
                            <a href="index.php?view=trade_details&id=${t.id}" class="glass-panel d-block text-decoration-none" style="padding: 20px; border-radius: 12px; transition: transform 0.2s; position: relative;">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-muted small">${dateStr}</span>
                                        <span class="badge-soft ${badgeClass}">${escapeHTML(st)}</span>
                                    </div>
                                    <div class="fw-bold ${dirColor}" style="text-transform: uppercase; font-size: 0.85rem;">${escapeHTML(t.direction)}</div>
                                </div>
                                <div class="d-flex justify-content-between align-items-end">
                                    <div>
                                        <div class="fw-bold text-white fs-4 mb-0">${escapeHTML(pair)}</div>
                                        <div class="text-muted small mt-1">${window.lang['result_label']}: <span class="text-white">${rrVal} R</span></div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fs-4 fw-bold ${pnlColor} font-mono">${CurrencyManager.format(pnlVal)}</div>
                                    </div>
                                </div>
                            </a>
                        `;
                        tradesContainer.appendChild(col);
                    });
                }
            }

            initMPAReportLogic(year, month);
        } else {
            console.error("API Error:", result.message);
        }
    } catch (e) {
        console.error("JS Error:", e);
    }
}

// --- MPA REPORT LOGIC ---

async function initMPAReportLogic(year, month) {
    const containerId = '#mpa-editor-container';

    await new Promise(resolve => setTimeout(resolve, 100));

    const container = document.querySelector(containerId);
    if (!container) return;

    if (typeof Quill === 'undefined') {
        console.error("Quill JS not loaded!");
        container.innerHTML = `<div class="text-danger">${window.lang['error']}: editor library not loaded.</div>`;
        return;
    }

    try {
        const Font = Quill.import('formats/font');
        Font.whitelist = ['inter', 'roboto', 'serif', 'monospace', 'Montserrat'];
        Quill.register(Font, true);
    } catch (e) {
        console.log("Fonts loaded or conflict ignored.");
    }

    if (mpaQuill) {
        try { mpaQuill.disable(); } catch(e){}
        mpaQuill = null;
    }

    container.innerHTML = '';

    const parent = container.parentNode;
    if (parent) {
        const oldToolbars = parent.querySelectorAll('.ql-toolbar');
        oldToolbars.forEach(tb => tb.remove());
        container.classList.remove('ql-container', 'ql-snow');
    }

    const toolbarOptions = [
        [{ 'font': ['inter', 'roboto', 'serif', 'monospace', 'Montserrat'] }],
        [{ 'size': ['small', false, 'large', 'huge'] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'color': [] }, { 'background': [] }],
        [{ 'header': 1 }, { 'header': 2 }, 'blockquote', 'code-block'],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        [{ 'align': [] }],
        ['link', 'image', 'clean']
    ];

    try {
        mpaQuill = new Quill(containerId, {
            theme: 'snow',
            modules: { toolbar: toolbarOptions },
            placeholder: window.lang['type_conclusions']
        });

        mpaQuill.root.style.color = '#e0e0e0';
        mpaQuill.root.style.fontFamily = "'Inter', sans-serif";
        mpaQuill.root.style.fontSize = '16px';
    } catch (err) {
        console.error("Critical Quill Init Error:", err);
        return;
    }

    try {
        const response = await fetch(`api/api.php?action=get_mpa_report&year=${year}&month=${month}`);
        if (response.ok) {
            const result = await response.json();
            if (result.success && result.content) {
                mpaQuill.root.innerHTML = result.content;
            }
        }
    } catch (e) {
        console.error("Load Error:", e);
    }

    const form = document.getElementById('mpa-report-form');
    if (form) {
        const newForm = form.cloneNode(true);
        form.parentNode.replaceChild(newForm, form);

        newForm.onsubmit = async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-save-report');
            const originalText = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i> ${window.lang['saving']}`;

            const content = mpaQuill.root.innerHTML;

            try {
                const res = await fetch('api/api.php?action=save_mpa_report', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ year, month, content })
                });
                const data = await res.json();

                if (data.success) {
                    btn.className = 'btn btn-success px-4 py-2';
                    btn.innerHTML = `<i class="fas fa-check me-2"></i> ${window.lang['saved']}`;
                    showToast(window.lang['report_saved'], 'success');

                    setTimeout(() => {
                        btn.className = 'btn btn-primary px-4 py-2';
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }, 2000);
                } else {
                    showToast('Error: ' + data.message, 'error');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            } catch (err) {
                showToast(window.lang['network_error'], 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        };
    }
}