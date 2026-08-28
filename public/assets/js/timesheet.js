(() => {
    const form = document.querySelector('#timesheet-form');
    if (!form) return;
    const parse = (value) => {
        value = value.trim().replace(',', '.');
        if (!value) return 0;
        const colon = value.match(/^(\d{1,2}):([0-5]?\d)$/);
        if (colon) {
            const minutePart = colon[2].length === 1 ? Number(colon[2]) * 10 : Number(colon[2]);
            return Number(colon[1]) * 60 + minutePart;
        }
        if (/^\d{3,4}$/.test(value)) {
            const hours = Number(value.slice(0,-2));
            const minutes = Number(value.slice(-2));
            return hours <= 24 && minutes <= 59 ? hours * 60 + minutes : 0;
        }
        const hours = Number(value);
        return Number.isFinite(hours) ? Math.round(hours * 60) : 0;
    };
    const format = (minutes) => `${Math.floor(minutes / 60)}:${String(minutes % 60).padStart(2, '0')}`;
    form.querySelectorAll('.hours-input').forEach((input) => {
        input.addEventListener('input', () => { input.value = input.value.replace(/[^0-9:.,]/g, ''); });
        input.addEventListener('blur', () => {
            const minutes = parse(input.value);
            if (input.value.trim() && minutes > 0) input.value = format(minutes);
        });
    });
    const update = () => {
        const columns = Array(7).fill(0);
        let week = 0;
        form.querySelectorAll('[data-client-row]').forEach((row) => {
            let rowTotal = 0;
            row.querySelectorAll('.hours-input').forEach((input, index) => {
                const minutes = parse(input.value);
                rowTotal += minutes; columns[index] += minutes;
            });
            row.querySelector('.row-total').textContent = format(rowTotal);
            week += rowTotal;
        });
        form.querySelectorAll('.day-total').forEach((cell, index) => cell.textContent = format(columns[index]));
        document.querySelector('#week-total').textContent = format(week);
    };
    form.addEventListener('input', update);
    update();
})();
