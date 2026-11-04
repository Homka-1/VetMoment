// Календарь выгула: выбор дня показывает панель слотов этого дня ([data-day-panel="YYYY-MM-DD"]).
// Панели лежат в том же [data-day-scope] (или во всём документе). Без JS видны все дни списком.

export function initDayPicker(root = document) {
    root.querySelectorAll('[data-day-picker]').forEach((picker) => {
        const scope = picker.closest('[data-day-scope]') || document;
        const panels = scope.querySelectorAll('[data-day-panel]');
        if (!panels.length) {
            return;
        }
        const show = (day) => {
            panels.forEach((panel) => {
                panel.hidden = panel.dataset.dayPanel !== day;
            });
        };
        const inputs = picker.querySelectorAll('[data-day-input]');
        inputs.forEach((input) => input.addEventListener('change', () => input.checked && show(input.value)));
        const current = [...inputs].find((i) => i.checked) || inputs[0];
        if (current) {
            current.checked = true;
            show(current.value);
        }
    });
}
