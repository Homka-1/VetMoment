// Минимальный JS интерфейса VetMoment — без фреймворков, на data-атрибутах.
// Всё работает и без скрипта (прогрессивное улучшение); те же модули пойдут в статический макет (этап 15).
import { initDialogs } from './ui/dialogs';
import { initToasts, showToast } from './ui/toasts';
import { initForms } from './ui/forms';
import { initGallery } from './ui/gallery';
import { initDayPicker } from './ui/day-picker';

function init(root = document) {
    initDialogs(root);
    initToasts(root);
    initForms(root);
    initGallery(root);
    initDayPicker(root);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => init());
} else {
    init();
}

window.VetMoment = { init, toast: showToast };
