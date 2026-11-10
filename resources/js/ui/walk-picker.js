// Выбор слота выгула (макет 06): выбранное время переносится в итоговую плашку и липкую панель
// ([data-walk-summary-title] / [data-walk-summary-text]). Данные — в data-summary-* у <label> слота.
// Без JS плашка просит выбрать время, а «Продолжить» отправляет форму как обычно.

export function initWalkPicker(root = document) {
    root.querySelectorAll('[data-walk-picker]').forEach((form) => {
        const titles = document.querySelectorAll('[data-walk-summary-title]');
        const texts = document.querySelectorAll('[data-walk-summary-text]');
        const update = (input) => {
            const label = input.closest('label');
            if (!label || !label.dataset.summaryTitle) {
                return;
            }
            titles.forEach((el) => { el.textContent = label.dataset.summaryTitle; });
            texts.forEach((el) => { el.textContent = label.dataset.summaryText || ''; });
        };
        const inputs = form.querySelectorAll('input[name="slot_id"]');
        inputs.forEach((input) => input.addEventListener('change', () => input.checked && update(input)));
        const checked = [...inputs].find((i) => i.checked);
        if (checked) {
            update(checked);
        }
    });
}
