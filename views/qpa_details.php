<div class="fade-in">
    <input type="hidden" id="qpa-year" value="<?php echo isset($_GET['year']) ? intval($_GET['year']) : ''; ?>">
    <input type="hidden" id="qpa-quarter" value="<?php echo isset($_GET['quarter']) ? intval($_GET['quarter']) : ''; ?>">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="index.php?view=qpa" class="btn-icon-back">
                <div class="d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: rgba(255,255,255,0.05); border-radius: 10px; transition: 0.2s;">
                    <i class="fas fa-arrow-left text-secondary"></i>
                </div>
            </a>
            <div>
                <h2 class="m-0 fw-bold" id="qpa-title"><?= $lang['loading'] ?></h2>
                <div class="text-muted d-flex align-items-center gap-2" style="font-size: 0.9rem;">
                    <i class="fas fa-layer-group"></i> <?= $lang['quarterly_review'] ?>
                </div>
            </div>
        </div>
    </div>

    <div class="grid-force-2 mb-5">
        
        <div class="metric-card glass-panel h-100">
            <div class="metric-icon"><i class="fas fa-wallet"></i></div>
            <div class="metric-content">
                <div class="metric-label"><?= $lang['net_profit'] ?></div>
                <div class="metric-value" id="qpa-pnl"><?= $lang['loading'] ?></div>
            </div>
        </div>

        <div class="metric-card glass-panel h-100">
            <div class="metric-icon"><i class="fas fa-trophy"></i></div>
            <div class="metric-content">
                <div class="metric-label"><?= $lang['win_rate'] ?></div>
                <div class="metric-value" id="qpa-winrate"><?= $lang['loading'] ?></div>
            </div>
        </div>

        <div class="metric-card glass-panel h-100">
            <div class="metric-icon"><i class="fas fa-exchange-alt"></i></div>
            <div class="metric-content">
                <div class="metric-label"><?= $lang['avg_rr'] ?></div>
                <div class="metric-value" id="qpa-rr"><?= $lang['loading'] ?></div>
            </div>
        </div>

        <div class="metric-card glass-panel h-100">
            <div class="metric-icon"><i class="fas fa-chart-line"></i></div>
            <div class="metric-content">
                <div class="metric-label"><?= $lang['total_trades_count'] ?></div>
                <div class="metric-value" id="qpa-total"><?= $lang['loading'] ?></div>
            </div>
        </div>

    </div>
    
    <div class="mb-5">
        <h5 class="mb-3 fw-bold text-white"><i class="far fa-calendar-alt me-2"></i><?= $lang['quarter_breakdown'] ?></h5>
        
        <div class="grid-force-3" id="qpa-months-container">
            <div class="text-muted"><?= $lang['loading_months'] ?></div>
        </div>
    </div>

    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-white m-0"><i class="fas fa-list me-2"></i><?= $lang['quarter_trades'] ?></h5>
            <span class="badge bg-secondary rounded-pill" id="trades-count-badge">0</span>
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
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-white m-0"><i class="fas fa-map me-2"></i><?= $lang['quarter_plans'] ?></h5>
            <span class="badge bg-info rounded-pill" id="plans-count-badge">0</span>
        </div>
        
        <div class="plans-grid" id="qpa-plans-body">
            <div class="text-center py-4 text-muted"><?= $lang['loading_plans'] ?></div>
        </div>
    </div>

    <div class="mt-5 mb-5">
        <h5 class="mb-3 fw-bold text-white"><i class="fas fa-feather-alt me-2"></i><?= $lang['quarterly_conclusion'] ?></h5>
        <div class="glass-panel p-4">
            <form id="qpa-report-form">
                <div class="mb-4">
                    <label class="form-label text-muted small text-uppercase fw-bold"><?= $lang['meta_analysis'] ?></label>
                    <div id="qpa-editor-container" style="height: 300px; background: rgba(0,0,0,0.2); border-radius: 8px;"></div>
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