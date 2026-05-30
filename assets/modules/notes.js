// assets/modules/notes.js
// ==================================================
// ФУНКЦИИ ДЛЯ ЗАМЕТОК (NOTES)
// ==================================================

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

async function deleteNote(id) {
    if (!await showConfirm(window.lang['confirm_delete_note'])) return;
    const fd = new FormData(); fd.append('id', id);
    await fetch('api/api.php?action=delete_note', { method: 'POST', body: fd });
    window.location.href = 'index.php?view=notes';
}
