<div class="fade-in">
    <input type="hidden" id="qpa-year" value="<?php echo isset($_GET['year']) ? intval($_GET['year']) : ''; ?>">
    <input type="hidden" id="qpa-quarter"
        value="<?php echo isset($_GET['quarter']) ? intval($_GET['quarter']) : ''; ?>">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="index.php?view=qpa" class="btn btn-outline">
                <div class="d-flex align-items-center justify-content-center">
                    <i class="fas fa-arrow-left text-secondary"></i>
                </div>
            </a>
            <div>
                <h2 class="page-title m-0" id="qpa-title"><?= $lang['loading'] ?></h2>
                <div class="text-muted d-flex align-items-center gap-2 mt-1" style="font-size: 0.9rem;">
                    <i class="fas fa-layer-group"></i> <?= $lang['quarterly_review'] ?>
                </div>
            </div>
        </div>
    </div>

    <div class="pro-metrics-grid mb-5">
        <div class="glass-panel metric-card-pro"
            style="border-bottom: 3px solid var(--text-profit, #00d66f) !important;">
            <i class="fas fa-wallet metric-bg-icon"></i>
            <div>
                <div class="metric-label-pro"><?= $lang['net_profit'] ?></div>
                <div class="metric-value-pro" id="qpa-pnl"><?= $lang['loading'] ?></div>
            </div>
            <div class="metric-subtext-pro" id="qpa-pnl-percent">-</div>
        </div>

        <div class="glass-panel metric-card-pro"
            style="border-bottom: 3px solid var(--accent-blue, #2997ff) !important;">
            <i class="fas fa-chart-pie metric-bg-icon"></i>
            <div>
                <div class="metric-label-pro"><?= $lang['win_rate'] ?></div>
                <div class="metric-value-pro" id="qpa-winrate"><?= $lang['loading'] ?></div>
            </div>
            <div class="mpa-progress-bg mt-3">
                <div id="qpa-winrate-bar" class="mpa-progress-fill" style="width: 0%; background: var(--accent-blue);">
                </div>
            </div>
        </div>

        <div class="glass-panel metric-card-pro" style="border-bottom: 3px solid #ffc107 !important;">
            <i class="fas fa-balance-scale metric-bg-icon"></i>
            <div>
                <div class="metric-label-pro"><?= $lang['avg_rr'] ?></div>
                <div class="metric-value-pro" id="qpa-rr"><?= $lang['loading'] ?></div>
            </div>
            <div class="metric-subtext-pro text-muted"><?= $lang['risk_reward_ratio'] ?></div>
        </div>

        <div class="glass-panel metric-card-pro"
            style="border-bottom: 3px solid var(--text-secondary, #6b7280) !important;">
            <i class="fas fa-list-ol metric-bg-icon"></i>
            <div>
                <div class="metric-label-pro"><?= $lang['total_trades_count'] ?></div>
                <div class="metric-value-pro" id="qpa-total"><?= $lang['loading'] ?></div>
            </div>
            <div class="metric-subtext-pro d-flex gap-2 mt-2" id="qpa-trades-breakdown"></div>
        </div>
    </div>

    <div class="mb-5">
        <h4 class="mb-3 fw-bold text-white"><i class="far fa-calendar-alt me-2"></i><?= $lang['quarter_breakdown'] ?>
        </h4>

        <div class="grid-force-3" id="qpa-months-container">
            <div class="text-muted" style="grid-column: 1 / -1;"><?= $lang['loading_months'] ?></div>
        </div>
    </div>

    <div class="mb-5">
        <div class="d-flex align-items-center gap-2 mb-3">
            <h4 class="fw-bold text-white m-0"><i class="fas fa-list me-2"></i><?= $lang['quarter_trades'] ?></h4>
            <span class="section-count-badge" id="trades-count-badge">0</span>
        </div>

        <div class="trades-list-wrapper glass-panel p-0">
            <div class="trade-row trade-header-row">
                <div class="t-col t-date"><?= $lang['date'] ?></div>
                <div class="t-col t-pair"><?= $lang['pair'] ?></div>
                <div class="t-col t-account"><?= $lang['account'] ?></div>
                <div class="t-col t-dir"><?= $lang['direction'] ?></div>
                <div class="t-col t-status"><?= $lang['status'] ?></div>
                <div class="t-col t-risk"><?= $lang['risk'] ?></div>
                <div class="t-col t-rr"><?= $lang['rr'] ?></div>
                <div class="t-col t-pnl"><?= $lang['pnl_dollar'] ?></div>
                <div class="t-col t-actions"></div>
            </div>

            <div class="trades-inner" id="qpa-trades-body">
                <div class="text-center py-4 text-muted"><?= $lang['loading_trades'] ?></div>
            </div>
        </div>
    </div>

    <div class="mb-5">
        <div class="d-flex align-items-center gap-2 mb-3">
            <h4 class="fw-bold text-white m-0"><i class="fas fa-map me-2"></i><?= $lang['quarter_plans'] ?></h4>
            <span class="section-count-badge" id="plans-count-badge">0</span>
        </div>

        <div class="plans-grid" id="qpa-plans-body">
            <div class="text-center py-4 text-muted" style="grid-column: 1 / -1;"><?= $lang['loading_plans'] ?></div>
        </div>
    </div>

    <div class="mt-5 mb-5">
        <h4 class="mb-3 fw-bold text-white"><i class="fas fa-feather-alt me-2"></i><?= $lang['quarterly_conclusion'] ?>
        </h4>
        <div class="glass-panel p-4">
            <form id="qpa-report-form">
                <div class="mb-4">
                    <label
                        class="form-label text-muted small text-uppercase fw-bold"><?= $lang['meta_analysis'] ?></label>
                    <div id="qpa-editor-container"
                        style="height: 300px; background: rgba(0,0,0,0.2); border-radius: 8px; border: 1px solid var(--glass-border);">
                    </div>
                </div>
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary px-4 py-2">
                        <i class="fas fa-save me-2"></i> <?= $lang['save_report'] ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>