#target illustrator
/*
 * VetMoment → Adobe Illustrator
 * ------------------------------------------------------------------------------------------
 * Собирает макеты проекта в нативные файлы Illustrator (.ai):
 *   - открывает подготовленные SVG из design/illustrator/svg/;
 *   - создаёт монтажную область на каждый артборд макета (desktop, mobile, состояния…)
 *     с понятными русскими названиями;
 *   - кладёт каждый артборд в отдельный слой с тем же названием;
 *   - сохраняет .ai (и по желанию PDF) в design/source/.
 *
 * Как запустить:
 *   1. Установите шрифты из design/fonts/desktop/ (выделить все .ttf → правый клик → «Установить
 *      для всех пользователей»), затем перезапустите Illustrator.
 *   2. Illustrator → Файл → Сценарии → Другой сценарий… (Ctrl+F12) → выберите этот файл.
 *   3. Отметьте нужные макеты и нажмите «Собрать».
 *
 * Данные (список файлов, границы и названия артбордов, шрифты) — в manifest.js рядом со скриптом.
 * Его и SVG генерирует design/tools/illustrator.mjs из исходных макетов: после изменения макетов
 * выполните в корне проекта `node design/tools/illustrator.mjs` и запустите скрипт снова.
 *
 * Совместимость: Illustrator CS6 и новее (Windows и macOS). Код — ExtendScript (ES3).
 */

(function () {
    var SCRIPT_NAME = 'VetMoment → Illustrator';

    // --- Пути ----------------------------------------------------------------------------
    var scriptFile = new File($.fileName);
    var root = scriptFile.parent;                       // design/illustrator
    var svgDir = new Folder(root + '/svg');
    var outDir = new Folder(root.parent + '/source');   // design/source
    var fontsDir = new Folder(root.parent + '/fonts/desktop');
    var manifestFile = new File(root + '/manifest.js');

    if (!manifestFile.exists) {
        alert('Не найден manifest.js рядом со скриптом:\n' + manifestFile.fsName +
            '\n\nСгенерируйте его командой в корне проекта:\nnode design/tools/illustrator.mjs', SCRIPT_NAME, true);
        return;
    }

    $.evalFile(manifestFile);
    if (typeof VETMOMENT_MANIFEST === 'undefined') {
        alert('manifest.js повреждён: нет переменной VETMOMENT_MANIFEST.', SCRIPT_NAME, true);
        return;
    }
    var M = VETMOMENT_MANIFEST;

    // --- Проверка шрифтов ------------------------------------------------------------------
    function hasFont(psName) {
        try {
            app.textFonts.getByName(psName);
            return true;
        } catch (e) {
            return false;
        }
    }

    var missing = [];
    for (var f = 0; f < M.fonts.length; f++) {
        if (!hasFont(M.fonts[f])) missing.push(M.fonts[f]);
    }
    if (missing.length) {
        var goOn = confirm(
            'Не установлены шрифты:\n  ' + missing.join('\n  ') +
            '\n\nУстановите все .ttf из папки\n' + fontsDir.fsName +
            '\n(правый клик → «Установить для всех пользователей») и перезапустите Illustrator.' +
            '\n\nПродолжить без них? Illustrator подставит другие шрифты.',
            true, SCRIPT_NAME);
        if (!goOn) return;
    }

    // --- Диалог выбора -------------------------------------------------------------------
    var options = showDialog(M);
    if (!options) return;

    if (!outDir.exists) outDir.create();

    // --- Обработка -------------------------------------------------------------------------
    var prevLevel = app.userInteractionLevel;
    app.userInteractionLevel = UserInteractionLevel.DONTDISPLAYALERTS;

    var done = [];
    var skipped = [];
    var errors = [];

    try {
        for (var i = 0; i < M.files.length; i++) {
            if (!options.selected[i]) continue;
            var entry = M.files[i];
            var aiFile = new File(outDir + '/' + entry.ai);

            if (aiFile.exists && !options.overwrite) {
                skipped.push(entry.ai);
                continue;
            }

            try {
                buildFile(entry, aiFile, options);
                done.push(entry.ai + ' — ' + entry.artboards.length + ' арт.');
            } catch (err) {
                errors.push(entry.ai + ': ' + err.message + (err.line ? ' (строка ' + err.line + ')' : ''));
            }
        }
    } finally {
        app.userInteractionLevel = prevLevel;
    }

    var report = 'Готово. Папка: ' + outDir.fsName + '\n\n';
    if (done.length) report += 'Собрано (' + done.length + '):\n  ' + done.join('\n  ') + '\n\n';
    if (skipped.length) report += 'Пропущено — файл уже есть (' + skipped.length + '):\n  ' + skipped.join('\n  ') + '\n\n';
    if (errors.length) report += 'Ошибки (' + errors.length + '):\n  ' + errors.join('\n  ') + '\n\n';
    if (missing.length) report += 'Внимание: были не установлены шрифты ' + missing.join(', ') + '.';
    alert(report, SCRIPT_NAME, errors.length > 0);

    // =========================================================================================

    function buildFile(entry, aiFile, opts) {
        var svgFile = new File(svgDir + '/' + entry.svg);
        if (!svgFile.exists) throw new Error('нет файла ' + svgFile.fsName);

        var doc = app.open(svgFile);
        try {
            // После импорта SVG первая монтажная область = viewBox документа.
            // Координаты скрипта: X вправо, Y вверх; в SVG Y растёт вниз — отсюда «T - y».
            var base = doc.artboards[0].artboardRect;
            var L = base[0];
            var T = base[1];
            var abs = entry.artboards;

            for (var a = 0; a < abs.length; a++) {
                var ab = abs[a];
                var rect = [L + ab.x, T - ab.y, L + ab.x + ab.w, T - ab.y - ab.h];
                var board = (a === 0) ? doc.artboards[0] : doc.artboards.add(rect);
                board.artboardRect = rect;
                board.name = ab.name;
            }

            arrangeLayers(doc, abs);

            if (abs.length) doc.artboards.setActiveArtboardIndex(0);

            var save = new IllustratorSaveOptions();
            save.pdfCompatible = true;
            save.compressed = true;
            save.embedLinkedFiles = true;
            save.fontSubsetThreshold = 100.0;
            doc.saveAs(aiFile, save);

            if (opts.pdf) {
                var pdfFile = new File(outDir + '/' + entry.ai.replace(/\.ai$/i, '.pdf'));
                var pdf = new PDFSaveOptions();
                pdf.preserveEditability = false;
                pdf.generateThumbnails = true;
                pdf.viewAfterSaving = false;
                doc.saveAs(pdfFile, pdf);
            }
        } finally {
            doc.close(SaveOptions.DONOTSAVECHANGES);
        }
    }

    // Каждый артборд-группу (<g id="artboard-…">) — в отдельный слой с русским названием.
    function arrangeLayers(doc, abs) {
        var originalLayers = [];
        for (var l = 0; l < doc.layers.length; l++) originalLayers.push(doc.layers[l]);

        var groups = indexGroups(doc);
        var moved = 0;

        // Слои создаём в обратном порядке, чтобы в панели «Слои» первый артборд был сверху.
        for (var a = abs.length - 1; a >= 0; a--) {
            var ab = abs[a];
            if (!ab.id) continue;
            var grp = groups[key(ab.id)];
            if (!grp) continue;

            var layer = doc.layers.add();
            layer.name = ab.name;
            grp.name = ab.name;
            grp.move(layer, ElementPlacement.PLACEATBEGINNING);
            moved++;
        }

        // Исходные слои импорта: пустые удаляем, остальное (служебные определения) подписываем.
        for (var o = 0; o < originalLayers.length; o++) {
            var lay = originalLayers[o];
            try {
                if (lay.pageItems.length === 0 && lay.layers.length === 0) {
                    lay.remove();
                } else if (moved > 0) {
                    lay.name = 'Прочее';
                }
            } catch (e) { /* слой мог быть заблокирован — оставляем как есть */ }
        }
    }

    // Illustrator может закодировать id при импорте (например, «-» → «_x2D_»), поэтому
    // сравниваем имена без служебных символов.
    function key(name) {
        return String(name).replace(/_x2D_/g, '').replace(/[^A-Za-z0-9]/g, '').toLowerCase();
    }

    function indexGroups(doc) {
        var map = {};
        var all = doc.groupItems;
        for (var g = 0; g < all.length; g++) {
            var item = all[g];
            var k = key(item.name);
            if (k.indexOf('artboard') === 0 && !map[k]) map[k] = item;
        }
        return map;
    }

    function showDialog(manifest) {
        var w = new Window('dialog', SCRIPT_NAME);
        w.orientation = 'column';
        w.alignChildren = ['fill', 'top'];

        var info = w.add('statictext', undefined,
            'Макеты будут сохранены в design/source как .ai (по файлу на группу экранов,\n' +
            'монтажная область на каждый артборд). Данные от ' + manifest.generated + '.',
            { multiline: true });
        info.preferredSize = [460, 36];

        var panel = w.add('panel', undefined, 'Макеты');
        panel.alignChildren = ['fill', 'top'];
        var checks = [];
        for (var i = 0; i < manifest.files.length; i++) {
            var it = manifest.files[i];
            var cb = panel.add('checkbox', undefined,
                it.ai + '  —  ' + it.title + '  (' + it.artboards.length + ' арт.)');
            cb.value = true;
            checks.push(cb);
        }

        var allRow = w.add('group');
        var bAll = allRow.add('button', undefined, 'Выбрать все');
        var bNone = allRow.add('button', undefined, 'Снять все');
        bAll.onClick = function () { for (var c = 0; c < checks.length; c++) checks[c].value = true; };
        bNone.onClick = function () { for (var c = 0; c < checks.length; c++) checks[c].value = false; };

        var cbOverwrite = w.add('checkbox', undefined, 'Перезаписывать существующие .ai');
        cbOverwrite.value = true;
        var cbPdf = w.add('checkbox', undefined, 'Дополнительно сохранить PDF');
        cbPdf.value = false;

        var buttons = w.add('group');
        buttons.alignment = 'right';
        buttons.add('button', undefined, 'Отмена', { name: 'cancel' });
        buttons.add('button', undefined, 'Собрать', { name: 'ok' });

        if (w.show() !== 1) return null;

        var selected = [];
        var any = false;
        for (var s = 0; s < checks.length; s++) {
            selected.push(checks[s].value);
            if (checks[s].value) any = true;
        }
        if (!any) return null;

        return { selected: selected, overwrite: cbOverwrite.value, pdf: cbPdf.value };
    }
})();
