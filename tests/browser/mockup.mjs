// VetMoment — проверка статического демо-макета (этап 15): mockup/ открывается по file:// без сети.
//
//   node tests/browser/mockup.mjs                  # обход всех страниц + сценарии
//   node tests/browser/mockup.mjs --screenshots    # + скриншоты 1440 и 390 в docs/screenshots/stage-15/
//   MOCKUP_DIR=путь node tests/browser/mockup.mjs  # другая папка макета (например, распакованный mockup.zip)
//
// Нужен Node.js и playwright с Chromium (как у smoke.mjs: `npm i --no-save playwright && npx playwright install chromium`).
//
// Что проверяется:
//   1. Каждая страница *.html: нет ошибок консоли и JS, нет запросов не к file:// (сеть отключена),
//      все относительные ссылки и картинки ведут на существующие файлы, нет горизонтальной прокрутки на 360 и 1440 px.
//   2. Сценарии: охрана кабинета для гостя, вход, фильтры каталога, пожертвование (растёт сбор), запись на выгул
//      (занимается место), заявка «Хочу забрать», избранное, переключение ролей, «Сбросить демо».

import { createRequire } from 'node:module';
import { existsSync, mkdirSync, readdirSync, readFileSync } from 'node:fs';
import { resolve, join } from 'node:path';
import { pathToFileURL } from 'node:url';

const require = createRequire(import.meta.url);
const { chromium } = require('playwright');

const DIR = resolve(process.env.MOCKUP_DIR || 'mockup');
const BASE = pathToFileURL(DIR).href + '/';
const SHOTS = process.argv.includes('--screenshots');
const SHOT_DIR = 'docs/screenshots/stage-15';

const failures = [];
let checks = 0;
const fail = (m) => { failures.push(m); console.log(`  ✗ ${m}`); };
const ok = (m) => { checks++; console.log(`  ✓ ${m}`); };

async function newContext(browser, width = 1440, scale = 1) {
    const context = await browser.newContext({ viewport: { width, height: 900 }, deviceScaleFactor: scale, locale: 'ru-RU', offline: true });
    // Всё, что не file:// и не data:, — нарушение «без интернета».
    await context.route('**/*', (route) => {
        const url = route.request().url();
        if (url.startsWith('file:') || url.startsWith('data:')) return route.continue();
        fail(`внешний запрос: ${url}`);
        return route.abort();
    });
    return context;
}

async function newPage(context) {
    const page = await context.newPage();
    page.problems = [];
    page.on('console', (msg) => { if (msg.type() === 'error') page.problems.push(`консоль: ${msg.text()}`); });
    page.on('pageerror', (err) => page.problems.push(`JS: ${err.message}`));
    return page;
}

const open = (page, file) => page.goto(BASE + file, { waitUntil: 'load' });

function takeProblems(page, where) {
    for (const p of page.problems.splice(0)) fail(`${where}: ${p}`);
}

/** Относительные ссылки и картинки — существующие файлы. */
function brokenRefs(file) {
    const html = readFileSync(join(DIR, file), 'utf8');
    const broken = [];
    for (const [, attr, value] of html.matchAll(/\s(href|src)="([^"]*)"/g)) {
        if (!value || value.startsWith('#') || /^(https?:|mailto:|tel:|data:|javascript:)/.test(value)) continue;
        const path = decodeURIComponent(value.split(/[?#]/)[0]);
        if (!existsSync(join(DIR, path))) broken.push(`${attr}=${value}`);
    }
    return broken;
}

async function crawl(browser) {
    console.log('\n1. Обход страниц (file://, без сети)');
    const files = readdirSync(DIR).filter((f) => f.endsWith('.html')).sort();
    const context = await newContext(browser);
    const page = await newPage(context);

    await open(page, 'index.html');
    let n = 0;
    for (const file of files) {
        const broken = brokenRefs(file);
        if (broken.length) fail(`${file}: битые ссылки ${broken.slice(0, 3).join(', ')}${broken.length > 3 ? ' …' : ''}`);

        // Страницы кабинетов «охраняются» ролью — смотрим их от имени нужной роли.
        const role = /data-demo-role="(\w+)"/.exec(readFileSync(join(DIR, file), 'utf8'))?.[1] ?? 'guest';
        await page.evaluate((r) => localStorage.setItem('vetmoment-demo', JSON.stringify({ role: r, donations: [], bookings: [], applications: [], withdrawn: [], ts: Date.now() })), role);
        await open(page, file);
        if (!page.url().endsWith(file)) fail(`${file}: ушли на ${page.url().slice(BASE.length)}`);

        for (const width of [1440, 360]) {
            await page.setViewportSize({ width, height: 800 });
            const extra = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
            if (extra > 0) fail(`${file}: горизонтальная прокрутка ${extra}px при ${width}`);
        }
        await page.setViewportSize({ width: 1440, height: 900 });
        takeProblems(page, file);
        n++;
    }
    ok(`${n} страниц`);
    await context.close();
}

const text = (page, selector) => page.locator(selector).first().innerText();
const money = (s) => Number(String(s).replace(/\D/g, ''));

async function scenarios(browser) {
    console.log('\n2. Сценарии');
    const context = await newContext(browser);
    const page = await newPage(context);
    await open(page, 'index.html');
    await page.evaluate(() => localStorage.clear());

    // 2.1 Гость не попадает в кабинет — его просят войти, после входа возвращают
    await open(page, 'cabinet-walks.html');
    await page.waitForURL(/login\.html\?next=/);
    await page.fill('input[name=identifier]', 'volunteer');
    await page.fill('input[name=password]', '123');
    await Promise.all([page.waitForNavigation(), page.click('form[data-demo-action="login"] button[type=submit]')]);
    if (!page.url().endsWith('cabinet-walks.html')) fail(`вход: после входа открыт ${page.url()}`); else ok('гость → вход → возврат в кабинет');
    await page.waitForSelector('[data-toast]');
    await page.click('[data-demo-switch=guest]');
    await page.waitForLoadState('load');

    // 2.2 Каталог: фильтр «Кошки» и поиск
    await open(page, 'pets.html?species[]=cat');
    const visible = () => page.$$eval('[data-pet-catalog] > li:not([hidden])', (lis) => lis.map((li) => li.dataset.pet));
    const species = await page.evaluate(() => window.DEMO_DATA.pets);
    const cats = await visible();
    if (!cats.length || cats.some((s) => species[s].species !== 'cat')) fail(`каталог: фильтр «Кошки» показал ${cats.join(', ')}`);
    else ok(`каталог: «Кошки» — ${cats.length}`);
    const name = species[cats[0]].name;
    await open(page, 'pets.html');
    await page.fill('input[name=q]', name.slice(0, 3).toLowerCase());
    await page.press('input[name=q]', 'Enter');
    const found = await visible();
    if (!found.length || found.some((s) => !species[s].name.toLowerCase().includes(name.slice(0, 3).toLowerCase()))) fail(`каталог: поиск «${name.slice(0, 3)}» → ${found.join(', ')}`);
    else ok(`каталог: поиск «${name.slice(0, 3)}» — ${found.length}`);
    await page.fill('input[name=q]', 'zzzz');
    await page.press('input[name=q]', 'Enter');
    if (!(await page.isVisible('[data-catalog-empty]'))) fail('каталог: нет пустого состояния'); else ok('каталог: «Никого не нашли»');

    // 2.3 Пожертвование гостем: сумма сбора растёт
    await open(page, 'campaigns.html');
    const campaignFile = await page.$eval('[data-campaign] :is(h2,h3) a', (a) => a.getAttribute('href'));
    await open(page, campaignFile);
    const before = money(await text(page, 'main [data-campaign-raised]'));
    await page.fill('input[name=amount]', '1500');
    await page.fill('input[name=donor_email]', 'guest@example.kz');
    await page.check('input[name=agree]', { force: true });
    await Promise.all([page.waitForNavigation(), page.click('[data-donation-form] button[type=submit]')]);
    await page.waitForSelector('[data-toast]');
    const after = money(await text(page, 'main [data-campaign-raised]'));
    if (after - before !== 1500) fail(`пожертвование: было ${before}, стало ${after}`); else ok('пожертвование: сбор вырос на 1 500 ₸');

    // 2.4 Запись на выгул волонтёром: место занимается, запись в «Моих выгулах»
    await page.click('[data-demo-switch=volunteer]');
    await page.waitForLoadState('load');
    await open(page, 'walks.html');
    const slotId = await page.$$eval('input[name=slot_id]:not([disabled])', (inputs) => inputs.find((i) => i.closest('label').offsetParent !== null
        && String(window.DEMO_DATA.slots[i.value]?.file).startsWith('walks-slots-'))?.value);
    const noteBefore = await text(page, `[data-slot-note="${slotId}"]`);
    await page.click(`label[for="slot-${slotId}"]`);
    await Promise.all([page.waitForNavigation(), page.click('#walk-slot-form button[type=submit]')]);
    await page.check('input[name=adult]', { force: true });
    await page.check('input[name=rules]', { force: true });
    await Promise.all([page.waitForNavigation(), page.click('form[data-demo-action$="/book"] button[type=submit]')]);
    if (!page.url().endsWith('cabinet-walks.html')) fail(`запись: открыт ${page.url()}`);
    const rows = await page.$$eval('main table tbody tr', (trs) => trs.length);
    if (rows === 0) fail('запись: нет строки в «Моих выгулах»'); else ok('запись на выгул: строка в «Моих выгулах»');
    await open(page, 'walks.html');
    const noteAfter = await page.locator(`[data-slot-note="${slotId}"]`).first().innerText().catch(() => '');
    if (noteAfter === noteBefore) fail(`запись: в слоте не изменилось «${noteBefore}»`); else ok(`слот: «${noteBefore}» → «${noteAfter}»`);

    // 2.5 Заявка «Хочу забрать» и избранное
    await open(page, species[cats[0]].file);
    const applyLink = await page.$('a[href*="-apply"]');
    if (!applyLink) fail('заявка: на карточке нет ссылки «Хочу забрать»');
    else {
        await Promise.all([page.waitForNavigation(), applyLink.click()]);
        await page.check('input[name=agree]', { force: true });
        const housing = page.locator('select[name=living_conditions]');
        if (await housing.count()) await housing.selectOption({ index: 1 });
        await Promise.all([page.waitForNavigation(), page.click('form[data-demo-action$="/apply"] button[type=submit]')]);
        const hasRow = await page.locator('main table tbody tr', { hasText: name }).count();
        if (!page.url().endsWith('cabinet-applications.html') || !hasRow) fail(`заявка: ${page.url()}, строк с «${name}»: ${hasRow}`);
        else ok('заявка: строка в «Моих заявках»');
    }
    await open(page, 'pets.html');
    const fav = page.locator(`[data-pet="${cats[0]}"] form[data-demo-action$="/favorite"] button`);
    const wasPressed = await fav.getAttribute('aria-pressed');
    await fav.click();
    const nowPressed = await fav.getAttribute('aria-pressed');
    await open(page, 'cabinet-favorites.html');
    const inFavorites = await page.locator('[data-favorites-list] h2 a, [data-favorites-list] h3 a', { hasText: name }).count();
    if (wasPressed === nowPressed || (nowPressed === 'true') !== (inFavorites > 0)) fail(`избранное: ${wasPressed} → ${nowPressed}, в списке ${inFavorites}`);
    else ok('избранное: сердечко и страница «Избранное»');

    // 2.6 Роли: приют и админ, шапка на публичной странице
    for (const [role, home] of [['shelter', 'shelter.html'], ['admin', 'admin.html']]) {
        await Promise.all([page.waitForNavigation(), page.click(`[data-demo-switch=${role}]`)]);
        if (!page.url().endsWith(home)) fail(`роль ${role}: открыт ${page.url()}`); else ok(`роль ${role}: ${home}`);
    }
    await open(page, 'help.html');
    if (await page.locator('header a[href="login.html"]').count()) fail('шапка: у вошедшего осталась кнопка «Войти»'); else ok('шапка роли на публичной странице');

    // 2.7 Ссылка на отсутствующую страницу — подсказка, а не переход
    const missing = page.locator('[data-demo-missing]').first();
    if (await missing.count()) {
        await missing.scrollIntoViewIfNeeded();
        await missing.click();
        if (!page.url().endsWith('help.html')) fail('нет страницы: произошёл переход'); else ok('ссылка вне макета — подсказка');
    }

    // 2.8 Сброс
    await Promise.all([page.waitForNavigation(), page.click('[data-demo-reset]')]);
    const reset = await page.evaluate(() => JSON.parse(localStorage.getItem('vetmoment-demo')));
    if (reset.role !== 'guest' || reset.donations.length || reset.bookings.length) fail('сброс: состояние не очищено'); else ok('«Сбросить демо»');

    takeProblems(page, 'сценарии');
    await context.close();
}

async function screenshots(browser) {
    console.log('\n3. Скриншоты');
    mkdirSync(SHOT_DIR, { recursive: true });
    const shots = [
        ['index', 'index.html', 'guest'], ['pets', 'pets.html', 'guest'], ['campaign', null, 'guest'],
        ['walks', 'walks.html', 'volunteer'], ['login', 'login.html', 'guest'], ['cabinet-walks', 'cabinet-walks.html', 'volunteer'],
        ['shelter', 'shelter.html', 'shelter'], ['admin', 'admin.html', 'admin'],
    ];
    for (const [width, suffix] of [[1440, 'desktop'], [390, 'mobile']]) {
        const context = await newContext(browser, width, 2);
        const page = await newPage(context);
        await open(page, 'index.html');
        for (const [name, file, role] of shots) {
            await page.evaluate((r) => localStorage.setItem('vetmoment-demo', JSON.stringify({ role: r, donations: [], bookings: [], applications: [], withdrawn: [], ts: Date.now() })), role);
            let target = file;
            if (!target) {
                await open(page, 'campaigns.html');
                target = await page.$eval('[data-campaign] :is(h2,h3) a', (a) => a.getAttribute('href'));
            }
            await open(page, target);
            await page.screenshot({ path: `${SHOT_DIR}/${name}-${suffix}.png`, fullPage: false });
        }
        await context.close();
    }
    ok(`скриншоты в ${SHOT_DIR}`);
}

const browser = await chromium.launch();
try {
    if (!existsSync(join(DIR, 'index.html'))) throw new Error(`нет ${DIR}/index.html — выполните php artisan mockup:export`);
    await crawl(browser);
    await scenarios(browser);
    if (SHOTS) await screenshots(browser);
} catch (e) {
    fail(`ошибка проверки: ${e.message}`);
} finally {
    await browser.close();
}

console.log(`\nПроверок: ${checks}, ошибок: ${failures.length}`);
process.exit(failures.length ? 1 : 0);
