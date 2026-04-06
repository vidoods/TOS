// assets/app.js

// ==================================================
// ОБЩИЕ ФУНКЦИИ ИНТЕРФЕЙСА И УТИЛИТЫ
// ==================================================

let menuOpen = false;
let accountBalances = {};
let quillEditor = null; // Глобальная переменная для редактора
let equityChartInstance = null;
let mpaQuill = null; // Отдельный инстанс для MPA

// --- 1. Регистрация шрифтов (в начало файла assets/app.js) ---
if (typeof Quill !== 'undefined') {
// Получаем объект Font из Quill
const Font = Quill.import('formats/font');
// Задаем список разрешенных шрифтов (названия классов CSS)
// Важно: Первым должен идти шрифт по умолчанию (пустая строка или 'inter')
Font.whitelist = ['inter', 'roboto', 'serif', 'monospace', 'Montserrat']; 
Quill.register(Font, true);
console.log("Quill succesfully loaded");
} else {
    console.warn("Quill not found");
}

// 1. Глобальная настройка тултипов
const tooltipOptions = {
    animation: true,
    html: true,             // Разрешаем HTML
    placement: 'top',      // Авто-позиция
    delay: { "show": 100, "hide": 100 },
    trigger: 'hover focus'
};

// 2. Функция, которая превращает title в Bootstrap Tooltip
function makeTooltip(el) {
    // Если у элемента есть title и еще нет тултипа
    if (el.hasAttribute('title') && !bootstrap.Tooltip.getInstance(el)) {
        new bootstrap.Tooltip(el, tooltipOptions);
    }
}

// 3. Создаем "Наблюдателя" (MutationObserver)
// Он будет срабатывать каждый раз, когда меняется HTML на странице
const observer = new MutationObserver((mutationsList) => {
    mutationsList.forEach((mutation) => {
        // Проверяем только добавленные узлы
        mutation.addedNodes.forEach((node) => {
            if (node.nodeType === 1) { // Если это HTML-элемент (div, span, button...)
                
                // А. Проверяем сам добавленный элемент
                makeTooltip(node);

                // Б. Ищем элементы с title ВНУТРИ добавленного куска
                node.querySelectorAll('[title]').forEach(makeTooltip);
            }
        });
    });
});

// ==================================================
// SYSTEM NOTIFICATIONS (TOASTS)
// ==================================================
function showToast(message, type = 'info') {
    // Цвета (Градиенты под ваш темный дизайн)
    let background = "linear-gradient(135deg, #2193b0, #6dd5ed)"; // Info (Синий)
    
    if (type === 'success') {
        background = "linear-gradient(135deg, #00b09b, #96c93d)"; // Success (Зеленый)
    } else if (type === 'error') {
        background = "linear-gradient(135deg, #ff5f6d, #ffc371)"; // Error (Красный)
    }

    if (typeof Toastify === 'undefined') {
        alert(message); // Если библиотека не загрузилась
        return;
    }

    Toastify({
        text: message,
        duration: 3000,
        gravity: "bottom", 
        position: "right", 
        stopOnFocus: true,
        style: {
            background: background,
        },
        className: "info-toast"
    }).showToast();
}

// ==================================================
// SKELETON LOADER GENERATOR
// ==================================================
function getSkeletonHtml(type, count = 1) {
    let html = '';
    
    for (let i = 0; i < count; i++) {
        // 1. Скелетон для Журнала
        if (type === 'trade-row') {
            html += `
            <div class="skeleton-row">
                <div class="skeleton" style="height: 15px; width: 80px;"></div>
                <div class="skeleton" style="height: 15px; width: 60px;"></div>
                <div class="skeleton" style="height: 15px; width: 100px;"></div>
                <div class="skeleton" style="height: 25px; width: 60px; border-radius: 12px; margin-left: auto;"></div>
            </div>`;
        } 
        // 2. Скелетон для Карточек
        else if (type === 'card') {
            html += `
            <div class="col-md-4 mb-4">
                <div class="skeleton-card">
                    <div class="d-flex justify-content-between mb-3">
                        <div class="skeleton" style="height: 20px; width: 40%;"></div>
                        <div class="skeleton" style="height: 20px; width: 20%;"></div>
                    </div>
                    <div class="skeleton" style="height: 15px; width: 70%; margin-bottom: 10px;"></div>
                    <div class="skeleton" style="height: 15px; width: 50%;"></div>
                </div>
            </div>`;
        }
        // 3. Скелетон для Списков
        else if (type === 'list-item') {
             html += `
             <div class="d-flex justify-content-between align-items-center mb-3 p-2">
                <div class="skeleton" style="height: 15px; width: 40%;"></div>
                <div class="skeleton" style="height: 15px; width: 20%;"></div>
             </div>`;
        }
        // 4. НОВОЕ: Скелетон для Метрик (цифры на дашборде)
        else if (type === 'metric') {
             html += `<div class="skeleton" style="height: 28px; width: 100px; display:inline-block; border-radius: 6px;"></div>`;
        }
        // 5. НОВОЕ: Скелетон для текста (чуть длиннее)
        else if (type === 'text-line') {
             html += `<div class="skeleton" style="height: 20px; width: 150px; display:inline-block; border-radius: 4px;"></div>`;
        }
    }
    return html;
}

function toggleMenu() {
    menuOpen = !menuOpen;
    const sidebar = document.getElementById('sidebar');
    const contentArea = document.querySelector('.content-area');
    if (sidebar && contentArea) {
        sidebar.classList.toggle('open', menuOpen);
        contentArea.classList.toggle('menu-open', menuOpen);
    }
}

function closeMenu() {
    if (menuOpen) toggleMenu();
}

document.addEventListener('click', (event) => {
    const sidebar = document.getElementById('sidebar');
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    if (menuOpen && sidebar && !sidebar.contains(event.target) && (!mobileMenuToggle || !mobileMenuToggle.contains(event.target))) {
        closeMenu();
    }
});

function showMessage(message, type = 'success') {
    // Используем нашу новую красивую функцию вместо alert
    showToast(message, type === 'success' ? 'success' : 'error');
}

async function loadUserInfo() {
    // 1. Элементы в сайдбаре
    const sidebarName = document.getElementById('sidebar-username');
    
    // 2. Элементы на странице профиля (могут отсутствовать, если мы не в профиле)
    const profilePageName = document.getElementById('profile-page-name');
    const profilePageEmail = document.getElementById('profile-page-email');
    const profilePageDate = document.getElementById('profile-page-date');

    try {
        const res = await fetch('api/api.php?action=get_user_info');
        const data = await res.json();
        
        if(data.success) {
            // Обновляем сайдбар (как и было)
            if (sidebarName) sidebarName.textContent = data.username;

            // Если мы на странице профиля — обновляем и её
            if (profilePageName) profilePageName.textContent = data.username;
            if (profilePageEmail) profilePageEmail.textContent = data.email;
            if (profilePageDate) profilePageDate.textContent = data.created_at;
        }
    } catch(e) { 
        console.error(e); 
        if (sidebarName) sidebarName.textContent = 'User';
    }
}

// ==================================================
// ФУНКЦИИ АВТОРИЗАЦИИ
// ==================================================

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
    submitBtn.innerHTML = 'Checking...';

    try {
        const response = await fetch('api/api.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) {
            window.location.href = 'index.php?view=dashboard';
        } else {
            errorDiv.textContent = result.message || 'Login error';
        }
    } catch (error) {
        errorDiv.textContent = 'Network error. Please try again later.';
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
        errorDiv.textContent = 'Password doesn`t match';
        return;
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;

    errorDiv.textContent = '';
    submitBtn.disabled = true;
    submitBtn.innerHTML = 'Registration...';

    try {
        // Формируем JSON объект
        const data = Object.fromEntries(formData.entries());
        
        // Отправляем запрос
        // Обратите внимание: action=register передается в URL, а данные в body как JSON
        const response = await fetch('api/api.php?action=register', { 
            method: 'POST', 
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data) 
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.location.href = 'index.php?view=dashboard';
        } else {
            errorDiv.textContent = result.message || 'Registration error';
        }
    } catch (error) {
        errorDiv.textContent = 'Network error.';
        console.error('Register error:', error);
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    }
}

async function logout() {
    if (confirm('Are you sure you want to logout?')) {
        try { await fetch('api/api.php?action=logout'); } catch (e) { console.error(e); }
        window.location.href = 'index.php?view=login';
    }
}

// ==================================================
// ОБЩИЕ ФУНКЦИИ ЗАГРУЗКИ ДАННЫХ И ФОРМ
// ==================================================

async function loadLookups() {
    try {
        const response = await fetch('api/api.php?action=get_lookups');
        const result = await response.json();
        if (result.success) {
            const data = result.data;
            
            accountBalances = {};
            if (data.accounts) {
                data.accounts.forEach(acc => {
                    accountBalances[acc.id] = parseFloat(acc.balance);
                });
            }

            populateSelect('plan-pair', data.pairs, 'symbol');
            populateSelect('trade-pair', data.pairs, 'symbol');
            populateSelect('trade-account', data.accounts, 'name');
            populateSelect('trade-style', data.styles, 'name');
            populateSelect('trade-model', data.models, 'name');
            populateSelect('trade-plan', data.plans, 'title');
			populateSelect('trade-note', data.notes, 'title', 'id', null, '--- No note ---');
            populateSelect('plan-note', data.notes, 'title', 'id', null, '--- No note ---');
            
            // Заполнение для заметок
            if(document.getElementById('note-plan')) {
                populateSelect('note-plan', data.plans, 'title');
                populateSelect('note-trade', data.trades, 'display_name', 'id', null, '-- Choose trade --');
            }
            
            populateSelect('filter-pair', data.pairs, 'symbol', 'id', null, 'All pairs');
            
            return data;
        } else {
            console.error('Failed to load:', result.message);
            showMessage('Failed to load.', 'error');
            return null;
        }
    } catch (error) {
        console.error('Failed to load due to network error:', error);
        showMessage('Failed to load due to network error:.', 'error');
        return null;
    }
}

function populateSelect(selectId, items, displayKey, valueKey = 'id', selectedValue = null, placeholderText = '--- Choose ---') {
    const select = document.getElementById(selectId);
    if (!select) return;
    const firstOption = select.querySelector('option[value=""]');
    select.innerHTML = '';
    if (firstOption) select.appendChild(firstOption);
    
    if (!items || items.length === 0) return;
    items.forEach(item => {
        const option = document.createElement('option');
        option.value = item[valueKey];
        option.textContent = item[displayKey] || item.id; // Fallback
        if (selectedValue && item[valueKey] == selectedValue) option.selected = true;
        select.appendChild(option);
    });
}

async function handleFormSubmit(event, action, entityName, redirectView) {
    event.preventDefault();
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span>⏳</span> Saving...';

    // 1. Переносим HTML из редактора
    if (quillEditor) {
        if (entityName === 'note') {
            const el = document.getElementById('note-content-hidden');
            if(el) el.value = quillEditor.root.innerHTML;
        } else if (entityName === 'strategy') {
            const el = document.getElementById('st-content-hidden');
            if(el) el.value = quillEditor.root.innerHTML;
        }
    }

    try {
        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => {
            if (key.includes('[')) {
                const [mainKey, index, subKey] = key.match(/(\w+)\[(\d+)\]\[(\w+)\]/).slice(1);
                if (!data[mainKey]) data[mainKey] = [];
                if (!data[mainKey][index]) data[mainKey][index] = {};
                data[mainKey][index][subKey] = value;
            } else {
                data[key] = value;
            }
        });
        
        // 2. Добавляем ID если это редактирование
        if (entityName === 'plan' && document.getElementById('edit-plan-id')) {
            const idVal = document.getElementById('edit-plan-id').value;
            if (idVal) data['id'] = idVal;
        }
        if (entityName === 'trade' && document.getElementById('edit-trade-id')) {
            const idVal = document.getElementById('edit-trade-id').value;
            if (idVal) data['id'] = idVal;
        }
        if (entityName === 'note' && document.getElementById('edit-note-id')) {
            const idVal = document.getElementById('edit-note-id').value;
            if (idVal) data['id'] = idVal;
        }
        if (entityName === 'strategy' && document.getElementById('edit-strategy-id')) {
            const idVal = document.getElementById('edit-strategy-id').value;
            if (idVal) data['id'] = idVal;
        }
        
        // 3. Обработка изображений (как было)
        ['timeframes', 'trade_images'].forEach(arrKey => {
             if (data[arrKey]) data[arrKey] = data[arrKey].filter(item => item && (item.url || item.notes || item.title));
        });

        const imagePromises = [];
        const processImages = (containerClass, arrayName, type) => {
            form.querySelectorAll(`.${containerClass}`).forEach((card, index) => {
                const fileInput = card.querySelector('input[type="file"]');
                const urlInput = card.querySelector('input[name*="[url]"]');
                const hiddenUrlInput = card.querySelector('input[type="hidden"][name*="[url]"]');
                
                if (fileInput && fileInput.files[0]) {
                    imagePromises.push(uploadFile(fileInput.files[0], type).then(url => {
                        if (data[arrayName] && data[arrayName][index]) data[arrayName][index].url = url;
                    }));
                } else if (urlInput && urlInput.value.trim() && (!hiddenUrlInput || urlInput.value.trim() !== hiddenUrlInput.value)) {
                     imagePromises.push(downloadImage(urlInput.value.trim(), type).then(url => {
                        if (data[arrayName] && data[arrayName][index]) data[arrayName][index].url = url;
                    }));
                }
            });
        };
        
        if (entityName === 'plan') processImages('tf-card', 'timeframes', 'plan');
        if (entityName === 'trade') processImages('trade-img-card', 'trade_images', 'trade');

        await Promise.all(imagePromises);

        // 4. Отправка запроса
        const response = await fetch(`api/api.php?action=${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();

        if (result.success) {
            // --- ЛОГИКА РЕДИРЕКТА ---
            // Если это стратегия и сервер вернул ID -> идем на страницу просмотра
            if (entityName === 'strategy' && result.id) {
                window.location.href = `index.php?view=strategy_details&id=${result.id}`;
            } else {
                // Иначе идем на общий список (как было раньше)
                window.location.href = `index.php?view=${redirectView}`;
            }
        } else {
            showMessage('Error saving: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Save error:', error);
        showMessage('An error occurred while saving.', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    }
}

async function uploadFile(file, type = 'general') {
    const formData = new FormData();
    formData.append('action', 'upload_image');
    formData.append('image', file);
    formData.append('type', type);
    const response = await fetch('api/api.php', { method: 'POST', body: formData });
    const result = await response.json();
    if (result.success) return result.url;
    throw new Error(result.message);
}

async function downloadImage(url, type = 'general') {
    const response = await fetch('api/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'download_image_from_url', image_url: url, type: type })
    });
    const result = await response.json();
    if (result.success) return result.url;
    throw new Error(result.message);
}

// ==================================================
// ЗАМЕТКИ (NOTES)
// ==================================================

async function loadNotes() {
    const container = document.getElementById('notes-grid-container');
    if (!container) return;
    
    // ДОБАВЛЕНО: Скелетоны перед загрузкой (эмулируем сетку)
    // Оборачиваем в row, если у тебя используется Bootstrap сетка
    container.innerHTML = `<div class="row">${getSkeletonHtml('card', 4)}</div>`;
    
    try {
        const res = await fetch('api/api.php?action=get_notes');
        const json = await res.json();
        
        if (json.success) {
            if (json.data.length === 0) {
                container.innerHTML = '<div class="empty-state">No notes. Create your first one!</div>';
                return;
            }
            
            let html = '';
            json.data.forEach(note => {
                // ... (код генерации HTML карточки заметки остаётся тем же) ...
                const isUsed = note.latest_usage !== 'Not Used';
                const usageStyle = isUsed ? 'color: var(--accent-blue); font-weight: 500;' : 'color: var(--text-secondary); opacity: 0.5;';
                
                // Обрати внимание: если у тебя note-card не внутри col-md-*, 
                // то скелетон (который внутри col-md-4) может выглядеть чуть иначе по ширине, 
                // но визуально это будет приемлемо для загрузки.
                html += `
                <a href="index.php?view=note_details&id=${note.id}" class="note-card">
                    <div class="note-header">
                        <i class="fas fa-bookmark note-icon"></i>
                        <div class="note-title">${note.title}</div>
                    </div>
                    <div class="note-meta">
                        <div class="note-meta-row">
                            <span>${note.date_formatted}</span>
                            <span class="meta-divider">/</span>
                            <span>${note.day}</span>
                            <span class="meta-divider">/</span>
                            <span>${note.week}</span>
                        </div>
                        <div class="note-meta-row" style="color: var(--text-secondary); opacity: 0.7;">
                            ${note.relations}
                        </div>
                         <div class="note-meta-row" style="${usageStyle}">
                            Latest usage: ${note.latest_usage}
                        </div>
                    </div>
                </a>`;
            });
            container.innerHTML = html;
        }
    } catch (e) { console.error(e); }
}

// Конфигурация "Полный фарш Quill"
const fullToolbarOptions = [
    ['bold', 'italic', 'underline', 'strike'],        				// Жирный, курсив, подчеркивание, зачеркивание
    ['blockquote', 'code-block'],                     				// Цитата, Блок кода

    [{ 'header': 1 }, { 'header': 2 }],               				// Заголовки H1, H2 кнопками
    [{ 'list': 'ordered'}, { 'list': 'bullet' }],     				// Списки
    [{ 'script': 'sub'}, { 'script': 'super' }],      				// Индексы (нижний/верхний)
    [{ 'indent': '-1'}, { 'indent': '+1' }],          				// Отступы
    [{ 'direction': 'rtl' }],                         				// Направление текста

    [{ 'size': ['small', false, 'large', 'huge'] }],  				// Размер шрифта
    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],        				// Заголовки списком

    [{ 'color': [] }, { 'background': [] }],          				// Цвет текста и фона
    [{ 'font': ['inter', 'roboto', 'serif', 'monospace', 'Montserrat'] }],        // Шрифт
    [{ 'align': [] }],                                				// Выравнивание

    ['clean'],                                        				// Очистить формат
    ['link', 'image', 'video']                        				// Ссылки, Картинки, Видео
];

async function initNoteForm() {
    const idEl = document.getElementById('edit-note-id');
    await loadLookups();
    
    if (document.getElementById('editor-container')) {
        document.getElementById('editor-container').innerHTML = ''; 
        quillEditor = new Quill('#editor-container', {
            theme: 'snow',
            placeholder: 'Type here...',
            modules: {
                toolbar: {
                    container: fullToolbarOptions, // Используем полную конфигурацию
                    handlers: {
                        'image': function() {
                            const input = document.createElement('input');
                            input.setAttribute('type', 'file');
                            input.setAttribute('accept', 'image/*');
                            input.click();

                            input.onchange = async () => {
                                const file = input.files[0];
                                if (file && /^image\//.test(file.type)) {
                                    try {
                                        const url = await uploadFile(file, 'notes'); 
                                        const range = quillEditor.getSelection();
                                        quillEditor.insertEmbed(range.index, 'image', url);
                                    } catch (e) {
                                        console.error('Upload failed:', e);
                                        alert('Failed to load an image');
                                    }
                                } else {
                                    alert('Select an image.');
                                }
                            };
                        }
                    }
                }
            }
        });
    }

    if(idEl && idEl.value) {
        const r = await fetch(`api/api.php?action=get_note_details&id=${idEl.value}`);
        const j = await r.json();
        if(j.success) {
            const n = j.data;
            document.getElementById('note-title').value = n.title;
            if(quillEditor) quillEditor.clipboard.dangerouslyPasteHTML(n.content || '');
            if(n.trade && n.trade.id) document.getElementById('note-trade').value = n.trade.id;
            if(n.plan && n.plan.id) document.getElementById('note-plan').value = n.plan.id;
        }
    }
}

async function loadNoteDetails() {
    const id = document.getElementById('current-note-id')?.value;
    if (!id) return;
    
    const res = await fetch(`api/api.php?action=get_note_details&id=${id}`);
    const json = await res.json();
    
    if (json.success) {
        const n = json.data;
        
        document.getElementById('note-details-title').textContent = n.title;
        document.getElementById('note-content-display').innerHTML = n.content; // Выводим HTML
        document.getElementById('note-created-at').textContent = n.date_formatted || n.created_formatted;
        document.getElementById('note-date-info').textContent = n.created_formatted;

        const tradeEl = document.getElementById('note-linked-trade');
        if (n.trade) {
            tradeEl.innerHTML = `<a href="index.php?view=trade_details&id=${n.trade.id}" class="info-badge badge-blue" style="text-decoration: none;">${n.trade.label}</a>`;
        } else {
            tradeEl.textContent = 'No link';
        }

        const planEl = document.getElementById('note-linked-plan');
        if (n.plan) {
            planEl.innerHTML = `<a href="index.php?view=plan_details&id=${n.plan.id}" class="info-badge badge-blue" style="text-decoration: none;">${n.plan.label}</a>`;
        } else {
            planEl.textContent = 'No link';
        }
        
        document.getElementById('btn-edit-note').onclick = () => window.location.href = `index.php?view=note_create&id=${n.id}`;
        document.getElementById('btn-delete-note').onclick = () => deleteNote(n.id);
    }
}

async function deleteNote(id) {
    if(!confirm('Delete note?')) return;
    const fd = new FormData(); fd.append('id', id);
    await fetch('api/api.php?action=delete_note', {method:'POST', body:fd});
    window.location.href='index.php?view=notes';
}

// ==================================================
// ФУНКЦИИ ДЛЯ ПЛАНОВ
// ==================================================

let tfCount = 0;
let isPlanEditMode = false;

async function initPlanForm() {
    const planIdInput = document.getElementById('edit-plan-id');
    isPlanEditMode = (planIdInput && planIdInput.value.trim() !== "");
    
    await loadLookups();
    
    if (isPlanEditMode) {
        await loadPlanDataForEdit(planIdInput.value);
    } else {
        addTimeframe();
    }
    setupAutoUpdateTitle();
}

async function loadPlanDataForEdit(planId) {
    try {
        const response = await fetch(`api/api.php?action=get_plan_details&id=${planId}`);
        const result = await response.json();
        if (result.success) {
            const plan = result.data;
            for (const key in plan) {
                 const input = document.querySelector(`[name="${key}"]`);
                 if (input) input.value = plan[key];
            }
            if (plan.pair_id) document.getElementById('plan-pair').value = plan.pair_id;
            if (plan.type) document.getElementById('plan-type').value = plan.type;
            if (plan.bias) document.getElementById('plan-bias').value = plan.bias;
			if(plan.note_id) document.getElementById('plan-note').value = plan.note_id;
            
            const container = document.getElementById('timeframes-container');
            container.innerHTML = '';
            if (plan.timeframes && plan.timeframes.length) {
                plan.timeframes.forEach(tf => addTimeframe(tf));
            } else {
                addTimeframe();
            }
            document.getElementById('form-page-title').textContent = 'Edit Plan';
        } else {
            showMessage('Error loading plan: ' + result.message, 'error');
            window.location.href = 'index.php?view=plans';
        }
    } catch (error) {
        console.error('Error loading plan for edit', error);
        showMessage('Network error.', 'error');
    }
}

function setupAutoUpdateTitle() {
     const update = () => {
        if (isPlanEditMode) return;
        const type = document.getElementById('plan-type')?.value || 'Weekly';
        const dateVal = document.getElementById('plan-date')?.value;
        if (!dateVal) return;
        const date = new Date(dateVal);
        const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        let formattedDate = `${date.getDate()} ${monthNames[date.getMonth()]} ${date.getFullYear()}`;
        if (type.toLowerCase().includes('weekly')) {
            const endDate = new Date(date); endDate.setDate(date.getDate() + 4);
            formattedDate = `${date.getDate()} ${monthNames[date.getMonth()]}`;
            if (date.getMonth() !== endDate.getMonth()) formattedDate += ` - ${endDate.getDate()} ${monthNames[endDate.getMonth()]}`;
            else formattedDate += `-${endDate.getDate()}`;
             formattedDate += ` ${date.getFullYear()}`;
        }
        const titleInput = document.getElementById('plan-title');
        if (titleInput) titleInput.value = `${type} Plan / ${formattedDate}`;
    };
    document.getElementById('plan-type')?.addEventListener('change', update);
    document.getElementById('plan-date')?.addEventListener('change', update);
    setTimeout(update, 500);
}


function addTimeframe(data = null) {
    tfCount++;
    const container = document.getElementById('timeframes-container');
    const tfId = `tf-${tfCount}`;
    const title = data?.title || '';
    const notes = data?.notes || '';
    const url = data?.image_url || '';
    
    const html = `
        <div class="tf-card glass-panel" id="${tfId}">
            <div class="tf-header">
                <input type="text" name="timeframes[${tfCount-1}][title]" class="input-field" value="${title}" placeholder="Timeframe. Example: H4 Chart">
                <button type="button" class="btn-remove" onclick="document.getElementById('${tfId}').remove()">Delete</button>
            </div>
            <div class="form-group">
                 ${getImageInputHtml(tfId, url, `timeframes[${tfCount-1}][url]`)}
            </div>
            <div class="form-group">
                <textarea class="textarea-field" name="timeframes[${tfCount-1}][notes]" rows="3" placeholder="Notes for timeframe">${notes}</textarea>
            </div>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
}

// Обновленная функция loadPlans (использует getPlanCardHtml)
async function loadPlans(filters = {}) {
    const container = document.getElementById('plans-list-container');
    if (!container) return;
    container.innerHTML = `<div class="row">${getSkeletonHtml('card', 3)}</div>`;

    try {
        const params = new URLSearchParams(filters);
        const response = await fetch(`api/api.php?action=get_plans&${params}`);
        const result = await response.json();

        if (result.success) {
            const groupedPlans = result.data;
            if (groupedPlans.length === 0) {
                container.innerHTML = '<div class="empty-state">Plans not found.</div>';
                return;
            }
            container.innerHTML = '';
            groupedPlans.forEach(group => {
                const monthTitle = document.createElement('div');
                monthTitle.className = 'month-title';
                monthTitle.textContent = group.month_label;
                container.appendChild(monthTitle);
                
                const plansGrid = document.createElement('div');
                plansGrid.className = 'plans-grid';
                
                // ВОТ ЗДЕСЬ ИСПОЛЬЗУЕМ ОБЩУЮ ФУНКЦИЮ
                let cardsHtml = '';
                group.plans.forEach(plan => {
                    cardsHtml += getPlanCardHtml(plan);
                });
                plansGrid.innerHTML = cardsHtml;
                container.appendChild(plansGrid);
            });
        } else { container.innerHTML = `<div class="error-state">Error: ${result.message}</div>`; }
    } catch (error) { console.error(error); container.innerHTML = '<div class="error-state">Loading error.</div>'; }
}

async function loadPlanDetails() {
    const planId = document.getElementById('current-plan-id')?.value;
    if (!planId) return;
    const container = document.getElementById('plan-details-container');
    if (container) container.style.opacity = '0.5';

    try {
        const response = await fetch(`api/api.php?action=get_plan_details&id=${planId}`);
        const result = await response.json();
        if (result.success) {
            const plan = result.data;
            document.getElementById('plan-details-title').textContent = plan.title;
            
            const editBtn = document.querySelector('.plan-actions .btn-secondary');
            const deleteBtn = document.querySelector('.plan-actions .btn-danger');
            if (editBtn) editBtn.onclick = () => window.location.href = `index.php?view=plan_create&id=${plan.id}`;
            if (deleteBtn) deleteBtn.onclick = () => deleteEntity(plan.id, 'delete_plan', 'plans');
            
            // ИСПРАВЛЕННЫЙ БЛОК ЗАПОЛНЕНИЯ ПОЛЕЙ
            // Мы заполняем каждое поле явно, чтобы избежать путаницы с ID
            document.getElementById('plan-type').textContent = plan.type;
            document.getElementById('plan-pair-symbol').textContent = plan.pair_symbol; // Тут был pair_symbol, а ID plan-pair-symbol
            document.getElementById('plan-date').textContent = plan.formatted_date;
            document.getElementById('plan-bias').textContent = plan.bias;
            // Исправление для даты создания (в JSON: formatted_created_at, в HTML ID: plan-created-at)
            document.getElementById('plan-created-at').textContent = plan.formatted_created_at;

            const biasEl = document.getElementById('plan-bias');
            if (biasEl) biasEl.className = `detail-value plan-bias-tag bias-${plan.bias.toLowerCase()}`;
            
            // Отображение привязанной заметки
            const oldNoteLink = document.getElementById('plan-note-link-container');
            if(oldNoteLink) oldNoteLink.remove();

            if (plan.note_id && plan.note_title) {
                const noteHtml = `
                    <div id="plan-note-link-container" class="detail-item mt-3">
						</br>
                        <span class="detail-label">Linked note:</span>
                        <a href="index.php?view=note_details&id=${plan.note_id}" class="info-badge badge-blue" style="text-decoration:none; width: fit-content;">${plan.note_title}</a>
                    </div>`;
                // Вставляем в конец секции обзора
                const overviewSection = document.querySelector('.plan-overview');
                if (overviewSection) {
                    overviewSection.insertAdjacentHTML('beforeend', noteHtml);
                }
            }

            const tfList = document.getElementById('timeframes-list');
            if (tfList) {
                tfList.innerHTML = '';
                if (plan.timeframes.length) {
                    plan.timeframes.forEach(tf => {
                        tfList.innerHTML += `
                            <div class="timeframe-card">
                                <h3>${tf.title || 'Timeframe'}</h3>
                                ${tf.image_url ? `<img src="${tf.image_url}" class="lightbox-trigger">` : '<p class="text-muted">No image</p>'}
                                ${tf.notes ? `<div class="notes">${tf.notes}</div>` : ''}
                            </div>`;
                    });
                } else { tfList.innerHTML = '<div class="empty-state">No images.</div>'; }
            }
        } else {
            showMessage('Error loading plan details: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error loading plan for edit:', error);
        showMessage('Network error.', 'error');
    }
    finally { if (container) container.style.opacity = '1'; }
}

// ==================================================
// ФУНКЦИИ ДЛЯ СДЕЛОК
// ==================================================

let tradeImgCount = 0;
let isTradeEditMode = false;

async function initTradeForm() {
    const tradeIdInput = document.getElementById('edit-trade-id');
    isTradeEditMode = (tradeIdInput && tradeIdInput.value.trim() !== ""); 
    
    await loadLookups();
    if (isTradeEditMode) {
        await loadTradeDataForEdit(tradeIdInput.value);
    } else {
        addTradeImage();
    }
    setupRRCalculation();
}

function setupRRCalculation() {
    const accountSelect = document.getElementById('trade-account');
    const riskInput = document.getElementById('trade-risk');
    const pnlInput = document.getElementById('trade-pnl');
    const rrInput = document.getElementById('trade-rr-achieved');

    if (!accountSelect || !riskInput || !pnlInput || !rrInput) return;

    const calculate = () => {
        const accountId = accountSelect.value;
        const riskPercent = parseFloat(riskInput.value);
        const pnl = parseFloat(pnlInput.value);
        
        const balance = accountBalances[accountId];

        if (accountId && balance && !isNaN(riskPercent) && !isNaN(pnl) && riskPercent > 0) {
            const riskAmount = balance * (riskPercent / 100);
            if (riskAmount > 0) {
                const rr = pnl / riskAmount;
                rrInput.value = rr.toFixed(2);
            }
        }
    };

    accountSelect.addEventListener('change', calculate);
    riskInput.addEventListener('input', calculate);
    pnlInput.addEventListener('input', calculate);
}

async function loadTradeDataForEdit(tradeId) {
     try {
        const response = await fetch(`api/api.php?action=get_trade_details&id=${tradeId}`);
        const result = await response.json();
        if (result.success) {
            const trade = result.data;
            for (const key in trade) {
                const input = document.querySelector(`[name="${key}"]`);
                if (input) {
                    if (input.type === 'radio') {
                         const radio = document.querySelector(`[name="${key}"][value="${trade[key]}"]`);
                         if (radio) radio.checked = true;
                    } else if (input.type === 'datetime-local' && trade[key]) {
                        input.value = trade[key].replace(' ', 'T').slice(0, 16);
                    } else {
                         input.value = trade[key];
                    }
                }
            }
            if(trade.pair_id) document.getElementById('trade-pair').value = trade.pair_id;
            if(trade.account_id) document.getElementById('trade-account').value = trade.account_id;
            if(trade.style_id) document.getElementById('trade-style').value = trade.style_id;
			if(trade.model_id) document.getElementById('trade-model').value = trade.model_id;
            if(trade.plan_id) document.getElementById('trade-plan').value = trade.plan_id;
            if(trade.status) document.getElementById('trade-status').value = trade.status;
            if(trade.entry_tf) document.getElementById('trade-entry-tf').value = trade.entry_tf;
			if(trade.note_id) document.getElementById('trade-note').value = trade.note_id;

            const container = document.getElementById('trade-images-container');
            container.innerHTML = '';
            if (trade.trade_images && trade.trade_images.length > 0) {
                trade.trade_images.forEach(img => addTradeImage(img));
            } else {
                addTradeImage();
            }
            document.getElementById('form-page-title').textContent = 'Edit trade';
            
            // Триггер пересчета RR
            const event = new Event('input', { bubbles: true });
            const pnlInput = document.getElementById('trade-pnl');
            if (pnlInput) pnlInput.dispatchEvent(event);
            
        } else {
            showMessage('Error loading trade: ' + result.message, 'error');
            window.location.href = 'index.php?view=journal';
        }
    } catch (error) {
        console.error('Error loading trade for edit:', error);
        showMessage('Network error.', 'error');
    }
}

function addTradeImage(data = null) {
    tradeImgCount++;
    const container = document.getElementById('trade-images-container');
    const imgId = `trade-img-${tradeImgCount}`;
    const url = data?.image_url || '';
    const notes = data?.notes || '';
    const title = data?.title || ''; 
    
    const html = `
        <div class="trade-img-card glass-panel" id="${imgId}">
             <div class="d-flex justify-content-between align-items-start mb-3">
                <div style="flex-grow: 1; margin-right: 15px;">
                     <label class="form-label" style="font-size: 0.8em; margin-bottom: 4px;">Timeframe / Context</label>
                     <input type="text" name="trade_images[${tradeImgCount-1}][title]" class="input-field" placeholder="Example: 4H, Entry, Setup" value="${title}">
                </div>
            </div>
            
            <div class="form-group">
                 ${getImageInputHtml(imgId, url, `trade_images[${tradeImgCount-1}][url]`)}
            </div>
            
             <div class="form-group">
                <label class="form-label" style="font-size: 0.8em; margin-bottom: 4px;">Description / Idea</label>
                <textarea class="textarea-field" name="trade_images[${tradeImgCount-1}][notes]" rows="2" placeholder="Describe screenshot...">${notes}</textarea>
            </div>
            
            <div class="text-end mt-2">
                <button type="button" class="btn btn-danger btn-sm" onclick="document.getElementById('${imgId}').remove()">
                    <i class="fas fa-trash-alt me-2"></i> Delete screenshot
                </button>
            </div>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
}

function getImageInputHtml(id, url, name) {
    return `
        <input type="hidden" name="${name}" value="${url}">
        <input type="file" id="${id}-file" class="input-field" style="display:none" onchange="previewImage(this, '${id}-preview')">
        
        <div class="d-flex gap-2 mb-2">
            <button type="button" class="btn btn-outline flex-grow-1" onclick="document.getElementById('${id}-file').click()">
                <i class="fas fa-upload me-2"></i> Upload file
            </button>
        </div>
        
        <input type="text" id="${id}-url" class="input-field mb-2" placeholder="Or paste the link for image" value="${url}" oninput="previewImage(this, '${id}-preview')">
        
        <div id="${id}-preview" class="image-preview-box">
            ${url ? `<img src="${url}" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                     <span class="image-preview-placeholder" style="display:none;">Loading error</span>` 
                  : '<span class="image-preview-placeholder">Image preview</span>'}
        </div>
    `;
}

function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const formGroup = preview.closest('.form-group');
    const hiddenUrlInput = formGroup.querySelector(`input[type="hidden"]`);
    const textUrlInput = formGroup.querySelector(`input[type="text"]`);
    
    if (input.type === 'file' && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { 
            // При загрузке файла сразу показываем его
            preview.innerHTML = `<img src="${e.target.result}">`; 
            if (textUrlInput) textUrlInput.value = ''; 
        };
        reader.readAsDataURL(input.files[0]);
    } else if (input.type === 'text') {
         const val = input.value.trim();
         if (val) {
             // При вставке ссылки используем более надежный способ
             preview.innerHTML = `<img src="${val}" onerror="this.style.display='none'; this.parentElement.querySelector('.err-msg').style.display='block';">
                                  <span class="image-preview-placeholder err-msg" style="display:none; color: var(--accent-red);">Could not load an image</span>`;
             if (hiddenUrlInput) hiddenUrlInput.value = val;
         } else {
             preview.innerHTML = '<span class="image-preview-placeholder">Image preview</span>';
             if (hiddenUrlInput) hiddenUrlInput.value = '';
         }
    }
}


// Обновленная функция loadTrades (использует getTradeRowHtml)
async function loadTrades(filters = {}) {
    const container = document.getElementById('trades-list-container');
    if (!container) return;
    container.innerHTML = getSkeletonHtml('trade-row', 6);

    try {
        const params = new URLSearchParams(filters);
        const response = await fetch(`api/api.php?action=get_trades&${params}`);
        const result = await response.json();

        if (result.success) {
            const groupedTrades = result.data;
            if (groupedTrades.length === 0) {
                 container.innerHTML = '<div class="empty-state">Trades not found.</div>';
                 return;
            }
            
            let html = '';
            groupedTrades.forEach(group => {
                const pnlClass = group.total_pnl >= 0 ? 'text-profit' : 'text-loss';
                const pnlSign = group.total_pnl >= 0 ? '+' : '';
                const pctVal = group.total_percent || 0;
                const pctSign = pctVal >= 0 ? '+' : '';
                
                html += `
                    <div class="month-group">
                        <div class="month-header" onclick="this.parentElement.classList.toggle('collapsed')">
                            <div class="month-label">
                                <i class="fas fa-chevron-right month-toggle-icon"></i>
                                <i class="far fa-calendar-alt text-muted"></i> 
                                ${group.month_label}
                            </div>
                            <div class="month-summary">
                                <div class="sum-item ${pnlClass}">
                                    PnL: ${pnlSign}${group.total_pnl.toFixed(2)}
                                </div>
                                <div class="sum-item ${pnlClass}">
                                    ${pctSign}${pctVal.toFixed(2)}%
                                </div>
                                <div class="sum-item text-main">
                                    RR: ${group.total_rr.toFixed(2)}
                                </div>
                            </div>
                        </div>
                        
                        <div class="trades-list-wrapper">
                            <div class="trades-inner">
                                <div class="trade-row trade-header-row">
                                    <div class="t-col t-date">Date</div>
                                    <div class="t-col t-pair">Pair</div>
                                    <div class="t-col t-account">Account</div>
                                    <div class="t-col t-dir">Dir</div>
                                    <div class="t-col t-status">Status</div>
                                    <div class="t-col t-risk">Risk</div>
                                    <div class="t-col t-rr">RR</div>
                                    <div class="t-col t-pnl">PnL</div>
                                    <div class="t-col t-actions">Actions</div>
                                </div>`;
                
                // ВОТ ЗДЕСЬ ИСПОЛЬЗУЕМ ОБЩУЮ ФУНКЦИЮ
                group.trades.forEach(trade => {
                     html += getTradeRowHtml(trade);
                });
                
                html += '</div></div></div>'; 
            });
            container.innerHTML = html;
        } else { container.innerHTML = `<div class="error-state">Error: ${result.message}</div>`; }
    } catch (error) { console.error(error); container.innerHTML = '<div class="error-state">Loading error.</div>'; }
}

// --- ДЕТАЛИ СДЕЛКИ ---

async function loadTradeDetails() {
    const tradeId = document.getElementById('current-trade-id')?.value;
    if (!tradeId) return;
    const container = document.getElementById('trade-details-container');
    if (container) container.style.opacity = '0.5';
    
    try {
        const response = await fetch(`api/api.php?action=get_trade_details&id=${tradeId}`);
        const result = await response.json();
        if(result.success) {
            const trade = result.data;
            
            // 1. Заголовок и Действия
            document.getElementById('trade-details-title').innerHTML = `${trade.pair_symbol} <span class="dir-tag dir-${trade.direction}" style="font-size: 0.6em; vertical-align: middle;">${trade.direction.toUpperCase()}</span>`;
            const editBtn = document.querySelector('.trade-actions .btn-secondary');
            const deleteBtn = document.querySelector('.trade-actions .btn-danger');
            if (editBtn) editBtn.onclick = () => window.location.href = `index.php?view=trade_create&id=${trade.id}`;
            if (deleteBtn) deleteBtn.onclick = () => deleteEntity(trade.id, 'delete_trade', 'journal');
            
            // 2. Отображение ДАТ (dd.mm.yy)
            ['entry_date', 'exit_date'].forEach(key => {
                 const el = document.getElementById(`trade-${key}`);
                 if(el && trade[key]) {
                     const dateObj = new Date(trade[key]);
                     const day = String(dateObj.getDate()).padStart(2, '0');
                     const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                     const year = String(dateObj.getFullYear()).slice(-2); 
                     el.textContent = `${day}.${month}.${year}`;
                 }
            });
            
            // 3. Расчет ДЛИТЕЛЬНОСТИ
            const durationEl = document.getElementById('trade-duration');
            if (trade.entry_date && trade.exit_date) {
                const entry = new Date(trade.entry_date);
                const exit = new Date(trade.exit_date);
                const diffMs = exit - entry; 
                
                const totalHours = Math.floor(diffMs / (1000 * 60 * 60));
                const days = Math.floor(totalHours / 24);
                const hours = totalHours % 24;
                
                let durationText = '';
                if (days > 0) durationText += `${days}d `;
                if (hours > 0) durationText += `${hours}h`;
                if (days === 0 && hours === 0) durationText = '< 1 hour';
                
                durationEl.textContent = durationText.trim();
                // ИСПРАВЛЕНО: Добавляем стиль бейджа
                durationEl.className = 'detail-value info-badge badge-neutral';
            } else {
                durationEl.textContent = trade.exit_date ? 'No entry date' : 'In progress';
                durationEl.className = 'detail-value'; // Без фона, если нет данных
            }
            
            // 4. Отображение остальных полей
            ['pair_symbol', 'account_name', 'style_name', 'model_name', 'risk_percent', 'rr_achieved', 
             'pnl', 'status', 'trade_conclusions', 'key_lessons',
             'notes', 'mistakes_made', 'emotional_state']
            .forEach(key => {
                const el = document.getElementById(`trade-${key.replace('formatted_', '')}`);
                if (el) {
                    if (key === 'pnl' || key === 'rr_achieved') {
                        const val = parseFloat(trade[key]);
                        el.textContent = val.toFixed(2) + (key === 'risk_percent' ? '%' : (key === 'rr_achieved' ? 'R' : ''));
                        el.className = 'detail-value info-badge ' + (val >= 0 ? 'badge-profit' : 'badge-loss');
                        if (key === 'risk_percent') el.textContent += '%';
                    
                    } else if (key === 'status') {
                        const val = trade[key].toLowerCase();
                        el.textContent = val.toUpperCase();
                        el.className = 'detail-value info-badge';
                        if (val === 'win') el.classList.add('badge-profit');
                        else if (val === 'loss') el.classList.add('badge-loss');
                        else if (val === 'breakeven') el.classList.add('badge-blue');
                        else el.classList.add('badge-neutral');
                    
                    } else if (key === 'risk_percent') {
                         el.textContent = `${trade[key]}%`;
                         // ИСПРАВЛЕНО: Добавляем стиль бейджа для Риска
                         el.className = 'detail-value info-badge badge-neutral';
                    
                    } else if (key === 'notes' || key === 'trade_conclusions' || key === 'key_lessons' || key === 'mistakes_made' || key === 'emotional_state') {
                         el.textContent = trade[key] || '-';
                    } else {
                        el.textContent = trade[key] || 'Empty';
                    }
                }
            });
            
            // 5. Таймфрейм
            const entryTfEl = document.getElementById('trade-entry_timeframe');
            if (entryTfEl) {
                entryTfEl.textContent = trade.entry_tf || 'Empty';
            }
            
            // 6. Направление
            const directionEl = document.getElementById('trade-direction');
            if (directionEl) {
                const dir = trade.direction.toLowerCase();
                const isLong = dir === 'long';
                directionEl.textContent = dir.toUpperCase();
                directionEl.className = 'info-badge'; 
                if (isLong) directionEl.classList.add('badge-profit'); 
                else directionEl.classList.add('badge-loss');   
            }
            
            // 7. Теги
            const tagsEl = document.getElementById('trade-tags');
            if (tagsEl) {
                if (trade.tags) {
                     tagsEl.innerHTML = trade.tags.split(',').map(tag => `<span class="trade-tag">${tag.trim()}</span>`).join('');
                } else {
                     tagsEl.textContent = 'None';
                }
            }
            
            // 8. План
            const planLink = document.getElementById('trade-plan-link');
            if (planLink) {
                if (trade.plan_id && trade.plan_title) {
                    planLink.href = `index.php?view=plan_details&id=${trade.plan_id}`;
                    planLink.textContent = trade.plan_title;
                    // Применяем стили бейджа:
                    planLink.className = 'info-badge badge-blue'; 
                    // Добавляем иконку ссылки для наглядности (опционально)
                    planLink.innerHTML = `<i class="fas fa-solid fa-link me-2"></i> ${trade.plan_title}`;
                } else {
                    planLink.textContent = 'No linked plan';
                    planLink.removeAttribute('href');
                    // Применяем стиль нейтрального бейджа (серый)
                    planLink.className = 'info-badge badge-neutral';
                }
            }
            
            // 9. Скриншоты
            const tradeImgList = document.getElementById('trade-images-list');
            if (tradeImgList) {
                tradeImgList.innerHTML = '';
                if (trade.trade_images && trade.trade_images.length) {
                    trade.trade_images.forEach(img => {
                        tradeImgList.innerHTML += `
                            <div class="trade-image-item">
                                ${img.image_url ? `<img src="${img.image_url}" class="lightbox-trigger">` : '<p class="text-muted">No image</p>'}
                                ${img.notes ? `<div class="notes small text-muted mt-2">${img.notes}</div>` : ''}
                            </div>`;
                    });
                } else { tradeImgList.innerHTML = '<div class="empty-state-small">No screenshots for this trade.</div>'; }
            }

        } else {
            showMessage('Error loading trade details: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error loading trade details for edit:', error);
        showMessage('Network error.', 'error');
    }
    finally { if (container) container.style.opacity = '1'; }
}

async function deleteEntity(id, action, redirectView) {
    if (!confirm('Are you sure? This action can not be undone.')) return;
    try {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('trade_id', id); 
        formData.append('plan_id', id);  
        
        const response = await fetch('api/api.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) window.location.href = `index.php?view=${redirectView}`;
        else showMessage('Delete error: ' + result.message, 'error');
    } catch (e) { console.error(e); showMessage('Network error.', 'error'); }
}

function setupFiltersModal(loadFunction) {
    const modal = document.getElementById('filters-modal');
    const openBtn = document.getElementById('show-filters-btn');
    const closeBtn = document.getElementById('filters-close-btn');
    const form = document.getElementById('filters-form');
    const resetBtn = document.getElementById('reset-filters-btn');
    if (!modal || !openBtn || !form) return;

    openBtn.onclick = async () => { 
        modal.style.display = "block"; 
        const filterPairSelect = document.getElementById('filter-pair');
        if(filterPairSelect && filterPairSelect.options.length <= 1) {
            await loadLookups();
        }
    };
    const close = () => modal.style.display = "none";
    if (closeBtn) closeBtn.onclick = close;
    window.onclick = e => { if(e.target === modal) close(); };
    
    form.onsubmit = e => {
        e.preventDefault();
        const filters = {};
        form.querySelectorAll('select, input').forEach(input => {
            if (input.value) {
                const paramName = input.id.replace('filter-', '').replace('pair', 'pair_id');
                filters[paramName] = input.value;
            }
        });
        loadFunction(filters);
        close();
    };
    
    if(resetBtn) resetBtn.onclick = () => { form.reset(); loadFunction({}); close(); };
}

function setupLightbox() {
    const modal = document.getElementById('image-modal');
    if (!modal) return;
    const modalImg = document.getElementById('modal-image');
    const closeBtn = modal.querySelector('.modal-close');

    document.addEventListener('click', e => {
        // Проверяем: либо класс lightbox-trigger, либо это картинка внутри заметки
        const isNoteImage = e.target.tagName === 'IMG' && e.target.closest('#note-content-display');
        
        if (e.target.classList.contains('lightbox-trigger') || isNoteImage) {
            modal.style.display = "flex"; 
            modalImg.src = e.target.src;
            document.body.style.overflow = 'hidden';
        }
    });

    const close = () => { 
        modal.style.display = "none"; 
        document.body.style.overflow = ''; 
    };

    if (closeBtn) closeBtn.onclick = close;
    modal.onclick = e => { if(e.target === modal) close(); };
}

// Обновленная функция метрик со Скелетонами
async function loadDashboardMetrics(overrideAccountId = null, isDetailedView = false) {
    try {
        let accountId, year, month;

        // Если это детальный просмотр, берем переданный ID
        if (isDetailedView && overrideAccountId) {
            accountId = overrideAccountId;
            year = ''; 
            month = '';
        } else {
            accountId = document.getElementById('dashboard-account-select')?.value || '';
            year = document.getElementById('dashboard-year-select')?.value || '';
            month = document.getElementById('dashboard-month-select')?.value || '';
        }

        // --- НАЧАЛО: ВСТАВКА СКЕЛЕТОНОВ ---
        // Определяем ID элементов в зависимости от страницы
        const idMap = isDetailedView ? {
            total_trades: 'ad-total-trades',
            breakdown: 'ad-trades-breakdown',
            win_rate: 'ad-winrate',
            avg_rr: 'ad-avg-rr',
            pnl: 'ad-pnl-value',
        } : {
            total_trades: 'total-trades-value',
            breakdown: 'total-trades-breakdown',
            win_rate: 'winning-ratio-value',
            avg_rr: 'average-rr-value',
            pnl: 'net-profit-value',
            monthly: 'avg-monthly-profit',
            mdd: 'max-drawdown-value',
            avg_time: 'avg-time-in-position-value'
        };

        // Функция быстрой вставки скелетона, если элемент существует
        const showSkel = (id, type) => {
            const el = document.getElementById(id);
            if(el) el.innerHTML = getSkeletonHtml(type);
        };

        // Запускаем скелетоны по всем метрикам
        showSkel(idMap.total_trades, 'metric');
        showSkel(idMap.breakdown, 'text-line');
        showSkel(idMap.win_rate, 'metric');
        showSkel(idMap.avg_rr, 'metric');
        showSkel(idMap.pnl, 'metric');

        if (!isDetailedView) {
             showSkel(idMap.monthly, 'text-line');
             showSkel(idMap.mdd, 'metric');
             showSkel(idMap.avg_time, 'metric');
        }
        // --- КОНЕЦ: ВСТАВКА СКЕЛЕТОНОВ ---

        const params = new URLSearchParams({
            action: 'get_dashboard_metrics',
            account_id: accountId,
            year: year,
            month: month
        });

        const response = await fetch(`api/api.php?${params}`);
        const result = await response.json();
        
        if (result.success) {
            const m = result.data;
            
            // Карта ID элементов (повторно объявляем или используем ту же логику для заполнения)
            // Чтобы не дублировать код карты ID, используем ту же структуру idMap, что объявили выше
            
            // Обновляем значения в HTML
            if(document.getElementById(idMap.total_trades)) 
                document.getElementById(idMap.total_trades).textContent = m.total_trades;

            if(document.getElementById(idMap.breakdown))
                document.getElementById(idMap.breakdown).innerHTML = 
                    `<span class="text-profit">${m.wins} W</span> / 
                     <span class="text-loss">${m.losses} L</span> / 
                     <span class="text-warning">${m.breakeven} B</span> /
                     <span class="text-info">${m.pending || 0} P</span>`;
            
            if(document.getElementById(idMap.win_rate))
                document.getElementById(idMap.win_rate).textContent = m.win_rate + '%';
            
            // Бар винрейта (скелетон не нужен, просто анимация CSS)
            const winBarId = isDetailedView ? 'ad-winrate-bar' : 'winning-ratio-progress';
            if(document.getElementById(winBarId))
                document.getElementById(winBarId).style.width = m.win_rate + '%';

            if(document.getElementById(idMap.avg_rr))
                document.getElementById(idMap.avg_rr).textContent = m.avg_rr_per_trade + ' R';

            // PnL
            if(document.getElementById(idMap.pnl)) {
                 const val = m.total_pnl;
                 const el = document.getElementById(idMap.pnl);
                 const text = (val >= 0 ? '+ ' : '') + val.toFixed(2);
                 el.innerHTML = text + ' $';
                 el.classList.remove('text-profit', 'text-loss');
                 el.classList.add(val >= 0 ? 'text-profit' : 'text-loss');
            }

            // Дополнительные поля только для Главного дашборда
            if (!isDetailedView) {
                document.getElementById(idMap.monthly).innerHTML = `Monthly average: ${m.avg_monthly_profit} $`;
                document.getElementById(idMap.avg_time).textContent = m.avg_time_in_position;
                const mddEl = document.getElementById(idMap.mdd);
                if(mddEl) {
                    mddEl.innerHTML = `-${m.max_drawdown_pct}% (-${m.max_drawdown_abs} $)`;
                    mddEl.className = 'metric-value text-loss';
                }
            }

            // Рисуем график
            const chartId = isDetailedView ? 'accountEquityChart' : 'equityChart';
            if (m.equity_chart) {
                renderEquityChart(m.equity_chart, chartId);
            }
        }
    } catch (e) { console.error(e); }
}

// Функция заполнения лет
function populateDateFilters() {
    const yearSelect = document.getElementById('dashboard-year-select');
    if (!yearSelect) return;
    
    const currentYear = new Date().getFullYear();
    // Очищаем, оставляя первую опцию
    while (yearSelect.options.length > 1) yearSelect.remove(1);
    
    // Добавляем годы от текущего до 2020
    for (let y = currentYear; y >= 2020; y--) {
        const opt = document.createElement('option');
        opt.value = y;
        opt.textContent = y;
        yearSelect.appendChild(opt);
    }
}

// Исправленная функция рендера графика
function renderEquityChart(dataPoints, canvasId = 'equityChart') {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return; // Если канвас не найден, выходим

    // Управление экземплярами графиков, чтобы они не накладывались
    // Сохраняем инстанс в глобальный объект window под уникальным именем
    const chartInstanceName = canvasId + 'Instance';

    if (window[chartInstanceName]) {
        window[chartInstanceName].destroy();
    }

    const labels = dataPoints.map(pt => pt.x);
    const data = dataPoints.map(pt => pt.y);

    const startBalance = data.length > 0 ? data[0] : 0;
    const currentBalance = data.length > 0 ? data[data.length - 1] : 0;
    const lineColor = currentBalance >= startBalance ? '#00d66f' : '#ff453a'; 
    const areaColor = currentBalance >= startBalance ? 'rgba(0, 214, 111, 0.1)' : 'rgba(255, 69, 58, 0.1)';

    window[chartInstanceName] = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Balance',
                data: data,
                borderColor: lineColor,
                backgroundColor: areaColor,
                borderWidth: 2,
                pointRadius: 0, 
                pointHoverRadius: 4,
                fill: true,
                tension: 0.4 
            }]
        },
        options: {
            responsive: true, 
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }, 
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(9, 12, 20, 0.9)',
                    titleColor: '#9ca3af',
                    bodyColor: '#fff',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return 'Balance: ' + context.parsed.y.toFixed(2) + ' $';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: { 
                        color: '#6b7280', 
                        maxTicksLimit: 6, 
                        maxRotation: 0
                    } 
                },
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)', drawBorder: false },
                    ticks: { color: '#6b7280' }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
}

// ==================================================
// ФУНКЦИИ АККАУНТОВ
// ==================================================

function togglePropFields() {
    const typeEl = document.getElementById('acc-type');
    const propBlock = document.getElementById('prop-settings');
    if (!typeEl || !propBlock) return;

    const type = typeEl.value;
    // Скрываем настройки проп-фирмы для Live и Demo
    if (type === 'Live' || type === 'Demo') {
        propBlock.style.display = 'none';
        // Очищаем значения, чтобы не сохранять мусор
        document.getElementById('acc-target').value = 0;
        document.getElementById('acc-dd').value = 0;
    } else {
        propBlock.style.display = 'block';
    }
}

async function loadAccounts() {
    const container = document.getElementById('accounts-grid');
    if(!container) return;
    
    container.innerHTML = getSkeletonHtml('card', 2);
    
    try {
        const res = await fetch('api/api.php?action=get_accounts_data');
        const json = await res.json();
        
        if(json.success) {
            if(json.data.length === 0) {
                container.innerHTML = '<div class="empty-state">No accounts. Add your first one!</div>';
                return;
            }
            
            let html = '';
            json.data.forEach(acc => {
                // Данные из API
                const startEquity = acc.starting_equity; // Например 100,000 (База)
                const currentEquity = acc.calculated_balance; // Например 102,000 (Факт)
                
                const targetPct = acc.target_percent;   // Например 10%
                const maxDDPct = acc.max_drawdown_percent; // Например 10%
                
                // 1. Считаем абсолютную прибыль/убыток относительно СТАРТОВОГО РАЗМЕРА
                // (Не относительно начала журнала, а относительно размера челленджа)
                const totalGainAbs = currentEquity - startEquity;
                
                // 2. Считаем, какой это процент от стартового капитала
                const totalGainPct = (startEquity > 0) ? (totalGainAbs / startEquity) * 100 : 0;
                
                // Оформление текста
                const profitClass = totalGainAbs >= 0 ? 'text-profit' : 'text-loss';
                const profitSign = totalGainAbs >= 0 ? '+' : '';

                // --- ЛОГИКА БАРА ---
                let widthLoss = 0;
                let widthProfit = 0;
                let labelLeft = maxDDPct > 0 ? `Max DD: ${maxDDPct}%` : 'No Limit';
                let labelRight = targetPct > 0 ? `Target: ${targetPct}%` : 'No Target';
                
                if (totalGainAbs >= 0) {
                    // Мы в ПЛЮСЕ от Starting Equity -> Растем ВПРАВО
                    if (targetPct > 0) {
                        // Насколько мы заполнили цель?
                        widthProfit = Math.min((totalGainPct / targetPct) * 100, 100);
                    } else {
                        // Если цели нет (Live), просто показываем небольшую полоску или 0
                        widthProfit = 0; 
                    }
                } else {
                    // Мы в МИНУСЕ от Starting Equity -> Растем ВЛЕВО
                    const currentDrawdownPct = Math.abs(totalGainPct);
                    
                    if (maxDDPct > 0) {
                        // Насколько мы близки к краху?
                        widthLoss = Math.min((currentDrawdownPct / maxDDPct) * 100, 100);
                    }
                }
                
                const barHtml = `
                    <div class="acc-split-bar-container">
                        <div class="acc-split-divider"></div>
                        <div class="acc-bar-left"><div class="acc-fill-loss" style="width: ${widthLoss}%"></div></div>
                        <div class="acc-bar-right"><div class="acc-fill-profit" style="width: ${widthProfit}%"></div></div>
                    </div>
                    <div class="acc-split-labels">
                        <span class="text-loss">${labelLeft}</span>
                        <span style="color:#fff; opacity:0.5; font-size:0.65rem;">Start: $${startEquity.toLocaleString()}</span>
                        <span class="text-profit">${labelRight}</span>
                    </div>`;

                html += `
                <div class="account-card" onclick="window.location.href='index.php?view=account_details&id=${acc.id}'">
                    <div class="acc-actions" onclick="event.stopPropagation()">
                        <a title="Edit" href="index.php?view=account_create&id=${acc.id}" class="acc-btn d-inline-flex align-items-center justify-content-center" style="text-decoration:none;"><i class="fas fa-pen" style="font-size:0.8rem"></i></a>
                        <button title="Delete" class="acc-btn delete" onclick="deleteAccount(${acc.id})"><i class="fas fa-trash" style="font-size:0.8rem"></i></button>
                    </div>
                
                    <div class="acc-header">
                        <div class="acc-name"><i class="fas fa-wallet" style="color:var(--accent-blue)"></i> ${acc.name}</div>
                        <span class="acc-type-badge">${acc.type}</span>
                    </div>
                    
                    <div class="acc-balance">$${parseFloat(currentEquity).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                    
                    <div style="font-size: 0.9rem; margin-bottom: 5px;" class="${profitClass}">
                        ${profitSign}${totalGainAbs.toFixed(2)}$ (${profitSign}${totalGainPct.toFixed(2)}%)
                    </div>
                    
                    ${barHtml}
                    
                    <div class="acc-stats-grid">
                        <div class="acc-stat-row"><span>Trades:</span><span class="acc-stat-val">${acc.total_trades}</span></div>
                        <div class="acc-stat-row"><span>Winrate:</span><span class="acc-stat-val">${acc.total_trades > 0 ? ((acc.wins/acc.total_trades)*100).toFixed(1) : 0}%</span></div>
                        <div class="acc-stat-row"><span>Avg RR:</span><span class="acc-stat-val">${acc.avg_rr}R</span></div>
                        <div class="acc-stat-row"><span>Journal PnL:</span><span class="acc-stat-val ${acc.profit >=0 ? 'text-profit':'text-loss'}">${acc.profit >=0?'+':''}${acc.profit.toFixed(2)}$</span></div>
                    </div>
                </div>`;
            });
            
            container.innerHTML = html;
        }
    } catch(e) { console.error(e); }
}

async function deleteAccount(id) {
    if(!confirm('Delete this account and all its data? This action can not be undone.')) return;
    const fd = new FormData(); fd.append('id', id);
    try {
        const res = await fetch('api/api.php?action=delete_account', {method:'POST', body:fd});
        const json = await res.json();
        if(json.success) {
            showToast('Account deleted successfully', 'success'); // <-- TOAST
            
            if (window.location.search.includes('view=account_details')) {
                window.location.href = 'index.php?view=accounts';
            } else {
                loadAccounts();
            }
        } else {
            showToast('Delete error: ' + json.message, 'error'); // <-- TOAST
        }
    } catch(e) { 
        console.error(e); 
        showToast('Network error', 'error'); 
    }
}

// Инициализация формы создания/редактирования
async function initAccountForm() {
    const idEl = document.getElementById('edit-acc-id');
    const form = document.getElementById('account-form');
    
    if(idEl && idEl.value) {
        try {
            const res = await fetch(`api/api.php?action=get_account_details&id=${idEl.value}`);
            const json = await res.json();
            if(json.success) {
                const d = json.data;
                document.getElementById('acc-name').value = d.name;
                document.getElementById('acc-type').value = d.type;
                // Заполняем Starting и Balance отдельно
                document.getElementById('acc-starting').value = d.starting_equity;
                document.getElementById('acc-balance').value = d.balance;
                
                document.getElementById('acc-target').value = d.target_percent;
                document.getElementById('acc-dd').value = d.max_drawdown_percent;
                
                togglePropFields();
            }
        } catch(e) { console.error(e); }
    } else {
        togglePropFields();
    }

    if(form) {
        form.onsubmit = async (e) => {
            e.preventDefault();
            const data = {
                id: document.getElementById('edit-acc-id').value,
                name: document.getElementById('acc-name').value,
                type: document.getElementById('acc-type').value,
                // Отправляем оба значения
                starting_equity: document.getElementById('acc-starting').value,
                balance: document.getElementById('acc-balance').value,
                
                target_percent: document.getElementById('acc-target').value,
                max_drawdown_percent: document.getElementById('acc-dd').value
            };
            
            try {
                const res = await fetch('api/api.php?action=save_account', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });
                const json = await res.json();
                if(json.success) {
                    window.location.href = 'index.php?view=accounts';
                } else {
                    showMessage('Error ' + json.message, 'error');
                }
            } catch(err) {
                showMessage('Network error', 'error');
            }
        };
    }
}

// ==================================================
// ФУНКЦИИ ВЫПЛАТ (PAYOUTS)
// ==================================================

async function loadPayouts() {
    const container = document.getElementById('payouts-list-container');
    if (!container) return;

    try {
        const res = await fetch('api/api.php?action=get_payouts');
        const json = await res.json();

        if (json.success) {
            if (json.data.length === 0) {
                container.innerHTML = `
                    <div class="glass-panel p-4 text-center text-muted">
                        <i class="fas fa-money-bill-wave mb-2" style="font-size: 2rem; opacity: 0.3;"></i>
                        <p>No payout history</p>
                    </div>`;
                return;
            }

            // Шапка (видна только на ПК благодаря CSS)
            let html = `
                <div class="payouts-grid">
                    <div class="payout-header-row">
                        <div>Date</div>
                        <div>Account</div>
                        <div>Status</div>
                        <div style="text-align: right;">Amount</div>
                        <div style="text-align: right;">Actions</div>
                    </div>`;

            let totalPayouts = 0;

            json.data.forEach(p => {
                const dateObj = new Date(p.payout_date);
                const date = dateObj.toLocaleDateString();
                const amount = parseFloat(p.amount);
                
                if(p.confirmation_status === 'Paid') totalPayouts += amount;

                let statusBadge = '';
                if (p.confirmation_status === 'Paid') statusBadge = '<span class="status-tag status-win">Paid</span>';
                else if (p.confirmation_status === 'Rejected') statusBadge = '<span class="status-tag status-loss">Rejected</span>';
                else statusBadge = '<span class="status-tag status-pending">Requested</span>';

                // Генерируем КАРТОЧКУ (DIV)
                html += `
                    <div class="payout-card">
                        
                        <div class="payout-col" data-label="Date">
                            <span class="text-muted"><i class="far fa-calendar-alt me-2"></i> ${date}</span>
                        </div>
                        
                        <div class="payout-col" data-label="Account">
                            <strong>${p.account_name}</strong>
                        </div>
                        
                        <div class="payout-col" data-label="Status">
                            ${statusBadge}
                        </div>
                        
                        <div class="payout-col" data-label="Amount" style="text-align: right;">
                            <span style="color: var(--accent-green); font-weight: 700; font-size: 1.1rem;">
                                +$${amount.toLocaleString(undefined, {minimumFractionDigits: 2})}
                            </span>
                        </div>
                        
                        <div class="payout-col payout-actions" style="text-align: right;">
                            <button class="acc-btn" style="width:32px; height:32px;" onclick="editPayout(${p.id}, '${p.account_id}', '${p.amount}', '${p.payout_date}', '${p.confirmation_status}')" title="Edit">
                                <i class="fas fa-pen" style="font-size: 0.8rem;"></i>
                            </button>
                            <button class="acc-btn delete" style="width:32px; height:32px;" onclick="deletePayout(${p.id})" title="Delete">
                                <i class="fas fa-trash" style="font-size: 0.8rem;"></i>
                            </button>
                        </div>
                        
                    </div>`;
            });

            // Футер с итогом
            html += `
                <div style="padding: 20px; text-align: right; font-size: 0.95rem; color: var(--text-secondary); margin-top: 10px;">
                    Total Paid: <span style="color: var(--text-main); font-weight: 700; font-size: 1.2rem;">$${totalPayouts.toLocaleString()}</span>
                </div>
            </div>`; // Закрываем payouts-grid
            
            container.innerHTML = html;
        }
    } catch (e) { console.error(e); }
}

// Модальное окно Выплат

function openPayoutModal() {
    const modal = document.getElementById('payout-modal');
    if (modal) {
        modal.style.display = 'flex'; // Используем flex для центрирования (если в CSS есть align-items: center)
        // Или modal.style.display = 'block'; если flex не используется в CSS для .modal
        
        document.getElementById('payout-form').reset();
        document.getElementById('payout-id').value = '';
        document.getElementById('payout-date').valueAsDate = new Date();
        document.getElementById('payout-modal-title').textContent = 'Add payout';
        
        // Загружаем список счетов в селект
        loadLookups().then(data => {
            if(data && data.accounts) {
                populateSelect('payout-account', data.accounts, 'name');
            }
        });
    }
}

function closePayoutModal() {
    const modal = document.getElementById('payout-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Закрытие по клику вне окна
window.onclick = function(event) {
    const modal = document.getElementById('payout-modal');
    if (event.target == modal) {
        closePayoutModal();
    }
}

function editPayout(id, accId, amount, date, status) {
    openPayoutModal();
    document.getElementById('payout-id').value = id;
    // Ждем загрузки селекта (костыль, но быстрый), либо используем уже загруженный
    setTimeout(() => { document.getElementById('payout-account').value = accId; }, 100);
    document.getElementById('payout-amount').value = amount;
    document.getElementById('payout-date').value = date;
    document.getElementById('payout-status').value = status;
    document.getElementById('payout-modal-title').textContent = 'Edit payout';
}

async function deletePayout(id) {
    if(!confirm('Delete payout?')) return;
    const fd = new FormData(); fd.append('id', id);
    await fetch('api/api.php?action=delete_payout', {method:'POST', body:fd});
    loadPayouts();
}

// --- ЛОГИКА СТРАНИЦЫ ДЕТАЛЕЙ АККАУНТА ---

async function loadAccountDetailsPage(id) {
    if (!id) return;
    try {
        // 1. Загружаем инфо об аккаунте
        const res = await fetch(`api/api.php?action=get_account_details&id=${id}`);
        const json = await res.json();
        
        if (json.success) {
            const d = json.data;
            document.getElementById('ad-name').textContent = d.name;
            document.getElementById('ad-type').textContent = d.type;
            
            // Получаем баланс и PnL из общего списка (для точности расчета)
            const resStats = await fetch('api/api.php?action=get_accounts_data');
            const jsonStats = await resStats.json();
            const accStats = jsonStats.data.find(a => a.id == id);
            
            if (accStats) {
                const bal = parseFloat(accStats.calculated_balance);
                const start = parseFloat(accStats.starting_equity);
                const profitAbs = bal - start;
                const profitPct = start > 0 ? (profitAbs / start) * 100 : 0;
                
                document.getElementById('ad-balance').textContent = '$ ' + bal.toLocaleString('en-US', {minimumFractionDigits: 2});
                const profAbsEl = document.getElementById('ad-profit-abs');
                profAbsEl.textContent = (profitAbs >= 0 ? '+' : '') + profitAbs.toFixed(2) + ' $';
                profAbsEl.className = profitAbs >= 0 ? 'text-profit' : 'text-loss';
                document.getElementById('ad-profit-pct').textContent = `(${profitPct.toFixed(2)} %)`;
                
                // Рендер бара прогресса (используем HTML строку, как в списке)
                renderAccountProgressBarDOM(accStats, 'ad-progress-container');
            }

            // Кнопки
            document.getElementById('btn-edit-account').onclick = () => window.location.href = `index.php?view=account_create&id=${d.id}`;
            document.getElementById('btn-add-trade-account').onclick = () => window.location.href = `index.php?view=trade_create&account_id=${d.id}`;
        }
        
        // 2. Загружаем Метрики и График (для этого аккаунта)
        loadDashboardMetrics(id, true);
        
        // 3. Загружаем Трейды (фильтруем по этому аккаунту)
        loadTrades({ account_id: id });
        
    } catch (e) { console.error(e); }
}

function initAccountTabs(accountId) {
    const tabs = document.querySelectorAll('.tab-btn');
    const contents = document.querySelectorAll('.tab-content');
    let payoutsLoaded = false; // Флаг, чтобы не грузить выплаты сто раз

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));
            
            tab.classList.add('active');
            const targetId = tab.getAttribute('data-tab');
            document.getElementById(`tab-${targetId}`).classList.add('active');
            
            // Если нажали "Выплаты" и еще не грузили их
            if (targetId === 'payouts' && !payoutsLoaded) {
                loadAccountPayouts(accountId);
                payoutsLoaded = true;
            }
        });
    });
}

// Загрузка выплат с фильтром на клиенте
async function loadAccountPayouts(accountId) {
    const container = document.getElementById('account-payouts-list-container');
    if (!container) return;

    try {
        const res = await fetch('api/api.php?action=get_payouts');
        const json = await res.json();

        if (json.success) {
            // ФИЛЬТР: Берем только выплаты текущего аккаунта
            const accountPayouts = json.data.filter(p => p.account_id == accountId);

            if (accountPayouts.length === 0) {
                container.innerHTML = `<div class="glass-panel p-4 text-center text-muted"><p>No payouts</p></div>`;
                return;
            }

            let html = '<div class="payouts-grid">';
            accountPayouts.forEach(p => {
                const date = new Date(p.payout_date).toLocaleDateString();
                const amount = parseFloat(p.amount);
                let statusBadge = '<span class="status-tag status-pending">Requested</span>';
                if (p.confirmation_status === 'Paid') statusBadge = '<span class="status-tag status-win">Paid</span>';
                else if (p.confirmation_status === 'Rejected') statusBadge = '<span class="status-tag status-loss">Rejected</span>';

                html += `
                    <div class="payout-card" style="grid-template-columns: 1fr 1fr 1fr 80px;">
                        <div class="payout-col"><span class="text-muted">${date}</span></div>
                        <div class="payout-col">${statusBadge}</div>
                        <div class="payout-col" style="text-align: right;"><span style="color: var(--accent-green); font-weight: 700;">+$${amount.toLocaleString()}</span></div>
                         <div class="payout-col payout-actions" style="text-align: right;">
                            <button class="acc-btn" style="width:32px; height:32px;" onclick="editPayout(${p.id}, '${p.account_id}', '${p.amount}', '${p.payout_date}', '${p.confirmation_status}')"><i class="fas fa-pen" style="font-size: 0.8rem;"></i></button>
                        </div>
                    </div>`;
            });
            html += '</div>';
            container.innerHTML = html;
        }
    } catch (e) { container.innerHTML = '<div class="error-state">Error</div>'; }
}

function renderAccountProgressBarDOM(acc, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const start = parseFloat(acc.starting_equity);
    const target = parseFloat(acc.target_percent);
    const maxDD = parseFloat(acc.max_drawdown_percent);
    const bal = parseFloat(acc.calculated_balance);
    const gainAbs = bal - start;
    const gainPct = start > 0 ? (gainAbs / start) * 100 : 0;
    
    let wLoss = 0, wProfit = 0;
    if (gainAbs >= 0) { if (target > 0) wProfit = Math.min((gainPct / target) * 100, 100); }
    else { const dd = Math.abs(gainPct); if (maxDD > 0) wLoss = Math.min((dd / maxDD) * 100, 100); }
    
    container.innerHTML = `
        <div class="acc-split-bar-container">
            <div class="acc-split-divider"></div>
            <div class="acc-bar-left"><div class="acc-fill-loss" style="width: ${wLoss}%"></div></div>
            <div class="acc-bar-right"><div class="acc-fill-profit" style="width: ${wProfit}%"></div></div>
        </div>
        <div class="acc-split-labels">
            <span class="text-loss">${maxDD > 0 ? 'Max DD: '+maxDD+'%' : ''}</span>
            <span class="text-profit">${target > 0 ? 'Target: '+target+'%' : ''}</span>
        </div>`;
}

// --- DATA ANALYSIS FUNCTIONS ---

async function loadDataAnalysis() {
    // ДОБАВЛЕНО: Заполняем все списки скелетонами
    ['list-direction', 'list-style', 'list-timeframe', 'list-model'].forEach(id => {
        const el = document.getElementById(id);
        if(el) el.innerHTML = getSkeletonHtml('list-item', 4);
    });
    
    // Для списка пар (обычно там плитки или строки)
    const listPairs = document.getElementById('list-pairs');
    if(listPairs) listPairs.innerHTML = getSkeletonHtml('list-item', 4);

    try {
        const res = await fetch('api/api.php?action=get_data_analysis');
        const json = await res.json();

        if (json.success) {
            const d = json.data;
            renderDataList('list-direction', d.direction);
            renderDataList('list-style', d.style);
            renderDataList('list-timeframe', d.timeframe);
            renderDataList('list-model', d.model);
            renderPairsGrid('list-pairs', d.pairs);
        }
    } catch (e) { console.error(e); }
}

function renderDataList(containerId, items) {
    const container = document.getElementById(containerId);
    if (!container) return;

    if (!items || items.length === 0) {
        container.innerHTML = '<div class="text-muted small">No data available</div>';
        return;
    }

    let html = '';
    items.forEach(item => {
        const label = item.label || 'N/A';
        const count = item.total_trades;
        const winrate = parseInt(item.win_rate);
        
        // Логика цвета: серый если 0 сделок или 0% винрейт, красный если убыток, зеленый если > 50%
        // Если сделок 0, цвет круга будет серым
        let color = '#6b7280'; // gray default
        if (count > 0) {
             if (winrate >= 50) color = '#00d66f'; // green
             else color = '#ff453a'; // red
        }

        const strokeDash = `${winrate}, 100`;
        // Подсказка для тултипа
        const tooltipText = `Winrate: ${winrate}%`;

        html += `
        <div class="data-row">
            <div class="data-label-group">
                ${getLabelIcon(label)}
                <span>${label}</span>
            </div>
            <div class="data-stats-group">
                <span class="data-count">${count} Trades</span>
                
                <div title="${tooltipText}" style="display: flex; align-items: center; gap: 6px; cursor: help;">
                    <span style="font-weight:700; font-size:0.8rem; color:${color}; width: 35px; text-align:right;">${winrate}%</span>
                    <svg viewBox="0 0 36 36" class="circular-chart">
                        <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="circle" stroke="${color}" stroke-dasharray="${strokeDash}" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                </div>
            </div>
        </div>`;
    });
    container.innerHTML = html;
}

function renderPairsGrid(containerId, items) {
    const container = document.getElementById(containerId);
    if (!container) return;

    if (!items || items.length === 0) {
        container.innerHTML = '<div class="text-muted small">No trades yet</div>';
        return;
    }

    let html = '';
    items.forEach(item => {
        const winrate = parseInt(item.win_rate);
        const count = item.total_trades;
        
        let color = '#6b7280';
        if (count > 0) {
            if (winrate >= 50) color = '#00d66f';
            else color = '#ff453a';
        }
        
        const strokeDash = `${winrate}, 100`;
        const tooltipText = `Winrate: ${winrate}%`;

        html += `
        <div class="pair-stat-card">
            <div style="font-weight:700; font-size: 1rem;">${item.label}</div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <span class="data-count">${count} T</span>
                
                <div title="${tooltipText}" style="display: flex; align-items: center; gap: 5px; cursor: help;">
                    <span style="font-weight:700; font-size:0.8rem; color:${color};">${winrate}%</span>
                    <svg viewBox="0 0 36 36" class="circular-chart">
                        <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="circle" stroke="${color}" stroke-dasharray="${strokeDash}" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                </div>
            </div>
        </div>`;
    });
    container.innerHTML = html;
}

function getLabelIcon(label) {
    const l = label.toLowerCase();
    if(l === 'long') return '<i class="fas fa-arrow-up text-profit" style="font-size: 0.8rem;"></i>';
    if(l === 'short') return '<i class="fas fa-arrow-down text-loss" style="font-size: 0.8rem;"></i>';
    return '';
}

function getIconForLabel(label) {
    const l = label.toLowerCase();
    if(l === 'long') return '<i class="fas fa-arrow-up text-profit small"></i>';
    if(l === 'short') return '<i class="fas fa-arrow-down text-loss small"></i>';
    if(l.includes('day')) return '<i class="fas fa-sun text-warning small"></i>'; // Intraday
    if(l.includes('swing')) return '<i class="fas fa-history text-info small"></i>'; // Swing
    return '';
}

// ==================================================
// STRATEGY FUNCTIONS
// ==================================================

async function loadStrategies() {
    const container = document.getElementById('strategy-grid');
    if (!container) return;
    container.innerHTML = `<div class="row">${getSkeletonHtml('card', 3)}</div>`;

    try {
        const res = await fetch('api/api.php?action=get_strategies');
        const json = await res.json();

        if (json.success) {
            if (json.data.length === 0) {
                container.innerHTML = '<div class="empty-state">No strategy modules yet. Add one!</div>';
                return;
            }

            let html = '';
            json.data.forEach(item => {
                // Выбираем цвет иконки рандомно или на основе названия (для красоты)
                let iconColorClass = 'green';
                if(item.title.includes('Risk')) iconColorClass = 'yellow';
                if(item.title.includes('Execution') || item.title.includes('Trade')) iconColorClass = 'blue';

                html += `
                <a href="index.php?view=strategy_details&id=${item.id}" class="strategy-card">
                    <div class="st-card-header">
                        <div class="st-icon ${iconColorClass}">
                            <i class="${item.icon}"></i>
                        </div>
                        <div class="st-title">${item.title}</div>
                    </div>
                    <div class="st-desc">${item.description || ''}</div>
                </a>`;
            });
            container.innerHTML = html;
        }
    } catch (e) { console.error(e); }
}

async function initStrategyForm() {
    const idEl = document.getElementById('edit-strategy-id');
    
    if (document.getElementById('editor-container')) {
        document.getElementById('editor-container').innerHTML = ''; 
        quillEditor = new Quill('#editor-container', {
            theme: 'snow',
            placeholder: 'Describe your strategy rules here...',
            modules: {
                toolbar: fullToolbarOptions // Используем ту же полную конфигурацию
            }
        });
    }

    if(idEl && idEl.value) {
        const res = await fetch(`api/api.php?action=get_strategy_details&id=${idEl.value}`);
        const json = await res.json();
        if(json.success) {
            const d = json.data;
            document.getElementById('st-title').value = d.title;
            document.getElementById('st-icon').value = d.icon;
            document.getElementById('st-desc').value = d.description;
            if(quillEditor) quillEditor.clipboard.dangerouslyPasteHTML(d.content || '');
        }
    }
    
    const form = document.getElementById('strategy-form');
    if(form) {
        form.onsubmit = (e) => handleFormSubmit(e, 'save_strategy', 'strategy', 'strategy');
    }
}

async function loadStrategyDetails() {
    const id = document.getElementById('current-strategy-id')?.value;
    if (!id) return;

    try {
        const res = await fetch(`api/api.php?action=get_strategy_details&id=${id}`);
        const json = await res.json();
        if (json.success) {
            const d = json.data;
            document.getElementById('st-detail-title').textContent = d.title;
            document.getElementById('st-detail-desc').textContent = d.description;
            document.getElementById('st-content-display').innerHTML = d.content;
            
            // Иконка
            const iconContainer = document.getElementById('st-detail-icon');
            iconContainer.innerHTML = `<i class="${d.icon}"></i>`;
            // Цвет иконки
            iconContainer.classList.remove('green', 'yellow', 'blue');
            if(d.title.includes('Risk')) iconContainer.classList.add('yellow');
            else if(d.title.includes('Execution')) iconContainer.classList.add('blue');
            else iconContainer.classList.add('green');

            document.getElementById('btn-edit-strategy').onclick = () => window.location.href = `index.php?view=strategy_create&id=${d.id}`;
			document.getElementById('btn-delete-strategy').onclick = () => deleteStrategy(d.id);
        }
    } catch (e) { console.error(e); }
}

async function deleteStrategy(id) {
    if(!confirm('Are you sure you want to delete this module?')) return;
    const fd = new FormData(); fd.append('id', id);
    await fetch('api/api.php?action=delete_strategy', {method:'POST', body:fd});
    window.location.href='index.php?view=strategy';
}

;
        opt.textContent = y;
        yearSelect.appendChild(opt);
    }

    // При смене года перезагружаем
    yearSelect.addEventListener('change', () => {
        loadMPAData(yearSelect.value);
    });

  // --- ЛОГИКА MPA (Monthly Performance Analysis) ---

let currentMpaData = null; // Глобальная переменная для хранения данных загруженного года

// 1. Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('mpa-dynamic-container')) {
        initMPA();
    }
});

function initMPA() {
    const yearSelect = document.getElementById('mpa-year-select');
    const quarterSelect = document.getElementById('mpa-quarter-select');
    const currentYear = new Date().getFullYear();
    
    // Заполняем селект годами
    if (yearSelect) {
        yearSelect.innerHTML = '';
        for (let y = currentYear; y >= currentYear - 3; y--) {
            const opt = document.createElement('option');
            opt.value = y;
            opt.textContent = y;
            yearSelect.appendChild(opt);
        }
        
        // Слушатель: при смене года грузим данные с сервера
        yearSelect.addEventListener('change', (e) => loadMPAData(e.target.value));
    }

    // Слушатель: при смене квартала просто перерисовываем уже загруженные данные
    if (quarterSelect) {
        quarterSelect.addEventListener('change', () => {
            if (currentMpaData && yearSelect) {
                renderMPAGrid(currentMpaData, document.getElementById('mpa-dynamic-container'), yearSelect.value);
            }
        });
    }

    // Первичная загрузка
    loadMPAData(currentYear);
}

// 2. Функция загрузки
async function loadMPAData(year) {
    const container = document.getElementById('mpa-dynamic-container');
    container.innerHTML = `<div class="text-center py-5"><div class="loading-spinner"></div> Loading Analysis...</div>`;

    try {
        const response = await fetch(`api/api.php?action=get_mpa_analysis&year=${year}`);
        const text = await response.text(); 

        let result;
        try {
            result = JSON.parse(text); 
        } catch (e) {
            console.error("Server response:", text);
            container.innerHTML = `<div class="alert alert-danger"><strong>Server error:</strong><br>${text.substring(0, 300)}</div>`;
            return;
        }

        if (result.success) {
            currentMpaData = result.data; // Сохраняем в память
            renderMPAGrid(result.data, container, year); // Рисуем
        } else {
            container.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
        }
    } catch (e) {
        console.error(e);
        container.innerHTML = `<div class="text-danger">Network Error: ${e.message}</div>`;
    }
}

// 3. Генерация HTML (С ФИЛЬТРОМ)
function renderMPAGrid(quartersData, container, year) {
    container.innerHTML = '';
    const monthNames = ["", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

    // Узнаем, какой квартал сейчас выбран
    const quarterSelect = document.getElementById('mpa-quarter-select');
    const selectedQ = quarterSelect ? quarterSelect.value : 'all';

    for (let q = 1; q <= 4; q++) {
        // ФИЛЬТРАЦИЯ: пропускаем ненужные кварталы
        if (selectedQ !== 'all' && selectedQ != q) {
            continue;
        }

        const qData = quartersData[q];
        if (!qData) continue; // Защита от пустых данных
        
        const qPnl = parseFloat(qData.pnl);
        const qPercent = parseFloat(qData.percent || 0);
        
        const pnlSign = qPnl > 0 ? '+' : '';
        const pnlClass = qPnl >= 0 ? 'text-profit' : 'text-loss';

        const section = document.createElement('div');
        section.className = 'quarter-section'; 
        
        section.innerHTML = `
            <div class="quarter-header" onclick="this.parentElement.classList.toggle('collapsed')">
                <i class="fas fa-chevron-down quarter-toggle-icon"></i>
                <h4 class="m-0 me-3">Q${q}</h4>
                <span class="${pnlClass}" style="font-weight: 500;">
                    ${qPercent.toFixed(1)}% <span class="text-muted ms-2" style="font-size: 0.9em">(${pnlSign}${qPnl.toFixed(0)}$)</span>
                </span>
            </div>
            <div class="quarter-grid"></div>
        `;

        container.appendChild(section);
        const grid = section.querySelector('.quarter-grid');

        // Карточки месяцев
        qData.months.forEach(m => {
            const hasTrades = m.count_total > 0;
            const closedTrades = m.count_total - m.count_pending;
            
            const avgRR = closedTrades > 0 ? (m.rr_total / closedTrades).toFixed(2) : '0.00';
            const winRate = m.winrate;
            
            let profitColorClass = m.pnl_total >= 0 ? 'text-profit' : 'text-loss';
            const profitSign = m.pnl_total > 0 ? '+' : '';

            let progressColor = '#4caf50';
            if (m.pnl_total < 0) progressColor = '#f44336';
            if (!hasTrades) progressColor = 'rgba(255,255,255,0.1)';

            const cardHTML = `
                <div class="mpa-card" onclick="window.location.href='index.php?view=mpa_details&year=${year}&month=${m.month_num}'">
                    <div class="mpa-card-title">
                        <span>${monthNames[m.month_num]} ${year}</span>
                    </div>

                    <div class="mpa-stat-row text-muted" style="font-size: 0.9rem; letter-spacing: 0.5px; font-weight: 500;">
                        <span class="text-white">Q: ${m.count_total}</span> / 
                        <span class="text-profit">W: ${m.count_win}</span> / 
                        <span class="text-loss">L: ${m.count_loss}</span> / 
                        <span class="text-warning">BE: ${m.count_be}</span> / 
                        <span class="text-info">P: ${m.count_pending}</span>
                    </div>

                    <div class="mpa-stat-row mt-2">
                        <span>
                            Profit: <span class="${profitColorClass}" style="font-weight: 600;">${m.pnl_percent.toFixed(1)}%</span> 
                            <span class="${profitColorClass}" style="font-size: 0.9em; opacity: 0.8;"> / ${profitSign}${m.pnl_total.toFixed(0)}$</span>
                        </span>
                        <span class="text-muted" style="font-size: 0.85em;">AVG: <span class="text-white">${avgRR} RR</span></span>
                    </div>

                    <div class="mpa-stat-row mt-1 text-muted" style="font-size: 0.85em;">
                        ${winRate}% Winrate
                    </div>

                    <div class="mpa-progress-bg mt-3">
                        <div class="mpa-progress-fill" style="width: ${hasTrades ? winRate : 0}%; background: ${progressColor};"></div>
                    </div>
                </div>
            `;
            grid.insertAdjacentHTML('beforeend', cardHTML);
        });
    }
}

// --- ФУНКЦИЯ ДЛЯ СТРАНИЦЫ ДЕТАЛЕЙ МЕСЯЦА (MPA DETAILS) ---
async function loadMPAMonthDetails() {
    const yearInput = document.getElementById('detail-year');
    const monthInput = document.getElementById('detail-month');
    
    if (!yearInput || !monthInput) return;

    const year = yearInput.value;
    const month = monthInput.value;
    const monthNames = ["", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    
    // 1. Заголовок
    const titleEl = document.getElementById('month-title');
    if(titleEl) titleEl.textContent = `${monthNames[month]} ${year}`;

    // 2. Контейнеры
    const plansContainer = document.getElementById('month-plans-container'); 
    const tradesContainer = document.getElementById('month-trades-container');

    // === ДОБАВЛЕНО: СКЕЛЕТОНЫ ЗАГРУЗКИ ===
    if(plansContainer) plansContainer.innerHTML = `<div class="row">${getSkeletonHtml('card', 3)}</div>`;
    if(tradesContainer) tradesContainer.innerHTML = `<div class="row">${getSkeletonHtml('card', 3)}</div>`;

    // Скелетоны для цифр (PnL, Winrate и т.д.)
    ['month-pnl', 'month-winrate', 'month-rr', 'month-trades-count'].forEach(id => {
        const el = document.getElementById(id);
        if(el) el.innerHTML = getSkeletonHtml('metric');
    });
    // ======================================

    try {
        const response = await fetch(`api/api.php?action=get_mpa_month_details&year=${year}&month=${month}`);
        
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const result = await response.json();

        if (result.success) {
            const s = result.stats;
            const trades = result.trades;
            const plans = result.plans || [];

            // --- ОБНОВЛЕНИЕ МЕТРИК ---
            const pnlEl = document.getElementById('month-pnl');
            if(pnlEl) {
                pnlEl.textContent = `${s.pnl > 0 ? '+' : ''}${s.pnl.toFixed(2)} $`;
                pnlEl.className = `metric-value-pro ${s.pnl >= 0 ? 'text-success' : 'text-danger'}`;
            }
            if(document.getElementById('month-pnl-percent')) {
                 document.getElementById('month-pnl-percent').textContent = `${s.percent_sum.toFixed(2)} % Return`;
                 document.getElementById('month-pnl-percent').className = `metric-subtext-pro ${s.percent_sum >= 0 ? 'text-success' : 'text-danger'}`;
            }
            if(document.getElementById('month-winrate')) document.getElementById('month-winrate').textContent = `${s.winrate} %`;
            if(document.getElementById('month-rr')) document.getElementById('month-rr').textContent = `${s.avg_rr} R`;
            if(document.getElementById('month-trades-count')) document.getElementById('month-trades-count').textContent = s.total;

            const bd = document.getElementById('month-trades-breakdown');
            if(bd) {
                bd.innerHTML = `
                    <span class="badge bg-dark border border-success text-success">${s.wins} W</span>
                    <span class="badge bg-dark border border-danger text-danger">${s.losses} L</span>
                    <span class="badge bg-dark border border-warning text-warning">${s.be} B</span>
                    <span class="badge bg-dark border border-info text-info">${s.pending} P</span>
                `;
            }

            // --- ОТРИСОВКА ПЛАНОВ (ПЛИТКИ) ---
            if (plansContainer) {
                plansContainer.innerHTML = '';
                if (plans.length === 0) {
                    plansContainer.innerHTML = '<div class="col-12 text-center text-muted py-4">No plans for this month.</div>';
                } else {
                    plans.forEach(plan => {
                        const dateObj = new Date(plan.date);
                        const day = dateObj.getDate();
                        const biasClass = `bias-${(plan.bias || '').toLowerCase()}`;
                        const pair = plan.pair_symbol || 'Unknown';
                        const typeChar = plan.type ? plan.type.charAt(0) : '?'; 

                        const col = document.createElement('div');
                        col.className = 'col-md-4 mb-3';

                        col.innerHTML = `
                            <a href="index.php?view=plan_details&id=${plan.id}" class="plan-card glass-panel d-block text-decoration-none" style="position: relative; padding: 20px; min-height: 140px;">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                     <div class="plan-date-box text-white">
                                        <span style="font-size: 1.2rem; font-weight: bold;">${day}</span>
                                        <span class="plan-date-type text-muted ms-1" style="font-size: 0.8rem; text-transform: uppercase;">${typeChar}</span>
                                     </div>
                                     <div class="plan-bias-tag ${biasClass}">${plan.bias}</div>
                                </div>
                                <div class="plan-info mt-3">
                                    <div class="fw-bold text-white fs-5">${pair}</div>
                                    <div class="text-white-50 small text-truncate">${plan.title}</div>
                                </div>
                            </a>
                        `;
                        plansContainer.appendChild(col);
                    });
                }
            }

            // --- ОТРИСОВКА СДЕЛОК ---
            if (tradesContainer) {
                tradesContainer.innerHTML = '';
                if (trades.length === 0) {
                    tradesContainer.innerHTML = '<div class="col-12 text-center text-muted py-5">No trades.</div>';
                } else {
                    trades.forEach(t => {
                        const dateObj = new Date(t.entry_date);
                        const dateStr = dateObj.toLocaleDateString('ru-RU');
                        const pnlVal = parseFloat(t.pnl);
                        const rrVal = parseFloat(t.rr_achieved || 0).toFixed(2);
                        const pair = t.pair_name || t.pair_id || 'Unknown';
                        
                        let badgeClass = 'secondary';
                        let st = (t.status || 'open').toUpperCase();
                        let pnlColor = 'text-white';
                        if(pnlVal > 0) pnlColor = 'text-success';
                        if(pnlVal < 0) pnlColor = 'text-danger';

                        if(st.includes('WIN')) badgeClass = 'success';
                        else if(st.includes('LOSS')) badgeClass = 'danger';
                        else if(st === 'BREAKEVEN') badgeClass = 'warning';
                        else if(st === 'OPEN' || st === 'PENDING') badgeClass = 'info';

                        const dirColor = t.direction === 'Long' ? 'text-success' : 'text-danger';

                        const col = document.createElement('div');
                        col.className = 'col-md-4 mb-3'; 
                        col.innerHTML = `
                            <a href="index.php?view=trade_details&id=${t.id}" class="glass-panel d-block text-decoration-none" style="padding: 20px; border-radius: 12px; transition: transform 0.2s; position: relative;">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-muted small">${dateStr}</span>
                                        <span class="badge-soft ${badgeClass}">${st}</span>
                                    </div>
                                    <div class="fw-bold ${dirColor}" style="text-transform: uppercase; font-size: 0.85rem;">${t.direction}</div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-end">
                                    <div>
                                        <div class="fw-bold text-white fs-4 mb-0">${pair}</div>
                                        <div class="text-muted small mt-1">Result: <span class="text-white">${rrVal} R</span></div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fs-4 fw-bold ${pnlColor} font-mono">${pnlVal > 0 ? '+' : ''}${pnlVal.toFixed(2)} $</div>
                                    </div>
                                </div>
                            </a>
                        `;
                        tradesContainer.appendChild(col);
                    });
                }
            }
            
            // Запускаем логику формы отчета
            initMPAReportLogic(year, month);

        } else {
            console.error("API Error:", result.message);
        }
    } catch (e) {
        console.error("JS Error:", e);
    }
}

// --- ЛОГИКА ОТЧЕТОВ MPA ---

async function initMPAReportLogic(year, month) {
    const containerId = '#mpa-editor-container';
    
    // Ждем 100мс, чтобы убедиться, что HTML вставился в DOM
    await new Promise(resolve => setTimeout(resolve, 100));
    
    const container = document.querySelector(containerId);
    if (!container) return; // Если контейнера нет, выходим

    // --- 1. ПРОВЕРКА QUILL ---
    if (typeof Quill === 'undefined') {
        console.error("Quill JS not loaded! Check index.php");
        container.innerHTML = '<div class="text-danger">Error: editor library not loaded.</div>';
        return;
    }

    // --- 2. РЕГИСТРАЦИЯ ШРИФТОВ ---
    // Делаем это внутри try-catch, чтобы не падало, если уже зарегистрировано
    try {
        const Font = Quill.import('formats/font');
        Font.whitelist = ['inter', 'roboto', 'serif', 'monospace', 'Montserrat']; 
        Quill.register(Font, true);
    } catch (e) {
        console.log("Fonts loaded or conflict ignored.");
    }

    // --- 3. ПОЛНАЯ ОЧИСТКА ---
    // Удаляем старый инстанс Quill, если он висит в памяти
    if (mpaQuill) { 
        try { mpaQuill.disable(); } catch(e){}
        mpaQuill = null; 
    }
    
    // Чистим HTML контейнера
    container.innerHTML = '';
    
    // Удаляем старые панели инструментов, которые Quill создает РЯДОМ с контейнером
    const parent = container.parentNode;
    if (parent) {
        const oldToolbars = parent.querySelectorAll('.ql-toolbar');
        oldToolbars.forEach(tb => tb.remove());
        // Удаляем класс ql-container, если он остался на самом диве
        container.classList.remove('ql-container', 'ql-snow');
    }

    // --- 4. НАСТРОЙКИ ---
    const toolbarOptions = [
        [{ 'font': ['inter', 'roboto', 'serif', 'monospace', 'Montserrat'] }],
        [{ 'size': ['small', false, 'large', 'huge'] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'color': [] }, { 'background': [] }],
        [{ 'header': 1 }, { 'header': 2 }, 'blockquote', 'code-block'],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        [{ 'align': [] }],
        ['link', 'image', 'clean']
    ];

    // --- 5. СОЗДАНИЕ ---
    try {
        mpaQuill = new Quill(containerId, {
            theme: 'snow',
            modules: { toolbar: toolbarOptions },
            placeholder: 'Type your conclusions...'
        });
        
        // Принудительные стили для текста
        mpaQuill.root.style.color = '#e0e0e0';
        mpaQuill.root.style.fontFamily = "'Inter', sans-serif";
        mpaQuill.root.style.fontSize = '16px';
        
    } catch (err) {
        console.error("Critical Quill Init Error:", err);
        return;
    }

    // --- 6. ЗАГРУЗКА ДАННЫХ ---
    try {
        const response = await fetch(`api/api.php?action=get_mpa_report&year=${year}&month=${month}`);
        if (response.ok) {
            const result = await response.json();
            if (result.success && result.content) {
                // Используем innerHTML для безопасности от ошибки Range
                mpaQuill.root.innerHTML = result.content;
            }
        }
    } catch (e) {
        console.error("Load Error:", e);
    }

    // --- 7. СОХРАНЕНИЕ ---
    const form = document.getElementById('mpa-report-form');
    if (form) {
        // Убираем старые эвенты через клонирование
        const newForm = form.cloneNode(true);
        form.parentNode.replaceChild(newForm, form);
        
        newForm.onsubmit = async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-save-report');
            const originalText = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Saving...';

            const content = mpaQuill.root.innerHTML;

            try {
                const res = await fetch('api/api.php?action=save_mpa_report', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ year, month, content })
                });
                const data = await res.json();
                
                if (data.success) {
                    btn.className = 'btn btn-success px-4 py-2';
                    btn.innerHTML = '<i class="fas fa-check me-2"></i> Saved';
                    
                    showToast('Report saved successfully', 'success'); // <-- TOAST

                    setTimeout(() => {
                        btn.className = 'btn btn-primary px-4 py-2';
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }, 2000);
                } else {
                    showToast('Error: ' + data.message, 'error'); // <-- TOAST
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            } catch (err) {
                showToast('Network Error', 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        };
    }
}

// ==================================================
// QPA DETAILS LOGIC
// ==================================================

let qpaQuill = null; // Глобальная переменная для редактора QPA

// Добавьте вызов initQPADetails() внутрь вашего основного слушателя DOMContentLoaded
// Обычно это где-то в начале app.js:
document.addEventListener('DOMContentLoaded', () => {
    // ... ваш существующий код ...
    
    // Инициализация QPA, если мы на странице деталей
    initQPADetails();
});

function initQPADetails() {
    // Проверяем наличие элемента, специфичного для этой страницы
    const qpaContainer = document.getElementById('qpa-editor-container');
    if (!qpaContainer) return; // Если элемента нет, значит мы не на странице QPA Details

    // 1. Инициализация Quill
    if (typeof Quill !== 'undefined') {
        qpaQuill = new Quill('#qpa-editor-container', {
            theme: 'snow',
            placeholder: 'Write your quarterly analysis here...',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'color': [] }, { 'background': [] }],
                    ['clean']
                ]
            }
        });
    }

    // 2. Загрузка данных
    loadQPADetails();

    // 3. Обработчик сохранения формы
    const form = document.getElementById('qpa-report-form');
    if (form) {
        form.onsubmit = async (e) => {
            e.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Saving...';

            const year = document.getElementById('qpa-year').value;
            const quarter = document.getElementById('qpa-quarter').value;
            const content = qpaQuill ? qpaQuill.root.innerHTML : '';

            try {
                const res = await fetch('api/api.php?action=save_qpa_report', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ year, quarter, content })
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Quarterly report saved!', 'success');
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Network error', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        };
    }
}

async function loadQPADetails() {
    const yearElem = document.getElementById('qpa-year');
    const quarterElem = document.getElementById('qpa-quarter');

    if (!yearElem || !quarterElem) return;
    
    const year = yearElem.value;
    const quarter = quarterElem.value;

    // Установка заголовка
    const titleElem = document.getElementById('qpa-title');
    if(titleElem) titleElem.textContent = `Q${quarter} ${year} Review`;

    try {
        const res = await fetch(`api/api.php?action=get_qpa_details&year=${year}&quarter=${quarter}`);
        const data = await res.json();

        if (data.success) {
            renderQPAHeader(data.stats);
            renderQPAMonths(data.months);
            renderQPATrades(data.trades); 
            renderQPAPlans(data.plans);

            // Загружаем контент в редактор
            if (qpaQuill && data.report_content) {
                qpaQuill.root.innerHTML = data.report_content;
            }
        } else {
            showToast('Failed to load data: ' + data.message, 'error');
        }
    } catch (err) {
        console.error(err);
        showToast('Error loading QPA details', 'error');
    }
}

// --- Рендер Функции ---

function renderQPAHeader(stats) {
    if (!stats) return;
    
    const pnlElem = document.getElementById('qpa-pnl');
    if(pnlElem) {
        pnlElem.textContent = `$${parseFloat(stats.pnl).toFixed(2)}`;
        pnlElem.className = `fs-4 fw-bold ${parseFloat(stats.pnl) >= 0 ? 'text-success' : 'text-danger'}`;
    }

    const wrElem = document.getElementById('qpa-winrate');
    if(wrElem) wrElem.textContent = `${stats.winrate}%`;
    
    const rrElem = document.getElementById('qpa-rr');
    if(rrElem) rrElem.textContent = `${stats.avg_rr}R`;
    
    const totElem = document.getElementById('qpa-total');
    if(totElem) totElem.textContent = stats.total;
}

// assets/app.js

function renderQPAMonths(months) {
    const container = document.getElementById('qpa-months-container');
    if(!container) return;
    container.innerHTML = '';

    if (!months || months.length === 0) {
        // span 3 чтобы надпись заняла всю ширину сетки
        container.innerHTML = '<div class="text-muted" style="grid-column: span 3;">No data available</div>';
        return;
    }

    months.forEach(m => {
        const isPositive = parseFloat(m.pnl) >= 0;
        const colorClass = isPositive ? 'text-success' : 'text-danger';
        // Выбираем иконку тренда
        const iconClass = isPositive ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down';
        const iconColor = isPositive ? 'text-success' : 'text-danger';

        const html = `
        <div class="metric-card glass-panel h-100">
            <div class="metric-icon">
                <i class="fas ${iconClass} ${iconColor}"></i>
            </div>
            
            <div class="metric-content w-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="metric-label text-white opacity-75">${m.name}</div>
                    <span class="badge bg-secondary opacity-25 small">M${m.num}</span>
                </div>
                
                <div class="metric-value ${colorClass} mb-1">$${parseFloat(m.pnl).toFixed(2)}</div>
                
                <div class="d-flex gap-3 text-muted small" style="font-size: 0.8rem;">
                    <div><i class="fas fa-chart-pie me-1"></i>${m.winrate}%</div>
                    <div><i class="fas fa-list me-1"></i>${m.count}</div>
                </div>
            </div>
        </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    });
}

function renderQPATrades(trades) {
    const tbody = document.getElementById('qpa-trades-body'); // Это теперь DIV с классом trades-inner
    const badge = document.getElementById('trades-count-badge');
    
    if (badge) badge.textContent = trades ? trades.length : 0;
    if (!tbody) return;
    
    tbody.innerHTML = '';

    if (!trades || trades.length === 0) {
        tbody.innerHTML = '<div class="text-center py-4 text-muted">No trades found for this quarter</div>';
        return;
    }

    // ИСПОЛЬЗУЕМ ТУ ЖЕ ФУНКЦИЮ, ЧТО И В ЖУРНАЛЕ
    let html = '';
    trades.forEach(t => {
        html += getTradeRowHtml(t);
    });
    tbody.innerHTML = html;
}

function renderQPAPlans(plans) {
    const container = document.getElementById('qpa-plans-body'); // Это теперь DIV с классом plans-grid
    const badge = document.getElementById('plans-count-badge');
    
    if (badge) badge.textContent = plans ? plans.length : 0;
    if (!container) return;

    container.innerHTML = '';

    if (!plans || plans.length === 0) {
        container.innerHTML = '<div class="col-12 text-center py-4 text-muted" style="grid-column: 1 / -1;">No plans found for this quarter</div>';
        return;
    }

    // ИСПОЛЬЗУЕМ ТУ ЖЕ ФУНКЦИЮ, ЧТО И В ПЛАНАХ
    let html = '';
    plans.forEach(p => {
        html += getPlanCardHtml(p);
    });
    container.innerHTML = html;
}

// ==================================================
// 1. НОВЫЕ ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ (SHARED UI)
// ==================================================

/**
 * Генерирует HTML одной строки сделки (стиль Журнала)
 */
function getTradeRowHtml(trade) {
    const date = new Date(trade.entry_date).toLocaleDateString(undefined, {day: '2-digit', month: '2-digit', year: '2-digit'});
    const statusClass = `status-${trade.status}`;
    const pnlVal = Number(trade.pnl).toFixed(2);
    const pnlColor = Number(trade.pnl) >= 0 ? 'text-profit' : 'text-loss';
    const rrVal = Number(trade.rr_achieved).toFixed(2);
    
    // Определяем имя аккаунта (если его нет в объекте, ставим прочерк)
    const accName = trade.account_name || '-';

    return `
    <div class="trade-row trade-item" onclick="window.location.href='index.php?view=trade_details&id=${trade.id}'">
        
        <div class="t-col t-date">
            <span class="mobile-label">Date:</span> ${date}
        </div>
        <div class="t-col t-pair">
            <span class="mobile-label">Pair:</span> <strong>${trade.pair_symbol}</strong>
        </div>
        
        <div class="t-col t-account">
            <span class="mobile-label">Acc:</span> <strong>${accName}</strong>
        </div>
        
        <div class="t-col t-dir">
            <span class="mobile-label">Dir:</span> 
            <span class="dir-tag dir-${trade.direction} status-tag ${statusClass}">${trade.direction.toUpperCase()}</span>
        </div>
        <div class="t-col t-status">
            <span class="mobile-label">Status:</span> 
            <span class="status-tag ${statusClass}">${trade.status.charAt(0).toUpperCase() + trade.status.slice(1)}</span>
        </div>
        
        <div class="t-col t-risk"><span class="mobile-label">Risk:</span> ${trade.risk_percent}%</div>
        <div class="t-col t-rr"><span class="mobile-label">RR:</span> ${rrVal}</div>
        
        <div class="t-col t-pnl ${pnlColor}">
            <span class="mobile-label">PnL:</span> ${pnlVal}
        </div>
        
        <div class="t-col t-actions" onclick="event.stopPropagation()">
            <a title="View" href="index.php?view=trade_details&id=${trade.id}" class="btn-icon"><i class="fas fa-eye"></i></a>
            <a title="Edit" href="index.php?view=trade_create&id=${trade.id}" class="btn-icon"><i class="fas fa-edit"></i></a>
        </div>
    </div>`;
}

/**
 * Генерирует HTML одной карточки плана (стиль Планов)
 */
function getPlanCardHtml(plan) {
    const dateObj = new Date(plan.date);
    const typeChar = plan.type ? plan.type.charAt(0) : '?';
    
    return `
    <a href="index.php?view=plan_details&id=${plan.id}" class="plan-card glass-panel">
        <div class="plan-date-box"><span>${dateObj.getDate()}</span><span class="plan-date-type">${typeChar}</span></div>
        <div class="plan-info"><span class="plan-symbol">${plan.pair_symbol}</span><span class="plan-title-text">${plan.title}</span></div>
        <div class="plan-bias-tag bias-${plan.bias.toLowerCase()}">${plan.bias}</div>
        <div class="plan-arrow">➜</div>
    </a>`;
}

// Функция запуска главной страницы QPA
function loadQPAList() {
    const container = document.getElementById('qpa-list-container');
    const yearSelect = document.getElementById('qpa-year-select');

    if (!container) return;

    // 1. Инициализация фильтра годов (если еще не заполнен)
    if (yearSelect && yearSelect.options.length === 0) {
        const currentYear = new Date().getFullYear();
        for (let y = currentYear; y >= currentYear - 3; y--) {
            const opt = document.createElement('option');
            opt.value = y;
            opt.textContent = y;
            yearSelect.appendChild(opt);
        }
        // Обработчик смены года
        yearSelect.addEventListener('change', () => fetchQPAData(yearSelect.value));
    }

    // 2. Загружаем данные для текущего выбранного года
    const selectedYear = yearSelect ? yearSelect.value : new Date().getFullYear();
    fetchQPAData(selectedYear);
}

// Вспомогательная функция запроса
async function fetchQPAData(year) {
    const container = document.getElementById('qpa-list-container');
    // Показываем скелетоны загрузки
    container.innerHTML = getSkeletonHtml('card', 4);

    try {
        const res = await fetch(`api/api.php?action=get_qpa_list&year=${year}`);
        const json = await res.json();

        if (json.success) {
            renderQpaListGrid(json.data, container);
        } else {
            container.innerHTML = `<div class="col-12 text-center text-danger">Error: ${json.message}</div>`;
        }
    } catch (e) {
        console.error(e);
        container.innerHTML = '<div class="col-12 text-center text-danger">Network Error</div>';
    }
}

// Функция отрисовки карточек кварталов

function renderQpaListGrid(quarters, container) {
    container.innerHTML = '';

    if (!quarters || quarters.length === 0) {
        container.innerHTML = '<div class="text-muted text-center py-5" style="grid-column: 1 / -1;">No data available</div>';
        return;
    }

    quarters.forEach(q => {
        const pnlVal = parseFloat(q.pnl);
        const pnlClass = pnlVal >= 0 ? 'text-success' : 'text-danger';
        const pnlSign = pnlVal > 0 ? '+' : '';
        
        // Легкий градиент фона в зависимости от PnL
        const cardBg = pnlVal >= 0 
            ? 'background: linear-gradient(160deg, rgba(25, 135, 84, 0.08) 0%, rgba(0,0,0,0) 100%);' 
            : 'background: linear-gradient(160deg, rgba(220, 53, 69, 0.08) 0%, rgba(0,0,0,0) 100%);';

        // Цветные элементы
        const qIconColor = pnlVal >= 0 ? 'rgba(25, 135, 84, 0.2)' : 'rgba(220, 53, 69, 0.2)';
        const qTextColor = pnlVal >= 0 ? '#198754' : '#dc3545';

        const html = `
        <div class="glass-panel qpa-card p-4 h-100 d-flex flex-column justify-content-between" 
             style="${cardBg}"
             onclick="window.location.href='index.php?view=qpa_details&year=${q.year}&quarter=${q.quarter}'">
            
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <h3 class="fw-bold m-0 text-white" style="font-size: 1.8rem;">Q${q.quarter}</h3>
                    <div class="text-muted small text-uppercase" style="letter-spacing: 1px;">${q.year}</div>
                </div>
            </div>

            <div class="mb-4">
                <div class="small text-muted text-uppercase fw-bold mb-1" style="font-size: 0.75rem;">Net Result</div>
                <div class="display-6 fw-bold ${pnlClass}">
                    ${pnlSign}${pnlVal.toFixed(2)}<small class="fs-6 opacity-50 ms-1">$</small>
                </div>
            </div>

            <div class="d-flex align-items-center pt-3 border-top border-white-10 text-muted small">
                <div class="d-flex align-items-center me-4" title="Winrate">
                    <i class="fas fa-chart-pie me-2 opacity-50"></i>
                    <span class="fw-bold text-white">${q.winrate}%</span>
                </div>
                <div class="d-flex align-items-center me-auto" title="Total Trades">
                    <i class="fas fa-list me-2 opacity-50"></i>
                    <span class="fw-bold text-white">${q.total}</span>
                </div>
                
                <div class="text-white opacity-50">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </div>

        </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    });
}

// Функция запускается при нажатии на кнопку "+"
async function initQuickTrade() {
    // 1. Устанавливаем текущее время
    const dateInput = document.getElementById('quick-date');
    if (dateInput) {
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        dateInput.value = now.toISOString().slice(0, 16);
    }

    // 2. Загружаем ПАРЫ (если список пуст)
    const pairSelect = document.getElementById('quick-pair');
    if (pairSelect && pairSelect.options.length <= 1) {
        try {
            const res = await fetch('api/api.php?action=get_ref_pairs');
            const json = await res.json();
            
            if (json.success) {
                pairSelect.innerHTML = '<option value="">Select</option>';
                json.data.forEach(p => {
                    // ВАЖНО: используем p.id, так как в форме name="pair_id"
                    // Если нужно имя тикера в значении, замените p.id на p.symbol
                    pairSelect.innerHTML += `<option value="${p.id}">${p.symbol}</option>`;
                });
            }
        } catch (e) { console.error("Error loading pairs:", e); }
    }

    // 3. Загружаем АККАУНТЫ (если список пуст)
    const accSelect = document.getElementById('quick-account');
    if (accSelect && accSelect.options.length <= 1) {
        try {
            const res = await fetch('api/api.php?action=get_accounts');
            const json = await res.json();
            
            if (json.success) {
                accSelect.innerHTML = '<option value="">Select</option>';
                json.data.forEach(a => {
                    accSelect.innerHTML += `<option value="${a.id}">${a.name} (${a.currency})</option>`;
                });
            }
        } catch (e) { console.error("Error loading accounts:", e); }
    }
}

// Функция загрузки профиля
async function loadSimpleProfile() {
    try {
        const response = await fetch('api/api.php?action=get_user_info');
        const result = await response.json();

        if (result.success) {
            // ВАЖНО: Берем данные напрямую из result, а не из result.data
            // Проверяем, существуют ли элементы на странице, перед тем как записывать
            const elUser = document.getElementById('profile-display-username');
            const elEmail = document.getElementById('profile-display-email');
            const elJoined = document.getElementById('profile-join-date');

            if (elUser) elUser.textContent = result.username;
            if (elEmail) elEmail.textContent = result.email;
            
            // Если дата приходит как 'created_at', используем её
            if (elJoined) elJoined.textContent = result.created_at || '-';
        }
    } catch (error) {
        console.error('Error loading profile:', error);
    }
}

// Обработчик отправки формы (Сохранение)
document.addEventListener('DOMContentLoaded', function() {
    const quickForm = document.getElementById('quick-trade-form');
    
    if (quickForm) {
        quickForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Собираем данные
            const formData = new FormData(quickForm);
            const data = Object.fromEntries(formData.entries());

            // Блокируем кнопку, чтобы не нажать дважды
            const btn = quickForm.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = 'Saving...';

            try {
                const res = await fetch('api/api.php?action=save_trade', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                
                if (result.success) {
                    // Закрываем окно (Bootstrap 5)
                    const modalEl = document.getElementById('quickAddModal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    modal.hide();
                    
                    // Показываем уведомление
                    if (typeof Toastify === 'function') {
                         Toastify({text: "Trade added successfully!", backgroundColor: "#198754"}).showToast();
                    } else {
                        alert("Trade added successfully!");
                    }
                    
                    // Перезагружаем страницу, чтобы увидеть новую сделку
                    setTimeout(() => location.reload(), 500);
                } else {
                    alert('Error: ' + (result.message || 'Unknown error'));
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            } catch (err) { 
                console.error(err);
                alert('Network Error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', async () => {
    // А. Инициализируем те, что уже есть в HTML при загрузке
    document.querySelectorAll('[title]').forEach(makeTooltip);

    // Б. Включаем наблюдение за всем телом документа (body)
    observer.observe(document.body, { 
        childList: true, // следить за добавлением детей
        subtree: true    // следить на любой глубине вложенности
    });

    const urlParams = new URLSearchParams(window.location.search);
    const view = urlParams.get('view');
	
	const accForm = document.getElementById('account-form');
    if(accForm) {
        accForm.onsubmit = async (e) => {
            e.preventDefault();
            const data = {
                id: document.getElementById('acc-id').value,
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
            closeAccountModal();
            loadAccounts();
        };
    }
    
    // 1. REGISTER FORM (Обновлено с Email)
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = document.getElementById('reg-username').value;
            const email = document.getElementById('reg-email').value; // Новое
            const password = document.getElementById('reg-password').value;

            try {
                const res = await fetch('api/api.php?action=register', {
                    method: 'POST',
                    body: JSON.stringify({ username, email, password })
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Registration successful! Please login.', 'success');
                    setTimeout(() => window.location.href = 'index.php?view=login', 1500);
                } else {
                    showToast(data.message || 'Registration failed', 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Network error', 'error');
            }
        });
    }

    // 2. FORGOT PASSWORD FORM
    const forgotForm = document.getElementById('forgot-form');
    if (forgotForm) {
        forgotForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('forgot-email').value;
            const btn = forgotForm.querySelector('button');
            const originalBtnText = btn.textContent;
            
            btn.disabled = true;
            btn.textContent = 'Sending...';

            try {
                const res = await fetch('api/api.php?action=forgot_password', {
                    method: 'POST',
                    body: JSON.stringify({ email })
                });
                const data = await res.json();
                
                if (data.success) {
                    showToast('Link generated!', 'success');
                    
                    // 1. Формируем полную ссылку для браузера
                    // Берем текущий путь, убираем index.php (если он есть) и добавляем ссылку от сервера
                    let path = window.location.pathname;
                    if (path.endsWith('index.php')) {
                        path = path.substring(0, path.length - 'index.php'.length);
                    }
                    // Убираем возможный двойной слэш
                    if (!path.endsWith('/')) path += '/';
                    
                    const fullLink = window.location.origin + path + data.debug_link;

                    // 2. Заменяем содержимое формы на удобный интерфейс с ссылкой
                    forgotForm.innerHTML = `
                        <div class="text-center mb-4">
                            <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                            <h4 class="text-white">Check your email</h4>
                            <p class="text-muted small">We've sent a password reset link to <strong>${email}</strong></p>
                        </div>

                        <div class="p-3 mb-3 rounded" style="background: rgba(25, 135, 84, 0.1); border: 1px dashed #198754;">
                            <div class="text-success fw-bold small mb-2 text-uppercase">Debug Mode: Simulation</div>
                            
                            <div class="input-group mb-2">
                                <input type="text" class="form-control bg-dark text-white border-secondary form-control-sm" 
                                       value="${fullLink}" id="debug-link-input" readonly>
                                <button class="btn btn-secondary btn-sm" type="button" onclick="
                                    const el = document.getElementById('debug-link-input');
                                    el.select();
                                    navigator.clipboard.writeText(el.value);
                                    this.innerHTML = '<i class=\'fas fa-check\'></i>';
                                " title="Copy to clipboard"><i class="fas fa-copy"></i></button>
                            </div>

                            <a href="${fullLink}" class="btn btn-success btn-sm w-100">Open Link Directly</a>
                        </div>
                    `;
                    
                } else {
                    showToast(data.message || 'Error', 'error');
                    btn.disabled = false;
                    btn.textContent = originalBtnText;
                }
            } catch (err) {
                console.error(err);
                showToast('Network error', 'error');
                btn.disabled = false;
                btn.textContent = originalBtnText;
            }
        });
    }

    // 3. RESET PASSWORD FORM
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
                    showToast('Password changed successfully!', 'success');
                    setTimeout(() => window.location.href = 'index.php?view=login', 2000);
                } else {
                    showToast(data.message || 'Error changing password', 'error');
                }
            } catch (err) {
                showToast('Network error', 'error');
            }
        });
    }

    document.getElementById('mobile-menu-toggle')?.addEventListener('click', toggleMenu);
    document.getElementById('login-form')?.addEventListener('submit', handleLoginSubmit);
    document.getElementById('logout-btn')?.addEventListener('click', logout);
    
    // ... (инициализация форм plan/trade/note без изменений) ...
    const planForm = document.getElementById('plan-form');
    if (planForm) { initPlanForm(); planForm.addEventListener('submit', e => handleFormSubmit(e, 'save_plan', 'plan', 'plans')); }
    const tradeForm = document.getElementById('trade-form');
    if (tradeForm) { initTradeForm(); tradeForm.addEventListener('submit', e => handleFormSubmit(e, 'save_trade', 'trade', 'journal')); const b=document.getElementById('add-trade-image-btn'); if(b) b.onclick=()=>addTradeImage(); }
    const noteForm = document.getElementById('note-form');
    if (noteForm) { initNoteForm(); noteForm.addEventListener('submit', e => handleFormSubmit(e, 'save_note', 'note', 'notes')); }

    if (typeof view !== 'undefined' && view === 'profile') {loadSimpleProfile();}

    if (view === 'plans') { loadPlans(); setupFiltersModal(loadPlans); }
    if (view === 'plan_details') { loadPlanDetails(); setTimeout(setupLightbox, 100); }
    if (view === 'journal') { loadTrades(); setupFiltersModal(loadTrades); }
    if (view === 'trade_details') { loadTradeDetails(); setTimeout(setupLightbox, 100); }
    if (view === 'notes') { loadNotes(); }
    if (view === 'note_details') { loadNoteDetails(); setTimeout(setupLightbox, 100); }
	
	if (view === 'accounts') { loadAccounts(); }
	if (view === 'account_create') { initAccountForm(); }
	
	if (view === 'data') {
        loadDataAnalysis();
    }
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
        
        // Логика показа месяца только если выбран год
        const yearSelect = document.getElementById('dashboard-year-select');
        const monthSelect = document.getElementById('dashboard-month-select');
        
        yearSelect.addEventListener('change', () => {
            if (yearSelect.value) {
                monthSelect.style.display = 'inline-block';
            } else {
                monthSelect.style.display = 'none';
                monthSelect.value = ''; // Сброс месяца при сбросе года
            }
            loadDashboardMetrics();
        });
        
        monthSelect.addEventListener('change', loadDashboardMetrics);
        
        // Загрузка счетов и первый рендер
        loadLookups().then(data => {
            if (data && data.accounts) {
                populateSelect('dashboard-account-select', data.accounts, 'name', 'id', null, 'All accounts');
            }
            loadDashboardMetrics(); 
        });
        
        document.getElementById('dashboard-account-select')?.addEventListener('change', loadDashboardMetrics);
    }
	
	if (view === 'accounts') { 
        loadAccounts(); 
        loadPayouts(); // <--- ДОБАВИТЬ ЭТО
    }
	
	if (view === 'account_details') { 
        const accId = document.getElementById('current-account-view-id')?.value;
        if(accId) {
            // Загружаем данные страницы
            loadAccountDetailsPage(accId); 
            // Инициализируем переключение вкладок
            initAccountTabs(accId);
            // Загружаем справочники для модального окна выплат
            loadLookups().then(data => {
                if(data && data.accounts) populateSelect('payout-account', data.accounts, 'name');
            });

            // Кнопка "Добавить выплату" на этой странице
            const btn = document.getElementById('btn-add-account-payout');
            if(btn) {
                btn.onclick = () => {
                    openPayoutModal();
                    // Предустанавливаем текущий счет
                    const sel = document.getElementById('payout-account');
                    if(sel) sel.value = accId;
                }
            }
        }
    }
    
    // Обработчик формы выплат
    const payoutForm = document.getElementById('payout-form');
    if(payoutForm) {
        payoutForm.onsubmit = async (e) => {
            e.preventDefault();
            
            // Блокируем кнопку
            const btn = payoutForm.querySelector('button[type="submit"]');
            const oldText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Saving...';

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
                    // КРАСИВОЕ УВЕДОМЛЕНИЕ
                    showToast('Payout saved successfully', 'success');
                } else {
                    showToast('Saving error: ' + result.message, 'error');
                }
            } catch (error) {
                console.error(error);
                showToast('Network error', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = oldText;
            }
        };
    }
    
    setupLightbox();
	loadUserInfo();
});