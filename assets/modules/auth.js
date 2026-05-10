// assets/modules/auth.js
// ==================================================
// ФУНКЦИИ АВТОРИЗАЦИИ
// ==================================================

async function loadUserInfo() {
    const sidebarName = document.getElementById('sidebar-username');
    const profilePageName = document.getElementById('profile-page-name');
    const profilePageEmail = document.getElementById('profile-page-email');
    const profilePageDate = document.getElementById('profile-page-date');

    try {
        const res = await fetch('api/api.php?action=get_user_info');
        const data = await res.json();

        if (data.success) {
            if (sidebarName) sidebarName.textContent = data.username;
            if (profilePageName) profilePageName.textContent = data.username;
            if (profilePageEmail) profilePageEmail.textContent = data.email;
            if (profilePageDate) profilePageDate.textContent = data.created_at;
        }
    } catch(e) {
        console.error(e);
        if (sidebarName) sidebarName.textContent = window.lang['user'];
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
        const response = await fetch('api/api.php', { method: 'POST', body: formData });
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
        const response = await fetch('api/api.php?action=register', {
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
        try { await fetch('api/api.php?action=logout'); } catch (e) { console.error(e); }
        window.location.href = 'index.php?view=login';
    }
}

async function loadSimpleProfile() {
    try {
        const response = await fetch('api/api.php?action=get_user_info');
        const result = await response.json();

        if (result.success) {
            const elUser = document.getElementById('profile-display-username');
            const elEmail = document.getElementById('profile-display-email');
            const elJoined = document.getElementById('profile-join-date');

            if (elUser) elUser.textContent = result.username;
            if (elEmail) elEmail.textContent = result.email;
            if (elJoined) elJoined.textContent = result.created_at || '-';

            // ВАЖНО: Мы вызываем функцию из другого модуля для синхронизации языка
            if (typeof syncLanguageSelect === 'function') {
                syncLanguageSelect(result.language);
            }
        }
    } catch (error) {
        console.error('Error loading profile:', error);
    }
}
