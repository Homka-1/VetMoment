/*
 * VetMoment — логика статического демо-макета (этап 15). Копируется в mockup/assets/demo.js командой
 * `php artisan mockup:export`. Обычный скрипт без модулей и запросов: работает по file:// в Chrome, Edge, Firefox.
 *
 * Что делает:
 *  - роль посетителя (Гость / Волонтёр / Приют / Админ): переключатель в плашке «Демо-версия», вход по логину,
 *    подмена шапки и подвала на публичных страницах, «охрана» страниц кабинетов;
 *  - имитация действий: пожертвование (растёт прогресс сбора), запись на выгул (занимается место),
 *    заявка на питомца и избранное (появляются в кабинете); остальные формы — подсказка «в демо не сохраняется»;
 *  - фильтры, поиск и сортировка каталога питомцев по данным из assets/data.js;
 *  - состояние — в localStorage (и в window.name — запасной путь для браузеров, где file:// не делит хранилище),
 *    кнопка «Сбросить демо» возвращает всё как было.
 */
(function () {
    'use strict';

    var D = window.DEMO_DATA || { roles: {}, chrome: {}, routes: {}, pets: {}, campaigns: {}, slots: {}, ui: {} };
    var STORE = 'vetmoment-demo';
    var NBSP = ' ';
    var root = document.documentElement;
    var pageFile = root.getAttribute('data-demo-page') || 'index.html';
    var pageRole = root.getAttribute('data-demo-role') || 'guest';

    // ---------------------------------------------------------------- состояние

    function blank() {
        return { role: 'guest', donations: [], bookings: [], applications: [], withdrawn: [], favorites: null, flash: null, ts: 0 };
    }

    function readStorage() {
        try {
            return JSON.parse(window.localStorage.getItem(STORE) || 'null');
        } catch (e) {
            return null;
        }
    }

    function readWindowName() {
        try {
            return window.name.indexOf(STORE + ':') === 0 ? JSON.parse(window.name.slice(STORE.length + 1)) : null;
        } catch (e) {
            return null;
        }
    }

    var fromStorage = readStorage();
    var fromName = readWindowName();
    var saved = fromStorage && (!fromName || (fromStorage.ts || 0) >= (fromName.ts || 0)) ? fromStorage : fromName;
    var state = blank();
    if (saved) {
        for (var k in saved) {
            if (Object.prototype.hasOwnProperty.call(saved, k)) {
                state[k] = saved[k];
            }
        }
    }
    if (!D.roles[state.role]) {
        state.role = 'guest';
    }

    function save() {
        state.ts = Date.now();
        var json = JSON.stringify(state);
        try {
            window.localStorage.setItem(STORE, json);
        } catch (e) { /* хранилище недоступно — остаётся window.name */ }
        try {
            window.name = STORE + ':' + json;
        } catch (e) { /* ignore */ }
    }

    function go(file) {
        save();
        window.location.href = file;
    }

    function flash(tone, title, text) {
        state.flash = { tone: tone, title: title, text: text || '' };
    }

    function roleLabel(role) {
        return (D.roles[role] && D.roles[role].label) || role;
    }

    function home(role) {
        return (D.roles[role] && D.roles[role].home) || 'index.html';
    }

    // ---------------------------------------------------------------- доступ к страницам (до отрисовки)

    if (pageRole !== 'guest' && state.role !== pageRole) {
        if (state.role === 'guest') {
            save();
            window.location.replace('login.html?next=' + encodeURIComponent(pageFile + window.location.search + window.location.hash));
            return;
        }
        // Страница другой роли (например, приют открыл запись на выгул) — смотрим её от имени этой роли.
        state.role = pageRole;
        flash('info', 'Вы смотрите страницу как «' + roleLabel(pageRole) + '»', 'Роль переключена в плашке «Демо-версия» вверху страницы.');
        save();
    }
    if (root.hasAttribute('data-demo-guest-only') && state.role !== 'guest') {
        save();
        window.location.replace(home(state.role));
        return;
    }
    root.setAttribute('data-demo-viewer', state.role);

    // ---------------------------------------------------------------- форматирование

    function money(tiyn) {
        var tenge = Math.floor(Math.abs(tiyn) / 100);
        return String(tenge).replace(/\B(?=(\d{3})+(?!\d))/g, NBSP) + NBSP + '₸';
    }

    function plural(n, forms) {
        var m10 = n % 10;
        var m100 = n % 100;
        var form = m10 === 1 && m100 !== 11 ? forms[0] : (m10 >= 2 && m10 <= 4 && (m100 < 12 || m100 > 14) ? forms[1] : forms[2]);
        return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, NBSP) + NBSP + form;
    }

    function dateText(ts, withTime) {
        var d = new Date(ts);
        var text = new Intl.DateTimeFormat('ru-RU', { day: 'numeric', month: 'long' }).format(d);
        if (withTime) {
            text += ', ' + new Intl.DateTimeFormat('ru-RU', { hour: '2-digit', minute: '2-digit' }).format(d);
        }
        return text;
    }

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function petById(id) {
        for (var slug in D.pets) {
            if (String(D.pets[slug].id) === String(id)) {
                return slug;
            }
        }
        return null;
    }

    function campaignBySlug(slug) {
        for (var id in D.campaigns) {
            if (D.campaigns[id].slug === slug) {
                return id;
            }
        }
        return null;
    }

    function favorites() {
        return state.favorites || (D.favorites || []).slice();
    }

    // ---------------------------------------------------------------- тосты

    function toast(tone, title, text) {
        var region = document.getElementById('toasts');
        if (!region && D.ui.toastRegion) {
            document.body.insertAdjacentHTML('beforeend', D.ui.toastRegion);
            region = document.getElementById('toasts');
        }
        var html = (D.ui.toasts && (D.ui.toasts[tone] || D.ui.toasts.info)) || '';
        if (!region || !html) {
            window.alert(title + (text ? '\n' + text : ''));
            return;
        }
        var box = document.createElement('div');
        box.innerHTML = html;
        var el = box.firstElementChild;
        var t = el.querySelector('[data-toast-title]');
        var x = el.querySelector('[data-toast-text]');
        if (t) { t.textContent = title; }
        if (x) { x.textContent = text || ''; }
        region.appendChild(el);
        if (tone !== 'danger') {
            setTimeout(function () { el.remove(); }, 7000);
        }
    }

    // ---------------------------------------------------------------- плашка «Демо-версия»

    function initBar() {
        var bar = document.querySelector('[data-demo-bar]');
        if (!bar) {
            return;
        }
        bar.querySelectorAll('[data-demo-switch]').forEach(function (button) {
            var role = button.getAttribute('data-demo-switch');
            button.setAttribute('aria-pressed', role === state.role ? 'true' : 'false');
            button.addEventListener('click', function () {
                if (role === state.role) {
                    return;
                }
                state.role = role;
                flash('success', role === 'guest' ? 'Вы вышли из аккаунта' : 'Вы вошли как «' + roleLabel(role) + '»',
                    role === 'guest' ? '' : (D.roles[role].name || '') + ' — демо-пользователь');
                // Публичная страница остаётся (меняется шапка), кабинет — открывается кабинет новой роли.
                go(pageRole === 'guest' && !root.hasAttribute('data-demo-guest-only') ? pageFile + window.location.search : home(role));
            });
        });
        var reset = bar.querySelector('[data-demo-reset]');
        if (reset) {
            reset.addEventListener('click', function () {
                state = blank();
                flash('success', 'Демо сброшено', 'Пожертвования, записи и заявки из демо удалены, вы снова гость.');
                go('index.html');
            });
        }
    }

    // ---------------------------------------------------------------- шапка, меню и подвал роли

    function applyChrome() {
        if (pageRole !== 'guest' || state.role === 'guest' || !D.chrome[state.role]) {
            return;
        }
        var chrome = D.chrome[state.role];
        var actions = document.querySelector('header .ml-auto');
        if (actions) {
            actions.innerHTML = chrome.header;
        }
        var nav = document.querySelector('#mobile-menu nav');
        if (nav) {
            while (nav.nextElementSibling) {
                nav.nextElementSibling.remove();
            }
            nav.insertAdjacentHTML('afterend', chrome.menu);
        }
        var footer = document.querySelector('footer');
        if (footer) {
            footer.innerHTML = chrome.footer;
        }
    }

    // ---------------------------------------------------------------- ссылки, которых нет в макете

    document.addEventListener('click', function (event) {
        var link = event.target.closest('[data-demo-missing]');
        if (!link) {
            return;
        }
        event.preventDefault();
        toast('info', 'Этой страницы нет в демо-версии', 'На сайте здесь откроется ' + link.getAttribute('data-demo-missing') + '.');
    });

    // ---------------------------------------------------------------- GET-формы: фильтры и переходы

    function formParams(form, submitter) {
        var data;
        try {
            data = new FormData(form, submitter || undefined);
        } catch (e) {
            data = new FormData(form);
        }
        var params = {};
        data.forEach(function (value, key) {
            if (typeof value !== 'string' || value === '') {
                return;
            }
            if (/\[\]$/.test(key)) {
                key = key.slice(0, -2);
                (params[key] = params[key] || []).push(value);
            } else {
                params[key] = value;
            }
        });
        return params;
    }

    function routeKey(path, params, keys) {
        var parts = keys.slice().sort().map(function (k) {
            return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]).replace(/%20/g, '+');
        });
        return parts.length ? path + '?' + parts.join('&') : path;
    }

    /** Файл страницы для адреса с параметрами: точное совпадение, иначе — с меньшим числом параметров. */
    function findRoute(path, params) {
        var keys = Object.keys(params).filter(function (k) { return !Array.isArray(params[k]); });
        var subsets = [[]];
        keys.forEach(function (k) {
            subsets = subsets.concat(subsets.map(function (s) { return s.concat([k]); }));
        });
        subsets.sort(function (a, b) { return b.length - a.length; });
        for (var i = 0; i < subsets.length; i++) {
            var key = routeKey(path, params, subsets[i]);
            var file = D.routes[key + '@' + state.role] || D.routes[key];
            if (file) {
                return file;
            }
        }
        return null;
    }

    function handleGet(form, submitter) {
        var path = form.getAttribute('data-demo-get');
        var params = formParams(form, submitter);

        if (path === 'pets') {
            if (document.querySelector('[data-pet-catalog]')) {
                applyCatalog(params, true);
                var sheet = form.closest('dialog');
                if (sheet && sheet.open && window.matchMedia('(max-width: 1023px)').matches) {
                    sheet.close();
                }
                return;
            }
            go('pets.html' + catalogQuery(params));
            return;
        }
        if (path === 'walks/continue') {
            var slot = D.slots[params.slot_id];
            if (!slot) {
                toast('danger', 'Выберите время прогулки', 'Нажмите на свободный слот в календаре.');
                return;
            }
            if (!slot.file) {
                toast('info', 'На это время записаться нельзя', 'Выберите другой слот.');
                return;
            }
            go(slot.file);
            return;
        }

        var file = findRoute(path, params);
        if (file) {
            go(file);
        } else {
            toast('info', 'Этой страницы нет в демо-версии', 'На сайте здесь откроется /' + path + '.');
        }
    }

    // ---------------------------------------------------------------- каталог питомцев

    var catalogFields = ['species', 'sex', 'age', 'size'];

    function catalogQuery(params) {
        var parts = [];
        Object.keys(params).forEach(function (k) {
            [].concat(params[k]).forEach(function (v) {
                parts.push(encodeURIComponent(Array.isArray(params[k]) ? k + '[]' : k) + '=' + encodeURIComponent(v));
            });
        });
        return parts.length ? '?' + parts.join('&') : '';
    }

    function parseQuery(search) {
        var params = {};
        new URLSearchParams(search).forEach(function (value, key) {
            if (value === '') {
                return;
            }
            if (/\[\d*\]$/.test(key)) {
                key = key.replace(/\[\d*\]$/, '');
                (params[key] = params[key] || []).push(value);
            } else {
                params[key] = value;
            }
        });
        return params;
    }

    function petMatches(pet, p) {
        for (var i = 0; i < catalogFields.length; i++) {
            var f = catalogFields[i];
            if (p[f] && p[f].length && p[f].indexOf(pet[f]) === -1) {
                return false;
            }
        }
        if (p.city && pet.city !== p.city) { return false; }
        if (p.shelter && pet.shelter !== p.shelter) { return false; }
        if (p.walk && !pet.walk) { return false; }
        if (p.guardianship && !pet.guardianship) { return false; }
        if (p.q && pet.name.toLowerCase().indexOf(String(p.q).toLowerCase().trim()) === -1) { return false; }
        return true;
    }

    function syncCatalogForm(p) {
        var form = document.getElementById('pet-filters');
        if (!form) {
            return;
        }
        Array.prototype.forEach.call(form.elements, function (el) {
            var name = (el.name || '').replace(/\[\]$/, '');
            if (!name) {
                return;
            }
            if (el.type === 'checkbox' || el.type === 'radio') {
                var value = p[name];
                el.checked = Array.isArray(value) ? value.indexOf(el.value) !== -1 : (value !== undefined && (el.value === value || (value === '1' && el.value === '1')));
            } else if (el.tagName === 'SELECT' || el.type === 'search' || el.type === 'text') {
                el.value = p[name] || (name === 'sort' ? 'waiting' : '');
            }
        });
    }

    function applyCatalog(p, fromForm) {
        var list = document.querySelector('[data-pet-catalog]');
        if (!list) {
            return;
        }
        var items = {};
        Array.prototype.forEach.call(list.children, function (li) {
            var a = li.querySelector('h3 a, h2 a');
            var href = a ? a.getAttribute('href') : '';
            for (var slug in D.pets) {
                if (D.pets[slug].file === href) {
                    items[slug] = li;
                    li.setAttribute('data-pet', slug);
                }
            }
        });
        var order = (D.catalogOrder && D.catalogOrder[p.sort === 'new' ? 'new' : 'waiting']) || Object.keys(items);
        var shown = 0;
        order.forEach(function (slug) {
            var li = items[slug];
            if (!li) {
                return;
            }
            list.appendChild(li);
            var ok = petMatches(D.pets[slug], p);
            li.hidden = !ok;
            shown += ok ? 1 : 0;
        });
        var empty = document.querySelector('[data-catalog-empty]');
        if (empty) {
            empty.hidden = shown > 0;
        }
        list.hidden = shown === 0;
        var found = document.querySelector('h2[aria-live]');
        if (found) {
            found.textContent = 'Найдено: ' + plural(shown, ['питомец', 'питомца', 'питомцев']);
        }
        if (!fromForm) {
            syncCatalogForm(p);
        }
        if (fromForm) {
            try {
                window.history.replaceState(null, '', 'pets.html' + catalogQuery(p));
            } catch (e) { /* file:// в некоторых браузерах не даёт менять адрес — не страшно */ }
        }
    }

    // ---------------------------------------------------------------- POST-формы: имитация действий

    function field(form, name) {
        var el = form.elements[name];
        if (!el) {
            return '';
        }
        if (el.length !== undefined && !el.tagName) {
            for (var i = 0; i < el.length; i++) {
                if (el[i].checked) {
                    return el[i].value;
                }
            }
            return '';
        }
        if (el.type === 'checkbox') {
            return el.checked ? (el.value || 'on') : '';
        }
        return (el.value || '').trim();
    }

    function nextFile() {
        var next = new URLSearchParams(window.location.search).get('next') || '';
        return /^[\w.-]+\.html([?#].*)?$/.test(next) ? next : null;
    }

    function handlePost(form, action, submitter) {
        var m;

        if (action === 'login') {
            var login = field(form, 'identifier').toLowerCase();
            if (!login) {
                toast('danger', 'Введите логин', 'В демо-версии: volunteer, shelter или admin, пароль — любой.');
                return;
            }
            var role = login.indexOf('admin') === 0 ? 'admin' : (login.indexOf('shelter') === 0 ? 'shelter' : 'volunteer');
            state.role = role;
            flash('success', 'Вы вошли как «' + roleLabel(role) + '»', (D.roles[role].name || '') + ' — демо-пользователь');
            go(nextFile() || home(role));
            return;
        }
        if (action === 'register') {
            state.role = 'volunteer';
            flash('success', 'Аккаунт создан', 'В демо-версии вы входите как волонтёр ' + (D.roles.volunteer.name || '') + '.');
            go(home('volunteer'));
            return;
        }
        if (action === 'logout') {
            state.role = 'guest';
            flash('success', 'Вы вышли из аккаунта', '');
            go('index.html');
            return;
        }
        if (action === 'forgot-password') {
            toast('success', 'Ссылка отправлена', 'Если такой email зарегистрирован, письмо придёт в течение пары минут. В демо письма не отправляются.');
            return;
        }

        if ((m = action.match(/^(campaigns|shelters)\/([^/]+)\/donate$/))) {
            donate(form, m[1] === 'campaigns' ? m[2] : null, m[1] === 'shelters' ? m[2] : null);
            return;
        }
        if ((m = action.match(/^walks\/slots\/(\d+)\/book$/))) {
            book(form, m[1]);
            return;
        }
        if ((m = action.match(/^pets\/([^/]+)\/apply$/))) {
            apply(form, m[1]);
            return;
        }
        if ((m = action.match(/^pets\/([^/]+)\/favorite$/))) {
            toggleFavorite(m[1]);
            return;
        }
        if ((m = action.match(/^applications\/(\w+)\/withdraw$/))) {
            state.withdrawn.push(m[1]);
            save();
            form.closest('tr') ? form.closest('tr').remove() : form.remove();
            toast('success', 'Заявка отозвана', 'Приют больше не увидит её в новых.');
            return;
        }
        if (action === 'notifications/read-all') {
            toast('success', 'Все уведомления прочитаны', 'В демо-версии отметка не сохраняется.');
            return;
        }

        var label = submitter ? submitter.textContent.replace(/\s+/g, ' ').trim() : '';
        toast('info', 'Демо-версия: изменения не сохраняются', label
            ? 'На сайте действие «' + label + '» выполнится и сохранится на сервере.'
            : 'На сайте эта форма отправится на сервер.');
    }

    function donate(form, campaignSlug, shelterSlug) {
        var amount = parseInt(field(form, 'amount').replace(/\D/g, ''), 10) || 0;
        if (!amount) {
            var preset = field(form, 'amount_preset');
            amount = parseInt(preset, 10) || 0;
        }
        var email = field(form, 'donor_email');
        if (amount < 200 || amount > 5000000) {
            toast('danger', 'Проверьте сумму', 'От 200 ₸ до 5 000 000 ₸.');
            return;
        }
        if (!/^\S+@\S+\.\S+$/.test(email)) {
            toast('danger', 'Укажите email для квитанции', 'Например, name@mail.kz.');
            return;
        }
        if (!field(form, 'agree')) {
            toast('danger', 'Нужно согласие с условиями оферты', 'Отметьте галочку под формой.');
            return;
        }
        var campaignId = campaignSlug ? campaignBySlug(campaignSlug) : null;
        var campaign = campaignId ? D.campaigns[campaignId] : null;
        state.donations.unshift({
            id: 'd' + Date.now(), campaign: campaignId, shelter: shelterSlug, amount: amount * 100, at: Date.now(),
            anonymous: !!field(form, 'is_anonymous'), user: state.role !== 'guest',
        });
        flash('success', 'Спасибо! Платёж прошёл', money(amount * 100) + (campaign ? ' — сбору «' + campaign.title + '»' : ' — в общий фонд приюта')
            + '. В демо-версии деньги не списываются.');
        go(campaign ? campaign.file : (shelterSlug ? 'shelters-' + shelterSlug + '.html' : 'campaigns.html'));
    }

    function spotsLeft(slotId) {
        var slot = D.slots[slotId];
        if (!slot) {
            return 0;
        }
        var mine = state.bookings.filter(function (b) { return String(b.slot) === String(slotId); }).length;
        return Math.max(0, slot.capacity - slot.booked - mine);
    }

    function book(form, slotId) {
        if (state.bookings.some(function (b) { return String(b.slot) === String(slotId); })) {
            toast('danger', 'Вы уже записаны на это время', 'Запись — в разделе «Мои выгулы».');
            return;
        }
        if (spotsLeft(slotId) <= 0) {
            toast('danger', 'Мест нет', 'Выберите другое время.');
            return;
        }
        if (!field(form, 'adult') || !field(form, 'rules')) {
            toast('danger', 'Отметьте обе галочки', 'Записаться можно с 18 лет и после знакомства с правилами приюта.');
            return;
        }
        state.bookings.unshift({ id: 'b' + Date.now(), slot: slotId, pet: petById(field(form, 'pet_id')), at: Date.now() });
        flash('success', 'Вы записались на выгул', 'Запись ждёт подтверждения приюта. В демо-версии место занято только в этом браузере.');
        go('cabinet-walks.html');
    }

    function apply(form, petSlug) {
        var type = field(form, 'type') || 'adoption';
        if (state.applications.some(function (a) { return a.pet === petSlug && a.type === type && state.withdrawn.indexOf(a.id) === -1; })) {
            toast('danger', 'Такая заявка уже отправлена', 'Её статус — в разделе «Мои заявки».');
            return;
        }
        if (!field(form, 'contact_name') || !field(form, 'contact_phone')) {
            toast('danger', 'Заполните имя и телефон', 'По ним приют свяжется с вами.');
            return;
        }
        if (!field(form, 'agree')) {
            toast('danger', 'Нужно согласие на передачу данных приюту', 'Отметьте галочку под формой.');
            return;
        }
        state.applications.unshift({ id: 'a' + Date.now(), pet: petSlug, type: type, at: Date.now() });
        flash('success', 'Заявка отправлена', 'Приют свяжется с вами по телефону. Статус — в разделе «Мои заявки».');
        go('cabinet-applications.html');
    }

    function toggleFavorite(slug) {
        if (state.role === 'guest') {
            go('login.html?next=' + encodeURIComponent(pageFile + window.location.search));
            return;
        }
        var list = favorites();
        var i = list.indexOf(slug);
        if (i === -1) {
            list.push(slug);
        } else {
            list.splice(i, 1);
        }
        state.favorites = list;
        save();
        syncFavoriteButtons();
        var name = D.pets[slug] ? D.pets[slug].name : 'Питомец';
        toast('success', i === -1 ? name + ' — в избранном' : name + ' — убран из избранного', '');
    }

    window.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }
        var get = form.getAttribute('data-demo-get');
        var post = form.getAttribute('data-demo-action');
        if (get === null && post === null) {
            return;
        }
        event.preventDefault();
        event.stopImmediatePropagation();
        if (get !== null) {
            handleGet(form, event.submitter);
        } else {
            handlePost(form, post, event.submitter);
        }
    }, true);

    // ---------------------------------------------------------------- данные демо на страницах

    function syncCampaigns() {
        document.querySelectorAll('[data-campaign]').forEach(function (box) {
            var c = D.campaigns[box.getAttribute('data-campaign')];
            if (!c) {
                return;
            }
            var extra = 0;
            var count = 0;
            state.donations.forEach(function (d) {
                if (String(d.campaign) === box.getAttribute('data-campaign')) {
                    extra += d.amount;
                    count++;
                }
            });
            if (!count) {
                return;
            }
            var raised = c.raised + extra;
            var percent = c.goal > 0 ? Math.min(100, Math.floor(raised * 100 / c.goal)) : 0;
            box.querySelectorAll('[data-campaign-raised]').forEach(function (el) { el.textContent = money(raised); });
            box.querySelectorAll('[data-campaign-percent], [data-progress-percent]').forEach(function (el) { el.textContent = percent + '%'; });
            box.querySelectorAll('[data-campaign-donors]').forEach(function (el) { el.textContent = plural(c.donors + count, ['человек', 'человека', 'человек']); });
            box.querySelectorAll('[data-progress-text]').forEach(function (el) { el.textContent = money(raised) + ' из ' + money(c.goal); });
            var bars = box.matches('[data-progress-bar]') ? [box] : box.querySelectorAll('[data-progress-bar]');
            Array.prototype.forEach.call(bars, function (bar) {
                bar.setAttribute('aria-valuenow', percent);
                bar.setAttribute('aria-label', 'Собрано ' + percent + '% от цели');
                var fill = bar.querySelector('[data-progress-fill]');
                if (fill) {
                    fill.style.width = percent + '%';
                    var tick = fill.querySelector('span');
                    if (tick) {
                        tick.style.left = 'calc(' + percent + '% - 2px)';
                    }
                }
            });
        });
    }

    function syncSlots() {
        document.querySelectorAll('[data-slot-note]').forEach(function (note) {
            var id = note.getAttribute('data-slot-note');
            if (!D.slots[id] || !state.bookings.some(function (b) { return String(b.slot) === id; })) {
                return;
            }
            var left = spotsLeft(id);
            note.textContent = left > 0 ? 'Осталось ' + plural(left, ['место', 'места', 'мест']) : 'Мест нет';
            var label = note.closest('label');
            var input = label ? label.querySelector('input[type=radio]') : null;
            if (input && left === 0) {
                input.disabled = true;
                input.checked = false;
            }
        });
    }

    function syncFavoriteButtons() {
        if (state.role === 'guest') {
            return;
        }
        var list = favorites();
        document.querySelectorAll('form[data-demo-action$="/favorite"]').forEach(function (form) {
            var slug = form.getAttribute('data-demo-action').replace(/^pets\//, '').replace(/\/favorite$/, '');
            var on = list.indexOf(slug) !== -1;
            var button = form.querySelector('button');
            var name = D.pets[slug] ? D.pets[slug].name : '';
            if (!button) {
                return;
            }
            button.setAttribute('aria-pressed', on ? 'true' : 'false');
            if (button.hasAttribute('aria-label')) {
                button.setAttribute('aria-label', (on ? 'Убрать ' : 'Добавить ') + name + (on ? ' из избранного' : ' в избранное'));
            }
            var use = button.querySelector('use');
            var svg = button.querySelector('svg');
            if (use) {
                use.setAttribute('href', on ? '#i-heart-filled' : '#i-heart');
            }
            if (svg) {
                svg.classList.toggle('text-danger-solid', on);
            }
        });
    }

    function syncFavoritesPage() {
        if (pageFile !== 'cabinet-favorites.html' || !state.favorites) {
            return;
        }
        var list = document.querySelector('[data-favorites-list]');
        var main = document.querySelector('main');
        if (!list && main) {
            var empty = main.querySelector('.bg-canvas');
            list = document.createElement('ul');
            list.className = 'grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-3 xl:gap-6';
            list.setAttribute('data-favorites-list', '');
            (empty ? empty.parentNode : main).insertBefore(list, empty || null);
            if (empty) {
                empty.hidden = true;
            }
        }
        if (!list) {
            return;
        }
        list.innerHTML = state.favorites.filter(function (slug) { return D.pets[slug] && D.pets[slug].card; }).map(function (slug) {
            return '<li class="flex min-w-0">' + D.pets[slug].card + '</li>';
        }).join('');
    }

    function tbody() {
        var table = document.querySelector('main table');
        return table ? table.tBodies[0] || table : null;
    }

    function prependRows(rows) {
        var body = tbody();
        if (!body || !rows.length) {
            return;
        }
        body.insertAdjacentHTML('afterbegin', rows.join(''));
    }

    function syncCabinet() {
        if (state.role !== 'volunteer' && pageRole !== 'volunteer') {
            return;
        }
        if (pageFile === 'cabinet-walks.html') {
            prependRows(state.bookings.map(function (b) {
                var s = D.slots[b.slot] || {};
                var pet = b.pet ? D.pets[b.pet] : null;
                return '<tr><td class="min-w-44"><p class="font-semibold"><time datetime="' + esc(s.iso) + '">' + esc(s.when) + '</time></p>'
                    + '<p class="text-sm text-secondary tabular-nums">' + esc(s.range) + '</p></td>'
                    + '<td class="min-w-44"><div class="flex items-center gap-3">' + (pet ? pet.thumb : D.ui.anyDog)
                    + '<span class="font-semibold">' + esc(pet ? pet.name : 'Любая собака') + '</span></div></td>'
                    + '<td class="min-w-40">' + esc(s.shelterName) + '<p class="text-xs text-secondary">' + esc(s.city) + '</p></td>'
                    + '<td>' + D.ui.badges.booking + '</td>'
                    + '<td class="text-right">' + (s.file ? D.ui.openButton.replace('href="#"', 'href="' + esc(s.file) + '"') : '') + '</td></tr>';
            }));
        }
        if (pageFile === 'cabinet-applications.html') {
            prependRows(state.applications.filter(function (a) { return state.withdrawn.indexOf(a.id) === -1; }).map(function (a) {
                var pet = D.pets[a.pet] || {};
                return '<tr><td class="min-w-48"><div class="flex items-center gap-3">' + (pet.thumb || '')
                    + '<div><a href="' + esc(pet.file) + '" class="link font-semibold">' + esc(pet.name) + '</a>'
                    + '<p class="text-xs text-secondary">' + esc(pet.shelterName) + ', ' + esc(pet.city) + '</p></div></div></td>'
                    + '<td class="whitespace-nowrap">' + esc(D.ui.types[a.type] || a.type) + '</td>'
                    + '<td>' + D.ui.badges.application + '</td>'
                    + '<td class="min-w-56 max-w-72 text-secondary">—</td>'
                    + '<td class="text-right whitespace-nowrap">' + esc(dateText(a.at)) + '</td>'
                    + '<td class="text-right"><form method="POST" action="#" data-demo-action="applications/' + esc(a.id) + '/withdraw">'
                    + D.ui.withdrawButton + '</form></td></tr>';
            }));
        }
        if (pageFile === 'cabinet-donations.html') {
            var mine = state.donations.filter(function (d) { return d.user; });
            prependRows(mine.map(function (d) {
                var c = d.campaign ? D.campaigns[d.campaign] : null;
                return '<tr><td class="whitespace-nowrap">' + esc(dateText(d.at, true)) + '</td>'
                    + '<td class="min-w-60">' + (c ? '<a href="' + esc(c.file) + '" class="font-semibold text-primary hover:underline">' + esc(c.title) + '</a>'
                        + '<p class="text-xs text-secondary">' + esc(c.shelterName) + '</p>' : '<span class="font-semibold">Общий фонд приюта</span>')
                    + '</td><td class="text-right font-semibold whitespace-nowrap tabular-nums">' + money(d.amount) + '</td>'
                    + '<td>' + D.ui.badges.donation + '</td></tr>';
            }));
            var total = document.querySelector('main .type-number');
            if (total && mine.length) {
                var base = parseInt(total.textContent.replace(/\D/g, ''), 10) || 0;
                var added = mine.reduce(function (sum, d) { return sum + d.amount; }, 0);
                total.textContent = money(base * 100 + added);
            }
        }
    }

    function loginHint() {
        var form = document.querySelector('form[data-demo-action="login"]');
        if (!form) {
            return;
        }
        form.insertAdjacentHTML('beforebegin', '<p class="demo-hint"><strong>Демо-входы:</strong> <code>volunteer</code>, <code>shelter</code> или <code>admin</code>, пароль — любой.</p>');
    }

    function showFlash() {
        if (!state.flash) {
            return;
        }
        var f = state.flash;
        state.flash = null;
        save();
        toast(f.tone, f.title, f.text);
    }

    function init() {
        initBar();
        applyChrome();
        syncCampaigns();
        syncSlots();
        syncFavoritesPage();
        syncFavoriteButtons();
        syncCabinet();
        loginHint();
        if (document.querySelector('[data-pet-catalog]')) {
            applyCatalog(parseQuery(window.location.search), false);
        }
        save();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    // Тост — после app.js (кнопка «Закрыть» тоста работает через его обработчики).
    window.addEventListener('load', showFlash);
}());
