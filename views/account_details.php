<div class="fade-in">
    <input type="hidden" id="current-account-view-id" value="<?php echo $_GET['id'] ?? ''; ?>">

    <div class="d-flex justify-content-between align-items-start mb-4 responsive-header">
        <div>
            <h2 class="m-0" id="ad-name" style="font-weight: 700; font-size: 2rem; line-height: 1.2;"><?= $lang['loading'] ?></h2>
            <div class="mt-2">
                <span id="ad-type" class="badge-neutral" style="padding: 4px 10px; border-radius: 6px; font-size: 0.85rem; letter-spacing: 0.5px;">TYPE</span>
                <span id="ad-status" class="ms-2 text-muted" style="font-size: 0.85rem;"><?= $lang['active'] ?></span>
            </div>
        </div>
        
        <div class="d-flex gap-2">
            <a href="index.php?view=accounts" class="btn btn-outline">
                <i class="fas fa-arrow-left me-2"></i> <?= $lang['back'] ?>
            </a>
            <button id="btn-edit-account" class="btn btn-secondary">
                <i class="fas fa-edit me-2"></i> <?= $lang['edit'] ?>
            </button>
            <button id="btn-delete-account" class="btn btn-danger" onclick="deleteAccount(document.getElementById('current-account-view-id').value)">
                <i class="fas fa-trash-alt me-2"></i> <?= $lang['delete'] ?>
            </button>
            <button id="btn-add-trade-account" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i> <?= $lang['trade'] ?>
            </button>
        </div>
    </div>

    <div class="account-tabs-nav">
        <button class="tab-btn active" data-tab="overview"><?= $lang['overview'] ?></button>
        <button class="tab-btn" data-tab="trades"><?= $lang['trades'] ?></button>
        <button class="tab-btn" data-tab="payouts"><?= $lang['payouts'] ?></button>
    </div>

    <div id="tab-overview" class="tab-content active">
        
        <div class="glass-panel mb-4" style="padding: 30px;">
            <div class="d-flex justify-content-between align-items-end mb-3">
                <div>
                    <div class="text-secondary text-uppercase" style="font-size: 0.85rem; font-weight: 600; margin-bottom: 5px;"><?= $lang['current_balance'] ?></div>
                    <div id="ad-balance" style="font-size: 3rem; font-weight: 700; color: var(--text-main); line-height: 1;">$0.00</div>
                </div>
                <div class="text-end">
                    <div id="ad-profit-abs" class="text-profit" style="font-size: 1.5rem; font-weight: 600;">+0.00$</div>
                    <div id="ad-profit-pct" class="text-secondary" style="font-size: 1rem;">(0.00%)</div>
                </div>
            </div>
            
            <div id="ad-progress-container" style="margin-top: 20px;"></div>
        </div>

        <div class="dashboard-grid mb-4">
            <div class="metric-card glass-panel">
                <div class="metric-icon"><i class="fas fa-chart-line"></i></div>
                <div class="metric-content">
                    <div class="metric-label"><?= $lang['total_trades'] ?></div>
                    <div class="metric-value" id="ad-total-trades">0</div>
                    <div class="metric-subtext" id="ad-trades-breakdown">-</div>
                </div>
            </div>

            <div class="metric-card glass-panel">
                <div class="metric-icon"><i class="fas fa-trophy"></i></div>
                <div class="metric-content">
                    <div class="metric-label"><?= $lang['winrate'] ?></div>
                    <div class="metric-value" id="ad-winrate">0%</div>
                    <div class="metric-progress-bar">
                        <div id="ad-winrate-bar" style="width:0%; background: var(--accent-green); height:100%;"></div>
                    </div>
                </div>
            </div>

            <div class="metric-card glass-panel">
                <div class="metric-icon"><i class="fas fa-exchange-alt"></i></div>
                <div class="metric-content">
                    <div class="metric-label"><?= $lang['average_rr'] ?></div>
                    <div class="metric-value" id="ad-avg-rr">0R</div>
                </div>
            </div>
            
             <div class="metric-card glass-panel">
                <div class="metric-icon"><i class="fas fa-chart-area"></i></div>
                <div class="metric-content">
                    <div class="metric-label"><?= $lang['total_pnl'] ?></div>
                    <div class="metric-value" id="ad-pnl-value">0.00$</div>
                </div>
            </div>
        </div>

        <div class="card glass-panel border-0 shadow-sm mb-4" style="border-radius: 12px; padding: 25px;">
            <h3 class="m-0 mb-4" style="font-size: 1.1rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;"><?= $lang['equity_curve'] ?></h3>
            
            <div class="chart-wrapper" style="height: 400px; width: 100%; position: relative;">
                <canvas id="accountEquityChart"></canvas>
            </div>
        </div>
    </div>

    <div id="tab-trades" class="tab-content">
        <div id="trades-list-container">
            <div class="loading-spinner"><?= $lang['loading_trades'] ?></div>
        </div>
    </div>

    <div id="tab-payouts" class="tab-content">
        <div class="d-flex justify-content-end mb-3">
             <button id="btn-add-account-payout" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-plus me-1"></i> <?= $lang['add_payout'] ?>
            </button>
        </div>
        <div id="account-payouts-list-container" class="payout-table-border">
             <div class="glass-panel p-4 text-center text-muted">
                <div class="loading-spinner"><?= $lang['loading_payouts'] ?></div>
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