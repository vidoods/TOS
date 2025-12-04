<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="m-0" style="font-weight: 600;">Торговые Счета</h2>
            <p class="text-muted m-0">Управление капиталом и статистика</p>
        </div>
        
        <a href="index.php?view=account_create" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i> Добавить Счет
        </a>
    </div>

    <div class="section-header d-flex justify-content-between align-items-center mb-3">
        <h4 class="text-uppercase text-secondary" style="font-size: 0.85rem; letter-spacing: 1px; font-weight: 700;">Accounts</h4>
    </div>

    <div id="accounts-grid" class="accounts-grid mb-5">
        <div class="loading-spinner">Загрузка счетов...</div>
    </div>

    <div class="section-header d-flex justify-content-between align-items-center mb-3">
        <h4 class="text-uppercase text-secondary" style="font-size: 0.85rem; letter-spacing: 1px; font-weight: 700;">Payouts History</h4>
        <button class="btn btn-sm btn-outline-secondary" onclick="openPayoutModal()">
            <i class="fas fa-plus me-1"></i> New Payout
        </button>
    </div>
    
    <div id="payouts-list-container">
        <div class="glass-panel p-4 text-center text-muted">
            <div class="loading-spinner">Загрузка выплат...</div>
        </div>
    </div>
</div>

<div id="payout-modal" class="modal" style="display: none;">
    <div class="modal-content glass-panel" style="max-width: 500px; width: 100%;">
        <span class="modal-close" onclick="closePayoutModal()">&times;</span>
        <h2 id="payout-modal-title" class="text-center mb-4">Добавить Выплату</h2>
        
        <form id="payout-form">
            <input type="hidden" name="id" id="payout-id">
            
            <div class="form-group">
                <label class="form-label">Счет (Account)</label>
                <select name="account_id" id="payout-account" class="select-field" required>
                    <option value="">Select</option>
                </select>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Сумма ($)</label>
                        <input type="number" name="amount" id="payout-amount" class="input-field" step="0.01" placeholder="0.00" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Дата выплаты</label>
                        <input type="date" name="payout_date" id="payout-date" class="input-field" required>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Статус</label>
                <select name="confirmation_status" id="payout-status" class="select-field">
                    <option value="Requested">🕒 Requested (Запрошено)</option>
                    <option value="Paid">✅ Paid (Получено)</option>
                    <option value="Rejected">❌ Rejected (Отклонено)</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary w-100 mt-3" style="padding: 12px; font-size: 1rem;">Сохранить</button>
        </form>
    </div>
</div>