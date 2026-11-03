/*
 * Общие примитивы для генерации SVG дизайн-системы VetMoment.
 * Выполняется в браузере (Chromium через Playwright): ширина текста измеряется
 * canvas.measureText по настоящим шрифтам из design/fonts/fonts.css.
 * Текст остаётся текстом (<text>), чтобы SVG можно было править в Illustrator/Figma/Inkscape.
 */
(function () {
  const TK = window.TOKENS;

  /** Значение цвета по пути палитры: c('ink.900'), c('success.bg'), c('white'). */
  function c(path) {
    const node = path.split('.').reduce((o, k) => o[k], TK.color);
    return node.$value;
  }

  const FONT = {
    display: "'Unbounded', 'Golos Text', Arial, sans-serif",
    body: "'Golos Text', Arial, sans-serif",
  };

  const ctx = document.createElement('canvas').getContext('2d');
  function measure(str, size, weight = 400, family = 'body') {
    const name = family === 'display' ? 'Unbounded' : 'Golos Text';
    ctx.font = `${weight} ${size}px "${name}"`;
    const ls = family === 'display' ? -0.02 * size : 0;
    return ctx.measureText(str).width + ls * Math.max(0, [...str].length - 1);
  }

  const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  const r2 = (n) => Math.round(n * 100) / 100;

  function attrs(o) {
    return Object.entries(o)
      .filter(([, v]) => v !== undefined && v !== null && v !== false)
      .map(([k, v]) => `${k}="${typeof v === 'number' ? r2(v) : esc(v)}"`)
      .join(' ');
  }

  /** Текст. o: size, weight, family ('body'|'display'), fill, anchor, id, opacity, decoration */
  function text(x, y, str, o = {}) {
    const size = o.size ?? 16;
    const family = o.family ?? 'body';
    return `<text ${attrs({
      id: o.id,
      x,
      y,
      'font-family': FONT[family],
      'font-size': size,
      'font-weight': o.weight ?? 400,
      'letter-spacing': family === 'display' ? r2(-0.02 * size) : undefined,
      fill: o.fill ?? c('ink.900'),
      'text-anchor': o.anchor,
      opacity: o.opacity,
      'text-decoration': o.decoration,
    })}>${esc(str)}</text>`;
  }

  /** Базовая линия, при которой строка размера size визуально центрирована в полосе [y, y+h]. */
  const mid = (y, h, size) => y + h / 2 + size * 0.36;

  function rect(x, y, w, h, o = {}) {
    return `<rect ${attrs({
      id: o.id,
      x,
      y,
      width: w,
      height: h,
      rx: o.r,
      fill: o.fill ?? 'none',
      stroke: o.stroke,
      'stroke-width': o.sw,
      'stroke-dasharray': o.dash,
      opacity: o.opacity,
      filter: o.filter,
    })}/>`;
  }

  function circle(cx, cy, r, o = {}) {
    return `<circle ${attrs({ cx, cy, r, fill: o.fill ?? 'none', stroke: o.stroke, 'stroke-width': o.sw, opacity: o.opacity })}/>`;
  }

  function line(x1, y1, x2, y2, o = {}) {
    return `<line ${attrs({ x1, y1, x2, y2, stroke: o.stroke ?? c('ink.200'), 'stroke-width': o.sw ?? 1, 'stroke-dasharray': o.dash, 'stroke-linecap': o.cap })}/>`;
  }

  /** Иконка из спрайта (#i-<name>), цвет через currentColor. */
  function icon(name, x, y, size = 24, color = c('ink.900')) {
    return `<use ${attrs({ href: `#i-${name}`, x, y, width: size, height: size, color })}/>`;
  }

  /** Группа-слой. */
  const g = (id, inner, o = {}) =>
    `<g ${attrs({ id, 'data-name': o.name, transform: o.transform, opacity: o.opacity, 'clip-path': o.clip })}>${inner}</g>`;

  /** Перенос строки по ширине (жадный). Возвращает массив строк. */
  function wrap(str, width, size, weight = 400, family = 'body') {
    const words = str.split(' ');
    const lines = [];
    let cur = '';
    for (const w of words) {
      const next = cur ? `${cur} ${w}` : w;
      if (measure(next, size, weight, family) > width && cur) {
        lines.push(cur);
        cur = w;
      } else cur = next;
    }
    if (cur) lines.push(cur);
    return lines;
  }

  function para(x, y, str, width, o = {}) {
    const size = o.size ?? 16;
    const lh = o.lh ?? size * 1.55;
    return wrap(str, width, size, o.weight ?? 400, o.family ?? 'body')
      .map((l, i) => text(x, y + i * lh, l, o))
      .join('');
  }

  window.K = { TK, c, FONT, measure, esc, attrs, text, mid, rect, circle, line, icon, g, wrap, para, r2 };
})();
