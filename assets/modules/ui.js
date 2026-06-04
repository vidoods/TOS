// assets/modules/ui.js
// ==================================================
// УВЕДОМЛЕНИЯ И UI-УТИЛИТЫ
// ==================================================

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
        
        if (!document.getElementById('custom-confirm-styles')) {
            const style = document.createElement('style');
            style.id = 'custom-confirm-styles';
            style.textContent = `
                .custom-confirm-modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100vw;
                    height: 100vh;
                    z-index: 99999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    opacity: 0;
                    transition: opacity 0.2s ease;
                }
                .custom-confirm-modal.show {
                    opacity: 1;
                }
                .custom-confirm-backdrop {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.6);
                    backdrop-filter: blur(8px);
                    -webkit-backdrop-filter: blur(8px);
                }
                .custom-confirm-content {
                    position: relative;
                    background: rgba(22, 27, 34, 0.85);
                    border: 1px solid rgba(255, 255, 255, 0.08);
                    border-radius: 16px;
                    width: 90%;
                    max-width: 400px;
                    padding: 24px;
                    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
                    transform: scale(0.9);
                    transition: transform 0.2s ease;
                    text-align: center;
                }
                .custom-confirm-modal.show .custom-confirm-content {
                    transform: scale(1);
                }
                .custom-confirm-body {
                    margin-bottom: 24px;
                }
                .custom-confirm-icon {
                    width: 56px;
                    height: 56px;
                    border-radius: 50%;
                    background: rgba(245, 158, 11, 0.1);
                    color: #f59e0b;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.5rem;
                    margin: 0 auto 16px;
                }
                .custom-confirm-message {
                    font-size: 0.95rem;
                    color: var(--text-main, #ffffff);
                    line-height: 1.5;
                    margin: 0;
                    font-weight: 500;
                }
                .custom-confirm-footer {
                    display: flex;
                    gap: 12px;
                    justify-content: flex-end;
                }
                .custom-confirm-footer button {
                    padding: 10px 18px;
                    border-radius: 8px;
                    font-size: 0.88rem;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s ease;
                }
                .btn-confirm-cancel {
                    background: rgba(255, 255, 255, 0.05);
                    border: 1px solid rgba(255, 255, 255, 0.08);
                    color: var(--text-secondary, #888888);
                }
                .btn-confirm-cancel:hover {
                    background: rgba(255, 255, 255, 0.1);
                    color: var(--text-main, #ffffff);
                }
                .btn-confirm-ok {
                    background: #ef4444;
                    border: 1px solid transparent;
                    color: #ffffff;
                }
                .btn-confirm-ok:hover {
                    background: #dc2626;
                    transform: translateY(-1px);
                    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
                }
                .btn-confirm-ok.btn-blue {
                    background: #4f46e5;
                }
                .btn-confirm-ok.btn-blue:hover {
                    background: #4338ca;
                    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
                }
            `;
            document.head.appendChild(style);
        }
        
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