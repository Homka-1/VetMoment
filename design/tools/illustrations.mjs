// Иллюстрации-портреты для интерфейса (пустые состояния, вход, ошибки): SVG без фона, 200×200.
// Запуск из корня репозитория:  node design/tools/illustrations.mjs
// Результат: public/images/illustrations/{dog,cat}-<окрас>.svg
import { mkdirSync, readFileSync, writeFileSync, rmSync } from 'node:fs';
import { createRequire } from 'node:module';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const require = createRequire(import.meta.url);
const { chromium } = require('playwright');

const here = dirname(fileURLToPath(import.meta.url));
const root = resolve(here, '../..');
const out = join(root, 'public/images/illustrations');
mkdirSync(out, { recursive: true });

const tokens = JSON.parse(readFileSync(join(here, '../tokens.json'), 'utf8'));
const harness = join(here, '.illustrations.html');
writeFileSync(
  harness,
  `<!doctype html><meta charset="utf-8">
<script>window.TOKENS=${JSON.stringify(tokens)};</script>
<script src="lib.js"></script><script src="icons.js"></script><script src="components.js"></script><body></body>`,
);

const variants = [
  ['dog', 'caramel'], ['dog', 'graphite'], ['cat', 'ginger'], ['cat', 'smoke'],
];

const browser = await chromium.launch();
const page = await browser.newPage();
await page.goto(pathToFileURL(harness).href);

for (const [kind, fur] of variants) {
  const art = await page.evaluate(({ kind, fur }) => (kind === 'cat' ? window.UI.catPortrait(fur) : window.UI.dogPortrait(fur)), { kind, fur });
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="200" height="200">${art}</svg>\n`;
  writeFileSync(join(out, `${kind}-${fur}.svg`), svg);
  console.log('✓', `${kind}-${fur}.svg`, svg.length);
}

await browser.close();
rmSync(harness);
