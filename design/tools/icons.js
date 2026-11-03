/*
 * Набор иконок VetMoment: сетка 24×24, линия 2px, скруглённые концы и стыки,
 * цвет — currentColor. Заполненные элементы (точки, подушечки лап) — fill="currentColor".
 * Имена совпадают с будущими Blade-иконками: <x-icon name="paw" />.
 */
(function () {
  const ICONS = {
    paw: ['Лапа', '<path d="M12 20.5c-3.3-2.2-5-4-5-6.1 0-1.5 1.1-2.6 2.5-2.6 1.1 0 2 .6 2.5 1.5.5-.9 1.4-1.5 2.5-1.5 1.4 0 2.5 1.1 2.5 2.6 0 2.1-1.7 3.9-5 6.1z"/><ellipse fill="currentColor" stroke="none" cx="5.4" cy="9.6" rx="1.6" ry="2.1" transform="rotate(-24 5.4 9.6)"/><ellipse fill="currentColor" stroke="none" cx="9.4" cy="5.6" rx="1.6" ry="2.2" transform="rotate(-8 9.4 5.6)"/><ellipse fill="currentColor" stroke="none" cx="14.6" cy="5.6" rx="1.6" ry="2.2" transform="rotate(8 14.6 5.6)"/><ellipse fill="currentColor" stroke="none" cx="18.6" cy="9.6" rx="1.6" ry="2.1" transform="rotate(24 18.6 9.6)"/>'],
    heart: ['Сердце, избранное', '<path d="M12 20s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7.3a4.3 4.3 0 0 1 7.5 2.5C19.5 15.4 12 20 12 20z"/>'],
    'heart-filled': ['В избранном', '<path fill="currentColor" d="M12 20s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7.3a4.3 4.3 0 0 1 7.5 2.5C19.5 15.4 12 20 12 20z"/>'],
    tag: ['Бирка (адресник)', '<circle cx="12" cy="15.5" r="6"/><circle cx="12" cy="5" r="2.5"/><circle cx="12" cy="12.2" r="1" fill="currentColor" stroke="none"/>'],
    home: ['Забрать домой, приют', '<path d="M4 11 12 4l8 7"/><path d="M6 9.5V20h12V9.5"/><path d="M10 20v-5h4v5"/>'],
    leash: ['Выгул', '<ellipse cx="6.2" cy="6.2" rx="2.9" ry="3.8" transform="rotate(-45 6.2 6.2)"/><path d="M8.6 8.6c3.2 3.2 1.2 6.6 5.6 8.6"/><circle cx="17.2" cy="18" r="2.6"/>'],
    tenge: ['Тенге, пожертвование', '<path d="M6.5 4.5h11"/><path d="M6.5 8.5h11"/><path d="M12 8.5V20"/>'],
    calendar: ['Календарь', '<rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M3.5 9.5h17"/><path d="M8 3v4"/><path d="M16 3v4"/>'],
    clock: ['Время', '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>'],
    'map-pin': ['Адрес', '<path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0c0 5.4-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.3"/>'],
    user: ['Профиль', '<circle cx="12" cy="8" r="3.8"/><path d="M4.5 20c.8-3.6 3.8-5.5 7.5-5.5s6.7 1.9 7.5 5.5"/>'],
    search: ['Поиск', '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/>'],
    filter: ['Фильтры', '<path d="M4 7h9"/><path d="M17 7h3"/><path d="M4 17h3"/><path d="M11 17h9"/><circle cx="15" cy="7" r="2"/><circle cx="9" cy="17" r="2"/>'],
    check: ['Готово', '<path d="m5 12.5 4.5 4.5L19 7.5"/>'],
    x: ['Закрыть', '<path d="M6 6l12 12"/><path d="M18 6 6 18"/>'],
    plus: ['Добавить', '<path d="M12 5v14"/><path d="M5 12h14"/>'],
    'alert-triangle': ['Внимание', '<path d="M10.3 4.9 3.2 17.5A2 2 0 0 0 4.9 20.5h14.2a2 2 0 0 0 1.7-3L13.7 4.9a2 2 0 0 0-3.4 0z"/><path d="M12 9.5v4.5"/><circle cx="12" cy="17.2" r=".6" fill="currentColor"/>'],
    'alert-circle': ['Ошибка', '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5v5.5"/><circle cx="12" cy="16.4" r=".6" fill="currentColor"/>'],
    info: ['Информация', '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5.5"/><circle cx="12" cy="7.8" r=".6" fill="currentColor"/>'],
    'check-circle': ['Успех', '<circle cx="12" cy="12" r="8.5"/><path d="m8.3 12.2 2.5 2.5 5-5"/>'],
    'chevron-left': ['Назад', '<path d="m14.5 6-6 6 6 6"/>'],
    'chevron-right': ['Дальше', '<path d="m9.5 6 6 6-6 6"/>'],
    'chevron-down': ['Раскрыть', '<path d="m6 9.5 6 6 6-6"/>'],
    menu: ['Меню', '<path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h16"/>'],
    bell: ['Уведомления', '<path d="M6 16.5V11a6 6 0 0 1 12 0v5.5l1.5 2h-15z"/><path d="M10 20.5a2 2 0 0 0 4 0"/>'],
    camera: ['Фото', '<rect x="3" y="7" width="18" height="13" rx="2.5"/><path d="m8.5 7 1.5-2.5h4L15.5 7"/><circle cx="12" cy="13.5" r="3.5"/>'],
    image: ['Изображение', '<rect x="3" y="4.5" width="18" height="15" rx="2.5"/><circle cx="8.5" cy="9.5" r="1.8"/><path d="m21 15.5-5-5-9 9"/>'],
    'shield-check': ['Проверенный приют', '<path d="M12 3.2 19 6v5.5c0 4.3-3 7.7-7 9.1-4-1.4-7-4.8-7-9.1V6z"/><path d="m8.8 12 2.2 2.2 4.2-4.4"/>'],
    syringe: ['Прививки', '<g transform="rotate(45 12 12)"><rect x="9" y="6" width="6" height="10.5" rx="1"/><path d="M12 6V2.5"/><path d="M9.5 2.5h5"/><path d="M12 16.5V22"/><path d="M9 9.5h2.5"/><path d="M9 12.5h2.5"/></g>'],
    cat: ['Кошка', '<path d="M5 10.5V4.5l4 3h6l4-3v6c0 5-3.1 9-7 9s-7-4-7-9z"/><circle cx="9.5" cy="12" r=".9" fill="currentColor"/><circle cx="14.5" cy="12" r=".9" fill="currentColor"/><path d="M11 15h2l-1 1z"/>'],
    dog: ['Собака', '<path d="M8 5.5h8a3 3 0 0 1 3 3V13c0 3.9-3.1 6.5-7 6.5S5 16.9 5 13V8.5a3 3 0 0 1 3-3z"/><path d="M8 5.5C5 5.5 3 7 3 10.5c0 1.3.9 2 2 2"/><path d="M16 5.5c3 0 5 1.5 5 5 0 1.3-.9 2-2 2"/><circle cx="9.5" cy="11" r=".9" fill="currentColor"/><circle cx="14.5" cy="11" r=".9" fill="currentColor"/><path d="M10.8 14.5h2.4L12 16z"/>'],
    edit: ['Редактировать', '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>'],
    trash: ['Удалить', '<path d="M4.5 7h15"/><path d="M9.5 7V4.5h5V7"/><path d="m6.5 7 1 13h9l1-13"/>'],
    upload: ['Загрузить', '<path d="M12 15V4"/><path d="M7.5 8.5 12 4l4.5 4.5"/><path d="M4.5 15v4.5h15V15"/>'],
    share: ['Поделиться', '<circle cx="18" cy="5.5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="18.5" r="2.5"/><path d="m8.2 10.8 7.6-4"/><path d="m8.2 13.2 7.6 4"/>'],
    phone: ['Телефон', '<path d="M6.5 3.5h3l1.5 4-2 1.2a10 10 0 0 0 5.3 5.3l1.2-2 4 1.5v3a2 2 0 0 1-2.2 2A15.5 15.5 0 0 1 4.5 5.7a2 2 0 0 1 2-2.2z"/>'],
    mail: ['Почта', '<rect x="3" y="5.5" width="18" height="13" rx="2"/><path d="m3.5 7 8.5 6 8.5-6"/>'],
    users: ['Волонтёры', '<circle cx="9" cy="8.5" r="3.3"/><path d="M3 19.5c.6-3.2 3-5 6-5s5.4 1.8 6 5"/><path d="M15.5 5.6a3.3 3.3 0 0 1 0 5.8"/><path d="M17.5 14.8c1.8.6 3.1 2.2 3.5 4.7"/>'],
    logout: ['Выйти', '<path d="M14 4.5h3.5a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H14"/><path d="M10 16.5 5.5 12 10 7.5"/><path d="M5.5 12h10"/>'],
  };

  function sprite() {
    return (
      '<defs id="icons">' +
      Object.entries(ICONS)
        .map(
          ([name, [label, body]]) =>
            `<symbol id="i-${name}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><title>${label}</title>${body}</symbol>`,
        )
        .join('') +
      '</defs>'
    );
  }

  window.ICONS = ICONS;
  window.iconSprite = sprite;
})();
