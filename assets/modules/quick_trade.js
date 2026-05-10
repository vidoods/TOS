// assets/modules/quick_trade.js
// ==================================================
// QUICK TRADE MODAL
// ==================================================

async function initQuickTrade() {
    const dateInput = document.getElementById('quick-date');
    if (dateInput) {
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        dateInput.value = now.toISOString().slice(0, 16);
    }

    const pairSelect = document.getElementById('quick-pair');
    if (pairSelect && pairSelect.options.length <= 1) {
        try {
            const res = await fetch('api/api.php?action=get_ref_pairs');
            const json = await res.json();

            if (json.success) {
                pairSelect.innerHTML = `<option value="">${window.lang['select']}</option>`;
                json.data.forEach(p => {
                    pairSelect.innerHTML += `<option value="${p.id}">${p.symbol}</option>`;
                });
            }
        } catch (e) { console.error("Error loading pairs:", e); }
    }

    const accSelect = document.getElementById('quick-account');
    if (accSelect && accSelect.options.length <= 1) {
        try {
            const res = await fetch('api/api.php?action=get_accounts');
            const json = await res.json();

            if (json.success) {
                accSelect.innerHTML = `<option value="">${window.lang['select']}</option>`;
                json.data.forEach(a => {
                    accSelect.innerHTML += `<option value="${a.id}">${a.name} (${a.currency})</option>`;
                });
            }
        } catch (e) { console.error("Error loading accounts:", e); }
    }
}

// Quick Trade form submit handler
document.addEventListener('DOMContentLoaded', function() {
    const quickForm = document.getElementById('quick-trade-form');

    if (quickForm) {
        quickForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(quickForm);
            const data = Object.fromEntries(formData.entries());

            const btn = quickForm.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = window.lang['saving'];

            try {
                const res = await fetch('api/api.php?action=save_trade', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await res.json();

                if (result.success) {
                    const modalEl = document.getElementById('quickAddModal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    modal.hide();

                    if (typeof Toastify === 'function') {
                        Toastify({text: window.lang['trade_added_successfully'], backgroundColor: "#198754"}).showToast();
                    } else {
                        alert(window.lang['trade_added_successfully']);
                    }

                    setTimeout(() => location.reload(), 500);
                } else {
                    alert(window.lang['error'] + ': ' + (result.message || window.lang['unknown_error']));
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            } catch (err) {
                console.error(err);
                alert(window.lang['network_error']);
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    }
});
