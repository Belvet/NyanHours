(() => {
    const items = document.querySelector('#invoice-items');
    const rate = document.querySelector('#invoice_rate');
    const currency = document.querySelector('#currency');
    const total = document.querySelector('#invoice-total');
    const payment = document.querySelector('#payment_details');
    if (!items || !rate || !currency || !total || !payment) return;

    const savedPayment = {};
    const money = (amount) => `${currency.value} ${Number(amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    const calculate = () => {
        let sum = 0;
        items.querySelectorAll('.invoice-item:not(.invoice-item-header)').forEach((row) => {
            const hours = Number(row.querySelector('.invoice-hours')?.value || 0);
            const amount = Math.max(0, hours) * Math.max(0, Number(rate.value || 0));
            row.querySelector('.invoice-line-total').textContent = money(amount);
            sum += amount;
        });
        total.textContent = money(sum);
    };
    const setPaymentTemplate = (previousCurrency = null) => {
        if (previousCurrency) savedPayment[previousCurrency] = payment.value;
        const template = document.querySelector(`#${currency.value.toLowerCase()}-payment-details`)?.value || '';
        payment.value = savedPayment[currency.value] ?? template;
    };
    let activeCurrency = currency.value;
    currency.addEventListener('change', () => { setPaymentTemplate(activeCurrency); activeCurrency = currency.value; calculate(); });
    rate.addEventListener('input', calculate);
    items.addEventListener('input', calculate);
    items.addEventListener('click', (event) => {
        const remove = event.target.closest('.invoice-remove');
        if (!remove) return;
        const rows = items.querySelectorAll('.invoice-item:not(.invoice-item-header)');
        if (rows.length > 1) remove.closest('.invoice-item').remove();
        calculate();
    });
    document.querySelector('#add-invoice-item')?.addEventListener('click', () => {
        const row = document.createElement('div');
        row.className = 'invoice-item';
        row.innerHTML = '<input name="item_description[]" maxlength="180" placeholder="Service description" required><input class="invoice-hours" name="item_hours[]" type="number" min="0.01" step="0.01" value="1.00" required><output class="invoice-line-total">USD 0.00</output><button class="invoice-remove" type="button" aria-label="Remove item">×</button>';
        items.appendChild(row);
        row.querySelector('input').focus();
        calculate();
    });
    setPaymentTemplate();
    calculate();
})();
