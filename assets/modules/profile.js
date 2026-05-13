document.addEventListener('DOMContentLoaded', function () {
    // --- TAB SWITCHING LOGIC ---
    const tabBtns = document.querySelectorAll('.profile-tab-btn');
    const tabContents = document.querySelectorAll('.profile-tab-content');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetTab = btn.dataset.tab;

            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));

            btn.classList.add('active');
            document.getElementById(`tab-${targetTab}`).classList.add('active');

            if (targetTab === 'settings') loadUserSettings();
        });
    });

    // --- SETTINGS CRUD LOGIC ---

    function loadUserSettings() {
        fetch('api/api.php?action=get_user_settings')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    renderList('list-timeframes', data.timeframes, 'timeframe');
                    renderList('list-styles', data.styles, 'style');
                    if (data.models) {
                        renderList('list-models', data.models, 'model');
                    }
                    renderPairs(data.pairs);
                }
            });
    // 2. НОВОЕ: Загружаем список доступных ТИПОВ из базы данных для селекта
    fetch('api/api.php?action=get_pair_types')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('new-pair-type');
                // Очищаем старые опции, кроме первой (если нужно) или просто пересоздаем
                select.innerHTML = ''; 
                data.types.forEach(type => {
                    const opt = document.createElement('option');
                    opt.value = type;
                    opt.textContent = type;
                    select.appendChild(opt);
                });
            }
        })
        .catch(err => console.error("Error loading pair types:", err));
    }

    function renderList(elementId, items, type) {
        const container = document.getElementById(elementId);
        container.innerHTML = '';
        items.forEach(item => {
            const li = document.createElement('li');
            li.className = 'settings-item';
            li.innerHTML = `
                <span>${item.name}</span>
                <i class="fas fa-trash btn-del" onclick="deleteSetting('${type}', ${item.id})"></i>
            `;
            container.appendChild(li);
        });
    }

    function renderPairs(pairs) {
        const container = document.getElementById('list-pairs');
        container.innerHTML = '';
        pairs.forEach(p => {
            const li = document.createElement('li');
            li.className = 'settings-item';
            li.innerHTML = `
                <span>${p.symbol} <small class='text-muted'>(${p.type})</small></span>
                <i class="fas fa-trash btn-del" onclick="deleteSetting('pair', ${p.id}, '${p.symbol}')"></i>
            `;
            container.appendChild(li);
        });
    }

    // Add Timeframe or Style
    window.addSetting = function(type, inputId) {
        const input = document.getElementById(inputId);
        const val = input.value.trim();
        if (!val) return;

        const formData = new FormData();
        formData.append('action', 'add_user_setting');
        formData.append('type', type);
        formData.append('name', val);

        fetch('api/api.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    input.value = '';
                    loadUserSettings();
                }
            });
    };

    // Add Pair
    window.addPair = function() {
        const symbol = document.getElementById('new-pair-symbol').value.trim();
        const type = document.getElementById('new-pair-type').value;
        if (!symbol) return;

        const formData = new FormData();
        formData.append('action', 'add_user_setting');
        formData.append('type', 'pair');
        formData.append('symbol', symbol);
        formData.append('pair_type', type);

        fetch('api/api.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    document.getElementById('new-pair-symbol').value = '';
                    loadUserSettings();
                }
            });
    };

    // Delete Setting
    window.deleteSetting = function(type, id, extra = '') {
        const formData = new FormData();
        formData.append('action', 'delete_user_setting');
        formData.append('type', type);
        formData.append('id', id);
        if (extra) formData.append('extra', extra);

        fetch('api/api.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) loadUserSettings();
            });
    };
});


    document.addEventListener('DOMContentLoaded', function () {
        // Wire up language card to hidden select + existing handler
        const langOptions = document.querySelectorAll('.lang-option');
        const hiddenSelect = document.getElementById('profile-language-select');

        langOptions.forEach(opt => {
            opt.addEventListener('click', function () {
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
        window.syncLanguageSelect = function (lang) {
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

                    const roleEl = document.getElementById('profile-page-role');
                    if (roleEl && data.role) roleEl.textContent = data.role;

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
            .catch(() => { });

        // Logout button
        document.getElementById('profile-logout-btn')?.addEventListener('click', logout);

        // Populate accounts dropdown
        fetch('api/api.php?action=get_accounts_data')
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    const sel = document.getElementById('profile-account-select');
                    data.data.forEach(acc => {
                        const opt = document.createElement('option');
                        opt.value = acc.id;
                        opt.textContent = acc.name;
                        sel.appendChild(opt);
                    });
                }
            })
            .catch(() => { });

        // Load stats — optionally filtered by account
        function loadProfileStats(accountId) {
            const url = accountId
                ? `api/api.php?action=get_dashboard_metrics&account_id=${accountId}`
                : 'api/api.php?action=get_dashboard_metrics';

            ['profile-stat-trades', 'profile-stat-winrate', 'profile-stat-pnl'].forEach(id => {
                const el = document.getElementById(id);
                if (el) { el.textContent = '…'; el.style.color = ''; }
            });

            fetch(url)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const m = data.data || {};

                        const total = m.total_trades ?? null;
                        document.getElementById('profile-stat-trades').textContent = total != null ? total : '—';

                        const wr = m.win_rate != null ? parseFloat(m.win_rate).toFixed(1) + '%' : null;
                        document.getElementById('profile-stat-winrate').textContent = wr ?? '—';

                        const pnlRaw = m.total_pnl ?? null;
                        const pnlEl = document.getElementById('profile-stat-pnl');
                        if (pnlRaw != null) {
                            const pnlVal = parseFloat(pnlRaw);
                            pnlEl.textContent = (pnlVal >= 0 ? '+' : '') + pnlVal.toFixed(2) + '$';
                            pnlEl.style.color = pnlVal >= 0 ? 'var(--accent-green, #22c55e)' : 'var(--accent-red, #ef4444)';
                        } else {
                            pnlEl.textContent = '—';
                            pnlEl.style.color = '';
                        }
                    }
                })
                .catch(() => { });
        }

        // Wire account selector to reload stats
        const accSelect = document.getElementById('profile-account-select');
        accSelect?.addEventListener('change', () => loadProfileStats(accSelect.value || null));

        // Initial load
        loadProfileStats(null);
    });