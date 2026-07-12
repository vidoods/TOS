// assets/modules/notes.js

async function loadNotes() {
    const container = document.getElementById('notes-grid-container');
    if (!container) return;

    container.innerHTML = `<div class="row">${getSkeletonHtml('card', 4)}</div>`;

    try {
        const res = await fetch('api/api.php?action=get_notes');
        const json = await res.json();

        if (json.success) {
            if (json.data.length === 0) {
                container.innerHTML = `<div class="empty-state">${window.lang['no_notes_create']}</div>`;
                return;
            }

            let html = '';
            json.data.forEach(note => {
                const isUsed = note.latest_usage !== window.lang['not_used'];
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
                                ${window.lang['latest_usage']}: ${note.latest_usage}
                            </div>
                        </div>
                </a>`;
            });
            container.innerHTML = html;
        }
    } catch (e) { console.error(e); }
}

async function loadAssetInsights() {
    const container = document.getElementById('assets-insights-grid-container');
    if (!container) return;

    container.innerHTML = `<div class="row">${getSkeletonHtml('card', 4)}</div>`;

    try {
        const res = await fetch('api/api.php?action=get_asset_insights');
        const json = await res.json();

        if (json.success) {
            if (json.data.length === 0) {
                container.innerHTML = `<div class="empty-state">${window.lang['no_asset_insights']}</div>`;
                return;
            }

            let html = '';
            json.data.forEach(insight => {
                const date = new Date(insight.created_at).toLocaleDateString();
                const creator = insight.creator_name || 'N/A';

                html += `
                    <a href="index.php?view=insight_details&id=${insight.id}" class="note-card">
                        <div class="note-header">
                            <i class="fas fa-chart-line note-icon"></i>
                            <div class="note-title">${escapeHTML(insight.asset_symbol_text)}</div>
                        </div>
                        <div class="note-meta">
                            <div class="note-meta-row">
                                ${date}
                            </div>
                            <div class="note-meta-row" style="color: var(--text-secondary); opacity: 0.7;">
                                ${window.lang['created_by']}: ${creator}
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

    if (document.getElementById('editor-container')) {
        document.getElementById('editor-container').innerHTML = '';
        quillEditor = new Quill('#editor-container', {
            theme: 'snow',
            placeholder: window.lang['type_here'],
            modules: {
                toolbar: {
                    container: fullToolbarOptions,
                    handlers: {
                        'image': function () {
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
                                        showToast(window.lang['failed_load_image'], 'error');
                                    }
                                } else {
                                    showToast(window.lang['select_image'], 'warning');
                                }
                            };
                        }
                    }
                }
            }
        });
    }

    if (idEl && idEl.value) {
        const r = await fetch(`api/api.php?action=get_note_details&id=${idEl.value}`);
        const j = await r.json();
        if (j.success) {
            const n = j.data;
            document.getElementById('note-title').value = n.title;
            if (quillEditor) quillEditor.clipboard.dangerouslyPasteHTML(n.content || '');
            if (n.trade && n.trade.id) document.getElementById('note-trade').value = n.trade.id;
            if (n.plan && n.plan.id) document.getElementById('note-plan').value = n.plan.id;
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
        document.getElementById('note-content-display').innerHTML = n.content;
        document.getElementById('note-created-at').textContent = n.date_formatted || n.created_formatted;
        document.getElementById('note-date-info').textContent = n.created_formatted;

        const tradeEl = document.getElementById('note-linked-trade');
        if (n.trade) {
            tradeEl.innerHTML = `<a href="index.php?view=trade_details&id=${n.trade.id}" class="info-badge badge-blue" style="text-decoration: none;">${n.trade.label}</a>`;
        } else {
            tradeEl.textContent = window.lang['no_link'];
        }

        const planEl = document.getElementById('note-linked-plan');
        if (n.plan) {
            planEl.innerHTML = `<a href="index.php?view=plan_details&id=${n.plan.id}" class="info-badge badge-blue" style="text-decoration: none;">${n.plan.label}</a>`;
        } else {
            planEl.textContent = window.lang['no_link'];
        }

        document.getElementById('btn-edit-note').onclick = () => window.location.href = `index.php?view=note_create&id=${n.id}`;
        document.getElementById('btn-delete-note').onclick = () => deleteNote(n.id);
    }
}

async function loadInsightDetails() {
    const id = document.getElementById('current-insight-id')?.value;
    if (!id) return;

    try {
        const res = await fetch(`api/api.php?action=get_insight_details&id=${id}`);
        const json = await res.json();

        if (json.success) {
            const i = json.data;

            // Заполняем заголовок и контент
            document.getElementById('insight-details-title').textContent = i.asset_symbol || 'Insight';
            document.getElementById('insight-content-display').innerHTML = i.content || '';
            
            // Метаданные
            const date = new Date(i.created_at).toLocaleDateString();
            document.getElementById('insight-created-at').textContent = date;
            
            document.getElementById('insight-asset-symbol').textContent = i.asset_symbol || 'N/A';
            document.getElementById('insight-creator').textContent = i.creator_name || 'System';

            // Кнопки действий
            document.getElementById('btn-edit-insight').onclick = () => window.location.href = `index.php?view=insight_create&id=${i.id}`;
            document.getElementById('btn-delete-insight').onclick = () => deleteInsight(i.id);
        } else {
            showToast(json.message, 'error');
        }
    } catch (e) {
        console.error('Error loading insight details:', e);
    }
}

async function deleteNote(id) {
    if (!await showConfirm(window.lang['confirm_delete_note'])) return;
    const fd = new FormData(); fd.append('id', id);
    await fetch('api/api.php?action=delete_note', { method: 'POST', body: fd });
    window.location.href = 'index.php?view=notes';
}

async function initInsightForm() {
    const idEl = document.getElementById('edit-insight-id');
    
    // Инициализация редактора Quill
    if (document.getElementById('insight-editor-container')) {
        document.getElementById('insight-editor-container').innerHTML = '';
        quillEditorInsight = new Quill('#insight-editor-container', {
            theme: 'snow',
            placeholder: window.lang['type_here'],
            modules: {
                toolbar: {
                    container: fullToolbarOptions,
                    handlers: {
                        'image': function () {
                            const input = document.createElement('input');
                            input.setAttribute('type', 'file');
                            input.setAttribute('accept', 'image/*');
                            input.click();

                            input.onchange = async () => {
                                const file = input.files[0];
                                if (file && /^image\//.test(file.type)) {
                                    try {
                                        const url = await uploadFile(file, 'insights');
                                        const range = quillEditorInsight.getSelection();
                                        quillEditorInsight.insertEmbed(range.index, 'image', url);
                                    } catch (e) {
                                        console.error('Upload failed:', e);
                                        showToast(window.lang['failed_load_image'], 'error');
                                    }
                                } else {
                                    showToast(window.lang['select_image'], 'warning');
                                }
                            };
                        }
                    }
                }
            }
        });
    }

    // Если мы в режиме редактирования (есть ID), подгружаем данные из API
    if (idEl && idEl.value) {
        try {
            const r = await fetch(`api/api.php?action=get_insight_details&id=${idEl.value}`);
            const j = await r.json();
            
            if (j.success) {
                const i = j.data;
                
                // Исправлено: устанавливаем значение asset_id в селект с id="insight-asset-id"
                const assetSelect = document.getElementById('insight-asset-id');
                if (assetSelect) {
                    assetSelect.value = i.asset_id;
                }

                // Заполняем контент редактора
                if (quillEditorInsight) {
                    quillEditorInsight.clipboard.dangerouslyPasteHTML(i.content || '');
                }
            }
        } catch (e) {
            console.error('Error loading insight data for edit:', e);
        }
    }
}

async function saveInsight(event) {
    if (event) event.preventDefault();

    const form = document.getElementById('insight-form');
    if (!form) {
        console.error('Insight form not found');
        showToast('Ошибка: форма инсайта не найдена', 'error');
        return;
    }

    const data = new FormData(form);

    // Получаем ID из скрытого поля
    const id = document.getElementById('edit-insight-id')?.value;
    
    // Добавляем ID в данные, если он существует
    if (id) {
        data.set('id', id);
    }
    
    // Проверяем содержимое редактора Quill
    const content = document.getElementById('insight-editor-container');
    if (content && typeof quillEditorInsight !== 'undefined' && quillEditorInsight) {
        try {
            const contentHTML = quillEditorInsight.root.innerHTML;
            data.set('content', contentHTML);
        } catch (e) {
            console.error('Error getting content from Quill editor:', e);
        }
    } else if (content && typeof quillEditorInsight === 'undefined') {
        console.warn('Quill editor not initialized yet');
    }

    // Отправляем данные
    try {
        const response = await fetch('api/api.php?action=save_insight', {
            method: 'POST',
            body: data
        });

        const json = await response.json();
        if (json.success) {
            // Переходим на страницу заметок при успехе
            window.location.href = 'index.php?view=notes';
        } else {
            showToast(json.message, 'error');
        }
    } catch (e) {
        console.error('Error saving insight:', e);
        showToast('Ошибка при сохранении инсайта', 'error');
    }
}


async function deleteInsight(id) {
    if (!await showConfirm(window.lang['confirm_delete_insight'])) return;
    const fd = new FormData(); 
    fd.append('id', id);
    
    try {
        const response = await fetch('api/api.php?action=delete_insight', { 
            method: 'POST',
            body: fd
        });

        const json = await response.json();
        
        if (json.success) {
            // Перенаправляем на страницу notes
            window.location.href = 'index.php?view=notes';
        } else {
            showToast(json.message, 'error');
        }
    } catch (e) {
        console.error('Error deleting insight:', e);
        showToast('Ошибка при удалении инсайта', 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initInsightForm();
});

async function initNotesTabs() {
    const tabs = document.querySelectorAll('.tab-btn');
    const contents = document.querySelectorAll('.tab-content');

    // Функция для изменения текста кнопки
    function updateAddButton(tabId) {
    const addButton = document.getElementById('add-note-btn');
    if (!addButton) return;

    // Находим текст внутри кнопки (теперь это span)
    const textSpan = addButton.querySelector('.btn-text');
    
    if (tabId === 'assets-insights') {
        // Меняем ссылку
        addButton.href = "index.php?view=insight_create";
        // Меняем текст
        if (textSpan) textSpan.textContent = window.lang['add_new_insight'] || 'Add Insight';
    } else {
        // Возвращаем старую ссылку
        addButton.href = "index.php?view=note_create";
        // Возвращаем старый текст
        if (textSpan) textSpan.textContent = window.lang['new_note'] || 'New Note';
    }
    }


    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const targetId = tab.getAttribute('data-tab');

            // Скрываем все вкладки и контенты
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));

            // Активируем выбранную вкладку и контент
            tab.classList.add('active');
            document.getElementById(`tab-${targetId}`).classList.add('active');

            // Загрузка данных для вкладки Assets Insights при первом открытии
            if (targetId === 'assets-insights' && !document.getElementById('tab-assets-insights').classList.contains('loaded')) {
                loadAssetInsights();
                document.getElementById('tab-assets-insights').classList.add('loaded');
            }
            // Обновляем текст кнопки
            updateAddButton(targetId);
        });
    });

    // Загрузка данных для вкладки Notes при загрузке страницы
    loadNotes();
}

// Вызов инициализации функции при загрузке страницы
document.addEventListener('DOMContentLoaded', () => {
    initNotesTabs();
    if (document.getElementById('current-insight-id')?.value) {
        loadInsightDetails(); 
        setTimeout(setupLightbox, 100); 
    }
});
