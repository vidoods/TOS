// assets/app.js

// ==================================================
// ОБЩИЕ ФУНКЦИИ ИНТЕРФЕЙСА И УТИЛИТЫ
// ==================================================

let menuOpen = false;
let accountBalances = {};
let quillEditor = null; // Глобальная переменная для редактора

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
    submitBtn.innerHTML = '<span>⏳</span> Сохранение...';

    // --- Если это заметка, переносим HTML из редактора в скрытое поле ---
    if (entityName === 'note' && quillEditor) {
        document.getElementById('note-content-hidden').value = quillEditor.root.innerHTML;
    }
    // -------------------------------------------------------------------

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
        
        // Добавляем ID, если это редактирование (поля часто вне формы)
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

        const response = await fetch(`api/api.php?action=${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();

        if (result.success) {
            window.location.href = `index.php?view=${redirectView}`;
        } else {
            showMessage('Ошибка сохранения: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Ошибка при сохранении:', error);
        showMessage('Произошла ошибка при сохранении. Проверьте консоль.', 'error');
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

async function initNoteForm() {
    const idEl = document.getElementById('edit-note-id');
    await loadLookups();
    
    // Инициализация редактора Quill
    if (document.getElementById('editor-container')) {
        document.getElementById('editor-container').innerHTML = ''; 
        quillEditor = new Quill('#editor-container', {
            theme: 'snow',
            placeholder: 'Пишите здесь...',
            modules: {
                toolbar: {
                    container: [
                        [{ 'header': [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['link', 'image', 'clean']
                    ],
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
                                        // Загружаем в папку notes
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
            // Вставляем HTML в редактор
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
            
            ['type', 'pair_symbol', 'formatted_date', 'bias', 'formatted_created_at'].forEach(key => {
                const el = document.getElementById(`plan-${key.replace('formatted_', '')}`);
                if (el) el.textContent = plan[key];
            });
            const biasEl = document.getElementById('plan-bias');
            if (biasEl) biasEl.className = `detail-value plan-bias-tag bias-${plan.bias.toLowerCase()}`;
            
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
                <button type="button" class="btn-remove" style="margin-top: 25px;" onclick="document.getElementById('${imgId}').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="form-group">
                 ${getImageInputHtml(imgId, url, `trade_images[${tradeImgCount-1}][url]`)}
            </div>
            
             <div class="form-group">
                <label class="form-label" style="font-size: 0.8em; margin-bottom: 4px;">Описание / Идея</label>
                <textarea class="textarea-field" name="trade_images[${tradeImgCount-1}][notes]" rows="2" placeholder="Что происходит на скриншоте...">${notes}</textarea>
            </div>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
}

function getImageInputHtml(id, url, name) {
    return `
        <input type="hidden" name="${name}" value="${url}">
        <input type="file" id="${id}-file" class="input-field" style="display:none" onchange="previewImage(this, '${id}-preview')">
        <button type="button" class="btn btn-outline w-100 mb-2" onclick="document.getElementById('${id}-file').click()">
            <i class="fas fa-upload me-2"></i> Загрузить файл
        </button>
        <input type="text" id="${id}-url" class="input-field mb-2" placeholder="Или вставьте прямую ссылку на изображение" value="${url}" oninput="previewImage(this, '${id}-preview')">
        <div id="${id}-preview" class="image-preview-box">${url ? `<img src="${url}">` : '<span class="image-preview-placeholder">Предпросмотр изображения</span>'}</div>
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
            preview.innerHTML = `<img src="${e.target.result}">`; 
            if (textUrlInput) textUrlInput.value = ''; 
            // Файл будет загружен при отправке
        };
        reader.readAsDataURL(input.files[0]);
    } else if (input.type === 'text' && input.value.trim()) {
         preview.innerHTML = `<img src="${input.value.trim()}" onerror="this.onerror=null;this.src='';this.alt='Ошибка загрузки изображения'; preview.innerHTML='<span class=\"image-preview-placeholder\">Ошибка загрузки изображения</span>';">`;
         if (hiddenUrlInput) hiddenUrlInput.value = input.value.trim();
    } else {
         preview.innerHTML = '<span class="image-preview-placeholder">Предпросмотр изображения</span>';
         if (hiddenUrlInput) hiddenUrlInput.value = '';
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
                html += `
                    <div class="month-group">
                        <div class="month-header">
                            <span class="month-label">${group.month_label}</span>
                            <span class="month-summary">PnL: <span class="${group.total_pnl >= 0 ? 'text-profit' : 'text-loss'}">${group.total_pnl.toFixed(2)}</span> | RR: ${group.total_rr.toFixed(2)}R</span>
                        </div>
                        <div class="trades-table-wrapper"><table class="trades-table">
                            <thead><tr>
                                <th>Date</th><th>Pair</th><th>Dir</th><th>Status</th><th>Risk</th><th>RR</th><th>PnL</th><th>Actions</th>
                            </tr></thead><tbody>`;
                
                group.trades.forEach(trade => {
                    const date = new Date(trade.entry_date).toLocaleDateString(undefined, {day: '2-digit', month: '2-digit', year: '2-digit'});
                    const statusClass = `status-${trade.status}`;
                    html += `
                        <tr onclick="window.location.href='index.php?view=trade_details&id=${trade.id}'">
                            <td>${date}</td>
                            <td><strong>${trade.pair_symbol}</strong></td>
                            <td><span class="dir-tag dir-${trade.direction}">${trade.direction.toUpperCase()}</span></td>
                            <td><span class="status-tag ${statusClass}">${trade.status.charAt(0).toUpperCase() + trade.status.slice(1)}</span></td>
                            <td>${trade.risk_percent}%</td>
                            <td>${Number(trade.rr_achieved).toFixed(2)}R</td>
                            <td class="${Number(trade.pnl) >= 0 ? 'text-profit' : 'text-loss'}">${Number(trade.pnl).toFixed(2)}</td>
                            <td class="actions-cell" onclick="event.stopPropagation()">
                                <a href="index.php?view=trade_details&id=${trade.id}" class="btn-icon" title="Детали"><i class="fas fa-eye"></i></a>
                                <a href="index.php?view=trade_create&id=${trade.id}" class="btn-icon" title="Редактировать"><i class="fas fa-edit"></i></a>
                            </td>
                        </tr>`;
                });
                html += '</tbody></table></div></div>';
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
            
            // 2. Отображение ДАТ (Изменен формат на dd.mm.yy)
            ['entry_date', 'exit_date'].forEach(key => {
                 const el = document.getElementById(`trade-${key}`);
                 if(el && trade[key]) {
                     const dateObj = new Date(trade[key]);
                     const day = String(dateObj.getDate()).padStart(2, '0');
                     const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                     const year = String(dateObj.getFullYear()).slice(-2); // Берем последние 2 цифры года
                     
                     el.textContent = `${day}.${month}.${year}`;
                 }
            });
            
            // 3. Расчет и отображение ДЛИТЕЛЬНОСТИ (Только дни и часы)
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
                
                // Если прошло меньше часа, пишем "Менее 1ч"
                if (days === 0 && hours === 0) {
                    durationText = 'Менее 1ч';
                }
                
                durationEl.textContent = durationText.trim();
            } else {
                durationEl.textContent = trade.exit_date ? 'Нет даты входа' : 'В процессе';
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
                        el.className = 'info-badge ' + (val >= 0 ? 'badge-profit' : 'badge-loss');
                        if (key === 'risk_percent') el.textContent += '%';
                    } else if (key === 'risk_percent') {
                         el.textContent = `${trade[key]}%`;
                    } else if (key === 'status') {
                        el.textContent = trade[key].charAt(0).toUpperCase() + trade[key].slice(1);
                        el.className = `badge status-tag status-${trade[key]} text-uppercase`;
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
                if (isLong) {
                    directionEl.classList.add('badge-profit'); 
                } else {
                    directionEl.classList.add('badge-loss');   
                }
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
            if (planLink && trade.plan_id) {
                planLink.href = `index.php?view=plan_details&id=${trade.plan_id}`;
                planLink.textContent = trade.plan_title;
                planLink.style.display = 'inline';
            } else if (planLink) {
                 planLink.textContent = 'Нет связанного плана';
                 planLink.removeAttribute('href');
                 planLink.style.display = 'block';
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

async function loadDashboardMetrics() {
    try {
        const response = await fetch('api/api.php?action=get_dashboard_metrics');
        const result = await response.json();
        if (result.success) {
            const m = result.data;
            document.getElementById('total-trades-value').textContent = m.total_trades;
            document.getElementById('winning-ratio-value').textContent = m.win_rate + '%';
            const winProgress = document.getElementById('winning-ratio-progress');
            if (winProgress) winProgress.style.width = m.win_rate + '%';
            
            // --- ИСПРАВЛЕНИЕ: Отображение среднего времени в позиции ---
            const avgTimeEl = document.getElementById('avg-time-in-position-value');
            if (avgTimeEl) {
                // Если данные есть, присваиваем их элементу
                avgTimeEl.textContent = m.avg_time_in_position; 
                // Дополнительно обновляем класс для цвета, если нужно (например, если N/A)
                avgTimeEl.classList.remove('text-profit', 'text-loss');
            }
            // --------------------------------------------------------
            
            const setPnL = (id, val, suffix = '') => {
                const el = document.getElementById(id);
                if(el) {
                    el.textContent = (val >= 0 ? '+' : '') + val.toFixed(2) + suffix;
                    el.classList.toggle('text-profit', val >= 0);
                    el.classList.toggle('text-loss', val < 0);
                }
            };
            setPnL('net-profit-value', m.total_pnl);
            setPnL('net-profit-rr-value', m.total_rr, 'R');
            setPnL('average-rr-value', m.avg_rr_per_trade, 'R');
        }
    } catch (e) { 
        console.error(e); 
        // В случае ошибки показываем, что данные недоступны
        document.getElementById('avg-time-in-position-value').textContent = 'Ошибка';
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    const urlParams = new URLSearchParams(window.location.search);
    const view = urlParams.get('view');
    
    document.getElementById('mobile-menu-toggle')?.addEventListener('click', toggleMenu);
    document.getElementById('login-form')?.addEventListener('submit', handleLoginSubmit);
    document.getElementById('logout-btn')?.addEventListener('click', logout);
    
    const planForm = document.getElementById('plan-form');
    const tradeForm = document.getElementById('trade-form');
    const noteForm = document.getElementById('note-form');

    if (planForm) {
        initPlanForm();
        planForm.addEventListener('submit', e => handleFormSubmit(e, 'save_plan', 'plan', 'plans'));
    }
    if (tradeForm) {
        initTradeForm();
        tradeForm.addEventListener('submit', e => handleFormSubmit(e, 'save_trade', 'trade', 'journal'));
        
        const addImgBtn = document.getElementById('add-trade-image-btn');
        if (addImgBtn) addImgBtn.addEventListener('click', () => addTradeImage());
    }

    if (noteForm) {
        initNoteForm();
        noteForm.addEventListener('submit', e => handleFormSubmit(e, 'save_note', 'note', 'notes'));
    }

    // Логика страниц
    if (view === 'plans') { 
        loadPlans(); 
        setupFiltersModal(loadPlans); 
    }
    if (view === 'plan_details') { 
        loadPlanDetails(); 
        setTimeout(setupLightbox, 100); 
    }
    if (view === 'journal') { 
        loadTrades(); 
        setupFiltersModal(loadTrades); 
    }
    if (view === 'trade_details') { 
        loadTradeDetails(); 
        setTimeout(setupLightbox, 100);
    }
    if (view === 'dashboard') { 
        loadDashboardMetrics(); 
    }
    if (view === 'notes') {
        loadNotes();
    }
    if (view === 'note_details') {
        loadNoteDetails();
        setTimeout(setupLightbox, 100);
    }
    
    setupLightbox();
});