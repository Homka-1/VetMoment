/*
 * Макеты экранов, часть 2: сборы и пожертвование, запись на выгул, приют, вход и регистрация.
 * Кабинеты, админка и системные состояния — в screens3.js.
 */
(function () {
  const { c, rect, circle, line, icon, g, measure, wrap, mid } = window.K;
  const UI = window.UI;
  const S = window.SCREENS;
  const { header, mheader, footer, mfooter, stickyBar, petMini, shelterCard, daySlot, banner, segmented, tabs, iconLine, iconDisc, avatar, crumbs, link, sectionHead, divider, P, T, D, muted, small, B, btn, btnW, PETS, CAMPAIGNS, X0, CW, MX, MCW, DW, MW } = S;

  /* ---------- Форма пожертвования ---------- */
  function donateForm(x, y, w, o = {}) {
    let s = '';
    let yy = y;
    s += T(x, yy, 'Сумма', { size: 15, weight: 600 });
    yy += 16;
    const presets = ['1 000 ₸', '2 000 ₸', '5 000 ₸', '10 000 ₸'];
    const pw = (w - 3 * 8) / 4;
    presets.forEach((p, i) => (s += UI.amount(x + i * (pw + 8), yy, pw, p, i === (o.err ? -1 : 2))));
    yy += 64;
    s += UI.field(x, yy, w, o.err ? { value: '150', suffix: '₸', state: 'error', error: 'Минимум 200 ₸. Увеличьте сумму или выберите готовую.' } : { placeholder: 'Другая сумма', suffix: '₸' });
    yy += 88;
    s += UI.field(x, yy, w, { label: 'Email для квитанции', value: o.err ? 'aigerim@mail' : 'aigerim@mail.kz', required: true, state: o.err ? 'error' : 'default', error: 'В адресе нет домена: например, aigerim@mail.kz' });
    yy += 108;
    s += UI.field(x, yy, w, { label: 'Имя', value: 'Айгерим', hint: 'Покажем в списке пожертвований' });
    yy += 108;
    s += UI.checkbox(x, yy, 'Не показывать моё имя', { checked: false });
    yy += 40;
    s += UI.checkbox(x, yy, 'Согласна с условиями оферты', { checked: !o.err });
    yy += 46;
    s += btn(x, yy, o.err ? 'Пожертвовать' : 'Пожертвовать 5 000 ₸', { variant: 'donate', size: 'lg', w, icon: 'heart' });
    yy += 72;
    s += iconLine(x, yy, 'shield-check', 'Оплата картой через защищённую страницу банка', { size: 13, is: 16, fill: c('ink.500') });
    return { svg: s, h: yy - y + 8 };
  }

  function raisedBlock(x, y, w, d) {
    let s = D(x, y, d.raised, 39);
    s += muted(x + measure(d.raised, 39, 600, 'display') + 10, y - 2, `из ${d.goal}`);
    s += UI.progress(x, y + 20, w, d.ratio, { h: 12 });
    s += iconLine(x, y + 64, 'users', d.people, { size: 14, is: 18 });
    s += iconLine(x + w / 2, y + 64, 'clock', `${d.left}, до 15 ноября`, { size: 14, is: 18 });
    return s;
  }

  /* =====================================================================
   * 04. Сборы: список
   * ===================================================================== */
  function campaignsDesktop() {
    let s = header(DW, { active: 1 });
    let y = 76;
    s += crumbs(X0, y + 44, ['Главная', 'Сборы']);
    s += D(X0, y + 112, 'Сборы', 49);
    s += P(X0, y + 152, 'Приюты собирают на конкретные нужды: операцию, корм, ремонт вольеров. Когда сбор закрыт, приют публикует отчёт с чеками.', 640, { size: 17, lh: 26 }).svg;
    y += 240;
    s += segmented(X0, y, ['Идут сейчас', 'Завершённые'], 0);
    s += UI.field(X0 + CW - 560, y, 260, { placeholder: 'Все приюты', select: true, h: 44 });
    s += UI.toggle(X0 + CW - 270, y + 9, 'Только на питомца', false);
    y += 84;
    CAMPAIGNS.slice(0, 3).forEach((d, i) => (s += UI.campaignCard(X0 + i * 437, y, { ...d, w: 405 })));
    y += 452 + 32;
    const more = [
      { title: 'Лекарства для Графа после чумки', shelter: 'Дом лап, Алматы', raised: '58 000 ₸', goal: '120 000 ₸', ratio: 0.48, people: '19 человек', left: 'Ещё 8 дней', status: ['Идёт сбор', 'tag'], kind: 'dog', fur: 'graphite', bg: c('tag.100') },
      { title: 'Новая крыша для кошатника', shelter: 'Мурлыка, Астана', raised: '1 210 000 ₸', goal: '1 500 000 ₸', ratio: 0.81, people: '206 человек', left: 'Ещё 3 дня', status: ['Идёт сбор', 'tag'], kind: 'cat', fur: 'ginger', bg: c('meadow.100') },
    ];
    more.forEach((d, i) => (s += UI.campaignCard(X0 + i * 437, y, { ...d, w: 405 })));
    // общий фонд
    const fx = X0 + 2 * 437;
    s += rect(fx, y, 405, 452, { r: 24, fill: c('tag.100') });
    s += iconDisc(fx + 60, y + 64, 32, 'tenge', c('white'), c('tag.700'));
    s += D(fx + 32, y + 148, 'Помочь приюту', 25);
    s += D(fx + 32, y + 178, 'без сбора', 25);
    s += P(fx + 32, y + 222, 'Деньги уйдут в общий фонд приюта: на корм, вакцины и счета ветклиники. Приют сам решит, что срочнее.', 340, { size: 15, lh: 23 }).svg;
    s += UI.field(fx + 32, y + 316, 341, { value: 'Дом лап, Алматы', select: true });
    s += btn(fx + 32, y + 380, 'Выбрать сумму', { variant: 'donate', w: 341, icon: 'heart' });
    y += 452 + 120;
    const f = footer(DW, y);
    return { svg: s + f.svg, h: y + f.h };
  }

  function campaignsMobile() {
    let s = mheader();
    let y = 64;
    s += D(MX, y + 64, 'Сборы', 31);
    s += P(MX, y + 98, 'Приюты собирают на конкретные нужды. После сбора — отчёт с чеками.', MCW, { size: 15, lh: 23 }).svg;
    y += 160;
    s += segmented(MX, y, ['Идут сейчас', 'Завершённые'], 0, { w: MCW });
    y += 60;
    s += btn(MX, y, 'Все приюты', { variant: 'secondary', iconRight: 'chevron-down', size: 'sm' });
    y += 56;
    [CAMPAIGNS[0], CAMPAIGNS[1]].forEach((d, i) => (s += UI.campaignCard(MX, y + i * 468, { ...d, w: MCW })));
    y += 2 * 468;
    s += btn(MX, y, 'Показать ещё', { variant: 'secondary', w: MCW });
    y += 92;
    const f = mfooter(y);
    return { svg: s + f.svg, h: y + f.h };
  }

  /* =====================================================================
   * 05. Страница сбора + пожертвование + результат платежа
   * ===================================================================== */
  function report(x, y, w, date, title, items, total) {
    let s = rect(x, y, w, 72 + items.length * 34 + 56, { r: 16, fill: c('white'), stroke: c('ink.200') });
    s += small(x + 24, y + 32, date);
    s += T(x + 24, y + 56, title, { size: 17, weight: 600 });
    items.forEach(([n, v], i) => {
      const iy = y + 92 + i * 34;
      s += T(x + 24, iy, n, { size: 15, fill: c('ink.700') });
      s += T(x + w - 24, iy, v, { size: 15, weight: 600, anchor: 'end' });
    });
    const ty = y + 72 + items.length * 34 + 22;
    s += divider(x + 24, ty - 6, w - 48);
    s += iconLine(x + 24, ty + 22, 'image', '3 фото чеков', { size: 14, is: 18 });
    s += T(x + w - 24, ty + 22, `Итого ${total}`, { size: 15, weight: 700, anchor: 'end' });
    return { svg: s, h: 72 + items.length * 34 + 56 };
  }

  function donorList(x, y, w, n = 5) {
    const rows = [
      ['Ержан', '10 000 ₸', '2 часа назад', 'ЕК', 'meadow'],
      ['Анонимно', '2 000 ₸', '5 часов назад', '?', 'sky'],
      ['Дана Л.', '5 000 ₸', 'вчера', 'ДЛ', 'tag'],
      ['Марат', '1 000 ₸', 'вчера', 'МА', 'sky'],
      ['Светлана К.', '20 000 ₸', '2 ноября', 'СК', 'meadow'],
    ].slice(0, n);
    let s = '';
    rows.forEach(([name, sum, when, ini, tone], i) => {
      const ry = y + i * 60;
      s += avatar(x + 20, ry + 20, 20, ini, tone);
      s += T(x + 52, ry + 18, name, { size: 15, weight: 600 });
      s += small(x + 52, ry + 38, when);
      s += T(x + w, ry + 26, sum, { size: 16, weight: 600, anchor: 'end' });
      if (i < rows.length - 1) s += divider(x, ry + 50, w);
    });
    return s;
  }

  function campaignDesktop(o = {}) {
    let s = header(DW, { active: 1 });
    let y = 76;
    s += crumbs(X0, y + 44, ['Главная', 'Сборы', 'Операция на лапе для Ромашки']);
    y += 76;
    const lw = 760;
    s += UI.petPhoto(X0, y, lw, 400, { kind: 'dog', fur: 'caramel', bg: c('tag.100'), r: 24, zoom: 0.9 });
    s += rect(X0 + 18, y + 18, 108, 32, { r: 16, fill: c('white') }) + B(X0 + 20, y + 20, 'Идёт сбор', 'tag');
    y += 440;
    s += D(X0, y + 30, 'Операция на лапе для Ромашки', 31);
    s += iconLine(X0, y + 72, 'home', 'Дом лап, Алматы', { size: 15 });
    s += link(X0 + 200, y + 72, 'Анкета Ромашки', { size: 15 });
    y += 112;
    const p1 = P(X0, y, 'Ромашку сбила машина на Аль-Фараби. Задняя левая лапа сломана в двух местах, нужен металлоостеосинтез: пластина и спицы. Операцию сделает клиника «Вет-Мед» 18 ноября, если соберём сумму до 15-го.', lw, { size: 17, lh: 27 });
    s += p1.svg;
    y += p1.h + 16;
    const p2 = P(X0, y, 'В сумму входят операция, наркоз, три дня стационара, рентген до и после и антибиотики на две недели. Если соберём больше, остаток пойдёт на реабилитацию.', lw, { size: 17, lh: 27 });
    s += p2.svg;
    y += p2.h + 40;
    s += D(X0, y, 'На что нужны деньги', 25);
    y += 24;
    const est = [['Операция и пластина', '380 000 ₸'], ['Наркоз и стационар, 3 дня', '120 000 ₸'], ['Рентген, 2 снимка', '36 000 ₸'], ['Лекарства на 2 недели', '64 000 ₸']];
    est.forEach(([n, v], i) => {
      s += T(X0, y + 30 + i * 40, n, { size: 16, fill: c('ink.700') });
      s += T(X0 + lw, y + 30 + i * 40, v, { size: 16, weight: 600, anchor: 'end' });
      s += divider(X0, y + 44 + i * 40, lw);
    });
    y += 30 + 4 * 40 + 56;
    s += D(X0, y, 'Отчёты приюта', 25);
    const r1 = report(X0, y + 28, lw, '28 октября', 'Первичный осмотр и рентген', [['Приём хирурга', '8 000 ₸'], ['Рентген, 2 проекции', '18 000 ₸'], ['Обезболивающее', '6 500 ₸']], '32 500 ₸');
    s += r1.svg;
    y += 28 + r1.h + 64;
    // правая колонка — липкая панель пожертвования
    const rx = X0 + lw + 48;
    const rw = CW - lw - 48;
    let ry = 152;
    const panelH = 812;
    s += rect(rx, ry, rw, panelH, { r: 24, fill: c('white'), stroke: c('ink.200'), filter: 'url(#shadow-md)' });
    s += raisedBlock(rx + 32, ry + 64, rw - 64, CAMPAIGNS[0]);
    s += divider(rx + 32, ry + 160, rw - 64);
    s += D(rx + 32, ry + 204, 'Пожертвовать', 20);
    const fm = donateForm(rx + 32, ry + 244, rw - 64, o);
    s += fm.svg;
    ry += panelH + 40;
    s += T(rx, ry, 'Последние пожертвования', { size: 17, weight: 600 });
    s += donorList(rx, ry + 24, rw);
    s += link(rx, ry + 24 + 5 * 60 + 8, 'Все 84 пожертвования', { size: 14 });
    y = Math.max(y, ry + 380) + 40;
    const f = footer(DW, y);
    return { svg: s + f.svg, h: y + f.h };
  }

  function campaignMobile() {
    let s = mheader();
    let y = 64;
    s += crumbs(MX, y + 34, ['Сборы', 'Ромашка']);
    y += 52;
    s += UI.petPhoto(MX, y, MCW, 220, { kind: 'dog', fur: 'caramel', bg: c('tag.100'), r: 24 });
    s += rect(MX + 14, y + 14, 108, 32, { r: 16, fill: c('white') }) + B(MX + 16, y + 16, 'Идёт сбор', 'tag');
    y += 252;
    const h = S.H(MX, y + 24, 'Операция на лапе для Ромашки', 25, MCW);
    s += h.svg;
    y += h.h + 24;
    s += iconLine(MX, y + 4, 'home', 'Дом лап, Алматы', { size: 14, is: 18 });
    y += 44;
    s += D(MX, y + 24, '412 500 ₸', 31);
    s += muted(MX + measure('412 500 ₸', 31, 600, 'display') + 8, y + 22, 'из 600 000 ₸', { size: 14 });
    s += UI.progress(MX, y + 42, MCW, 0.69, { h: 12 });
    s += iconLine(MX, y + 84, 'users', '84 человека', { size: 14, is: 18 });
    s += iconLine(MX + 180, y + 84, 'clock', 'Ещё 12 дней', { size: 14, is: 18 });
    y += 124;
    const p = P(MX, y, 'Ромашку сбила машина на Аль-Фараби. Задняя левая лапа сломана в двух местах, нужна операция с пластиной. Клиника «Вет-Мед» прооперирует 18 ноября, если соберём сумму до 15-го.', MCW, { size: 15, lh: 23 });
    s += p.svg + link(MX, y + p.h + 8, 'Читать полностью', { size: 14, chevron: false });
    y += p.h + 56;
    s += rect(0, y, MW, 760, { fill: c('ink.50') });
    s += D(MX, y + 48, 'Пожертвовать', 20);
    const fm = donateForm(MX, y + 88, MCW);
    s += fm.svg;
    y += 760 + 40;
    s += D(MX, y, 'Отчёты приюта', 20);
    const r1 = report(MX, y + 24, MCW, '28 октября', 'Осмотр и рентген', [['Приём хирурга', '8 000 ₸'], ['Рентген', '18 000 ₸'], ['Обезболивающее', '6 500 ₸']], '32 500 ₸');
    s += r1.svg;
    y += 24 + r1.h + 48;
    const f = mfooter(y);
    s += f.svg;
    y += f.h + 76;
    s += rect(0, y - 76, MW, 76, { fill: c('ink.900') });
    s += stickyBar(y - 76, btn(MX, y - 60, 'Помочь сбору', { variant: 'donate', icon: 'heart', w: MCW }));
    return { svg: s, h: y };
  }

  /** Результат платежа: success | pending | failed. */
  function paymentResult(kind) {
    const W = 760;
    let s = '';
    const conf = {
      success: { tone: 'success', ic: 'check-circle', badge: ['Оплачено', 'success', { icon: 'check' }], title: 'Спасибо, Айгерим!', body: '5 000 ₸ ушли на операцию Ромашки. Квитанция — на aigerim@mail.kz. Когда приют опубликует отчёт, мы напишем.', a1: 'Поделиться сбором', a1v: 'primary', a1i: 'share', a2: 'Посмотреть других питомцев' },
      pending: { tone: 'warning', ic: 'clock', badge: ['Ожидает оплаты', 'warning'], title: 'Ждём ответ банка', body: 'Обычно это занимает до минуты. Страница обновится сама, закрывать её не нужно.', a1: null, a2: 'Вернуться к сбору' },
      failed: { tone: 'danger', ic: 'alert-circle', badge: ['Ошибка оплаты', 'danger'], title: 'Платёж не прошёл', body: 'Банк отклонил карту: недостаточно средств. Деньги не списаны. Попробуйте другую карту или меньшую сумму.', a1: 'Попробовать снова', a1v: 'donate', a1i: 'heart', a2: 'Вернуться к сбору' },
    }[kind];
    const t = UI.TONE[conf.tone];
    s += rect(0, 0, W, 520, { fill: c('ink.50') });
    s += rect(80, 48, W - 160, 424, { r: 24, fill: c('white'), stroke: c('ink.200') });
    s += circle(W / 2, 124, 36, { fill: t.bg });
    if (kind === 'pending') s += UI.spinner(W / 2, 124, 18, t.fg);
    else s += icon(conf.ic, W / 2 - 18, 106, 36, t.fg);
    s += D(W / 2, 204, conf.title, 25, { anchor: 'middle' });
    const lines = wrap(conf.body, 480, 16);
    lines.forEach((l, i) => (s += T(W / 2, 242 + i * 25, l, { size: 16, fill: c('ink.700'), anchor: 'middle' })));
    const by = 242 + lines.length * 25 + 8;
    const bw = S.BW(conf.badge[0], conf.badge[2] ?? {});
    s += B(W / 2 - bw / 2, by, conf.badge[0], conf.badge[1], conf.badge[2]);
    const ay = by + 56;
    if (conf.a1) {
      const w1 = btnW(conf.a1, { icon: conf.a1i });
      const w2 = btnW(conf.a2, {});
      const sx = W / 2 - (w1 + 12 + w2) / 2;
      s += btn(sx, ay, conf.a1, { variant: conf.a1v, icon: conf.a1i });
      s += btn(sx + w1 + 12, ay, conf.a2, { variant: 'secondary' });
    } else s += btn(W / 2 - btnW(conf.a2) / 2, ay, conf.a2, { variant: 'secondary' });
    return { svg: s, h: 520 };
  }

  /* =====================================================================
   * 06. Запись на выгул
   * ===================================================================== */
  function stepper(x, y, active, labels, o = {}) {
    let s = '';
    let sx = x;
    labels.forEach((l, i) => {
      const done = i < active;
      const cur = i === active;
      s += circle(sx + 16, y, 16, { fill: done ? c('meadow.600') : cur ? c('ink.900') : c('white'), stroke: done || cur ? undefined : c('ink.300'), sw: 1.5 });
      if (done) s += icon('check', sx + 6, y - 10, 20, c('white'));
      else s += T(sx + 16, y + 5, String(i + 1), { size: 14, weight: 700, fill: cur ? c('white') : c('ink.500'), anchor: 'middle' });
      if (!o.compact || cur) s += T(sx + 42, y + 5, l, { size: 15, weight: cur ? 600 : 500, fill: cur || done ? c('ink.900') : c('ink.500') });
      const lw = o.compact ? (cur ? measure(l, 15, 600) + 42 + 12 : 32 + 12) : measure(l, 15, 600) + 42 + 24;
      if (i < labels.length - 1) s += line(sx + lw - 12, y, sx + lw + (o.compact ? 4 : 24), y, { stroke: c('ink.300'), sw: 1.5 });
      sx += lw + (o.compact ? 16 : 36);
    });
    return s;
  }

  const WEEK = [
    ['Пн', '3', [['open', '10:00–11:00', 'Осталось 3 места'], ['past', '15:00–16:00', 'Прошёл']], true],
    ['Вт', '4', [['past', '10:00–11:00', 'Прошёл']], true],
    ['Ср', '5', [['open', '17:00–18:00', 'Осталось 2 места']]],
    ['Чт', '6', [['few', '10:00–11:00', 'Осталось 1 место'], ['open', '16:00–17:00', 'Осталось 4 места']]],
    ['Пт', '7', []],
    ['Сб', '8', [['open', '10:00–11:00', 'Осталось 3 места'], ['full', '12:00–13:00', 'Мест нет'], ['selected', '15:00–16:00', 'Вы выбрали']]],
    ['Вс', '9', [['few', '10:00–11:00', 'Осталось 1 место'], ['open', '14:00–15:00', 'Осталось 5 мест']]],
  ];

  function walksDesktop() {
    let s = header(DW, { active: 2, user: { name: 'Айгерим', ini: 'АС', bell: 2 } });
    let y = 76;
    s += crumbs(X0, y + 44, ['Главная', 'Запись на выгул']);
    s += D(X0, y + 112, 'Запись на выгул', 49);
    s += P(X0, y + 150, 'Выберите приют и свободное время. Прогулка — около часа, приют выдаст поводок и расскажет о собаке.', 640, { size: 17, lh: 26 }).svg;
    y += 220;
    s += stepper(X0, y + 16, 1, ['Приют', 'Время', 'Подтверждение']);
    y += 64;
    // приюты
    const sh = [['Дом лап', 'Алматы', true], ['Тёплый угол', 'Шымкент', false], ['Хвостики', 'Караганда', false]];
    sh.forEach(([n, city, on], i) => {
      const sx = X0 + i * 260;
      s += rect(sx, y, 244, 72, { r: 16, fill: on ? c('meadow.100') : c('white'), stroke: on ? c('ink.900') : c('ink.300'), sw: on ? 2 : 1.5 });
      s += UI.radio(sx + 18, y + 25, '', { checked: on });
      s += T(sx + 52, y + 32, n, { size: 16, weight: 600 });
      s += T(sx + 52, y + 54, city, { size: 14, fill: c('ink.700') });
    });
    s += UI.field(X0 + 800, y + 12, 480, { value: 'Любая собака', select: true, icon: 'dog' });
    s += small(X0 + 800, y, 'Питомец (необязательно)', { size: 14, weight: 600, fill: c('ink.900') });
    y += 120;
    // неделя
    s += btn(X0, y, '', { variant: 'secondary', icon: 'chevron-left', iconOnly: true, state: 'disabled' });
    s += D(X0 + 64, y + 32, '3 – 9 ноября', 25);
    s += btn(X0 + 64 + measure('3 – 9 ноября', 25, 600, 'display') + 20, y, '', { variant: 'secondary', icon: 'chevron-right', iconOnly: true });
    // легенда
    let lx = X0 + CW;
    [['Прошёл', 'clock', c('ink.500')], ['Мест нет', 'x', c('ink.500')], ['Мало мест', 'alert-triangle', c('warning.fg')], ['Свободно', 'leash', c('meadow.700')]].forEach(([l, ic, col]) => {
      const w = measure(l, 14) + 24;
      lx -= w;
      s += icon(ic, lx, y + 13, 16, col) + T(lx + 22, y + 26, l, { size: 14, fill: c('ink.700') });
      lx -= 20;
    });
    y += 72;
    WEEK.forEach(([dn, dd, slots, past], i) => {
      const cx = X0 + i * 185.3;
      const today = dd === '5';
      s += rect(cx, y, 168, 56, { r: 12, fill: today ? c('ink.900') : c('ink.50') });
      s += T(cx + 16, y + 24, dn, { size: 13, weight: 600, fill: today ? c('tag.500') : past ? c('ink.400') : c('ink.700') });
      s += T(cx + 16, y + 44, `${dd} ноября`, { size: 16, weight: 600, fill: today ? c('white') : past ? c('ink.400') : c('ink.900') });
      if (today) s += T(cx + 152, y + 24, 'сегодня', { size: 12, weight: 600, fill: c('tag.500'), anchor: 'end' });
      if (!slots.length) {
        s += rect(cx, y + 72, 168, 76, { r: 10, stroke: c('ink.200'), dash: '4 4' });
        s += T(cx + 84, y + 115, 'Выгула нет', { size: 14, fill: c('ink.500'), anchor: 'middle' });
      }
      slots.forEach(([st, t, n], j) => (s += UI.slot(cx, y + 72 + j * 88, st, t, n)));
    });
    y += 72 + 3 * 88 + 24;
    // панель выбранного слота
    s += rect(X0, y, CW, 96, { r: 24, fill: c('meadow.100') });
    s += iconDisc(X0 + 52, y + 48, 24, 'calendar', c('white'), c('meadow.700'));
    s += T(X0 + 92, y + 42, 'Суббота, 8 ноября, 15:00–16:00', { size: 18, weight: 600 });
    s += T(X0 + 92, y + 68, 'Дом лап, Алматы, ул. Жандосова, 186. Осталось 2 места из 6.', { size: 15, fill: c('ink.700') });
    s += btn(X0 + CW - 24 - 200, y + 22, 'Продолжить', { size: 'lg', w: 200, iconRight: 'chevron-right' });
    y += 96 + 120;
    const f = footer(DW, y);
    return { svg: s + f.svg, h: y + f.h };
  }

  function walksMobile() {
    let s = mheader({ user: { bell: 2 } });
    let y = 64;
    s += D(MX, y + 60, 'Запись на выгул', 31);
    y += 96;
    s += stepper(MX, y, 1, ['Приют', 'Время', 'Подтверждение'], { compact: true });
    y += 36;
    s += UI.field(MX, y, MCW, { label: 'Приют', value: 'Дом лап, Алматы', select: true });
    y += 92;
    s += UI.field(MX, y, MCW, { label: 'Питомец', value: 'Любая собака', select: true });
    y += 104;
    s += D(MX, y + 4, 'Ноябрь', 20);
    s += btn(MW - MX - 96, y - 26, '', { variant: 'secondary', icon: 'chevron-left', iconOnly: true });
    s += btn(MW - MX - 44, y - 26, '', { variant: 'secondary', icon: 'chevron-right', iconOnly: true });
    y += 28;
    // лента дней
    const days = [['Ср', '5'], ['Чт', '6'], ['Пт', '7'], ['Сб', '8'], ['Вс', '9'], ['Пн', '10']];
    days.forEach(([dn, dd], i) => {
      const dx = MX + i * 62;
      const on = dd === '8';
      const empty = dd === '7';
      s += rect(dx, y, 54, 68, { r: 14, fill: on ? c('ink.900') : c('white'), stroke: on ? undefined : c('ink.300'), sw: 1.5 });
      s += T(dx + 27, y + 26, dn, { size: 13, weight: 600, fill: on ? c('tag.500') : c('ink.700'), anchor: 'middle' });
      s += T(dx + 27, y + 50, dd, { size: 18, weight: 600, fill: on ? c('white') : empty ? c('ink.400') : c('ink.900'), anchor: 'middle' });
      if (!empty) s += circle(dx + 27, y + 60, 2.5, { fill: on ? c('tag.500') : c('meadow.500') });
    });
    y += 96;
    s += T(MX, y, 'Суббота, 8 ноября', { size: 16, weight: 600 });
    y += 16;
    const sl = [['open', '10:00–11:00', 'Осталось 3 места'], ['full', '12:00–13:00', 'Мест нет'], ['selected', '15:00–16:00', 'Вы выбрали']];
    sl.forEach(([st, t, n], i) => (s += `<g transform="translate(${MX + (i % 2) * 187} ${y + Math.floor(i / 2) * 88}) scale(${171 / 168})">${UI.slot(0, 0, st, t, n)}</g>`));
    y += 2 * 88 + 40;
    const f = mfooter(y);
    s += f.svg;
    y += f.h + 92;
    s += rect(0, y - 92, MW, 92, { fill: c('ink.900') });
    s += rect(0, y - 92, MW, 92, { fill: c('white') }) + line(0, y - 92, MW, y - 92, { stroke: c('ink.200') });
    s += T(MX, y - 64, 'Сб, 8 ноября, 15:00–16:00', { size: 15, weight: 600 });
    s += small(MX, y - 44, 'Дом лап, осталось 2 места');
    s += btn(MW - MX - 150, y - 76, 'Продолжить', { w: 150 });
    return { svg: s, h: y };
  }

  /** Шаг 3: подтверждение брони. */
  function walkConfirm() {
    let s = header(DW, { active: 2, user: { name: 'Айгерим', ini: 'АС', bell: 2 } });
    let y = 76;
    s += crumbs(X0, y + 44, ['Запись на выгул', 'Подтверждение']);
    y += 84;
    s += stepper(X0, y + 16, 2, ['Приют', 'Время', 'Подтверждение']);
    y += 72;
    const lw = 760;
    s += D(X0, y + 30, 'С кем погуляете', 31);
    s += muted(X0, y + 64, 'Можно выбрать одну собаку. В этом слоте гуляют четверо.');
    y += 92;
    const dogs = [PETS[0], PETS[2], PETS[4], { ...PETS[8], status: ['Ищет дом', 'info'] }];
    dogs.forEach((p, i) => {
      const dx = X0 + (i % 2) * 380;
      const dy = y + Math.floor(i / 2) * 112;
      const on = i === 0;
      const busy = i === 2;
      s += rect(dx, dy, 364, 96, { r: 16, fill: on ? c('meadow.100') : c('white'), stroke: on ? c('ink.900') : c('ink.300'), sw: on ? 2 : 1.5 });
      s += UI.radio(dx + 18, dy + 37, '', { checked: on, state: busy ? 'disabled' : undefined });
      s += UI.petPhoto(dx + 54, dy + 12, 72, 72, { kind: p.kind, fur: p.fur, bg: p.bg, r: 12, zoom: 1.35, dy: 24, dim: busy });
      s += T(dx + 142, dy + 42, p.name, { size: 17, weight: 600, fill: busy ? c('ink.400') : c('ink.900') });
      s += T(dx + 142, dy + 66, busy ? 'Уже выбрали другие волонтёры' : p.traits.join(', '), { size: 14, fill: busy ? c('ink.500') : c('ink.700') });
    });
    y += 2 * 112 + 32;
    s += UI.textarea(X0, y, lw, 96, { label: 'Комментарий для приюта', value: 'Буду с подругой, она тоже записана на этот слот.', counter: '48 / 500', hint: 'Необязательно' });
    y += 180;
    s += UI.checkbox(X0, y, 'Мне есть 18 лет', { checked: true });
    s += UI.checkbox(X0, y + 40, 'Прочитала правила выгула приюта', { checked: true });
    y += 96;
    s += btn(X0, y, 'Записаться на выгул', { size: 'lg', icon: 'leash', w: 280 });
    s += btn(X0 + 292, y, 'Изменить время', { variant: 'ghost', size: 'lg' });
    // правая колонка — сводка и правила
    const rx = X0 + lw + 48;
    const rw = CW - lw - 48;
    let ry = 232;
    s += rect(rx, ry, rw, 236, { r: 24, fill: c('ink.50') });
    s += T(rx + 28, ry + 44, 'Ваша прогулка', { size: 15, weight: 600, fill: c('ink.700') });
    s += D(rx + 28, ry + 86, 'Сб, 8 ноября', 25);
    s += D(rx + 28, ry + 120, '15:00–16:00', 25);
    s += iconLine(rx + 28, ry + 166, 'map-pin', 'Дом лап, ул. Жандосова, 186', { size: 15 });
    s += iconLine(rx + 28, ry + 198, 'users', 'Осталось 2 места из 6', { size: 15 });
    ry += 260;
    s += T(rx, ry + 20, 'Правила приюта', { size: 17, weight: 600 });
    ['Приходите за 10 минут: инструктаж и поводок.', 'Возьмите удостоверение личности.', 'Не отпускайте собаку с поводка.', 'Отменить запись можно не позже чем за 3 часа.'].forEach((r, i) => {
      s += circle(rx + 6, ry + 58 + i * 34 - 5, 3, { fill: c('ink.900') });
      s += T(rx + 20, ry + 58 + i * 34, r, { size: 15, fill: c('ink.700') });
    });
    ry += 58 + 4 * 34 + 8;
    s += banner(rx, ry, rw, 'warning', 'clock', 'Запись ждёт подтверждения', 'Приют подтвердит её до пятницы, 18:00. Мы пришлём SMS и уведомление.').svg;
    y += 52 + 120;
    const f = footer(DW, y);
    return { svg: s + f.svg, h: y + f.h };
  }

  function walkSuccessMobile() {
    let s = mheader({ user: { bell: 3 } });
    let y = 64 + 48;
    s += circle(MW / 2, y + 48, 48, { fill: c('meadow.100') }) + icon('leash', MW / 2 - 24, y + 24, 48, c('meadow.700'));
    y += 140;
    s += D(MW / 2, y, 'Вы записаны', 25, { anchor: 'middle' });
    s += D(MW / 2, y + 30, 'на выгул', 25, { anchor: 'middle' });
    y += 56;
    const bw = S.BW('Ждёт подтверждения', { icon: 'clock' });
    s += B(MW / 2 - bw / 2, y, 'Ждёт подтверждения', 'warning', { icon: 'clock' });
    y += 56;
    s += rect(MX, y, MCW, 200, { r: 24, fill: c('ink.50') });
    s += UI.petPhoto(MX + 20, y + 20, 64, 64, { kind: 'dog', fur: 'caramel', bg: c('sky.100'), r: 12, zoom: 1.4, dy: 22 });
    s += T(MX + 100, y + 46, 'Бублик', { size: 17, weight: 600 });
    s += T(MX + 100, y + 70, 'Дом лап, Алматы', { size: 14, fill: c('ink.700') });
    s += iconLine(MX + 20, y + 130, 'calendar', 'Суббота, 8 ноября, 15:00–16:00', { size: 15 });
    s += iconLine(MX + 20, y + 166, 'map-pin', 'ул. Жандосова, 186', { size: 15 });
    y += 232;
    s += P(MX, y, 'Приют подтвердит запись до пятницы, 18:00. Если планы изменятся, отмените запись в кабинете не позже чем за 3 часа.', MCW, { size: 15, lh: 23 }).svg;
    y += 100;
    s += btn(MX, y, 'Добавить в календарь', { variant: 'secondary', icon: 'calendar', w: MCW });
    s += btn(MX, y + 56, 'Мои выгулы', { w: MCW });
    y += 140;
    return { svg: s, h: Math.max(y, 844) };
  }

  /* =====================================================================
   * 07. Страница приюта
   * ===================================================================== */
  function shelterDesktop() {
    let s = header(DW, { active: 3 });
    let y = 76;
    s += crumbs(X0, y + 44, ['Главная', 'Приюты', 'Дом лап']);
    y += 72;
    // шапка приюта
    s += rect(X0, y, CW, 280, { r: 24, fill: c('meadow.100') });
    // декоративный ряд бирок-портретов справа
    [['dog', 'caramel', c('sky.100')], ['cat', 'smoke', c('tag.100')], ['dog', 'graphite', c('white')]].forEach(([k, f, bg], i) => {
      s += `<g>${UI.petPhoto(X0 + CW - 120 - i * 128 - 80, y + 60 + (i % 2) * 24, 128, 160, { kind: k, fur: f, bg, r: 64 })}</g>`;
    });
    s += iconDisc(X0 + 80, y + 80, 44, 'home', c('white'), c('meadow.700'));
    s += D(X0 + 148, y + 84, 'Дом лап', 49);
    s += B(X0 + 148, y + 104, 'Проверенный приют', 'success', { icon: 'shield-check' });
    s += iconLine(X0 + 40, y + 190, 'map-pin', 'Алматы, ул. Жандосова, 186', { fill: c('ink.900') });
    s += iconLine(X0 + 340, y + 190, 'phone', '+7 701 234 56 78', { fill: c('ink.900') });
    s += iconLine(X0 + 540, y + 190, 'clock', 'Ежедневно 10:00–18:00', { fill: c('ink.900') });
    s += btn(X0 + 40, y + 212, 'Помочь приюту', { variant: 'donate', icon: 'heart' });
    s += btn(X0 + 40 + btnW('Помочь приюту', { icon: 'heart' }) + 12, y + 212, 'Записаться на выгул', { variant: 'secondary', icon: 'leash' });
    y += 280 + 56;
    s += tabs(X0, y, ['О приюте', 'Питомцы 38', 'Сборы 2', 'Отчёты 7', 'Выгул'], 0, CW);
    y += 72;
    const lw = 760;
    s += P(X0, y, 'Мы работаем с 2017 года и принимаем собак и кошек, пострадавших от людей и машин. Сейчас у нас 38 питомцев: 29 собак и 9 кошек. Всех прививаем, стерилизуем и чипируем до того, как отдать в семью.', lw, { size: 17, lh: 27 }).svg;
    s += P(X0, y + 110, 'Приют держится на пожертвованиях и волонтёрах: в месяц уходит около 900 000 ₸ на корм, лечение и аренду. Отчёты публикуем по каждому сбору.', lw, { size: 17, lh: 27 }).svg;
    // правила выгула — справа
    const rx = X0 + lw + 48;
    const rw = CW - lw - 48;
    s += rect(rx, y - 30, rw, 260, { r: 24, fill: c('ink.50') });
    s += iconLine(rx + 28, y + 12, 'leash', 'Правила выгула', { size: 17, weight: 600, fill: c('ink.900'), icFill: c('meadow.700') });
    ['Сб и вс, 10:00–16:00, до 6 человек', 'С 18 лет, с удостоверением', 'Первая прогулка — с куратором', 'Отмена не позже чем за 3 часа'].forEach((r, i) => (s += S.checkRow(rx + 28, y + 56 + i * 38, true, r)));
    y += 290;
    s += sectionHead(X0, y, CW, 'Питомцы приюта', 'Все 38', { size: 31 });
    [PETS[0], PETS[2], PETS[4], { ...PETS[1], shelter: 'Дом лап, Алматы' }].forEach((p, i) => (s += UI.petCard(X0 + i * 328, y + 36, p)));
    y += 36 + 438 + 96;
    s += sectionHead(X0, y, CW, 'Сборы', 'Все сборы приюта', { size: 31 });
    s += UI.campaignCard(X0, y + 36, { ...CAMPAIGNS[0], w: 405 });
    s += UI.campaignCard(X0 + 437, y + 36, { ...CAMPAIGNS[3], title: 'Вакцины на осень', shelter: 'Дом лап, Алматы', w: 405 });
    // ближайшие слоты
    const sx = X0 + 874;
    s += T(sx, y + 70, 'Ближайшие выгулы', { size: 17, weight: 600 });
    [['Сб, 8 ноября', 'open', '10:00–11:00', 'Осталось 3 места'], ['Сб, 8 ноября', 'full', '12:00–13:00', 'Мест нет'], ['Вс, 9 ноября', 'few', '10:00–11:00', 'Осталось 1 место']].forEach(([d, st, t, n], i) => {
      s += T(sx, y + 112 + i * 112, d, { size: 14, weight: 600 });
      s += UI.slot(sx, y + 124 + i * 112, st, t, n);
    });
    s += link(sx, y + 480, 'Все слоты');
    y += 36 + 452 + 120;
    const f = footer(DW, y);
    return { svg: s + f.svg, h: y + f.h };
  }

  function shelterMobile() {
    let s = mheader();
    let y = 64;
    s += rect(0, y, MW, 300, { fill: c('meadow.100') });
    s += iconDisc(MX + 36, y + 64, 36, 'home', c('white'), c('meadow.700'));
    s += D(MX, y + 148, 'Дом лап', 31);
    s += B(MX, y + 166, 'Проверенный приют', 'success', { icon: 'shield-check' });
    s += iconLine(MX, y + 232, 'map-pin', 'Алматы, ул. Жандосова, 186', { size: 14, is: 18, fill: c('ink.900') });
    s += iconLine(MX, y + 262, 'phone', '+7 701 234 56 78', { size: 14, is: 18, fill: c('ink.900') });
    y += 324;
    s += btn(MX, y, 'Помочь приюту', { variant: 'donate', icon: 'heart', w: MCW });
    s += btn(MX, y + 56, 'Записаться на выгул', { variant: 'secondary', icon: 'leash', w: MCW });
    y += 140;
    // вкладки прокручиваются горизонтально
    s += tabs(MX, y, ['О приюте', 'Питомцы 38', 'Сборы 2', 'Отчёты'], 1, MW);
    y += 48;
    [PETS[0], PETS[2], PETS[4], PETS[1]].forEach((p, i) => (s += petMini(MX + (i % 2) * 187, y + Math.floor(i / 2) * 290, 171, p).svg));
    y += 2 * 290;
    s += btn(MX, y, 'Все 38 питомцев', { variant: 'secondary', w: MCW });
    y += 96;
    const f = mfooter(y);
    return { svg: s + f.svg, h: y + f.h };
  }

  /* =====================================================================
   * 08. Вход и регистрация
   * ===================================================================== */
  function authAside(x, y, w, h) {
    let s = rect(x, y, w, h, { fill: c('tag.500') });
    s += `<g transform="translate(${x + w / 2 - 170} ${y + 150}) scale(1.7)"><circle cx="100" cy="100" r="96" fill="${c('tag.100')}"/><clipPath id="clip-auth"><circle cx="100" cy="100" r="96"/></clipPath><g clip-path="url(#clip-auth)"><g transform="translate(0 8)">${UI.catPortrait('ginger')}</g></g></g>`;
    s += D(x + 80, y + h - 200, 'Рыжик ждёт вас', 31);
    s += D(x + 80, y + h - 160, 'на знакомство', 31);
    s += P(x + 80, y + h - 116, 'После входа можно записываться на выгул, следить за заявками и сохранять питомцев в избранное.', w - 160, { size: 16, lh: 25, fill: c('ink.900') }).svg;
    return s;
  }

  function authDesktop(kind) {
    const H = 900;
    let s = '';
    const fx = 120;
    const fw = 440;
    s += window.KIT.logoLockup('light', fx, 48, 40 / 64);
    s += authAside(720, 0, 720, H);
    let y = 200;
    if (kind === 'login') {
      s += D(fx, y, 'Вход', 49);
      s += muted(fx, y + 40, 'Нет аккаунта?') + link(fx + measure('Нет аккаунта?', 15) + 6, y + 40, 'Зарегистрироваться', { chevron: false });
      y += 88;
      s += UI.field(fx, y, fw, { label: 'Логин или телефон', value: '+7 701 234 56 78' });
      y += 100;
      s += UI.field(fx, y, fw, { label: 'Пароль', value: '••••••••', state: 'focus' });
      s += link(fx + fw, y + 14, 'Забыли пароль?', { anchor: 'end', chevron: false, size: 14 });
      y += 100;
      s += UI.checkbox(fx, y, 'Запомнить меня', { checked: true });
      y += 52;
      s += btn(fx, y, 'Войти', { size: 'lg', w: fw });
    } else {
      s += D(fx, y - 40, 'Регистрация', 49);
      s += muted(fx, y, 'Уже есть аккаунт?') + link(fx + measure('Уже есть аккаунт?', 15) + 6, y, 'Войти', { chevron: false });
      y += 40;
      s += UI.field(fx, y, fw, { label: 'Имя', value: 'Айгерим', required: true });
      y += 96;
      s += UI.field(fx, y, fw, { label: 'Телефон', value: '+7 701 234 56', required: true, state: 'error', error: 'Не хватает цифр: нужно 10 после +7' });
      y += 104;
      s += UI.field(fx, y, fw, { label: 'Email', placeholder: 'name@mail.kz', hint: 'Для квитанций о пожертвованиях' });
      y += 104;
      s += UI.field(fx, y, fw, { label: 'Пароль', value: '•••••', required: true, state: 'error', error: 'Минимум 8 символов' });
      y += 112;
      s += UI.checkbox(fx, y, 'Согласна на обработку персональных данных', { checked: false });
      y += 48;
      s += btn(fx, y, 'Создать аккаунт', { size: 'lg', w: fw });
    }
    return { svg: s, h: H };
  }

  function authMobile() {
    let s = window.KIT.logoLockup('light', MX, 20, 32 / 64);
    let y = 120;
    s += D(MX, y, 'Вход', 31);
    s += muted(MX, y + 32, 'Нет аккаунта?', { size: 14 }) + link(MX + measure('Нет аккаунта?', 14) + 6, y + 32, 'Зарегистрироваться', { chevron: false, size: 14 });
    y += 64;
    const bn = banner(MX, y, MCW, 'danger', 'alert-circle', null, 'Неверный телефон или пароль. Осталось 3 попытки, потом вход закроется на минуту.');
    s += bn.svg;
    y += bn.h + 28;
    s += UI.field(MX, y, MCW, { label: 'Логин или телефон', value: '+7 701 234 56 78' });
    y += 100;
    s += UI.field(MX, y, MCW, { label: 'Пароль', value: '••••••••' });
    y += 92;
    s += link(MX, y, 'Забыли пароль?', { chevron: false, size: 14 });
    y += 32;
    s += btn(MX, y, 'Войти', { size: 'lg', w: MCW });
    y += 96;
    s += rect(0, y, MW, 844 - y, { fill: c('tag.500') });
    s += `<g transform="translate(${MW / 2 - 90} ${y + 28}) scale(0.9)"><circle cx="100" cy="100" r="96" fill="${c('tag.100')}"/><clipPath id="clip-auth-m"><circle cx="100" cy="100" r="96"/></clipPath><g clip-path="url(#clip-auth-m)"><g transform="translate(0 8)">${UI.catPortrait('ginger')}</g></g></g>`;
    return { svg: s, h: 844 };
  }

  window.buildScreens2 = function (screenFile) {
    screenFile('04-campaigns', 'Сборы', [
      { key: 'desktop', name: 'Сборы — desktop 1440', w: DW, build: campaignsDesktop },
      { key: 'mobile', name: 'Сборы — mobile 390', w: MW, build: campaignsMobile },
    ]);
    screenFile('05-campaign', 'Страница сбора и пожертвование', [
      { key: 'desktop', name: 'Страница сбора — desktop 1440', w: DW, build: () => campaignDesktop() },
      { key: 'mobile', name: 'Страница сбора — mobile 390', w: MW, build: campaignMobile },
      { key: 'form-errors', name: 'Форма с ошибками — desktop', w: DW, build: () => campaignDesktop({ err: true }) },
      { key: 'paid', name: 'Результат: оплачено', w: 760, build: () => paymentResult('success') },
      { key: 'pending', name: 'Результат: ожидание', w: 760, build: () => paymentResult('pending') },
      { key: 'failed', name: 'Результат: ошибка', w: 760, build: () => paymentResult('failed') },
    ]);
    screenFile('06-walks', 'Запись на выгул', [
      { key: 'desktop', name: 'Выгул, шаг 2: время — desktop 1440', w: DW, build: walksDesktop },
      { key: 'mobile', name: 'Выгул, шаг 2: время — mobile 390', w: MW, build: walksMobile },
      { key: 'confirm', name: 'Выгул, шаг 3: подтверждение — desktop', w: DW, build: walkConfirm },
      { key: 'success-mobile', name: 'Вы записаны — mobile', w: MW, build: walkSuccessMobile },
    ]);
    screenFile('07-shelter', 'Страница приюта', [
      { key: 'desktop', name: 'Приют — desktop 1440', w: DW, build: shelterDesktop },
      { key: 'mobile', name: 'Приют — mobile 390', w: MW, build: shelterMobile },
    ]);
    screenFile('08-auth', 'Вход и регистрация', [
      { key: 'login', name: 'Вход — desktop 1440', w: DW, build: () => authDesktop('login') },
      { key: 'register-errors', name: 'Регистрация с ошибками — desktop', w: DW, build: () => authDesktop('register') },
      { key: 'mobile', name: 'Вход с ошибкой — mobile 390', w: MW, build: authMobile },
    ]);
    if (window.buildScreens3) window.buildScreens3(screenFile);
  };
})();
