// assets/modules/init.js
// ==================================================
// ГЛАВНАЯ ТОЧКА ВХОДА — DOMContentLoaded
// ==================================================

document.addEventListener('DOMContentLoaded', async () => {
    // А. Инициализируем тултипы для уже существующих элементов
    document.querySelectorAll('[title]').forEach(makeTooltip);

    // Б. Включаем наблюдение за DOM
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    // В. MPA-контейнер (инициализация на странице MPA)
    if (document.getElementById('mpa-dynamic-container')) {
        initMPA();
    }

    // Г. QPA Details (инициализация на странице деталей квартала)
    initQPADetails();

    // --- Определяем текущий view ---
    const urlParams = new URLSearchParams(window.location.search);
    const view = urlParams.get('view');

    // --- Форма аккаунта (старый вариант через modal, если остался) ---
    const accForm = document.getElementById('account-form');
    if (accForm) {
        accForm.onsubmit = async (e) => {
            e.preventDefault();
            const data = {
                id: document.getElementById('acc-id')?.value,
                name: document.getElementById('acc-name').value,
                type: document.getElementById('acc-type').value,
                balance: document.getElementById('acc-balance').value,
                target_percent: document.getElementById('acc-target').value,
                max_drawdown_percent: document.getElementById('acc-dd').value
            };

            await fetch('api/api.php?action=save_account', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            if (typeof closeAccountModal === 'function') closeAccountModal();
            loadAccounts();
        };
    }

    // --- REGISTER FORM ---
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = document.getElementById('reg-username').value;
            const email = document.getElementById('reg-email').value;
            const password = document.getElementById('reg-password').value;

            try {
                const res = await fetch('api/api.php?action=register', {
                    method: 'POST',
                    body: JSON.stringify({ username, email, password })
                });
                const data = await res.json();
                if (data.success) {
                    showToast(window.lang['registration_successful'], 'success');
                    setTimeout(() => window.location.href = 'index.php?view=login', 1500);
                } else {
                    showToast(data.message || window.lang['registration_failed'], 'error');
                }
            } catch (err) {
                console.error(err);
                showToast(window.lang['network_error'], 'error');
            }
        });
    }

    // --- FORGOT PASSWORD FORM ---
    const forgotForm = document.getElementById('forgot-form');
    if (forgotForm) {
        forgotForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('forgot-email').value;
            const btn = forgotForm.querySelector('button');
            const originalBtnText = btn.textContent;

            btn.disabled = true;
            btn.textContent = window.lang['sending'];

            try {
                const res = await fetch('api/api.php?action=forgot_password', {
                    method: 'POST',
                    body: JSON.stringify({ email })
                });
                const data = await res.json();

                if (data.success) {
                    showToast(window.lang['link_generated'], 'success');

                    let path = window.location.pathname;
                    if (path.endsWith('index.php')) {
                        path = path.substring(0, path.length - 'index.php'.length);
                    }
                    if (!path.endsWith('/')) path += '/';

                    const fullLink = window.location.origin + path + data.debug_link;

                    forgotForm.innerHTML = `
                        <div class="text-center mb-4">
                            <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                            <h4 class="text-white">${window.lang['check_email']}</h4>
                            <p class="text-muted small">${window.lang['reset_link_sent']} <strong>${email}</strong></p>
                        </div>
                        <div class="p-3 mb-3 rounded" style="background: rgba(25, 135, 84, 0.1); border: 1px dashed #198754;">
                            <div class="text-success fw-bold small mb-2 text-uppercase">${window.lang['debug_simulation']}</div>
                            <div class="input-group mb-2">
                                <input type="text" class="form-control bg-dark text-white border-secondary form-control-sm"
                                       value="${fullLink}" id="debug-link-input" readonly>
                                <button class="btn btn-secondary btn-sm" type="button" onclick="
                                    const el = document.getElementById('debug-link-input');
                                    el.select();
                                    navigator.clipboard.writeText(el.value);
                                    this.innerHTML = '<i class=\\'fas fa-check\\'></i>';
                                " title="${window.lang['copy_clipboard']}"><i class="fas fa-copy"></i></button>
                            </div>
                            <a href="${fullLink}" class="btn btn-success btn-sm w-100">${window.lang['open_link']}</a>
                        </div>
                    `;
                } else {
                    showToast(data.message || window.lang['error'], 'error');
                    btn.disabled = false;
                    btn.textContent = originalBtnText;
                }
            } catch (err) {
                console.error(err);
                showToast(window.lang['network_error'], 'error');
                btn.disabled = false;
                btn.textContent = originalBtnText;
            }
        });
    }

    // --- RESET PASSWORD FORM ---
    const resetForm = document.getElementById('reset-form');
    if (resetForm) {
        resetForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const token = document.getElementById('reset-token').value;
            const password = document.getElementById('reset-password').value;

            try {
                const res = await fetch('api/api.php?action=reset_password', {
                    method: 'POST',
                    body: JSON.stringify({ token, password })
                });
                const data = await res.json();

                if (data.success) {
                    showToast(window.lang['password_changed'], 'success');
                    setTimeout(() => window.location.href = 'index.php?view=login', 2000);
                } else {
                    showToast(data.message || window.lang['error_changing_password'], 'error');
                }
            } catch (err) {
                showToast(window.lang['network_error'], 'error');
            }
        });
    }

    // --- Базовые обработчики ---
    document.getElementById('mobile-menu-toggle')?.addEventListener('click', toggleMenu);
    document.getElementById('login-form')?.addEventListener('submit', handleLoginSubmit);
    document.getElementById('logout-btn')?.addEventListener('click', logout);

    // --- Формы сущностей ---
    const planForm = document.getElementById('plan-form');
    if (planForm) {
        initPlanForm();
        planForm.addEventListener('submit', e => handleFormSubmit(e, 'save_plan', 'plan', 'plans'));
    }

    const tradeForm = document.getElementById('trade-form');
    if (tradeForm) {
        initTradeForm();
        tradeForm.addEventListener('submit', e => handleFormSubmit(e, 'save_trade', 'trade', 'journal'));
        const b = document.getElementById('add-trade-image-btn');
        if (b) b.onclick = () => addTradeImage();
    }

    const noteForm = document.getElementById('note-form');
    if (noteForm) {
        initNoteForm();
        noteForm.addEventListener('submit', e => handleFormSubmit(e, 'save_note', 'note', 'notes'));
    }

    // --- Роутинг по view ---
    if (view === 'profile') { loadSimpleProfile(); }

    if (view === 'plans') { loadPlans(); setupFiltersModal(loadPlans); }
    if (view === 'plan_details') { loadPlanDetails(); setTimeout(setupLightbox, 100); }
    if (view === 'journal') { loadTrades(); setupFiltersModal(loadTrades); }
    if (view === 'trade_details') { loadTradeDetails(); setTimeout(setupLightbox, 100); }
    if (view === 'notes') { loadNotes(); }
    if (view === 'note_details') { loadNoteDetails(); setTimeout(setupLightbox, 100); }

    if (view === 'accounts') {
        loadAccounts();
        loadPayouts();
    }
    if (view === 'account_create') { initAccountForm(); }

    if (view === 'data') { loadDataAnalysis(); }

    if (view === 'qpa') loadQPAList();
    if (view === 'qpa_details') loadQPADetails();

    if (view === 'mpa_details') {
        console.log("View is mpa_details, launching loadMPAMonthDetails...");
        loadMPAMonthDetails();
    }

    if (view === 'strategy') { loadStrategies(); }
    if (view === 'strategy_create') { initStrategyForm(); }
    if (view === 'strategy_details') { loadStrategyDetails(); setTimeout(setupLightbox, 100); }

    if (view === 'dashboard') {
        populateDateFilters();

        const yearSelect = document.getElementById('dashboard-year-select');
        const monthSelect = document.getElementById('dashboard-month-select');

        yearSelect.addEventListener('change', () => {
            if (yearSelect.value) {
                monthSelect.style.display = 'inline-block';
            } else {
                monthSelect.style.display = 'none';
                monthSelect.value = '';
            }
            loadDashboardMetrics();
        });

        monthSelect.addEventListener('change', loadDashboardMetrics);

        loadLookups().then(data => {
            if (data && data.accounts) {
                populateSelect('dashboard-account-select', data.accounts, 'name', 'id', null, 'All accounts');
            }
            loadDashboardMetrics();
        });

        document.getElementById('dashboard-account-select')?.addEventListener('change', loadDashboardMetrics);
    }

    if (view === 'account_details') {
        const accId = document.getElementById('current-account-view-id')?.value;
        if (accId) {
            loadAccountDetailsPage(accId);
            initAccountTabs(accId);
            loadLookups().then(data => {
                if (data && data.accounts) populateSelect('payout-account', data.accounts, 'name');
            });

            const btn = document.getElementById('btn-add-account-payout');
            if (btn) {
                btn.onclick = () => {
                    openPayoutModal();
                    const sel = document.getElementById('payout-account');
                    if (sel) sel.value = accId;
                };
            }
        }
    }

    // --- Форма выплат ---
    const payoutForm = document.getElementById('payout-form');
    if (payoutForm) {
        payoutForm.onsubmit = async (e) => {
            e.preventDefault();

            const btn = payoutForm.querySelector('button[type="submit"]');
            const oldText = btn.textContent;
            btn.disabled = true;
            btn.textContent = window.lang['saving'];

            const fd = new FormData(payoutForm);
            const data = Object.fromEntries(fd.entries());

            try {
                const response = await fetch('api/api.php?action=save_payout', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    closePayoutModal();
                    loadPayouts();
                    showToast(window.lang['payout_saved'], 'success');
                } else {
                    showToast(window.lang['saving_error'] + ': ' + result.message, 'error');
                }
            } catch (error) {
                console.error(error);
                showToast(window.lang['network_error'], 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = oldText;
            }
        };
    }

    // --- Закрытие modal выплат по клику вне ---
    window.onclick = function(event) {
        const modal = document.getElementById('payout-modal');
        if (event.target == modal) {
            closePayoutModal();
        }
    };

    if (typeof initLanguageSwitcher === 'function') {
        initLanguageSwitcher();
    }

    // --- Общие утилиты ---
    setupLightbox();
    loadUserInfo();
});
