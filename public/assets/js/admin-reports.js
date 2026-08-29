(() => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!csrfToken) return;

    document.querySelectorAll('.admin-inline-activity').forEach((button) => {
        button.addEventListener('click', () => {
            if (button.dataset.editing === 'true') return;
            button.dataset.editing = 'true';
            const original = button.dataset.activity || '';
            const input = document.createElement('input');
            input.className = 'inline-description-input admin-activity-input';
            input.value = original;
            input.maxLength = 1000;
            button.replaceWith(input);
            input.focus();
            input.select();

            let finished = false;
            const cancel = () => {
                if (finished) return;
                finished = true;
                input.replaceWith(button);
                button.dataset.editing = 'false';
            };
            const save = async () => {
                if (finished) return;
                const activity = input.value.trim();
                if (!activity) {
                    input.classList.add('input-error');
                    input.title = 'Escribí una actividad.';
                    input.focus();
                    return;
                }
                if (activity === original) { cancel(); return; }
                finished = true;
                input.disabled = true;
                const body = new URLSearchParams({
                    csrf_token: csrfToken,
                    user_id: button.dataset.userId,
                    client_id: button.dataset.clientId,
                    work_date: button.dataset.date,
                    original_activity: original,
                    new_activity: activity,
                });
                try {
                    const response = await fetch('/admin/update-report-activity.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body,
                    });
                    const result = await response.json();
                    if (!response.ok || !result.ok) throw new Error(result.message || 'No se pudo guardar.');
                    button.textContent = result.activity;
                    button.dataset.activity = result.activity;
                    input.replaceWith(button);
                    button.dataset.editing = 'false';
                } catch (error) {
                    finished = false;
                    input.disabled = false;
                    input.classList.add('input-error');
                    input.title = error.message;
                    input.focus();
                }
            };
            input.addEventListener('input', () => input.classList.remove('input-error'));
            input.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') { event.preventDefault(); save(); }
                if (event.key === 'Escape') { event.preventDefault(); cancel(); }
            });
            input.addEventListener('blur', save);
        });
    });
})();
