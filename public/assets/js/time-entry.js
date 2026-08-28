(() => {
    const start = document.querySelector('#start_time');
    const end = document.querySelector('#end_time');
    const output = document.querySelector('#duration');
    if (!start || !end || !output) return;

    const update = () => {
        if (!start.value || !end.value) { output.textContent = '—'; return; }
        const [startHour, startMinute] = start.value.split(':').map(Number);
        const [endHour, endMinute] = end.value.split(':').map(Number);
        const minutes = (endHour * 60 + endMinute) - (startHour * 60 + startMinute);
        output.textContent = minutes > 0
            ? `${Math.floor(minutes / 60)}:${String(minutes % 60).padStart(2, '0')} hs`
            : 'Horario inválido';
    };
    start.addEventListener('input', update);
    end.addEventListener('input', update);
    update();
})();
