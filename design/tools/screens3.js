/*
 * Макеты экранов, часть 3: кабинет волонтёра, кабинет приюта, админ-модерация, системные состояния.
 */
(function () {
  const { c, rect, circle, line, icon, measure, wrap, mid } = window.K;
  const UI = window.UI;
  const S = window.SCREENS;
  const { header, mheader, banner, segmented, tabs, iconLine, iconDisc, avatar, crumbs, link, divider, P, T, D, muted, small, B, BW, btn, btnW, PETS, X0, CW, MX, MCW, DW, MW } = S;

  /* ---------- Каркас кабинета ---------- */
  const SIDE_W = 256;
  const CX = X0 + SIDE_W + 48; // начало контента
  const CCW = X0 + CW - CX; // ширина контента (976)

  function sidebar(y, title, sub, items, active, o = {}) {
    let s = '';
    s += T(X0, y + 24, title, { size: 17, weight: 600 });
    if (sub) s += small(X0, y + 46, sub, { size: 14 });
    let yy = y + (sub ? 76 : 52);
    if (o.badge) {
      s += o.badge(X0, yy);
      yy += 48;
    }
    items.forEach(([ic, label, count], i) => {
      const on = i === active;
      if (on) s += rect(X0, yy, SIDE_W, 44, { r: 10, fill: c('ink.100') }) + rect(X0, yy + 10, 4, 24, { r: 2, fill: c('tag.500') });
      s += icon(ic, X0 + 16, yy + 12, 20, on ? c('ink.900') : c('ink.700'));
      s += T(X0 + 46, mid(yy, 44, 15), label, { size: 15, weight: on ? 600 : 500, fill: on ? c('ink.900') : c('ink.700') });
      if (count) {
        const cw = measure(String(count), 12, 700) + 14;
        s += rect(X0 + SIDE_W - 12 - cw, yy + 12, cw, 20, { r: 10, fill: o.countTone === 'danger' && i === o.countIdx ? c('danger.solid') : c('ink.900') });
        s += T(X0 + SIDE_W - 12 - cw / 2, yy + 26, String(count), { size: 12, weight: 700, fill: c('white'), anchor: 'middle' });
      }
      yy += 48;
    });
    return s;
  }

  /** Таблица. cols: [{ t, w, align }], rows: [[cell, …]], cell — строка или (x, yMid, w) => svg. */
  function table(x, y, w, cols, rows, o = {}) {
    const rh = o.rh ?? 68;
    const hh = 44;
    const H = hh + rows.length * rh;
    let s = rect(x, y, w, H, { r: 16, fill: c('white'), stroke: c('ink.200') });
    s += `<path d="M${x} ${y + 16} a16 16 0 0 1 16 -16 H${x + w - 16} a16 16 0 0 1 16 16 V${y + hh} H${x} Z" fill="${c('ink.50')}"/>`;
    s += line(x, y + hh, x + w, y + hh, { stroke: c('ink.200') });
    let cx = x + 20;
    cols.forEach((col) => {
      const tx = col.align === 'end' ? cx + col.w - 20 : cx;
      s += T(tx, y + 27, col.t, { size: 13, weight: 600, fill: c('ink.700'), anchor: col.align === 'end' ? 'end' : undefined });
      cx += col.w;
    });
    rows.forEach((r, i) => {
      const ry = y + hh + i * rh;
      if (i > 0) s += line(x, ry, x + w, ry, { stroke: c('ink.200') });
      let rx = x + 20;
      r.forEach((cell, j) => {
        const col = cols[j];
        const ym = ry + rh / 2;
        if (typeof cell === 'function') s += cell(rx, ym, col.w - 20);
        else if (cell != null) s += T(col.align === 'end' ? rx + col.w - 20 : rx, ym + 5, cell, { size: 15, anchor: col.align === 'end' ? 'end' : undefined, fill: c('ink.900') });
        rx += col.w;
      });
    });
    return { svg: s, h: H };
  }

  const petCell = (p, sub) => (x, ym) =>
    UI.petPhoto(x, ym - 22, 44, 44, { kind: p.kind, fur: p.fur, bg: p.bg, r: 10, zoom: 1.5, dy: 15 }) + T(x + 56, ym - 2, p.name, { size: 15, weight: 600 }) + small(x + 56, ym + 17, sub ?? p.short);
  const badgeCell = (l, t, o) => (x, ym) => B(x, ym - 14, l, t, o);
  const twoLine = (a, b) => (x, ym) => T(x, ym - 2, a, { size: 15 }) + small(x, ym + 17, b);
  const actions = (list) => (x, ym, w) => {
    let s = '';
    let ax = x + w;
    [...list].reverse().forEach(([l, v, ic]) => {
      const bw = l ? btnW(l, { size: 'sm', icon: ic }) : 36;
      ax -= bw;
      s += btn(ax, ym - 18, l, { size: 'sm', variant: v, icon: ic, iconOnly: !l });
      ax -= 8;
    });
    return s;
  };

  function statTile(x, y, w, label, value, sub, o = {}) {
    let s = rect(x, y, w, 132, { r: 24, fill: o.bg ?? c('white'), stroke: o.bg ? undefined : c('ink.200') });
    s += T(x + 24, y + 36, label, { size: 14, weight: 500, fill: c('ink.700') });
    s += D(x + 24, y + 84, value, 31);
    if (sub) s += T(x + 24, y + 112, sub, { size: 13, fill: o.subFill ?? c('ink.500'), weight: o.subFill ? 600 : 400 });
    return s;
  }

  const ME = { name: 'Айгерим', ini: 'АС', bell: 2 };
  const VOL_NAV = [['home', 'Обзор'], ['leash', 'Мои выгулы', 2], ['paw', 'Мои заявки', 1], ['tenge', 'Мои пожертвования'], ['heart', 'Избранное', 6], ['bell', 'Уведомления', 2], ['user', 'Профиль'], ['plus', 'Стать приютом']];

  /* =====================================================================
   * 09. Кабинет волонтёра
   * ===================================================================== */
  function volunteerOverview() {
    let s = header(DW, { user: ME });
    let y = 76;
    s += crumbs(X0, y + 44, ['Главная', 'Кабинет']);
    y += 76;
    s += sidebar(y, 'Айгерим Сапарова', 'Волонтёр с августа 2026', VOL_NAV, 0);
    s += D(CX, y + 36, 'Здравствуйте, Айгерим', 39);
    y += 76;
    // ближайший выгул — главный блок
    s += rect(CX, y, CCW, 188, { r: 24, fill: c('meadow.100') });
    s += UI.petPhoto(CX + 24, y + 24, 140, 140, { kind: 'dog', fur: 'caramel', bg: c('sky.100'), r: 16, zoom: 1.1, dy: 10 });
    s += T(CX + 188, y + 48, 'Ближайший выгул', { size: 14, weight: 600, fill: c('meadow.700') });
    s += D(CX + 188, y + 88, 'Суббота, 8 ноября, 15:00', 25);
    s += T(CX + 188, y + 118, 'Бублик, приют «Дом лап», ул. Жандосова, 186', { size: 15, fill: c('ink.700') });
    s += B(CX + 188, y + 136, 'Подтверждена', 'success', { icon: 'check' });
    s += btn(CX + CCW - 24 - 180, y + 72, 'Как добраться', { variant: 'secondary', icon: 'map-pin', w: 180 });
    s += btn(CX + CCW - 24 - 180, y + 124, 'Отменить запись', { variant: 'ghost', w: 180, size: 'sm' });
    y += 188 + 40;
    // заявки и пожертвования
    const hw = (CCW - 32) / 2;
    s += T(CX, y + 20, 'Мои заявки', { size: 18, weight: 600 }) + link(CX + hw, y + 18, 'Все', { anchor: 'end', size: 14 });
    s += rect(CX, y + 40, hw, 208, { r: 24, fill: c('white'), stroke: c('ink.200') });
    s += petCell(PETS[3], 'Мурлыка, Астана')(CX + 24, y + 92, 200);
    s += B(CX + 24, y + 128, 'На рассмотрении', 'warning', { icon: 'clock' });
    s += P(CX + 24, y + 186, 'Забрать домой. Приют позвонит до 7 ноября, чтобы договориться о знакомстве.', hw - 48, { size: 14, lh: 21 }).svg;
    const dx = CX + hw + 32;
    s += T(dx, y + 20, 'Мои пожертвования', { size: 18, weight: 600 }) + link(dx + hw, y + 18, 'Все', { anchor: 'end', size: 14 });
    s += rect(dx, y + 40, hw, 208, { r: 24, fill: c('white'), stroke: c('ink.200') });
    s += D(dx + 24, y + 98, '17 000 ₸', 31);
    s += small(dx + 24, y + 122, 'за 2026 год, 4 пожертвования', { size: 14 });
    [['Операция для Ромашки', '5 000 ₸'], ['Корм на зиму для 40 кошек', '10 000 ₸']].forEach(([n, v], i) => {
      s += divider(dx + 24, y + 146 + i * 40, hw - 48);
      s += T(dx + 24, y + 172 + i * 40, n, { size: 14, fill: c('ink.700') });
      s += T(dx + hw - 24, y + 172 + i * 40, v, { size: 14, weight: 600, anchor: 'end' });
    });
    y += 248 + 48;
    s += T(CX, y + 20, 'Избранное', { size: 18, weight: 600 }) + link(CX + CCW, y + 18, 'Все 6', { anchor: 'end', size: 14 });
    [PETS[0], PETS[1], PETS[5], PETS[7]].forEach((p, i) => (s += S.petMini(CX + i * 250, y + 40, 226, { ...p, fav: true }).svg));
    y += 40 + 312 + 80;
    return { svg: s, h: y };
  }

  function volunteerWalks(o = {}) {
    let s = header(DW, { user: ME });
    let y = 76;
    s += crumbs(X0, y + 44, ['Кабинет', 'Мои выгулы']);
    y += 76;
    s += sidebar(y, 'Айгерим Сапарова', 'Волонтёр с августа 2026', VOL_NAV, 1);
    s += D(CX, y + 36, 'Мои выгулы', 39);
    s += btn(CX + CCW - btnW('Записаться ещё', { icon: 'plus' }), y, 'Записаться ещё', { icon: 'plus' });
    y += 84;
    s += tabs(CX, y, ['Предстоящие 2', 'Прошедшие 9', 'Отменённые'], o.empty ? 0 : 0, CCW);
    y += 44;
    if (o.empty) {
      s += rect(CX, y, CCW, 360, { r: 24, fill: c('ink.50') });
      s += `<g transform="translate(${CX + CCW / 2 - 72} ${y + 36}) scale(0.72)"><circle cx="100" cy="100" r="92" fill="${c('meadow.100')}"/><g transform="translate(0 6)">${UI.dogPortrait('caramel')}</g></g>`;
      s += D(CX + CCW / 2, y + 240, 'Вы ещё не записывались на выгул', 20, { anchor: 'middle' });
      s += muted(CX + CCW / 2, y + 272, 'Выберите приют и удобное время. Первая прогулка занимает около часа.', { anchor: 'middle' });
      s += btn(CX + CCW / 2 - 80, y + 296, 'Найти время', { w: 160, size: 'sm' });
      return { svg: s, h: y + 360 + 80 };
    }
    const rows = [
      [petCell(PETS[0], 'Дом лап, Алматы'), twoLine('Сб, 8 ноября', '15:00–16:00'), badgeCell('Подтверждена', 'success', { icon: 'check' }), actions([['Отменить', 'secondary']])],
      [petCell(PETS[6], 'Тёплый угол, Шымкент'), twoLine('Вс, 16 ноября', '11:00–12:00'), badgeCell('Ждёт подтверждения', 'warning', { icon: 'clock' }), actions([['Отменить', 'secondary']])],
    ];
    const t = table(CX, y, CCW, [{ t: 'Питомец и приют', w: 380 }, { t: 'Когда', w: 200 }, { t: 'Статус', w: 236 }, { t: '', w: 160, align: 'end' }], rows, { rh: 80 });
    s += t.svg;
    y += t.h + 24;
    s += banner(CX, y, CCW, 'info', 'info', null, 'Отменить запись можно не позже чем за 3 часа до начала. Если опаздываете — позвоните в приют.').svg;
    y += 64 + 48;
    // подсказка: прошедшие — пример строк с другими статусами
    s += T(CX, y + 20, 'Прошедшие', { size: 18, weight: 600 });
    const t2 = table(CX, y + 40, CCW, [{ t: 'Питомец и приют', w: 380 }, { t: 'Когда', w: 200 }, { t: 'Статус', w: 236 }, { t: '', w: 160, align: 'end' }], [
      [petCell(PETS[2], 'Дом лап, Алматы'), twoLine('Сб, 1 ноября', '10:00–11:00'), badgeCell('Состоялся', 'walk', { icon: 'leash' }), actions([['Записаться снова', 'ghost']])],
      [petCell(PETS[4], 'Дом лап, Алматы'), twoLine('Вс, 26 октября', '12:00–13:00'), badgeCell('Не пришёл', 'danger', { icon: 'x' }), null],
      [petCell(PETS[8], 'Хвостики, Караганда'), twoLine('Пт, 17 октября', '16:00–17:00'), badgeCell('Отклонена', 'danger'), null],
    ], { rh: 80 });
    s += t2.svg;
    y += 40 + t2.h + 80;
    return { svg: s, h: y };
  }

  function volunteerMobile() {
    let s = mheader({ user: { bell: 2 } });
    let y = 64;
    s += D(MX, y + 56, 'Мои выгулы', 25);
    y += 80;
    // горизонтальные вкладки разделов кабинета
    let tx = MX;
    ['Обзор', 'Выгулы', 'Заявки', 'Пожертвования'].forEach((t, i) => {
      const w = measure(t, 14, 600) + 28;
      s += rect(tx, y, w, 36, { r: 18, fill: i === 1 ? c('ink.900') : c('white'), stroke: i === 1 ? undefined : c('ink.300'), sw: 1.5 });
      s += T(tx + w / 2, mid(y, 36, 14), t, { size: 14, weight: 600, fill: i === 1 ? c('white') : c('ink.900'), anchor: 'middle' });
      tx += w + 8;
    });
    y += 64;
    s += segmented(MX, y, ['Предстоящие', 'Прошедшие'], 0, { w: MCW });
    y += 64;
    const card = (p, when, place, b, bt, bo) => {
      let r = rect(MX, y, MCW, 196, { r: 24, fill: c('white'), stroke: c('ink.200') });
      r += UI.petPhoto(MX + 16, y + 16, 64, 64, { kind: p.kind, fur: p.fur, bg: p.bg, r: 12, zoom: 1.4, dy: 22 });
      r += T(MX + 96, y + 40, p.name, { size: 17, weight: 600 });
      r += small(MX + 96, y + 62, place, { size: 14 });
      r += B(MX + 16, y + 96, b, bt, bo);
      r += iconLine(MX + 16, y + 156, 'calendar', when, { size: 15, fill: c('ink.900') });
      r += btn(MX + MCW - 16 - 104, y + 130, 'Отменить', { variant: 'secondary', size: 'sm', w: 104 });
      y += 196 + 12;
      return r;
    };
    s += card(PETS[0], 'Сб, 8 ноября, 15:00', 'Дом лап, Алматы', 'Подтверждена', 'success', { icon: 'check' });
    s += card(PETS[6], 'Вс, 16 ноября, 11:00', 'Тёплый угол, Шымкент', 'Ждёт подтверждения', 'warning', { icon: 'clock' });
    y += 24;
    s += btn(MX, y, 'Записаться ещё', { icon: 'plus', w: MCW });
    y += 92;
    // тост «запись отменена»
    s += rect(MX, y, MCW, 92, { r: 16, fill: c('white'), stroke: c('ink.200'), filter: 'url(#shadow-lg)' });
    s += iconDisc(MX + 36, y + 36, 18, 'check-circle', c('success.bg'), c('success.fg'));
    s += T(MX + 64, y + 34, 'Запись отменена', { size: 16, weight: 600 });
    s += P(MX + 64, y + 58, 'Место освободилось для других волонтёров.', MCW - 96, { size: 14, lh: 20 }).svg;
    y += 92 + 40;
    return { svg: s, h: Math.max(y, 844) };
  }

  /* =====================================================================
   * 10. Кабинет приюта
   * ===================================================================== */
  const SH_NAV = [['home', 'Сводка'], ['paw', 'Питомцы', 38], ['mail', 'Заявки', 3], ['tenge', 'Сборы', 2], ['heart', 'Пожертвования'], ['calendar', 'Слоты выгула'], ['leash', 'Записи на выгул', 5], ['edit', 'Профиль приюта'], ['users', 'Сотрудники']];
  const STAFF = { name: 'Динара', ini: 'ДЛ', tone: 'tag', bell: 5 };
  const shBadge = (x, y) => B(x, y, 'Проверенный приют', 'success', { icon: 'shield-check' });

  function shelterDashboard() {
    let s = header(DW, { user: STAFF });
    let y = 76;
    s += crumbs(X0, y + 44, ['Кабинет приюта', 'Сводка']);
    y += 76;
    s += sidebar(y, 'Дом лап', 'Алматы', SH_NAV, 0, { badge: shBadge });
    s += D(CX, y + 36, 'Сводка', 39);
    s += btn(CX + CCW - btnW('Добавить питомца', { icon: 'plus' }), y, 'Добавить питомца', { icon: 'plus' });
    y += 72;
    const tw = (CCW - 3 * 16) / 4;
    s += statTile(CX, y, tw, 'Ищут дом', '27', 'и 4 на лечении');
    s += statTile(CX + (tw + 16), y, tw, 'Новые заявки', '3', 'ответьте до 7 ноября', { subFill: c('warning.fg') });
    s += statTile(CX + 2 * (tw + 16), y, tw, 'Выгулы сегодня', '5', '2 ждут подтверждения', { subFill: c('warning.fg') });
    s += statTile(CX + 3 * (tw + 16), y, tw, 'Собрано за месяц', '730 500 ₸', 'по 2 активным сборам', { bg: c('tag.100') });
    y += 132 + 48;
    s += T(CX, y, 'Записи на сегодня, 5 ноября', { size: 20, weight: 600 }) + link(CX + CCW, y - 2, 'Все записи', { anchor: 'end', size: 14 });
    const rows = [
      [twoLine('10:00–11:00', 'слот 1 из 3'), (x, ym) => avatar(x + 18, ym, 18, 'ЕК', 'meadow') + T(x + 46, ym - 2, 'Ержан Касымов', { size: 15, weight: 600 }) + small(x + 46, ym + 17, '+7 707 555 12 34'), 'Граф', badgeCell('Подтверждена', 'success', { icon: 'check' }), actions([['Состоялся', 'secondary'], ['Не пришёл', 'ghost']])],
      [twoLine('10:00–11:00', 'слот 1 из 3'), (x, ym) => avatar(x + 18, ym, 18, 'АС', 'sky') + T(x + 46, ym - 2, 'Айгерим Сапарова', { size: 15, weight: 600 }) + small(x + 46, ym + 17, '+7 701 234 56 78'), 'Бублик', badgeCell('Ждёт подтверждения', 'warning', { icon: 'clock' }), actions([['Подтвердить', 'primary', 'check'], ['Отклонить', 'ghost']])],
      [twoLine('17:00–18:00', 'слот 2 из 3'), (x, ym) => avatar(x + 18, ym, 18, 'МБ', 'tag') + T(x + 46, ym - 2, 'Марат Бекенов', { size: 15, weight: 600 }) + small(x + 46, ym + 17, 'первая прогулка'), 'Ромашка', badgeCell('Ждёт подтверждения', 'warning', { icon: 'clock' }), actions([['Подтвердить', 'primary', 'check'], ['Отклонить', 'ghost']])],
    ];
    const t = table(CX, y + 24, CCW, [{ t: 'Время', w: 140 }, { t: 'Волонтёр', w: 250 }, { t: 'Питомец', w: 110 }, { t: 'Статус', w: 220 }, { t: '', w: 256, align: 'end' }], rows, { rh: 76 });
    s += t.svg;
    y += 24 + t.h + 48;
    // новые заявки
    s += T(CX, y, 'Новые заявки на питомцев', { size: 20, weight: 600 }) + link(CX + CCW, y - 2, 'Все заявки', { anchor: 'end', size: 14 });
    const rows2 = [
      [petCell(PETS[0]), twoLine('Айгерим Сапарова', 'Забрать домой'), twoLine('4 ноября', '12:40'), badgeCell('Новая', 'info'), actions([['Открыть', 'secondary']])],
      [petCell(PETS[4]), twoLine('Ольга Ким', 'Под опеку'), twoLine('3 ноября', '19:05'), badgeCell('На рассмотрении', 'warning'), actions([['Открыть', 'secondary']])],
    ];
    const t2 = table(CX, y + 24, CCW, [{ t: 'Питомец', w: 240 }, { t: 'Кто и что', w: 250 }, { t: 'Получена', w: 160 }, { t: 'Статус', w: 196 }, { t: '', w: 130, align: 'end' }], rows2, { rh: 76 });
    s += t2.svg;
    y += 24 + t2.h + 80;
    return { svg: s, h: y };
  }

  function shelterPets() {
    let s = header(DW, { user: STAFF });
    let y = 76;
    s += crumbs(X0, y + 44, ['Кабинет приюта', 'Питомцы']);
    y += 76;
    s += sidebar(y, 'Дом лап', 'Алматы', SH_NAV, 1, { badge: (x, yy) => B(x, yy, 'На модерации', 'warning', { icon: 'clock' }) });
    s += D(CX, y + 36, 'Питомцы', 39);
    s += btn(CX + CCW - btnW('Добавить питомца', { icon: 'plus' }), y, 'Добавить питомца', { icon: 'plus' });
    y += 64;
    const bn = banner(CX, y, CCW, 'warning', 'clock', 'Приют на проверке', 'Пока модератор проверяет документы, анкеты сохраняются как черновики и не видны на сайте. Обычно проверка занимает 1–2 рабочих дня.');
    s += bn.svg;
    y += bn.h + 28;
    s += UI.field(CX, y, 360, { icon: 'search', placeholder: 'Кличка', h: 44 });
    let fx = CX + 376;
    [['Все', 38, true], ['Ищут дом', 27], ['На лечении', 4], ['Черновики', 3]].forEach(([l, n, on]) => {
      const ch = UI.chip(fx, y + 4, l, { selected: on, count: n });
      s += ch.svg;
      fx += ch.w + 8;
    });
    y += 68;
    const R = (p, st, walk, apps, upd) => [petCell(p, p.meta), badgeCell(...st), walk ? (x, ym) => iconLine(x, ym + 5, 'leash', 'Да', { size: 15, is: 18, fill: c('meadow.700'), weight: 600 }) : (x, ym) => T(x, ym + 5, 'Нет', { size: 15, fill: c('ink.500') }), apps, upd, actions([[null, 'ghost', 'edit'], [null, 'ghost', 'trash']])];
    const rows = [
      R(PETS[0], ['Ищет дом', 'info'], true, '2', '4 ноября'),
      R(PETS[2], ['Забронирован', 'neutral'], true, '1', '2 ноября'),
      R(PETS[4], ['На лечении', 'warning'], false, '—', '1 ноября'),
      R({ ...PETS[3], name: 'Кнопка', meta: 'Кошка, 2 месяца, девочка' }, ['Черновик', 'neutral', { dashed: true }], false, '—', 'сегодня'),
      R({ ...PETS[6], name: 'Шарик', meta: 'Собака, 9 лет, мальчик' }, ['Нашёл дом', 'success', { icon: 'home' }], false, '—', '28 октября'),
    ];
    const t = table(CX, y, CCW, [{ t: 'Питомец', w: 300 }, { t: 'Статус', w: 200 }, { t: 'Выгул', w: 100 }, { t: 'Заявки', w: 100, align: 'end' }, { t: 'Изменён', w: 150, align: 'end' }, { t: '', w: 126, align: 'end' }], rows, { rh: 76 });
    s += t.svg;
    y += t.h + 32;
    s += small(CX, y + 14, 'Показано 1–5 из 38', { size: 14, fill: c('ink.700') });
    y += 80;
    return { svg: s, h: y };
  }

  function shelterSlots() {
    let s = header(DW, { user: STAFF });
    let y = 76;
    s += crumbs(X0, y + 44, ['Кабинет приюта', 'Слоты выгула']);
    y += 76;
    s += sidebar(y, 'Дом лап', 'Алматы', SH_NAV, 5, { badge: shBadge });
    s += D(CX, y + 36, 'Слоты выгула', 39);
    y += 72;
    s += btn(CX, y, '', { variant: 'secondary', icon: 'chevron-left', iconOnly: true });
    s += T(CX + 60, y + 29, '3 – 9 ноября', { size: 20, weight: 600 });
    s += btn(CX + 60 + measure('3 – 9 ноября', 20, 600) + 16, y, '', { variant: 'secondary', icon: 'chevron-right', iconOnly: true });
    s += btn(CX + CCW - btnW('Шаблон недели', { icon: 'calendar' }), y, 'Шаблон недели', { variant: 'secondary', icon: 'calendar' });
    y += 68;
    // календарь недели (сокращённая ширина) + форма создания справа
    const gw = CCW;
    const colW = (gw - 6 * 8) / 7;
    const days = [['Пн', '3'], ['Вт', '4'], ['Ср', '5'], ['Чт', '6'], ['Пт', '7'], ['Сб', '8'], ['Вс', '9']];
    const slots = {
      0: [['10:00', '3/6', 'past']],
      2: [['17:00', '4/6', 'open']],
      3: [['10:00', '5/6', 'few'], ['16:00', '2/6', 'open']],
      5: [['10:00', '3/6', 'open'], ['12:00', '6/6', 'full'], ['15:00', '4/6', 'open']],
      6: [['10:00', '5/6', 'few'], ['14:00', '1/6', 'open']],
    };
    days.forEach(([dn, dd], i) => {
      const dx = CX + i * (colW + 8);
      s += T(dx, y + 16, dn, { size: 13, weight: 600, fill: c('ink.700') });
      s += T(dx, y + 36, dd, { size: 18, weight: 600 });
      s += rect(dx, y + 52, colW, 316, { r: 12, fill: c('ink.50') });
      (slots[i] ?? []).forEach(([t, n, st], j) => {
        const sy = y + 60 + j * 84;
        const col = { open: c('meadow.500'), few: c('warning.border'), full: c('ink.300'), past: c('ink.300') }[st];
        s += rect(dx + 6, sy, colW - 12, 76, { r: 10, fill: st === 'full' ? c('ink.100') : c('white'), stroke: col, sw: 1.5, dash: st === 'past' ? '4 4' : undefined });
        s += T(dx + 16, sy + 28, t, { size: 16, weight: 600, fill: st === 'past' ? c('ink.400') : c('ink.900') });
        s += T(dx + 16, sy + 52, `записано ${n}`, { size: 13, fill: c('ink.700') });
        s += T(dx + 16, sy + 68, st === 'full' ? 'мест нет' : st === 'past' ? 'прошёл' : '', { size: 12, fill: c('ink.500') });
      });
      if (!slots[i]) s += T(dx + colW / 2, y + 190, 'нет', { size: 13, fill: c('ink.400'), anchor: 'middle' });
    });
    // форма разового слота — под календарём
    y += 52 + 316 + 32;
    s += rect(CX, y, CCW, 236, { r: 24, fill: c('white'), stroke: c('ink.200') });
    s += T(CX + 24, y + 40, 'Новый разовый слот', { size: 20, weight: 600 });
    const fy = y + 64;
    s += UI.field(CX + 24, fy, 220, { label: 'Дата', value: 'Пт, 7 ноября', icon: 'calendar' });
    s += UI.field(CX + 260, fy, 120, { label: 'Начало', value: '16:00' });
    s += UI.field(CX + 396, fy, 120, { label: 'Конец', value: '17:00' });
    s += UI.field(CX + 532, fy, 96, { label: 'Мест', value: '6' });
    s += T(CX + 652, fy + 14, 'Собаки в слоте', { size: 14, weight: 600 });
    let cx = CX + 652;
    let cy = fy + 30;
    [['Бублик', true], ['Граф', true], ['Барон', false], ['Ромашка', false]].forEach(([l, on]) => {
      const ch = UI.chip(cx, cy, l, { selected: on });
      if (cx + ch.w > CX + CCW - 24) {
        cx = CX + 652;
        cy += 44;
      }
      s += UI.chip(cx, cy, l, { selected: on }).svg;
      cx += ch.w + 8;
    });
    s += btn(CX + 24, y + 168, 'Создать слот', { icon: 'plus' });
    s += small(CX + 24 + btnW('Создать слот', { icon: 'plus' }) + 16, y + 196, 'Волонтёры увидят слот сразу после создания.', { size: 14 });
    y += 236 + 80;
    return { svg: s, h: y };
  }

  function shelterMobile() {
    let s = mheader({ user: { bell: 5 } });
    let y = 64;
    s += small(MX, y + 36, 'Кабинет приюта «Дом лап»', { size: 14, weight: 600, fill: c('ink.700') });
    s += D(MX, y + 76, 'Сводка', 25);
    y += 100;
    const tw = (MCW - 12) / 2;
    s += `<g transform="translate(0 0)">${statTile(MX, y, tw, 'Новые заявки', '3', 'ответьте до 7 ноября', { subFill: c('warning.fg') })}${statTile(MX + tw + 12, y, tw, 'Выгулы сегодня', '5', '2 ждут ответа', { subFill: c('warning.fg') })}</g>`;
    y += 148;
    s += T(MX, y + 20, 'Ждут подтверждения', { size: 17, weight: 600 });
    y += 40;
    [['Айгерим Сапарова', 'Бублик, 10:00–11:00', 'АС', 'sky'], ['Марат Бекенов', 'Ромашка, 17:00–18:00', 'МБ', 'tag']].forEach(([n, d, ini, tone]) => {
      s += rect(MX, y, MCW, 140, { r: 20, fill: c('white'), stroke: c('ink.200') });
      s += avatar(MX + 36, y + 40, 20, ini, tone);
      s += T(MX + 68, y + 36, n, { size: 16, weight: 600 });
      s += small(MX + 68, y + 56, d, { size: 14 });
      const hw = (MCW - 40 - 8) / 2;
      s += btn(MX + 20, y + 80, 'Подтвердить', { icon: 'check', w: hw, size: 'sm' });
      s += btn(MX + 20 + hw + 8, y + 80, 'Отклонить', { variant: 'secondary', w: hw, size: 'sm' });
      y += 152;
    });
    y += 16;
    s += T(MX, y + 20, 'Разделы', { size: 17, weight: 600 });
    y += 36;
    SH_NAV.slice(1, 7).forEach(([ic, l, n]) => {
      s += icon(ic, MX, y + 12, 20, c('ink.700'));
      s += T(MX + 32, y + 27, l, { size: 16, weight: 500 });
      if (n) s += T(MW - MX - 28, y + 27, String(n), { size: 15, weight: 600, fill: c('ink.500'), anchor: 'end' });
      s += icon('chevron-right', MW - MX - 20, y + 12, 20, c('ink.400'));
      s += divider(MX, y + 44, MCW);
      y += 48;
    });
    y += 40;
    return { svg: s, h: Math.max(y, 844) };
  }

  /* =====================================================================
   * 11. Админка: модерация приютов
   * ===================================================================== */
  const ADMIN_NAV = [['home', 'Статистика'], ['shield-check', 'Приюты', 4], ['paw', 'Анкеты'], ['tenge', 'Сборы'], ['users', 'Пользователи']];

  function adminShelters(o = {}) {
    let s = header(DW, { user: { name: 'Модератор', ini: 'МД', tone: 'meadow', bell: 4 } });
    let y = 76;
    s += crumbs(X0, y + 44, ['Админка', 'Приюты']);
    y += 76;
    s += sidebar(y, 'Админка', 'Модерация площадки', ADMIN_NAV, 1);
    s += D(CX, y + 36, 'Приюты', 39);
    y += 64;
    s += segmented(CX, y, ['На проверке 4', 'Проверенные 12', 'Отклонённые 2', 'Заблокированные 1'], 0);
    y += 72;
    const sh = (name, city, who, when, bin) => (x, ym) => iconDisc(x + 20, ym, 20, 'home', c('ink.100'), c('ink.700')) + T(x + 52, ym - 2, name, { size: 15, weight: 600 }) + small(x + 52, ym + 17, city);
    const rows = [
      [sh('Лапа помощи', 'Павлодар'), twoLine('Ирина Ли', '+7 702 111 22 33'), '120440012345', twoLine('2 ноября', '3 файла'), badgeCell('На модерации', 'warning', { icon: 'clock' }), actions([['Одобрить', 'primary', 'check'], ['Отклонить', 'secondary']])],
      [sh('Друг', 'Усть-Каменогорск'), twoLine('Ахмет Ж.', '+7 705 333 44 55'), '980312300456', twoLine('1 ноября', '2 файла'), badgeCell('На модерации', 'warning', { icon: 'clock' }), actions([['Одобрить', 'primary', 'check'], ['Отклонить', 'secondary']])],
      [sh('Котопёс', 'Актобе'), twoLine('Салтанат Н.', '+7 778 900 10 20'), '—', twoLine('31 октября', 'нет файлов'), badgeCell('На модерации', 'warning', { icon: 'clock' }), actions([['Одобрить', 'primary', 'check'], ['Отклонить', 'secondary']])],
    ];
    const t = table(CX, y, CCW, [{ t: 'Приют', w: 210 }, { t: 'Заявитель', w: 180 }, { t: 'БИН / ИИН', w: 140 }, { t: 'Подана', w: 120 }, { t: 'Статус', w: 150 }, { t: '', w: 176, align: 'end' }], rows, { rh: 76 });
    s += t.svg;
    y += t.h + 24;
    s += banner(CX, y, CCW, 'info', 'info', null, 'Проверьте БИН/ИИН в реестре и документы. Приют без документов можно одобрить только после звонка заявителю.').svg;
    y += 64 + 80;
    if (o.modal) {
      s += rect(0, 0, DW, Math.max(y, 860), { fill: c('ink.950'), opacity: 0.55 });
      const mw = 560;
      const mx = (DW - mw) / 2;
      const my = 220;
      s += rect(mx, my, mw, 420, { r: 24, fill: c('white'), filter: 'url(#shadow-lg)' });
      s += D(mx + 32, my + 52, 'Отклонить заявку приюта?', 20);
      s += btn(mx + mw - 60, my + 20, '', { variant: 'ghost', icon: 'x', iconOnly: true, size: 'sm' });
      s += P(mx + 32, my + 90, '«Котопёс», Актобе. Заявитель получит письмо с причиной и сможет подать заявку снова.', mw - 64, { size: 15, lh: 23 }).svg;
      s += UI.textarea(mx + 32, my + 140, mw - 64, 120, { label: 'Причина отказа', value: 'Не приложены документы и не указан БИН/ИИН. Добавьте свидетельство о регистрации.', counter: '84 / 500', hint: 'Увидит заявитель' });
      s += btn(mx + mw - 32 - 200, my + 340, 'Отклонить заявку', { variant: 'danger', w: 200 });
      s += btn(mx + mw - 32 - 200 - 12 - 140, my + 340, 'Отмена', { variant: 'secondary', w: 140 });
    }
    return { svg: s, h: Math.max(y, 860) };
  }

  function adminStats() {
    let s = header(DW, { user: { name: 'Модератор', ini: 'МД', tone: 'meadow', bell: 4 } });
    let y = 76;
    s += crumbs(X0, y + 44, ['Админка', 'Статистика']);
    y += 76;
    s += sidebar(y, 'Админка', 'Модерация площадки', ADMIN_NAV, 0);
    s += D(CX, y + 36, 'Статистика', 39);
    s += UI.field(CX + CCW - 220, y, 220, { value: 'Октябрь 2026', select: true, h: 44 });
    y += 72;
    const tw = (CCW - 3 * 16) / 4;
    s += statTile(CX, y, tw, 'Пристроено', '34', '+6 к сентябрю', { subFill: c('success.fg') });
    s += statTile(CX + tw + 16, y, tw, 'Собрано', '2,1 млн ₸', '612 пожертвований', { bg: c('tag.100') });
    s += statTile(CX + 2 * (tw + 16), y, tw, 'Прогулок', '286', 'в 9 приютах');
    s += statTile(CX + 3 * (tw + 16), y, tw, 'Неявки', '4,2%', '12 из 286', { subFill: c('ink.500') });
    y += 132 + 40;
    // столбики по неделям — простая диаграмма
    s += rect(CX, y, CCW, 300, { r: 24, fill: c('white'), stroke: c('ink.200') });
    s += T(CX + 24, y + 40, 'Прогулки по неделям', { size: 17, weight: 600 });
    const vals = [48, 61, 57, 72, 48];
    const labels = ['29 сен', '6 окт', '13 окт', '20 окт', '27 окт'];
    const bx0 = CX + 64;
    const base = y + 252;
    [0, 40, 80].forEach((v) => {
      const ly = base - v * 2;
      s += line(bx0 - 8, ly, CX + CCW - 24, ly, { stroke: c('ink.100') });
      s += T(bx0 - 16, ly + 4, String(v), { size: 12, fill: c('ink.500'), anchor: 'end' });
    });
    vals.forEach((v, i) => {
      const bx = bx0 + 40 + i * 176;
      s += rect(bx, base - v * 2, 64, v * 2, { r: 6, fill: c('meadow.500') });
      s += T(bx + 32, base - v * 2 - 8, String(v), { size: 13, weight: 600, anchor: 'middle' });
      s += T(bx + 32, base + 22, labels[i], { size: 12, fill: c('ink.500'), anchor: 'middle' });
    });
    y += 300 + 80;
    return { svg: s, h: y };
  }

  function adminMobile() {
    let s = mheader({ user: { bell: 4 } });
    let y = 64;
    s += small(MX, y + 36, 'Админка', { size: 14, weight: 600, fill: c('ink.700') });
    s += D(MX, y + 76, 'Приюты на проверке', 25);
    y += 104;
    [['Лапа помощи', 'Павлодар', 'Ирина Ли', 'БИН 120440012345', '2 ноября'], ['Котопёс', 'Актобе', 'Салтанат Н.', 'БИН не указан', '31 октября']].forEach(([n, city, who, bin, when]) => {
      s += rect(MX, y, MCW, 228, { r: 20, fill: c('white'), stroke: c('ink.200') });
      s += iconDisc(MX + 40, y + 40, 20, 'home', c('ink.100'), c('ink.700'));
      s += T(MX + 72, y + 36, n, { size: 17, weight: 600 });
      s += small(MX + 72, y + 56, `${city}, подана ${when}`, { size: 14 });
      s += B(MX + 20, y + 80, 'На модерации', 'warning', { icon: 'clock' });
      s += iconLine(MX + 20, y + 144, 'user', who, { size: 14, is: 18 });
      s += iconLine(MX + 180, y + 144, 'shield-check', bin, { size: 14, is: 18, fill: bin.includes('не') ? c('danger.fg') : c('ink.700') });
      const hw = (MCW - 40 - 8) / 2;
      s += btn(MX + 20, y + 168, 'Одобрить', { icon: 'check', w: hw, size: 'sm' });
      s += btn(MX + 20 + hw + 8, y + 168, 'Отклонить', { variant: 'secondary', w: hw, size: 'sm' });
      y += 240;
    });
    y += 40;
    return { svg: s, h: Math.max(y, 844) };
  }

  /* =====================================================================
   * 12. Системные состояния: ошибки, пустые экраны, загрузка, успех
   * ===================================================================== */
  function errorPage(code, title, body, cta, kind, fur) {
    let s = header(DW, {});
    const y = 76;
    s += `<g transform="translate(${DW / 2 - 130} ${y + 60}) scale(1.3)"><circle cx="100" cy="100" r="92" fill="${c('sky.100')}"/><clipPath id="clip-err-${code}"><circle cx="100" cy="100" r="92"/></clipPath><g clip-path="url(#clip-err-${code})"><g transform="translate(0 6)">${kind === 'cat' ? UI.catPortrait(fur) : UI.dogPortrait(fur)}</g></g></g>`;
    // бирка с кодом ошибки
    s += `<g transform="rotate(-8 ${DW / 2 + 120} ${y + 290})">${circle(DW / 2 + 120, y + 290, 56, { fill: c('tag.500') })}${circle(DW / 2 + 120, y + 246, 5, { fill: c('white'), stroke: c('ink.900'), sw: 2 })}${D(DW / 2 + 120, y + 302, code, 31, { anchor: 'middle' })}</g>`;
    s += D(DW / 2, y + 420, title, 39, { anchor: 'middle' });
    wrap(body, 560, 17).forEach((l, i) => (s += T(DW / 2, y + 462 + i * 27, l, { size: 17, fill: c('ink.700'), anchor: 'middle' })));
    const w1 = btnW(cta[0], {});
    const w2 = btnW(cta[1], {});
    const sx = DW / 2 - (w1 + 12 + w2) / 2;
    s += btn(sx, y + 530, cta[0], {});
    s += btn(sx + w1 + 12, y + 530, cta[1], { variant: 'secondary' });
    return { svg: s, h: 720 };
  }

  function emptyStates() {
    let s = '';
    const W = DW;
    s += D(X0, 90, 'Пустые состояния', 39);
    s += muted(X0, 126, 'Пустой экран подсказывает следующий шаг: короткий заголовок, что сделать, и одна кнопка.');
    const items = [
      ['heart', 'sky', 'В избранном пока никого', 'Нажмите на сердечко на карточке питомца, чтобы не потерять его.', 'Смотреть питомцев'],
      ['tenge', 'tag', 'Пожертвований пока нет', 'Здесь появится история пожертвований и квитанции.', 'Выбрать сбор'],
      ['paw', 'meadow', 'Заявок пока нет', 'Понравился питомец? Оставьте заявку на его странице.', 'Смотреть питомцев'],
      ['bell', 'sky', 'Новых уведомлений нет', 'Мы напишем, когда приют ответит на заявку или запись.', null],
      ['calendar', 'meadow', 'На эту неделю слотов нет', 'Создайте разовый слот или включите шаблон недели.', 'Создать слот'],
      ['mail', 'tag', 'Новых заявок нет', 'Когда кто-то захочет забрать питомца, заявка появится здесь.', null],
    ];
    items.forEach(([ic, tone, t, b, cta], i) => {
      const x = X0 + (i % 3) * 437;
      const y = 176 + Math.floor(i / 3) * 312;
      s += rect(x, y, 405, 288, { r: 24, fill: c('ink.50') });
      s += iconDisc(x + 202, y + 64, 36, ic, c(`${tone}.100`), c(`${tone}.700`));
      s += T(x + 202, y + 140, t, { size: 18, weight: 600, anchor: 'middle' });
      wrap(b, 320, 15).forEach((l, j) => (s += T(x + 202, y + 172 + j * 23, l, { size: 15, fill: c('ink.700'), anchor: 'middle' })));
      if (cta) s += btn(x + 202 - btnW(cta, { size: 'sm' }) / 2, y + 224, cta, { size: 'sm', variant: 'secondary' });
    });
    // загрузка и успех
    let y = 176 + 2 * 312 + 40;
    s += D(X0, y, 'Загрузка, отправка, успех', 31);
    y += 36;
    s += UI.skeletonCard(X0, y);
    s += small(X0, y + 462, 'Загрузка списка — скелетон карточки', { size: 14, weight: 600, fill: c('ink.700') });
    s += btn(X0 + 340, y, 'Пожертвовать', { variant: 'donate', state: 'loading', w: 260, size: 'lg' });
    s += small(X0 + 340, y + 82, 'Отправка формы — кнопка со спиннером,', { size: 14, fill: c('ink.700') });
    s += small(X0 + 340, y + 102, 'повторное нажатие заблокировано', { size: 14, fill: c('ink.700') });
    const toast = (tx, ty, kind, title, body) => {
      const tone = { success: ['check-circle', c('success.fg'), c('success.bg')], error: ['alert-circle', c('danger.fg'), c('danger.bg')], info: ['info', c('info.fg'), c('info.bg')] }[kind];
      let t = rect(tx, ty, 520, 104, { r: 16, fill: c('white'), stroke: c('ink.200'), filter: 'url(#shadow-lg)' });
      t += iconDisc(tx + 40, ty + 40, 20, tone[0], tone[2], tone[1]);
      t += T(tx + 76, ty + 36, title, { size: 16, weight: 600 });
      t += P(tx + 76, ty + 60, body, 400, { size: 14, lh: 21 }).svg;
      t += icon('x', tx + 484, ty + 18, 20, c('ink.500'));
      return t;
    };
    s += toast(X0 + 760, y, 'success', 'Заявка отправлена', 'Приют «Дом лап» свяжется с вами по телефону в течение двух дней.');
    s += toast(X0 + 760, y + 128, 'success', 'Анкета опубликована', 'Бублик появился в каталоге. Ссылку можно отправить друзьям.');
    s += toast(X0 + 760, y + 256, 'error', 'Не удалось сохранить', 'Нет связи с сервером. Изменения не потеряны — нажмите «Сохранить» ещё раз.');
    s += banner(X0 + 340, y + 160, 380, 'danger', 'alert-circle', 'Проверьте 2 поля', 'Телефон и пароль заполнены с ошибками — они отмечены ниже.').svg;
    y += 520;
    return { svg: s, h: y };
  }

  window.buildScreens3 = function (screenFile) {
    screenFile('09-cabinet-volunteer', 'Кабинет волонтёра', [
      { key: 'overview', name: 'Кабинет волонтёра: обзор — desktop 1440', w: DW, build: volunteerOverview },
      { key: 'walks', name: 'Мои выгулы — desktop', w: DW, build: () => volunteerWalks() },
      { key: 'walks-empty', name: 'Мои выгулы: пусто — desktop', w: DW, build: () => volunteerWalks({ empty: true }) },
      { key: 'mobile', name: 'Мои выгулы — mobile 390', w: MW, build: volunteerMobile },
    ]);
    screenFile('10-cabinet-shelter', 'Кабинет приюта', [
      { key: 'dashboard', name: 'Кабинет приюта: сводка — desktop 1440', w: DW, build: shelterDashboard },
      { key: 'pets', name: 'Питомцы приюта (приют на модерации) — desktop', w: DW, build: shelterPets },
      { key: 'slots', name: 'Слоты выгула — desktop', w: DW, build: shelterSlots },
      { key: 'mobile', name: 'Сводка — mobile 390', w: MW, build: shelterMobile },
    ]);
    screenFile('11-admin', 'Админка', [
      { key: 'shelters', name: 'Модерация приютов — desktop 1440', w: DW, build: () => adminShelters() },
      { key: 'reject-modal', name: 'Отклонение с причиной — desktop', w: DW, build: () => adminShelters({ modal: true }) },
      { key: 'stats', name: 'Статистика — desktop', w: DW, build: adminStats },
      { key: 'mobile', name: 'Модерация — mobile 390', w: MW, build: adminMobile },
    ]);
    screenFile('12-states', 'Состояния и ошибки', [
      { key: '404', name: 'Ошибка 404', w: DW, build: () => errorPage('404', 'Такой страницы нет', 'Возможно, питомца уже забрали домой и анкету убрали из каталога. Посмотрите других — многие ждут дольше.', ['Смотреть питомцев', 'На главную'], 'dog', 'graphite') },
      { key: '500', name: 'Ошибка 500', w: DW, build: () => errorPage('500', 'Сайт временно не отвечает', 'Мы уже чиним. Если вы оплачивали пожертвование, деньги не потеряются: квитанция придёт на почту.', ['Обновить страницу', 'На главную'], 'cat', 'smoke') },
      { key: 'empty-loading-success', name: 'Пустые экраны, загрузка, уведомления', w: DW, build: emptyStates },
    ]);
  };
})();
