// assets/modules/core.js
// ==================================================
// ГЛОБАЛЬНЫЕ ПЕРЕМЕННЫЕ И БАЗОВАЯ ИНИЦИАЛИЗАЦИЯ
// ==================================================

let menuOpen = false;
let accountBalances = {};
let quillEditor = null;
let equityChartInstance = null;
let mpaQuill = null;
let qpaQuill = null;
let tfCount = 0;
let isPlanEditMode = false;
let tradeImgCount = 0;
let isTradeEditMode = false;
let currentMpaData = null;

// --- Регистрация шрифтов Quill ---
if (typeof Quill !== 'undefined') {
    const Font = Quill.import('formats/font');
    if (Font) {
        Font.whitelist = ['inter', 'roboto', 'serif', 'monospace', 'Montserrat'];
        Quill.register(Font, true);
    } else {
        console.warn("Failed to import Quill font module");
    }
} else {
    console.warn("Quill not found");
}

// --- Глобальная настройка тултипов ---
const tooltipOptions = {
    animation: true,
    html: true,
    placement: 'top',
    delay: { "show": 100, "hide": 100 },
    trigger: 'hover focus'
};

// Функция для защиты от XSS (экранирование опасных символов)
function escapeHTML(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function makeTooltip(el) {
    if (el && el.hasAttribute('title') && !bootstrap.Tooltip.getInstance(el)) {
        new bootstrap.Tooltip(el, tooltipOptions);
    }
}

// MutationObserver для автоматической инициализации тултипов
const observer = new MutationObserver((mutationsList) => {
    const processedNodes = new Set();
    mutationsList.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
            if (node.nodeType === 1 && !processedNodes.has(node)) {
                processedNodes.add(node);
                makeTooltip(node);
                node.querySelectorAll('[title]').forEach(makeTooltip);
            }
        });
    });
});

// --- Меню ---
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

// --- Конфигурация Quill (полная панель) ---
const fullToolbarOptions = [
    ['bold', 'italic', 'underline', 'strike'],
    ['blockquote', 'code-block'],
    [{ 'header': 1 }, { 'header': 2 }],
    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
    [{ 'script': 'sub'}, { 'script': 'super' }],
    [{ 'indent': '-1'}, { 'indent': '+1' }],
    [{ 'direction': 'rtl' }],
    [{ 'size': ['small', false, 'large', 'huge'] }],
    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
    [{ 'color': [] }, { 'background': [] }],
    [{ 'font': ['inter', 'roboto', 'serif', 'monospace', 'Montserrat'] }],
    [{ 'align': [] }],
    ['clean'],
    ['link', 'image', 'video']
];
