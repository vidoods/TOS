// assets/modules/shared_ui.js
// ==================================================
// ОБЩИЕ HTML-ГЕНЕРАТОРЫ (используются в нескольких модулях)
// ==================================================

/**
 * Генерирует HTML одной строки сделки (стиль Журнала)
 */
function getTradeRowHtml(trade) {
    const date = new Date(trade.entry_date).toLocaleDateString(undefined, {day: '2-digit', month: '2-digit', year: '2-digit'});
    const statusClass = `status-${trade.status}`;
    
    // ИСПРАВЛЕНИЕ: Интегрировали CurrencyManager для красивого вывода валюты в строке таблицы
    const pnlVal = parseFloat(trade.pnl);
    const formattedPnl = CurrencyManager.format(pnlVal);
    const pnlColor = pnlVal >= 0 ? 'text-profit' : 'text-loss';
    let dirColorClass = trade.direction === 'Long' ? 'text-long' : 'text-short';
    
    const rrVal = Number(trade.rr_achieved).toFixed(2);
    const accName = trade.account_name || '-';

    return `
    <div class="trade-row trade-item" onclick="window.location.href='index.php?view=trade_details&id=${trade.id}'">
        <div class="t-col t-date">
            <span class="mobile-label">${window.lang['date']}:</span> ${date}
        </div>
        <div class="t-col t-pair">
            <span class="mobile-label">${window.lang['pair']}:</span> <strong>${escapeHTML(trade.pair_symbol)}</strong>
        </div>
        <div class="t-col t-account">
            <span class="mobile-label">${window.lang['acc']}:</span> <strong>${escapeHTML(accName)}</strong>
        </div>
        <div class="t-col t-dir">
            <span class="mobile-label">${window.lang['dir']}:</span>
            <span class="dir-tag dir-${trade.direction} status-tag ${dirColorClass}">${trade.direction.toUpperCase()}</span>
        </div>
        <div class="t-col t-status">
            <span class="mobile-label">${window.lang['status']}:</span>
            <span class="status-tag ${statusClass}">${trade.status.charAt(0).toUpperCase() + trade.status.slice(1)}</span>
        </div>
        <div class="t-col t-risk"><span class="mobile-label">${window.lang['risk']}:</span> ${trade.risk_percent}%</div>
        <div class="t-col t-rr"><span class="mobile-label">${window.lang['rr_table']}:</span> ${rrVal}</div>
        <div class="t-col t-pnl ${pnlColor}">
            <span class="mobile-label">${window.lang['pnl_table']}:</span> ${formattedPnl}
        </div>
        <div class="t-col t-actions" onclick="event.stopPropagation()">
            <a title="${window.lang['view']}" href="index.php?view=trade_details&id=${trade.id}" class="btn-icon"><i class="fas fa-eye"></i></a>
            <a title="${window.lang['edit']}" href="index.php?view=trade_create&id=${trade.id}" class="btn-icon"><i class="fas fa-edit"></i></a>
        </div>
    </div>`;
}

/**
 * Генерирует HTML одной карточки плана (стиль Планов)
 */
function getPlanCardHtml(plan) {
    const dateObj = new Date(plan.date);
    const typeChar = plan.type ? plan.type.charAt(0) : '?';

    // БЕЗОПАСНОСТЬ: Добавлено экранирование escapeHTML для защиты от XSS в названии плана и тикера
    return `
    <a href="index.php?view=plan_details&id=${plan.id}" class="plan-card glass-panel">
        <div class="plan-date-box"><span>${dateObj.getDate()}</span><span class="plan-date-type">${typeChar}</span></div>
        <div class="plan-info"><span class="plan-symbol">${escapeHTML(plan.pair_symbol)}</span><span class="plan-title-text">${escapeHTML(plan.title)}</span></div>
        <div class="plan-bias-tag bias-${plan.bias.toLowerCase()}">${plan.bias}</div>
        <div class="plan-arrow">➜</div>
    </a>`;
}