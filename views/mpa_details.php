<div class="fade-in">
    <input type="hidden" id="detail-year" value="<?php echo isset($_GET['year']) ? intval($_GET['year']) : ''; ?>">
    <input type="hidden" id="detail-month" value="<?php echo isset($_GET['month']) ? intval($_GET['month']) : ''; ?>">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="index.php?view=mpa" class="btn-icon-back">
                <div class="d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: rgba(255,255,255,0.05); border-radius: 10px; transition: 0.2s;">
                    <i class="fas fa-arrow-left text-secondary"></i>
                </div>
            </a>
            <div>
                <h2 class="page-title m-0" id="month-title"><?= $lang['loading'] ?></h2>
                <div class="text-muted d-flex align-items-center gap-2 mt-1" style="font-size: 0.9rem;">
                    <i class="far fa-calendar-alt"></i> <?= $lang['monthly_performance_review'] ?>
                </div>
            </div>
        </div>
    </div>

    <div class="pro-metrics-grid mb-5">
        <div class="glass-panel metric-card-pro" style="border-bottom: 3px solid var(--text-profit, #00d66f) !important;" id="card-pnl">
            <i class="fas fa-wallet metric-bg-icon"></i>
            <div>
                <div class="metric-label-pro"><?= $lang['net_profit'] ?></div>
                <div class="metric-value-pro" id="month-pnl">-</div>
            </div>
            <div class="metric-subtext-pro" id="month-pnl-percent">-</div>
        </div>

        <div class="glass-panel metric-card-pro" style="border-bottom: 3px solid var(--accent-blue, #2997ff) !important;">
            <i class="fas fa-chart-pie metric-bg-icon"></i>
            <div>
                <div class="metric-label-pro"><?= $lang['win_rate'] ?></div>
                <div class="metric-value-pro" id="month-winrate">-</div>
            </div>
            <div class="mpa-progress-bg mt-3">
                <div id="month-winrate-bar" class="mpa-progress-fill" style="width: 0%; background: var(--accent-blue);"></div>
            </div>
        </div>

        <div class="glass-panel metric-card-pro" style="border-bottom: 3px solid #ffc107 !important;">
            <i class="fas fa-balance-scale metric-bg-icon"></i>
            <div>
                <div class="metric-label-pro"><?= $lang['avg_rr'] ?></div>
                <div class="metric-value-pro" id="month-rr">-</div>
            </div>
            <div class="metric-subtext-pro text-muted"><?= $lang['risk_reward_ratio'] ?></div>
        </div>

        <div class="glass-panel metric-card-pro" style="border-bottom: 3px solid var(--text-secondary, #6b7280) !important;">
            <i class="fas fa-list-ol metric-bg-icon"></i>
            <div>
                <div class="metric-label-pro"><?= $lang['total_trades_count'] ?></div>
                <div class="metric-value-pro" id="month-trades-count">-</div>
            </div>
            <div class="metric-subtext-pro d-flex gap-2 mt-2" id="month-trades-breakdown">
            </div>
        </div>
    </div>

    <div class="mb-5">
        <div class="d-flex align-items-center gap-2 mb-3">
            <h4 class="fw-bold text-white m-0"><i class="fas fa-chess-board me-2"></i><?= $lang['plan_performance'] ?></h4>
            <span class="section-count-badge" id="month-plans-count-badge">0</span>
        </div>
        <div class="plans-grid" id="month-plans-container">
            <div class="text-muted" style="grid-column: 1 / -1;"><?= $lang['loading_plans_data'] ?></div>
        </div>
    </div>

    <div class="mb-5">
        <div class="d-flex align-items-center gap-2 mb-3">
            <h4 class="fw-bold text-white m-0"><i class="fas fa-list me-2"></i><?= $lang['trade_history'] ?></h4>
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

            <div class="trades-inner" id="month-trades-container">
                <div class="text-center py-4 text-muted"><?= $lang['loading_trades'] ?></div>
            </div>
        </div>
    </div>

    <div class="mt-5 mb-5">
        <h4 class="mb-3 fw-bold text-white"><i class="fas fa-feather-alt me-2"></i><?= $lang['monthly_conclusion'] ?></h4>

        <div class="glass-panel p-4">
            <form id="mpa-report-form">
                <div class="mb-3">
                    <label class="form-label text-muted small text-uppercase fw-bold"><?= $lang['meta_analysis'] ?></label>
                    <div id="mpa-editor-container" style="height: 300px; background: rgba(0,0,0,0.2); border-radius: 8px; border: 1px solid var(--glass-border);"></div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary px-4 py-2" id="btn-save-report">
                        <i class="fas fa-save me-2"></i> <?= $lang['save_conclusion'] ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
