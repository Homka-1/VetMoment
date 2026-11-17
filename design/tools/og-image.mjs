// Растровые картинки бренда для мест, где SVG не работает:
//  - public/images/og-default.png — картинка для шаринга по умолчанию (Open Graph, 1200×630):
//    логотип, портрет с биркой, три пути помощи (App\Support\Seo::DEFAULT_IMAGE);
//  - public/images/brand/logo-email.png — логотип для писем (почтовые клиенты не показывают SVG), 2× от 184×32.
// Запуск из корня репозитория:  node design/tools/og-image.mjs
import { readFileSync, rmSync, writeFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const require = createRequire(import.meta.url);
const { chromium } = require('playwright');

const here = dirname(fileURLToPath(import.meta.url));
const root = resolve(here, '../..');
const t = JSON.parse(readFileSync(join(here, '../tokens.json'), 'utf8'));
const c = (path) => path.split('.').reduce((o, k) => o[k], t.color).$value;
const svg = (rel) => readFileSync(join(root, rel), 'utf8');
const fonts = '../fonts/fonts.css';

const html = `<!doctype html><meta charset="utf-8"><link rel="stylesheet" href="${fonts}">
<style>
  html,body{margin:0;width:1200px;height:630px;background:#fff;color:${c('ink.900')};font-family:'Golos Text',sans-serif}
  .wrap{position:relative;width:1200px;height:630px;overflow:hidden}
  .logo{position:absolute;left:72px;top:64px;height:56px}
  .logo svg{height:56px;width:auto}
  h1{position:absolute;left:72px;top:184px;width:600px;margin:0;font:700 52px/1.12 Unbounded,sans-serif;letter-spacing:-0.01em}
  .paths{position:absolute;left:72px;bottom:72px;display:flex;gap:12px}
  .paths span{display:block;padding:12px 22px;border-radius:999px;font-weight:600;font-size:24px}
  .portrait{position:absolute;right:96px;top:96px;width:400px;height:400px;border-radius:50%;background:${c('sky.100')};overflow:hidden;display:grid;place-items:end center}
  .portrait svg{width:92%;height:auto;display:block}
  .tag{position:absolute;right:72px;top:392px;width:168px;height:168px;border-radius:50%;background:${c('tag.500')};display:grid;place-items:center;font:700 64px/1 Unbounded,sans-serif}
  .tag::before{content:'';position:absolute;top:14px;left:50%;width:18px;height:18px;margin-left:-9px;border-radius:50%;background:#fff;border:3px solid ${c('ink.900')}}
</style>
<div class="wrap">
  <div class="logo">${svg('public/images/brand/logo.svg')}</div>
  <h1>Забрать домой, помочь сбору, погулять с&nbsp;собакой</h1>
  <div class="paths">
    <span style="background:${c('sky.100')}">Питомцы</span>
    <span style="background:${c('tag.100')}">Сборы</span>
    <span style="background:${c('meadow.100')}">Выгул</span>
  </div>
  <div class="portrait">${svg('public/images/illustrations/dog-caramel.svg')}</div>
  <div class="tag">₸</div>
</div>`;

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1200, height: 630 }, deviceScaleFactor: 1 });
// Страница — файлом рядом со скриптом: шрифты из fonts.css грузятся по относительным file://-путям.
const harness = join(here, '.og.html');
writeFileSync(harness, html);
await page.goto(pathToFileURL(harness).href, { waitUntil: 'networkidle' });
await page.evaluate(() => document.fonts.ready);
await page.screenshot({ path: join(root, 'public/images/og-default.png') });

// Логотип для писем: SVG 367×64 → PNG 368×64 (показывается 184×32), прозрачный фон.
const logo = await browser.newPage({ viewport: { width: 368, height: 64 }, deviceScaleFactor: 1 });
await logo.setContent(`<style>html,body{margin:0;background:transparent}svg{display:block;width:368px;height:64px}</style>${svg('public/images/brand/logo.svg')}`);
await logo.screenshot({ path: join(root, 'public/images/brand/logo-email.png'), omitBackground: true });
await browser.close();
rmSync(harness);
console.log('public/images/og-default.png, public/images/brand/logo-email.png');
