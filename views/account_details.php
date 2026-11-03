<div class="fade-in">
    <input type="hidden" id="current-account-view-id" value="<?php echo $_GET['id'] ?? ''; ?>">

    <div class="d-flex justify-content-between align-items-center mb-4 responsive-header">
        <div class="d-flex align-items-center gap-3">
            <a href="index.php?view=accounts" class="btn-icon-back">
                <div class="d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: rgba(255,255,255,0.05); border-radius: 10px; transition: 0.2s;">
                    <i class="fas fa-arrow-left text-secondary"></i>
                </div>
            </a>
            <div>
                <h2 class="page-title m-0" id="ad-name"><span class="skeleton" style="height: 32px; width: 180px; display: inline-block;"></span></h2>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <span id="ad-type" class="badge-neutral" style="padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; letter-spacing: 0.5px;">TYPE</span>
                    <span id="ad-status" class="text-muted small"><?= $lang['active'] ?></span>
                </div>
            </div>
        </div>
        
        <div class="d-flex gap-2">
            <button id="btn-edit-account" class="btn btn-secondary">
                <i class="fas fa-edit me-2"></i> <?= $lang['edit'] ?>
            </button>
            <button id="btn-delete-account" class="btn btn-danger" onclick="deleteAccount(document.getElementById('current-account-view-id').value)">
                <i class="fas fa-trash-alt me-2"></i> <?= $lang['delete'] ?>
            </button>
            <a href="index.php?view=trade_create&account_id=<?php echo htmlspecialchars($_GET['id'] ?? ''); ?>" id="btn-add-trade-account" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i> <?= $lang['trade'] ?>
            </a>
        </div>
    </div>

    <div class="account-tabs-nav">
        <button class="tab-btn active" data-tab="overview"><?= $lang['overview'] ?></button>
        <button class="tab-btn" data-tab="trades"><?= $lang['trades'] ?></button>
        <button class="tab-btn" data-tab="payouts"><?= $lang['payouts'] ?></button>
    </div>

    <div id="tab-overview" class="tab-content active">
        
        <div class="glass-panel mb-4" style="padding: 24px;">
            <div class="d-flex justify-content-between align-items-end mb-3">
                <div>
                    <div class="metric-label-pro"><?= $lang['current_balance'] ?></div>
                    <div id="ad-balance" style="font-size: 2.5rem; font-weight: 700; color: var(--text-main); line-height: 1.1;">$0.00</div>
                </div>
                <div class="text-end">
                    <div id="ad-profit-abs" class="text-profit" style="font-size: 1.4rem; font-weight: 600;">+0.00$</div>
                    <div id="ad-profit-pct" class="text-secondary" style="font-size: 0.95rem;">(0.00%)</div>
                </div>
            </div>
            
            <div id="ad-progress-container" style="margin-top: 15px;"></div>
        </div>

        <div class="pro-metrics-grid mb-4">
            <div class="glass-panel metric-card-pro" style="border-bottom: 3px solid var(--text-profit, #00d66f) !important;">
                <i class="fas fa-chart-area metric-bg-icon"></i>
                <div>
                    <div class="metric-label-pro"><?= $lang['total_pnl'] ?></div>
                    <div class="metric-value-pro" id="ad-pnl-value">0.00$</div>
                </div>
            </div>

            <div class="glass-panel metric-card-pro" style="border-bottom: 3px solid var(--accent-blue, #2997ff) !important;">
                <i class="fas fa-trophy metric-bg-icon"></i>
                <div>
                    <div class="metric-label-pro"><?= $lang['winrate'] ?></div>
                    <div class="metric-value-pro" id="ad-winrate">0%</div>
                </div>
                <div class="mpa-progress-bg mt-3">
                    <div id="ad-winrate-bar" class="mpa-progress-fill" style="width:0%; background: var(--accent-blue, #2997ff);"></div>
                </div>
            </div>

            <div class="glass-panel metric-card-pro" style="border-bottom: 3px solid #ffc107 !important;">
                <i class="fas fa-balance-scale metric-bg-icon"></i>
                <div>
                    <div class="metric-label-pro"><?= $lang['average_rr'] ?></div>
                    <div class="metric-value-pro" id="ad-avg-rr">0R</div>
                </div>
                <div class="metric-subtext-pro text-muted"><?= $lang['risk_reward_ratio'] ?? 'Risk/Reward' ?></div>
            </div>

            <div class="glass-panel metric-card-pro" style="border-bottom: 3px solid var(--text-secondary, #6b7280) !important;">
                <i class="fas fa-list-ol metric-bg-icon"></i>
                <div>
                    <div class="metric-label-pro"><?= $lang['total_trades'] ?></div>
                    <div class="metric-value-pro" id="ad-total-trades">0</div>
                </div>
                <div class="metric-subtext-pro text-muted mt-2" id="ad-trades-breakdown">-</div>
            </div>
        </div>

        <div class="glass-panel mb-4" style="border-radius: var(--border-radius-lg, 14px); padding: 24px;">
            <h4 class="mb-3 fw-bold text-white"><i class="fas fa-chart-line me-2"></i><?= $lang['equity_curve'] ?></h4>
            
            <div class="chart-wrapper" style="height: 380px; width: 100%; position: relative;">
                <canvas id="accountEquityChart"></canvas>
            </div>
        </div>
    </div>

    <div id="tab-trades" class="tab-content">
        <div id="trades-list-container">
            <div class="skeleton-row">
                <div class="skeleton" style="height: 15px; width: 80px;"></div>
                <div class="skeleton" style="height: 15px; width: 60px;"></div>
                <div class="skeleton" style="height: 15px; width: 100px;"></div>
                <div class="skeleton" style="height: 25px; width: 60px; border-radius: 12px; margin-left: auto;"></div>
            </div>
            <div class="skeleton-row">
                <div class="skeleton" style="height: 15px; width: 80px;"></div>
                <div class="skeleton" style="height: 15px; width: 60px;"></div>
                <div class="skeleton" style="height: 15px; width: 100px;"></div>
                <div class="skeleton" style="height: 25px; width: 60px; border-radius: 12px; margin-left: auto;"></div>
            </div>
        </div>
    </div>

    <div id="tab-payouts" class="tab-content">
        <div class="d-flex justify-content-end mb-3">
             <button id="btn-add-account-payout" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-plus me-1"></i> <?= $lang['add_payout'] ?>
            </button>
        </div>
        <div id="account-payouts-list-container" class="payout-table-border">
             <div class="glass-panel p-3">
                <div class="d-flex justify-content-between align-items-center mb-3 p-2">
                    <div class="skeleton" style="height: 15px; width: 40%;"></div>
                    <div class="skeleton" style="height: 15px; width: 20%;"></div>
                </div>
                <div class="d-flex justify-content-between align-items-center p-2">
                    <div class="skeleton" style="height: 15px; width: 40%;"></div>
                    <div class="skeleton" style="height: 15px; width: 20%;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="payout-modal" class="modal" style="display: none;">
    <div class="modal-content glass-panel" style="max-width: 500px; width: 100%;">
        <span class="modal-close" onclick="closePayoutModal()">&times;</span>
        <h2 id="payout-modal-title" class="text-center mb-4"><?= $lang['add_payout'] ?></h2>
        
        <form id="payout-form">
            <input type="hidden" name="id" id="payout-id">
            
            <div class="form-group">
                <label class="form-label"><?= $lang['account'] ?></label>
                <select name="account_id" id="payout-account" class="select-field" required>
                    <option value=""><?= $lang['select'] ?></option>
                </select>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label"><?= $lang['amount'] ?></label>
                        <input type="number" name="amount" id="payout-amount" class="input-field" step="0.01" placeholder="0.00" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label"><?= $lang['payout_date'] ?></label>
                        <input type="date" name="payout_date" id="payout-date" class="input-field" required>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label"><?= $lang['status'] ?></label>
                <select name="confirmation_status" id="payout-status" class="select-field">
                    <option value="Requested">🕒 <?= $lang['requested'] ?></option>
                    <option value="Paid">✅ <?= $lang['paid'] ?></option>
                    <option value="Rejected">❌ <?= $lang['rejected'] ?></option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary w-100 mt-3" style="padding: 12px; font-size: 1rem;"><?= $lang['save'] ?></button>
        </form>
    </div>
</div>
