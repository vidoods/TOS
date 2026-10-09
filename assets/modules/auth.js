// assets/modules/auth.js
// ==================================================
// ФУНКЦИИ АВТОРИЗАЦИИ
// ==================================================

const API_URL = 'api/api.php';

// -------------------------------------------------------
// Вспомогательные функции
// -------------------------------------------------------

/** Показывает/скрывает пароль и переключает иконку */
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    const icon = btn.querySelector('i');
    if (icon) {
        icon.className = isHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
    }
}

/** Инициализирует кнопки показа/скрытия пароля */
function initPasswordToggles() {
    document.querySelectorAll('[data-toggle-password]').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-toggle-password');
            togglePassword(targetId, btn);
        });
    });
}

/** Рассчитывает силу пароля (0–4) */
function calcPasswordStrength(password) {
    let score = 0;
    if (password.length >= 8)  score++;
    if (password.length >= 12) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;
    return Math.min(score, 4);
}

/** Обновляет индикатор силы пароля */
function updateStrengthBar(password, barId) {
    const bar = document.getElementById(barId);
    if (!bar) return;

    const score  = calcPasswordStrength(password);
    const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
    const colors = ['', '#ef4444', '#f59e0b', '#3b82f6', '#00d66f'];

    bar.style.width     = password.length ? `${score * 25}%` : '0%';
    bar.style.background = colors[score] || '#ef4444';

    const label = bar.closest('.password-strength-wrap')?.querySelector('.strength-label');
    if (label) label.textContent = password.length ? labels[score] : '';
}

/** Инициализирует индикатор силы пароля для поля */
function initStrengthIndicator(inputId, barId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    input.addEventListener('input', () => updateStrengthBar(input.value, barId));
}

/** Показывает сообщение об ошибке/успехе в элементе */
function showFormMessage(el, message, type = 'error') {
    if (!el) return;
    el.textContent = message;
    el.className   = `auth-message auth-message--${type}`;
    el.style.display = 'block';
}

/** Блокирует кнопку сабмита и сохраняет оригинальный текст */
function setButtonLoading(btn, loadingText) {
    if (!btn) return;
    btn._originalHtml   = btn.innerHTML;
    btn.disabled        = true;
    btn.innerHTML       = `<span class="btn-spinner"></span>${loadingText}`;
}

/** Восстанавливает кнопку */
function resetButton(btn) {
    if (!btn || !btn._originalHtml) return;
    btn.disabled  = false;
    btn.innerHTML = btn._originalHtml;
}

// -------------------------------------------------------
// Данные о пользователе
// -------------------------------------------------------

async function loadUserInfo() {
    const elements = {
        sidebarName:     document.getElementById('sidebar-username'),
        profilePageName: document.getElementById('profile-page-name'),
        profilePageEmail: document.getElementById('profile-page-email'),
        profilePageDate:  document.getElementById('profile-page-date'),
        avatarImg:        document.getElementById('profile-avatar-img'),
        sidebarImg:       document.getElementById('sidebar-avatar-img'),
        avatarIcon:       document.getElementById('profile-avatar-icon'),
        sidebarIcon:      document.getElementById('sidebar-avatar-icon')
    };

    try {
        const res  = await fetch(`${API_URL}?action=get_user_info`);
        const data = await res.json();

        if (data.success) {
            Object.entries(elements).forEach(([key, element]) => {
                if (!element) return;
                switch (key) {
                    case 'sidebarName':
                    case 'profilePageName':
                        element.textContent = data.username; break;
                    case 'profilePageEmail':
                        element.textContent = data.email; break;
                    case 'profilePageDate':
                        element.textContent = data.created_at; break;
                }
            });
            updateAvatar(data.avatar_url, elements);
        }
    } catch (e) {
        console.error(e);
        if (elements.sidebarName) elements.sidebarName.textContent = window.lang?.user || 'User';
    }
}

function updateAvatar(avatarUrl, elements) {
    const { avatarImg, sidebarImg, avatarIcon, sidebarIcon } = elements;

    if (avatarUrl) {
        [avatarImg, sidebarImg].forEach(img => {
            if (img) { img.src = avatarUrl; img.style.display = 'block'; }
        });
        [avatarIcon, sidebarIcon].forEach(icon => {
            if (icon) icon.style.display = 'none';
        });
    } else {
        [avatarImg, sidebarImg].forEach(img => {
            if (img) img.style.display = 'none';
        });
        [avatarIcon, sidebarIcon].forEach(icon => {
            if (icon) icon.style.display = 'block';
        });
    }
}

// -------------------------------------------------------
// Авторизация (Login)
// -------------------------------------------------------

async function handleLoginSubmit(event) {
    event.preventDefault();
    const form      = event.target;
    const formData  = new FormData(form);
    formData.append('action', 'login');
    const errorDiv  = document.getElementById('login-error');
    const submitBtn = form.querySelector('button[type="submit"]');

    showFormMessage(errorDiv, '', 'error');
    setButtonLoading(submitBtn, window.lang?.['checking'] || 'Checking...');

    try {
        const response = await fetch(API_URL, { method: 'POST', body: formData });
        const result   = await response.json();

        if (result.success) {
            window.location.href = 'index.php?view=dashboard';
        } else {
            showFormMessage(errorDiv, result.message || window.lang?.['login_error'] || 'Login error');
        }
    } catch (error) {
        showFormMessage(errorDiv, window.lang?.['network_error_try_later'] || 'Network error. Please try again.');
        console.error('Login error:', error);
    } finally {
        resetButton(submitBtn);
    }
}

// -------------------------------------------------------
// Регистрация (Register)
// -------------------------------------------------------

async function handleRegisterSubmit(event) {
    event.preventDefault();
    const form      = event.target;
    const formData  = new FormData(form);
    const pass      = formData.get('password');
    const confirm   = formData.get('password_confirm');
    const errorDiv  = document.getElementById('register-error');
    const submitBtn = form.querySelector('button[type="submit"]');

    showFormMessage(errorDiv, '', 'error');

    if (pass !== confirm) {
        showFormMessage(errorDiv, window.lang?.['password_doesnt_match'] || "Passwords don't match");
        return;
    }

    setButtonLoading(submitBtn, window.lang?.['registration'] || 'Registering...');

    try {
        const data     = Object.fromEntries(formData.entries());
        const response = await fetch(`${API_URL}?action=register`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(data)
        });
        const result = await response.json();

        if (result.success) {
            showFormMessage(errorDiv, window.lang?.['registration_successful'] || 'Registration successful! Redirecting...', 'success');
            setTimeout(() => { window.location.href = 'index.php?view=login'; }, 1800);
        } else {
            showFormMessage(errorDiv, result.message || window.lang?.['registration_error'] || 'Registration error');
        }
    } catch (error) {
        showFormMessage(errorDiv, window.lang?.['network_error'] || 'Network error');
        console.error('Register error:', error);
    } finally {
        resetButton(submitBtn);
    }
}

// -------------------------------------------------------
// Забыл пароль (Forgot Password)
// -------------------------------------------------------

async function handleForgotPasswordSubmit(event) {
    event.preventDefault();
    const form      = event.target;
    const emailEl   = document.getElementById('forgot-email');
    const msgEl     = document.getElementById('forgot-message');
    const submitBtn = form.querySelector('button[type="submit"]');

    if (!emailEl) return;
    showFormMessage(msgEl, '', 'error');
    setButtonLoading(submitBtn, window.lang?.['sending'] || 'Sending...');

    try {
        const response = await fetch(`${API_URL}?action=forgot_password`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ email: emailEl.value.trim() })
        });
        const result = await response.json();

        if (result.success) {
            if (result.simulated && result.debug_link) {
                // DEV-режим: показываем ссылку в удобном UI
                showDevResetLink(result.debug_link, emailEl.value.trim());
            } else {
                // Показываем состояние "успешно отправлено"
                showForgotSuccess(emailEl.value.trim(), form);
            }
        } else {
            showFormMessage(msgEl, result.message || 'Error. Try again.', 'error');
        }
    } catch (error) {
        showFormMessage(msgEl, window.lang?.['network_error_try_later'] || 'Network error.', 'error');
        console.error('Forgot password error:', error);
    } finally {
        resetButton(submitBtn);
    }
}

/** Заменяет форму на экран успешной отправки */
function showForgotSuccess(email, form) {
    const wrap = form.closest('.login-box') || form.parentElement;
    const successHtml = `
        <div class="auth-success-state fade-in">
            <div class="auth-success-icon"><i class="fas fa-envelope-open-text"></i></div>
            <h2 class="auth-success-title">${window.lang?.['check_email'] || 'Check your email'}</h2>
            <p class="auth-success-text">
                ${window.lang?.['reset_link_sent'] || "We've sent a reset link to"}<br>
                <strong>${email}</strong>
            </p>
            <p class="auth-success-note">Didn't get it? Check spam or <a href="index.php?view=forgot_password" class="auth-link">try again</a>.</p>
        </div>`;
    if (wrap) wrap.innerHTML = successHtml;
}

/** Показывает модальное окно с dev-ссылкой для сброса пароля */
function showDevResetLink(link, email) {
    const overlay = document.createElement('div');
    overlay.className = 'dev-modal-overlay';
    overlay.innerHTML = `
        <div class="dev-modal fade-in">
            <div class="dev-modal-badge"><i class="fas fa-bug"></i> Dev Mode</div>
            <h3>Reset Link Generated</h3>
            <p class="dev-modal-email">For: <strong>${email}</strong></p>
            <div class="dev-modal-link-wrap">
                <input class="dev-modal-input" type="text" value="${link}" readonly id="dev-reset-link-input">
                <button class="dev-modal-copy-btn" onclick="copyDevLink()"><i class="fas fa-copy"></i></button>
            </div>
            <p class="dev-modal-note">SMTP is not configured. Configure it in <code>api/mailer.php</code> or use this link directly.</p>
            <div class="dev-modal-actions">
                <button class="btn btn-primary" onclick="window.location.href='${link}'">
                    <i class="fas fa-external-link-alt me-2"></i>Open Link
                </button>
                <button class="btn btn-secondary" onclick="this.closest('.dev-modal-overlay').remove()">Close</button>
            </div>
        </div>`;
    document.body.appendChild(overlay);
    // Закрытие по клику на фон
    overlay.addEventListener('click', e => { if (e.target === overlay) overlay.remove(); });
}

function copyDevLink() {
    const input = document.getElementById('dev-reset-link-input');
    if (!input) return;
    navigator.clipboard.writeText(input.value).then(() => {
        if (typeof showToast === 'function') showToast('Link copied!', 'success');
    });
}

// -------------------------------------------------------
// Сброс пароля (Reset Password)
// -------------------------------------------------------

async function handleResetPasswordSubmit(event) {
    event.preventDefault();
    const form      = event.target;
    const tokenEl   = document.getElementById('reset-token');
    const passEl    = document.getElementById('reset-password');
    const confirmEl = document.getElementById('reset-password-confirm');
    const msgEl     = document.getElementById('reset-message');
    const submitBtn = form.querySelector('button[type="submit"]');

    showFormMessage(msgEl, '', 'error');

    if (!tokenEl || !passEl) return;

    if (confirmEl && passEl.value !== confirmEl.value) {
        showFormMessage(msgEl, window.lang?.['password_doesnt_match'] || "Passwords don't match");
        return;
    }

    setButtonLoading(submitBtn, window.lang?.['sending'] || 'Saving...');

    try {
        const response = await fetch(`${API_URL}?action=reset_password`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ token: tokenEl.value, password: passEl.value })
        });
        const result = await response.json();

        if (result.success) {
            showFormMessage(msgEl, window.lang?.['password_changed'] || 'Password changed successfully!', 'success');
            setTimeout(() => { window.location.href = 'index.php?view=login'; }, 2000);
        } else {
            showFormMessage(msgEl, result.message || window.lang?.['error_changing_password'] || 'Error');
        }
    } catch (error) {
        showFormMessage(msgEl, window.lang?.['network_error_try_later'] || 'Network error.', 'error');
        console.error('Reset password error:', error);
    } finally {
        resetButton(submitBtn);
    }
}

// -------------------------------------------------------
// Выход
// -------------------------------------------------------

async function logout() {
    if (await showConfirm(window.lang?.['confirm_logout'] || 'Logout?')) {
        try { await fetch(`${API_URL}?action=logout`); } catch (e) { console.error(e); }
        window.location.href = 'index.php?view=login';
    }
}

// -------------------------------------------------------
// Профиль (упрощённый)
// -------------------------------------------------------

async function loadSimpleProfile() {
    try {
        const response = await fetch(`${API_URL}?action=get_user_info`);
        const result   = await response.json();

        if (result.success) {
            const elUser   = document.getElementById('profile-display-username');
            const elEmail  = document.getElementById('profile-display-email');
            const elJoined = document.getElementById('profile-join-date');

            if (elUser)   elUser.textContent   = result.username;
            if (elEmail)  elEmail.textContent  = result.email;
            if (elJoined) elJoined.textContent = result.created_at || '-';

            if (typeof syncLanguageSelect === 'function') {
                syncLanguageSelect(result.language);
            }
        }
    } catch (error) {
        console.error('Error loading profile:', error);
    }
}

// -------------------------------------------------------
// DOMContentLoaded — аватар + инициализация форм
// -------------------------------------------------------

document.addEventListener('DOMContentLoaded', () => {
    // Кнопки показа пароля
    initPasswordToggles();

    // Индикаторы силы пароля
    initStrengthIndicator('reg-password',        'reg-strength-bar');
    initStrengthIndicator('reset-password',       'reset-strength-bar');

    // Обработчики форм
    const forgotForm = document.getElementById('forgot-form');
    if (forgotForm) forgotForm.addEventListener('submit', handleForgotPasswordSubmit);

    const resetForm = document.getElementById('reset-form');
    if (resetForm) resetForm.addEventListener('submit', handleResetPasswordSubmit);

    // Загрузка аватара
    const avatarInput = document.getElementById('avatar-input');
    if (avatarInput) {
        avatarInput.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            if (file.size > 2 * 1024 * 1024) {
                if (typeof showToast === 'function') showToast('File too large (max 2MB)', 'error');
                return;
            }

            const fd = new FormData();
            fd.append('action', 'upload_avatar');
            fd.append('avatar', file);

            const wrapper = document.querySelector('.profile-avatar-inner');
            if (wrapper) wrapper.classList.add('avatar-uploading');

            try {
                const res  = await fetch(API_URL, { method: 'POST', body: fd });
                const json = await res.json();

                if (json.success) {
                    const newSrc    = `${json.avatar_url}?t=${Date.now()}`;
                    const avatarImg  = document.getElementById('profile-avatar-img');
                    const avatarIcon = document.getElementById('profile-avatar-icon');
                    const sidebarImg  = document.getElementById('sidebar-avatar-img');
                    const sidebarIcon = document.getElementById('sidebar-avatar-icon');
                    updateAvatar(newSrc, { avatarImg, sidebarImg, avatarIcon, sidebarIcon });
                    if (typeof showToast === 'function') showToast('Avatar updated!', 'success');
                } else {
                    if (typeof showToast === 'function') showToast(json.message, 'error');
                }
            } catch (error) {
                console.error(error);
            } finally {
                if (wrapper) wrapper.classList.remove('avatar-uploading');
                avatarInput.value = '';
            }
        });
    }
});


async function loadUserInfo() {
    const elements = {
        sidebarName: document.getElementById('sidebar-username'),
        profilePageName: document.getElementById('profile-page-name'),
        profilePageEmail: document.getElementById('profile-page-email'),
        profilePageDate: document.getElementById('profile-page-date'),
        avatarImg: document.getElementById('profile-avatar-img'),
        sidebarImg: document.getElementById('sidebar-avatar-img'),
        avatarIcon: document.getElementById('profile-avatar-icon'),
        sidebarIcon: document.getElementById('sidebar-avatar-icon')
    };

    try {
        const res = await fetch(`${API_URL}?action=get_user_info`);
        const data = await res.json();

        if (data.success) {
            Object.entries(elements).forEach(([key, element]) => {
                if (element) {
                    switch (key) {
                        case 'sidebarName':
                        case 'profilePageName':
                            element.textContent = data.username;
                            break;
                        case 'profilePageEmail':
                            element.textContent = data.email;
                            break;
                        case 'profilePageDate':
                            element.textContent = data.created_at;
                            break;
                    }
                }
            });

            updateAvatar(data.avatar_url, elements);
        }
    } catch (e) {
        console.error(e);
        if (elements.sidebarName) elements.sidebarName.textContent = window.lang?.user || 'User';
    }
}

function updateAvatar(avatarUrl, elements) {
    const { avatarImg, sidebarImg, avatarIcon, sidebarIcon } = elements;

    if (avatarUrl) {
        [avatarImg, sidebarImg].forEach(img => {
            if (img) {
                img.src = avatarUrl;
                img.style.display = 'block';
            }
        });
        [avatarIcon, sidebarIcon].forEach(icon => {
            if (icon) icon.style.display = 'none';
        });
    } else {
        [avatarImg, sidebarImg].forEach(img => {
            if (img) img.style.display = 'none';
        });
        [avatarIcon, sidebarIcon].forEach(icon => {
            if (icon) icon.style.display = 'block';
        });
    }
}

async function handleLoginSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    formData.append('action', 'login');
    const errorDiv = document.getElementById('login-error');
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;

    errorDiv.textContent = '';
    submitBtn.disabled = true;
    submitBtn.innerHTML = window.lang['checking'];

    try {
        const response = await fetch(API_URL, { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) {
            window.location.href = 'index.php?view=dashboard';
        } else {
            errorDiv.textContent = result.message || window.lang['login_error'];
        }
    } catch (error) {
        errorDiv.textContent = window.lang['network_error_try_later'];
        console.error('Login error:', error);
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    }
}

async function handleRegisterSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    const pass = formData.get('password');
    const passConfirm = formData.get('password_confirm');
    const errorDiv = document.getElementById('register-error');

    if (pass !== passConfirm) {
        errorDiv.textContent = window.lang['password_doesnt_match'];
        return;
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;

    errorDiv.textContent = '';
    submitBtn.disabled = true;
    submitBtn.innerHTML = window.lang['registration'];

    try {
        const data = Object.fromEntries(formData.entries());
        const response = await fetch(`${API_URL}?action=register`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();

        if (result.success) {
            window.location.href = 'index.php?view=dashboard';
        } else {
            errorDiv.textContent = result.message || window.lang['registration_error'];
        }
    } catch (error) {
        errorDiv.textContent = window.lang['network_error'];
        console.error('Register error:', error);
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    }
}

async function logout() {
    if (await showConfirm(window.lang['confirm_logout'])) {
        try { await fetch(`${API_URL}?action=logout`); } catch (e) { console.error(e); }
        window.location.href = 'index.php?view=login';
    }
}

async function loadSimpleProfile() {
    try {
        const response = await fetch(`${API_URL}?action=get_user_info`);
        const result = await response.json();

        if (result.success) {
            const elUser = document.getElementById('profile-display-username');
            const elEmail = document.getElementById('profile-display-email');
            const elJoined = document.getElementById('profile-join-date');

            if (elUser) elUser.textContent = result.username;
            if (elEmail) elEmail.textContent = result.email;
            if (elJoined) elJoined.textContent = result.created_at || '-';

            // Вызываем функцию из другого модуля для синхронизации языка
            if (typeof syncLanguageSelect === 'function') {
                syncLanguageSelect(result.language);
            }
        }
    } catch (error) {
        console.error('Error loading profile:', error);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const avatarInput = document.getElementById('avatar-input');
    
    if (avatarInput) {
        avatarInput.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            if (file.size > 2 * 1024 * 1024) {
                if (typeof showToast === 'function') showToast('File too large (max 2MB)', 'error');
                return;
            }

            const fd = new FormData();
            fd.append('action', 'upload_avatar');
            fd.append('avatar', file);

            const wrapper = document.querySelector('.profile-avatar-inner');
            if (wrapper) wrapper.classList.add('avatar-uploading'); // Анимация пульсации

            try {
                const res = await fetch(API_URL, { method: 'POST', body: fd });
                const json = await res.json();

                if (json.success) {
                    const newSrc = `${json.avatar_url}?t=${new Date().getTime()}`;
                    
                    // Элементы профиля
                    const avatarImg = document.getElementById('profile-avatar-img');
                    const avatarIcon = document.getElementById('profile-avatar-icon');
                    
                    // Элементы сайдбара
                    const sidebarImg = document.getElementById('sidebar-avatar-img');
                    const sidebarIcon = document.getElementById('sidebar-avatar-icon');
                    
                    // Обновляем картинки
                    updateAvatar(newSrc, { avatarImg, sidebarImg, avatarIcon, sidebarIcon });

                    if (typeof showToast === 'function') showToast('Avatar updated!', 'success');
                } else {
                    if (typeof showToast === 'function') showToast(json.message, 'error');
                }
            } catch (error) {
                console.error(error);
            } finally {
                if (wrapper) wrapper.classList.remove('avatar-uploading');
                avatarInput.value = ''; 
            }
        });
    }
});