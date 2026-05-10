// assets/modules/strategy.js
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
                container.innerHTML = `<div class="empty-state">${window.lang['no_strategy_modules']}</div>`;
                return;
            }

            let html = '';
            json.data.forEach(item => {
                let iconColorClass = 'green';
                if (item.title.includes('Risk')) iconColorClass = 'yellow';
                if (item.title.includes('Execution') || item.title.includes('Trade')) iconColorClass = 'blue';

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
            placeholder: window.lang['describe_strategy'],
            modules: {
                toolbar: fullToolbarOptions
            }
        });
    }

    if (idEl && idEl.value) {
        const res = await fetch(`api/api.php?action=get_strategy_details&id=${idEl.value}`);
        const json = await res.json();
        if (json.success) {
            const d = json.data;
            document.getElementById('st-title').value = d.title;
            document.getElementById('st-icon').value = d.icon;
            document.getElementById('st-desc').value = d.description;
            if (quillEditor) quillEditor.clipboard.dangerouslyPasteHTML(d.content || '');
        }
    }

    const form = document.getElementById('strategy-form');
    if (form) {
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

            const iconContainer = document.getElementById('st-detail-icon');
            iconContainer.innerHTML = `<i class="${d.icon}"></i>`;
            iconContainer.classList.remove('green', 'yellow', 'blue');
            if (d.title.includes('Risk')) iconContainer.classList.add('yellow');
            else if (d.title.includes('Execution')) iconContainer.classList.add('blue');
            else iconContainer.classList.add('green');

            document.getElementById('btn-edit-strategy').onclick = () => window.location.href = `index.php?view=strategy_create&id=${d.id}`;
            document.getElementById('btn-delete-strategy').onclick = () => deleteStrategy(d.id);
        }
    } catch (e) { console.error(e); }
}

async function deleteStrategy(id) {
    if (!confirm(window.lang['confirm_delete_module'])) return;
    const fd = new FormData(); fd.append('id', id);
    await fetch('api/api.php?action=delete_strategy', { method: 'POST', body: fd });
    window.location.href = 'index.php?view=strategy';
}
