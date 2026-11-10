// Мелкие улучшения форм: счётчик символов у textarea и выбор суммы пожертвования.

const nf = new Intl.NumberFormat('ru-RU');

function initCounters(root) {
    root.querySelectorAll('textarea[data-counter][maxlength]').forEach((field) => {
        const out = document.querySelector(`[data-counter-for="${field.id}"]`);
        if (!out) {
            return;
        }
        const update = () => {
            out.textContent = `${field.value.length} / ${field.maxLength}`;
        };
        field.addEventListener('input', update);
        update();
    });
}

function initAmountPickers(root) {
    root.querySelectorAll('[data-amount-picker]').forEach((picker) => {
        const input = picker.querySelector('[data-amount-input]');
        const presets = picker.querySelectorAll('[data-amount-preset]');
        if (!input) {
            return;
        }
        // Пресет → сумма в поле «Другая сумма» (форма отправляет одно поле).
        presets.forEach((preset) => {
            preset.addEventListener('change', () => {
                if (preset.checked) {
                    input.value = nf.format(Number(preset.value));
                }
            });
        });
        // Своя сумма снимает выбор пресета, если не совпадает с ним.
        input.addEventListener('input', () => {
            const digits = input.value.replace(/\D/g, '');
            presets.forEach((preset) => {
                preset.checked = preset.value === digits;
            });
        });

        // Кнопка формы называет сумму: «Пожертвовать 5 000 ₸».
        const label = picker.closest('form')?.querySelector('[data-amount-label]');
        if (label) {
            const update = () => {
                const amount = Number(input.value.replace(/\D/g, ''));
                const base = label.dataset.labelBase;
                label.textContent = amount > 0 ? `${base} ${nf.format(amount)}\u00a0₸` : base;
            };
            input.addEventListener('input', update);
            presets.forEach((preset) => preset.addEventListener('change', update));
        }
    });
}

export function initForms(root = document) {
    initCounters(root);
    initAmountPickers(root);
}
