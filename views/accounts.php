<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="m-0" style="font-weight: 600;"><?= $lang['accounts'] ?></h2>
        </div>
        
        <a href="index.php?view=account_create" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i> <?= $lang['add_account'] ?>
        </a>
    </div>

    <div id="accounts-grid" class="accounts-grid mb-5">
        <div class="row w-100">
            <div class="col-md-6 mb-4">
                <div class="skeleton-card">
                    <div class="d-flex justify-content-between mb-3">
                        <div class="skeleton" style="height: 20px; width: 40%;"></div>
                        <div class="skeleton" style="height: 20px; width: 20%;"></div>
                    </div>
                    <div class="skeleton" style="height: 15px; width: 70%; margin-bottom: 10px;"></div>
                    <div class="skeleton" style="height: 15px; width: 50%;"></div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="skeleton-card">
                    <div class="d-flex justify-content-between mb-3">
                        <div class="skeleton" style="height: 20px; width: 40%;"></div>
                        <div class="skeleton" style="height: 20px; width: 20%;"></div>
                    </div>
                    <div class="skeleton" style="height: 15px; width: 70%; margin-bottom: 10px;"></div>
                    <div class="skeleton" style="height: 15px; width: 50%;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="section-header d-flex justify-content-between align-items-center mb-3">
        <h4 class="text-uppercase text-secondary" style="font-size: 0.85rem; letter-spacing: 1px; font-weight: 700;"><?= $lang['payouts_history'] ?></h4>
        <button class="btn btn-sm btn-outline-secondary" onclick="openPayoutModal()">
            <i class="fas fa-plus me-1"></i> <?= $lang['new_payout'] ?>
        </button>
    </div>
    
    <div id="payouts-list-container" class="payout-table-border">
        <div class="glass-panel p-3">
            <div class="d-flex justify-content-between align-items-center mb-3 p-2">
                <div class="skeleton" style="height: 15px; width: 40%;"></div>
                <div class="skeleton" style="height: 15px; width: 20%;"></div>
            </div>
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