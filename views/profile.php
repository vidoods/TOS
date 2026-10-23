<?php
// views/profile.php
?>

<input type="hidden" id="profile-display-username" value="">
<input type="hidden" id="profile-display-email" value="">
<input type="hidden" id="profile-join-date" value="">

<div class="fade-in" style="max-width: 900px; margin: 0 auto;">

    <div class="profile-tabs-nav">
        <button class="profile-tab-btn active" data-tab="overview"><?= $lang['overview'] ?? 'Overview' ?></button>
        <button class="profile-tab-btn" data-tab="settings"><?= $lang['settings'] ?? 'Settings' ?></button>
    </div>

    <div id="tab-overview" class="profile-tab-content active">
    <div class="profile-page-header" style="position: relative;">
        <div class="profile-header-glow"></div>
        <div class="profile-controls-wrap">
            <h3 style="align-items: center; display: flex; gap: 10px;">
                <i class="fas fa-coins"></i>
                <?= $lang['change_currency'] ?? 'Change currency' ?>
            </h3>
            <div class="profile-currency-selector glass-panel">
                <button class="currency-badge-btn" data-currency="USD" onclick="CurrencyManager.change('USD')" title="<?= $lang['change_currency_usd'] ?? 'Change currency to USD ($)' ?>">$</button>
                <button class="currency-badge-btn" data-currency="EUR" onclick="CurrencyManager.change('EUR')" title="<?= $lang['change_currency_eur'] ?? 'Change currency to EUR (€)' ?>">€</button>
                <button class="currency-badge-btn" data-currency="UAH" onclick="CurrencyManager.change('UAH')" title="<?= $lang['change_currency_uah'] ?? 'Change currency to UAH (₴)' ?>">₴</button>
                <button class="currency-badge-btn" data-currency="RUB" onclick="CurrencyManager.change('RUB')" title="<?= $lang['change_currency_rub'] ?? 'Change currency to RUB (₽)' ?>">₽</button>
            </div>
        </div>

        <div class="profile-avatar-wrap">
            <div class="profile-avatar-ring">
                <div class="profile-avatar-inner" onclick="document.getElementById('avatar-input').click()">
                    
                    <i class="fas fa-user" id="profile-avatar-icon"></i>
                    
                    <img id="profile-avatar-img" 
                         src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" 
                         class="profile-avatar-img" 
                         alt="">
                    
                    <div class="avatar-upload-overlay" style="border-radius: 50%;">
                        <i class="fas fa-camera mb-1"></i>
                        <span class="avatar-upload-overlay-txt">
                            <?= $lang['change'] ?? 'Change' ?>
                        </span>
                    </div>

                </div>
            </div>
            <div class="profile-avatar-status"></div>
            <input type="file" id="avatar-input" hidden accept="image/jpeg, image/png, image/webp">
        </div>
        <div class="profile-header-info">
            <h1 class="profile-username" id="profile-page-name"><span class="skeleton profile-page-name"></span></h1>
            <p class="profile-email" id="profile-page-email"><span class="skeleton profile-page-email"></span></p>
            <div class="profile-badge">
                <i class="fas fa-shield-alt me-1"></i>
                <span id="profile-page-role"><?= $lang['trader'] ?? 'Trader' ?></span>
            </div>
        </div>
    </div>

    <div class="profile-stats-strip">
        <div class="profile-stat-item">
            <i class="fas fa-calendar-check profile-stat-icon text-profit"></i>
            <div>
                <div class="profile-stat-label"><?= $lang['joined'] ?></div>
                <div class="profile-stat-value" id="profile-page-date">—</div>
            </div>
        </div>
        <div class="profile-stat-divider"></div>
        <div class="profile-stat-item">
            <i class="fas fa-chart-line profile-stat-icon" style="color: var(--accent-blue)"></i>
            <div>
                <div class="profile-stat-label"><?= $lang['total_trades'] ?></div>
                <div class="profile-stat-value" id="profile-stat-trades">—</div>
            </div>
        </div>
        <div class="profile-stat-divider"></div>
        <div class="profile-stat-item">
            <i class="fas fa-trophy profile-stat-icon" style="color: var(--accent-yellow, #f59e0b)"></i>
            <div>
                <div class="profile-stat-label"><?= $lang['winrate'] ?></div>
                <div class="profile-stat-value" id="profile-stat-winrate">—</div>
            </div>
        </div>
        <div class="profile-stat-divider"></div>
        <div class="profile-stat-item">
            <i class="fas fa-dollar-sign profile-stat-icon text-profit"></i>
            <div>
                <div class="profile-stat-label"><?= $lang['net_profit'] ?></div>
                <div class="profile-stat-value" id="profile-stat-pnl">—</div>
            </div>
        </div>
        <div class="profile-stat-divider"></div>
        <div class="profile-stat-item profile-acc-filter">
            <div class="profile-acc-top-row">
                <i class="fas fa-wallet profile-stat-icon" style="color: var(--text-secondary)"></i>
                <span class="profile-stat-label"><?= $lang['account'] ?? 'Account' ?></span>
            </div>
            <div class="profile-acc-select-wrapper">
                <select id="profile-account-select" class="profile-acc-select">
                    <option value=""><?= $lang['all_accounts'] ?? 'All accounts' ?></option>
                </select>
            </div>
        </div>
    </div>

    <div class="profile-cards-grid">

        <div class="profile-card glass-panel">
            <div class="profile-card-header">
                <div class="profile-card-icon icon-lang">
                    <i class="fas fa-globe"></i>
                </div>
                <div>
                    <div class="profile-card-title"><?= $lang['language'] ?></div>
                    <div class="profile-card-subtitle"><?= $lang['interface_language'] ?? 'Interface language' ?></div>
                </div>
            </div>
            <div class="profile-card-body">
                <div class="lang-options">
                    <label class="lang-option" id="lang-opt-en">
                        <input type="radio" name="profile-lang" value="en" style="display:none">
                        <span class="lang-flag">🇬🇧</span>
                        <span class="lang-name"><?= $lang['english'] ?></span>
                        <i class="fas fa-check lang-check"></i>
                    </label>
                    <label class="lang-option" id="lang-opt-ua">
                        <input type="radio" name="profile-lang" value="ua" style="display:none">
                        <span class="lang-flag">🇺🇦</span>
                        <span class="lang-name"><?= $lang['ukrainian'] ?></span>
                        <i class="fas fa-check lang-check"></i>
                    </label>
                    <label class="lang-option" id="lang-opt-ru">
                        <input type="radio" name="profile-lang" value="ru" style="display:none">
                        <span class="lang-flag">🇷🇺</span>
                        <span class="lang-name"><?= $lang['russian'] ?></span>
                        <i class="fas fa-check lang-check"></i>
                    </label>
                </div>
                <select id="profile-language-select" style="display:none">
                    <option value="en"><?= $lang['english'] ?></option>
                    <option value="ua"><?= $lang['ukrainian'] ?></option>
                    <option value="ru"><?= $lang['russian'] ?></option>
                </select>
            </div>
        </div>

        <div class="profile-card glass-panel">
            <div class="profile-card-header">
                <div class="profile-card-icon icon-acc">
                    <i class="fas fa-id-card"></i>
                </div>
                <div>
                    <div class="profile-card-title"><?= $lang['account_info'] ?? 'Account Info' ?></div>
                    <div class="profile-card-subtitle"><?= $lang['your_credentials'] ?? 'Your credentials' ?></div>
                </div>
            </div>
            <div class="profile-card-body">
                <div class="profile-info-row">
                    <span class="profile-info-label"><i
                            class="fas fa-user me-2 text-muted"></i><?= $lang['username'] ?? 'Username' ?></span>
                    <span class="profile-info-value" id="profile-info-name">—</span>
                </div>
                <div class="profile-info-row">
                    <span class="profile-info-label"><i
                            class="fas fa-envelope me-2 text-muted"></i><?= $lang['email'] ?? 'Email' ?></span>
                    <div class="profile-info-actions-wrap">
                        <span class="profile-info-value" id="profile-info-email">—</span>
                        <button type="button" class="profile-row-action-btn" onclick="openChangeEmailModal()" title="<?= $lang['change_email'] ?? 'Change Email' ?>">
                            <i class="fas fa-pen"></i>
                            <span><?= $lang['change'] ?? 'Change' ?></span>
                        </button>
                    </div>
                </div>
                <div class="profile-info-row">
                    <span class="profile-info-label"><i
                            class="fas fa-key me-2 text-muted"></i><?= $lang['password'] ?? 'Password' ?></span>
                    <div class="profile-info-actions-wrap">
                        <span class="profile-info-value">••••••••</span>
                        <button type="button" class="profile-row-action-btn" onclick="openChangePasswordModal()" title="<?= $lang['change_password'] ?? 'Change Password' ?>">
                            <i class="fas fa-pen"></i>
                            <span><?= $lang['change'] ?? 'Change' ?></span>
                        </button>
                    </div>
                </div>
                <div class="profile-info-row">
                    <span class="profile-info-label"><i
                            class="fas fa-clock me-2 text-muted"></i><?= $lang['member_since'] ?? 'Member since' ?></span>
                    <span class="profile-info-value" id="profile-info-date">—</span>
                </div>
            </div>
        </div>

    </div>
</div>
<div id="tab-settings" class="profile-tab-content">
    <div class="profile-cards-grid">

            <div class="profile-card glass-panel">
                <div class="profile-card-header">
                    <div class="profile-card-icon icon-tf">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="profile-card-title"><?= $lang['timeframe'] ?? 'Timeframe' ?></div>
                </div>
                <div class="profile-card-body">
                    <div class="setting-input-group">
                        <input type="text" id="new-tf-name" class="profile-input" placeholder="<?php echo $lang['profile_example_timeframe'] ?? 'e.g. M15'; ?>">
                        <button class="btn-add" onclick="addSetting('timeframe', 'new-tf-name')">+</button>
                    </div>
                    <ul id="list-timeframes" class="settings-list"></ul>
                </div>
            </div>

            <div class="profile-card glass-panel">
                <div class="profile-card-header">
                    <div class="profile-card-icon icon-style">
                        <i class="fas fa-paint-brush"></i>
                    </div>
                    <div class="profile-card-title"><?= $lang['trading_style'] ?? 'Style' ?></div>
                </div>
                <div class="profile-card-body">
                    <div class="setting-input-group">
                        <input type="text" id="new-style-name" class="profile-input" placeholder="<?= $lang['example_strategy'] ?? 'e.g. Scalping' ?>">
                        <button class="btn-add" onclick="addSetting('style', 'new-style-name')">+</button>
                    </div>
                    <ul id="list-styles" class="settings-list"></ul>
                </div>
            </div>
            <div class="profile-card glass-panel">
                <div class="profile-card-header">
                    <div class="profile-card-icon icon-model">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div class="profile-card-title"><?= $lang['entry_model'] ?? 'Entry Model' ?></div>
                </div>
                <div class="profile-card-body">
                    <div class="setting-input-group">
                        <input type="text" id="new-model-name" class="profile-input" placeholder="<?= $lang['example_model'] ?? 'e.g. FVG' ?>">
                        <button class="btn-add" onclick="addSetting('model', 'new-model-name')">+</button>
                    </div>
                    <ul id="list-models" class="settings-list"></ul>
                </div>
            </div>
    </div>

            <div class="profile-card glass-panel" style="grid-column: 1 / -1; margin-top: 15px;">
                <div class="profile-card-header">
                    <div class="profile-card-icon icon-pair">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="profile-card-title"><?= $lang['pair'] ?? 'Pair' ?></div>
                </div>
                <div class="profile-card-body">
                    <div class="setting-input-group pair-inputs">
                        <input type="text" id="new-pair-symbol" class="profile-input" placeholder="<?= $lang['example_pair_symbol'] ?? 'Ticker: BTCUSDT, EURUSD' ?>">
                        <input type="text" id="new-pair-provider" class="profile-input" placeholder="<?= $lang['example_pair_provider'] ?? 'Provider: BINANCE, OANDA' ?>">
                        <select id="new-pair-type" class="profile-input">
                        </select>
                        <button class="btn-add" onclick="addPair()">+</button>
                    </div>
                    <ul id="list-pairs" class="settings-list"></ul>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal: Change Email -->
<div id="modal-change-email" class="modal profile-modal" style="display: none;">
    <div class="modal-content glass-panel" style="max-width: 480px; width: 92%; position: relative; border-radius: 20px; padding: 28px 26px;">
        <span class="modal-close" onclick="closeChangeEmailModal()" style="top: 14px; right: 18px; font-size: 28px; line-height: 1;">&times;</span>
        
        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 22px;">
            <div class="profile-card-icon icon-email" style="flex-shrink: 0;">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #fff;"><?= $lang['change_email'] ?? 'Change Email' ?></h3>
                <div style="font-size: 0.82rem; color: var(--text-secondary); margin-top: 2px;"><?= $lang['change_email_subtitle'] ?? 'Update your login email address' ?></div>
            </div>
        </div>

        <form id="profile-change-email-form" novalidate>
            <div class="profile-field">
                <label for="profile-new-email" class="profile-field-label"><?= $lang['new_email'] ?? 'New Email' ?></label>
                <div class="profile-input-wrap">
                    <i class="fas fa-envelope profile-input-icon"></i>
                    <input
                        type="email"
                        id="profile-new-email"
                        name="new_email"
                        class="profile-form-input"
                        placeholder="<?= $lang['enter_new_email'] ?? 'Enter new email address' ?>"
                        required
                        autocomplete="email"
                    >
                </div>
            </div>

            <div class="profile-field">
                <label for="profile-email-confirm-password" class="profile-field-label"><?= $lang['current_password'] ?? 'Current Password' ?></label>
                <div class="profile-input-wrap">
                    <i class="fas fa-lock profile-input-icon"></i>
                    <input
                        type="password"
                        id="profile-email-confirm-password"
                        name="password"
                        class="profile-form-input"
                        placeholder="<?= $lang['enter_current_password'] ?? 'Enter current password' ?>"
                        required
                        autocomplete="current-password"
                    >
                    <button type="button" class="profile-eye-btn" data-toggle-password="profile-email-confirm-password" onclick="togglePassword('profile-email-confirm-password', this)" aria-label="<?= $lang["toggle_password"] ?>">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div id="profile-email-msg" class="profile-form-msg" style="display: none;"></div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" onclick="closeChangeEmailModal()" style="flex: 1; border-radius: 10px; padding: 10px; font-weight: 600;">
                    <?= $lang['cancel'] ?? 'Cancel' ?>
                </button>
                <button type="submit" id="profile-email-submit-btn" class="profile-btn-submit" style="flex: 1.5; margin-top: 0;">
                    <i class="fas fa-save me-1"></i>
                    <span><?= $lang['update_email_btn'] ?? 'Update Email' ?></span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Change Password -->
<div id="modal-change-password" class="modal profile-modal" style="display: none;">
    <div class="modal-content glass-panel" style="max-width: 480px; width: 92%; position: relative; border-radius: 20px; padding: 28px 26px;">
        <span class="modal-close" onclick="closeChangePasswordModal()" style="top: 14px; right: 18px; font-size: 28px; line-height: 1;">&times;</span>
        
        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 22px;">
            <div class="profile-card-icon icon-password" style="flex-shrink: 0;">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #fff;"><?= $lang['change_password'] ?? 'Change Password' ?></h3>
                <div style="font-size: 0.82rem; color: var(--text-secondary); margin-top: 2px;"><?= $lang['change_password_subtitle'] ?? 'Update your account password' ?></div>
            </div>
        </div>

        <form id="profile-change-password-form" novalidate>
            <div class="profile-field">
                <label for="profile-current-password" class="profile-field-label"><?= $lang['current_password'] ?? 'Current Password' ?></label>
                <div class="profile-input-wrap">
                    <i class="fas fa-lock profile-input-icon"></i>
                    <input
                        type="password"
                        id="profile-current-password"
                        name="current_password"
                        class="profile-form-input"
                        placeholder="<?= $lang['enter_current_password'] ?? 'Enter current password' ?>"
                        required
                        autocomplete="current-password"
                    >
                    <button type="button" class="profile-eye-btn" data-toggle-password="profile-current-password" onclick="togglePassword('profile-current-password', this)" aria-label="<?= $lang["toggle_password"] ?>">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="profile-field">
                <label for="profile-new-password" class="profile-field-label"><?= $lang['new_password'] ?? 'New Password' ?></label>
                <div class="profile-input-wrap">
                    <i class="fas fa-key profile-input-icon"></i>
                    <input
                        type="password"
                        id="profile-new-password"
                        name="new_password"
                        class="profile-form-input"
                        placeholder="<?= $lang['enter_new_password'] ?? 'Enter new password' ?>"
                        required
                        autocomplete="new-password"
                    >
                    <button type="button" class="profile-eye-btn" data-toggle-password="profile-new-password" onclick="togglePassword('profile-new-password', this)" aria-label="<?= $lang["toggle_password"] ?>">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="password-strength-wrap" style="margin-top: 8px;">
                    <div class="strength-bar-track" style="height: 4px; background: rgba(255,255,255,0.08); border-radius: 4px; overflow: hidden;">
                        <div id="profile-password-strength-bar" class="strength-bar-fill" style="height: 100%; width: 0%; transition: all 0.3s ease;"></div>
                    </div>
                    <span class="strength-label" style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 4px; display: block;"></span>
                </div>
            </div>

            <div class="profile-field">
                <label for="profile-confirm-password" class="profile-field-label"><?= $lang['confirm_new_password'] ?? 'Confirm New Password' ?></label>
                <div class="profile-input-wrap">
                    <i class="fas fa-check-double profile-input-icon"></i>
                    <input
                        type="password"
                        id="profile-confirm-password"
                        name="confirm_password"
                        class="profile-form-input"
                        placeholder="<?= $lang['repeat_new_password'] ?? 'Repeat new password' ?>"
                        required
                        autocomplete="new-password"
                    >
                    <button type="button" class="profile-eye-btn" data-toggle-password="profile-confirm-password" onclick="togglePassword('profile-confirm-password', this)" aria-label="<?= $lang["toggle_password"] ?>">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div id="profile-password-msg" class="profile-form-msg" style="display: none;"></div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" onclick="closeChangePasswordModal()" style="flex: 1; border-radius: 10px; padding: 10px; font-weight: 600;">
                    <?= $lang['cancel'] ?? 'Cancel' ?>
                </button>
                <button type="submit" id="profile-password-submit-btn" class="profile-btn-submit" style="flex: 1.5; margin-top: 0;">
                    <i class="fas fa-save me-1"></i>
                    <span><?= $lang['update_password_btn'] ?? 'Update Password' ?></span>
                </button>
            </div>
        </form>
    </div>
</div>