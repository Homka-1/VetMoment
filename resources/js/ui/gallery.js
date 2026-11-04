// Галерея фото: клик по миниатюре меняет большое фото (без перехода по ссылке).

export function initGallery(root = document) {
    root.querySelectorAll('[data-gallery]').forEach((gallery) => {
        const main = gallery.querySelector('[data-gallery-main]');
        gallery.addEventListener('click', (event) => {
            const thumb = event.target.closest('[data-gallery-thumb]');
            if (!thumb || !main) {
                return;
            }
            event.preventDefault();
            main.src = thumb.dataset.galleryThumb;
            gallery.querySelectorAll('[data-gallery-thumb]').forEach((t) => t.removeAttribute('aria-current'));
            thumb.setAttribute('aria-current', 'true');
        });
    });
}
