// assets/modules/auth.js
// ==================================================
// ФУНКЦИИ АВТОРИЗАЦИИ
// ==================================================

const API_URL = 'api/api.php';

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
    if (confirm(window.lang['confirm_logout'])) {
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