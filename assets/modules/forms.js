// assets/modules/forms.js
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
            populateSelect('note-trade', data.trades, 'display_name', 'id', null, window.lang['choose_trade']);
            populateSelect('filter-pair', data.pairs, 'symbol', 'id', null, window.lang['all_pairs']);

            return data;
        } else {
            console.error('Failed to load:', result.message);
            showMessage(window.lang['failed_to_load'], 'error');
            return null;
        }
    } catch (error) {
        console.error('Failed to load due to network error:', error);
        showMessage(window.lang['failed_to_load_network'], 'error');
        return null;
    }
}

function populateSelect(selectId, items, displayKey, valueKey = 'id', selectedValue = null, placeholderText = window.lang['choose']) {
    const select = document.getElementById(selectId);
    if (!select) return;
    const firstOption = select.querySelector('option[value=""]');
    select.innerHTML = '';
    if (firstOption) select.appendChild(firstOption);

    if (!items || items.length === 0) return;
    items.forEach(item => {
        if (item) { // Проверка на null
            const option = document.createElement('option');
            option.value = item[valueKey];
            option.textContent = item[displayKey] || item.id;
            if (selectedValue && item[valueKey] == selectedValue) option.selected = true;
            select.appendChild(option);
        }
    });
}

async function handleFormSubmit(event, action, entityName, redirectView) {
    event.preventDefault();
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span>⏳</span> ' + window.lang['saving'];

    // CSRF protection
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    if (!csrfToken) {
        console.error('CSRF token not found');
        showMessage(window.lang['csrf_token_missing'], 'error');
        return;
    }

    // Переносим HTML из редактора
    if (quillEditor) {
        const target = document.getElementById(entityName === 'note' ? 'note-content-hidden' : 'st-content-hidden');
        if (target) target.value = quillEditor.root.innerHTML;
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

        // Добавляем ID если это редактирование
        const idField = document.getElementById(`edit-${entityName}-id`);
        if (idField && idField.value) {
            data['id'] = idField.value;
        }

        // Обработка изображений
        const imagePromises = [];
        ['timeframes', 'trade_images'].forEach(arrKey => {
            if (data[arrKey]) data[arrKey] = data[arrKey].filter(item => item && (item.url || item.notes || item.title));
        });

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
            headers: { 
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(data)
        });
        const result = await response.json();

        if (result.success) {
            if (entityName === 'strategy' && result.id) {
                window.location.href = `index.php?view=strategy_details&id=${result.id}`;
            } else {
                window.location.href = `index.php?view=${redirectView}`;
            }
        } else {
            showMessage(window.lang['error_saving'] + ': ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Save error:', error);
        showMessage(window.lang['error_occurred_saving'], 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    }
}

async function uploadFile(file, type = 'general') {
    const maxFileSize = 5 * 1024 * 1024; // 5MB
    if (file.size > maxFileSize) {
        throw new Error(window.lang['file_too_large']);
    }

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
