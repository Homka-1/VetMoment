/*
 * Компоненты UI-кита VetMoment в виде функций, возвращающих SVG-фрагменты.
 * Каждая функция рисует компонент в точке (x, y) и возвращает строку SVG.
 * Те же размеры и цвета будут перенесены в Blade-компоненты на этапе 7.
 */
(function () {
  const { c, text, mid, rect, circle, line, icon, g, measure, para, wrap, esc } = window.K;

  /* ---------- Иллюстрации-«портреты» (заглушки фото) ---------- */
  // Геометричные портреты из простых фигур, без контура. На шее — ошейник с биркой (мотив айдентики).
  const FUR = {
    caramel: '#D39A5B',
    caramelDark: '#A86B34',
    cream: '#F1DDBF',
    graphite: '#3B3330',
    smoke: '#9AA1AB',
    smokeDark: '#6E7581',
    ginger: '#E08A3C',
    white: '#FFFFFF',
  };

  function collar(cx, y, w) {
    return (
      rect(cx - w / 2, y, w, 12, { r: 6, fill: c('ink.900') }) +
      circle(cx, y + 26, 13, { fill: c('tag.500') }) +
      circle(cx, y + 17, 2.4, { fill: c('ink.900') }) +
      circle(cx, y + 12, 5.5, { stroke: c('ink.900'), sw: 2.5 })
    );
  }

  /** Собака: висячие уши, светлая морда. Координаты в поле 200×200. */
  function dogPortrait(palette = 'caramel') {
    const base = palette === 'graphite' ? FUR.graphite : FUR.caramel;
    const ear = palette === 'graphite' ? '#221C1A' : FUR.caramelDark;
    const muzzle = palette === 'graphite' ? '#D8D2CC' : FUR.cream;
    return (
      `<path d="M42 210 C46 160 66 150 100 150 C134 150 154 160 158 210 Z" fill="${base}"/>` +
      collar(100, 150, 88) +
      `<ellipse cx="100" cy="98" rx="50" ry="54" fill="${base}"/>` +
      `<ellipse cx="54" cy="96" rx="17" ry="38" transform="rotate(14 54 96)" fill="${ear}"/>` +
      `<ellipse cx="146" cy="96" rx="17" ry="38" transform="rotate(-14 146 96)" fill="${ear}"/>` +
      `<ellipse cx="100" cy="126" rx="30" ry="24" fill="${muzzle}"/>` +
      `<path d="M100 60 C92 76 92 96 100 108 C108 96 108 76 100 60Z" fill="${muzzle}" opacity=".9"/>` +
      `<ellipse cx="100" cy="114" rx="10" ry="7" fill="#221C1A"/>` +
      `<path d="M100 121 V130 M90 132 Q100 140 110 132" stroke="#221C1A" stroke-width="3" fill="none" stroke-linecap="round"/>` +
      circle(80, 92, 5.5, { fill: '#221C1A' }) +
      circle(120, 92, 5.5, { fill: '#221C1A' }) +
      circle(81.8, 90.2, 1.6, { fill: '#fff' }) +
      circle(121.8, 90.2, 1.6, { fill: '#fff' })
    );
  }

  /** Кошка: треугольные уши, крупные глаза цвета «бирки». */
  function catPortrait(palette = 'smoke') {
    const base = palette === 'ginger' ? FUR.ginger : FUR.smoke;
    const dark = palette === 'ginger' ? '#C9692A' : FUR.smokeDark;
    const light = palette === 'ginger' ? '#F7D9B5' : '#E4E7EC';
    return (
      `<path d="M50 210 C54 162 72 152 100 152 C128 152 146 162 150 210 Z" fill="${base}"/>` +
      collar(100, 150, 80) +
      `<path d="M52 92 L58 38 L94 66 Z" fill="${base}"/><path d="M148 92 L142 38 L106 66 Z" fill="${base}"/>` +
      `<path d="M61 78 L64 52 L84 68 Z" fill="${light}"/><path d="M139 78 L136 52 L116 68 Z" fill="${light}"/>` +
      `<ellipse cx="100" cy="104" rx="54" ry="48" fill="${base}"/>` +
      `<path d="M88 62 Q100 72 112 62 M84 72 Q100 84 116 72" stroke="${dark}" stroke-width="4" fill="none" stroke-linecap="round"/>` +
      `<ellipse cx="100" cy="128" rx="26" ry="18" fill="${light}"/>` +
      `<ellipse cx="78" cy="102" rx="11" ry="9" fill="${c('tag.500')}"/><ellipse cx="122" cy="102" rx="11" ry="9" fill="${c('tag.500')}"/>` +
      `<ellipse cx="78" cy="102" rx="3" ry="8" fill="#221C1A"/><ellipse cx="122" cy="102" rx="3" ry="8" fill="#221C1A"/>` +
      `<path d="M94 118 H106 L100 125 Z" fill="#B85C64"/>` +
      `<path d="M100 125 V131 M100 131 Q94 136 89 133 M100 131 Q106 136 111 133" stroke="#221C1A" stroke-width="2.5" fill="none" stroke-linecap="round"/>` +
      `<path d="M64 120 H38 M64 128 L40 136 M136 120 H162 M136 128 L160 136" stroke="#fff" stroke-width="2" stroke-linecap="round" opacity=".8"/>`
    );
  }

  /** «Фото» питомца: фон-подложка + портрет, обрезанные скруглённым прямоугольником. */
  let clipSeq = 0;
  function petPhoto(x, y, w, h, o = {}) {
    const id = `clip-photo-${++clipSeq}`;
    const s = (Math.min(w, h * 1.05) / 200) * (o.zoom ?? 1);
    const px = x + (w - 200 * s) / 2;
    const py = y + h - 200 * s + (o.dy ?? 0);
    const art = o.kind === 'cat' ? catPortrait(o.fur) : dogPortrait(o.fur);
    return (
      `<clipPath id="${id}">${rect(x, y, w, h, { r: o.r ?? 16 })}</clipPath>` +
      `<g clip-path="url(#${id})">` +
      rect(x, y, w, h, { fill: o.bg ?? c('sky.100') }) +
      (o.sun ? circle(x + w * 0.8, y + h * 0.22, h * 0.14, { fill: c('tag.200') }) : '') +
      `<g transform="translate(${px.toFixed(2)} ${py.toFixed(2)}) scale(${s.toFixed(4)})">${art}</g>` +
      (o.dim ? rect(x, y, w, h, { fill: '#FFFFFF', opacity: 0.45 }) : '') +
      '</g>'
    );
  }

  /* ---------- Кнопки ---------- */
  const BTN = {
    primary: {
      default: { bg: c('ink.900'), fg: c('white') },
      hover: { bg: c('ink.800'), fg: c('white') },
      active: { bg: c('ink.950'), fg: c('white') },
      disabled: { bg: c('ink.200'), fg: c('ink.400') },
    },
    donate: {
      default: { bg: c('tag.500'), fg: c('ink.900') },
      hover: { bg: c('tag.600'), fg: c('ink.900') },
      active: { bg: '#C98F0A', fg: c('ink.900') },
      disabled: { bg: c('ink.200'), fg: c('ink.400') },
    },
    secondary: {
      default: { bg: c('white'), fg: c('ink.900'), border: c('ink.900') },
      hover: { bg: c('ink.50'), fg: c('ink.900'), border: c('ink.900') },
      active: { bg: c('ink.100'), fg: c('ink.900'), border: c('ink.900') },
      disabled: { bg: c('white'), fg: c('ink.400'), border: c('ink.300') },
    },
    ghost: {
      default: { bg: 'none', fg: c('ink.900') },
      hover: { bg: c('ink.100'), fg: c('ink.900') },
      active: { bg: c('ink.200'), fg: c('ink.900') },
      disabled: { bg: 'none', fg: c('ink.400') },
    },
    danger: {
      default: { bg: c('danger.solid'), fg: c('white') },
      hover: { bg: '#A82E25', fg: c('white') },
      active: { bg: '#8F271F', fg: c('white') },
      disabled: { bg: c('ink.200'), fg: c('ink.400') },
    },
  };
  const BTN_SIZE = { sm: { h: 36, px: 14, fs: 14, ic: 16 }, md: { h: 44, px: 20, fs: 16, ic: 20 }, lg: { h: 52, px: 24, fs: 17, ic: 20 } };

  function focusRing(x, y, w, h, r) {
    return rect(x - 4.5, y - 4.5, w + 9, h + 9, { r: r + 4.5, stroke: c('sky.600'), sw: 3 });
  }

  function spinner(cx, cy, r, color) {
    return (
      circle(cx, cy, r, { stroke: color, sw: 2.5, opacity: 0.3 }) +
      `<path d="M${cx} ${cy - r} A${r} ${r} 0 0 1 ${cx + r} ${cy}" stroke="${color}" stroke-width="2.5" fill="none" stroke-linecap="round"/>`
    );
  }

  /** Кнопка. o: variant, state (default|hover|active|focus|disabled|loading), size, icon, iconRight, w (фикс. ширина), iconOnly */
  function button(x, y, label, o = {}) {
    const v = o.variant ?? 'primary';
    const st = o.state ?? 'default';
    const sz = BTN_SIZE[o.size ?? 'md'];
    const sty = BTN[v][st === 'focus' || st === 'loading' ? 'default' : st];
    const tw = label ? measure(label, sz.fs, 600) : 0;
    const iw = o.icon || o.iconRight ? sz.ic + (label ? 8 : 0) : 0;
    const w = o.w ?? (o.iconOnly ? sz.h : tw + iw + sz.px * 2);
    const h = sz.h;
    const r = 10;
    let s = '';
    if (st === 'focus') s += focusRing(x, y, w, h, r);
    s += rect(x, y, w, h, {
      r,
      fill: sty.bg,
      stroke: sty.border,
      sw: sty.border ? 1.5 : undefined,
    });
    const contentW = o.iconOnly ? sz.ic : tw + iw;
    let cx = x + (w - contentW) / 2;
    if (st === 'loading') {
      s += spinner(x + w / 2, y + h / 2, 8, sty.fg);
      return g(null, s);
    }
    if (o.icon) {
      s += icon(o.icon, cx, y + (h - sz.ic) / 2, sz.ic, sty.fg);
      cx += sz.ic + (label ? 8 : 0);
    }
    if (label) {
      s += text(cx, mid(y, h, sz.fs), label, { size: sz.fs, weight: 600, fill: sty.fg });
      cx += tw + 8;
    }
    if (o.iconRight) s += icon(o.iconRight, cx, y + (h - sz.ic) / 2, sz.ic, sty.fg);
    return g(null, s);
  }
  button.width = (label, o = {}) => {
    const sz = BTN_SIZE[o.size ?? 'md'];
    const iw = o.icon || o.iconRight ? sz.ic + 8 : 0;
    return measure(label, sz.fs, 600) + iw + sz.px * 2;
  };

  /* ---------- Бейджи статусов (мотив «бирки»: пробитое отверстие слева) ---------- */
  const TONE = {
    neutral: { bg: c('neutral.bg'), fg: c('neutral.fg'), border: c('neutral.border') },
    info: { bg: c('info.bg'), fg: c('info.fg'), border: c('info.border') },
    success: { bg: c('success.bg'), fg: c('success.fg'), border: c('success.border') },
    warning: { bg: c('warning.bg'), fg: c('warning.fg'), border: c('warning.border') },
    danger: { bg: c('danger.bg'), fg: c('danger.fg'), border: c('danger.border') },
    tag: { bg: c('tag.500'), fg: c('ink.900'), border: c('tag.600') },
    walk: { bg: c('meadow.100'), fg: c('meadow.700'), border: c('meadow.200') },
    solidDanger: { bg: c('danger.solid'), fg: c('white'), border: c('danger.solid') },
  };

  function badge(x, y, label, tone = 'neutral', o = {}) {
    const t = TONE[tone];
    const fs = o.size === 'sm' ? 12 : 13;
    const h = o.size === 'sm' ? 24 : 28;
    const ic = o.icon ? 14 + 5 : 0;
    const w = 22 + ic + measure(label, fs, 600) + 12;
    let s = rect(x, y, w, h, { r: h / 2, fill: t.bg, stroke: o.dashed ? t.border : undefined, sw: o.dashed ? 1.5 : undefined, dash: o.dashed ? '4 3' : undefined });
    // отверстие бирки
    s += circle(x + 11, y + h / 2, 3, { fill: o.holeFill ?? c('white'), stroke: t.fg, sw: 1.5 });
    let tx = x + 21;
    if (o.icon) {
      s += icon(o.icon, tx, y + (h - 14) / 2, 14, t.fg);
      tx += 19;
    }
    s += text(tx, mid(y, h, fs), label, { size: fs, weight: 600, fill: t.fg });
    return { svg: g(null, s), w };
  }

  /* ---------- Поля форм ---------- */
  function field(x, y, w, o = {}) {
    const st = o.state ?? 'default';
    const h = o.h ?? 48;
    let s = '';
    let yy = y;
    if (o.label) {
      s += text(x, yy + 14, o.label, { size: 14, weight: 600, fill: st === 'disabled' ? c('ink.400') : c('ink.900') });
      if (o.required) s += text(x + measure(o.label, 14, 600) + 4, yy + 14, '*', { size: 14, weight: 600, fill: c('danger.fg') });
      yy += 24;
    }
    const border = { default: c('ink.500'), hover: c('ink.900'), focus: c('ink.900'), filled: c('ink.500'), error: c('danger.solid'), disabled: c('ink.200') }[st];
    if (st === 'focus') s += focusRing(x, yy, w, h, 10);
    s += rect(x, yy, w, h, { r: 10, fill: st === 'disabled' ? c('ink.100') : c('white'), stroke: border, sw: st === 'error' || st === 'focus' ? 2 : 1.5 });
    let tx = x + 16;
    if (o.prefix) {
      s += text(tx, mid(yy, h, 16), o.prefix, { size: 16, weight: 500, fill: c('ink.500') });
      tx += measure(o.prefix, 16, 500) + 8;
    }
    if (o.icon) {
      s += icon(o.icon, x + 14, yy + (h - 20) / 2, 20, c('ink.500'));
      tx = x + 44;
    }
    const val = o.value;
    if (val) s += text(tx, mid(yy, h, 16), val, { size: 16, fill: st === 'disabled' ? c('ink.400') : c('ink.900') });
    else if (o.placeholder) s += text(tx, mid(yy, h, 16), o.placeholder, { size: 16, fill: c('ink.500') });
    if (st === 'focus' && !val) s += line(tx, yy + 14, tx, yy + h - 14, { stroke: c('ink.900'), sw: 1.5 });
    if (o.suffix) s += text(x + w - 16, mid(yy, h, 16), o.suffix, { size: 16, weight: 600, fill: c('ink.700'), anchor: 'end' });
    if (o.select) s += icon('chevron-down', x + w - 36, yy + (h - 20) / 2, 20, c('ink.700'));
    yy += h;
    if (st === 'error' && o.error) {
      s += icon('alert-circle', x, yy + 8, 16, c('danger.fg'));
      s += text(x + 22, yy + 21, o.error, { size: 13, weight: 500, fill: c('danger.fg') });
    } else if (o.hint) {
      s += text(x, yy + 21, o.hint, { size: 13, fill: c('ink.500') });
    }
    return g(null, s);
  }

  function textarea(x, y, w, h, o = {}) {
    let s = text(x, y + 14, o.label, { size: 14, weight: 600 });
    s += rect(x, y + 24, w, h, { r: 10, fill: c('white'), stroke: c('ink.500'), sw: 1.5 });
    s += para(x + 16, y + 52, o.value, w - 32, { size: 16, lh: 24 });
    s += text(x + w, y + 24 + h + 21, o.counter, { size: 13, fill: c('ink.500'), anchor: 'end' });
    s += text(x, y + 24 + h + 21, o.hint, { size: 13, fill: c('ink.500') });
    return s;
  }

  function checkbox(x, y, label, o = {}) {
    const on = o.checked;
    const dis = o.state === 'disabled';
    let s = '';
    if (o.state === 'focus') s += rect(x - 4.5, y - 4.5, 31, 31, { r: 10, stroke: c('sky.600'), sw: 3 });
    s += rect(x, y, 22, 22, { r: 6, fill: on ? (dis ? c('ink.300') : c('ink.900')) : dis ? c('ink.100') : c('white'), stroke: on ? undefined : dis ? c('ink.300') : c('ink.500'), sw: 1.5 });
    if (on) s += `<path d="M${x + 5.5} ${y + 11.5} l3.8 3.8 7.2-7.6" stroke="#fff" stroke-width="2.4" fill="none" stroke-linecap="round" stroke-linejoin="round"/>`;
    if (label) s += text(x + 32, y + 16, label, { size: 16, fill: dis ? c('ink.400') : c('ink.900') });
    return s;
  }

  function radio(x, y, label, o = {}) {
    const on = o.checked;
    const dis = o.state === 'disabled';
    let s = '';
    if (o.state === 'focus') s += circle(x + 11, y + 11, 16.5, { stroke: c('sky.600'), sw: 3 });
    s += circle(x + 11, y + 11, 10.25, { fill: dis ? c('ink.100') : c('white'), stroke: on && !dis ? c('ink.900') : dis ? c('ink.300') : c('ink.500'), sw: on ? 2 : 1.5 });
    if (on) s += circle(x + 11, y + 11, 5.5, { fill: dis ? c('ink.300') : c('ink.900') });
    if (label) s += text(x + 32, y + 16, label, { size: 16, fill: dis ? c('ink.400') : c('ink.900') });
    return s;
  }

  function toggle(x, y, label, on) {
    let s = rect(x, y, 44, 26, { r: 13, fill: on ? c('ink.900') : c('ink.300') });
    s += circle(on ? x + 31 : x + 13, y + 13, 10, { fill: c('white') });
    if (on) s += `<path d="M${x + 26.5} ${y + 13} l3 3 5-5.5" stroke="${c('ink.900')}" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>`;
    s += text(x + 56, y + 18, label, { size: 16 });
    return s;
  }

  /** Чип фильтра. */
  function chip(x, y, label, o = {}) {
    const on = o.selected;
    const iw = on ? 22 : 0;
    const cnt = o.count != null ? ` ${o.count}` : '';
    const w = 32 + iw + measure(label + cnt, 14, 500) + (cnt ? 2 : 0);
    let s = '';
    if (o.state === 'focus') s += rect(x - 4.5, y - 4.5, w + 9, 45, { r: 22.5, stroke: c('sky.600'), sw: 3 });
    s += rect(x, y, w, 36, { r: 18, fill: on ? c('ink.900') : o.state === 'hover' ? c('ink.50') : c('white'), stroke: on ? undefined : o.state === 'hover' ? c('ink.900') : c('ink.300'), sw: 1.5 });
    let tx = x + 16;
    if (on) {
      s += icon('check', tx - 2, y + 9, 18, c('white'));
      tx += iw;
    }
    s += text(tx, mid(y, 36, 14), label, { size: 14, weight: 500, fill: on ? c('white') : c('ink.900') });
    if (cnt) s += text(tx + measure(label, 14, 500) + 5, mid(y, 36, 14), o.count, { size: 14, weight: 500, fill: on ? c('ink.300') : c('ink.500') });
    return { svg: s, w };
  }

  /** Пресет суммы пожертвования. */
  function amount(x, y, w, label, selected) {
    let s = rect(x, y, w, 52, { r: 10, fill: selected ? c('tag.100') : c('white'), stroke: selected ? c('ink.900') : c('ink.300'), sw: selected ? 2 : 1.5 });
    s += text(x + w / 2, mid(y, 52, 17), label, { size: 17, weight: 600, anchor: 'middle' });
    return s;
  }

  /* ---------- Прогресс сбора ---------- */
  function progress(x, y, w, ratio, o = {}) {
    const h = o.h ?? 10;
    const done = ratio >= 1;
    let s = rect(x, y, w, h, { r: h / 2, fill: c('ink.100') });
    const fw = Math.max(h, w * Math.min(1, ratio));
    s += rect(x, y, fw, h, { r: h / 2, fill: done ? c('success.solid') : c('tag.500') });
    if (!done) s += rect(x + fw - 3, y, 3, h, { fill: c('ink.900'), r: 1.5 }); // «засечка» текущего уровня — видна без цвета
    return s;
  }

  /* ---------- Слот выгула ---------- */
  function slot(x, y, st, time, note) {
    const w = 168;
    const h = 76;
    const sty = {
      open: { bg: c('white'), border: c('meadow.500'), fg: c('ink.900'), sub: c('meadow.700'), sw: 1.5 },
      few: { bg: c('white'), border: c('warning.border'), fg: c('ink.900'), sub: c('warning.fg'), sw: 1.5 },
      full: { bg: c('ink.100'), border: c('ink.200'), fg: c('ink.500'), sub: c('ink.500'), sw: 1 },
      past: { bg: c('white'), border: c('ink.200'), fg: c('ink.400'), sub: c('ink.500'), sw: 1, dash: '4 4' },
      selected: { bg: c('ink.900'), border: c('ink.900'), fg: c('white'), sub: c('tag.500'), sw: 1.5 },
      focus: { bg: c('white'), border: c('meadow.500'), fg: c('ink.900'), sub: c('meadow.700'), sw: 1.5 },
    }[st];
    let s = '';
    if (st === 'focus') s += focusRing(x, y, w, h, 10);
    s += rect(x, y, w, h, { r: 10, fill: sty.bg, stroke: sty.border, sw: sty.sw, dash: sty.dash });
    s += text(x + 16, y + 32, time, { size: 18, weight: 600, fill: sty.fg, decoration: st === 'past' ? 'line-through' : undefined });
    const ic = { open: 'leash', few: 'alert-triangle', full: 'x', past: 'clock', selected: 'check', focus: 'leash' }[st];
    s += icon(ic, x + 16, y + 44, 16, sty.sub);
    s += text(x + 38, y + 57, note, { size: 13, weight: 600, fill: sty.sub });
    return s;
  }

  /* ---------- Карточка питомца ---------- */
  function petCard(x, y, d) {
    const w = d.w ?? 296;
    const ph = d.ph ?? 236;
    let s = '';
    s += rect(x, y, w, d.h ?? ph + 202, { r: 24, fill: c('white'), stroke: d.hover ? c('ink.300') : c('ink.200'), sw: 1, filter: d.hover ? 'url(#shadow-md)' : undefined });
    s += petPhoto(x + 10, y + 10, w - 20, ph, { kind: d.kind, fur: d.fur, bg: d.bg, sun: d.sun, dim: d.dim });
    const b = badge(x + 22, y + 22, d.status[0], d.status[1]);
    s += rect(x + 20, y + 20, b.w + 4, 32, { r: 16, fill: c('white') });
    s += b.svg;
    // избранное
    s += circle(x + w - 40, y + 40, 20, { fill: c('white') });
    s += icon(d.fav ? 'heart-filled' : 'heart', x + w - 51, y + 29, 22, d.fav ? c('danger.solid') : c('ink.900'));
    let yy = y + ph + 46;
    s += text(x + 20, yy, d.name, { family: 'display', size: 22, weight: 600, fill: d.dim ? c('ink.700') : c('ink.900') });
    yy += 26;
    s += text(x + 20, yy, d.meta, { size: 15, fill: c('ink.700') });
    yy += 18;
    let cx = x + 20;
    for (const t of d.traits) {
      const tw = measure(t, 13, 500) + 20;
      s += rect(cx, yy, tw, 26, { r: 13, fill: c('ink.100') });
      s += text(cx + 10, mid(yy, 26, 13), t, { size: 13, weight: 500, fill: c('ink.700') });
      cx += tw + 6;
    }
    yy += 46;
    s += line(x + 20, yy - 8, x + w - 20, yy - 8, { stroke: c('ink.200') });
    s += icon('home', x + 20, yy + 2, 18, c('ink.500'));
    s += text(x + 44, yy + 16, d.shelter, { size: 14, fill: c('ink.700') });
    if (d.walk) {
      s += icon('leash', x + 20, yy + 28, 18, c('meadow.700'));
      s += text(x + 44, yy + 42, 'Можно погулять', { size: 14, weight: 600, fill: c('meadow.700') });
    }
    return g(null, s);
  }

  function skeletonCard(x, y) {
    const w = 296;
    let s = rect(x, y, w, 438, { r: 24, fill: c('white'), stroke: c('ink.200') });
    s += rect(x + 10, y + 10, w - 20, 236, { r: 16, fill: c('ink.100') });
    s += rect(x + 20, y + 268, 140, 22, { r: 6, fill: c('ink.100') });
    s += rect(x + 20, y + 304, 200, 14, { r: 6, fill: c('ink.100') });
    s += rect(x + 20, y + 332, 90, 26, { r: 13, fill: c('ink.100') });
    s += rect(x + 116, y + 332, 70, 26, { r: 13, fill: c('ink.100') });
    s += rect(x + 20, y + 384, 220, 14, { r: 6, fill: c('ink.100') });
    s += rect(x + 20, y + 410, 150, 14, { r: 6, fill: c('ink.100') });
    return s;
  }

  /* ---------- Карточка сбора ---------- */
  function campaignCard(x, y, d) {
    const w = d.w ?? 384;
    const h = 452;
    let s = rect(x, y, w, h, { r: 24, fill: c('white'), stroke: c('ink.200') });
    s += petPhoto(x + 10, y + 10, w - 20, 168, { kind: d.kind, fur: d.fur, bg: d.bg, zoom: 0.95, dy: 0 });
    const b = badge(x + 22, y + 22, d.status[0], d.status[1]);
    s += rect(x + 20, y + 20, b.w + 4, 32, { r: 16, fill: c('white') });
    s += b.svg;
    let yy = y + 214;
    const tl = wrap(d.title, w - 40, 19, 600, 'display');
    tl.forEach((l, i) => (s += text(x + 20, yy + i * 25, l, { family: 'display', size: 19, weight: 600 })));
    yy += 25 + 26;
    s += text(x + 20, yy, d.shelter, { size: 14, fill: c('ink.700') });
    yy += 34;
    s += text(x + 20, yy, d.raised, { family: 'display', size: 25, weight: 600 });
    s += text(x + 20 + measure(d.raised, 25, 600, 'display') + 8, yy, `из ${d.goal}`, { size: 15, fill: c('ink.700') });
    s += text(x + w - 20, yy, `${Math.round(d.ratio * 100)}%`, { size: 15, weight: 600, anchor: 'end' });
    yy += 16;
    s += progress(x + 20, yy, w - 40, d.ratio);
    yy += 36;
    s += icon('users', x + 20, yy - 14, 18, c('ink.500'));
    s += text(x + 44, yy, d.people, { size: 14, fill: c('ink.700') });
    s += icon(d.ratio >= 1 ? 'check-circle' : 'clock', x + Math.round(w * 0.536), yy - 14, 18, d.ratio >= 1 ? c('success.fg') : c('ink.500'));
    s += text(x + Math.round(w * 0.536) + 24, yy, d.left, { size: 14, fill: d.ratio >= 1 ? c('success.fg') : c('ink.700'), weight: d.ratio >= 1 ? 600 : 400 });
    yy += 22;
    s += d.ratio >= 1 ? button(x + 20, yy, 'Смотреть отчёт', { variant: 'secondary', w: w - 40 }) : button(x + 20, yy, 'Помочь сбору', { variant: 'donate', w: w - 40, icon: 'heart' });
    return g(null, s);
  }

  window.UI = { FUR, petPhoto, dogPortrait, catPortrait, button, badge, field, textarea, checkbox, radio, toggle, chip, amount, progress, slot, petCard, skeletonCard, campaignCard, focusRing, spinner, TONE, BTN };
})();
