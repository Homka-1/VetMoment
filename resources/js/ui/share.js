// «Поделиться»: системное меню телефона (Web Share API), иначе — копирование ссылки и тост.
import { showToast } from './toasts';

export function initShare(root = document) {
    root.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-share]');
        if (!button) {
            return;
        }
        const url = button.dataset.share;
        const title = button.dataset.shareTitle || document.title;

        if (navigator.share) {
            try {
                await navigator.share({ title, url });
            } catch {
                // Человек закрыл меню — ничего не делаем.
            }
            return;
        }

        try {
            await navigator.clipboard.writeText(url);
            showToast({ title: 'Ссылка скопирована' });
        } catch {
            window.prompt('Скопируйте ссылку', url);
        }
    });
}
