<div class="fade-in">
    <input type="hidden" id="detail-year" value="<?php echo isset($_GET['year']) ? intval($_GET['year']) : ''; ?>">
    <input type="hidden" id="detail-month" value="<?php echo isset($_GET['month']) ? intval($_GET['month']) : ''; ?>">

    <div class="d-flex justify-content-between align-items-center mb-5">
        <div class="d-flex align-items-center gap-4">
            <a href="index.php?view=mpa" class="btn-icon-back">
                <div class="d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background: rgba(255,255,255,0.05); border-radius: 12px; transition: 0.2s; margin-right: 5px;">
                    <i class="fas fa-arrow-left text-secondary"></i>
                </div>
            </a>
            <div>
                <h2 class="m-0 fw-bold display-6" id="month-title"><?= $lang['loading'] ?></h2>
                <div class="text-muted d-flex align-items-center gap-2 mt-1">
                    <i class="far fa-calendar-alt"></i> <?= $lang['monthly_performance_review'] ?>
                </div>
            </div>
        </div>
        
    </div>

    <div class="row g-4 mb-5">
        
        <div class="col-12 col-md-6 col-lg-3">
            <div class="metric-card-pro" style="border-bottom: 3px solid #198754;" id="card-pnl">
                <i class="fas fa-wallet metric-bg-icon"></i>
                <div>
                    <div class="metric-label-pro"><?= $lang['net_profit'] ?></div>
                    <div class="metric-value-pro" id="month-pnl">-</div>
                </div>
                <div class="metric-subtext-pro" id="month-pnl-percent">-</div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="metric-card-pro" style="border-bottom: 3px solid #0dcaf0;">
                <i class="fas fa-chart-pie metric-bg-icon"></i>
                <div>
                    <div class="metric-label-pro"><?= $lang['win_rate'] ?></div>
                    <div class="metric-value-pro" id="month-winrate">-</div>
                </div>
                <div class="progress mt-3" style="height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px;">
                    <div id="month-winrate-bar" class="progress-bar bg-info" style="width: 0%; border-radius: 3px;"></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="metric-card-pro" style="border-bottom: 3px solid #ffc107;">
                <i class="fas fa-balance-scale metric-bg-icon"></i>
                <div>
                    <div class="metric-label-pro"><?= $lang['avg_rr'] ?></div>
                    <div class="metric-value-pro" id="month-rr">-</div>
                </div>
                <div class="metric-subtext-pro text-muted"><?= $lang['risk_reward_ratio'] ?></div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="metric-card-pro" style="border-bottom: 3px solid #6c757d;">
                <i class="fas fa-list-ol metric-bg-icon"></i>
                <div>
                    <div class="metric-label-pro"><?= $lang['total_trades_count'] ?></div>
                    <div class="metric-value-pro" id="month-trades-count">-</div>
                </div>
                <div class="metric-subtext-pro d-flex gap-2 mt-2" id="month-trades-breakdown">
                    </div>
            </div>
        </div>
    </div>
	
	<div class="mb-5">
        <h4 class="mb-3 fw-bold text-white"><i class="fas fa-chess-board me-2"></i><?= $lang['plan_performance'] ?></h4>
        <div class="row" id="month-plans-container">
            <div class="col-12 text-muted"><?= $lang['loading_plans_data'] ?></div>
        </div>
    </div>

    <h4 class="mb-3 fw-bold text-white"><?= $lang['trade_history'] ?></h4>
    
    <div class="row" id="month-trades-container">
        <div class="col-12 text-center text-muted py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <div class="mt-2"><?= $lang['loading_trades'] ?></div>
        </div>
    </div>
	
	<div class="mt-5 mb-5">
        <h4 class="mb-3 fw-bold text-white"><i class="fas fa-feather-alt me-2"></i><?= $lang['monthly_conclusion'] ?></h4>
        
        <div class="card glass-panel border-0 shadow-sm" style="border-radius: 12px;">
            <div class="card-body p-4">
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
</div>
</div>