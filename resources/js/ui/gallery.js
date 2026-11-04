// Галерея фото: клик по миниатюре меняет большое фото (без перехода по ссылке) и счётчик «N / M».

export function initGallery(root = document) {
    root.querySelectorAll('[data-gallery]').forEach((gallery) => {
        const main = gallery.querySelector('[data-gallery-main]');
        const index = gallery.querySelector('[data-gallery-index]');
        gallery.addEventListener('click', (event) => {
            const thumb = event.target.closest('[data-gallery-thumb]');
            if (!thumb || !main) {
                return;
            }
            event.preventDefault();
            main.src = thumb.dataset.galleryThumb;
            if (thumb.dataset.galleryAlt) {
                main.alt = thumb.dataset.galleryAlt;
            }
            if (index && thumb.dataset.galleryPosition) {
                index.textContent = thumb.dataset.galleryPosition;
            }
            gallery.querySelectorAll('[data-gallery-thumb]').forEach((t) => t.removeAttribute('aria-current'));
            thumb.setAttribute('aria-current', 'true');
        });
    });
}
