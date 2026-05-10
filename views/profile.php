<?php
// views/profile.php
?>

<!-- Hidden inputs for JS -->
<input type="hidden" id="profile-display-username" value="">
<input type="hidden" id="profile-display-email" value="">
<input type="hidden" id="profile-join-date" value="">

<div class="fade-in" style="max-width: 900px; margin: 0 auto;">

    <!-- Page Header -->
    <div class="profile-page-header">
        <div class="profile-header-glow"></div>
        <div class="profile-avatar-wrap">
            <div class="profile-avatar-ring">
                <div class="profile-avatar-inner">
                    <i class="fas fa-user"></i>
                </div>
            </div>
            <div class="profile-avatar-status"></div>
        </div>
        <div class="profile-header-info">
            <h1 class="profile-username" id="profile-page-name"><?= $lang['loading'] ?></h1>
            <p class="profile-email" id="profile-page-email">...</p>
            <div class="profile-badge">
                <i class="fas fa-shield-alt me-1"></i>
                <?= $lang['trader'] ?? 'Trader' ?>
            </div>
        </div>
    </div>

    <!-- Stats Strip -->
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
    </div>

    <!-- Settings Cards Grid -->
    <div class="profile-cards-grid">

        <!-- Language Card -->
        <div class="profile-card glass-panel">
            <div class="profile-card-header">
                <div class="profile-card-icon" style="background: linear-gradient(135deg, #667eea, #764ba2)">
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
                    <label class="lang-option" id="lang-opt-ru">
                        <input type="radio" name="profile-lang" value="ru" style="display:none">
                        <span class="lang-flag">🇷🇺</span>
                        <span class="lang-name"><?= $lang['russian'] ?></span>
                        <i class="fas fa-check lang-check"></i>
                    </label>
                </div>
                <!-- hidden select for compatibility with existing JS -->
                <select id="profile-language-select" style="display:none">
                    <option value="en"><?= $lang['english'] ?></option>
                    <option value="ru"><?= $lang['russian'] ?></option>
                </select>
            </div>
        </div>

        <!-- Account Info Card -->
        <div class="profile-card glass-panel">
            <div class="profile-card-header">
                <div class="profile-card-icon" style="background: linear-gradient(135deg, #0ea5e9, #06b6d4)">
                    <i class="fas fa-id-card"></i>
                </div>
                <div>
                    <div class="profile-card-title"><?= $lang['account_info'] ?? 'Account Info' ?></div>
                    <div class="profile-card-subtitle"><?= $lang['your_credentials'] ?? 'Your credentials' ?></div>
                </div>
            </div>
            <div class="profile-card-body">
                <div class="profile-info-row">
                    <span class="profile-info-label"><i class="fas fa-user me-2 text-muted"></i><?= $lang['username'] ?? 'Username' ?></span>
                    <span class="profile-info-value" id="profile-info-name">—</span>
                </div>
                <div class="profile-info-row">
                    <span class="profile-info-label"><i class="fas fa-envelope me-2 text-muted"></i><?= $lang['email'] ?? 'Email' ?></span>
                    <span class="profile-info-value" id="profile-info-email">—</span>
                </div>
                <div class="profile-info-row">
                    <span class="profile-info-label"><i class="fas fa-clock me-2 text-muted"></i><?= $lang['member_since'] ?? 'Member since' ?></span>
                    <span class="profile-info-value" id="profile-info-date">—</span>
                </div>
            </div>
        </div>

        <!-- Danger Zone Card -->
        <div class="profile-card glass-panel profile-danger-card">
            <div class="profile-card-header">
                <div class="profile-card-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626)">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div>
                    <div class="profile-card-title"><?= $lang['danger_zone'] ?? 'Session' ?></div>
                    <div class="profile-card-subtitle"><?= $lang['session_management'] ?? 'Manage your session' ?></div>
                </div>
            </div>
            <div class="profile-card-body">
                <button id="profile-logout-btn" class="profile-logout-btn">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    <?= $lang['logout'] ?? 'Logout' ?>
                </button>
            </div>
        </div>

    </div>
</div>

<style>
/* =================== PROFILE PAGE =================== */
.profile-page-header {
    position: relative;
    display: flex;
    align-items: center;
    gap: 28px;
    padding: 36px 36px 32px;
    background: linear-gradient(135deg, rgba(99,102,241,0.12) 0%, rgba(168,85,247,0.08) 100%);
    border: 1px solid rgba(99,102,241,0.2);
    border-radius: 20px;
    margin-bottom: 20px;
    overflow: hidden;
}

.profile-header-glow {
    position: absolute;
    top: -60px;
    left: -60px;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(99,102,241,0.25) 0%, transparent 70%);
    pointer-events: none;
}

.profile-avatar-wrap {
    position: relative;
    flex-shrink: 0;
}

.profile-avatar-ring {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    padding: 3px;
    background: linear-gradient(135deg, #6366f1, #a855f7, #06b6d4);
    animation: spin-ring 8s linear infinite;
}

@keyframes spin-ring {
    from { filter: hue-rotate(0deg); }
    to { filter: hue-rotate(360deg); }
}

.profile-avatar-inner {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: var(--bg-card, #1a1a2e);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
    color: #6366f1;
}

.profile-avatar-status {
    position: absolute;
    bottom: 4px;
    right: 4px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #22c55e;
    border: 3px solid var(--bg-main, #121212);
    animation: pulse-status 2s ease-in-out infinite;
}

@keyframes pulse-status {
    0%, 100% { box-shadow: 0 0 0 0 rgba(34,197,94,0.4); }
    50% { box-shadow: 0 0 0 6px rgba(34,197,94,0); }
}

.profile-header-info { flex: 1; min-width: 0; }

.profile-username {
    font-size: 1.9rem;
    font-weight: 800;
    color: #fff;
    margin: 0 0 4px;
    background: linear-gradient(135deg, #fff 60%, #a855f7);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    line-height: 1.2;
}

.profile-email {
    color: var(--text-secondary);
    font-size: 0.95rem;
    margin: 0 0 12px;
}

.profile-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 14px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    background: linear-gradient(135deg, rgba(99,102,241,0.25), rgba(168,85,247,0.2));
    border: 1px solid rgba(99,102,241,0.35);
    color: #a5b4fc;
}

/* Stats Strip */
.profile-stats-strip {
    display: flex;
    align-items: center;
    gap: 0;
    background: var(--bg-card, #1a1a1a);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 16px;
    padding: 20px 28px;
    margin-bottom: 20px;
}

.profile-stat-item {
    display: flex;
    align-items: center;
    gap: 14px;
    flex: 1;
}

.profile-stat-icon {
    font-size: 1.4rem;
    width: 40px;
    text-align: center;
    flex-shrink: 0;
}

.profile-stat-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--text-secondary);
    font-weight: 600;
    margin-bottom: 3px;
}

.profile-stat-value {
    font-size: 1.15rem;
    font-weight: 700;
    color: #fff;
}

.profile-stat-divider {
    width: 1px;
    height: 44px;
    background: rgba(255,255,255,0.08);
    margin: 0 24px;
    flex-shrink: 0;
}

/* Cards Grid */
.profile-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 16px;
}

.profile-card {
    border-radius: 16px;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.profile-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.3);
}

.profile-card-header {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px 22px 14px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.profile-card-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: #fff;
    flex-shrink: 0;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}

.profile-card-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: #fff;
}

.profile-card-subtitle {
    font-size: 0.78rem;
    color: var(--text-secondary);
    margin-top: 2px;
}

.profile-card-body {
    padding: 20px 22px;
}

/* Language options */
.lang-options {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.lang-option {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 16px;
    border-radius: 12px;
    border: 1.5px solid rgba(255,255,255,0.07);
    background: rgba(255,255,255,0.03);
    cursor: pointer;
    transition: all 0.2s ease;
    user-select: none;
}

.lang-option:hover {
    border-color: rgba(99,102,241,0.4);
    background: rgba(99,102,241,0.08);
}

.lang-option.active {
    border-color: #6366f1;
    background: rgba(99,102,241,0.15);
}

.lang-flag { font-size: 1.4rem; line-height: 1; }
.lang-name { flex: 1; font-weight: 600; font-size: 0.92rem; color: #fff; }

.lang-check {
    color: #6366f1;
    opacity: 0;
    transition: opacity 0.2s;
    font-size: 0.85rem;
}

.lang-option.active .lang-check { opacity: 1; }

/* Info rows */
.profile-info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.profile-info-row:last-child { border-bottom: none; }

.profile-info-label {
    font-size: 0.85rem;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
}

.profile-info-value {
    font-size: 0.9rem;
    font-weight: 600;
    color: #fff;
    text-align: right;
    max-width: 55%;
    word-break: break-all;
}

/* Logout button */
.profile-logout-btn {
    width: 100%;
    padding: 12px 20px;
    border: 1.5px solid rgba(239,68,68,0.35);
    background: rgba(239,68,68,0.08);
    color: #f87171;
    border-radius: 12px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.profile-logout-btn:hover {
    background: rgba(239,68,68,0.18);
    border-color: rgba(239,68,68,0.6);
    color: #fca5a5;
    transform: translateY(-1px);
}

@media (max-width: 600px) {
    .profile-page-header { flex-direction: column; text-align: center; padding: 28px 20px; }
    .profile-stats-strip { flex-wrap: wrap; gap: 16px; }
    .profile-stat-divider { display: none; }
    .profile-stat-item { flex: 1 1 40%; }
    .profile-username { font-size: 1.5rem; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Wire up language card to hidden select + existing handler
    const langOptions = document.querySelectorAll('.lang-option');
    const hiddenSelect = document.getElementById('profile-language-select');

    langOptions.forEach(opt => {
        opt.addEventListener('click', function() {
            const val = this.querySelector('input[type="radio"]').value;
            hiddenSelect.value = val;
            hiddenSelect.dispatchEvent(new Event('change'));
        });
    });

    // Sync visual state of language UI
    function syncLangUI(lang) {
        document.querySelectorAll('.lang-option').forEach(o => o.classList.remove('active'));
        const target = document.getElementById('lang-opt-' + lang);
        if (target) target.classList.add('active');
    }

    // Override syncLanguageSelect to also update the visual lang buttons
    const _origSync = window.syncLanguageSelect;
    window.syncLanguageSelect = function(lang) {
        if (_origSync) _origSync(lang);
        syncLangUI(lang);
    };

    // Load user info directly and populate all profile elements
    fetch('api/api.php?action=get_user_info')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Header
                const nameEl = document.getElementById('profile-page-name');
                if (nameEl) nameEl.textContent = data.username || '—';

                const emailEl = document.getElementById('profile-page-email');
                if (emailEl) emailEl.textContent = data.email || '—';

                const dateEl = document.getElementById('profile-page-date');
                if (dateEl) dateEl.textContent = data.created_at || '—';

                // Info card rows
                const infoName = document.getElementById('profile-info-name');
                if (infoName) infoName.textContent = data.username || '—';

                const infoEmail = document.getElementById('profile-info-email');
                if (infoEmail) infoEmail.textContent = data.email || '—';

                const infoDate = document.getElementById('profile-info-date');
                if (infoDate) infoDate.textContent = data.created_at || '—';

                // Sync lang UI
                if (data.language) syncLangUI(data.language);
            }
        })
        .catch(() => {});

    // Logout button
    document.getElementById('profile-logout-btn')?.addEventListener('click', logout);

    // Load stats from dashboard API
    fetch('api/api.php?action=get_dashboard_metrics')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const m = data.data || {};

                // total_trades
                const total = m.total_trades ?? null;
                document.getElementById('profile-stat-trades').textContent = total != null ? total : '—';

                // win_rate (not winrate)
                const wr = m.win_rate != null ? parseFloat(m.win_rate).toFixed(1) + '%' : null;
                document.getElementById('profile-stat-winrate').textContent = wr ?? '—';

                // total_pnl (not net_pnl)
                const pnlRaw = m.total_pnl ?? null;
                const pnlEl = document.getElementById('profile-stat-pnl');
                if (pnlRaw != null) {
                    const pnlVal = parseFloat(pnlRaw);
                    pnlEl.textContent = (pnlVal >= 0 ? '+' : '') + pnlVal.toFixed(2) + '$';
                    pnlEl.style.color = pnlVal >= 0 ? 'var(--accent-green, #22c55e)' : 'var(--accent-red, #ef4444)';
                } else {
                    pnlEl.textContent = '—';
                }
            }
        })
        .catch(() => {});
});
</script>