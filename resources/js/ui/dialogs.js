// Модальные окна и мобильное меню на <dialog>.
// Открыть: [data-modal-open="id"]; закрыть: [data-modal-close], Esc или клик по затемнению.
// Выпадающие меню на <details data-dropdown> закрываются кликом мимо и по Esc.

export function initDialogs(root = document) {
    root.addEventListener('click', (event) => {
        const opener = event.target.closest('[data-modal-open]');
        if (opener) {
            const dialog = document.getElementById(opener.dataset.modalOpen);
            if (dialog instanceof HTMLDialogElement) {
                event.preventDefault();
                dialog.showModal();
                opener.setAttribute('aria-expanded', 'true');
                dialog.addEventListener('close', () => {
                    opener.setAttribute('aria-expanded', 'false');
                    opener.focus();
                }, { once: true });
            }
            return;
        }

        const closer = event.target.closest('[data-modal-close]');
        if (closer) {
            closer.closest('dialog')?.close();
            return;
        }

        // Клик по затемнению: событие приходит на сам <dialog>, а не на содержимое.
        if (event.target instanceof HTMLDialogElement && event.target.open) {
            const rect = event.target.getBoundingClientRect();
            const inside = event.clientX >= rect.left && event.clientX <= rect.right
                && event.clientY >= rect.top && event.clientY <= rect.bottom;
            if (!inside) {
                event.target.close();
            }
        }

        // Выпадающие меню: закрыть все, кроме того, внутри которого клик.
        document.querySelectorAll('details[data-dropdown][open]').forEach((details) => {
            if (!details.contains(event.target)) {
                details.open = false;
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }
        document.querySelectorAll('details[data-dropdown][open]').forEach((details) => {
            details.open = false;
            details.querySelector('summary')?.focus();
        });
    });

    // Мобильное меню закрывается при переходе на ширину десктопа.
    const desktop = window.matchMedia('(min-width: 64rem)');
    desktop.addEventListener('change', (e) => {
        if (e.matches) {
            document.getElementById('mobile-menu')?.close();
        }
    });
}
