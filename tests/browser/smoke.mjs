// VetMoment — браузерная smoke-проверка (этап 14) на работающем сайте с демо-данными.
//
//   node tests/browser/smoke.mjs                         # http://localhost:8000
//   BASE_URL=http://localhost:8080 node tests/browser/smoke.mjs
//   node tests/browser/smoke.mjs --screenshots           # + скриншоты 1440 и 390 в docs/screenshots/stage-14/
//
// Нужен Node.js и пакет playwright с Chromium (в проект не добавлен, чтобы `npm ci` в Docker-образе
// не тянул браузер): `npm i --no-save playwright && npx playwright install chromium`.
//
// Что проверяется:
//   1. Обход страниц под ролями «гость / волонтёр / приют / админ»: код ответа < 400, нет ошибок
//      в консоли (в том числе нарушений Content-Security-Policy), нет запросов на чужие домены,
//      нет горизонтальной прокрутки на 360 и 1440 px.
//   2. Сквозные сценарии: пожертвование гостем через демо-банк, заявка «Хочу забрать», запись на выгул,
//      приют видит заявку и запись и подтверждает её, волонтёр получает уведомление.
// Сценарии меняют данные — запускайте на демо-базе (полный сброс: docker compose down -v).

import { createRequire } from 'node:module';
import { mkdirSync } from 'node:fs';

const require = createRequire(import.meta.url);
const { chromium } = require('playwright');

const BASE = (process.env.BASE_URL || 'http://localhost:8000').replace(/\/$/, '');
const HOST = new URL(BASE).host;
const SHOTS = process.argv.includes('--screenshots');
const SHOT_DIR = 'docs/screenshots/stage-14';
const PASSWORD = 'password';

const failures = [];
let checks = 0;

function fail(message) {
    failures.push(message);
    console.log(`  ✗ ${message}`);
}

function ok(message) {
    checks++;
    console.log(`  ✓ ${message}`);
}

/** Страница со слушателями ошибок консоли и внешних запросов. */
async function newPage(context) {
    const page = await context.newPage();
    page.problems = [];
    page.on('console', (msg) => {
        if (msg.type() === 'error') page.problems.push(`консоль: ${msg.text()}`);
    });
    page.on('pageerror', (err) => page.problems.push(`JS: ${err.message}`));
    page.on('request', (req) => {
        const url = new URL(req.url());
        if (['http:', 'https:'].includes(url.protocol) && url.host !== HOST) {
            page.problems.push(`внешний запрос: ${req.url()}`);
        }
    });
    return page;
}

function takeProblems(page, where) {
    for (const p of page.problems.splice(0)) fail(`${where}: ${p}`);
}

async function visit(page, path, { expect = [200] } = {}) {
    const response = await page.goto(BASE + path, { waitUntil: 'load' });
    const status = response?.status() ?? 0;
    if (!expect.includes(status)) fail(`${path}: ответ ${status}, ожидали ${expect.join('/')}`);
    return status;
}

async function noHorizontalScroll(page, path) {
    const extra = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
    if (extra > 0) fail(`${path}: горизонтальная прокрутка ${extra}px при ширине ${page.viewportSize().width}`);
}

async function login(page, login) {
    await visit(page, '/login');
    await page.fill('input[name=identifier]', login);
    await page.fill('input[name=password]', PASSWORD);
    await Promise.all([page.waitForNavigation(), page.click('form[action$="/login"] button[type=submit]')]);
    if (page.url().includes('/login')) throw new Error(`не удалось войти как ${login}`);
}

// Сессии вошедших пользователей: вход ограничен 10 попытками в минуту с одного IP (throttle:login),
// поэтому каждый пользователь входит один раз за прогон.
const sessions = {};

/** Новый контекст браузера (вошедший под user или гость) и страница в нём. */
async function open(browser, user = null, { width = 1440, scale = 1 } = {}) {
    const context = await browser.newContext({
        viewport: { width, height: 900 }, deviceScaleFactor: scale, locale: 'ru-RU', storageState: user ? sessions[user] : undefined,
    });
    const page = await newPage(context);
    if (user && !sessions[user]) {
        await login(page, user);
        sessions[user] = await context.storageState();
    }
    return page;
}

/** Первая ссылка по шаблону адреса на текущей странице (например, первая анкета каталога). */
async function firstLink(page, pattern) {
    const hrefs = await page.$$eval('a[href]', (as) => as.map((a) => a.href));
    const found = hrefs.map((h) => new URL(h)).find((u) => u.host === HOST && pattern.test(u.pathname));
    return found ? found.pathname : null;
}

/** Свободные слоты на странице «Запись на выгул» (радиокнопки slot_id) → адреса страниц слотов. */
async function slotPaths(page) {
    const ids = await page.$$eval('input[name=slot_id]:not([disabled])', (inputs) => inputs.map((i) => i.value));
    return ids.map((id) => `/walks/slots/${id}`);
}

const ROLES = {
    guest: {
        login: null,
        pages: ['/', '/pets', '/pets?species=cat', '/campaigns', '/walks', '/shelters', '/help', '/privacy',
            '/login', '/register', '/forgot-password', '/donate', '/sitemap.xml', '/robots.txt'],
    },
    volunteer: {
        login: 'volunteer',
        pages: ['/cabinet', '/cabinet/applications', '/cabinet/favorites', '/cabinet/donations', '/cabinet/walks',
            '/cabinet/profile', '/notifications', '/pets', '/walks'],
    },
    shelter: {
        login: 'shelter',
        pages: ['/shelter', '/shelter/pets', '/shelter/pets/create', '/shelter/applications', '/shelter/campaigns',
            '/shelter/campaigns/create', '/shelter/donations', '/shelter/slots', '/shelter/slots/templates',
            '/shelter/bookings', '/shelter/profile', '/notifications'],
    },
    admin: {
        login: 'admin',
        pages: ['/admin', '/admin/shelters', '/admin/pets', '/admin/campaigns', '/admin/applications', '/admin/users'],
    },
};

async function crawl(browser) {
    console.log('\n1. Обход страниц');
    for (const [role, { login: user, pages }] of Object.entries(ROLES)) {
        const page = await open(browser, user);

        // Карточки из списков: первая анкета, сбор, приют, слот
        const extra = [];
        if (role === 'guest') {
            for (const [list, re] of [['/pets', /^\/pets\/[^/]+$/], ['/campaigns', /^\/campaigns\/[^/]+$/],
                ['/shelters', /^\/shelters\/[^/]+$/]]) {
                await visit(page, list);
                const link = await firstLink(page, re);
                if (link) extra.push(link); else fail(`${list}: не нашли ни одной карточки`);
            }
            await visit(page, '/walks');
            const [slot] = await slotPaths(page);
            if (slot) extra.push(slot); else fail('/walks: нет свободных слотов');
        }

        let visited = 0;
        for (const path of [...pages, ...extra]) {
            await visit(page, path);
            if (!path.endsWith('.xml') && !path.endsWith('.txt')) {
                await noHorizontalScroll(page, path);
                await page.setViewportSize({ width: 360, height: 800 });
                await noHorizontalScroll(page, path);
                await page.setViewportSize({ width: 1440, height: 900 });
            }
            takeProblems(page, `${role} ${path}`);
            visited++;
        }

        // Чужой раздел — фирменная 403, несуществующий адрес — 404
        if (role === 'volunteer') {
            await visit(page, '/admin', { expect: [403] });
            await visit(page, '/shelter', { expect: [403] });
        }
        if (role === 'guest') await visit(page, '/pets/net-takogo-pitomca', { expect: [404] });
        page.problems.splice(0); // «Failed to load resource: 403/404» — ожидаемо
        ok(`${role}: ${visited} страниц`);
        await page.context().close();
    }
}

async function scenarios(browser) {
    console.log('\n2. Сквозные сценарии');

    // 2.1 Гость жертвует через демо-банк
    {
        const page = await open(browser);
        await visit(page, '/campaigns');
        const campaign = await firstLink(page, /^\/campaigns\/[^/]+$/);
        await visit(page, campaign);
        const before = await page.textContent('main');
        await visit(page, `${campaign}/donate`);
        await page.fill('input[name=amount]', '1500');
        await page.fill('input[name=donor_email]', 'guest@example.kz');
        await page.check('input[name=agree]', { force: true });
        await Promise.all([page.waitForNavigation(), page.click('button[data-amount-submit]')]);
        if (!page.url().includes('/payments/fake/')) fail(`пожертвование: ожидали демо-банк, открылось ${page.url()}`);
        await Promise.all([page.waitForNavigation(), page.click('button[name=outcome][value=success]')]);
        const result = await page.textContent('main');
        if (!/спасибо/i.test(result)) fail('пожертвование: на странице результата нет «Спасибо»');
        await visit(page, campaign);
        if ((await page.textContent('main')) === before) fail('пожертвование: страница сбора не изменилась');
        takeProblems(page, 'пожертвование');
        ok('гость пожертвовал 1 500 ₸ через демо-банк');
        await page.context().close();
    }

    const volunteer = await open(browser, 'volunteer');

    // 2.2 Заявка «Хочу забрать» на питомца приюта «Дом лап»
    let petName = null;
    let domLap = null;
    const comment = `smoke-${Date.now()}`;
    let bookingUrl = null;
    {
        await visit(volunteer, '/shelters');
        const shelterLink = (await volunteer.$$eval('a[href*="/shelters/"]', (as) => as
            .filter((a) => /Дом лап/.test(a.textContent)).map((a) => new URL(a.href).pathname)))[0];
        domLap = shelterLink.split('/').pop();
        await visit(volunteer, shelterLink);
        const pets = await volunteer.$$eval('a[href*="/pets/"]', (as) => [...new Set(as.map((a) => new URL(a.href).pathname))]
            .filter((p) => /^\/pets\/[^/]+$/.test(p)));
        let applied = false;
        for (const pet of pets) {
            await visit(volunteer, pet);
            if (!(await volunteer.$(`a[href*="${pet}/apply"]`))) continue;
            petName = (await volunteer.textContent('h1')).trim();
            await visit(volunteer, `${pet}/apply?type=adoption`);
            await volunteer.selectOption('select[name=living_conditions]', { index: 1 });
            await volunteer.fill('textarea[name=experience]', 'Живу в квартире, был кот 12 лет. Есть опыт ухода.');
            await volunteer.check('input[name=agree]', { force: true });
            await Promise.all([volunteer.waitForNavigation(), volunteer.click(`form[action$="${pet}/apply"] button`)]);
            // Успех — возврат на карточку с сообщением; если заявка уже есть (повторный прогон) — пробуем следующего
            applied = /Заявка отправлена/.test(await volunteer.textContent('body'));
            if (applied) break;
        }
        if (!applied) throw new Error('заявка: не удалось подать заявку ни на одного питомца приюта');
        await visit(volunteer, '/cabinet/applications');
        if (petName && !(await volunteer.textContent('main')).includes(petName)) fail(`заявка: «${petName}» нет в «Мои заявки»`);
        takeProblems(volunteer, 'заявка');
        ok(`волонтёр подал заявку на «${petName}»`);
    }

    // 2.3 Запись на выгул в приют «Дом лап»
    {
        await visit(volunteer, `/walks?shelter=${domLap}`);
        const slots = await slotPaths(volunteer);
        let booked = false;
        for (const slot of slots) {
            await visit(volunteer, slot);
            if (!(await volunteer.$('form[action$="/book"]'))) continue;
            await volunteer.fill('textarea[name=comment]', comment);
            await volunteer.check('input[name=adult]', { force: true });
            await volunteer.check('input[name=rules]', { force: true });
            await Promise.all([volunteer.waitForNavigation(), volunteer.click('form[action$="/book"] button')]);
            booked = /\/walks\/bookings\/\d+/.test(volunteer.url());
            bookingUrl = booked ? new URL(volunteer.url()).pathname : null;
            // На повторном прогоне на ту же базу слот может быть недоступен по правилам записи — берём следующий
            if (booked) break;
        }
        if (!booked) fail('выгул: не удалось записаться ни на один слот');
        if (!slots.length) fail('выгул: нет слотов');
        takeProblems(volunteer, 'выгул');
        if (booked) ok('волонтёр записался на выгул');
    }

    // 2.4 Приют видит заявку и подтверждает запись → волонтёру приходит уведомление
    {
        const shelter = await open(browser, 'shelter');
        await visit(shelter, '/shelter/applications');
        if (petName && !(await shelter.textContent('main')).includes(petName)) fail('приют: новой заявки нет в списке');
        await visit(shelter, '/shelter/bookings');
        const confirm = shelter.locator('tr', { hasText: comment }).locator('form button', { hasText: 'Подтвердить' }).first();
        if (await confirm.count()) {
            await Promise.all([shelter.waitForNavigation(), confirm.click()]);
            ok('приют подтвердил запись на выгул');
        } else {
            fail('приют: нет кнопки «Подтвердить» у новой записи');
        }
        takeProblems(shelter, 'приют');
        await shelter.context().close();

        // Уведомления уходят через очередь — даём воркеру несколько секунд
        let notified = false;
        for (let i = 0; i < 10 && !notified; i++) {
            if (i) await volunteer.waitForTimeout(1000);
            await visit(volunteer, '/notifications');
            notified = /Прогулка подтверждена/.test(await volunteer.textContent('main'));
        }
        if (!notified) fail('уведомления: нет уведомления о подтверждении выгула');
        else ok('волонтёр получил уведомление о подтверждении');
        takeProblems(volunteer, 'уведомления');
        // 2.5 Волонтёр отменяет запись (заодно освобождает лимит активных записей для следующего прогона)
        if (bookingUrl) {
            await visit(volunteer, bookingUrl);
            await volunteer.click('button[data-modal-open=cancel-booking]');
            await Promise.all([volunteer.waitForNavigation(), volunteer.click('form[action$="/cancel"] button')]);
            if (!/отменен/i.test(await volunteer.textContent('main'))) fail('отмена выгула: нет статуса «Отменена»');
            else ok('волонтёр отменил запись');
            takeProblems(volunteer, 'отмена выгула');
        }
    }
    await volunteer.context().close();
}

async function screenshots(browser) {
    console.log('\n3. Скриншоты');
    mkdirSync(SHOT_DIR, { recursive: true });
    const list = [['home', '/', null], ['pets', '/pets', null], ['campaigns', '/campaigns', null], ['walks', '/walks', null],
        ['cabinet-volunteer', '/cabinet', 'volunteer'], ['cabinet-shelter', '/shelter', 'shelter'], ['admin', '/admin', 'admin']];
    for (const [width, suffix, scale] of [[1440, 'desktop', 1], [390, 'mobile', 2]]) {
        for (const [name, path, user] of list) {
            const page = await open(browser, user, { width, scale });
            await visit(page, path);
            await page.screenshot({ path: `${SHOT_DIR}/${name}-${suffix}.png` });
            await page.context().close();
        }
    }
    ok(`скриншоты в ${SHOT_DIR}`);
}

const browser = await chromium.launch();
try {
    console.log(`VetMoment smoke: ${BASE}`);
    await crawl(browser);
    await scenarios(browser);
    if (SHOTS) await screenshots(browser);
} catch (error) {
    fail(`сбой проверки: ${error.message}`);
} finally {
    await browser.close();
}

console.log(`\nИтого: проверок ${checks}, ошибок ${failures.length}`);
process.exit(failures.length ? 1 : 0);
