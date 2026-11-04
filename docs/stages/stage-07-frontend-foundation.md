# Этап 7. Фронтенд-основа: тема, layout, Blade-компоненты

Дата: 2026-11-04 · Статус: ✅ готов

## Цель этапа
Перенести дизайн этапов 2–3 в код: токены → `@theme`, локальные шрифты и иконки, layout сайта и кабинетов,
UI-кит в Blade-компонентах, минимальный JS и витрина `/styleguide`, чтобы этапы 8–11 собирали страницы
из готовых деталей.

## Что сделано
- **Тема** (`resources/css/app.css`): все токены `design/tokens.json` в `@theme` — палитра ink/tag/sky/meadow,
  тона состояний, роли (`bg-canvas`, `text-muted`, `border-subtle`, `bg-path-donate`…), шкала текста 12…61,
  межстрочные, радиусы xs/sm/md/lg/pill, тени sm/md/lg, высоты контролов (`h-control`, `h-field`),
  контейнеры (`max-w-page`, `max-w-narrow`), кривые движения. **Палитра, шкала текста, радиусы и тени
  Tailwind по умолчанию отключены** (`--color-*: initial` и т.п.) — в шаблонах доступны только токены.
  Утилиты: `type-h1…h4`, `type-lead`, `type-label`, `type-caption`, `type-number` (адаптивные размеры из
  `typography`), `page-container` (1280 + поля 16/24/32), `link`, `border-control` (1.5 px). Фокус — кольцо
  3 px `sky-600` с отступом 2 px глобально; `prefers-reduced-motion` отключает анимации.
- **Шрифты** Unbounded 500/600 и Golos Text 400–700 — локально (`resources/fonts/`, `resources/css/fonts.css`),
  Vite кладёт woff2 в `public/build`; без Google Fonts. Unbounded 700 не подключён (не используется).
- **Иконки**: спрайт `resources/svg/icons.svg` (из `design/icons/icons.svg`, без `<title>`) встраивается в layout,
  `<x-icon name="paw" size="sm|md|lg" label="…">`.
- **Логотип и фавиконы** — `public/images/brand/`, `public/favicon.svg`, PNG 32/180.
- **Иллюстрации** для пустых состояний, входа и заглушек фото — `public/images/illustrations/{dog,cat}-*.svg`
  (генератор `design/tools/illustrations.mjs` из тех же функций, что UI-кит).
- **Layout'ы** (`components/layouts/`): `app` (шапка, контент, подвал, мобильное меню, тосты, ссылка
  «Перейти к содержанию»), `auth` (форма + бирочная панель с портретом, макет 08), `cabinet` (боковое меню 256,
  на телефоне — «таблетки»). Части — `components/site/`: `head`, `header`, `footer`, `mobile-menu`, `icon-sprite`.
- **Компоненты UI-кита** (`resources/views/components/`): `button`, `input`, `select`, `textarea`, `checkbox`,
  `radio`, `toggle`, `chip`, `amount-picker`, `form.field`, `form-errors`, `badge`, `card`, `pet-card`,
  `pet-card-skeleton`, `campaign-card`, `shelter-card`, `help-path`, `progress`, `walk-slot`, `day-picker`,
  `modal`, `toast`, `flash`, `alert`, `empty-state`, `pagination` (+ вид `pagination/vetmoment` по умолчанию),
  `breadcrumbs`, `tabs`, `avatar`, `table` (+ `table.th`), `sticky-bar`, `gallery`, `logo`, `icon`.
- **JS без фреймворков** (`resources/js/ui/*`, 3.7 КБ): модалки и мобильное меню на `<dialog>`, выпадающее меню
  профиля на `<details>`, тосты (закрытие, автоскрытие, `VetMoment.toast()`), счётчик символов, пресеты суммы,
  галерея, выбор дня → панель слотов. Всё на data-атрибутах и работает без JS.
- **Хелперы форматирования** (долг этапа 4): `App\Support\Money` (`1 500 ₸`, неразрывные пробелы, тиыны),
  `App\Support\Plural` (склонения, возраст), `App\Support\LocalTime` (UTC → Asia/Almaty, «8 ноября, 10:00»,
  «10:00–11:00», «сб»). В `Pet` — `ageLabel()` и `summary()` («Собака, 2 года, мальчик»).
- **Навигация** — `App\Support\Navigation`: разделы шапки/меню/подвала и меню кабинетов по ролям.
- **Переверстаны** страницы этапа 6 по макетам: вход, регистрация, «забыли пароль», новый пароль, заявка приюта,
  стартовые страницы кабинетов. `layouts/stub.blade.php` удалён. Главная — герой и «Как помочь» по макету 01.
- **Витрина** `/styleguide` — все компоненты с данными в памяти (без БД); в `production` — 404.
- Тесты: `MoneyTest`, `PluralTest`, `LocalTimeTest`, `PetSummaryTest`, `Ui/LayoutTest`, `Ui/StyleguideTest`.

## Изменённые / созданные файлы
| Файл | Что |
|------|-----|
| `resources/css/app.css`, `resources/css/fonts.css`, `resources/fonts/*` | тема из токенов, шрифты |
| `resources/svg/icons.svg`, `public/images/**`, `public/favicon*`, `public/apple-touch-icon.png` | иконки, логотипы, иллюстрации |
| `resources/views/components/**` | layout'ы, части сайта, UI-кит |
| `resources/views/pagination/vetmoment.blade.php` | вид пагинации |
| `resources/views/auth/*`, `cabinet/*`, `home.blade.php`, `styleguide.blade.php` | страницы |
| `resources/js/app.js`, `resources/js/ui/*` | JS интерфейса |
| `app/Support/{Money,Plural,LocalTime,Navigation}.php`, `app/Models/Pet.php` | хелперы |
| `app/Http/Controllers/StyleguideController.php`, `routes/web.php` | витрина |
| `app/Providers/AppServiceProvider.php` | пагинация по умолчанию |
| `design/tools/illustrations.mjs` | генератор иллюстраций |
| `tests/Unit/Support/*`, `tests/Unit/Models/PetSummaryTest.php`, `tests/Feature/Ui/*` | тесты |
| `docs/PLAN.md`, `README.md` | статус, публичные страницы без этапа, витрина |
| `docs/screenshots/stage-07/*` | скриншоты 1440 и 390 |

## Принятые решения
- **Чистый JS вместо Alpine.js**: интерактива мало (модалки, меню, тосты, галерея, день), `<dialog>` и
  `<details>` дают фокус, Esc и доступность бесплатно; те же модули без сборщика пойдут в статический макет (этап 15).
- **Палитра Tailwind отключена** — «магическое» `bg-blue-500` просто не соберётся. Значения не из токенов
  остались только там, где это размеры раскладки (`max-w-[480px]` у модалки, `h-[72px]` у дня) и цвета нажатия
  кнопок из UI-кита — они добавлены в `@theme` как `tag-press`, `danger-hover`, `danger-press`.
- **Компонент слота — `<x-walk-slot>`**: имя `x-slot` зарезервировано Blade.
- **Слот, день, чип, пресет суммы — нативные `radio`/`checkbox`** со стилями через `has-checked:`; выбор
  работает без JS и отправляется формой.
- **Карточки принимают модели** (`:pet`, `:campaign`, `:shelter`) и читают только загруженные связи
  (`relationLoaded`) — нет скрытых запросов; ссылки строятся по маршрутам `pets.show` / `campaigns.show` /
  `shelters.show`, когда они появятся (пока `#`).
- **Нет фото — иллюстрация** на фоне одного из тонов (стабильно по кличке), как в макетах.
- **Разделы будущих этапов в шапке** ведут на URL из карты сайта (`/pets`, `/campaigns`…), пока маршрутов нет
  (ответ 404). В меню кабинета, наоборот, показываются только разделы с готовыми маршрутами — иначе кабинет
  состоял бы из битых ссылок.
- **Flash → тосты**: `session('status')` — успех, `error` — ошибка, `info`, `toast` (массив). Ошибки не скрываются сами.
- **Сводка ошибок + ошибка у поля**: в длинных формах (`x-form-errors`), в коротких (вход) — только у поля.
- **Спрайт читается с диска на каждый запрос** (12 КБ), а не кэшируется: кэш в томе `storage` пережил бы
  обновление образа.

## Отклонения от плана
- Компонентов больше, чем в списке плана (+ checkbox, radio, toggle, chip, amount-picker, alert, toast, tabs,
  breadcrumbs, avatar, table, sticky-bar, gallery, shelter-card, help-path, day-picker, skeleton) — по составу UI-кита.
- Сделаны хелперы форматирования (Money, Plural, LocalTime) — были отложены на этот этап заметкой этапа 4.
- Главная: только статичные блоки; блоки с данными, `/shelters`, `/help`, `/privacy` не были ни в одном этапе —
  добавлены в этап 11 плана.
- Фирменные страницы ошибок не делались (этап 13).

## Как проверялось
Локальный PHP — 8.3, поэтому тесты гонялись в контейнере `php:8.4-cli`; сайт для скриншотов — тот же контейнер
(`artisan serve`, SQLite, `migrate:fresh --seed`), браузер — Playwright/Chromium.

| Проверка | Результат |
|----------|-----------|
| `vendor/bin/phpunit` (SQLite in-memory) | ✅ 205 тестов, 1084 проверки |
| `./vendor/bin/pint --test` | ✅ |
| `npm run build` | ✅ CSS 69 КБ (12.5 gzip), JS 3.7 КБ, шрифты из `resources/fonts` |
| Скриншоты 1440 и 390: главная, витрина, вход (+ ошибки), регистрация, кабинеты волонтёра и приюта, заявка приюта, мобильное меню | ✅ `docs/screenshots/stage-07/`, ошибок в консоли нет |
| Горизонтальная прокрутка на 360 и 390 px (скрипт по всем элементам) | ✅ нет |
| Сверка с UI-китом (артборды 02–05) и макетами 01, 08 | ✅ визуально совпадает, отличия ниже |
| `docker compose up --build` | ⚠️ локально не проверялся (сеть окружения) — проверит CI; новых шагов запуска нет, `resources/` и `public/images` уже попадают в образ |

## Известные проблемы и долги
- Отличия от UI-кита: процент сбора округляется вниз (`progressPercent()` этапа 5: 68% вместо 69% в макете);
  `<input type=file>` подписан языком браузера; у полей входа есть звёздочка «обязательно» (в макете нет).
- Ссылки шапки на разделы этапов 8–11 пока дают 404.
- Счётчик уведомлений в шапке — `unreadNotifications()->count()` (один запрос на страницу), страница уведомлений — этап 12.
- Тёмной темы нет (не проектировалась).

## Что важно знать на следующем этапе
- Страница: `<x-layouts.app title="…">` (публичная), `<x-layouts.cabinet title="…">` (+ слот `actions`),
  `<x-layouts.auth>`. Контейнер — класс `page-container`, заголовки — `type-h1…h4`, суммы — `type-number`.
- Карточки: `<x-pet-card :pet="$pet" :favorite-url="…" :favorited="…">` — загрузите `cover` и `shelter`
  (`with(['cover', 'shelter'])`); `<x-campaign-card :campaign>` — `with('shelter')`; `<x-shelter-card :shelter>` —
  `withCount(['pets', 'campaigns'])`. Маршруты `pets.show`, `campaigns.show`, `shelters.show` подхватятся сами.
- Формы: `<x-input name="…" label="…" hint="…">` сам берёт `old()` и ошибку из `$errors` (имена `a[b]` → `a.b`);
  id поля — `f-<name>`. Сумма пожертвования — `<x-amount-picker name="amount">` отправляет тенге строкой
  с пробелами → `Money::fromTenge()`.
- Фильтры каталога — `<x-chip name="filter[]" value="…">` внутри GET-формы; пагинация — `<x-pagination :paginator>`.
- Выгул (этап 10): `<x-day-picker :days>` + панели `data-day-panel="Y-m-d"` в общем `data-day-scope`;
  слоты — `<x-walk-slot :model="$slot" name="slot_id">`.
- Подтверждения — `<x-modal id>` + кнопка `data-modal-open="id"`; результат действия — `session('status')`
  с тем же глаголом, что на кнопке.
- В сетках с вкладками/лентами давайте колонкам `min-w-0` или `grid-cols-1`, иначе на телефоне появится
  горизонтальная прокрутка.
- Даты в шаблонах — только через `LocalTime`, деньги — через `Money::format()`.
