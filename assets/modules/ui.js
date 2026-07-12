// assets/modules/ui.js
// ==================================================
// УВЕДОМЛЕНИЯ И UI-УТИЛИТЫ
// ==================================================
function setupLightbox() {
    const modal = document.getElementById('image-modal');
    if (!modal) return;
    const modalImg = document.getElementById('modal-image');
    const closeBtn = modal.querySelector('.modal-close');

    document.addEventListener('click', e => {
        const isNoteImage = e.target.tagName === 'IMG' && e.target.closest('#note-content-display');
        const isInsightImage = e.target.tagName === 'IMG' && e.target.closest('#insight-content-display');

        if (e.target.classList.contains('lightbox-trigger') || isNoteImage || isInsightImage) {
            modal.style.display = "flex";
            modalImg.src = e.target.src;
            document.body.style.overflow = 'hidden';
        }
    });

    const close = () => {
        modal.style.display = "none";
        document.body.style.overflow = '';
    };

    if (closeBtn) closeBtn.addEventListener('click', close);
    modal.onclick = e => { if (e.target === modal) close(); };
}

function showToast(message, type = 'info') {
    let background = "linear-gradient(135deg, #2193b0, #6dd5ed)";

    if (type === 'success') {
        background = "linear-gradient(135deg, #00b09b, #96c93d)";
    } else if (type === 'error') {
        background = "linear-gradient(135deg, #ff5f6d, #ffc371)";
    } else if (type === 'warning') {
        background = "linear-gradient(135deg, #f7971e, #ffd200)";
    }

    if (typeof Toastify === 'undefined') {
        console.warn('[showToast] Toastify not loaded:', message);
        return;
    }

    Toastify({
        text: message,
        duration: 3000,
        gravity: "bottom",
        position: "right",
        stopOnFocus: true,
        style: { background: background },
        className: "info-toast"
    }).showToast();
}

function showMessage(message, type = 'success') {
    showToast(message, type === 'success' ? 'success' : 'error');
}

// Global window.alert override
const originalAlert = window.alert;
window.alert = function(message) {
    if (typeof Toastify !== 'undefined') {
        showToast(message, 'info');
    } else {
        originalAlert(message);
    }
};

/**
 * Async confirm dialog — replaces native confirm().
 * Returns Promise<boolean>.
 * Usage: if (!await showConfirm(window.lang['confirm_delete_action'])) return;
 */
function showConfirm(message, confirmText = null, cancelText = null) {
    return new Promise((resolve) => {
        const modal = document.createElement('div');
        modal.className = 'custom-confirm-modal';
        
        const isRu = window.lang && window.lang['language'] === 'Язык';
        const isUa = window.lang && window.lang['language'] === 'Мова';
        
        let yesLabel = confirmText;
        let noLabel = cancelText || (window.lang ? window.lang['cancel'] : 'Cancel');
        
        if (!yesLabel) {
            if (window.lang && message === window.lang['confirm_logout']) {
                yesLabel = window.lang['logout'];
            } else if (message.includes('delete') || message.includes('Delete') || message.includes('Удалить') || message.includes('Видалити')) {
                yesLabel = window.lang ? window.lang['delete'] : 'Delete';
            } else {
                yesLabel = isRu ? 'Да' : (isUa ? 'Так' : 'Yes');
            }
        }
        
        modal.innerHTML = `
            <div class="custom-confirm-backdrop"></div>
            <div class="custom-confirm-content glass-panel">
                <div class="custom-confirm-body">
                    <div class="custom-confirm-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <p class="custom-confirm-message">${message}</p>
                </div>
                <div class="custom-confirm-footer">
                    <button class="btn-confirm-cancel">${noLabel}</button>
                    <button class="btn-confirm-ok">${yesLabel}</button>
                </div>
            </div>
        `;
        
        const okBtn = modal.querySelector('.btn-confirm-ok');
        if (yesLabel !== (window.lang ? window.lang['delete'] : 'Delete')) {
            okBtn.classList.add('btn-blue');
        }
        
        document.body.appendChild(modal);
        
        modal.offsetHeight;
        modal.classList.add('show');
        
        const cleanup = (value) => {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.remove();
                resolve(value);
            }, 200);
        };
        
        modal.querySelector('.btn-confirm-cancel').addEventListener('click', () => cleanup(false));
        modal.querySelector('.btn-confirm-ok').addEventListener('click', () => cleanup(true));
        modal.querySelector('.custom-confirm-backdrop').addEventListener('click', () => cleanup(false));
        
        const onKey = (e) => {
            if (e.key === 'Escape') {
                cleanup(false);
                document.removeEventListener('keydown', onKey);
            }
        };
        document.addEventListener('keydown', onKey);
    });
}

window.showConfirm = showConfirm;

function getSkeletonHtml(type, count = 1) {
    let html = '';

    for (let i = 0; i < count; i++) {
        if (type === 'trade-row') {
            html += `
            <div class="skeleton-row">
                <div class="skeleton" style="height: 15px; width: 80px;"></div>
                <div class="skeleton" style="height: 15px; width: 60px;"></div>
                <div class="skeleton" style="height: 15px; width: 100px;"></div>
                <div class="skeleton" style="height: 25px; width: 60px; border-radius: 12px; margin-left: auto;"></div>
            </div>`;
        } else if (type === 'card') {
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
        } else if (type === 'list-item') {
            html += `
             <div class="d-flex justify-content-between align-items-center mb-3 p-2">
                <div class="skeleton" style="height: 15px; width: 40%;"></div>
                <div class="skeleton" style="height: 15px; width: 20%;"></div>
             </div>`;
        } else if (type === 'metric') {
            html += `<div class="skeleton" style="height: 28px; width: 100px; display:inline-block; border-radius: 6px;"></div>`;
        } else if (type === 'text-line') {
            html += `<div class="skeleton" style="height: 20px; width: 150px; display:inline-block; border-radius: 4px;"></div>`;
        }
    }
    return html;
}

document.addEventListener('DOMContentLoaded', () => {
    // Находим наш новый чекбокс-переключатель в сайдбаре
    const themeCheckbox = document.getElementById('theme-toggle-checkbox');
    
    if (themeCheckbox) {
        // 1. При загрузке проверяем сохраненную тему
        const savedTheme = localStorage.getItem('theme') || 'dark';
        
        // Ставим правильное положение ползунка
        themeCheckbox.checked = (savedTheme === 'light');
        
        // Применяем тему к сайту
        document.documentElement.setAttribute('data-theme', savedTheme);

        // 2. Слушаем переключение чекбокса
        themeCheckbox.addEventListener('change', function() {
            const newTheme = this.checked ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        });
    }
});