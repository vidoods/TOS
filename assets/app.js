// assets/app.js

// ==================================================
// ОБЩИЕ ФУНКЦИИ ИНТЕРФЕЙСА И УТИЛИТЫ
// ==================================================

let menuOpen = false;
let accountBalances = {};
let quillEditor = null; // Глобальная переменная для редактора
let equityChartInstance = null;

// --- 1. Регистрация шрифтов (в начало файла assets/app.js) ---

// Получаем объект Font из Quill
const Font = Quill.import('formats/font');
// Задаем список разрешенных шрифтов (названия классов CSS)
// Важно: Первым должен идти шрифт по умолчанию (пустая строка или 'inter')
Font.whitelist = ['inter', 'roboto', 'serif', 'monospace', 'Montserrat']; 
Quill.register(Font, true);

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
    alert(message);
}

async function loadUserInfo() {
    const el = document.getElementById('sidebar-username');
    if (!el) return; // Если мы на странице входа, элемента нет
    
    try {
        const res = await fetch('api/api.php?action=get_user_info');
        const data = await res.json();
        if(data.success) {
            el.textContent = data.username;
        }
    } catch(e) { 
        console.error(e); 
        el.textContent = 'User';
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
    submitBtn.innerHTML = 'Проверка...';

    try {
        const response = await fetch('api/api.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) {
            window.location.href = 'index.php?view=dashboard';
        } else {
            errorDiv.textContent = result.message || 'Ошибка входа';
        }
    } catch (error) {
        errorDiv.textContent = 'Ошибка сети. Попробуйте позже.';
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
        errorDiv.textContent = 'Пароли не совпадают!';
        return;
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;

    errorDiv.textContent = '';
    submitBtn.disabled = true;
    submitBtn.innerHTML = 'Регистрация...';

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
            errorDiv.textContent = result.message || 'Ошибка регистрации';
        }
    } catch (error) {
        errorDiv.textContent = 'Ошибка сети.';
        console.error('Register error:', error);
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    }
}

async function logout() {
    if (confirm('Вы уверены, что хотите выйти?')) {
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
			populateSelect('trade-note', data.notes, 'title', 'id', null, '--- Без заметки ---');
            populateSelect('plan-note', data.notes, 'title', 'id', null, '--- Без заметки ---');
            
            // Заполнение для заметок
            if(document.getElementById('note-plan')) {
                populateSelect('note-plan', data.plans, 'title');
                populateSelect('note-trade', data.trades, 'display_name', 'id', null, '-- Выберите сделку --');
            }
            
            populateSelect('filter-pair', data.pairs, 'symbol', 'id', null, 'Все инструменты');
            
            return data;
        } else {
            console.error('Ошибка загрузки справочников:', result.message);
            showMessage('Не удалось загрузить справочные данные.', 'error');
            return null;
        }
    } catch (error) {
        console.error('Ошибка сети при загрузке справочников:', error);
        showMessage('Ошибка сети при загрузке справочников.', 'error');
        return null;
    }
}

function populateSelect(selectId, items, displayKey, valueKey = 'id', selectedValue = null, placeholderText = '--- Выберите ---') {
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
    
    try {
        const res = await fetch('api/api.php?action=get_notes');
        const json = await res.json();
        
        if (json.success) {
            if (json.data.length === 0) {
                container.innerHTML = '<div class="empty-state">Нет заметок. Создайте первую!</div>';
                return;
            }
            
            let html = '';
            json.data.forEach(note => {
                const isUsed = note.latest_usage !== 'Not Used';
                const usageStyle = isUsed ? 'color: var(--accent-blue); font-weight: 500;' : 'color: var(--text-secondary); opacity: 0.5;';
                
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
            placeholder: 'Пишите здесь...',
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
                                        alert('Не удалось загрузить изображение');
                                    }
                                } else {
                                    alert('Пожалуйста, выберите файл изображения.');
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
            tradeEl.textContent = 'Нет привязки';
        }

        const planEl = document.getElementById('note-linked-plan');
        if (n.plan) {
            planEl.innerHTML = `<a href="index.php?view=plan_details&id=${n.plan.id}" class="info-badge badge-blue" style="text-decoration: none;">${n.plan.label}</a>`;
        } else {
            planEl.textContent = 'Нет привязки';
        }
        
        document.getElementById('btn-edit-note').onclick = () => window.location.href = `index.php?view=note_create&id=${n.id}`;
        document.getElementById('btn-delete-note').onclick = () => deleteNote(n.id);
    }
}

async function deleteNote(id) {
    if(!confirm('Удалить заметку?')) return;
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
            document.getElementById('form-page-title').textContent = 'Редактировать План';
        } else {
            showMessage('Ошибка загрузки плана: ' + result.message, 'error');
            window.location.href = 'index.php?view=plans';
        }
    } catch (error) {
        console.error('Ошибка при загрузке плана для редактирования:', error);
        showMessage('Ошибка сети.', 'error');
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
                <input type="text" name="timeframes[${tfCount-1}][title]" class="input-field" value="${title}" placeholder="Название таймфрейма (например, 4H Chart)">
                <button type="button" class="btn-remove" onclick="document.getElementById('${tfId}').remove()">Удалить</button>
            </div>
            <div class="form-group">
                 ${getImageInputHtml(tfId, url, `timeframes[${tfCount-1}][url]`)}
            </div>
            <div class="form-group">
                <textarea class="textarea-field" name="timeframes[${tfCount-1}][notes]" rows="3" placeholder="Заметки к этому таймфрейму...">${notes}</textarea>
            </div>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
}

// --- СПИСОК ПЛАНОВ ---
async function loadPlans(filters = {}) {
    const container = document.getElementById('plans-list-container');
    if (!container) return;
    container.innerHTML = '<div class="loading-spinner">Загрузка планов...</div>';

    try {
        const params = new URLSearchParams(filters);
        const response = await fetch(`api/api.php?action=get_plans&${params}`);
        const result = await response.json();

        if (result.success) {
            const groupedPlans = result.data;
            if (groupedPlans.length === 0) {
                container.innerHTML = '<div class="empty-state">Планы не найдены.</div>';
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
                group.plans.forEach(plan => {
                    const dateObj = new Date(plan.date);
                    const card = document.createElement('a');
                    card.className = 'plan-card glass-panel';
                    card.href = `index.php?view=plan_details&id=${plan.id}`;
                    card.innerHTML = `
                        <div class="plan-date-box"><span>${dateObj.getDate()}</span><span class="plan-date-type">${plan.type.charAt(0)}</span></div>
                        <div class="plan-info"><span class="plan-symbol">${plan.pair_symbol}</span><span class="plan-title-text">${plan.title}</span></div>
                        <div class="plan-bias-tag bias-${plan.bias.toLowerCase()}">${plan.bias}</div>
                        <div class="plan-arrow">➜</div>`;
                    plansGrid.appendChild(card);
                });
                container.appendChild(plansGrid);
            });
        } else { container.innerHTML = `<div class="error-state">Ошибка: ${result.message}</div>`; }
    } catch (error) { console.error(error); container.innerHTML = '<div class="error-state">Ошибка загрузки.</div>'; }
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
                        <span class="detail-label">Привязанная заметка:</span>
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
                                <h3>${tf.title || 'Таймфрейм'}</h3>
                                ${tf.image_url ? `<img src="${tf.image_url}" class="lightbox-trigger">` : '<p class="text-muted">Нет изображения</p>'}
                                ${tf.notes ? `<div class="notes">${tf.notes}</div>` : ''}
                            </div>`;
                    });
                } else { tfList.innerHTML = '<div class="empty-state">Нет изображений.</div>'; }
            }
        } else {
            showMessage('Ошибка загрузки деталей плана: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Ошибка при загрузке плана для редактирования:', error);
        showMessage('Ошибка сети.', 'error');
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
            document.getElementById('form-page-title').textContent = 'Редактировать Сделку';
            
            // Триггер пересчета RR
            const event = new Event('input', { bubbles: true });
            const pnlInput = document.getElementById('trade-pnl');
            if (pnlInput) pnlInput.dispatchEvent(event);
            
        } else {
            showMessage('Ошибка загрузки сделки: ' + result.message, 'error');
            window.location.href = 'index.php?view=journal';
        }
    } catch (error) {
        console.error('Ошибка при загрузке сделки для редактирования:', error);
        showMessage('Ошибка сети.', 'error');
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
                     <label class="form-label" style="font-size: 0.8em; margin-bottom: 4px;">Таймфрейм / Контекст</label>
                     <input type="text" name="trade_images[${tradeImgCount-1}][title]" class="input-field" placeholder="Например: 4H, Entry, Setup" value="${title}">
                </div>
            </div>
            
            <div class="form-group">
                 ${getImageInputHtml(imgId, url, `trade_images[${tradeImgCount-1}][url]`)}
            </div>
            
             <div class="form-group">
                <label class="form-label" style="font-size: 0.8em; margin-bottom: 4px;">Описание / Идея</label>
                <textarea class="textarea-field" name="trade_images[${tradeImgCount-1}][notes]" rows="2" placeholder="Что происходит на скриншоте...">${notes}</textarea>
            </div>
            
            <div class="text-end mt-2">
                <button type="button" class="btn btn-danger btn-sm" onclick="document.getElementById('${imgId}').remove()">
                    <i class="fas fa-trash-alt me-2"></i> Удалить скриншот
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
                <i class="fas fa-upload me-2"></i> Загрузить файл
            </button>
        </div>
        
        <input type="text" id="${id}-url" class="input-field mb-2" placeholder="Или вставьте прямую ссылку на изображение" value="${url}" oninput="previewImage(this, '${id}-preview')">
        
        <div id="${id}-preview" class="image-preview-box">
            ${url ? `<img src="${url}" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                     <span class="image-preview-placeholder" style="display:none;">Ошибка загрузки</span>` 
                  : '<span class="image-preview-placeholder">Предпросмотр изображения</span>'}
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
                                  <span class="image-preview-placeholder err-msg" style="display:none; color: var(--accent-red);">Не удалось загрузить изображение</span>`;
             if (hiddenUrlInput) hiddenUrlInput.value = val;
         } else {
             preview.innerHTML = '<span class="image-preview-placeholder">Предпросмотр изображения</span>';
             if (hiddenUrlInput) hiddenUrlInput.value = '';
         }
    }
}


// --- ЖУРНАЛ СДЕЛОК (СПИСОК) ---
async function loadTrades(filters = {}) {
    const container = document.getElementById('trades-list-container');
    if (!container) return;
    container.innerHTML = '<div class="loading-spinner">Загрузка журнала...</div>';

    try {
        const params = new URLSearchParams(filters);
        const response = await fetch(`api/api.php?action=get_trades&${params}`);
        const result = await response.json();

        if (result.success) {
            const groupedTrades = result.data;
            if (groupedTrades.length === 0) {
                 container.innerHTML = '<div class="empty-state">Сделки не найдены.</div>';
                 return;
            }
            
            let html = '';
            groupedTrades.forEach(group => {
                const pnlClass = group.total_pnl >= 0 ? 'text-profit' : 'text-loss';
                const pnlSign = group.total_pnl >= 0 ? '+' : '';
                
                // Проценты
                const pctVal = group.total_percent || 0; // Защита если API еще не обновился
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
                                <span class="${pnlClass}">
                                    PnL: ${pnlSign}${group.total_pnl.toFixed(2)}
                                    <span style="color: var(--text-secondary); margin: 0 15px; opacity: 0.4;">|</span>
                                    ${pctSign}${pctVal.toFixed(2)}%
                                </span>
                                
                                <span class="divider" style="margin: 0 15px; color: var(--glass-border-hover);">|</span>
                                
                                <span class="text-main">
                                    RR: ${group.total_rr.toFixed(2)}R
                                </span>
                            </div>
                        </div>
                        
                        <div class="trades-list-wrapper">
                            <div class="trades-inner">
                                
                                <div class="trade-row trade-header-row">
                                    <div class="t-col t-date">Date</div>
                                    <div class="t-col t-pair">Pair</div>
                                    <div class="t-col t-dir">Dir</div>
                                    <div class="t-col t-status">Status</div>
                                    <div class="t-col t-risk">Risk</div>
                                    <div class="t-col t-rr">RR</div>
                                    <div class="t-col t-pnl">PnL</div>
                                    <div class="t-col t-actions">Actions</div>
                                </div>

                                `;
                
                group.trades.forEach(trade => {
                     const date = new Date(trade.entry_date).toLocaleDateString(undefined, {day: '2-digit', month: '2-digit', year: '2-digit'});
                     const statusClass = `status-${trade.status}`;
                     const pnlVal = Number(trade.pnl).toFixed(2);
                     const pnlColor = Number(trade.pnl) >= 0 ? 'text-profit' : 'text-loss';
                     const rrVal = Number(trade.rr_achieved).toFixed(2);
                     
                     html += `
                        <div class="trade-row trade-item" onclick="window.location.href='index.php?view=trade_details&id=${trade.id}'">
                            
                            <div class="t-col t-date">
                                <span class="mobile-label">Date:</span> ${date}
                            </div>
                            <div class="t-col t-pair">
                                <span class="mobile-label">Pair:</span> <strong>${trade.pair_symbol}</strong>
                            </div>
                            
                            <div class="t-col t-dir">
                                <span class="mobile-label">Dir:</span> 
                                <span class="dir-tag dir-${trade.direction}">${trade.direction.toUpperCase()}</span>
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
                                <a title="Просмотр" href="index.php?view=trade_details&id=${trade.id}" class="btn-icon"><i class="fas fa-eye"></i></a>
                                <a title="Редактировать" href="index.php?view=trade_create&id=${trade.id}" class="btn-icon"><i class="fas fa-edit"></i></a>
                            </div>
                        </div>`;
                });
                
                html += '</div></div></div>'; 
            });
            container.innerHTML = html;
        } else { container.innerHTML = `<div class="error-state">Ошибка: ${result.message}</div>`; }
    } catch (error) { console.error(error); container.innerHTML = '<div class="error-state">Ошибка загрузки.</div>'; }
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
                if (days > 0) durationText += `${days}д `;
                if (hours > 0) durationText += `${hours}ч`;
                if (days === 0 && hours === 0) durationText = 'Менее 1ч';
                
                durationEl.textContent = durationText.trim();
                // ИСПРАВЛЕНО: Добавляем стиль бейджа
                durationEl.className = 'detail-value info-badge badge-neutral';
            } else {
                durationEl.textContent = trade.exit_date ? 'Нет даты входа' : 'В процессе';
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
                        el.textContent = trade[key] || 'Не указано';
                    }
                }
            });
            
            // 5. Таймфрейм
            const entryTfEl = document.getElementById('trade-entry_timeframe');
            if (entryTfEl) {
                entryTfEl.textContent = trade.entry_tf || 'Не указано';
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
                     tagsEl.textContent = 'Нет';
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
                    planLink.textContent = 'Нет связанного плана';
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
                                ${img.image_url ? `<img src="${img.image_url}" class="lightbox-trigger">` : '<p class="text-muted">Нет изображения</p>'}
                                ${img.notes ? `<div class="notes small text-muted mt-2">${img.notes}</div>` : ''}
                            </div>`;
                    });
                } else { tradeImgList.innerHTML = '<div class="empty-state-small">Нет скриншотов для этой сделки.</div>'; }
            }

        } else {
            showMessage('Ошибка загрузки деталей сделки: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Ошибка при загрузке сделки для редактирования:', error);
        showMessage('Ошибка сети.', 'error');
    }
    finally { if (container) container.style.opacity = '1'; }
}

async function deleteEntity(id, action, redirectView) {
    if (!confirm('Вы уверены? Это действие нельзя отменить.')) return;
    try {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('trade_id', id); 
        formData.append('plan_id', id);  
        
        const response = await fetch('api/api.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) window.location.href = `index.php?view=${redirectView}`;
        else showMessage('Ошибка удаления: ' + result.message, 'error');
    } catch (e) { console.error(e); showMessage('Ошибка сети.', 'error'); }
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

// Обновленная функция метрик
async function loadDashboardMetrics(overrideAccountId = null, isDetailedView = false) {
    try {
        let accountId, year, month;

        // Если это детальный просмотр, берем переданный ID, иначе берем из фильтров дашборда
        if (isDetailedView && overrideAccountId) {
            accountId = overrideAccountId;
            year = ''; 
            month = '';
        } else {
            accountId = document.getElementById('dashboard-account-select')?.value || '';
            year = document.getElementById('dashboard-year-select')?.value || '';
            month = document.getElementById('dashboard-month-select')?.value || '';
        }

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
            
            // Карта ID элементов: (Детальный просмотр -> Главный дашборд)
            const idMap = isDetailedView ? {
                total_trades: 'ad-total-trades',
                breakdown: 'ad-trades-breakdown',
                win_rate: 'ad-winrate',
                win_bar: 'ad-winrate-bar',
                avg_rr: 'ad-avg-rr',
                pnl: 'ad-pnl-value', // ID для новой страницы
                chart: 'accountEquityChart'
            } : {
                total_trades: 'total-trades-value',
                breakdown: 'total-trades-breakdown',
                win_rate: 'winning-ratio-value',
                win_bar: 'winning-ratio-progress',
                avg_rr: 'average-rr-value',
                pnl: 'net-profit-value',
                chart: 'equityChart',
                // Эти поля есть только на главном дашборде
                monthly: 'avg-monthly-profit',
                mdd: 'max-drawdown-value',
                avg_time: 'avg-time-in-position-value'
            };

            // Обновляем значения в HTML
            if(document.getElementById(idMap.total_trades)) 
                document.getElementById(idMap.total_trades).textContent = m.total_trades;

            if(document.getElementById(idMap.breakdown))
                document.getElementById(idMap.breakdown).innerHTML = 
                    `<span class="text-profit">${m.wins} W</span> / 
                     <span class="text-loss">${m.losses} L</span> / 
                     <span class="text-warning">${m.breakeven} B</span>`;
            
            if(document.getElementById(idMap.win_rate))
                document.getElementById(idMap.win_rate).textContent = m.win_rate + '%';
            
            if(document.getElementById(idMap.win_bar))
                document.getElementById(idMap.win_bar).style.width = m.win_rate + '%';

            if(document.getElementById(idMap.avg_rr))
                document.getElementById(idMap.avg_rr).textContent = m.avg_rr_per_trade + ' R';

            // PnL
            if(document.getElementById(idMap.pnl)) {
                 const dollarHtml = ' <i class="fas fa-dollar-sign" style="font-size: 0.85em; opacity: 0.8;"></i>';
                 const val = m.total_pnl;
                 const el = document.getElementById(idMap.pnl);
                 const text = (val >= 0 ? '+ ' : '') + val.toFixed(2);
                 el.innerHTML = text + (isDetailedView ? '$' : dollarHtml);
                 el.classList.remove('text-profit', 'text-loss');
                 el.classList.add(val >= 0 ? 'text-profit' : 'text-loss');
            }

            // Дополнительные поля только для Главного дашборда
            if (!isDetailedView) {
                const dollarHtml = ' <i class="fas fa-dollar-sign" style="font-size: 0.85em; opacity: 0.8;"></i>';
                document.getElementById(idMap.monthly).innerHTML = `Среднемес.: ${m.avg_monthly_profit}${dollarHtml}`;
                document.getElementById(idMap.avg_time).textContent = m.avg_time_in_position;
                const mddEl = document.getElementById(idMap.mdd);
                if(mddEl) {
                    mddEl.innerHTML = `-${m.max_drawdown_pct}% (-${m.max_drawdown_abs}${dollarHtml})`;
                    mddEl.className = 'metric-value text-loss';
                }
            }

            // Рисуем график
            if (m.equity_chart) {
                renderEquityChart(m.equity_chart, idMap.chart);
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
    
    container.innerHTML = '<div class="loading-spinner">Загрузка...</div>';
    
    try {
        const res = await fetch('api/api.php?action=get_accounts_data');
        const json = await res.json();
        
        if(json.success) {
            if(json.data.length === 0) {
                container.innerHTML = '<div class="empty-state">Нет счетов. Добавьте первый!</div>';
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
    if(!confirm('Удалить этот счет и все связанные данные? Это действие нельзя отменить.')) return;
    const fd = new FormData(); fd.append('id', id);
    try {
        const res = await fetch('api/api.php?action=delete_account', {method:'POST', body:fd});
        const json = await res.json();
        if(json.success) {
            // Если мы на странице деталей, редиректим, иначе обновляем список
            if (window.location.search.includes('view=account_details')) {
                window.location.href = 'index.php?view=accounts';
            } else {
                loadAccounts();
            }
        } else {
            alert('Ошибка удаления: ' + json.message);
        }
    } catch(e) { console.error(e); }
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
                    showMessage('Ошибка: ' + json.message, 'error');
                }
            } catch(err) {
                showMessage('Ошибка сети', 'error');
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
                        <p>История выплат пуста</p>
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
        document.getElementById('payout-modal-title').textContent = 'Добавить Выплату';
        
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
    document.getElementById('payout-modal-title').textContent = 'Редактировать Выплату';
}

async function deletePayout(id) {
    if(!confirm('Удалить запись о выплате?')) return;
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
                
                document.getElementById('ad-balance').textContent = '$' + bal.toLocaleString('en-US', {minimumFractionDigits: 2});
                const profAbsEl = document.getElementById('ad-profit-abs');
                profAbsEl.textContent = (profitAbs >= 0 ? '+' : '') + profitAbs.toFixed(2) + '$';
                profAbsEl.className = profitAbs >= 0 ? 'text-profit' : 'text-loss';
                document.getElementById('ad-profit-pct').textContent = `(${profitPct.toFixed(2)}%)`;
                
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
                container.innerHTML = `<div class="glass-panel p-4 text-center text-muted"><p>Нет выплат</p></div>`;
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
    } catch (e) { container.innerHTML = '<div class="error-state">Ошибка</div>'; }
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
                
                <div class="winrate-tooltip-wrapper" data-tooltip="${tooltipText}" style="display: flex; align-items: center; gap: 6px;">
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
                
                <div class="winrate-tooltip-wrapper" data-tooltip="${tooltipText}" style="display: flex; align-items: center; gap: 5px;">
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
    container.innerHTML = '<div class="loading-spinner">Loading system...</div>';

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

document.addEventListener('DOMContentLoaded', async () => {
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
                populateSelect('dashboard-account-select', data.accounts, 'name', 'id', null, 'Все счета');
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
    
    // Обработчик формы выплат (ИСПРАВЛЕННЫЙ)
    const payoutForm = document.getElementById('payout-form');
    if(payoutForm) {
        payoutForm.onsubmit = async (e) => {
            e.preventDefault();
            
            // Блокируем кнопку, чтобы не было двойных нажатий
            const btn = payoutForm.querySelector('button[type="submit"]');
            const oldText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Сохранение...';

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
                    loadPayouts(); // Перезагружаем таблицу
                } else {
                    // ВОТ ЗДЕСЬ МЫ УВИДИМ РЕАЛЬНУЮ ОШИБКУ
                    alert('Ошибка сохранения: ' + result.message);
                }
            } catch (error) {
                console.error(error);
                alert('Произошла ошибка сети или сервера. Проверьте консоль (F12).');
            } finally {
                btn.disabled = false;
                btn.textContent = oldText;
            }
        };
    }
    
    setupLightbox();
	loadUserInfo();
});