// Тосты: закрытие по кнопке и автоскрытие ([data-autohide] — миллисекунды). Наведение ставит таймер на паузу.

function dismiss(toast) {
    if (toast.dataset.leaving) {
        return;
    }
    toast.dataset.leaving = '1';
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    setTimeout(() => toast.remove(), reduce ? 0 : 200);
}

function arm(toast) {
    const delay = Number(toast.dataset.autohide);
    if (!delay) {
        return;
    }
    let timer = setTimeout(() => dismiss(toast), delay);
    toast.addEventListener('mouseenter', () => clearTimeout(timer));
    toast.addEventListener('focusin', () => clearTimeout(timer));
    toast.addEventListener('mouseleave', () => {
        timer = setTimeout(() => dismiss(toast), delay / 2);
    });
}

export function initToasts(root = document) {
    root.querySelectorAll('[data-toast]').forEach(arm);

    root.addEventListener('click', (event) => {
        const close = event.target.closest('[data-toast-close]');
        if (close) {
            dismiss(close.closest('[data-toast]'));
        }
    });
}

/**
 * Показать тост из скрипта: VetMoment.toast({ tone: 'success', title: 'Сохранено', text: '' }).
 * Разметка повторяет компонент x-toast, шаблон — <template id="toast-template"> (если есть на странице).
 */
export function showToast({ title, text = '' }) {
    const region = document.getElementById('toasts');
    const template = document.getElementById('toast-template');
    if (!region || !(template instanceof HTMLTemplateElement)) {
        return;
    }
    const toast = template.content.firstElementChild.cloneNode(true);
    toast.querySelector('[data-toast-title]').textContent = title;
    toast.querySelector('[data-toast-text]').textContent = text;
    region.append(toast);
    arm(toast);
}
