document.addEventListener('DOMContentLoaded', function () {
    const activeCurrency = CurrencyManager.currentCurrency || 'USD';
    const activeBtn = document.querySelector(`.currency-badge-btn[data-currency="${activeCurrency}"]`);
    if (activeBtn) {
        activeBtn.style.background = 'var(--accent-color, #00ff9d)';
        activeBtn.style.color = '#0d1117';
        activeBtn.style.boxShadow = '0 0 10px var(--accent-glow, rgba(0, 255, 157, 0.4))';
    }

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

        // Загружаем список доступных ТИПОВ из базы данных для селекта
        fetch('api/api.php?action=get_pair_types')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const select = document.getElementById('new-pair-type');
                    if (select) {
                        select.innerHTML = ''; 
                        data.types.forEach(type => {
                            const opt = document.createElement('option');
                            opt.value = type;
                            opt.textContent = type;
                            select.appendChild(opt);
                        });
                    }
                }
            })
            .catch(err => console.error("Error loading pair types:", err));
    }

    function renderList(elementId, items, type) {
        const container = document.getElementById(elementId);
        if (!container) return;
        container.innerHTML = '';
        items.forEach(item => {
            const li = document.createElement('li');
            li.className = 'settings-item';
            // ИСПРАВЛЕНИЕ УЯЗВИМОСТИ XSS: Безопасное экранирование названия настройки через escapeHTML
            li.innerHTML = `
                <span>${escapeHTML(item.name)}</span>
                <i class="fas fa-trash btn-del" onclick="deleteSetting('${type}', ${item.id})"></i>
            `;
            container.appendChild(li);
        });
    }

    function renderPairs(pairs) {
        const container = document.getElementById('list-pairs');
        if (!container) return;
        container.innerHTML = '';
        pairs.forEach(p => {
            const li = document.createElement('li');
            li.className = 'settings-item';
            // ИСПРАВЛЕНИЕ УЯЗВИМОСТИ XSS: Безопасное экранирование тикера и типа пары
            li.innerHTML = `
                <span>${escapeHTML(p.symbol)}:${escapeHTML(p.provider)} <small class='text-muted'>(${escapeHTML(p.type)})</small></span>
                <i class="fas fa-trash btn-del" onclick="deleteSetting('pair', ${p.id}, '${escapeHTML(p.symbol)}')"></i>
            `;
            container.appendChild(li);
        });
    }

    // Add Timeframe, Style, or Model
    window.addSetting = function(type, inputId) {
        const input = document.getElementById(inputId);
        if (!input) return;
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
        const symbolEl = document.getElementById('new-pair-symbol');
        const typeEl = document.getElementById('new-pair-type');
        const providerEl = document.getElementById('new-pair-provider');
        if (!symbolEl || !typeEl || !providerEl) return;

        const symbol = symbolEl.value.trim();
        const type = typeEl.value;
        const provider = providerEl.value.trim().toUpperCase();
        if (!symbol || !provider) return;

        const formData = new FormData();
        formData.append('action', 'add_user_setting');
        formData.append('type', 'pair');
        formData.append('symbol', symbol);
        formData.append('pair_type', type);
        formData.append('provider', provider);

        fetch('api/api.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    symbolEl.value = '';
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

    // --- LANGUAGE AND REGULAR PROFILE LOGIC ---
    
    const langOptions = document.querySelectorAll('.lang-option');
    const hiddenSelect = document.getElementById('profile-language-select');

    langOptions.forEach(opt => {
        opt.addEventListener('click', function () {
            const inputRadio = this.querySelector('input[type="radio"]');
            if (inputRadio) {
                const val = inputRadio.value;
                if (hiddenSelect) {
                    hiddenSelect.value = val;
                    hiddenSelect.dispatchEvent(new Event('change'));
                }
            }
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
                if (sel) {
                    data.data.forEach(acc => {
                        const opt = document.createElement('option');
                        opt.value = acc.id;
                        opt.textContent = acc.name;
                        sel.appendChild(opt);
                    });
                }
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
                        // ИСПРАВЛЕНИЕ: Интегрировали CurrencyManager.format() для вывода чистого профита в профиле
                        pnlEl.textContent = CurrencyManager.format(pnlVal);
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

    // --- PROFILE SECURITY MODALS (EMAIL & PASSWORD) ---

    window.openChangeEmailModal = function () {
        const modal = document.getElementById('modal-change-email');
        if (!modal) return;
        const form = document.getElementById('profile-change-email-form');
        if (form) form.reset();
        showProfileFormMessage(document.getElementById('profile-email-msg'), '');
        modal.style.display = 'flex';
        setTimeout(() => {
            document.getElementById('profile-new-email')?.focus();
        }, 120);
    };

    window.closeChangeEmailModal = function () {
        const modal = document.getElementById('modal-change-email');
        if (modal) modal.style.display = 'none';
    };

    window.openChangePasswordModal = function () {
        const modal = document.getElementById('modal-change-password');
        if (!modal) return;
        const form = document.getElementById('profile-change-password-form');
        if (form) form.reset();
        const bar = document.getElementById('profile-password-strength-bar');
        if (bar) bar.style.width = '0%';
        const label = bar?.closest('.password-strength-wrap')?.querySelector('.strength-label');
        if (label) label.textContent = '';
        showProfileFormMessage(document.getElementById('profile-password-msg'), '');
        modal.style.display = 'flex';
        setTimeout(() => {
            document.getElementById('profile-current-password')?.focus();
        }, 120);
    };

    window.closeChangePasswordModal = function () {
        const modal = document.getElementById('modal-change-password');
        if (modal) modal.style.display = 'none';
    };

    // Close modals on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeChangeEmailModal();
            closeChangePasswordModal();
        }
    });

    // Close modals on backdrop click
    document.getElementById('modal-change-email')?.addEventListener('click', function (e) {
        if (e.target === this) closeChangeEmailModal();
    });
    document.getElementById('modal-change-password')?.addEventListener('click', function (e) {
        if (e.target === this) closeChangePasswordModal();
    });

    // Ensure password toggle buttons on profile page are initialized
    if (typeof initPasswordToggles === 'function') {
        initPasswordToggles();
    }

    // Password strength indicator for profile
    if (typeof initStrengthIndicator === 'function') {
        initStrengthIndicator('profile-new-password', 'profile-password-strength-bar');
    }

    function showProfileFormMessage(el, message, type = 'error') {
        if (!el) return;
        if (!message) {
            el.style.display = 'none';
            el.innerHTML = '';
            return;
        }
        el.className = `profile-form-msg profile-form-msg--${type}`;
        el.innerHTML = type === 'error'
            ? `<i class="fas fa-exclamation-circle"></i> <span>${escapeHTML(message)}</span>`
            : `<i class="fas fa-check-circle"></i> <span>${escapeHTML(message)}</span>`;
        el.style.display = 'flex';
    }

    // Email change handler
    const emailForm = document.getElementById('profile-change-email-form');
    if (emailForm) {
        emailForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const msgEl = document.getElementById('profile-email-msg');
            const submitBtn = document.getElementById('profile-email-submit-btn');
            showProfileFormMessage(msgEl, '');

            const newEmail = (document.getElementById('profile-new-email')?.value || '').trim();
            const password = document.getElementById('profile-email-confirm-password')?.value || '';

            if (!newEmail) {
                showProfileFormMessage(msgEl, window.lang?.enter_new_email || 'Please enter a new email address.');
                return;
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(newEmail)) {
                showProfileFormMessage(msgEl, window.lang?.invalid_email || 'Please enter a valid email address.');
                return;
            }

            if (!password) {
                showProfileFormMessage(msgEl, window.lang?.enter_current_password || 'Please enter your current password.');
                return;
            }

            if (typeof setButtonLoading === 'function') {
                setButtonLoading(submitBtn, window.lang?.updating || 'Updating...');
            }

            const formData = new FormData();
            formData.append('action', 'change_email');
            formData.append('new_email', newEmail);
            formData.append('password', password);

            fetch('api/api.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        const updatedEmail = res.new_email || newEmail;
                        // Update UI
                        const pageEmail = document.getElementById('profile-page-email');
                        if (pageEmail) pageEmail.textContent = updatedEmail;
                        const infoEmail = document.getElementById('profile-info-email');
                        if (infoEmail) infoEmail.textContent = updatedEmail;

                        emailForm.reset();
                        const successText = res.message || window.lang?.email_updated_success || 'Email updated successfully!';
                        showProfileFormMessage(msgEl, successText, 'success');
                        if (typeof showToast === 'function') {
                            showToast(successText, 'success');
                        }
                        setTimeout(() => {
                            closeChangeEmailModal();
                        }, 1200);
                    } else {
                        const errorText = res.message || window.lang?.error_changing_email || 'Error updating email';
                        showProfileFormMessage(msgEl, errorText, 'error');
                        if (typeof showToast === 'function') {
                            showToast(errorText, 'error');
                        }
                    }
                })
                .catch(err => {
                    console.error("Change email error:", err);
                    showProfileFormMessage(msgEl, window.lang?.network_error_try_later || 'Network error. Please try again later.', 'error');
                })
                .finally(() => {
                    if (typeof resetButton === 'function') {
                        resetButton(submitBtn);
                    }
                });
        });
    }

    // Password change handler
    const passwordForm = document.getElementById('profile-change-password-form');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const msgEl = document.getElementById('profile-password-msg');
            const submitBtn = document.getElementById('profile-password-submit-btn');
            showProfileFormMessage(msgEl, '');

            const currentPassword = document.getElementById('profile-current-password')?.value || '';
            const newPassword = document.getElementById('profile-new-password')?.value || '';
            const confirmPassword = document.getElementById('profile-confirm-password')?.value || '';

            if (!currentPassword) {
                showProfileFormMessage(msgEl, window.lang?.enter_current_password || 'Please enter current password.');
                return;
            }

            if (!newPassword) {
                showProfileFormMessage(msgEl, window.lang?.enter_new_password || 'Please enter new password.');
                return;
            }

            if (newPassword.length < 8) {
                showProfileFormMessage(msgEl, window.lang?.password_min_length || 'Password must be at least 8 characters.');
                return;
            }

            if (!/[A-Z]/.test(newPassword)) {
                showProfileFormMessage(msgEl, 'Пароль должен содержать хотя бы одну заглавную букву (A-Z).');
                return;
            }

            if (!/[0-9]/.test(newPassword)) {
                showProfileFormMessage(msgEl, 'Пароль должен содержать хотя бы одну цифру (0-9).');
                return;
            }

            if (newPassword !== confirmPassword) {
                showProfileFormMessage(msgEl, window.lang?.passwords_dont_match || 'New passwords do not match.');
                return;
            }

            if (typeof setButtonLoading === 'function') {
                setButtonLoading(submitBtn, window.lang?.updating || 'Updating...');
            }

            const formData = new FormData();
            formData.append('action', 'change_password');
            formData.append('current_password', currentPassword);
            formData.append('new_password', newPassword);
            formData.append('confirm_password', confirmPassword);

            fetch('api/api.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        passwordForm.reset();
                        // Reset strength bar
                        const bar = document.getElementById('profile-password-strength-bar');
                        if (bar) bar.style.width = '0%';
                        const label = bar?.closest('.password-strength-wrap')?.querySelector('.strength-label');
                        if (label) label.textContent = '';

                        const successText = res.message || window.lang?.password_updated_success || 'Password updated successfully!';
                        showProfileFormMessage(msgEl, successText, 'success');
                        if (typeof showToast === 'function') {
                            showToast(successText, 'success');
                        }
                        setTimeout(() => {
                            closeChangePasswordModal();
                        }, 1200);
                    } else {
                        const errorText = res.message || window.lang?.error_changing_password || 'Error updating password';
                        showProfileFormMessage(msgEl, errorText, 'error');
                        if (typeof showToast === 'function') {
                            showToast(errorText, 'error');
                        }
                    }
                })
                .catch(err => {
                    console.error("Change password error:", err);
                    showProfileFormMessage(msgEl, window.lang?.network_error_try_later || 'Network error. Please try again later.', 'error');
                })
                .finally(() => {
                    if (typeof resetButton === 'function') {
                        resetButton(submitBtn);
                    }
                });
        });
    }
});