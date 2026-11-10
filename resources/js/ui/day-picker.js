// Календарь выгула: выбор дня показывает панель слотов этого дня ([data-day-panel="YYYY-MM-DD"]).
// Панели лежат в том же [data-day-scope] (или во всём документе). Без JS видны все дни списком.
// [data-day-scope="responsive"]: панели не скрываются атрибутом hidden, а получают [data-day-inactive] —
// так на телефоне виден один день (класс max-lg:data-day-inactive:hidden), а на десктопе — вся неделя.

export function initDayPicker(root = document) {
    root.querySelectorAll('[data-day-picker]').forEach((picker) => {
        const scope = picker.closest('[data-day-scope]') || document;
        const panels = scope.querySelectorAll('[data-day-panel]');
        if (!panels.length) {
            return;
        }
        const responsive = scope instanceof Element && scope.dataset.dayScope === 'responsive';
        const show = (day) => {
            panels.forEach((panel) => {
                const inactive = panel.dataset.dayPanel !== day;
                if (responsive) {
                    panel.toggleAttribute('data-day-inactive', inactive);
                } else {
                    panel.hidden = inactive;
                }
            });
        };
        const inputs = picker.querySelectorAll('[data-day-input]');
        inputs.forEach((input) => input.addEventListener('change', () => input.checked && show(input.value)));
        const current = [...inputs].find((i) => i.checked) || inputs[0];
        if (current) {
            current.checked = true;
            show(current.value);
            // Выбранный день мог оказаться за краем ленты (телефон) — прокручиваем ленту, не страницу.
            const label = current.closest('label');
            const strip = label && label.parentElement;
            if (strip && strip.scrollWidth > strip.clientWidth) {
                strip.scrollLeft = label.offsetLeft - strip.offsetLeft - (strip.clientWidth - label.offsetWidth) / 2;
            }
        }
    });
}
