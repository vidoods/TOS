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

    init(sessionCurrency) {
        if (sessionCurrency) {
            this.currentCurrency = sessionCurrency;
            localStorage.setItem('tradeos_currency', sessionCurrency);
        }
    },

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

            if (this.currentCurrency === 'UAH') {
                formatted = formatted.replace(/грн\.?|UAH/g, '').trim() + ' ₴';
            }
            return formatted;
        } catch (e) {
            return value.toFixed(2) + ' ' + this.currentCurrency;
        }
    },

    async change(newCurrency) {
        if (!this.locales[newCurrency]) return;

        // Сразу меняем визуально
        this.currentCurrency = newCurrency;
        localStorage.setItem('tradeos_currency', newCurrency);

        try {
            const formData = new FormData();
            formData.append('action', 'add_user_setting'); // (или то слово, которое у вас заработало)
            formData.append('type', 'currency');
            formData.append('currency', newCurrency);

            // Отправляем в базу
            await fetch('api/api.php', {
                method: 'POST',
                body: formData
            });

            // Мгновенная перезагрузка без задержек
            location.reload(); 
        } catch (e) {
            console.error('Ошибка сохранения валюты на сервере:', e);
            location.reload(); 
        }
    }
};

// ВАЖНО: Делаем объект доступным глобально (для onclick)
window.CurrencyManager = CurrencyManager;