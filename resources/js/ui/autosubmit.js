// Фильтры каталога применяются сразу: изменение поля отправляет GET-форму [data-autosubmit].
// Внутри открытого нижнего листа (телефон) — нет: там человек жмёт «Показать питомцев».
// Кнопка [data-autosubmit-fallback] нужна только без JS и скрывается.

export function initAutosubmit(root = document) {
    root.querySelectorAll('form[data-autosubmit]').forEach((form) => {
        form.querySelectorAll('[data-autosubmit-fallback]').forEach((button) => {
            button.hidden = true;
        });

        const fields = [...form.elements].concat([...document.querySelectorAll(`[form="${form.id}"]`)]);
        const unique = [...new Set(fields)];

        unique.forEach((field) => {
            if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement)) {
                return;
            }
            if (field.type === 'search' || field.type === 'text') {
                return; // поиск — по Enter
            }
            field.addEventListener('change', () => {
                const sheet = field.closest('dialog');
                if (sheet && sheet.open) {
                    return;
                }
                form.requestSubmit();
            });
        });
    });
}
