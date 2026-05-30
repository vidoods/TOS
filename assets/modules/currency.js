// assets/modules/currency.js

const CurrencyManager = {
    // Дефолтная валюта. Берем из localStorage или ставим USD
    currentCurrency: localStorage.getItem('tradeos_currency') || 'USD',

    // Соответствие валют и локалей
    locales: {
        'USD': 'en-US',
        'EUR': 'de-DE',
        'RUB': 'ru-RU',
        'UAH': 'uk-UA'
    },

    /**
     * Инициализация менеджера валют
     * @param {string} sessionCurrency - Валюта из PHP сессии
     */
    init(sessionCurrency) {
        if (sessionCurrency) {
            this.currentCurrency = sessionCurrency;
            localStorage.setItem('tradeos_currency', sessionCurrency);
        }
    },

    /**
     * Главная функция форматирования денежных сумм
     * @param {number|string} amount - Число
     * @returns {string} - Строка с символом валюты
     */
    format(amount) {
        const value = parseFloat(amount);
        if (isNaN(value)) return '0.00';

        const locale = this.locales[this.currentCurrency] || 'en-US';
        
        try {
            let formatted = new Intl.NumberFormat(locale, {
                style: 'currency',
                currency: this.currentCurrency,
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(value);

            // Если выбрана гривна, заменяем текст "грн" на красивый символ "₴"
            if (this.currentCurrency === 'UAH') {
                formatted = formatted.replace(/грн\.?|UAH/g, '').trim() + ' ₴';
            }
            return formatted;
        } catch (e) {
            // Резервный вариант, если Intl в браузере сбойнул, чтобы сайт не ломался
            return value.toFixed(2) + ' ' + this.currentCurrency;
        }
    },

    /**
     * Смена глобальной валюты
     * @param {string} newCurrency - Код валюты
     */
    async change(newCurrency) {
        if (!this.locales[newCurrency]) return;

        this.currentCurrency = newCurrency;
        localStorage.setItem('tradeos_currency', newCurrency);

        try {
            const formData = new FormData();
            formData.append('type', 'currency');
            formData.append('currency', newCurrency);

            await fetch('api/api.php?action=add_settings', {
                method: 'POST',
                body: formData
            });

            if (window.showToast) {
                window.showToast('Валюта успешно изменена!', 'success');
            }

            location.reload(); 
        } catch (e) {
            console.error('Ошибка сохранения валюты на сервере:', e);
            location.reload(); // Перезагружаем в любом случае, чтобы применить фронтенд
        }
    }
};

// Экспортируем в глобальный объект window, чтобы другие файлы сразу его видели
window.CurrencyManager = CurrencyManager;