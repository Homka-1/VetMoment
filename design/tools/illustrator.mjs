// Подготовка макетов для Adobe Illustrator.
// Запуск из корня репозитория:  node design/tools/illustrator.mjs
// (вызывается и в конце build.mjs). Нужны Node 20+ и Playwright с Chromium.
//
// Что делает:
//  1. Берёт готовые SVG из design/ и делает «Illustrator-совместимые» копии в design/illustrator/svg/:
//     - <use href="#i-…"> (SVG 2, Illustrator его не понимает) → встроенные группы с явным цветом;
//     - feDropShadow (SVG 2) → эквивалентный фильтр SVG 1.1;
//     - шрифты → PostScript-имена начертаний (GolosText-SemiBold, Unbounded-Bold …),
//       чтобы Illustrator сразу подставил нужный вес;
//     - убирает @import веб-шрифтов.
//  2. Считает границы каждого артборда (<g id="artboard-…">) и пишет design/illustrator/manifest.js —
//     его читает скрипт VetMoment-to-AI.jsx, который в самом Illustrator создаёт монтажные области,
//     раскладывает артборды по слоям и сохраняет .ai в design/source/.
import { mkdirSync, writeFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { basename, dirname, join, resolve } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const require = createRequire(import.meta.url);
const { chromium } = require('playwright');

const here = dirname(fileURLToPath(import.meta.url));
const design = resolve(here, '..');
const outDir = join(design, 'illustrator');

// Порядок = порядок обработки в Illustrator. out — имя итогового .ai (без расширения).
const SOURCES = [
  ['concept.svg', '00-concept'],
  ['ui-kit.svg', '00-ui-kit'],
  ['brand/logo-sheet.svg', '00-brand-logo-sheet'],
  ['icons/icon-sheet.svg', '00-icon-sheet'],
  ['screens/01-home.svg', '01-home'],
  ['screens/02-catalog.svg', '02-catalog'],
  ['screens/03-pet.svg', '03-pet'],
  ['screens/04-campaigns.svg', '04-campaigns'],
  ['screens/05-campaign.svg', '05-campaign'],
  ['screens/06-walks.svg', '06-walks'],
  ['screens/07-shelter.svg', '07-shelter'],
  ['screens/08-auth.svg', '08-auth'],
  ['screens/09-cabinet-volunteer.svg', '09-cabinet-volunteer'],
  ['screens/10-cabinet-shelter.svg', '10-cabinet-shelter'],
  ['screens/11-admin.svg', '11-admin'],
  ['screens/12-states.svg', '12-states'],
];

// Начертания, которые используются в макетах (файлы — design/fonts/desktop/*.ttf).
const WEIGHT_NAMES = { 400: 'Regular', 500: 'Medium', 600: 'SemiBold', 700: 'Bold' };

const browser = await chromium.launch();
const manifest = { generated: new Date().toISOString().slice(0, 10), fonts: new Set(), files: [] };

for (const [rel, out] of SOURCES) {
  const page = await browser.newPage();
  await page.goto(pathToFileURL(join(design, rel)).href);
  await page.evaluate(() => document.fonts.ready);

  const result = await page.evaluate((WEIGHT_NAMES) => {
    const NS = 'http://www.w3.org/2000/svg';
    const svg = document.documentElement;
    const fonts = new Set();

    // --- 1. Артборды: границы в координатах документа -------------------------------------
    const vb = svg.viewBox.baseVal;
    const docW = vb && vb.width ? vb.width : svg.width.baseVal.value;
    const docH = vb && vb.height ? vb.height : svg.height.baseVal.value;

    const rectOf = (g) => {
      const m = g.transform.baseVal.consolidate();
      const tx = m ? m.matrix.e : 0;
      const ty = m ? m.matrix.f : 0;
      // а) прямоугольник обрезки артборда, б) первый прямоугольник-фон, в) габариты группы
      const clipHost = g.querySelector(':scope > g[clip-path]');
      if (clipHost) {
        const id = clipHost.getAttribute('clip-path').match(/#([^)]+)/)[1];
        const r = document.getElementById(id)?.querySelector('rect');
        if (r) return { x: tx + r.x.baseVal.value, y: ty + r.y.baseVal.value, w: r.width.baseVal.value, h: r.height.baseVal.value };
      }
      const bg = g.querySelector(':scope > rect');
      if (bg) return { x: tx + bg.x.baseVal.value, y: ty + bg.y.baseVal.value, w: bg.width.baseVal.value, h: bg.height.baseVal.value };
      const b = g.getBBox();
      return { x: tx + b.x, y: ty + b.y, w: b.width, h: b.height };
    };

    const round = (v) => Math.round(v * 100) / 100;
    let artboards = [...svg.querySelectorAll('g[id^="artboard-"]')].map((g) => {
      const r = rectOf(g);
      return { id: g.id, name: g.getAttribute('data-name') || g.id, x: round(r.x), y: round(r.y), w: round(r.w), h: round(r.h) };
    });
    const title = svg.querySelector(':scope > title')?.textContent || '';
    if (!artboards.length) artboards = [{ id: '', name: title || 'Макет', x: 0, y: 0, w: round(docW), h: round(docH) }];

    // --- 2. Шрифты: PostScript-имена начертаний -------------------------------------------
    for (const t of svg.querySelectorAll('text, tspan')) {
      const cs = getComputedStyle(t);
      const family = /Unbounded/i.test(cs.fontFamily) ? 'Unbounded' : 'GolosText';
      let w = parseInt(cs.fontWeight, 10) || 400;
      if (!WEIGHT_NAMES[w]) w = w < 450 ? 400 : w < 550 ? 500 : w < 650 ? 600 : 700;
      if (family === 'Unbounded' && w < 500) w = 500;
      const ps = `${family}-${WEIGHT_NAMES[w]}`;
      fonts.add(ps);
      if (t.hasAttribute('font-family') || t.tagName === 'text') t.setAttribute('font-family', `'${ps}'`);
      t.removeAttribute('font-weight');
    }

    // --- 3. <use> → встроенные группы ------------------------------------------------------
    for (const use of [...svg.querySelectorAll('use')]) {
      const href = use.getAttribute('href') || use.getAttribute('xlink:href') || '';
      const sym = document.getElementById(href.replace('#', ''));
      if (!sym) { use.remove(); continue; }
      const color = use.getAttribute('color') || getComputedStyle(use).color || '#000000';
      const x = parseFloat(use.getAttribute('x') || '0');
      const y = parseFloat(use.getAttribute('y') || '0');
      const sv = sym.viewBox.baseVal;
      const vbw = sv && sv.width ? sv.width : 24;
      const vbh = sv && sv.height ? sv.height : 24;
      const w = parseFloat(use.getAttribute('width') || vbw);
      const h = parseFloat(use.getAttribute('height') || vbh);

      const g = document.createElementNS(NS, 'g');
      g.setAttribute('transform', `translate(${x} ${y}) scale(${round(w / vbw * 1000) / 1000} ${round(h / vbh * 1000) / 1000})`);
      for (const a of ['fill', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'opacity']) {
        const v = sym.getAttribute(a) ?? use.getAttribute(a);
        if (v != null) g.setAttribute(a, v);
      }
      const titleEl = sym.querySelector('title');
      if (titleEl) g.setAttribute('id', `icon-${href.slice(3)}`);
      for (const child of sym.childNodes) {
        if (child.nodeName === 'title') continue;
        g.appendChild(child.cloneNode(true));
      }
      for (const el of [g, ...g.querySelectorAll('*')]) {
        for (const attr of [...el.attributes]) {
          if (attr.value.includes('currentColor')) el.setAttribute(attr.name, attr.value.replaceAll('currentColor', color));
        }
      }
      use.replaceWith(g);
    }
    for (const s of [...svg.querySelectorAll('symbol')]) s.remove();
    for (const d of [...svg.querySelectorAll('defs')]) if (!d.children.length) d.remove();

    // --- 4. feDropShadow → SVG 1.1 ---------------------------------------------------------
    for (const ds of [...svg.querySelectorAll('feDropShadow')]) {
      const f = ds.parentNode;
      const dx = ds.getAttribute('dx') || '0';
      const dy = ds.getAttribute('dy') || '0';
      const sd = ds.getAttribute('stdDeviation') || '0';
      const fc = ds.getAttribute('flood-color') || '#000000';
      const fo = ds.getAttribute('flood-opacity') || '1';
      f.innerHTML =
        `<feGaussianBlur in="SourceAlpha" stdDeviation="${sd}"/>` +
        `<feOffset dx="${dx}" dy="${dy}" result="offsetblur"/>` +
        `<feFlood flood-color="${fc}" flood-opacity="${fo}"/>` +
        `<feComposite in2="offsetblur" operator="in"/>` +
        `<feMerge><feMergeNode/><feMergeNode in="SourceGraphic"/></feMerge>`;
    }

    // --- 5. Веб-шрифты не нужны: в Illustrator используются установленные TTF ---------------
    for (const st of [...svg.querySelectorAll('style')]) st.remove();

    const xml = '<?xml version="1.0" encoding="UTF-8"?>\n' + new XMLSerializer().serializeToString(svg);
    return { xml, artboards, title, fonts: [...fonts] };
  }, WEIGHT_NAMES);

  const svgName = `${out}.svg`;
  const target = join(outDir, 'svg', svgName);
  mkdirSync(dirname(target), { recursive: true });
  writeFileSync(target, result.xml);
  result.fonts.forEach((f) => manifest.fonts.add(f));
  manifest.files.push({ svg: svgName, ai: `${out}.ai`, title: result.title || basename(rel), artboards: result.artboards });
  console.log(`ai-svg ${svgName}  (${result.artboards.length} арт.)`);
  await page.close();
}

await browser.close();

// ExtendScript (ES3) не знает JSON.parse в старых версиях Illustrator, поэтому манифест — JS-литерал.
// Кириллица экранируется (\uXXXX): $.evalFile на Windows может прочитать UTF-8 без BOM как ANSI.
const data = { ...manifest, fonts: [...manifest.fonts].sort() };
const asciiJson = (v) => JSON.stringify(v, null, 2).replace(/[\u0080-\uffff]/g, (c) => '\\u' + c.charCodeAt(0).toString(16).padStart(4, '0'));
writeFileSync(
  join(outDir, 'manifest.js'),
  `// Generated by design/tools/illustrator.mjs - do not edit. Read by VetMoment-to-AI.jsx.\n` +
    `var VETMOMENT_MANIFEST = ${asciiJson(data)};\n`,
);
const total = data.files.reduce((n, f) => n + f.artboards.length, 0);
console.log(`manifest.js: ${data.files.length} файлов, ${total} артбордов, шрифты: ${data.fonts.join(', ')}`);
