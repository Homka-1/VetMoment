/*
 * Макеты ключевых экранов VetMoment (этап 3).
 * Собираются из компонентов UI-кита (components.js) и логотипа (kit.js), чтобы макеты, токены
 * и будущая вёрстка не расходились. Каждый экран — отдельный файл design/screens/NN-slug.svg,
 * внутри — артборды (desktop 1440, mobile 390, состояния), разложенные слева направо.
 * window.buildScreens() → { files: { 'screens/…svg': svg }, artboards: [{ file, png, x, y, w, h, scale }] }
 */
(function () {
  const { c, text, mid, rect, circle, line, icon, g, measure, para, wrap } = window.K;
  const UI = window.UI;
  const { logoLockup, svgDoc } = window.KIT;

  const DW = 1440; // desktop
  const MW = 390; // mobile
  const X0 = 80; // поле контейнера desktop (контейнер 1280)
  const CW = 1280;
  const MX = 16; // поле mobile
  const MCW = MW - MX * 2;

  /* ================= Мелкие помощники ================= */
  const T = (x, y, s, o = {}) => text(x, y, s, o);
  const D = (x, y, s, size = 25, o = {}) => text(x, y, s, { family: 'display', size, weight: 600, ...o });
  const muted = (x, y, s, o = {}) => text(x, y, s, { size: 15, fill: c('ink.700'), ...o });
  const small = (x, y, s, o = {}) => text(x, y, s, { size: 13, fill: c('ink.500'), ...o });
  const B = (x, y, label, tone, o) => UI.badge(x, y, label, tone, o).svg;
  const BW = (label, o = {}) => 22 + (o.icon ? 19 : 0) + measure(label, o.size === 'sm' ? 12 : 13, 600) + 12;
  const btn = UI.button;
  const btnW = (label, o = {}) => (o.w ? o.w : UI.button.width(label, o));

  /** Абзац с возвратом высоты. */
  function P(x, y, str, width, o = {}) {
    const size = o.size ?? 16;
    const lh = o.lh ?? Math.round(size * 1.55);
    const lines = wrap(str, width, size, o.weight ?? 400, o.family ?? 'body');
    return { svg: lines.map((l, i) => text(x, y + i * lh, l, { fill: c('ink.700'), ...o, size })).join(''), h: lines.length * lh };
  }

  /** Заголовок в несколько строк (display). */
  function H(x, y, str, size, width, o = {}) {
    const lh = Math.round(size * 1.12);
    const lines = wrap(str, width, size, 600, 'display');
    return { svg: lines.map((l, i) => D(x, y + i * lh, l, size, o)).join(''), h: lines.length * lh };
  }

  /** Ссылка с подчёркиванием и шевроном. */
  function link(x, y, label, o = {}) {
    const size = o.size ?? 15;
    const w = measure(label, size, 600);
    const ax = o.anchor === 'end' ? x - w - (o.chevron === false ? 0 : 22) : x;
    let s = T(ax, y, label, { size, weight: 600, decoration: 'underline', fill: o.fill ?? c('ink.900') });
    if (o.chevron !== false) s += icon('chevron-right', ax + w + 4, y - size + 1, 18, o.fill ?? c('ink.900'));
    return s;
  }

  function sectionHead(x, y, w, title, linkLabel, o = {}) {
    let s = D(x, y, title, o.size ?? 39);
    if (linkLabel) s += link(x + w, y - 4, linkLabel, { anchor: 'end' });
    return s;
  }

  function crumbs(x, y, items) {
    let s = '';
    let bx = x;
    items.forEach((b, i) => {
      const last = i === items.length - 1;
      s += T(bx, y, b, { size: 14, weight: last ? 600 : 400, fill: last ? c('ink.900') : c('ink.700'), decoration: last ? undefined : 'underline' });
      bx += measure(b, 14, last ? 600 : 400) + 6;
      if (!last) {
        s += icon('chevron-right', bx, y - 13, 16, c('ink.400'));
        bx += 22;
      }
    });
    return s;
  }

  function avatar(cx, cy, r, ini, tone = 'sky') {
    return circle(cx, cy, r, { fill: c(`${tone}.200`) }) + T(cx, cy + r * 0.27, ini, { size: Math.round(r * 0.62), weight: 700, fill: c(`${tone}.700`), anchor: 'middle' });
  }

  /** Плашка-иконка в круге. */
  function iconDisc(cx, cy, r, name, bg, fg) {
    const is = Math.round(r * 1.05);
    return circle(cx, cy, r, { fill: bg }) + icon(name, cx - is / 2, cy - is / 2, is, fg);
  }

  function divider(x, y, w) {
    return line(x, y, x + w, y, { stroke: c('ink.200') });
  }

  /** Сегментированный переключатель / вкладки-«таблетки». */
  function segmented(x, y, items, active, o = {}) {
    const h = o.h ?? 44;
    const pad = 18;
    const widths = items.map((t) => measure(t, 15, 600) + pad * 2);
    const total = widths.reduce((a, b) => a + b, 0) + 8;
    let s = rect(x, y, o.w ?? total, h, { r: 12, fill: c('ink.100') });
    let cx = x + 4;
    items.forEach((t, i) => {
      const w = o.w ? (o.w - 8) / items.length : widths[i];
      if (i === active) s += rect(cx, y + 4, w, h - 8, { r: 9, fill: c('white'), stroke: c('ink.200') });
      s += T(cx + w / 2, mid(y, h, 15), t, { size: 15, weight: 600, fill: i === active ? c('ink.900') : c('ink.700'), anchor: 'middle' });
      cx += w;
    });
    return s;
  }

  /** Вкладки с подчёркиванием. */
  function tabs(x, y, items, active, w) {
    let s = '';
    let tx = x;
    items.forEach((t, i) => {
      const tw = measure(t, 16, 600);
      s += T(tx, y, t, { size: 16, weight: 600, fill: i === active ? c('ink.900') : c('ink.500') });
      if (i === active) s += rect(tx, y + 14, tw, 3, { r: 1.5, fill: c('ink.900') });
      tx += tw + 32;
    });
    s += line(x, y + 17, x + (w ?? tx - x), y + 17, { stroke: c('ink.200') });
    return s;
  }

  /** Строка «иконка + текст». */
  function iconLine(x, y, ic, str, o = {}) {
    const fg = o.fill ?? c('ink.700');
    return icon(ic, x, y - 15, o.is ?? 20, o.icFill ?? fg) + T(x + (o.is ?? 20) + 8, y, str, { size: o.size ?? 15, fill: fg, weight: o.weight });
  }

  function checkRow(x, y, ok, label) {
    const col = ok ? c('success.fg') : c('ink.500');
    return iconDisc(x + 12, y - 5, 12, ok ? 'check' : 'x', ok ? c('success.bg') : c('ink.100'), col) + T(x + 34, y, label, { size: 15, fill: ok ? c('ink.900') : c('ink.700') });
  }

  /** Информационная плашка (баннер). */
  function banner(x, y, w, tone, ic, title, body, o = {}) {
    const t = UI.TONE[tone];
    const lines = body ? wrap(body, w - 76 - (o.action ? 220 : 0), 14) : [];
    const h = o.h ?? Math.max(64, 40 + (title ? 22 : 0) + lines.length * 21);
    let s = rect(x, y, w, h, { r: 16, fill: t.bg, stroke: t.border });
    s += icon(ic, x + 20, y + 20, 24, t.fg);
    let ty = y + 37;
    if (title) {
      s += T(x + 56, ty, title, { size: 16, weight: 600, fill: c('ink.900') });
      ty += 22;
    }
    lines.forEach((l, i) => (s += T(x + 56, ty + i * 21 - (title ? 0 : 0), l, { size: 14, fill: c('ink.700') })));
    if (o.action) s += btn(x + w - 20 - btnW(o.action, { size: 'sm' }), y + (h - 36) / 2, o.action, { variant: 'secondary', size: 'sm' });
    return { svg: s, h };
  }

  /* ================= Данные-примеры ================= */
  const PETS = [
    { slug: 'bublik', name: 'Бублик', kind: 'dog', fur: 'caramel', meta: 'Собака, 2 года, мальчик', short: '2 года, мальчик', traits: ['спокойный', 'ладит с детьми'], shelter: 'Дом лап, Алматы', walk: true, status: ['Ищет дом', 'info'], bg: c('sky.100'), sun: true },
    { slug: 'myata', name: 'Мята', kind: 'cat', fur: 'smoke', meta: 'Кошка, 1 год, девочка', short: '1 год, девочка', traits: ['ласковая', 'привита'], shelter: 'Мурлыка, Астана', walk: false, status: ['На лечении', 'warning'], bg: c('tag.100'), fav: true },
    { slug: 'graf', name: 'Граф', kind: 'dog', fur: 'graphite', meta: 'Собака, 6 лет, мальчик', short: '6 лет, мальчик', traits: ['активный', 'любит бегать'], shelter: 'Дом лап, Алматы', walk: true, status: ['Забронирован', 'neutral'], bg: c('meadow.100') },
    { slug: 'ryzhik', name: 'Рыжик', kind: 'cat', fur: 'ginger', meta: 'Кот, 3 года, мальчик', short: '3 года, мальчик', traits: ['игривый', 'приучен к лотку'], shelter: 'Мурлыка, Астана', walk: false, status: ['Ищет дом', 'info'], bg: c('sky.100') },
    { slug: 'romashka', name: 'Ромашка', kind: 'dog', fur: 'caramel', meta: 'Собака, 4 года, девочка', short: '4 года, девочка', traits: ['добрая', 'послушная'], shelter: 'Дом лап, Алматы', walk: true, status: ['На лечении', 'warning'], bg: c('tag.100') },
    { slug: 'tuchka', name: 'Тучка', kind: 'cat', fur: 'smoke', meta: 'Кошка, 5 лет, девочка', short: '5 лет, девочка', traits: ['спокойная', 'стерилизована'], shelter: 'Тёплый угол, Шымкент', walk: false, status: ['Ищет дом', 'info'], bg: c('meadow.100') },
    { slug: 'lord', name: 'Лорд', kind: 'dog', fur: 'graphite', meta: 'Собака, 8 лет, мальчик', short: '8 лет, мальчик', traits: ['мудрый', 'ладит с кошками'], shelter: 'Тёплый угол, Шымкент', walk: true, status: ['Ищет дом', 'info'], bg: c('sky.100') },
    { slug: 'persik', name: 'Персик', kind: 'cat', fur: 'ginger', meta: 'Кот, 7 месяцев, мальчик', short: '7 месяцев, мальчик', traits: ['любопытный', 'привит'], shelter: 'Мурлыка, Астана', walk: false, status: ['Ищет дом', 'info'], bg: c('tag.100') },
    { slug: 'baron', name: 'Барон', kind: 'dog', fur: 'caramel', meta: 'Собака, 3 года, мальчик', short: '3 года, мальчик', traits: ['дружелюбный', 'знает команды'], shelter: 'Хвостики, Караганда', walk: true, status: ['Ищет дом', 'info'], bg: c('meadow.100') },
  ];
  const CAMPAIGNS = [
    { title: 'Операция на лапе для Ромашки', shelter: 'Дом лап, Алматы', raised: '412 500 ₸', goal: '600 000 ₸', ratio: 0.69, people: '84 человека', left: 'Ещё 12 дней', status: ['Идёт сбор', 'tag'], kind: 'dog', fur: 'caramel', bg: c('tag.100') },
    { title: 'Утеплить вольеры до морозов', shelter: 'Тёплый угол, Шымкент', raised: '318 000 ₸', goal: '900 000 ₸', ratio: 0.35, people: '57 человек', left: 'Ещё 25 дней', status: ['Идёт сбор', 'tag'], kind: 'dog', fur: 'graphite', bg: c('sky.100') },
    { title: 'Стерилизация 15 кошек', shelter: 'Хвостики, Караганда', raised: '96 000 ₸', goal: '300 000 ₸', ratio: 0.32, people: '22 человека', left: 'Ещё 40 дней', status: ['Идёт сбор', 'tag'], kind: 'cat', fur: 'smoke', bg: c('meadow.100') },
    { title: 'Корм на зиму для 40 кошек', shelter: 'Мурлыка, Астана', raised: '250 000 ₸', goal: '250 000 ₸', ratio: 1, people: '131 человек', left: 'Собрано', status: ['Цель достигнута', 'success', { icon: 'check' }], kind: 'cat', fur: 'ginger', bg: c('sky.100') },
  ];
  const SHELTERS = [
    { name: 'Дом лап', city: 'Алматы, Бостандыкский район', pets: '38 питомцев', camps: '2 сбора', walk: 'Выгул: сб, вс', tone: 'meadow' },
    { name: 'Мурлыка', city: 'Астана, район Есиль', pets: '52 питомца', camps: '1 сбор', walk: 'Только кошки', tone: 'sky' },
    { name: 'Тёплый угол', city: 'Шымкент, Абайский район', pets: '24 питомца', camps: '1 сбор', walk: 'Выгул: ежедневно', tone: 'tag' },
    { name: 'Хвостики', city: 'Караганда, Юго-Восток', pets: '31 питомец', camps: '1 сбор', walk: 'Выгул: пт, сб', tone: 'meadow' },
  ];

  /* ================= Каркас страницы ================= */
  const NAV = ['Питомцы', 'Сборы', 'Выгул', 'Приюты', 'Как помочь'];

  function header(w, o = {}) {
    let s = rect(0, 0, w, 76, { fill: c('white') });
    s += line(0, 76, w, 76, { stroke: c('ink.200') });
    s += logoLockup('light', X0, 18, 40 / 64);
    let nx = X0 + 280;
    NAV.forEach((n, i) => {
      const active = i === o.active;
      s += T(nx, 44, n, { size: 16, weight: active ? 600 : 500, fill: active ? c('ink.900') : c('ink.700') });
      if (active) s += rect(nx, 72, measure(n, 16, 600), 4, { r: 2, fill: c('tag.500') });
      nx += measure(n, 16, 600) + 32;
    });
    const rx = w - X0;
    if (o.user) {
      const u = o.user;
      const nameW = measure(u.name, 15, 500);
      s += T(rx - 20, 43, u.name, { size: 15, weight: 500, anchor: 'end' });
      s += icon('chevron-down', rx - 18, 30, 18, c('ink.700'));
      s += avatar(rx - 20 - nameW - 30, 38, 20, u.ini, u.tone ?? 'sky');
      const bx = rx - 20 - nameW - 30 - 60;
      s += circle(bx, 38, 22, { fill: c('ink.50') });
      s += icon('bell', bx - 12, 26, 24, c('ink.900'));
      if (u.bell) {
        s += circle(bx + 12, 26, 9, { fill: c('danger.solid') });
        s += T(bx + 12, 30.5, String(u.bell), { size: 11, weight: 700, fill: c('white'), anchor: 'middle' });
      }
    } else {
      s += btn(rx - 136, 16, 'Помочь', { variant: 'donate', icon: 'heart', w: 136 });
      s += btn(rx - 136 - 12 - 82, 16, 'Войти', { variant: 'ghost' });
    }
    return s;
  }

  function mheader(o = {}) {
    let s = rect(0, 0, MW, 64, { fill: c('white') });
    s += line(0, 64, MW, 64, { stroke: c('ink.200') });
    s += logoLockup('light', MX, 16, 32 / 64);
    s += btn(MW - MX - 44, 10, '', { variant: 'ghost', icon: 'menu', iconOnly: true });
    if (o.user) {
      s += btn(MW - MX - 44 - 48, 10, '', { variant: 'ghost', icon: 'bell', iconOnly: true });
      if (o.user.bell) {
        s += circle(MW - MX - 44 - 48 + 31, 20, 8, { fill: c('danger.solid') });
        s += T(MW - MX - 44 - 48 + 31, 24, String(o.user.bell), { size: 10, weight: 700, fill: c('white'), anchor: 'middle' });
      }
    } else {
      s += btn(MW - MX - 44 - 8 - 92, 14, 'Помочь', { variant: 'donate', size: 'sm', w: 92 });
    }
    return s;
  }

  function footer(w, y) {
    const h = 300;
    let s = rect(0, y, w, h, { fill: c('ink.900') });
    s += logoLockup('inverse', X0, y + 56, 40 / 64);
    s += P(X0, y + 136, 'Помогаем приютам Казахстана найти хозяев, деньги на лечение и волонтёров для прогулок.', 340, { size: 15, fill: c('ink.300'), lh: 23 }).svg;
    const cols = [
      ['Помочь', ['Питомцы', 'Сборы', 'Запись на выгул', 'Как помочь']],
      ['Приютам', ['Стать приютом-партнёром', 'Кабинет приюта', 'Правила площадки']],
      ['Связаться', ['hello@vetmoment.kz', '+7 727 300 12 40', 'Алматы, ул. Сатпаева, 30']],
    ];
    cols.forEach(([t, items], i) => {
      const cx = X0 + 520 + i * 260;
      s += T(cx, y + 76, t, { size: 15, weight: 600, fill: c('white') });
      items.forEach((it, j) => (s += T(cx, y + 112 + j * 32, it, { size: 15, fill: c('ink.300') })));
    });
    s += line(X0, y + h - 64, w - X0, y + h - 64, { stroke: c('ink.700') });
    s += T(X0, y + h - 28, '© 2026 VetMoment', { size: 14, fill: c('ink.400') });
    s += T(w - X0, y + h - 28, 'Политика обработки персональных данных', { size: 14, fill: c('ink.300'), anchor: 'end', decoration: 'underline' });
    return { svg: s, h };
  }

  function mfooter(y) {
    const h = 300;
    let s = rect(0, y, MW, h, { fill: c('ink.900') });
    s += logoLockup('inverse', MX, y + 32, 36 / 64);
    s += P(MX, y + 104, 'Помогаем приютам Казахстана найти хозяев, деньги на лечение и волонтёров для прогулок.', MCW, { size: 14, fill: c('ink.300'), lh: 21 }).svg;
    ['Питомцы', 'Сборы', 'Выгул', 'Приюты'].forEach((n, i) => (s += T(MX + (i % 2) * 180, y + 176 + Math.floor(i / 2) * 30, n, { size: 15, fill: c('white') })));
    s += T(MX, y + 250, 'hello@vetmoment.kz', { size: 14, fill: c('ink.300') });
    s += T(MX, y + 276, '© 2026 VetMoment', { size: 13, fill: c('ink.400') });
    return { svg: s, h };
  }

  /** Липкая нижняя панель действий (mobile). */
  function stickyBar(y, inner) {
    return rect(0, y, MW, 76, { fill: c('white') }) + line(0, y, MW, y, { stroke: c('ink.200') }) + inner;
  }

  /* ================= Составные блоки ================= */
  /** Компактная карточка питомца для сетки 2×N на телефоне. */
  function petMini(x, y, w, d) {
    const ph = Math.round(w * 0.92);
    let s = rect(x, y, w, ph + 104, { r: 16, fill: c('white'), stroke: c('ink.200') });
    s += UI.petPhoto(x + 6, y + 6, w - 12, ph - 6, { kind: d.kind, fur: d.fur, bg: d.bg, r: 12 });
    const bw = BW(d.status[0], { size: 'sm' });
    s += rect(x + 12, y + 12, bw + 4, 28, { r: 14, fill: c('white') });
    s += B(x + 14, y + 14, d.status[0], d.status[1], { size: 'sm' });
    s += circle(x + w - 26, y + ph - 20, 16, { fill: c('white') });
    s += icon(d.fav ? 'heart-filled' : 'heart', x + w - 35, y + ph - 29, 18, d.fav ? c('danger.solid') : c('ink.900'));
    s += D(x + 12, y + ph + 30, d.name, 18);
    s += T(x + 12, y + ph + 52, d.short, { size: 13, fill: c('ink.700') });
    if (d.walk) s += iconLine(x + 12, y + ph + 82, 'leash', 'Можно погулять', { size: 13, is: 16, fill: c('meadow.700'), weight: 600 });
    else s += iconLine(x + 12, y + ph + 82, 'home', d.shelter.split(',')[0], { size: 13, is: 16, fill: c('ink.700') });
    return { svg: s, h: ph + 104 };
  }

  function shelterCard(x, y, w, d, o = {}) {
    const h = o.h ?? 200;
    let s = rect(x, y, w, h, { r: 24, fill: c('white'), stroke: c('ink.200') });
    s += iconDisc(x + 52, y + 52, 28, 'home', c(`${d.tone}.100`), c(`${d.tone}.700`));
    s += D(x + 96, y + 46, d.name, 20);
    s += T(x + 96, y + 70, d.city, { size: 14, fill: c('ink.700') });
    s += B(x + 24, y + 100, 'Проверенный приют', 'success', { icon: 'shield-check' });
    s += iconLine(x + 24, y + 166, 'paw', d.pets, { size: 14, is: 18 });
    s += iconLine(x + 24 + 26 + measure(d.pets, 14) + 20, y + 166, 'leash', d.walk, { size: 14, is: 18 });
    return s;
  }

  function helpPaths(x, y, w, o = {}) {
    const items = [
      ['home', 'sky', 'Забрать домой', 'Выберите питомца и оставьте заявку. Приют перезвонит и расскажет, как познакомиться.', 'Смотреть питомцев'],
      ['tenge', 'tag', 'Пожертвовать', 'Любая сумма от 200 ₸, без регистрации. По каждому сбору приют публикует отчёт.', 'Выбрать сбор'],
      ['leash', 'meadow', 'Погулять с собакой', 'Запишитесь на свободное время в приюте рядом. Прогулка занимает около часа.', 'Найти время'],
    ];
    let s = '';
    if (o.mobile) {
      let yy = y;
      items.forEach(([ic, tone, title, body, cta]) => {
        const p = P(x + 24, yy + 112, body, w - 48, { size: 15, lh: 23 });
        const h = 112 + p.h + 44;
        s += rect(x, yy, w, h, { r: 24, fill: c(`${tone}.100`) });
        s += iconDisc(x + 48, yy + 48, 24, ic, c('white'), c(`${tone}.700`));
        s += D(x + 88, yy + 56, title, 20);
        s += p.svg + link(x + 24, yy + h - 24, cta);
        yy += h + 12;
      });
      return { svg: s, h: yy - y - 12 };
    }
    const cw = (w - 64) / 3;
    items.forEach(([ic, tone, title, body, cta], i) => {
      const cx = x + i * (cw + 32);
      s += rect(cx, y, cw, 284, { r: 24, fill: c(`${tone}.100`) });
      s += iconDisc(cx + 56, y + 56, 28, ic, c('white'), c(`${tone}.700`));
      s += D(cx + 32, y + 128, title, 22);
      s += P(cx + 32, y + 160, body, cw - 64, { size: 15, lh: 23 }).svg;
      s += link(cx + 32, y + 250, cta);
    });
    return { svg: s, h: 284 };
  }

  /** Слот с подписью дня (для витрин «ближайшие выгулы»). */
  function daySlot(x, y, day, place, st, time, note) {
    return T(x, y, day, { size: 14, weight: 600 }) + small(x, y + 20, place) + UI.slot(x, y + 34, st, time, note);
  }

  /* ================= Обёртка файла с артбордами ================= */
  const OUT = { files: {}, artboards: [] };

  /**
   * boards: [{ key, name, w, build: () => ({ svg, h }), scale }]
   * Артборды раскладываются в ряд; над каждым — подпись.
   */
  function screenFile(slug, title, boards) {
    const GAP = 120;
    const TOP = 90;
    let x = 60;
    let inner = '';
    let maxH = 0;
    for (const b of boards) {
      const r = b.build();
      const id = `artboard-${slug}-${b.key}`;
      inner += T(x, TOP - 28, b.name, { size: 20, weight: 600, fill: c('ink.700') });
      inner += `<clipPath id="clip-${id}"><rect x="0" y="0" width="${b.w}" height="${r.h}"/></clipPath>`;
      inner += `<g id="${id}" data-name="${b.name}" transform="translate(${x} ${TOP})"><g clip-path="url(#clip-${id})">${rect(0, 0, b.w, r.h, { fill: b.bg ?? c('white') })}${r.svg}</g></g>`;
      OUT.artboards.push({ file: `screens/${slug}.svg`, png: `screens/png/${slug}-${b.key}.png`, x, y: TOP, w: b.w, h: r.h, scale: b.w <= 600 ? 2 : 1 });
      maxH = Math.max(maxH, r.h);
      x += b.w + GAP;
    }
    const W = x - GAP + 60;
    const Hh = TOP + maxH + 60;
    const bg = rect(0, 0, W, Hh, { fill: '#E4E8EF', id: 'canvas' });
    OUT.files[`screens/${slug}.svg`] = svgDoc(W, Hh, bg + inner, { title: `VetMoment — ${title}`, rel: '../' });
  }

  /* =====================================================================
   * 01. Главная
   * ===================================================================== */
  /** Герой: портрет питомца с крупной адресной биркой — фирменный мотив. */
  function heroArt(cx, cy, R, o = {}) {
    const clip = `clip-hero-${o.id ?? 'd'}`;
    let s = circle(cx, cy, R, { fill: c('sky.100') });
    s += circle(cx + R * 0.62, cy - R * 0.62, R * 0.2, { fill: c('tag.200') });
    // портрет: масштаб так, чтобы морда заполнила круг; низ обрезан кругом
    const sc = (R * 2) / 200 * 0.92;
    const px = cx - 100 * sc;
    const py = cy - R * 0.98;
    s += `<clipPath id="${clip}"><circle cx="${cx}" cy="${cy}" r="${R}"/></clipPath>`;
    s += `<g clip-path="url(#${clip})"><g transform="translate(${px.toFixed(1)} ${py.toFixed(1)}) scale(${sc.toFixed(3)})">${UI.dogPortrait('caramel')}</g></g>`;
    // адресная бирка, подвешенная к ошейнику
    const ax = cx; // точка крепления на ошейнике (portrait 100,176)
    const ay = py + 176 * sc;
    const tr = o.tagR ?? R * 0.42;
    const tx = cx + R * 0.52;
    const ty = cy + R * 0.78;
    s += `<path d="M${ax} ${ay} Q ${(ax + tx) / 2 + 10} ${ay + 10} ${tx} ${ty - tr - 10}" stroke="${c('ink.900')}" stroke-width="${Math.max(2.5, tr * 0.045)}" fill="none" stroke-linecap="round"/>`;
    s += `<g transform="rotate(-8 ${tx} ${ty})">`;
    s += circle(tx, ty - tr - 10, tr * 0.16, { stroke: c('ink.900'), sw: Math.max(2.5, tr * 0.05) });
    s += circle(tx, ty, tr, { fill: c('tag.500') });
    s += circle(tx, ty - tr + tr * 0.17, tr * 0.07, { fill: c('white'), stroke: c('ink.900'), sw: 2 });
    const f1 = Math.round(tr * 0.3);
    s += D(tx, ty + f1 * 0.05, 'Бублик', f1, { anchor: 'middle' });
    s += T(tx, ty + f1 * 0.95, '2 года, ищет дом', { size: Math.round(tr * 0.13), weight: 600, anchor: 'middle' });
    s += T(tx, ty + f1 * 1.6, 'Дом лап, Алматы', { size: Math.round(tr * 0.12), anchor: 'middle', fill: c('ink.800') });
    s += '</g>';
    return s;
  }

  function homeDesktop() {
    let s = header(DW, {});
    let y = 76;
    // Герой
    s += D(X0, y + 168, 'У каждого здесь', 61) + D(X0, y + 236, 'есть имя', 61);
    s += P(X0, y + 296, 'Анкеты питомцев из приютов Казахстана. Заберите друга домой, помогите сбору на лечение или приходите погулять с собакой в свободный день.', 540, { size: 18, lh: 28 }).svg;
    s += btn(X0, y + 384, 'Найти питомца', { size: 'lg', icon: 'search' });
    s += btn(X0 + btnW('Найти питомца', { size: 'lg', icon: 'search' }) + 12, y + 384, 'Помочь деньгами', { size: 'lg', variant: 'donate', icon: 'heart' });
    s += iconLine(X0, y + 484, 'shield-check', '12 проверенных приютов в 6 городах', { fill: c('ink.700'), icFill: c('success.fg') });
    s += heroArt(1040, y + 280, 236);
    y += 600;
    // Как помочь
    s += sectionHead(X0, y + 40, CW, 'Как помочь');
    const hp = helpPaths(X0, y + 80, CW);
    s += hp.svg;
    y += 80 + hp.h + 100;
    // Питомцы недели
    s += sectionHead(X0, y, CW, 'Ждут знакомства', 'Все питомцы');
    s += muted(X0, y + 36, 'Дольше всех ищут дом. Каждую неделю подборка обновляется.');
    PETS.slice(0, 4).forEach((p, i) => (s += UI.petCard(X0 + i * 328, y + 76, p)));
    y += 76 + 438 + 100;
    // Сборы — на светлом фоне секции
    s += rect(0, y - 64, DW, 680, { fill: c('ink.50') });
    s += sectionHead(X0, y, CW, 'Сборы сейчас', 'Все сборы');
    s += muted(X0, y + 36, 'Деньги идут прямо приюту. После сбора приют публикует отчёт с чеками.');
    CAMPAIGNS.slice(0, 3).forEach((d, i) => (s += UI.campaignCard(X0 + i * 437, y + 76, { ...d, w: 405 })));
    y += 76 + 452 + 140;
    // Ближайшие выгулы
    s += sectionHead(X0, y, CW, 'Можно погулять на этой неделе', 'Выбрать время');
    const days = [
      ['Сегодня, ср', 'Дом лап', 'open', '17:00–18:00', 'Осталось 2 места'],
      ['Чт, 6 ноября', 'Тёплый угол', 'few', '10:00–11:00', 'Осталось 1 место'],
      ['Пт, 7 ноября', 'Хвостики', 'open', '16:00–17:00', 'Осталось 4 места'],
      ['Сб, 8 ноября', 'Дом лап', 'open', '10:00–11:00', 'Осталось 3 места'],
      ['Сб, 8 ноября', 'Дом лап', 'full', '12:00–13:00', 'Мест нет'],
      ['Вс, 9 ноября', 'Тёплый угол', 'open', '11:00–12:00', 'Осталось 5 мест'],
      ['Вс, 9 ноября', 'Хвостики', 'few', '14:00–15:00', 'Осталось 1 место'],
    ];
    days.forEach((d, i) => (s += daySlot(X0 + i * 185.3, y + 60, ...d)));
    y += 60 + 110 + 100;
    // Приюты
    s += sectionHead(X0, y, CW, 'Приюты-партнёры', 'Все приюты');
    SHELTERS.forEach((d, i) => (s += shelterCard(X0 + i * 328, y + 44, 296, d)));
    y += 44 + 200 + 100;
    // Итоги года — спокойная строка, не «герой с цифрами»
    s += rect(X0, y, CW, 168, { r: 24, fill: c('tag.100') });
    s += T(X0 + 40, y + 52, 'Что получилось вместе в 2026 году', { size: 18, weight: 600 });
    [['312', 'питомцев нашли дом'], ['18,4 млн ₸', 'собрано на лечение и корм'], ['2 140', 'прогулок с волонтёрами']].forEach(([n, l], i) => {
      const nx = X0 + 40 + i * 410;
      s += D(nx, y + 108, n, 39);
      s += muted(nx, y + 138, l);
    });
    y += 168 + 100;
    const f = footer(DW, y);
    return { svg: s + f.svg, h: y + f.h };
  }

  function homeMobile() {
    let s = mheader();
    let y = 64;
    s += D(MX, y + 64, 'У каждого', 31) + D(MX, y + 100, 'здесь есть имя', 31);
    const p = P(MX, y + 140, 'Анкеты питомцев из приютов Казахстана. Заберите друга домой, помогите сбору или приходите погулять.', MCW, { size: 16, lh: 25 });
    s += p.svg;
    y += 140 + p.h + 8;
    s += btn(MX, y, 'Найти питомца', { size: 'lg', icon: 'search', w: MCW });
    s += btn(MX, y + 64, 'Помочь деньгами', { size: 'lg', variant: 'donate', icon: 'heart', w: MCW });
    y += 64 + 52 + 24;
    s += heroArt(MW / 2 - 30, y + 150, 140, { id: 'm', tagR: 74 });
    y += 340;
    s += D(MX, y, 'Как помочь', 25);
    const hp = helpPaths(MX, y + 24, MCW, { mobile: true });
    s += hp.svg;
    y += 24 + hp.h + 64;
    s += D(MX, y, 'Ждут знакомства', 25);
    s += UI.petCard(MX, y + 24, PETS[0]) + UI.petCard(MX + 296 + 12, y + 24, PETS[1]);
    s += link(MX, y + 24 + 438 + 40, 'Все питомцы');
    y += 24 + 438 + 104;
    s += rect(0, y - 40, MW, 640, { fill: c('ink.50') });
    s += D(MX, y, 'Сборы сейчас', 25);
    s += UI.campaignCard(MX, y + 24, { ...CAMPAIGNS[0], w: MCW });
    s += link(MX, y + 24 + 452 + 40, 'Все сборы');
    y += 24 + 452 + 124;
    s += D(MX, y, 'Погулять на неделе', 25);
    s += daySlot(MX, y + 40, 'Сегодня, ср', 'Дом лап', 'open', '17:00–18:00', 'Осталось 2 места');
    s += daySlot(MX + 186, y + 40, 'Чт, 6 ноября', 'Тёплый угол', 'few', '10:00–11:00', 'Осталось 1 место');
    s += link(MX, y + 40 + 110 + 40, 'Выбрать время');
    y += 40 + 110 + 104;
    s += rect(MX, y, MCW, 264, { r: 24, fill: c('tag.100') });
    s += T(MX + 24, y + 44, 'Вместе в 2026 году', { size: 16, weight: 600 });
    [['312', 'питомцев нашли дом'], ['18,4 млн ₸', 'собрано на лечение и корм'], ['2 140', 'прогулок с волонтёрами']].forEach(([n, l], i) => {
      s += D(MX + 24, y + 96 + i * 62, n, 25);
      s += T(MX + 24, y + 120 + i * 62, l, { size: 14, fill: c('ink.700') });
    });
    y += 264 + 64;
    const f = mfooter(y);
    return { svg: s + f.svg, h: y + f.h };
  }

  /* =====================================================================
   * 02. Каталог питомцев
   * ===================================================================== */
  function filterGroup(x, y, title, chips) {
    let s = T(x, y, title, { size: 15, weight: 600 });
    let cx = x;
    let cy = y + 16;
    chips.forEach(([l, on]) => {
      const w = 32 + (on ? 22 : 0) + measure(l, 14, 500);
      if (cx + w > x + 296) {
        cx = x;
        cy += 44;
      }
      s += UI.chip(cx, cy, l, { selected: on }).svg;
      cx += w + 8;
    });
    return { svg: s, h: cy - y + 36 };
  }

  function catalogFilters(x, y) {
    let s = '';
    let yy = y;
    const groups = [
      ['Вид', [['Собаки', true], ['Кошки', false], ['Другие', false]]],
      ['Пол', [['Мальчик', false], ['Девочка', false]]],
      ['Возраст', [['До года', false], ['1–3 года', true], ['3–7 лет', false], ['Старше 7', false]]],
      ['Размер', [['Маленький', false], ['Средний', false], ['Крупный', false]]],
    ];
    for (const [t, ch] of groups) {
      const r = filterGroup(x, yy, t, ch);
      s += r.svg;
      yy += r.h + 40;
    }
    s += UI.field(x, yy - 10, 296, { label: 'Город', value: 'Алматы', select: true });
    yy += 92;
    s += UI.field(x, yy - 10, 296, { label: 'Приют', placeholder: 'Все приюты', select: true });
    yy += 100;
    s += UI.toggle(x, yy - 10, 'Готов к выгулу', true);
    s += UI.toggle(x, yy + 30, 'Можно под опеку', false);
    yy += 84;
    s += link(x, yy, 'Сбросить фильтры', { chevron: false, size: 15 });
    return { svg: s, h: yy - y };
  }

  function catalogDesktop(o = {}) {
    let s = header(DW, { active: 0 });
    let y = 76;
    s += crumbs(X0, y + 44, ['Главная', 'Питомцы']);
    s += D(X0, y + 112, 'Питомцы', 49);
    s += muted(X0 + measure('Питомцы', 49, 600, 'display') + 16, y + 110, o.empty ? 'не найдено' : '126 ищут дом');
    // поиск и сортировка
    s += UI.field(X0 + 328, y + 150, 600, { icon: 'search', placeholder: 'Кличка питомца', value: o.empty ? 'Шарик' : undefined });
    s += T(X0 + CW - 242, y + 180, 'Сначала:', { size: 15, fill: c('ink.700'), anchor: 'end' });
    s += UI.field(X0 + CW - 230, y + 150, 230, { value: 'Дольше всех ждут', select: true });
    // фильтры
    const fl = catalogFilters(X0, y + 166);
    s += fl.svg;
    // активные фильтры
    let ax = X0 + 328;
    const active = ['Собаки', '1–3 года', 'Алматы', 'Готов к выгулу'];
    active.forEach((a) => {
      const w = measure(a, 14, 500) + 48;
      s += rect(ax, y + 222, w, 32, { r: 16, fill: c('ink.100') });
      s += T(ax + 14, mid(y + 222, 32, 14), a, { size: 14, weight: 500 });
      s += icon('x', ax + w - 26, y + 230, 16, c('ink.700'));
      ax += w + 8;
    });
    const gy = y + 278;
    let endY;
    if (o.empty) {
      s += rect(X0 + 328, gy, 952, 420, { r: 24, fill: c('ink.50') });
      s += `<g transform="translate(${X0 + 328 + 476 - 72} ${gy + 48}) scale(0.72)"><circle cx="100" cy="100" r="92" fill="${c('sky.100')}"/><g transform="translate(0 6)">${UI.dogPortrait('graphite')}</g></g>`;
      s += D(X0 + 804, gy + 252, 'По этим фильтрам никого нет', 20, { anchor: 'middle' });
      s += muted(X0 + 804, gy + 284, 'Шарика нет среди собак 1–3 лет в Алматы. Уберите часть фильтров', { anchor: 'middle' });
      s += muted(X0 + 804, gy + 307, 'или посмотрите питомцев в других городах.', { anchor: 'middle' });
      s += btn(X0 + 804 - 186, gy + 336, 'Сбросить фильтры', { variant: 'secondary', w: 180 });
      s += btn(X0 + 804 + 6, gy + 336, 'Все города', { w: 180 });
      endY = gy + 420;
    } else if (o.loading) {
      for (let i = 0; i < 6; i++) s += UI.skeletonCard(X0 + 328 + (i % 3) * 328, gy + Math.floor(i / 3) * 470);
      endY = gy + 470 * 2 - 32;
    } else {
      const list = [PETS[0], PETS[2], PETS[4], PETS[6], PETS[8], { ...PETS[0], name: 'Тоша', fur: 'graphite', bg: c('tag.100'), traits: ['весёлый', 'привит'], sun: false }];
      list.forEach((p, i) => (s += UI.petCard(X0 + 328 + (i % 3) * 328, gy + Math.floor(i / 3) * 470, p)));
      endY = gy + 470 * 2 - 32;
      // пагинация
      const py = endY + 48;
      let px = X0 + 328;
      s += small(px, py + 28, 'Показано 1–12 из 37', { size: 14, fill: c('ink.700') });
      px = X0 + CW - 298;
      s += btn(px, py, '', { variant: 'secondary', icon: 'chevron-left', iconOnly: true, state: 'disabled' });
      ['1', '2', '3', '4'].forEach((n, i) => {
        const bx = px + 54 + i * 50;
        const cur = i === 0;
        s += rect(bx, py, 44, 44, { r: 10, fill: cur ? c('ink.900') : c('white'), stroke: cur ? undefined : c('ink.300'), sw: 1.5 });
        s += T(bx + 22, mid(py, 44, 16), n, { size: 16, weight: 600, fill: cur ? c('white') : c('ink.900'), anchor: 'middle' });
      });
      s += btn(px + 54 + 4 * 50, py, '', { variant: 'secondary', icon: 'chevron-right', iconOnly: true });
      endY = py + 44;
    }
    const y2 = Math.max(endY, y + 166 + fl.h) + 120;
    const f = footer(DW, y2);
    return { svg: s + f.svg, h: y2 + f.h };
  }

  function catalogMobile() {
    let s = mheader();
    let y = 64;
    s += crumbs(MX, y + 36, ['Главная', 'Питомцы']);
    s += D(MX, y + 84, 'Питомцы', 31);
    s += muted(MX + measure('Питомцы', 31, 600, 'display') + 10, y + 82, '126 ищут дом', { size: 14 });
    s += UI.field(MX, y + 104, MCW, { icon: 'search', placeholder: 'Кличка питомца' });
    y += 104 + 64;
    s += btn(MX, y, 'Фильтры', { variant: 'secondary', icon: 'filter', w: 171 });
    s += circle(MX + 165, y + 4, 12, { fill: c('ink.900') }) + T(MX + 165, y + 8.5, '4', { size: 12, weight: 700, fill: c('white'), anchor: 'middle' });
    s += btn(MX + 187, y, 'Дольше ждут', { variant: 'secondary', iconRight: 'chevron-down', w: 171 });
    y += 60;
    let ax = MX;
    ['Собаки', '1–3 года'].forEach((a) => {
      const w = measure(a, 14, 500) + 48;
      s += rect(ax, y, w, 32, { r: 16, fill: c('ink.100') });
      s += T(ax + 14, mid(y, 32, 14), a, { size: 14, weight: 500 });
      s += icon('x', ax + w - 26, y + 8, 16, c('ink.700'));
      ax += w + 8;
    });
    s += T(ax + 4, mid(y, 32, 14), 'ещё 2', { size: 14, weight: 600, decoration: 'underline' });
    y += 52;
    const list = [PETS[0], PETS[2], PETS[4], PETS[6], PETS[8], { ...PETS[2], name: 'Тоша', short: '1 год, мальчик', bg: c('tag.100') }];
    let h = 0;
    list.forEach((p, i) => {
      const r = petMini(MX + (i % 2) * 187, y + Math.floor(i / 2) * 290, 171, p);
      s += r.svg;
      h = r.h;
    });
    y += 290 * 3 + 8;
    s += btn(MX, y, 'Показать ещё 12', { variant: 'secondary', w: MCW });
    s += small(MW / 2, y + 72, 'Показано 6 из 37', { anchor: 'middle' });
    y += 120;
    const f = mfooter(y);
    return { svg: s + f.svg, h: y + f.h };
  }

  /** Лист фильтров на телефоне (открыт поверх каталога). */
  function catalogFilterSheet() {
    let s = rect(0, 0, MW, 844, { fill: c('ink.950'), opacity: 0.55 });
    s += rect(0, 40, MW, 830, { r: 24, fill: c('white') });
    s += rect(MW / 2 - 20, 50, 40, 4, { r: 2, fill: c('ink.300') });
    s += D(MX, 100, 'Фильтры', 25);
    s += btn(MW - MX - 44, 70, '', { variant: 'ghost', icon: 'x', iconOnly: true });
    let y = 148;
    const groups = [
      ['Вид', [['Собаки', true], ['Кошки', false], ['Другие', false]]],
      ['Пол', [['Мальчик', false], ['Девочка', false]]],
      ['Возраст', [['До года', false], ['1–3 года', true], ['3–7 лет', false], ['Старше 7', false]]],
      ['Размер', [['Маленький', false], ['Средний', false], ['Крупный', false]]],
    ];
    for (const [t, ch] of groups) {
      const r = filterGroup(MX, y, t, ch);
      s += r.svg;
      y += r.h + 36;
    }
    s += UI.field(MX, y - 6, MCW, { label: 'Город', value: 'Алматы', select: true });
    y += 96;
    s += UI.toggle(MX, y - 10, 'Готов к выгулу', true);
    s += UI.toggle(MX, y + 30, 'Можно под опеку', false);
    s += stickyBar(768, btn(MX, 784, 'Сбросить', { variant: 'ghost', w: 120 }) + btn(MX + 132, 784, 'Показать 37 питомцев', { w: MCW - 132 }));
    return { svg: s, h: 844 };
  }

  /* =====================================================================
   * 03. Карточка питомца
   * ===================================================================== */
  function petGallery(x, y, w, h) {
    let s = UI.petPhoto(x, y, w, h, { kind: 'dog', fur: 'caramel', bg: c('sky.100'), sun: true, r: 24 });
    const tw = (w - 3 * 12) / 4;
    const thumbs = [
      { bg: c('sky.100'), fur: 'caramel', sun: true },
      { bg: c('meadow.100'), fur: 'caramel', zoom: 1.3 },
      { bg: c('tag.100'), fur: 'caramel', zoom: 0.8 },
      { bg: c('ink.100'), fur: 'caramel', zoom: 1.1 },
    ];
    thumbs.forEach((t, i) => {
      const tx = x + i * (tw + 12);
      s += UI.petPhoto(tx, y + h + 12, tw, 96, { kind: 'dog', ...t, r: 12 });
      if (i === 0) s += rect(tx, y + h + 12, tw, 96, { r: 12, stroke: c('ink.900'), sw: 3 });
    });
    s += rect(x + w - 92, y + h - 52, 76, 36, { r: 18, fill: c('white') });
    s += icon('camera', x + w - 82, y + h - 44, 20, c('ink.900'));
    s += T(x + w - 56, y + h - 28, '1 / 6', { size: 14, weight: 600 });
    return s;
  }

  function petFacts(x, y, w) {
    // характер и здоровье: две колонки
    let s = D(x, y, 'Характер', 25);
    const ch = [
      [true, 'Ладит с детьми'],
      [true, 'Ладит с кошками'],
      [false, 'С другими собаками — осторожно'],
      [true, 'Ходит на поводке'],
      [true, 'Приучен к туалету на улице'],
    ];
    ch.forEach(([ok, l], i) => (s += checkRow(x, y + 46 + i * 36, ok, l)));
    const hx = x + w / 2 + 16;
    s += D(hx, y, 'Здоровье', 25);
    const hl = [
      ['syringe', 'Привит, последняя прививка в мае 2026'],
      ['shield-check', 'Кастрирован'],
      ['tag', 'Чипирован'],
      ['info', 'Здоров, особого ухода не нужно'],
    ];
    hl.forEach(([ic, l], i) => (s += iconLine(hx, y + 46 + i * 36, ic, l, { fill: c('ink.900'), icFill: c('sky.700') })));
    return { svg: s, h: 46 + 5 * 36 };
  }

  function petDesktop() {
    let s = header(DW, { active: 0 });
    let y = 76;
    s += crumbs(X0, y + 44, ['Главная', 'Питомцы', 'Бублик']);
    y += 76;
    const gw = 720;
    s += petGallery(X0, y, gw, 520);
    // правая колонка
    const rx = X0 + gw + 48;
    const rw = CW - gw - 48;
    s += B(rx, y, 'Ищет дом', 'info');
    s += btn(rx + rw - 44, y - 8, '', { variant: 'secondary', icon: 'heart', iconOnly: true });
    s += btn(rx + rw - 44 - 52, y - 8, '', { variant: 'secondary', icon: 'share', iconOnly: true });
    s += D(rx, y + 84, 'Бублик', 49);
    s += T(rx, y + 120, 'Собака, 2 года, мальчик, средний', { size: 18, fill: c('ink.700') });
    let tx = rx;
    ['спокойный', 'ладит с детьми', 'любит долгие прогулки'].forEach((t) => {
      const tw = measure(t, 14, 500) + 24;
      s += rect(tx, y + 140, tw, 30, { r: 15, fill: c('ink.100') });
      s += T(tx + 12, mid(y + 140, 30, 14), t, { size: 14, weight: 500, fill: c('ink.700') });
      tx += tw + 8;
    });
    // блок действий
    const ay = y + 200;
    s += rect(rx, ay, rw, 296, { r: 24, fill: c('white'), stroke: c('ink.200') });
    s += btn(rx + 24, ay + 24, 'Хочу забрать', { size: 'lg', icon: 'home', w: rw - 48 });
    const hw = (rw - 48 - 12) / 2;
    s += btn(rx + 24, ay + 88, 'Взять под опеку', { variant: 'secondary', w: hw });
    s += btn(rx + 24 + hw + 12, ay + 88, 'Погулять', { variant: 'secondary', icon: 'leash', w: hw });
    s += divider(rx + 24, ay + 156, rw - 48);
    s += P(rx + 24, ay + 190, 'Не готовы забрать? Помогите приюту, где живёт Бублик: деньги пойдут на корм и лечение.', rw - 48, { size: 14, lh: 21 }).svg;
    s += btn(rx + 24, ay + 236, 'Помочь приюту', { variant: 'donate', icon: 'heart', size: 'sm' });
    // приют
    const sy = ay + 320;
    s += rect(rx, sy, rw, 120, { r: 24, fill: c('ink.50') });
    s += iconDisc(rx + 48, sy + 44, 24, 'home', c('meadow.100'), c('meadow.700'));
    s += T(rx + 84, sy + 40, 'Дом лап', { size: 17, weight: 600 });
    s += T(rx + 84, sy + 62, 'Алматы, Бостандыкский район', { size: 14, fill: c('ink.700') });
    s += link(rx + 24, sy + 98, 'Страница приюта', { size: 14 });
    s += iconLine(rx + 220, sy + 98, 'phone', '+7 701 234 56 78', { size: 14, is: 18 });
    y += 520 + 12 + 96 + 80;
    // характер и здоровье
    const ff = petFacts(X0, y, gw);
    s += ff.svg;
    y += ff.h + 64;
    s += D(X0, y, 'История', 25);
    s += P(X0, y + 40, 'Бублика нашли в марте у рынка «Алтын Орда»: худой, с порезом на лапе. За полгода в приюте он поправился, выучил команды «сидеть» и «рядом» и полюбил долгие прогулки по Терренкуру. Бублик спокойно ведёт себя в транспорте и не тянет поводок. Ищет семью, где с ним будут гулять дважды в день.', gw, { size: 16, lh: 26 }).svg;
    y += 40 + 26 * 5 + 64;
    // выгул этого питомца
    s += D(X0, y, 'Погулять с Бубликом', 25);
    s += link(X0 + CW, y - 2, 'Все слоты приюта', { anchor: 'end' });
    [['Сегодня, ср', 'open', '17:00–18:00', 'Осталось 2 места'], ['Сб, 8 ноября', 'open', '10:00–11:00', 'Осталось 3 места'], ['Сб, 8 ноября', 'full', '12:00–13:00', 'Мест нет'], ['Вс, 9 ноября', 'few', '10:00–11:00', 'Осталось 1 место']].forEach(([d, st, t, n], i) => {
      s += daySlot(X0 + i * 192, y + 44, d, 'Дом лап', st, t, n);
    });
    s += banner(X0 + 790, y + 44, 490, 'info', 'info', 'Перед первой прогулкой', 'Возьмите удостоверение личности: приют сверит его с записью. Гулять можно с 18 лет.').svg;
    y += 44 + 110 + 96;
    // похожие
    s += sectionHead(X0, y, CW, 'Похожие питомцы', 'Все собаки', { size: 31 });
    [PETS[2], PETS[6], PETS[8], PETS[4]].forEach((p, i) => (s += UI.petCard(X0 + i * 328, y + 36, p)));
    y += 36 + 438 + 120;
    const f = footer(DW, y);
    return { svg: s + f.svg, h: y + f.h };
  }

  function petMobile() {
    let s = mheader();
    let y = 64;
    s += crumbs(MX, y + 34, ['Питомцы', 'Бублик']);
    y += 52;
    s += UI.petPhoto(MX, y, MCW, 300, { kind: 'dog', fur: 'caramel', bg: c('sky.100'), sun: true, r: 24 });
    // точки галереи
    for (let i = 0; i < 6; i++) s += circle(MW / 2 - 40 + i * 16, y + 280, 4, { fill: i === 0 ? c('ink.900') : c('white') });
    s += circle(MX + MCW - 30, y + 30, 20, { fill: c('white') }) + icon('heart', MX + MCW - 41, y + 19, 22, c('ink.900'));
    y += 324;
    s += B(MX, y, 'Ищет дом', 'info');
    s += D(MX, y + 70, 'Бублик', 31);
    s += T(MX, y + 100, 'Собака, 2 года, мальчик, средний', { size: 16, fill: c('ink.700') });
    let tx = MX;
    ['спокойный', 'ладит с детьми'].forEach((t) => {
      const tw = measure(t, 13, 500) + 20;
      s += rect(tx, y + 116, tw, 28, { r: 14, fill: c('ink.100') });
      s += T(tx + 10, mid(y + 116, 28, 13), t, { size: 13, weight: 500, fill: c('ink.700') });
      tx += tw + 6;
    });
    y += 172;
    s += rect(MX, y, MCW, 84, { r: 16, fill: c('ink.50') });
    s += iconDisc(MX + 36, y + 42, 20, 'home', c('meadow.100'), c('meadow.700'));
    s += T(MX + 66, y + 38, 'Дом лап, Алматы', { size: 16, weight: 600 });
    s += link(MX + 66, y + 62, 'Страница приюта', { size: 14 });
    y += 116;
    s += D(MX, y, 'Характер', 20);
    [[true, 'Ладит с детьми'], [true, 'Ладит с кошками'], [false, 'С собаками — осторожно'], [true, 'Ходит на поводке']].forEach(([ok, l], i) => (s += checkRow(MX, y + 40 + i * 34, ok, l)));
    y += 40 + 4 * 34 + 32;
    s += D(MX, y, 'Здоровье', 20);
    [['syringe', 'Привит'], ['shield-check', 'Кастрирован'], ['tag', 'Чипирован']].forEach(([ic, l], i) => (s += iconLine(MX, y + 40 + i * 34, ic, l, { fill: c('ink.900'), icFill: c('sky.700') })));
    y += 40 + 3 * 34 + 32;
    s += D(MX, y, 'История', 20);
    const p = P(MX, y + 34, 'Бублика нашли в марте у рынка «Алтын Орда»: худой, с порезом на лапе. За полгода в приюте он поправился, выучил команды и полюбил долгие прогулки.', MCW, { size: 15, lh: 23 });
    s += p.svg + link(MX, y + 34 + p.h + 10, 'Читать полностью', { chevron: false, size: 14 });
    y += 34 + p.h + 64;
    s += D(MX, y, 'Погулять с Бубликом', 20);
    s += daySlot(MX, y + 40, 'Сегодня, ср', 'Дом лап', 'open', '17:00–18:00', 'Осталось 2 места');
    s += daySlot(MX + 186, y + 40, 'Сб, 8 ноября', 'Дом лап', 'open', '10:00–11:00', 'Осталось 3 места');
    y += 40 + 110 + 40;
    s += rect(MX, y, MCW, 148, { r: 24, fill: c('tag.100') });
    s += P(MX + 20, y + 36, 'Не готовы забрать? Помогите приюту, где живёт Бублик.', MCW - 40, { size: 15, lh: 23, fill: c('ink.900') }).svg;
    s += btn(MX + 20, y + 80, 'Помочь приюту', { variant: 'donate', icon: 'heart', w: MCW - 40 });
    y += 148 + 48;
    const f = mfooter(y);
    s += f.svg;
    y += f.h + 76; // запас под липкую панель, чтобы она не закрывала подвал
    s += rect(0, y - 76, MW, 76, { fill: c('ink.900') });
    // липкая панель действий: всегда прижата к низу экрана; на макете показана в конце страницы
    const sb = stickyBar(y - 76, btn(MX, y - 60, 'Погулять', { variant: 'secondary', icon: 'leash', w: 140 }) + btn(MX + 152, y - 60, 'Хочу забрать', { icon: 'home', w: MCW - 152 }));
    s += g('sticky-cta', sb);
    return { svg: s, h: y };
  }

  /** Форма заявки «забрать / под опеку». */
  function applyDesktop() {
    let s = header(DW, { active: 0, user: { name: 'Айгерим', ini: 'АС', bell: 2 } });
    let y = 76;
    s += crumbs(X0, y + 44, ['Питомцы', 'Бублик', 'Заявка']);
    const fx = 340;
    const fw = 760;
    y += 104;
    s += UI.petPhoto(fx, y, 72, 72, { kind: 'dog', fur: 'caramel', bg: c('sky.100'), r: 16, zoom: 1.4, dy: 26 });
    s += D(fx + 92, y + 32, 'Заявка на Бублика', 31);
    s += muted(fx + 92, y + 62, 'Дом лап, Алматы. Приют ответит в течение двух дней.');
    y += 120;
    s += T(fx, y, 'Что вы хотите', { size: 15, weight: 600 });
    const opt = (ox, on, title, sub) => {
      let r = rect(ox, y + 16, 370, 96, { r: 16, fill: on ? c('sky.100') : c('white'), stroke: on ? c('ink.900') : c('ink.300'), sw: on ? 2 : 1.5 });
      r += UI.radio(ox + 20, y + 40, '', { checked: on });
      r += T(ox + 56, y + 56, title, { size: 17, weight: 600 });
      r += T(ox + 56, y + 80, sub, { size: 14, fill: c('ink.700') });
      return r;
    };
    s += opt(fx, true, 'Забрать домой', 'Бублик переедет к вам насовсем');
    s += opt(fx + 390, false, 'Взять под опеку', 'Бублик живёт в приюте, вы помогаете');
    y += 144;
    s += UI.field(fx, y, 370, { label: 'Имя', value: 'Айгерим Сапарова', required: true });
    s += UI.field(fx + 390, y, 370, { label: 'Телефон', value: '+7 701 234 56 78', required: true });
    y += 100;
    s += UI.field(fx, y, 370, { label: 'Город', value: 'Алматы', select: true, required: true });
    s += UI.field(fx + 390, y, 370, { label: 'Жильё', value: 'Квартира, есть балкон', select: true });
    y += 100;
    s += UI.textarea(fx, y, fw, 120, { label: 'Расскажите о себе', value: 'Живём вдвоём с мужем, дочке 9 лет. Раньше была собака, прожила с нами 12 лет. Работаю из дома, гулять сможем утром и вечером.', counter: '142 / 2000', hint: 'Опыт с животными, кто живёт с вами, сколько времени на прогулки' });
    y += 200;
    s += UI.checkbox(fx, y, 'Согласна на обработку персональных данных', { checked: true });
    s += T(fx + 32, y + 40, 'Приют увидит имя, телефон и ответы из анкеты.', { size: 14, fill: c('ink.500') });
    y += 72;
    s += btn(fx, y, 'Отправить заявку', { size: 'lg', w: 240 });
    s += btn(fx + 252, y, 'Отмена', { variant: 'ghost', size: 'lg' });
    y += 132;
    const f = footer(DW, y);
    return { svg: s + f.svg, h: y + f.h };
  }

  /* ---------- экспорт части 1 ---------- */
  window.SCREENS = { header, mheader, footer, mfooter, stickyBar, petMini, shelterCard, helpPaths, daySlot, banner, segmented, tabs, iconLine, checkRow, iconDisc, avatar, crumbs, link, sectionHead, divider, P, H, T, D, muted, small, B, BW, btn, btnW, PETS, CAMPAIGNS, SHELTERS, X0, CW, MX, MCW, DW, MW };

  window.buildScreens = function () {
    OUT.files = {};
    OUT.artboards = [];
    screenFile('01-home', 'Главная', [
      { key: 'desktop', name: 'Главная — desktop 1440', w: DW, build: homeDesktop },
      { key: 'mobile', name: 'Главная — mobile 390', w: MW, build: homeMobile },
    ]);
    screenFile('02-catalog', 'Каталог питомцев', [
      { key: 'desktop', name: 'Каталог — desktop 1440', w: DW, build: () => catalogDesktop() },
      { key: 'mobile', name: 'Каталог — mobile 390', w: MW, build: catalogMobile },
      { key: 'mobile-filters', name: 'Лист фильтров — mobile', w: MW, build: catalogFilterSheet },
      { key: 'empty', name: 'Пустой результат — desktop', w: DW, build: () => catalogDesktop({ empty: true }) },
      { key: 'loading', name: 'Загрузка — desktop', w: DW, build: () => catalogDesktop({ loading: true }) },
    ]);
    screenFile('03-pet', 'Карточка питомца', [
      { key: 'desktop', name: 'Карточка питомца — desktop 1440', w: DW, build: petDesktop },
      { key: 'mobile', name: 'Карточка питомца — mobile 390', w: MW, build: petMobile },
      { key: 'apply', name: 'Заявка: забрать или под опеку — desktop', w: DW, build: applyDesktop },
    ]);
    if (window.buildScreens2) window.buildScreens2(screenFile);
    return OUT;
  };
})();
