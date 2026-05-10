// assets/modules/accounts.js
// ==================================================
// ФУНКЦИИ АККАУНТОВ И ВЫПЛАТ
// ==================================================

function togglePropFields() {
    const typeEl = document.getElementById('acc-type');
    const propBlock = document.getElementById('prop-settings');
    if (!typeEl || !propBlock) return;

    const type = typeEl.value;
    if (type === 'Live' || type === 'Demo') {
        propBlock.style.display = 'none';
        document.getElementById('acc-target').value = 0;
        document.getElementById('acc-dd').value = 0;
    } else {
        propBlock.style.display = 'block';
    }
}

async function loadAccounts() {
    const container = document.getElementById('accounts-grid');
    if (!container) return;

    container.innerHTML = getSkeletonHtml('card', 2);

    try {
        const res = await fetch('api/api.php?action=get_accounts_data');
        const json = await res.json();

        if (json.success) {
            if (json.data.length === 0) {
                container.innerHTML = `<div class="empty-state">${window.lang['no_accounts_add_first']}</div>`;
                return;
            }

            let html = '';
            json.data.forEach(acc => {
                const startEquity = acc.starting_equity;
                const currentEquity = acc.calculated_balance;
                const targetPct = acc.target_percent;
                const maxDDPct = acc.max_drawdown_percent;

                const totalGainAbs = currentEquity - startEquity;
                const totalGainPct = (startEquity > 0) ? (totalGainAbs / startEquity) * 100 : 0;

                const profitClass = totalGainAbs >= 0 ? 'text-profit' : 'text-loss';
                const profitSign = totalGainAbs >= 0 ? '+' : '';

                let widthLoss = 0;
                let widthProfit = 0;
                let labelLeft = maxDDPct > 0 ? `${window.lang['max_dd']}: ${maxDDPct}%` : window.lang['no_limit'];
                let labelRight = targetPct > 0 ? `${window.lang['target']}: ${targetPct}%` : window.lang['no_target'];

                if (totalGainAbs >= 0) {
                    if (targetPct > 0) {
                        widthProfit = Math.min((totalGainPct / targetPct) * 100, 100);
                    } else {
                        widthProfit = 0;
                    }
                } else {
                    const currentDrawdownPct = Math.abs(totalGainPct);
                    if (maxDDPct > 0) {
                        widthLoss = Math.min((currentDrawdownPct / maxDDPct) * 100, 100);
                    }
                }

                const barHtml = `
                    <div class="acc-split-bar-container">
                        <div class="acc-split-divider"></div>
                        <div class="acc-bar-left"><div class="acc-fill-loss" style="width: ${widthLoss}%"></div></div>
                        <div class="acc-bar-right"><div class="acc-fill-profit" style="width: ${widthProfit}%"></div></div>
                    </div>
                    <div class="acc-split-labels">
                        <span class="text-loss">${labelLeft}</span>
                        <span style="color:#fff; opacity:0.5; font-size:0.65rem;">${window.lang['start_label']}: $${startEquity.toLocaleString()}</span>
                        <span class="text-profit">${labelRight}</span>
                    </div>`;

                html += `
                <div class="account-card" onclick="window.location.href='index.php?view=account_details&id=${acc.id}'">
                    <div class="acc-actions" onclick="event.stopPropagation()">
                        <a title="${window.lang['edit']}" href="index.php?view=account_create&id=${acc.id}" class="acc-btn d-inline-flex align-items-center justify-content-center" style="text-decoration:none;"><i class="fas fa-pen" style="font-size:0.8rem"></i></a>
                        <button title="${window.lang['delete']}" class="acc-btn delete" onclick="deleteAccount(${acc.id})"><i class="fas fa-trash" style="font-size:0.8rem"></i></button>
                    </div>
                    <div class="acc-header">
                        <div class="acc-name"><i class="fas fa-wallet" style="color:var(--accent-blue)"></i> ${acc.name}</div>
                        <span class="acc-type-badge">${acc.type}</span>
                    </div>
                    <div class="acc-balance">$${parseFloat(currentEquity).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                    <div style="font-size: 0.9rem; margin-bottom: 5px;" class="${profitClass}">
                        ${profitSign}${totalGainAbs.toFixed(2)}$ (${profitSign}${totalGainPct.toFixed(2)}%)
                    </div>
                    ${barHtml}
                    <div class="acc-stats-grid">
                        <div class="acc-stat-row"><span>${window.lang['total_trades']}:</span><span class="acc-stat-val">${acc.total_trades}</span></div>
                        <div class="acc-stat-row"><span>${window.lang['winrate']}:</span><span class="acc-stat-val">${acc.total_trades > 0 ? ((acc.wins/acc.total_trades)*100).toFixed(1) : 0}%</span></div>
                        <div class="acc-stat-row"><span>${window.lang['avg_rr']}:</span><span class="acc-stat-val">${acc.avg_rr}R</span></div>
                        <div class="acc-stat-row"><span>${window.lang['journal_pnl']}:</span><span class="acc-stat-val ${acc.profit >=0 ? 'text-profit':'text-loss'}">${acc.profit >=0?'+':''}${acc.profit.toFixed(2)}$</span></div>
                    </div>
                </div>`;
            });

            container.innerHTML = html;
        }
    } catch(e) { console.error(e); }
}

async function deleteAccount(id) {
    if (!confirm(window.lang['confirm_delete_account'])) return;
    const fd = new FormData(); fd.append('id', id);
    try {
        const res = await fetch('api/api.php?action=delete_account', { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) {
            showToast(window.lang['account_deleted'], 'success');
            if (window.location.search.includes('view=account_details')) {
                window.location.href = 'index.php?view=accounts';
            } else {
                loadAccounts();
            }
        } else {
            showToast(window.lang['delete_error'] + ': ' + json.message, 'error');
        }
    } catch(e) {
        console.error(e);
        showToast(window.lang['network_error'], 'error');
    }
}

async function initAccountForm() {
    const idEl = document.getElementById('edit-acc-id');
    const form = document.getElementById('account-form');

    if (idEl && idEl.value) {
        try {
            const res = await fetch(`api/api.php?action=get_account_details&id=${idEl.value}`);
            const json = await res.json();
            if (json.success) {
                const d = json.data;
                document.getElementById('acc-name').value = d.name;
                document.getElementById('acc-type').value = d.type;
                document.getElementById('acc-starting').value = d.starting_equity;
                document.getElementById('acc-balance').value = d.balance;
                document.getElementById('acc-target').value = d.target_percent;
                document.getElementById('acc-dd').value = d.max_drawdown_percent;
                togglePropFields();
            }
        } catch(e) { console.error(e); }
    } else {
        togglePropFields();
    }

    if (form) {
        form.onsubmit = async (e) => {
            e.preventDefault();
            const data = {
                id: document.getElementById('edit-acc-id').value,
                name: document.getElementById('acc-name').value,
                type: document.getElementById('acc-type').value,
                starting_equity: document.getElementById('acc-starting').value,
                balance: document.getElementById('acc-balance').value,
                target_percent: document.getElementById('acc-target').value,
                max_drawdown_percent: document.getElementById('acc-dd').value
            };

            try {
                const res = await fetch('api/api.php?action=save_account', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });
                const json = await res.json();
                if (json.success) {
                    window.location.href = 'index.php?view=accounts';
                } else {
                    showMessage(window.lang['error'] + ' ' + json.message, 'error');
                }
            } catch(err) {
                showMessage(window.lang['network_error'], 'error');
            }
        };
    }
}

// ==================================================
// ФУНКЦИИ ВЫПЛАТ (PAYOUTS)
// ==================================================

async function loadPayouts() {
    const container = document.getElementById('payouts-list-container');
    if (!container) return;

    try {
        const res = await fetch('api/api.php?action=get_payouts');
        const json = await res.json();

        if (json.success) {
            if (json.data.length === 0) {
                container.innerHTML = `
                    <div class="glass-panel p-4 text-center text-muted">
                        <i class="fas fa-money-bill-wave mb-2" style="font-size: 2rem; opacity: 0.3;"></i>
                        <p>${window.lang['no_payout_history']}</p>
                    </div>`;
                return;
            }

            let html = `
                <div class="payouts-grid">
                    <div class="payout-header-row">
                        <div>${window.lang['date']}</div>
                        <div>${window.lang['account']}</div>
                        <div>${window.lang['status']}</div>
                        <div style="text-align: right;">${window.lang['amount']}</div>
                        <div style="text-align: right;">${window.lang['actions']}</div>
                    </div>`;

            let totalPayouts = 0;

            json.data.forEach(p => {
                const dateObj = new Date(p.payout_date);
                const date = dateObj.toLocaleDateString();
                const amount = parseFloat(p.amount);

                if (p.confirmation_status === 'Paid') totalPayouts += amount;

                let statusBadge = '';
                if (p.confirmation_status === 'Paid') statusBadge = `<span class="status-tag status-win">${window.lang['paid']}</span>`;
                else if (p.confirmation_status === 'Rejected') statusBadge = `<span class="status-tag status-loss">${window.lang['rejected']}</span>`;
                else statusBadge = `<span class="status-tag status-pending">${window.lang['requested']}</span>`;

                html += `
                    <div class="payout-card">
                        <div class="payout-col" data-label="Date">
                            <span class="text-muted"><i class="far fa-calendar-alt me-2"></i> ${date}</span>
                        </div>
                        <div class="payout-col" data-label="Account">
                            <strong>${p.account_name}</strong>
                        </div>
                        <div class="payout-col" data-label="Status">
                            ${statusBadge}
                        </div>
                        <div class="payout-col" data-label="Amount" style="text-align: right;">
                            <span style="color: var(--accent-green); font-weight: 700; font-size: 1.1rem;">
                                +$${amount.toLocaleString(undefined, {minimumFractionDigits: 2})}
                            </span>
                        </div>
                        <div class="payout-col payout-actions" style="text-align: right;">
                            <button class="acc-btn" style="width:32px; height:32px;" onclick="editPayout(${p.id}, '${p.account_id}', '${p.amount}', '${p.payout_date}', '${p.confirmation_status}')" title="${window.lang['edit']}">
                                <i class="fas fa-pen" style="font-size: 0.8rem;"></i>
                            </button>
                            <button class="acc-btn delete" style="width:32px; height:32px;" onclick="deletePayout(${p.id})" title="${window.lang['delete']}">
                                <i class="fas fa-trash" style="font-size: 0.8rem;"></i>
                            </button>
                        </div>
                    </div>`;
            });

            html += `
                <div style="padding: 20px; text-align: right; font-size: 0.95rem; color: var(--text-secondary); margin-top: 10px;">
                    ${window.lang['total_paid']}: <span style="color: var(--text-main); font-weight: 700; font-size: 1.2rem;">$${totalPayouts.toLocaleString()}</span>
                </div>
            </div>`;

            container.innerHTML = html;
        }
    } catch (e) { console.error(e); }
}

function openPayoutModal() {
    const modal = document.getElementById('payout-modal');
    if (modal) {
        modal.style.display = 'flex';
        document.getElementById('payout-form').reset();
        document.getElementById('payout-id').value = '';
        document.getElementById('payout-date').valueAsDate = new Date();
        document.getElementById('payout-modal-title').textContent = window.lang['add_payout'];

        loadLookups().then(data => {
            if (data && data.accounts) {
                populateSelect('payout-account', data.accounts, 'name');
            }
        });
    }
}

function closePayoutModal() {
    const modal = document.getElementById('payout-modal');
    if (modal) modal.style.display = 'none';
}

function editPayout(id, accId, amount, date, status) {
    openPayoutModal();
    document.getElementById('payout-id').value = id;
    setTimeout(() => { document.getElementById('payout-account').value = accId; }, 100);
    document.getElementById('payout-amount').value = amount;
    document.getElementById('payout-date').value = date;
    document.getElementById('payout-status').value = status;
    document.getElementById('payout-modal-title').textContent = window.lang['edit_payout'];
}

async function deletePayout(id) {
    if (!confirm(window.lang['confirm_delete_payout'])) return;
    const fd = new FormData(); fd.append('id', id);
    await fetch('api/api.php?action=delete_payout', { method: 'POST', body: fd });
    loadPayouts();
}

async function loadAccountDetailsPage(id) {
    if (!id) return;
    try {
        const res = await fetch(`api/api.php?action=get_account_details&id=${id}`);
        const json = await res.json();

        if (json.success) {
            const d = json.data;
            document.getElementById('ad-name').textContent = d.name;
            document.getElementById('ad-type').textContent = d.type;

            const resStats = await fetch('api/api.php?action=get_accounts_data');
            const jsonStats = await resStats.json();
            const accStats = jsonStats.data.find(a => a.id == id);

            if (accStats) {
                const bal = parseFloat(accStats.calculated_balance);
                const start = parseFloat(accStats.starting_equity);
                const profitAbs = bal - start;
                const profitPct = start > 0 ? (profitAbs / start) * 100 : 0;

                document.getElementById('ad-balance').textContent = '$ ' + bal.toLocaleString('en-US', {minimumFractionDigits: 2});
                const profAbsEl = document.getElementById('ad-profit-abs');
                profAbsEl.textContent = (profitAbs >= 0 ? '+' : '') + profitAbs.toFixed(2) + ' $';
                profAbsEl.className = profitAbs >= 0 ? 'text-profit' : 'text-loss';
                document.getElementById('ad-profit-pct').textContent = `(${profitPct.toFixed(2)} %)`;

                renderAccountProgressBarDOM(accStats, 'ad-progress-container');
            }

            document.getElementById('btn-edit-account').onclick = () => window.location.href = `index.php?view=account_create&id=${d.id}`;
            document.getElementById('btn-add-trade-account').onclick = () => window.location.href = `index.php?view=trade_create&account_id=${d.id}`;
        }

        loadDashboardMetrics(id, true);
        loadTrades({ account_id: id });
    } catch (e) { console.error(e); }
}

function initAccountTabs(accountId) {
    const tabs = document.querySelectorAll('.tab-btn');
    const contents = document.querySelectorAll('.tab-content');
    let payoutsLoaded = false;

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));

            tab.classList.add('active');
            const targetId = tab.getAttribute('data-tab');
            document.getElementById(`tab-${targetId}`).classList.add('active');

            if (targetId === 'payouts' && !payoutsLoaded) {
                loadAccountPayouts(accountId);
                payoutsLoaded = true;
            }
        });
    });
}

async function loadAccountPayouts(accountId) {
    const container = document.getElementById('account-payouts-list-container');
    if (!container) return;

    try {
        const res = await fetch('api/api.php?action=get_payouts');
        const json = await res.json();

        if (json.success) {
            const accountPayouts = json.data.filter(p => p.account_id == accountId);

            if (accountPayouts.length === 0) {
                container.innerHTML = `<div class="glass-panel p-4 text-center text-muted"><p>${window.lang['no_payouts']}</p></div>`;
                return;
            }

            let html = '<div class="payouts-grid">';
            accountPayouts.forEach(p => {
                const date = new Date(p.payout_date).toLocaleDateString();
                const amount = parseFloat(p.amount);
                let statusBadge = `<span class="status-tag status-pending">${window.lang['requested']}</span>`;
                if (p.confirmation_status === 'Paid') statusBadge = `<span class="status-tag status-win">${window.lang['paid']}</span>`;
                else if (p.confirmation_status === 'Rejected') statusBadge = `<span class="status-tag status-loss">${window.lang['rejected']}</span>`;

                html += `
                    <div class="payout-card" style="grid-template-columns: 1fr 1fr 1fr 80px;">
                        <div class="payout-col"><span class="text-muted">${date}</span></div>
                        <div class="payout-col">${statusBadge}</div>
                        <div class="payout-col" style="text-align: right;"><span style="color: var(--accent-green); font-weight: 700;">+$${amount.toLocaleString()}</span></div>
                        <div class="payout-col payout-actions" style="text-align: right;">
                            <button class="acc-btn" style="width:32px; height:32px;" onclick="editPayout(${p.id}, '${p.account_id}', '${p.amount}', '${p.payout_date}', '${p.confirmation_status}')"><i class="fas fa-pen" style="font-size: 0.8rem;"></i></button>
                        </div>
                    </div>`;
            });
            html += '</div>';
            container.innerHTML = html;
        }
    } catch (e) { container.innerHTML = '<div class="error-state">Error</div>'; }
}

function renderAccountProgressBarDOM(acc, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const start = parseFloat(acc.starting_equity);
    const target = parseFloat(acc.target_percent);
    const maxDD = parseFloat(acc.max_drawdown_percent);
    const bal = parseFloat(acc.calculated_balance);
    const gainAbs = bal - start;
    const gainPct = start > 0 ? (gainAbs / start) * 100 : 0;

    let wLoss = 0, wProfit = 0;
    if (gainAbs >= 0) { if (target > 0) wProfit = Math.min((gainPct / target) * 100, 100); }
    else { const dd = Math.abs(gainPct); if (maxDD > 0) wLoss = Math.min((dd / maxDD) * 100, 100); }

    container.innerHTML = `
        <div class="acc-split-bar-container">
            <div class="acc-split-divider"></div>
            <div class="acc-bar-left"><div class="acc-fill-loss" style="width: ${wLoss}%"></div></div>
            <div class="acc-bar-right"><div class="acc-fill-profit" style="width: ${wProfit}%"></div></div>
        </div>
        <div class="acc-split-labels">
            <span class="text-loss">${maxDD > 0 ? window.lang['max_dd']+': '+maxDD+'%' : ''}</span>
            <span class="text-profit">${target > 0 ? window.lang['target']+': '+target+'%' : ''}</span>
        </div>`;
}
