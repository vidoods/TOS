// assets/modules/language.js
// ==================================================
// ФУНКЦИИ ЛОКАЛИЗАЦИИ (ЯЗЫК)
// ==================================================

/**
 * Обработчик изменения языка в выпадающем списке
 */
async function handleLanguageChange(event) {
    const newLang = event.target.value;
    const fd = new FormData();
    fd.append('lang', newLang);
    fd.append('action', 'change_language');

    try {
        const res = await fetch('api/api.php', {
            method: 'POST',
            body: fd
        });
        const data = await res.json();
        
        if (data.success) {
            window.location.reload(); 
        } else {
            Toastify({
                text: data.message || 'Error changing language',
                duration: 3000,
                gravity: "top",
                position: "right",
                style: { background: "#ff5f6d" }
            }).showToast();
        }
    } catch (e) {
        console.error('Language change error:', e);
        Toastify({
            text: 'Network error',
            duration: 3000,
            style: { background: "#ff5f6d" }
        }).showToast();
    }
}

/**
 * Инициализация слушателей событий для языка
 */
function initLanguageSwitcher() {
    const langSelect = document.getElementById('profile-language-select');
    if (langSelect) {
        // Удаляем старый обработчик, чтобы не было дублей при повторных вызовах
        langSelect.removeEventListener('change', handleLanguageChange);
        langSelect.addEventListener('string', handleLanguageChange);
        langSelect.addEventListener('change', handleLanguageChange);
    }
}

/**
 * Функция для синхронизации значения селекта с данными пользователя
 * (Вызывается из auth.js после загрузки данных)
 */
function syncLanguageSelect(lang) {
    const langSelect = document.getElementById('profile-language-select');
    if (langSelect && lang) {
        langSelect.value = lang;
    }
}
