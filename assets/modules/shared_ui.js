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
    const pnlVal = Number(trade.pnl).toFixed(2);
    const pnlColor = Number(trade.pnl) >= 0 ? 'text-profit' : 'text-loss';
    const rrVal = Number(trade.rr_achieved).toFixed(2);
    const accName = trade.account_name || '-';

    return `
    <div class="trade-row trade-item" onclick="window.location.href='index.php?view=trade_details&id=${trade.id}'">
        <div class="t-col t-date">
            <span class="mobile-label">Date:</span> ${date}
        </div>
        <div class="t-col t-pair">
            <span class="mobile-label">Pair:</span> <strong>${trade.pair_symbol}</strong>
        </div>
        <div class="t-col t-account">
            <span class="mobile-label">Acc:</span> <strong>${accName}</strong>
        </div>
        <div class="t-col t-dir">
            <span class="mobile-label">Dir:</span>
            <span class="dir-tag dir-${trade.direction} status-tag ${statusClass}">${trade.direction.toUpperCase()}</span>
        </div>
        <div class="t-col t-status">
            <span class="mobile-label">Status:</span>
            <span class="status-tag ${statusClass}">${trade.status.charAt(0).toUpperCase() + trade.status.slice(1)}</span>
        </div>
        <div class="t-col t-risk"><span class="mobile-label">Risk:</span> ${trade.risk_percent}%</div>
        <div class="t-col t-rr"><span class="mobile-label">RR:</span> ${rrVal}</div>
        <div class="t-col t-pnl ${pnlColor}">
            <span class="mobile-label">PnL:</span> ${pnlVal}
        </div>
        <div class="t-col t-actions" onclick="event.stopPropagation()">
            <a title="View" href="index.php?view=trade_details&id=${trade.id}" class="btn-icon"><i class="fas fa-eye"></i></a>
            <a title="Edit" href="index.php?view=trade_create&id=${trade.id}" class="btn-icon"><i class="fas fa-edit"></i></a>
        </div>
    </div>`;
}

/**
 * Генерирует HTML одной карточки плана (стиль Планов)
 */
function getPlanCardHtml(plan) {
    const dateObj = new Date(plan.date);
    const typeChar = plan.type ? plan.type.charAt(0) : '?';

    return `
    <a href="index.php?view=plan_details&id=${plan.id}" class="plan-card glass-panel">
        <div class="plan-date-box"><span>${dateObj.getDate()}</span><span class="plan-date-type">${typeChar}</span></div>
        <div class="plan-info"><span class="plan-symbol">${plan.pair_symbol}</span><span class="plan-title-text">${plan.title}</span></div>
        <div class="plan-bias-tag bias-${plan.bias.toLowerCase()}">${plan.bias}</div>
        <div class="plan-arrow">➜</div>
    </a>`;
}
