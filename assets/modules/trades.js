// assets/modules/trades.js
// ==================================================
// ФУНКЦИИ ДЛЯ СДЕЛОК
// ==================================================

async function initTradeForm() {
    const tradeIdInput = document.getElementById('edit-trade-id');
    isTradeEditMode = (tradeIdInput && tradeIdInput.value.trim() !== "");

    await loadLookups();
    if (isTradeEditMode) {
        await loadTradeDataForEdit(tradeIdInput.value);
    } else {
        addTradeImage();
    }
    setupRRCalculation();
}

function setupRRCalculation() {
    const accountSelect = document.getElementById('trade-account');
    const riskInput = document.getElementById('trade-risk');
    const pnlInput = document.getElementById('trade-pnl');
    const rrInput = document.getElementById('trade-rr-achieved');

    if (!accountSelect || !riskInput || !pnlInput || !rrInput) return;

    const calculate = () => {
        const accountId = accountSelect.value;
        const riskPercent = parseFloat(riskInput.value);
        const pnl = parseFloat(pnlInput.value);
        const balance = accountBalances[accountId];

        if (accountId && balance && !isNaN(riskPercent) && !isNaN(pnl) && riskPercent > 0) {
            const riskAmount = balance * (riskPercent / 100);
            if (riskAmount > 0) {
                const rr = pnl / riskAmount;
                rrInput.value = rr.toFixed(2);
            }
        }
    };

    accountSelect.addEventListener('change', calculate);
    riskInput.addEventListener('input', calculate);
    pnlInput.addEventListener('input', calculate);
}

async function loadTradeDataForEdit(tradeId) {
    try {
        const response = await fetch(`api/api.php?action=get_trade_details&id=${tradeId}`);
        const result = await response.json();
        if (result.success) {
            const trade = result.data;
            for (const key in trade) {
                const input = document.querySelector(`[name="${key}"]`);
                if (input) {
                    if (input.type === 'radio') {
                        const radio = document.querySelector(`[name="${key}"][value="${trade[key]}"]`);
                        if (radio) radio.checked = true;
                    } else if (input.type === 'datetime-local' && trade[key]) {
                        input.value = trade[key].replace(' ', 'T').slice(0, 16);
                    } else {
                        input.value = trade[key];
                    }
                }
            }
            if (trade.pair_id) document.getElementById('trade-pair').value = trade.pair_id;
            if (trade.account_id) document.getElementById('trade-account').value = trade.account_id;
            if (trade.style_id) document.getElementById('trade-style').value = trade.style_id;
            if (trade.model_id) document.getElementById('trade-model').value = trade.model_id;
            if (trade.plan_id) document.getElementById('trade-plan').value = trade.plan_id;
            if (trade.status) document.getElementById('trade-status').value = trade.status;
            if (trade.entry_tf) document.getElementById('trade-entry-tf').value = trade.entry_tf;
            if (trade.note_id) document.getElementById('trade-note').value = trade.note_id;

            const container = document.getElementById('trade-images-container');
            container.innerHTML = '';
            if (trade.trade_images && trade.trade_images.length > 0) {
                trade.trade_images.forEach(img => addTradeImage(img));
            } else {
                addTradeImage();
            }
            document.getElementById('form-page-title').textContent = window.lang['edit_trade'];

            const event = new Event('input', { bubbles: true });
            const pnlInput = document.getElementById('trade-pnl');
            if (pnlInput) pnlInput.dispatchEvent(event);
        } else {
            showMessage(window.lang['error_loading_trade'] + ': ' + result.message, 'error');
            window.location.href = 'index.php?view=journal';
        }
    } catch (error) {
        console.error('Error loading trade for edit:', error);
        showMessage(window.lang['network_error'], 'error');
    }
}

function addTradeImage(data = null) {
    tradeImgCount++;
    const container = document.getElementById('trade-images-container');
    const imgId = `trade-img-${tradeImgCount}`;
    const url = data?.image_url || '';
    const notes = data?.notes || '';
    const title = data?.title || '';

    const html = `
        <div class="trade-img-card glass-panel" id="${imgId}">
             <div class="d-flex justify-content-between align-items-start mb-3">
                <div style="flex-grow: 1; margin-right: 15px;">
                     <label class="form-label" style="font-size: 0.8em; margin-bottom: 4px;">${window.lang['timeframe_context']}</label>
                     <input type="text" name="trade_images[${tradeImgCount-1}][title]" class="input-field" placeholder="${window.lang['example_timeframe']}" value="${title}">
                </div>
            </div>
            <div class="form-group">
                 ${getImageInputHtml(imgId, url, `trade_images[${tradeImgCount-1}][url]`)}
            </div>
             <div class="form-group">
                <label class="form-label" style="font-size: 0.8em; margin-bottom: 4px;">${window.lang['description_idea']}</label>
                <textarea class="textarea-field" name="trade_images[${tradeImgCount-1}][notes]" rows="2" placeholder="${window.lang['describe_screenshot']}">${notes}</textarea>
            </div>
            <div class="text-end mt-2">
                <button type="button" class="btn btn-danger btn-sm" onclick="document.getElementById('${imgId}').remove()">
                    <i class="fas fa-trash-alt me-2"></i> ${window.lang['delete_screenshot']}
                </button>
            </div>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
}

function getImageInputHtml(id, url, name) {
    return `
        <input type="hidden" name="${name}" value="${url}">
        <input type="file" id="${id}-file" class="input-field" style="display:none" onchange="previewImage(this, '${id}-preview')">
        <div class="d-flex gap-2 mb-2">
            <button type="button" class="btn btn-outline flex-grow-1" onclick="document.getElementById('${id}-file').click()">
                <i class="fas fa-upload me-2"></i> ${window.lang['upload_file']}
            </button>
        </div>
        <input type="text" id="${id}-url" class="input-field mb-2" placeholder="${window.lang['paste_link_image']}" value="${url}" oninput="previewImage(this, '${id}-preview')">
        <div id="${id}-preview" class="image-preview-box">
            ${url ? `<img src="${url}" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                     <span class="image-preview-placeholder" style="display:none;">${window.lang['loading_error']}</span>`
                  : `<span class="image-preview-placeholder">${window.lang['image_preview']}</span>`}
        </div>
    `;
}

function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const formGroup = preview.closest('.form-group');
    const hiddenUrlInput = formGroup.querySelector(`input[type="hidden"]`);
    const textUrlInput = formGroup.querySelector(`input[type="text"]`);

    if (input.type === 'file' && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.innerHTML = `<img src="${e.target.result}">`;
            if (textUrlInput) textUrlInput.value = '';
        };
        reader.readAsDataURL(input.files[0]);
    } else if (input.type === 'text') {
        const val = input.value.trim();
        if (val) {
            preview.innerHTML = `<img src="${val}" onerror="this.style.display='none'; this.parentElement.querySelector('.err-msg').style.display='block';">
                                  <span class="image-preview-placeholder err-msg" style="display:none; color: var(--accent-red);">${window.lang['could_not_load_image']}</span>`;
            if (hiddenUrlInput) hiddenUrlInput.value = val;
        } else {
            preview.innerHTML = `<span class="image-preview-placeholder">${window.lang['image_preview']}</span>`;
            if (hiddenUrlInput) hiddenUrlInput.value = '';
        }
    }
}

async function loadTrades(filters = {}) {
    const container = document.getElementById('trades-list-container');
    if (!container) return;
    container.innerHTML = getSkeletonHtml('trade-row', 6);

    try {
        const params = new URLSearchParams(filters);
        const response = await fetch(`api/api.php?action=get_trades&${params}`);
        const result = await response.json();

        if (result.success) {
            const groupedTrades = result.data;
            if (groupedTrades.length === 0) {
                container.innerHTML = `<div class="empty-state">${window.lang['trades_not_found']}</div>`;
                return;
            }

            let html = '';
            groupedTrades.forEach(group => {
                const pnlClass = group.total_pnl >= 0 ? 'text-profit' : 'text-loss';
                const pnlSign = group.total_pnl >= 0 ? '+' : '';
                const pctVal = group.total_percent || 0;
                const pctSign = pctVal >= 0 ? '+' : '';

                html += `
                    <div class="month-group">
                        <div class="month-header" onclick="this.parentElement.classList.toggle('collapsed')">
                            <div class="month-label">
                                <i class="fas fa-chevron-right month-toggle-icon"></i>
                                <i class="far fa-calendar-alt text-muted"></i>
                                ${group.month_label}
                            </div>
                            <div class="month-summary">
                                <div class="sum-item ${pnlClass}">
                                    PnL: ${pnlSign}${group.total_pnl.toFixed(2)}
                                </div>
                                <div class="sum-item ${pnlClass}">
                                    ${pctSign}${pctVal.toFixed(2)}%
                                </div>
                                <div class="sum-item text-main">
                                    RR: ${group.total_rr.toFixed(2)}
                                </div>
                            </div>
                        </div>
                        <div class="trades-list-wrapper">
                            <div class="trades-inner">
                                <div class="trade-row trade-header-row">
                                    <div class="t-col t-date">${window.lang['date']}</div>
                                    <div class="t-col t-pair">${window.lang['pair']}</div>
                                    <div class="t-col t-account">${window.lang['account']}</div>
                                    <div class="t-col t-dir">${window.lang['dir']}</div>
                                    <div class="t-col t-status">${window.lang['status']}</div>
                                    <div class="t-col t-risk">${window.lang['risk']}</div>
                                    <div class="t-col t-rr">${window.lang['rr_table']}</div>
                                    <div class="t-col t-pnl">${window.lang['pnl_table']}</div>
                                    <div class="t-col t-actions">${window.lang['actions']}</div>
                                </div>`;

                group.trades.forEach(trade => {
                    html += getTradeRowHtml(trade);
                });

                html += '</div></div></div>';
            });
            container.innerHTML = html;
        } else { container.innerHTML = `<div class="error-state">${window.lang['error']}: ${result.message}</div>`; }
    } catch (error) { console.error(error); container.innerHTML = `<div class="error-state">${window.lang['loading_error']}</div>`; }
}

async function loadTradeDetails() {
    const tradeId = document.getElementById('current-trade-id')?.value;
    if (!tradeId) return;
    const container = document.getElementById('trade-details-container');
    if (container) container.style.opacity = '0.5';

    try {
        const response = await fetch(`api/api.php?action=get_trade_details&id=${tradeId}`);
        const result = await response.json();
        if (result.success) {
            const trade = result.data;

            document.getElementById('trade-details-title').innerHTML = `${trade.pair_symbol} <span class="dir-tag dir-${trade.direction}" style="font-size: 0.6em; vertical-align: middle;">${trade.direction.toUpperCase()}</span>`;
            const editBtn = document.querySelector('.trade-actions .btn-secondary');
            const deleteBtn = document.querySelector('.trade-actions .btn-danger');
            if (editBtn) editBtn.onclick = () => window.location.href = `index.php?view=trade_create&id=${trade.id}`;
            if (deleteBtn) deleteBtn.onclick = () => deleteEntity(trade.id, 'delete_trade', 'journal');

            ['entry_date', 'exit_date'].forEach(key => {
                const el = document.getElementById(`trade-${key}`);
                if (el && trade[key]) {
                    const dateObj = new Date(trade[key]);
                    const day = String(dateObj.getDate()).padStart(2, '0');
                    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                    const year = String(dateObj.getFullYear()).slice(-2);
                    el.textContent = `${day}.${month}.${year}`;
                }
            });

            const durationEl = document.getElementById('trade-duration');
            if (trade.entry_date && trade.exit_date) {
                const entry = new Date(trade.entry_date);
                const exit = new Date(trade.exit_date);
                const diffMs = exit - entry;
                const totalHours = Math.floor(diffMs / (1000 * 60 * 60));
                const days = Math.floor(totalHours / 24);
                const hours = totalHours % 24;
                let durationText = '';
                if (days > 0) durationText += `${days}d `;
                if (hours > 0) durationText += `${hours}h`;
                if (days === 0 && hours === 0) durationText = window.lang['less_than_1_hour'];
                durationEl.textContent = durationText.trim();
                durationEl.className = 'detail-value info-badge badge-neutral';
            } else {
                durationEl.textContent = trade.exit_date ? window.lang['no_entry_date'] : window.lang['in_progress'];
                durationEl.className = 'detail-value';
            }

            ['pair_symbol', 'account_name', 'style_name', 'model_name', 'risk_percent', 'rr_achieved',
             'pnl', 'status', 'trade_conclusions', 'key_lessons',
             'notes', 'mistakes_made', 'emotional_state']
            .forEach(key => {
                const el = document.getElementById(`trade-${key.replace('formatted_', '')}`);
                if (el) {
                    if (key === 'pnl' || key === 'rr_achieved') {
                        const val = parseFloat(trade[key]);
                        el.textContent = val.toFixed(2) + (key === 'risk_percent' ? '%' : (key === 'rr_achieved' ? 'R' : ''));
                        el.className = 'detail-value info-badge ' + (val >= 0 ? 'badge-profit' : 'badge-loss');
                        if (key === 'risk_percent') el.textContent += '%';
                    } else if (key === 'status') {
                        const val = trade[key].toLowerCase();
                        el.textContent = val.toUpperCase();
                        el.className = 'detail-value info-badge';
                        if (val === 'win') el.classList.add('badge-profit');
                        else if (val === 'loss') el.classList.add('badge-loss');
                        else if (val === 'breakeven') el.classList.add('badge-blue');
                        else el.classList.add('badge-neutral');
                    } else if (key === 'risk_percent') {
                        el.textContent = `${trade[key]}%`;
                        el.className = 'detail-value info-badge badge-neutral';
                    } else if (key === 'notes' || key === 'trade_conclusions' || key === 'key_lessons' || key === 'mistakes_made' || key === 'emotional_state') {
                        el.textContent = trade[key] || '-';
                    } else {
                        el.textContent = trade[key] || window.lang['empty'];
                    }
                }
            });

            const entryTfEl = document.getElementById('trade-entry_timeframe');
            if (entryTfEl) entryTfEl.textContent = trade.entry_tf || window.lang['empty'];

            const directionEl = document.getElementById('trade-direction');
            if (directionEl) {
                const dir = trade.direction.toLowerCase();
                directionEl.textContent = dir.toUpperCase();
                directionEl.className = 'info-badge';
                if (dir === 'long') directionEl.classList.add('badge-profit');
                else directionEl.classList.add('badge-loss');
            }

            const tagsEl = document.getElementById('trade-tags');
            if (tagsEl) {
                if (trade.tags) {
                    tagsEl.innerHTML = trade.tags.split(',').map(tag => `<span class="trade-tag">${tag.trim()}</span>`).join('');
                } else {
                    tagsEl.textContent = window.lang['none'];
                }
            }

            const planLink = document.getElementById('trade-plan-link');
            if (planLink) {
                if (trade.plan_id && trade.plan_title) {
                    planLink.href = `index.php?view=plan_details&id=${trade.plan_id}`;
                    planLink.className = 'info-badge badge-blue';
                    planLink.innerHTML = `<i class="fas fa-solid fa-link me-2"></i> ${trade.plan_title}`;
                } else {
                    planLink.textContent = window.lang['no_linked_plan'];
                    planLink.removeAttribute('href');
                    planLink.className = 'info-badge badge-neutral';
                }
            }

            const tradeImgList = document.getElementById('trade-images-list');
            if (tradeImgList) {
                tradeImgList.innerHTML = '';
                if (trade.trade_images && trade.trade_images.length) {
                    trade.trade_images.forEach(img => {
                        tradeImgList.innerHTML += `
                            <div class="trade-image-item">
                                ${img.image_url ? `<img src="${img.image_url}" class="lightbox-trigger">` : `<p class="text-muted">${window.lang['no_image']}</p>`}
                                ${img.notes ? `<div class="notes small text-muted mt-2">${img.notes}</div>` : ''}
                            </div>`;
                    });
                } else { tradeImgList.innerHTML = `<div class="empty-state-small">${window.lang['no_screenshots_trade']}</div>`; }
            }
        } else {
            showMessage(window.lang['error_loading_trade_details'] + ': ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error loading trade details for edit:', error);
        showMessage(window.lang['network_error'], 'error');
    } finally { if (container) container.style.opacity = '1'; }
}

async function deleteEntity(id, action, redirectView) {
    if (!confirm(window.lang['confirm_delete_action'])) return;
    try {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('trade_id', id);
        formData.append('plan_id', id);

        const response = await fetch('api/api.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) window.location.href = `index.php?view=${redirectView}`;
        else showMessage(window.lang['delete_error'] + ': ' + result.message, 'error');
    } catch (e) { console.error(e); showMessage(window.lang['network_error'], 'error'); }
}
