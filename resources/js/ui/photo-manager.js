// Фото в анкете питомца ([data-photo-manager]):
// — порядок загруженных фото — перетаскиванием и стрелками; номера пишутся в скрытые поля photos[id][sort];
// — превью выбранных файлов с выбором обложки (cover=new:N) и предупреждением о лимите;
// — перетаскивание файлов на зону загрузки.
// Без JS работают поля «Порядок», «Обложка», «Удалить» и обычный выбор файлов.

function renumber(list) {
    list.querySelectorAll('[data-photo-item]').forEach((item, index) => {
        const sort = item.querySelector('[data-photo-sort]');
        if (sort) {
            sort.value = String(index);
        }
    });
}

function initOrdering(manager, list) {
    const items = () => [...list.querySelectorAll('[data-photo-item]')];
    if (items().length === 0) {
        return;
    }

    manager.querySelectorAll('[data-photo-js]').forEach((el) => {
        el.hidden = false;
    });
    items().forEach((item) => {
        item.draggable = true;
        item.classList.add('cursor-grab');
        item.querySelector('[data-photo-sort-wrap]')?.classList.add('hidden');
        const move = item.querySelector('[data-photo-move-wrap]');
        move?.classList.remove('hidden');
        move?.classList.add('flex');
    });

    // Сначала упорядочиваем по текущим номерам, затем нумеруем подряд.
    items()
        .sort((a, b) => Number(a.querySelector('[data-photo-sort]')?.value || 0) - Number(b.querySelector('[data-photo-sort]')?.value || 0))
        .forEach((item) => list.append(item));
    renumber(list);

    list.addEventListener('click', (event) => {
        const button = event.target.closest('[data-photo-move]');
        if (!button) {
            return;
        }
        const item = button.closest('[data-photo-item]');
        const step = Number(button.dataset.photoMove);
        const sibling = step < 0 ? item.previousElementSibling : item.nextElementSibling;
        if (sibling) {
            step < 0 ? sibling.before(item) : sibling.after(item);
            renumber(list);
            button.focus();
        }
    });

    let dragged = null;
    list.addEventListener('dragstart', (event) => {
        dragged = event.target.closest('[data-photo-item]');
        dragged?.classList.add('opacity-50');
        event.dataTransfer.effectAllowed = 'move';
    });
    list.addEventListener('dragover', (event) => {
        if (!dragged) {
            return;
        }
        event.preventDefault();
        const over = event.target.closest('[data-photo-item]');
        if (!over || over === dragged) {
            return;
        }
        const rect = over.getBoundingClientRect();
        const after = event.clientX > rect.left + rect.width / 2;
        after ? over.after(dragged) : over.before(dragged);
    });
    list.addEventListener('dragend', () => {
        dragged?.classList.remove('opacity-50');
        dragged = null;
        renumber(list);
    });
}

function initPreviews(manager, input, preview) {
    const max = Number(manager.dataset.photoMax || 10);
    const existing = () => manager.querySelectorAll('[data-photo-item]').length;

    const render = () => {
        preview.replaceChildren();
        const files = [...input.files];
        preview.hidden = files.length === 0;
        const hasCover = manager.querySelector('input[name="cover"]:checked');

        files.forEach((file, index) => {
            const li = document.createElement('li');
            li.className = 'grid gap-2 rounded-md border border-subtle bg-white p-2';

            const frame = document.createElement('div');
            frame.className = 'aspect-[4/3] overflow-hidden rounded-sm bg-ink-100';
            const img = document.createElement('img');
            img.className = 'size-full object-cover';
            img.alt = `Новое фото ${index + 1}: ${file.name}`;
            img.src = URL.createObjectURL(file);
            img.onload = () => URL.revokeObjectURL(img.src);
            frame.append(img);

            const label = document.createElement('label');
            label.className = 'inline-flex cursor-pointer items-center gap-3 py-1 text-sm';
            const radio = document.createElement('input');
            radio.type = 'radio';
            radio.name = 'cover';
            radio.value = `new:${index}`;
            radio.checked = !hasCover && index === 0;
            radio.className = 'size-5 shrink-0 cursor-pointer appearance-none rounded-pill border-control border-strong bg-white checked:border-[6px] checked:border-ink-900';
            label.append(radio, document.createTextNode('Обложка'));

            li.append(frame, label);
            if (file.size > 8 * 1024 * 1024) {
                const warn = document.createElement('p');
                warn.className = 'text-xs font-medium text-danger-fg';
                warn.textContent = 'Больше 8 МБ — не загрузится';
                li.append(warn);
            }
            preview.append(li);
        });

        const over = existing() + files.length - max;
        input.setCustomValidity(over > 0 ? `Можно добавить не больше ${max - existing()} фото` : '');
        if (over > 0) {
            const note = document.createElement('li');
            note.className = 'col-span-full text-sm font-medium text-danger-fg';
            note.textContent = `Выбрано на ${over} больше, чем можно: у питомца может быть до ${max} фото.`;
            preview.append(note);
        }
    };

    input.addEventListener('change', render);

    const zone = input.closest('label');
    zone?.addEventListener('dragover', (event) => {
        event.preventDefault();
        zone.classList.add('border-ink-900');
    });
    zone?.addEventListener('dragleave', () => zone.classList.remove('border-ink-900'));
    zone?.addEventListener('drop', (event) => {
        event.preventDefault();
        zone.classList.remove('border-ink-900');
        if (event.dataTransfer?.files?.length) {
            input.files = event.dataTransfer.files;
            render();
        }
    });
}

export function initPhotoManager(root = document) {
    root.querySelectorAll('[data-photo-manager]').forEach((manager) => {
        const list = manager.querySelector('[data-photo-list]');
        const input = manager.querySelector('[data-photo-input]');
        const preview = manager.querySelector('[data-photo-new]');
        if (list) {
            initOrdering(manager, list);
        }
        if (input && preview) {
            initPreviews(manager, input, preview);
        }
    });
}
