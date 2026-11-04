// Демо-«фото» питомцев для сидера: портреты-иллюстрации из UI-кита (components.js) → JPEG 800×800.
// Запуск из корня репозитория:  node design/tools/demo-photos.mjs
// Результат: database/seeders/photos/{dog,cat}-<окрас>-<фон>.jpg (в образ Docker попадает вместе с database/).
import { mkdirSync, readFileSync, writeFileSync, rmSync } from 'node:fs';
import { createRequire } from 'node:module';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const require = createRequire(import.meta.url);
const { chromium } = require('playwright');

const here = dirname(fileURLToPath(import.meta.url));
const root = resolve(here, '../..');
const out = join(root, 'database/seeders/photos');
mkdirSync(out, { recursive: true });

const tokens = JSON.parse(readFileSync(join(here, '../tokens.json'), 'utf8'));
const harness = join(here, '.photos.html');
writeFileSync(
  harness,
  `<!doctype html><meta charset="utf-8"><style>html,body{margin:0}</style>
<script>window.TOKENS=${JSON.stringify(tokens)};</script>
<script src="lib.js"></script><script src="icons.js"></script><script src="components.js"></script><body></body>`,
);

const SIZE = 800;
const variants = [];
for (const fur of ['caramel', 'graphite']) {
  for (const bg of ['sky.100', 'meadow.100', 'tag.100', 'ink.100']) variants.push({ kind: 'dog', fur, bg });
}
for (const fur of ['smoke', 'ginger']) {
  for (const bg of ['sky.100', 'meadow.100', 'tag.100', 'ink.100']) variants.push({ kind: 'cat', fur, bg });
}

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: SIZE, height: SIZE }, deviceScaleFactor: 1 });
await page.goto(pathToFileURL(harness).href);

for (const v of variants) {
  await page.evaluate(({ v, SIZE }) => {
    const { c } = window.K;
    const art = window.UI.petPhoto(0, 0, SIZE, SIZE, { kind: v.kind, fur: v.fur, bg: c(v.bg), r: 0, sun: v.bg === 'sky.100', zoom: 0.85 });
    document.body.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="${SIZE}" height="${SIZE}" viewBox="0 0 ${SIZE} ${SIZE}">${art}</svg>`;
  }, { v, SIZE });
  const file = `${v.kind}-${v.fur}-${v.bg.split('.')[0]}.jpg`;
  await page.screenshot({ path: join(out, file), type: 'jpeg', quality: 82, clip: { x: 0, y: 0, width: SIZE, height: SIZE } });
  console.log('✓', file);
}

await browser.close();
rmSync(harness);
