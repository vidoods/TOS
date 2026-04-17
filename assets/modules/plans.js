// assets/modules/plans.js
// ==================================================
// ФУНКЦИИ ДЛЯ ПЛАНОВ
// ==================================================

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
            if (plan.note_id) document.getElementById('plan-note').value = plan.note_id;

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

            document.getElementById('plan-type').textContent = plan.type;
            document.getElementById('plan-pair-symbol').textContent = plan.pair_symbol;
            document.getElementById('plan-date').textContent = plan.formatted_date;
            document.getElementById('plan-bias').textContent = plan.bias;
            document.getElementById('plan-created-at').textContent = plan.formatted_created_at;

            const biasEl = document.getElementById('plan-bias');
            if (biasEl) biasEl.className = `detail-value plan-bias-tag bias-${plan.bias.toLowerCase()}`;

            const oldNoteLink = document.getElementById('plan-note-link-container');
            if (oldNoteLink) oldNoteLink.remove();

            if (plan.note_id && plan.note_title) {
                const noteHtml = `
                    <div id="plan-note-link-container" class="detail-item mt-3">
						</br>
                        <span class="detail-label">Linked note:</span>
                        <a href="index.php?view=note_details&id=${plan.note_id}" class="info-badge badge-blue" style="text-decoration:none; width: fit-content;">${plan.note_title}</a>
                    </div>`;
                const overviewSection = document.querySelector('.plan-overview');
                if (overviewSection) overviewSection.insertAdjacentHTML('beforeend', noteHtml);
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
    } finally { if (container) container.style.opacity = '1'; }
}
