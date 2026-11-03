/*
 * Сборка файлов дизайн-системы: логотипы, иконки, концепт-борд, UI-кит.
 * window.buildDesign() → { 'путь/файл.svg': 'содержимое', ... } (пути относительно design/).
 */
(function () {
  const { TK, c, text, mid, rect, circle, line, icon, g, measure, para, wrap } = window.K;
  const UI = window.UI;
  const LOGO = window.LOGO; // { d, width } — контур слова VetMoment (Unbounded 600, 40px), из outline_text.py

  const fontsImport = (rel) => `<style>@import url('${rel}fonts/fonts.css');</style>`;
  const defs = () =>
    '<defs>' +
    '<filter id="shadow-md" x="-30%" y="-30%" width="160%" height="200%"><feDropShadow dx="0" dy="8" stdDeviation="9" flood-color="#1D2B4F" flood-opacity="0.16"/></filter>' +
    '<filter id="shadow-lg" x="-40%" y="-40%" width="180%" height="240%"><feDropShadow dx="0" dy="20" stdDeviation="20" flood-color="#1D2B4F" flood-opacity="0.28"/></filter>' +
    '</defs>' +
    window.iconSprite();

  const svgDoc = (w, h, inner, o = {}) =>
    `<?xml version="1.0" encoding="UTF-8"?>\n<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}">` +
    (o.title ? `<title>${o.title}</title>` : '') +
    (o.fonts !== false ? fontsImport(o.rel ?? '') : '') +
    (o.defs !== false ? defs() : '') +
    inner +
    '</svg>\n';

  /* ================= Логотип ================= */
  // Знак: круглая бирка на кольце; внутри — лапа, у которой главная подушечка — сердце.
  function markShapes(o = {}) {
    const ring = o.ring ?? c('ink.900');
    const tagFill = o.tag ?? c('tag.500');
    const paw = o.paw ?? c('ink.900');
    const hole = o.hole ?? c('white');
    return (
      `<circle cx="32" cy="43" r="28" fill="${tagFill}"/>` +
      `<circle cx="32" cy="21" r="3.4" fill="${hole}"/>` +
      `<circle cx="32" cy="11.5" r="8" fill="none" stroke="${ring}" stroke-width="3.2"/>` +
      `<path fill="${paw}" d="M32 62.5C24.6 57.4 21 53.4 21 49c0-3.3 2.5-5.8 5.6-5.8 2.4 0 4.4 1.3 5.4 3.3 1-2 3-3.3 5.4-3.3 3.1 0 5.6 2.5 5.6 5.8 0 4.4-3.6 8.4-11 13.5z"/>` +
      `<ellipse fill="${paw}" cx="19.6" cy="37.6" rx="3.5" ry="4.6" transform="rotate(-26 19.6 37.6)"/>` +
      `<ellipse fill="${paw}" cx="27.3" cy="30.6" rx="3.5" ry="4.8" transform="rotate(-8 27.3 30.6)"/>` +
      `<ellipse fill="${paw}" cx="36.7" cy="30.6" rx="3.5" ry="4.8" transform="rotate(8 36.7 30.6)"/>` +
      `<ellipse fill="${paw}" cx="44.4" cy="37.6" rx="3.5" ry="4.6" transform="rotate(26 44.4 37.6)"/>`
    );
  }

  // Горизонтальный логотип: знак 56px по высоте + слово.
  const LOCK = (() => {
    const s = 64 / 72;
    const markW = 64 * s;
    const gap = 12;
    const baseline = 43 * s + 15; // центр бирки + половина высоты прописной (30/2)
    return { s, markW, gap, baseline, w: Math.ceil(markW + gap + LOGO.width + 2), h: 64 };
  })();

  function logoLockup(theme = 'light', x = 0, y = 0, scale = 1) {
    const inv = theme === 'inverse';
    const onTag = theme === 'on-tag'; // на жёлтом фоне бирка становится белой
    const word = inv ? c('white') : c('ink.900');
    const mark = markShapes({ ring: inv ? c('white') : c('ink.900'), hole: inv ? c('ink.900') : onTag ? c('tag.500') : c('white'), tag: onTag ? c('white') : undefined });
    return (
      `<g id="logo-${theme}" transform="translate(${x} ${y}) scale(${scale})">` +
      `<g id="logo-mark" transform="scale(${LOCK.s.toFixed(4)})">${mark}</g>` +
      `<path id="logo-wordmark" fill="${word}" transform="translate(${(LOCK.markW + LOCK.gap).toFixed(2)} ${LOCK.baseline.toFixed(2)})" d="${LOGO.d}"/>` +
      '</g>'
    );
  }

  function brandFiles() {
    const files = {};
    const plain = (w, h, inner, title) =>
      `<?xml version="1.0" encoding="UTF-8"?>\n<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}"><title>${title}</title>${inner}</svg>\n`;
    files['brand/logo.svg'] = plain(LOCK.w, LOCK.h, logoLockup('light'), 'VetMoment');
    files['brand/logo-on-tag.svg'] = plain(LOCK.w, LOCK.h, logoLockup('on-tag'), 'VetMoment — для жёлтого фона');
    files['brand/logo-inverse.svg'] = plain(LOCK.w, LOCK.h, logoLockup('inverse'), 'VetMoment — для тёмного фона');
    files['brand/logo-mark.svg'] = plain(64, 72, markShapes(), 'VetMoment — знак');
    // Фавикон: только бирка с лапой, без кольца — читается на 16px.
    files['brand/favicon.svg'] = plain(
      64,
      64,
      `<circle cx="32" cy="32" r="31" fill="${c('tag.500')}"/><circle cx="32" cy="9.5" r="3.6" fill="${c('ink.900')}"/>` +
        `<g transform="translate(0 -6)">` +
        markShapes().split('/>').slice(3).join('/>') +
        `</g>`,
      'VetMoment'
    );
    return files;
  }

  function logoSheet() {
    const W = 1440;
    const H = 1000;
    let s = rect(0, 0, W, H, { fill: c('ink.50') });
    s += text(80, 110, 'Логотип VetMoment', { family: 'display', size: 39, weight: 600 });
    s += para(80, 154, 'Знак — адресная бирка на кольце ошейника. На бирке лапа, у которой главная подушечка — сердце: «найдись», «я свой», «помоги». Слово набрано Unbounded SemiBold и переведено в кривые.', 720, { size: 17, fill: c('ink.700') });
    // светлый
    s += rect(80, 240, 620, 300, { r: 24, fill: c('white'), stroke: c('ink.200') });
    s += logoLockup('light', 80 + (620 - LOCK.w * 1.3) / 2, 240 + 150 - 32 * 1.3, 1.3);
    s += text(104, 520, 'Основной, на светлом фоне', { size: 14, fill: c('ink.500') });
    // тёмный
    s += rect(740, 240, 620, 300, { r: 24, fill: c('ink.900') });
    s += logoLockup('inverse', 740 + (620 - LOCK.w * 1.3) / 2, 240 + 150 - 32 * 1.3, 1.3);
    s += text(764, 520, 'Инверсный, на ink-900', { size: 14, fill: c('ink.300') });
    // знак, фавикон, минимальные размеры
    s += rect(80, 580, 400, 340, { r: 24, fill: c('white'), stroke: c('ink.200') });
    s += `<g transform="translate(200 620) scale(2.5)">${markShapes()}</g>`;
    s += text(104, 900, 'Знак: аватар, водяной знак на фото', { size: 14, fill: c('ink.500') });
    s += rect(520, 580, 400, 340, { r: 24, fill: c('white'), stroke: c('ink.200') });
    const fav = (x, y, sz) =>
      `<g transform="translate(${x} ${y}) scale(${sz / 64})"><circle cx="32" cy="32" r="31" fill="${c('tag.500')}"/><circle cx="32" cy="9.5" r="3.6" fill="${c('ink.900')}"/><g transform="translate(0 -6)">${markShapes().split('/>').slice(3).join('/>')}</g></g>`;
    s += fav(560, 650, 128) + fav(720, 682, 64) + fav(808, 698, 32) + fav(860, 706, 16);
    s += text(560, 830, '128', { size: 13, fill: c('ink.500') }) + text(720, 830, '64', { size: 13, fill: c('ink.500') }) + text(808, 830, '32', { size: 13, fill: c('ink.500') }) + text(860, 830, '16', { size: 13, fill: c('ink.500') });
    s += text(544, 900, 'Фавикон: без кольца, отверстие — точкой', { size: 14, fill: c('ink.500') });
    s += rect(960, 580, 400, 340, { r: 24, fill: c('tag.500') });
    s += logoLockup('on-tag', 960 + (400 - LOCK.w * 0.9) / 2, 700, 0.9);
    s += text(984, 900, 'На жёлтом фоне бирка становится белой', { size: 14, fill: c('ink.900') });
    return svgDoc(W, H, s, { rel: '../', title: 'VetMoment — логотип' });
  }

  /* ================= Иконки ================= */
  function iconSheet() {
    const names = Object.keys(window.ICONS);
    const cols = 8;
    const cw = 160;
    const ch = 132;
    const W = 80 * 2 + cols * cw;
    const rows = Math.ceil(names.length / cols);
    const H = 200 + rows * ch + 60;
    let s = rect(0, 0, W, H, { fill: c('white') });
    s += text(80, 100, 'Иконки', { family: 'display', size: 39, weight: 600 });
    s += text(80, 140, 'Сетка 24 × 24, линия 2 px, скруглённые концы. Цвет наследуется (currentColor). Размеры в интерфейсе: 16, 20, 24.', { size: 16, fill: c('ink.700') });
    names.forEach((n, i) => {
      const x = 80 + (i % cols) * cw;
      const y = 180 + Math.floor(i / cols) * ch;
      s += rect(x + 8, y, cw - 16, ch - 16, { r: 16, fill: c('ink.50') });
      s += icon(n, x + cw / 2 - 16, y + 22, 32, c('ink.900'));
      s += text(x + cw / 2, y + 82, n, { size: 13, weight: 600, anchor: 'middle' });
      s += text(x + cw / 2, y + 100, window.ICONS[n][0], { size: 11, fill: c('ink.500'), anchor: 'middle' });
    });
    return svgDoc(W, H, s, { rel: '../', title: 'VetMoment — иконки' });
  }

  function iconSprite() {
    return `<?xml version="1.0" encoding="UTF-8"?>\n<svg xmlns="http://www.w3.org/2000/svg" style="display:none">${window.iconSprite().replace('<defs id="icons">', '').replace('</defs>', '')}</svg>\n`;
  }

  /* ================= Общие элементы артбордов ================= */
  const W = 1440;
  const PAD = 80;

  function sectionTitle(x, y, title, desc, width = 760) {
    let s = text(x, y, title, { family: 'display', size: 25, weight: 600 });
    if (desc) s += para(x, y + 32, desc, width, { size: 15, fill: c('ink.700'), lh: 23 });
    return s;
  }
  const caption = (x, y, str, o = {}) => text(x, y, str, { size: 13, fill: c('ink.500'), ...o });

  /** Оборачивает содержимое в артборд. Возвращает { svg, h }. */
  function artboard(id, name, y, h, inner, bg = c('white')) {
    const label = text(0, y - 20, name, { size: 18, weight: 600, fill: c('ink.700') });
    return label + `<g id="${id}" data-name="${name}" transform="translate(0 ${y})">` + rect(0, 0, W, h, { fill: bg, id: `${id}-bg` }) + inner + '</g>';
  }

  /* ---------- Артборд 1: основы ---------- */
  function abFoundations() {
    let s = '';
    let y = 100;
    s += text(PAD, y, 'Основы', { family: 'display', size: 49, weight: 600 });
    s += para(PAD, y + 44, 'Цвета, шрифты, отступы и формы, из которых собран интерфейс. Значения — в design/tokens.json, описание — в docs/design/tokens.md.', 760, { size: 17, fill: c('ink.700') });
    y += 140;

    // Палитра
    s += sectionTitle(PAD, y, 'Палитра', 'Ночная степь (ink) несёт текст и основные действия. Бирка (tag) — тёплый акцент: пожертвования, прогресс, логотип. Небо и луг обозначают пути «забрать» и «погулять».');
    y += 100;
    const ramps = [
      ['ink', 'Ночная степь', ['950', '900', '800', '700', '600', '500', '400', '300', '200', '100', '50']],
      ['tag', 'Бирка', ['700', '600', '500', '200', '100']],
      ['sky', 'Небо', ['700', '600', '500', '200', '100']],
      ['meadow', 'Луг', ['700', '600', '500', '200', '100']],
    ];
    for (const [key, label, steps] of ramps) {
      s += text(PAD, y + 18, label, { size: 15, weight: 600 });
      s += caption(PAD, y + 38, key);
      steps.forEach((st, i) => {
        const x = PAD + 170 + i * 100;
        const hex = c(`${key}.${st}`);
        s += rect(x, y, 92, 64, { r: 10, fill: hex, stroke: ['50', '100'].includes(st) ? c('ink.200') : undefined });
        s += text(x, y + 84, st, { size: 13, weight: 600 });
        s += caption(x, y + 102, hex, { size: 12 });
      });
      y += 128;
    }
    // Семантика
    s += text(PAD, y + 18, 'Состояния', { size: 15, weight: 600 });
    s += caption(PAD, y + 38, 'fg / bg / border');
    ['success', 'warning', 'danger', 'info', 'neutral'].forEach((k, i) => {
      const x = PAD + 170 + i * 220;
      s += rect(x, y, 208, 64, { r: 10, fill: c(`${k}.bg`), stroke: c(`${k}.border`) });
      s += text(x + 16, y + 28, { success: 'Успех', warning: 'Внимание', danger: 'Ошибка', info: 'Информация', neutral: 'Нейтральный' }[k], { size: 15, weight: 600, fill: c(`${k}.fg`) });
      s += text(x + 16, y + 48, c(`${k}.fg`), { size: 12, fill: c(`${k}.fg`) });
    });
    y += 150;

    // Типографика
    s += sectionTitle(PAD, y, 'Шрифты', 'Unbounded — широкий округлый гротеск для заголовков и цифр: дружелюбный, но собранный. Golos Text — текстовый гротеск, спроектированный под кириллицу: ровный, хорошо читается с телефона. Шкала 1.25 от 16 px.');
    y += 140;
    const styles = [
      ['h1', 'Найдите друга в приюте рядом', 'display', 49, 600, 'H1 · 49/55 · Unbounded 600 · моб. 31'],
      ['h2', 'Сборы этой недели', 'display', 39, 600, 'H2 · 39/44 · Unbounded 600 · моб. 25'],
      ['h3', 'Операция для Ромашки', 'display', 25, 600, 'H3 · 25/33 · Unbounded 600 · моб. 20'],
      ['h4', 'Здоровье и характер', 'display', 20, 500, 'H4 · 20/26 · Unbounded 500 · моб. 18'],
      ['lead', 'Помогите так, как вам удобно: домом, деньгами или временем.', 'body', 18, 400, 'Lead · 18/28 · Golos 400'],
      ['body', 'Бублик любит долгие прогулки и спокойно относится к кошкам. Привит, стерилизован.', 'body', 16, 400, 'Body · 16/25 · Golos 400'],
      ['small', 'Приют «Дом лап», Алматы, Бостандыкский район', 'body', 14, 400, 'Small · 14/22 · Golos 400'],
      ['label', 'Телефон для связи', 'body', 14, 600, 'Label · 14/18 · Golos 600'],
      ['caption', 'Обновлено 3 ноября в 14:00', 'body', 12, 500, 'Caption · 12/16 · Golos 500'],
      ['number', '412 500 ₸', 'display', 31, 600, 'Number · 31 · Unbounded 600 — суммы и счётчики'],
    ];
    for (const [, sample, fam, size, weight, spec] of styles) {
      s += caption(PAD, y + size * 0.8, spec, { size: 12 });
      s += text(PAD + 320, y + size * 0.8, sample, { family: fam, size, weight });
      y += Math.max(44, size * 1.3 + 14);
    }
    y += 40;

    // Отступы, радиусы, тени
    s += sectionTitle(PAD, y, 'Отступы, радиусы, тени', 'Шаг 4 px. Радиус растёт вместе с элементом. Тени тонированы синим и появляются только у того, что поднято над страницей: выпадающие списки, модальные окна, наведённая карточка.');
    y += 110;
    const spaces = [4, 8, 12, 16, 20, 24, 32, 40, 48, 64, 80, 96];
    spaces.forEach((v, i) => {
      const x = PAD + i * 70;
      s += rect(x, y + 96 - v, v > 56 ? 56 : v, v, { fill: c('tag.200') });
      s += caption(x, y + 120, `${v}`, { weight: 600, fill: c('ink.700') });
    });
    s += caption(PAD, y + 142, 'space-1 … space-24');
    const radii = [['xs', 6], ['sm', 10], ['md', 16], ['lg', 24], ['pill', 999]];
    radii.forEach(([n, r], i) => {
      const x = PAD + 880 + i * 92;
      s += rect(x, y + 16, 76, 76, { r: Math.min(r, 38), fill: c('sky.100'), stroke: c('sky.500'), sw: 1.5 });
      s += caption(x, y + 120, `${n} · ${r === 999 ? '∞' : r}`, { weight: 600, fill: c('ink.700') });
    });
    y += 180;
    [['shadow-sm', 'sm — кнопки на фото', 'none'], ['shadow-md', 'md — наведение, списки', 'url(#shadow-md)'], ['shadow-lg', 'lg — модальные окна, тосты', 'url(#shadow-lg)']].forEach(([, lbl, f], i) => {
      const x = PAD + i * 300;
      s += rect(x, y, 260, 120, { r: 24, fill: c('white'), stroke: i === 0 ? c('ink.200') : undefined, filter: f === 'none' ? undefined : f });
      s += caption(x + 20, y + 66, lbl, { weight: 600, fill: c('ink.700') });
    });
    // Фокус
    s += UI.button(PAD + 960, y + 38, 'Кольцо фокуса', { state: 'focus' });
    s += caption(PAD + 960, y + 118, 'sky-600, 3 px, отступ 2 px — у всех интерактивных элементов');
    y += 200;

    // Сетка
    s += sectionTitle(PAD, y, 'Сетка и брейкпоинты', 'Контейнер 1280 px, 12 колонок. Поля: 16 px на телефоне, 24 px на планшете, 32 px на десктопе. Макеты рисуются для 390 и 1440 px.');
    y += 96;
    for (let i = 0; i < 12; i++) s += rect(PAD + i * ((1280 - 11 * 32) / 12 + 32), y, (1280 - 11 * 32) / 12, 80, { fill: c('tag.100'), stroke: c('tag.200') });
    y += 110;
    [['sm', 640], ['md', 768], ['lg', 1024], ['xl', 1280], ['2xl', 1536]].forEach(([n, v], i) => {
      s += text(PAD + i * 200, y, n, { size: 15, weight: 600 });
      s += caption(PAD + i * 200 + 34, y, `≥ ${v} px`);
    });
    y += 60;
    return { svg: s, h: y };
  }

  /* ---------- Артборд 2: кнопки и формы ---------- */
  function abControls() {
    let s = '';
    let y = 100;
    s += text(PAD, y, 'Кнопки и формы', { family: 'display', size: 49, weight: 600 });
    y += 70;
    s += sectionTitle(PAD, y, 'Кнопки', 'Подпись говорит, что произойдёт. Жёлтая кнопка — только для пожертвований, чтобы деньги всегда выглядели одинаково. Радиус 10, высота 44 (sm 36, lg 52).');
    y += 110;
    const states = ['default', 'hover', 'active', 'focus', 'disabled', 'loading'];
    const stNames = ['Обычная', 'Наведение', 'Нажатие', 'Фокус', 'Недоступна', 'Загрузка'];
    stNames.forEach((n, i) => (s += caption(PAD + 200 + i * 190, y, n, { weight: 600, fill: c('ink.700') })));
    y += 24;
    const variants = [
      ['primary', 'Основная', 'Записаться'],
      ['donate', 'Пожертвование', 'Помочь сбору'],
      ['secondary', 'Вторичная', 'Хочу забрать'],
      ['ghost', 'Тихая', 'Подробнее'],
      ['danger', 'Опасное действие', 'Отменить запись'],
    ];
    for (const [v, n, lbl] of variants) {
      s += text(PAD, y + 28, n, { size: 15, weight: 600 });
      s += caption(PAD, y + 46, v);
      states.forEach((st, i) => (s += UI.button(PAD + 200 + i * 190, y, lbl, { variant: v, state: st, w: 164 })));
      y += 76;
    }
    y += 20;
    s += text(PAD, y + 28, 'Размеры и иконки', { size: 15, weight: 600 });
    let x = PAD + 200;
    for (const sz of ['sm', 'md', 'lg']) {
      const lbl = { sm: 'Маленькая', md: 'Средняя', lg: 'Большая' }[sz];
      s += UI.button(x, y + (52 - { sm: 36, md: 44, lg: 52 }[sz]) / 2, lbl, { size: sz });
      x += UI.button.width(lbl, { size: sz }) + 20;
    }
    s += UI.button(x, y + 4, 'Записаться на выгул', { icon: 'leash' });
    x += UI.button.width('Записаться на выгул', { icon: 'leash' }) + 20;
    s += UI.button(x, y + 4, 'Ещё питомцы', { variant: 'secondary', iconRight: 'chevron-down' });
    x += UI.button.width('Ещё питомцы', { iconRight: 'chevron-down' }) + 20;
    s += UI.button(x, y + 4, '', { variant: 'secondary', icon: 'share', iconOnly: true });
    s += UI.button(x + 56, y + 4, '', { variant: 'ghost', icon: 'heart', iconOnly: true });
    y += 76;
    s += caption(PAD + 200, y, 'Кнопка-иконка всегда с aria-label («Поделиться», «В избранное»). Минимальная зона касания — 44 × 44.');
    y += 70;

    // Поля
    s += sectionTitle(PAD, y, 'Поля ввода', 'Подпись всегда над полем, а не внутри. Граница ink-500 (контраст 5:1). Ошибка — красная граница, иконка и текст, который объясняет, как исправить.');
    y += 110;
    const fw = 300;
    s += UI.field(PAD, y, fw, { label: 'Имя', placeholder: 'Как к вам обращаться', state: 'default', hint: 'Увидит только приют' });
    s += UI.field(PAD + 340, y, fw, { label: 'Имя', placeholder: 'Как к вам обращаться', state: 'hover', hint: 'Наведение' });
    s += UI.field(PAD + 680, y, fw, { label: 'Имя', state: 'focus', hint: 'Фокус' });
    s += UI.field(PAD + 1020, y, fw, { label: 'Имя', value: 'Айгерим', state: 'filled', hint: 'Заполнено' });
    y += 120;
    s += UI.field(PAD, y, fw, { label: 'Телефон', required: true, prefix: '+7', value: '701 234 56', state: 'error', error: 'Не хватает цифр: нужно 10 после +7' });
    s += UI.field(PAD + 340, y, fw, { label: 'Электронная почта', value: 'aigerim@mail.kz', state: 'disabled', hint: 'Нельзя изменить после оплаты' });
    s += UI.field(PAD + 680, y, fw, { label: 'Вид', value: 'Собаки', select: true, hint: 'Выпадающий список' });
    s += UI.field(PAD + 1020, y, fw, { label: 'Своя сумма', value: '3 500', suffix: '₸', hint: 'От 200 до 5 000 000 ₸' });
    y += 120;
    s += UI.field(PAD, y, 640, { label: 'Поиск по кличке', icon: 'search', placeholder: 'Например, Бублик' });
    s += UI.textarea(PAD + 680, y, 640, 104, { label: 'Комментарий для приюта', value: 'Был опыт с собаками, живу в частном доме с двором. Могу приезжать по выходным.', counter: '78 / 1000', hint: 'Необязательно' });
    y += 190;

    // Выбор
    s += sectionTitle(PAD, y, 'Выбор', 'Чекбоксы, переключатели и радиокнопки. Зона клика — вся строка с подписью.');
    y += 90;
    s += UI.checkbox(PAD, y, 'Ладит с детьми');
    s += UI.checkbox(PAD, y + 40, 'Привит', { checked: true });
    s += UI.checkbox(PAD, y + 80, 'Мне есть 18 лет', { state: 'focus' });
    s += UI.checkbox(PAD, y + 120, 'Недоступно', { state: 'disabled' });
    s += UI.checkbox(PAD, y + 160, 'Недоступно, выбрано', { state: 'disabled', checked: true });
    s += UI.radio(PAD + 340, y, 'Забрать домой', { checked: true });
    s += UI.radio(PAD + 340, y + 40, 'Взять под опеку');
    s += UI.radio(PAD + 340, y + 80, 'Фокус', { state: 'focus' });
    s += UI.radio(PAD + 340, y + 120, 'Недоступно', { state: 'disabled' });
    s += UI.toggle(PAD + 680, y - 2, 'Пожертвовать анонимно', true);
    s += UI.toggle(PAD + 680, y + 38, 'Только готовые к выгулу', false);
    y += 260;

    // Чипы и суммы
    s += sectionTitle(PAD, y, 'Фильтры и суммы', 'Чипы фильтров каталога показывают число найденных питомцев. Суммы пожертвования — крупные кнопки-пресеты; выбранная обведена и подсвечена бирочным цветом.');
    y += 100;
    x = PAD;
    for (const [lbl, o] of [['Собаки', { selected: true, count: 24 }], ['Кошки', { count: 17 }], ['Щенки и котята', { count: 6 }], ['Ладит с детьми', { state: 'hover' }], ['Можно погулять', { selected: true }], ['Приют «Дом лап»', { state: 'focus' }]]) {
      const ch = UI.chip(x, y, lbl, o);
      s += ch.svg;
      x += ch.w + 12;
    }
    y += 70;
    ['1 000 ₸', '2 000 ₸', '5 000 ₸', '10 000 ₸'].forEach((a, i) => (s += UI.amount(PAD + i * 136, y, 124, a, i === 2)));
    s += UI.field(PAD + 560, y - 24, 260, { label: 'Другая сумма', placeholder: '0', suffix: '₸' });
    y += 100;
    return { svg: s, h: y };
  }

  /* ---------- Артборд 3: статусы и прогресс ---------- */
  function abStatuses() {
    let s = '';
    let y = 100;
    s += text(PAD, y, 'Статусы', { family: 'display', size: 49, weight: 600 });
    s += para(PAD, y + 44, 'Бейдж похож на бирку: слева пробито отверстие. Статус всегда передаётся текстом, цвет только помогает. Набор соответствует enum-ам из docs/product/data-requirements.md.', 820, { size: 17, fill: c('ink.700') });
    y += 140;
    const groups = [
      ['Питомец', 'PetStatus', [['Черновик', 'neutral', { dashed: true }], ['Ищет дом', 'info'], ['На лечении', 'warning'], ['Забронирован', 'neutral'], ['Нашёл дом', 'success', { icon: 'home' }], ['В архиве', 'neutral', { dashed: true }]]],
      ['Сбор', 'CampaignStatus', [['Черновик', 'neutral', { dashed: true }], ['Идёт сбор', 'tag'], ['Цель достигнута', 'success', { icon: 'check' }], ['Завершён', 'neutral'], ['В архиве', 'neutral', { dashed: true }]]],
      ['Запись на выгул', 'BookingStatus', [['Ждёт подтверждения', 'warning', { icon: 'clock' }], ['Подтверждена', 'success', { icon: 'check' }], ['Отклонена', 'danger'], ['Отменена', 'neutral'], ['Состоялся', 'walk', { icon: 'leash' }], ['Не пришёл', 'danger', { icon: 'x' }]]],
      ['Заявка на питомца', 'ApplicationStatus', [['Новая', 'info'], ['На рассмотрении', 'warning'], ['Одобрена', 'success', { icon: 'check' }], ['Отклонена', 'danger'], ['Отозвана', 'neutral'], ['Завершена', 'success', { icon: 'home' }]]],
      ['Приют', 'ShelterStatus', [['На модерации', 'warning', { icon: 'clock' }], ['Проверенный приют', 'success', { icon: 'shield-check' }], ['Отклонён', 'danger'], ['Заблокирован', 'solidDanger']]],
      ['Платёж', 'PaymentStatus', [['Ожидает оплаты', 'warning'], ['Оплачено', 'success', { icon: 'check' }], ['Ошибка оплаты', 'danger'], ['Срок оплаты истёк', 'neutral'], ['Возврат', 'info']]],
    ];
    for (const [name, en, items] of groups) {
      s += text(PAD, y + 19, name, { size: 15, weight: 600 });
      s += caption(PAD, y + 38, en);
      let x = PAD + 220;
      for (const [lbl, tone, o] of items) {
        const b = UI.badge(x, y, lbl, tone, o);
        s += b.svg;
        x += b.w + 12;
      }
      y += 64;
    }
    y += 40;
    s += sectionTitle(PAD, y, 'Слоты выгула', 'Время крупно, под ним — сколько осталось мест. Заполненный и прошедший слоты отличаются не только цветом: иконкой, текстом, пунктиром и зачёркиванием.');
    y += 100;
    const slots = [
      ['open', '10:00–11:00', 'Осталось 3 места', 'Свободен'],
      ['few', '12:00–13:00', 'Осталось 1 место', 'Мало мест'],
      ['selected', '14:00–15:00', 'Вы выбрали', 'Выбран'],
      ['focus', '16:00–17:00', 'Осталось 4 места', 'Фокус'],
      ['full', '17:00–18:00', 'Мест нет', 'Заполнен'],
      ['past', '08:00–09:00', 'Уже прошёл', 'Прошёл'],
    ];
    slots.forEach(([st, t, n, cap], i) => {
      s += UI.slot(PAD + i * 196, y, st, t, n);
      s += caption(PAD + i * 196, y + 100, cap, { weight: 600, fill: c('ink.700') });
    });
    y += 150;
    // Дни
    s += text(PAD, y + 19, 'Выбор дня', { size: 15, weight: 600 });
    const days = [['пн', '3'], ['вт', '4'], ['ср', '5'], ['чт', '6'], ['пт', '7'], ['сб', '8'], ['вс', '9']];
    days.forEach(([d, n], i) => {
      const x = PAD + 220 + i * 76;
      const sel = i === 2;
      const none = i === 4;
      s += rect(x, y - 8, 64, 72, { r: 10, fill: sel ? c('ink.900') : c('white'), stroke: sel ? undefined : c('ink.300'), sw: 1.5 });
      s += text(x + 32, y + 18, d, { size: 13, weight: 500, fill: sel ? c('ink.300') : c('ink.500'), anchor: 'middle' });
      s += text(x + 32, y + 44, n, { size: 20, weight: 600, fill: sel ? c('white') : none ? c('ink.400') : c('ink.900'), anchor: 'middle' });
      if (!none) s += circle(x + 32, y + 54, 3, { fill: sel ? c('tag.500') : c('meadow.500') });
    });
    s += caption(PAD + 220, y + 92, 'Точка — в этот день есть свободные слоты. Пятница без точки: слотов нет.');
    y += 150;

    s += sectionTitle(PAD, y, 'Прогресс сбора', 'Заливка бирочного цвета с тёмной засечкой на текущем уровне. При достижении цели полоса становится зелёной, а рядом появляется надпись «Цель достигнута».');
    y += 96;
    [[0.08, '48 000 ₸ из 600 000 ₸', '8%'], [0.69, '412 500 ₸ из 600 000 ₸', '69%'], [1, '250 000 ₸ из 250 000 ₸', 'Цель достигнута']].forEach(([r, lbl, pct], i) => {
      const x = PAD + i * 420;
      s += text(x, y, lbl, { size: 15, weight: 600 });
      s += text(x + 380, y, pct, { size: 15, weight: 600, anchor: 'end', fill: r >= 1 ? c('success.fg') : c('ink.900') });
      s += UI.progress(x, y + 14, 380, r);
    });
    y += 80;
    return { svg: s, h: y };
  }

  /* ---------- Артборд 4: карточки ---------- */
  function abCards() {
    let s = '';
    let y = 100;
    s += text(PAD, y, 'Карточки', { family: 'display', size: 49, weight: 600 });
    y += 70;
    s += sectionTitle(PAD, y, 'Карточка питомца', 'Главный объект интерфейса. Фото со статусом и кнопкой «в избранное», кличка, возраст и пол, до трёх черт характера, приют и готовность к выгулу. Вся карточка — одна ссылка.');
    y += 104;
    const pets = [
      { name: 'Бублик', meta: 'Собака, 2 года, мальчик', traits: ['спокойный', 'ладит с детьми'], shelter: 'Дом лап, Алматы', walk: true, status: ['Ищет дом', 'info'], kind: 'dog', fur: 'caramel', bg: c('sky.100'), sun: true },
      { name: 'Мята', meta: 'Кошка, 1 год, девочка', traits: ['ласковая', 'привита'], shelter: 'Мурлыка, Астана', walk: false, status: ['На лечении', 'warning'], kind: 'cat', fur: 'smoke', bg: c('tag.100'), fav: true, hover: true },
      { name: 'Граф', meta: 'Собака, 6 лет, мальчик', traits: ['активный', 'любит бегать'], shelter: 'Дом лап, Алматы', walk: true, status: ['Забронирован', 'neutral'], kind: 'dog', fur: 'graphite', bg: c('meadow.100') },
    ];
    pets.forEach((p, i) => {
      s += UI.petCard(PAD + i * 320, y, p);
      s += caption(PAD + i * 320, y + 462, ['Обычное состояние', 'Наведение: граница темнее, тень md', 'Забронирован — выгул ещё возможен'][i], { weight: 600, fill: c('ink.700') });
    });
    s += UI.skeletonCard(PAD + 960, y);
    s += caption(PAD + 960, y + 462, 'Загрузка (скелетон)', { weight: 600, fill: c('ink.700') });
    y += 520;

    s += sectionTitle(PAD, y, 'Карточка сбора', 'Сумма набрана Unbounded — это самое важное число на карточке. Срок и число помогших — рядом, чтобы было видно, что сбор живой.');
    y += 104;
    s += UI.campaignCard(PAD, y, { title: 'Операция на лапе для Ромашки', shelter: 'Дом лап, Алматы', raised: '412 500 ₸', goal: '600 000 ₸', ratio: 0.69, people: '84 человека', left: 'Ещё 12 дней', status: ['Идёт сбор', 'tag'], kind: 'dog', fur: 'caramel', bg: c('tag.100') });
    s += UI.campaignCard(PAD + 416, y, { title: 'Корм на зиму для 40 кошек', shelter: 'Мурлыка, Астана', raised: '250 000 ₸', goal: '250 000 ₸', ratio: 1, people: '131 человек', left: 'Собрано', status: ['Цель достигнута', 'success', { icon: 'check' }], kind: 'cat', fur: 'ginger', bg: c('sky.100') });

    // Карточка приюта
    const sx = PAD + 832;
    s += rect(sx, y, 408, 236, { r: 24, fill: c('white'), stroke: c('ink.200') });
    s += circle(sx + 52, y + 52, 32, { fill: c('meadow.100') });
    s += icon('home', sx + 36, y + 36, 32, c('meadow.700'));
    s += text(sx + 100, y + 46, 'Дом лап', { family: 'display', size: 20, weight: 600 });
    s += text(sx + 100, y + 70, 'Алматы, Бостандыкский район', { size: 14, fill: c('ink.700') });
    s += UI.badge(sx + 24, y + 100, 'Проверенный приют', 'success', { icon: 'shield-check' }).svg;
    let stx = sx + 24;
    [['paw', '38 питомцев'], ['tenge', '2 сбора'], ['leash', 'Выгул: сб, вс']].forEach(([ic, t]) => {
      s += icon(ic, stx, y + 152, 20, c('ink.700'));
      s += text(stx + 26, y + 167, t, { size: 14, fill: c('ink.700') });
      stx += 26 + measure(t, 14) + 24;
    });
    s += text(sx + 24, y + 210, 'Все питомцы приюта', { size: 15, weight: 600, decoration: 'underline' });
    s += caption(sx, y + 262, 'Карточка приюта', { weight: 600, fill: c('ink.700') });
    y += 500;

    // Три пути помощи
    s += sectionTitle(PAD, y, 'Три пути помощи', 'Повторяются на главной и внизу карточки питомца. Цвет пути помогает узнавать раздел: небо — забрать, бирка — пожертвовать, луг — погулять.');
    y += 104;
    const paths = [
      ['home', c('sky.100'), c('sky.700'), 'Забрать домой', 'Выберите питомца и оставьте заявку. Приют перезвонит и расскажет, как познакомиться.', 'Смотреть питомцев'],
      ['tenge', c('tag.100'), c('tag.700'), 'Пожертвовать', 'Любая сумма от 200 ₸, без регистрации. По каждому сбору приют публикует отчёт.', 'Выбрать сбор'],
      ['leash', c('meadow.100'), c('meadow.700'), 'Погулять с собакой', 'Запишитесь на свободное время в приюте рядом. Прогулка занимает около часа.', 'Найти время'],
    ];
    paths.forEach(([ic, bg, fg, title, body, cta], i) => {
      const x = PAD + i * 427;
      s += rect(x, y, 400, 284, { r: 24, fill: bg });
      s += circle(x + 56, y + 56, 28, { fill: c('white') });
      s += icon(ic, x + 42, y + 42, 28, fg);
      s += text(x + 32, y + 128, title, { family: 'display', size: 22, weight: 600 });
      s += para(x + 32, y + 160, body, 330, { size: 15, fill: c('ink.700'), lh: 23 });
      s += text(x + 32, y + 250, cta, { size: 15, weight: 600, decoration: 'underline' });
      s += icon('chevron-right', x + 36 + measure(cta, 15, 600), y + 236, 18, c('ink.900'));
    });
    y += 320;
    return { svg: s, h: y };
  }

  /* ---------- Артборд 5: навигация и обратная связь ---------- */
  function header(x, y, w, loggedIn) {
    let s = rect(x, y, w, 76, { fill: c('white') });
    s += line(x, y + 76, x + w, y + 76, { stroke: c('ink.200') });
    s += logoLockup('light', x + 32, y + 18, 40 / 64);
    const nav = ['Питомцы', 'Сборы', 'Выгул', 'Приюты', 'Как помочь'];
    let nx = x + 300;
    nav.forEach((n, i) => {
      const active = i === 0;
      s += text(nx, y + 44, n, { size: 16, weight: active ? 600 : 500, fill: active ? c('ink.900') : c('ink.700') });
      if (active) s += rect(nx, y + 72, measure(n, 16, 600), 4, { r: 2, fill: c('tag.500') });
      nx += measure(n, 16, 600) + 32;
    });
    if (loggedIn) {
      s += circle(x + w - 172, y + 38, 22, { fill: c('ink.50') });
      s += icon('bell', x + w - 184, y + 26, 24, c('ink.900'));
      s += circle(x + w - 160, y + 26, 9, { fill: c('danger.solid') });
      s += text(x + w - 160, y + 30.5, '2', { size: 11, weight: 700, fill: c('white'), anchor: 'middle' });
      s += circle(x + w - 116, y + 38, 20, { fill: c('sky.200') });
      s += text(x + w - 116, y + 43, 'АС', { size: 13, weight: 700, fill: c('sky.700'), anchor: 'middle' });
      s += text(x + w - 88, y + 43, 'Айгерим', { size: 15, weight: 500 });
    } else {
      s += UI.button(x + w - 32 - 136 - 12 - 82, y + 16, 'Войти', { variant: 'ghost' });
      s += UI.button(x + w - 32 - 136, y + 16, 'Помочь', { variant: 'donate', icon: 'heart', w: 136 });
    }
    return s;
  }

  function mobileHeader(x, y, w) {
    let s = rect(x, y, w, 64, { fill: c('white') });
    s += line(x, y + 64, x + w, y + 64, { stroke: c('ink.200') });
    s += logoLockup('light', x + 16, y + 14, 36 / 64);
    s += UI.button(x + w - 16 - 44, y + 10, '', { variant: 'ghost', icon: 'menu', iconOnly: true });
    s += UI.button(x + w - 16 - 44 - 52, y + 10, '', { variant: 'ghost', icon: 'search', iconOnly: true });
    return s;
  }

  function mobileMenu(x, y, w, h) {
    let s = rect(x, y, w, h, { fill: c('white') });
    s += logoLockup('light', x + 16, y + 14, 36 / 64);
    s += UI.button(x + w - 60, y + 10, '', { variant: 'ghost', icon: 'x', iconOnly: true });
    let yy = y + 104;
    [['paw', 'Питомцы'], ['tenge', 'Сборы'], ['leash', 'Выгул'], ['home', 'Приюты'], ['heart', 'Как помочь']].forEach(([ic, n], i) => {
      s += icon(ic, x + 20, yy - 20, 24, c('ink.700'));
      s += text(x + 56, yy, n, { family: 'display', size: 20, weight: 500 });
      s += line(x + 16, yy + 22, x + w - 16, yy + 22, { stroke: c('ink.200') });
      yy += 60;
    });
    yy += 10;
    s += UI.button(x + 16, yy, 'Помочь', { variant: 'donate', icon: 'heart', w: w - 32, size: 'lg' });
    s += UI.button(x + 16, yy + 64, 'Войти', { variant: 'secondary', w: w - 32, size: 'lg' });
    return s;
  }

  function abNavigation() {
    let s = '';
    let y = 100;
    s += text(PAD, y, 'Навигация и отклик', { family: 'display', size: 49, weight: 600 });
    y += 70;
    s += sectionTitle(PAD, y, 'Шапка', 'Пять разделов, активный подчёркнут бирочной полосой. Справа — вход и жёлтая «Помочь»; после входа — уведомления со счётчиком и профиль.');
    y += 96;
    s += rect(PAD - 1, y - 1, 1282, 78, { r: 0, stroke: c('ink.200') });
    s += header(PAD, y, 1280, false);
    y += 110;
    s += rect(PAD - 1, y - 1, 1282, 78, { stroke: c('ink.200') });
    s += header(PAD, y, 1280, true);
    y += 130;
    // мобильные
    s += text(PAD, y, 'Телефон, 390 px', { size: 15, weight: 600 });
    y += 20;
    s += rect(PAD, y, 390, 560, { r: 24, fill: c('ink.50'), stroke: c('ink.200') });
    s += `<clipPath id="clip-m1"><rect x="${PAD}" y="${y}" width="390" height="560" rx="24"/></clipPath><g clip-path="url(#clip-m1)">${mobileHeader(PAD, y, 390)}`;
    s += UI.petPhoto(PAD + 16, y + 84, 358, 200, { kind: 'dog', fur: 'caramel', bg: c('sky.100') });
    s += text(PAD + 16, y + 324, 'Бублик', { family: 'display', size: 25, weight: 600 });
    s += para(PAD + 16, y + 354, 'Собака, 2 года. Любит долгие прогулки и спокойно относится к кошкам.', 340, { size: 15, fill: c('ink.700'), lh: 23 });
    // липкая панель CTA
    s += rect(PAD, y + 480, 390, 80, { fill: c('white') });
    s += line(PAD, y + 480, PAD + 390, y + 480, { stroke: c('ink.200') });
    s += UI.button(PAD + 16, y + 498, 'Погулять', { icon: 'leash', w: 170 });
    s += UI.button(PAD + 196, y + 498, 'Хочу забрать', { variant: 'secondary', w: 178 });
    s += '</g>';
    s += caption(PAD, y + 590, 'Липкая панель действий на карточке питомца', { weight: 600, fill: c('ink.700') });
    s += `<clipPath id="clip-m2"><rect x="${PAD + 430}" y="${y}" width="390" height="560" rx="24"/></clipPath><g clip-path="url(#clip-m2)">`;
    s += mobileMenu(PAD + 430, y, 390, 560) + '</g>';
    s += rect(PAD + 430, y, 390, 560, { r: 24, stroke: c('ink.200') });
    s += caption(PAD + 430, y + 590, 'Открытое меню: полноэкранный лист', { weight: 600, fill: c('ink.700') });

    // хлебные крошки, пагинация, вкладки — справа
    let rx = PAD + 880;
    let ry = y + 10;
    s += text(rx, ry, 'Хлебные крошки', { size: 15, weight: 600 });
    ry += 36;
    let bx = rx;
    ['Главная', 'Питомцы'].forEach((b) => {
      s += text(bx, ry, b, { size: 14, fill: c('ink.700'), decoration: 'underline' });
      bx += measure(b, 14) + 6;
      s += icon('chevron-right', bx, ry - 13, 16, c('ink.400'));
      bx += 22;
    });
    s += text(bx, ry, 'Бублик', { size: 14, weight: 600 });
    ry += 70;
    s += text(rx, ry, 'Пагинация', { size: 15, weight: 600 });
    ry += 20;
    s += UI.button(rx, ry, '', { variant: 'secondary', icon: 'chevron-left', iconOnly: true, size: 'md' });
    ['1', '2', '3', '…', '9'].forEach((p, i) => {
      const px = rx + 54 + i * 50;
      const cur = p === '2';
      if (p !== '…') s += rect(px, ry, 44, 44, { r: 10, fill: cur ? c('ink.900') : c('white'), stroke: cur ? undefined : c('ink.300'), sw: 1.5 });
      s += text(px + 22, mid(ry, 44, 16), p, { size: 16, weight: 600, fill: cur ? c('white') : c('ink.900'), anchor: 'middle' });
    });
    s += UI.button(rx + 54 + 5 * 50, ry, '', { variant: 'secondary', icon: 'chevron-right', iconOnly: true });
    ry += 100;
    s += text(rx, ry, 'Вкладки', { size: 15, weight: 600 });
    ry += 36;
    let tx = rx;
    ['Предстоящие', 'Прошедшие', 'Отменённые'].forEach((t, i) => {
      const tw = measure(t, 15, 600);
      s += text(tx, ry, t, { size: 15, weight: 600, fill: i === 0 ? c('ink.900') : c('ink.500') });
      if (i === 0) s += rect(tx, ry + 13, tw, 3, { r: 1.5, fill: c('ink.900') });
      tx += tw + 28;
    });
    s += line(rx, ry + 16, rx + 400, ry + 16, { stroke: c('ink.200') });
    ry += 80;
    s += text(rx, ry, 'Аватар', { size: 15, weight: 600 });
    ry += 20;
    [['АС', 'sky'], ['ЕК', 'meadow'], ['ДЛ', 'tag']].forEach(([ini, tone], i) => {
      s += circle(rx + 24 + i * 60, ry + 24, 24, { fill: c(`${tone}.200`) });
      s += text(rx + 24 + i * 60, ry + 29.5, ini, { size: 15, weight: 700, fill: c(`${tone}.700`), anchor: 'middle' });
    });
    y += 660;

    // Модальное окно и тосты
    s += sectionTitle(PAD, y, 'Модальное окно и уведомления', 'Модальное окно — только для подтверждения необратимых действий. Тосты сообщают результат и тем же глаголом, что был на кнопке.');
    y += 96;
    s += rect(PAD, y, 700, 400, { r: 24, fill: c('ink.950'), opacity: 0.55 });
    const mx = PAD + 90;
    const my = y + 56;
    s += rect(mx, my, 520, 288, { r: 24, fill: c('white'), filter: 'url(#shadow-lg)' });
    s += text(mx + 32, my + 52, 'Отменить запись на выгул?', { family: 'display', size: 20, weight: 600 });
    s += UI.button(mx + 520 - 60, my + 20, '', { variant: 'ghost', icon: 'x', iconOnly: true, size: 'sm' });
    s += para(mx + 32, my + 92, 'Суббота, 8 ноября, 10:00–11:00, Бублик. Приют освободит место для другого волонтёра. Отменить можно не позже чем за 3 часа.', 456, { size: 15, fill: c('ink.700'), lh: 23 });
    s += UI.button(mx + 520 - 32 - 186, my + 212, 'Отменить запись', { variant: 'danger', w: 186 });
    s += UI.button(mx + 520 - 32 - 186 - 12 - 160, my + 212, 'Оставить', { variant: 'secondary', w: 160 });

    const toast = (tx2, ty, kind, title, body) => {
      const tone = { success: ['check-circle', c('success.fg'), c('success.bg')], error: ['alert-circle', c('danger.fg'), c('danger.bg')], info: ['info', c('info.fg'), c('info.bg')] }[kind];
      let t = rect(tx2, ty, 520, 104, { r: 16, fill: c('white'), stroke: c('ink.200'), filter: 'url(#shadow-lg)' });
      t += circle(tx2 + 40, ty + 40, 20, { fill: tone[2] });
      t += icon(tone[0], tx2 + 28, ty + 28, 24, tone[1]);
      t += text(tx2 + 76, ty + 36, title, { size: 16, weight: 600 });
      t += para(tx2 + 76, ty + 60, body, 380, { size: 14, fill: c('ink.700'), lh: 21 });
      t += icon('x', tx2 + 484, ty + 18, 20, c('ink.500'));
      return t;
    };
    s += toast(PAD + 760, y, 'success', 'Вы записаны на выгул', 'Суббота, 10:00. Приют подтвердит запись до пятницы, 18:00.');
    s += toast(PAD + 760, y + 136, 'error', 'Платёж не прошёл', 'Банк отклонил карту. Проверьте данные или выберите другую карту.');
    s += toast(PAD + 760, y + 272, 'info', 'Заявка отправлена', 'Приют «Дом лап» свяжется с вами по телефону в течение двух дней.');
    y += 460;

    // Пустое состояние
    s += sectionTitle(PAD, y, 'Пустое состояние', 'Пустой экран подсказывает следующий шаг, а не грустит.');
    y += 80;
    s += rect(PAD, y, 1280, 340, { r: 24, fill: c('ink.50') });
    s += `<g transform="translate(${PAD + 560} ${y + 36}) scale(0.8)">` + `<circle cx="100" cy="100" r="92" fill="${c('meadow.100')}"/>` + `<g transform="translate(0 6)">${UI.dogPortrait('caramel')}</g></g>`;
    s += text(PAD + 640, y + 248, 'Вы ещё не записывались на выгул', { family: 'display', size: 20, weight: 600, anchor: 'middle' });
    s += text(PAD + 640, y + 278, 'Выберите приют и удобное время. Первая прогулка занимает около часа.', { size: 15, fill: c('ink.700'), anchor: 'middle' });
    s += UI.button(PAD + 640 - 80, y + 300, 'Найти время', { w: 160, size: 'sm' });
    y += 400;
    return { svg: s, h: y };
  }

  function uiKit() {
    const boards = [
      ['artboard-01-foundations', '01 · Основы: цвет, шрифт, отступы', abFoundations],
      ['artboard-02-controls', '02 · Кнопки и формы', abControls],
      ['artboard-03-statuses', '03 · Статусы, слоты, прогресс', abStatuses],
      ['artboard-04-cards', '04 · Карточки', abCards],
      ['artboard-05-navigation', '05 · Навигация и отклик', abNavigation],
    ];
    let y = 80;
    let inner = '';
    const meta = [];
    for (const [id, name, fn] of boards) {
      const b = fn();
      inner += artboard(id, name, y, b.h, b.svg);
      meta.push({ id, y, h: b.h });
      y += b.h + 140;
    }
    const H = y - 60;
    const bg = rect(0, 0, W, H, { fill: '#E4E8EF', id: 'canvas' });
    return { svg: svgDoc(W, H, bg + inner, { title: 'VetMoment — UI-кит' }), meta };
  }

  /* ================= Концепт-борд ================= */
  function concept() {
    const CW = 1440;
    const CH = 1260;
    let s = rect(0, 0, CW, CH, { fill: c('white') });
    // Левая колонка: суть
    s += logoLockup('light', 80, 72, 0.8);
    s += text(80, 200, 'Бирка', { family: 'display', size: 61, weight: 600 });
    s += text(80, 268, 'на ошейнике', { family: 'display', size: 61, weight: 600 });
    s += para(80, 322, 'Адресная бирка — то, что отличает «своего» пса от потерявшегося. Мы делаем такую бирку для каждого животного из приюта: имя, характер, кому позвонить. Отсюда форма знака, бейджей и тёплое золото акцента.', 600, { size: 18, fill: c('ink.700'), lh: 28 });
    // Палитра крупными полосами
    const sw = [
      ['ink.900', 'Ночная степь', 'текст, действия', c('white')],
      ['tag.500', 'Бирка', 'акцент, деньги', c('ink.900')],
      ['sky.100', 'Небо', 'забрать домой', c('ink.900')],
      ['meadow.100', 'Луг', 'погулять', c('ink.900')],
      ['ink.50', 'Снег', 'фон секций', c('ink.900')],
    ];
    sw.forEach(([k, n, role, fg], i) => {
      const x = 80 + i * 124;
      s += rect(x, 440, 116, 160, { r: 16, fill: c(k), stroke: k === 'ink.50' ? c('ink.200') : undefined });
      s += text(x + 14, 550, n, { size: 14, weight: 600, fill: fg });
      s += text(x + 14, 570, role, { size: 12, fill: fg, opacity: 0.85 });
      s += text(x + 14, 588, c(k), { size: 11, fill: fg, opacity: 0.7 });
    });
    // Шрифтовая пара
    s += text(80, 730, 'Аа', { family: 'display', size: 96, weight: 600 });
    s += text(260, 700, 'Unbounded', { size: 18, weight: 600 });
    s += text(260, 726, 'заголовки и суммы', { size: 15, fill: c('ink.500') });
    s += text(80, 850, 'Аа', { size: 96, weight: 500 });
    s += text(260, 820, 'Golos Text', { size: 18, weight: 600 });
    s += text(260, 846, 'текст и интерфейс', { size: 15, fill: c('ink.500') });
    // Принципы
    const pr = [
      ['Конкретный питомец, а не «животные»', 'Кличка, характер и лицо — в каждой карточке, сборе и записи.'],
      ['Деньги всегда жёлтые', 'Цвет бирки закреплён за пожертвованиями и прогрессом.'],
      ['Статус — словами', 'Цвет только помогает, текст и форма бейджа говорят сами.'],
    ];
    pr.forEach(([t, d], i) => {
      const y = 950 + i * 92;
      s += circle(92, y - 6, 12, { fill: c('tag.500') });
      s += circle(92, y - 12, 2.5, { fill: c('ink.900') });
      s += text(120, y, t, { size: 18, weight: 600 });
      s += text(120, y + 26, d, { size: 15, fill: c('ink.700') });
    });
    // Правая колонка: композиция из компонентов
    s += rect(760, 0, 680, CH, { fill: c('ink.50') });
    s += UI.petCard(800, 80, { name: 'Бублик', meta: 'Собака, 2 года, мальчик', traits: ['спокойный', 'ладит с детьми'], shelter: 'Дом лап, Алматы', walk: true, status: ['Ищет дом', 'info'], kind: 'dog', fur: 'caramel', bg: c('sky.100'), sun: true });
    s += UI.petCard(1112, 160, { name: 'Мята', meta: 'Кошка, 1 год, девочка', traits: ['ласковая', 'привита'], shelter: 'Мурлыка, Астана', walk: false, status: ['На лечении', 'warning'], kind: 'cat', fur: 'smoke', bg: c('tag.100'), fav: true });
    // мини-блок сбора
    s += rect(800, 640, 600, 200, { r: 24, fill: c('white'), stroke: c('ink.200') });
    s += text(828, 688, 'Операция на лапе для Ромашки', { family: 'display', size: 19, weight: 600 });
    s += text(828, 732, '412 500 ₸', { family: 'display', size: 31, weight: 600 });
    s += text(828 + measure('412 500 ₸', 31, 600, 'display') + 10, 732, 'из 600 000 ₸', { size: 15, fill: c('ink.700') });
    s += UI.progress(828, 750, 544, 0.69);
    s += UI.button(828, 780, 'Помочь сбору', { variant: 'donate', icon: 'heart', size: 'sm' });
    s += text(1372, 804, 'Ещё 12 дней', { size: 14, fill: c('ink.700'), anchor: 'end' });
    // слоты
    ['open', 'selected', 'full'].forEach((st, i) => (s += UI.slot(800 + i * 186, 880, st, ['10:00–11:00', '14:00–15:00', '17:00–18:00'][i], ['Осталось 3 места', 'Вы выбрали', 'Мест нет'][i])));
    // бейджи
    let bx = 800;
    for (const [l, t, o] of [['Ищет дом', 'info'], ['Идёт сбор', 'tag'], ['Подтверждена', 'success', { icon: 'check' }], ['Ждёт подтверждения', 'warning', { icon: 'clock' }]]) {
      const b = UI.badge(bx, 996, l, t, o);
      s += b.svg;
      bx += b.w + 10;
    }
    // иконки
    ['paw', 'heart', 'home', 'leash', 'tenge', 'calendar', 'shield-check', 'syringe', 'cat', 'dog'].forEach((n, i) => {
      s += rect(800 + i * 60, 1064, 48, 48, { r: 12, fill: c('white'), stroke: c('ink.200') });
      s += icon(n, 812 + i * 60, 1076, 24, c('ink.900'));
    });
    s += text(800, 1170, 'Иллюстрации — геометричные портреты без контура, у каждого на шее ошейник с биркой.', { size: 14, fill: c('ink.500') });
    s += text(800, 1192, 'Ими закрываем места без фото: пустые состояния, заглушки, ошибки 404.', { size: 14, fill: c('ink.500') });
    return svgDoc(CW, CH, `<g id="artboard-concept" data-name="Концепция">${s}</g>`, { title: 'VetMoment — концепция «Бирка»' });
  }

  // Экспорт для генератора экранов (screens.js).
  window.KIT = { logoLockup, svgDoc, LOCK };

  window.buildDesign = function () {
    const files = { ...brandFiles() };
    files['brand/logo-sheet.svg'] = logoSheet();
    files['icons/icons.svg'] = iconSprite();
    files['icons/icon-sheet.svg'] = iconSheet();
    files['concept.svg'] = concept();
    const kit = uiKit();
    files['ui-kit.svg'] = kit.svg;
    const screens = window.buildScreens ? window.buildScreens() : { files: {}, artboards: [] };
    Object.assign(files, screens.files);
    return { files, artboards: kit.meta, screens: screens.artboards };
  };
})();
