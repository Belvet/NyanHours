(() => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!csrfToken) return;
    const sanitizeDuration = (value) => value.replace(/[^0-9:.,]/g, '');
    const normalizeDuration = (value) => {
        value = value.trim().replace(',', '.');
        let minutes;
        const colon = value.match(/^(\d{1,2}):([0-5]?\d)$/);
        if (colon) {
            const minutePart = colon[2].length === 1 ? Number(colon[2]) * 10 : Number(colon[2]);
            minutes = Number(colon[1]) * 60 + minutePart;
        } else if (/^\d{3,4}$/.test(value)) {
            const hours = Number(value.slice(0, -2));
            const minutePart = Number(value.slice(-2));
            if (hours > 24 || minutePart > 59) return null;
            minutes = hours * 60 + minutePart;
        } else if (/^\d{1,2}(?:\.\d+)?$/.test(value)) {
            minutes = Math.round(Number(value) * 60);
        } else {
            return null;
        }
        if (minutes < 1 || minutes > 1440) return null;
        return {minutes, formatted: `${Math.floor(minutes/60)}:${String(minutes%60).padStart(2,'0')}`};
    };
    document.querySelectorAll('.duration-value-input').forEach((input) => {
        input.addEventListener('input', () => { input.value = sanitizeDuration(input.value); });
        input.addEventListener('blur', () => {
            const normalized = normalizeDuration(input.value);
            if (normalized) input.value = normalized.formatted;
        });
    });

    document.querySelectorAll('.inline-description').forEach((button) => {
        button.addEventListener('click', () => {
            if (button.dataset.editing === 'true') return;
            button.dataset.editing = 'true';
            const original = button.dataset.description || '';
            const input = document.createElement('input');
            input.className = 'inline-description-input';
            input.value = original;
            input.placeholder = 'Escribí la actividad…';
            input.maxLength = 1000;
            button.replaceWith(input);
            input.focus();
            input.select();

            let finished = false;
            const cancel = () => {
                if (finished) return;
                finished = true; input.replaceWith(button); button.dataset.editing = 'false';
            };
            const save = async () => {
                if (finished) return;
                const description = input.value.trim();
                if (!description) { input.classList.add('input-error'); input.focus(); return; }
                finished = true; input.disabled = true;
                const body = new URLSearchParams({csrf_token: csrfToken, id: button.dataset.entryId, description});
                try {
                    const response = await fetch('/time-entries/update-description.php', {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body});
                    const result = await response.json();
                    if (!response.ok || !result.ok) throw new Error(result.message || 'No se pudo guardar.');
                    button.textContent = result.description; button.dataset.description = result.description; button.classList.remove('is-empty');
                    input.replaceWith(button); button.dataset.editing = 'false';
                } catch (error) {
                    finished = false; input.disabled = false; input.classList.add('input-error');
                    input.title = error.message; input.focus();
                }
            };
            input.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') { event.preventDefault(); save(); }
                if (event.key === 'Escape') { event.preventDefault(); cancel(); }
            });
            input.addEventListener('blur', save);
        });
    });

    document.querySelectorAll('.inline-duration').forEach((button) => {
        button.addEventListener('click', () => {
            if (button.dataset.editing === 'true') return;
            button.dataset.editing = 'true';
            const originalMinutes = Number(button.dataset.minutes);
            const input = document.createElement('input');
            input.className = 'inline-duration-input';
            input.value = `${Math.floor(originalMinutes / 60)}:${String(originalMinutes % 60).padStart(2, '0')}`;
            input.inputMode = 'decimal';
            input.pattern = '[0-9:.,]+';
            button.replaceWith(input); input.focus(); input.select();
            let finished = false;
            input.addEventListener('input', () => {
                input.value = sanitizeDuration(input.value);
                input.classList.remove('input-error');
                input.removeAttribute('title');
            });
            const cancel = () => { if (finished) return; finished = true; input.replaceWith(button); button.dataset.editing = 'false'; };
            const save = async () => {
                if (finished) return;
                const normalized = normalizeDuration(input.value);
                if (!normalized) {
                    input.classList.add('input-error');
                    input.title = 'Usá un valor entre 0:01 y 24:00, por ejemplo 1, 1.5 o 1:30';
                    input.focus();
                    return;
                }
                input.value = normalized.formatted;
                finished = true; input.disabled = true;
                const body = new URLSearchParams({csrf_token: csrfToken, id: button.dataset.entryId, duration: input.value.trim()});
                try {
                    const response = await fetch('/time-entries/update-duration.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body});
                    const result = await response.json();
                    if (!response.ok || !result.ok) throw new Error(result.message || 'No se pudo guardar.');
                    button.textContent = result.formatted; button.dataset.minutes = result.minutes;
                    input.replaceWith(button); button.dataset.editing = 'false';
                    const day = button.closest('.tracker-day');
                    const total = [...day.querySelectorAll('.inline-duration')].reduce((sum, item) => sum + Number(item.dataset.minutes), 0);
                    day.querySelector('header strong').textContent = `Total: ${Math.floor(total/60)}:${String(total%60).padStart(2,'0')} hs`;
                } catch (error) {
                    finished = false; input.disabled = false; input.classList.add('input-error');
                    input.title = error.message; input.focus();
                }
            };
            input.addEventListener('keydown', (event) => { if(event.key==='Enter'){event.preventDefault();save();} if(event.key==='Escape'){event.preventDefault();cancel();} });
            input.addEventListener('blur', save);
        });
    });
})();
