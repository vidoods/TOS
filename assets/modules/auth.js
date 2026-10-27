// assets/modules/auth.js
// ==================================================
// ФУНКЦИИ АВТОРИЗАЦИИ И ПРОФИЛЯ (TradeOS)
// ==================================================

const API_URL = 'api/api.php';

// -------------------------------------------------------
// Вспомогательные функции UI
// -------------------------------------------------------

function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    const icon = btn ? btn.querySelector('i') : null;
    if (icon) {
        icon.className = isHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
    }
}
window.togglePassword = togglePassword;

function initPasswordToggles() {
    document.querySelectorAll('[data-toggle-password]').forEach(btn => {
        btn.onclick = function (e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            const targetId = this.getAttribute('data-toggle-password');
            togglePassword(targetId, this);
        };
    });
}
window.initPasswordToggles = initPasswordToggles;

function calcPasswordStrength(password) {
    let score = 0;
    if (password.length >= 8)  score++;
    if (password.length >= 12) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;
    return Math.min(score, 4);
}

function updateStrengthBar(password, barId) {
    const bar = document.getElementById(barId);
    if (!bar) return;

    const score  = calcPasswordStrength(password);
    const labels = [
        '',
        window.lang?.['password_strength_weak'] || 'Weak',
        window.lang?.['password_strength_medium'] || 'Medium',
        window.lang?.['password_strength_good'] || 'Good',
        window.lang?.['password_strength_strong'] || 'Strong'
    ];
    const colors = ['', '#ef4444', '#f59e0b', '#3b82f6', '#10b981'];

    bar.style.width     = password.length ? `${score * 25}%` : '0%';
    bar.style.background = colors[score] || '#ef4444';

    const label = bar.closest('.password-strength-wrap')?.querySelector('.strength-label');
    if (label) label.textContent = password.length ? labels[score] : '';
}

function initStrengthIndicator(inputId, barId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    input.addEventListener('input', () => updateStrengthBar(input.value, barId));
}

function showFormMessage(el, message, type = 'error') {
    if (!el) return;
    if (!message) {
        el.style.display = 'none';
        return;
    }
    el.innerHTML = type === 'error' ? `<i class="fas fa-exclamation-circle"></i> ${message}` : `<i class="fas fa-check-circle"></i> ${message}`;
    el.className = `auth-message auth-message--${type}`;
    el.style.display = 'flex';
}

function setButtonLoading(btn, loadingText) {
    if (!btn) return;
    btn._originalHtml   = btn.innerHTML;
    btn.disabled        = true;
    btn.innerHTML       = `<span class="btn-spinner"></span> ${loadingText}`;
}

function resetButton(btn) {
    if (!btn || !btn._originalHtml) return;
    btn.disabled  = false;
    btn.innerHTML = btn._originalHtml;
}

// -------------------------------------------------------
// Функции Профиля (Восстановлено)
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
        console.error('Ошибка загрузки пользователя:', e);
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
        console.error('Ошибка загрузки профиля:', error);
    }
}

// ВОССТАНОВЛЕНО: Функция выхода
async function logout() {
    // Если у тебя есть кастомная функция showConfirm, используем её, иначе fallback на стандартный confirm
    const confirmMessage = window.lang?.['confirm_logout'] || 'Are you sure you want to log out?';
    const isConfirmed = typeof showConfirm === 'function' ? await showConfirm(confirmMessage) : confirm(confirmMessage);
    
    if (isConfirmed) {
        try { 
            await fetch(`${API_URL}?action=logout`); 
        } catch (e) { 
            console.error('Logout error:', e); 
        }
        window.location.href = 'index.php?view=login';
    }
}

// -------------------------------------------------------
// Обработчики Форм Авторизации
// -------------------------------------------------------

async function handleLoginSubmit(event) {
    event.preventDefault();
    const form      = event.target;
    const formData  = new FormData(form);
    formData.append('action', 'login');
    const errorDiv  = document.getElementById('login-error');
    const submitBtn = form.querySelector('button[type="submit"]');

    if (submitBtn.disabled) return; // Защита от спам-кликов

    showFormMessage(errorDiv, '');
    setButtonLoading(submitBtn, window.lang?.['checking'] || 'Checking...');

    try {
        const response = await fetch(API_URL, { method: 'POST', body: formData });
        const result   = await response.json();

        if (result.success) {
            window.location.href = 'index.php?view=dashboard';
        } else if (result.unverified) {
            const email = result.email || '';
            const unverifiedText = window.lang?.['email_not_confirmed'] || 'Email is not verified.';
            const sendAgainText = window.lang?.['send_link_again'] || 'Send link again';
            errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${unverifiedText} <a href="javascript:void(0)" onclick="resendVerificationEmail('${email}')" style="color: var(--accent-blue, #2997ff); text-decoration: underline; margin-left: 6px;">${sendAgainText}</a>`;
            errorDiv.className = 'auth-message auth-message--error';
            errorDiv.style.display = 'flex';
        } else {
            showFormMessage(errorDiv, result.message || window.lang?.['login_error'] || 'Login error');
        }
    } catch (error) {
        showFormMessage(errorDiv, window.lang?.['network_error_try_later'] || window.lang?.['network_error'] || 'Network error. Please try again later.');
    } finally {
        resetButton(submitBtn);
    }
}

async function handleRegisterSubmit(event) {
    event.preventDefault();
    const form      = event.target;
    const formData  = new FormData(form);
    const pass      = formData.get('password');
    const confirm   = formData.get('password_confirm');
    const errorDiv  = document.getElementById('register-error');
    const submitBtn = form.querySelector('button[type="submit"]');

    if (submitBtn.disabled) return;

    showFormMessage(errorDiv, '');

    if (pass !== confirm) {
        showFormMessage(errorDiv, window.lang?.['password_doesnt_match'] || "Passwords do not match");
        return;
    }

    setButtonLoading(submitBtn, window.lang?.['creating_account'] || window.lang?.['registration'] || 'Creating account...');

    try {
        const data     = Object.fromEntries(formData.entries());
        const response = await fetch(`${API_URL}?action=register`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(data)
        });
        const result = await response.json();

        if (result.success) {
            if (result.requires_verification) {
                showRegisterSuccess(data.email, form);
            } else {
                showFormMessage(errorDiv, window.lang?.['registration_successful_redirect'] || window.lang?.['registration_successful'] || 'Success! Redirecting...', 'success');
                setTimeout(() => { window.location.href = 'index.php?view=login'; }, 1800);
            }
        } else {
            showFormMessage(errorDiv, result.message || window.lang?.['registration_error'] || 'Registration error');
        }
    } catch (error) {
        showFormMessage(errorDiv, window.lang?.['network_error'] || 'Network error');
    } finally {
        resetButton(submitBtn);
    }
}

async function handleForgotPasswordSubmit(event) {
    event.preventDefault();
    const form      = event.target;
    const emailEl   = document.getElementById('forgot-email');
    const msgEl     = document.getElementById('forgot-message');
    const submitBtn = form.querySelector('button[type="submit"]');

    if (!emailEl || submitBtn.disabled) return; 
    
    showFormMessage(msgEl, '');
    setButtonLoading(submitBtn, window.lang?.['sending'] || 'Sending...');

    try {
        const response = await fetch(`${API_URL}?action=forgot_password`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ email: emailEl.value.trim() })
        });
        const result = await response.json();

        if (result.success) {
            showForgotSuccess(emailEl.value.trim(), form);
        } else {
            showFormMessage(msgEl, result.message || window.lang?.['error_try_later'] || window.lang?.['network_error_try_later'] || 'Error. Please try again later.', 'error');
        }
    } catch (error) {
        showFormMessage(msgEl, window.lang?.['network_error_try_later'] || window.lang?.['network_error'] || 'Network error.', 'error');
    } finally {
        resetButton(submitBtn);
    }
}

function showRegisterSuccess(email, form) {
    const wrap = form.closest('.login-box') || form.parentElement;
    const checkEmailText = window.lang?.['check_email'] || 'Check your email';
    const sentText = window.lang?.['activation_link_sent_to'] || 'We sent an activation link to';
    const instructionsText = window.lang?.['activation_instructions'] || 'Follow the link in the email to verify your email address and get started in TradeOS.';
    const goToLoginText = window.lang?.['go_to_login_btn'] || window.lang?.['login'] || 'Go to Login';
    const resendText = window.lang?.['didnt_receive_email'] || 'Didn\'t receive the email? Resend';

    const successHtml = `
        <div class="auth-success-state fade-in" style="text-align: center; padding: 20px 0;">
            <div style="font-size: 3.5rem; color: #10b981; margin-bottom: 20px;"><i class="fas fa-envelope-circle-check"></i></div>
            <h2 style="font-size: 1.5rem; font-weight: 700; color: #f8fafc; margin: 0 0 12px;">${checkEmailText}</h2>
            <p style="font-size: 0.95rem; color: #94a3b8; line-height: 1.6; margin: 0 0 16px;">
                ${sentText}<br><strong style="color: #f8fafc;">${email}</strong>
            </p>
            <p style="font-size: 0.85rem; color: #94a3b8; line-height: 1.5; margin-bottom: 24px;">
                ${instructionsText}
            </p>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <a href="index.php?view=login" class="auth-submit-btn" style="text-decoration: none; padding: 12px; font-size: 0.95rem; text-align: center; display: block;">
                    ${goToLoginText}
                </a>
                <button type="button" onclick="resendVerificationEmail('${email}')" style="background: none; border: none; cursor: pointer; color: var(--accent-blue, #2997ff); font-size: 0.85rem; padding: 8px;">
                    ${resendText}
                </button>
            </div>
            <div id="resend-message-inline" class="auth-message" style="display:none; margin-top: 14px;"></div>
        </div>`;
    if (wrap) wrap.innerHTML = successHtml;
}

async function resendVerificationEmail(email) {
    if (!email) {
        const input = document.getElementById('resend-email-input');
        if (input) email = input.value.trim();
    }
    const msgEl = document.getElementById('resend-message') || document.getElementById('resend-message-inline') || document.getElementById('login-error');
    if (!email) {
        if (msgEl) showFormMessage(msgEl, window.lang?.['specify_email'] || 'Please provide an email address.', 'error');
        return;
    }

    const btn = document.getElementById('resend-verify-btn');
    if (btn) setButtonLoading(btn, window.lang?.['sending'] || 'Sending...');

    try {
        const res = await fetch(`${API_URL}?action=resend_verification`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email })
        });
        const json = await res.json();
        if (msgEl) {
            showFormMessage(msgEl, json.message || window.lang?.['link_sent'] || 'Link sent.', json.success ? 'success' : 'error');
        }
    } catch (e) {
        if (msgEl) showFormMessage(msgEl, window.lang?.['network_error_try_later'] || window.lang?.['network_error'] || 'Network error.', 'error');
    } finally {
        if (btn) resetButton(btn);
    }
}

function showForgotSuccess(email, form) {
    const wrap = form.closest('.login-box') || form.parentElement;
    const checkEmailText = window.lang?.['check_email'] || 'Check your email';
    const sentText = window.lang?.['recovery_link_sent_to'] || 'We sent a password recovery link to';
    const retryText = window.lang?.['check_spam_or_retry'] || 'No email? Check spam or <a href="index.php?view=forgot_password" style="color: #3b82f6; text-decoration: none;">try again</a>.';

    const successHtml = `
        <div class="auth-success-state fade-in" style="text-align: center; padding: 16px 0;">
            <div style="font-size: 3rem; color: #3b82f6; margin-bottom: 16px;"><i class="fas fa-envelope-open-text"></i></div>
            <h2 style="font-size: 1.4rem; font-weight: 700; color: #f8fafc; margin: 0 0 10px;">${checkEmailText}</h2>
            <p style="font-size: 0.9rem; color: #94a3b8; line-height: 1.6; margin: 0 0 12px;">
                ${sentText}<br><strong style="color: #f8fafc;">${email}</strong>
            </p>
            <p style="font-size: 0.8rem; color: #94a3b8; opacity: 0.7;">${retryText}</p>
        </div>`;
    if (wrap) wrap.innerHTML = successHtml;
}

function showDevResetLink(link, email) {
    const overlay = document.createElement('div');
    overlay.className = 'dev-modal-overlay';
    overlay.style.cssText = "position: fixed; inset: 0; z-index: 100000; display: flex; align-items: center; justify-content: center; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(8px);";
    const devModeText = window.lang?.['dev_mode'] || 'Dev Mode';
    const linkGeneratedText = window.lang?.['link_generated'] || 'Link generated';
    const forText = window.lang?.['link_for'] || 'For:';
    const openLinkText = window.lang?.['open_link'] || 'Open link';
    const closeText = window.lang?.['close'] || window.lang?.['cancel'] || 'Close';

    overlay.innerHTML = `
        <div class="dev-modal fade-in" style="background: rgba(22, 27, 34, 0.98); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 16px; padding: 32px; width: 90%; max-width: 500px; box-shadow: 0 20px 60px rgba(0,0,0,0.6);">
            <div style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 20px; font-size: 0.75rem; font-weight: 700; color: #f59e0b; text-transform: uppercase; margin-bottom: 16px;"><i class="fas fa-bug"></i> ${devModeText}</div>
            <h3 style="font-size: 1.2rem; font-weight: 700; color: #f8fafc; margin: 0 0 8px;">${linkGeneratedText}</h3>
            <p style="font-size: 0.85rem; color: #94a3b8; margin: 0 0 20px;">${forText} <strong style="color: #f8fafc;">${email}</strong></p>
            <div style="display: flex; gap: 8px; margin-bottom: 16px;">
                <input type="text" value="${link}" readonly id="dev-reset-link-input" style="flex: 1; padding: 10px 14px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #3b82f6; font-size: 0.78rem; font-family: monospace; outline: none;">
                <button onclick="document.getElementById('dev-reset-link-input').select(); document.execCommand('copy');" style="padding: 10px 14px; background: rgba(41,151,255,0.1); border: 1px solid rgba(41,151,255,0.2); border-radius: 8px; color: #3b82f6; cursor: pointer;"><i class="fas fa-copy"></i></button>
            </div>
            <div style="display: flex; gap: 10px;">
                <button onclick="window.location.href='${link}'" style="flex: 1; padding: 10px; background: #3b82f6; color: #fff; border: none; border-radius: 8px; cursor: pointer;">${openLinkText}</button>
                <button onclick="this.closest('.dev-modal-overlay').remove()" style="flex: 1; padding: 10px; background: rgba(255,255,255,0.06); color: #94a3b8; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; cursor: pointer;">${closeText}</button>
            </div>
        </div>`;
    document.body.appendChild(overlay);
}

async function handleResetPasswordSubmit(event) {
    event.preventDefault();
    const form      = event.target;
    const tokenEl   = document.getElementById('reset-token');
    const passEl    = document.getElementById('reset-password');
    const confirmEl = document.getElementById('reset-password-confirm');
    const msgEl     = document.getElementById('reset-message');
    const submitBtn = form.querySelector('button[type="submit"]');

    if (submitBtn.disabled) return;

    showFormMessage(msgEl, '');

    if (!tokenEl || !passEl) return;

    if (confirmEl && passEl.value !== confirmEl.value) {
        showFormMessage(msgEl, window.lang?.['password_doesnt_match'] || "Passwords do not match");
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
            showFormMessage(msgEl, result.message || window.lang?.['error_changing_password'] || 'Error changing password');
        }
    } catch (error) {
        showFormMessage(msgEl, window.lang?.['network_error_try_later'] || window.lang?.['network_error'] || 'Network error.', 'error');
    } finally {
        resetButton(submitBtn);
    }
}

// -------------------------------------------------------
// Инициализация при загрузке страницы
// -------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    // 1. Инициализация UI паролей
    initPasswordToggles();
    initStrengthIndicator('reg-password', 'reg-strength-bar');
    initStrengthIndicator('reset-password', 'reset-strength-bar');

    // 2. Слушатели форм авторизации
    const forgotForm = document.getElementById('forgot-form');
    if (forgotForm) forgotForm.addEventListener('submit', handleForgotPasswordSubmit);

    const resetForm = document.getElementById('reset-form');
    if (resetForm) resetForm.addEventListener('submit', handleResetPasswordSubmit);

    // 3. ВОССТАНОВЛЕНО: Слушатель загрузки аватара профиля
    const avatarInput = document.getElementById('avatar-input');
    if (avatarInput) {
        avatarInput.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            if (file.size > 2 * 1024 * 1024) {
                if (typeof showToast === 'function') showToast(window.lang?.['file_too_large_2mb'] || 'File is too large (max 2MB)', 'error');
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
                    if (typeof showToast === 'function') showToast(window.lang?.['avatar_updated'] || 'Avatar updated!', 'success');
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