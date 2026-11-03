// Сборка дизайн-артефактов: SVG (логотипы, иконки, концепт, UI-кит) + PNG-превью.
// Запуск из корня репозитория:  node design/tools/build.mjs
// Нужны: Node 20+, Playwright с Chromium, Python 3 с fonttools и brotli (для контуров логотипа).
import { execFileSync } from 'node:child_process';
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const require = createRequire(import.meta.url);
const { chromium } = require('playwright');

const here = dirname(fileURLToPath(import.meta.url));
const design = resolve(here, '..');

const tokens = JSON.parse(readFileSync(join(design, 'tokens.json'), 'utf8'));
const logo = JSON.parse(
  execFileSync('python3', [join(here, 'outline_text.py'), join(design, 'fonts/unbounded-latin-600-normal.woff2'), 'VetMoment', '40', '-0.02'], { encoding: 'utf8' }),
);

// Страница-«печатная машинка»: шрифты + скрипты генератора.
const harness = join(here, '.harness.html');
writeFileSync(
  harness,
  `<!doctype html><meta charset="utf-8"><link rel="stylesheet" href="../fonts/fonts.css">
<script>window.TOKENS=${JSON.stringify(tokens)};window.LOGO=${JSON.stringify(logo)};</script>
<script src="lib.js"></script><script src="icons.js"></script><script src="components.js"></script><script src="kit.js"></script>`,
);

const browser = await chromium.launch();
const page = await browser.newPage();
await page.goto(pathToFileURL(harness).href);
await page.evaluate(async () => {
  const samples = 'Аа Яя Ёё Әә Ққ VetMoment ₸ 0123';
  const loads = [];
  for (const w of [500, 600, 700]) loads.push(document.fonts.load(`${w} 20px "Unbounded"`, samples));
  for (const w of [400, 500, 600, 700]) loads.push(document.fonts.load(`${w} 20px "Golos Text"`, samples));
  await Promise.all(loads);
});
const { files, artboards } = await page.evaluate(() => window.buildDesign());

for (const [rel, content] of Object.entries(files)) {
  const out = join(design, rel);
  mkdirSync(dirname(out), { recursive: true });
  writeFileSync(out, content);
  console.log('svg ', rel);
}

// PNG-превью: открываем сами SVG (шрифты подтягиваются через @import fonts.css).
async function renderPng(svgRel, pngRel, opts = {}) {
  const p = await browser.newPage({ deviceScaleFactor: opts.scale ?? 1, viewport: { width: 800, height: 600 } });
  await p.goto(pathToFileURL(join(design, svgRel)).href);
  await p.evaluate(() => document.fonts.ready);
  await p.waitForTimeout(150);
  const size = await p.evaluate(() => {
    const s = document.documentElement;
    return { w: s.width.baseVal.value, h: s.height.baseVal.value };
  });
  await p.setViewportSize({ width: Math.ceil(size.w), height: Math.ceil(size.h) });
  const out = join(design, pngRel);
  mkdirSync(dirname(out), { recursive: true });
  await p.screenshot({ path: out, clip: opts.clip ?? { x: 0, y: 0, width: size.w, height: size.h }, omitBackground: !!opts.transparent });
  await p.close();
  console.log('png ', pngRel);
}

await renderPng('concept.svg', 'png/concept.png');
await renderPng('ui-kit.svg', 'png/ui-kit.png');
for (const a of artboards) await renderPng('ui-kit.svg', `png/ui-kit-${a.id.replace('artboard-', '')}.png`, { clip: { x: 0, y: a.y, width: 1440, height: a.h } });
await renderPng('brand/logo-sheet.svg', 'brand/png/logo-sheet.png');
await renderPng('brand/logo.svg', 'brand/png/logo@4x.png', { scale: 4, transparent: true });
await renderPng('brand/logo-inverse.svg', 'brand/png/logo-inverse@4x.png', { scale: 4, transparent: true });
for (const s of [16, 32, 180, 512]) await renderPng('brand/favicon.svg', `brand/png/favicon-${s}.png`, { scale: s / 64, transparent: true });
await renderPng('icons/icon-sheet.svg', 'png/icon-sheet.png');

await browser.close();
