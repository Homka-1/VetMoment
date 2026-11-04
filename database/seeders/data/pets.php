<?php

/*
| Демо-питомцы. Поля:
|   shelter — код приюта из shelters.php; age — возраст в месяцах (≈ — приблизительно);
|   fur — окрас портрета-иллюстрации (database/seeders/photos/{species}-{fur}-*.jpg);
|   traits — включённые черты (good_with_kids, good_with_cats, good_with_dogs, house_trained, leash_trained);
|   health — vaccinated/sterilized/chipped; waited — сколько дней в приюте.
*/

$all = ['vaccinated', 'sterilized', 'chipped'];

return [
    // ── «Дом лап», Алматы ──────────────────────────────────────────────
    [
        'key' => 'bublik', 'shelter' => 'dom-lap', 'name' => 'Бублик', 'species' => 'dog', 'sex' => 'male', 'age' => 24,
        'size' => 'medium', 'fur' => 'caramel', 'color' => 'рыжий с белым', 'status' => 'looking_for_home',
        'traits' => ['good_with_kids', 'good_with_dogs', 'house_trained', 'leash_trained'], 'health' => $all,
        'guardianship' => true, 'waited' => 230,
        'character' => 'Спокойный и ласковый, ладит с детьми и другими собаками. Знает команды «сидеть» и «рядом», '
            .'любит долгие прогулки и чесание за ухом.',
        'story' => 'Бублика нашли в марте у рынка «Алтын Орда»: худой, с порезом на лапе. За полгода в приюте он '
            .'поправился, выучил команды «сидеть» и «рядом» и полюбил долгие прогулки. Ищет семью, где с ним будут много гулять.',
    ],
    [
        'key' => 'graf', 'shelter' => 'dom-lap', 'name' => 'Граф', 'species' => 'dog', 'sex' => 'male', 'age' => 72,
        'size' => 'large', 'fur' => 'graphite', 'color' => 'чёрный', 'status' => 'reserved',
        'traits' => ['good_with_kids', 'leash_trained'], 'health' => $all, 'guardianship' => true, 'waited' => 410,
        'character' => 'Активный и умный, любит бегать и играть с мячом. С незнакомыми сначала сдержан, '
            .'но быстро привязывается.',
        'story' => 'Граф попал под машину на трассе Алматы — Капшагай. После операции на лапе восстанавливается, '
            .'уже бегает. Его забронировала семья из Каскелена — ждём, когда закончится лечение.',
        'health_notes' => 'После металлоостеосинтеза: пластина и спицы. Нужны осмотры раз в месяц.',
    ],
    [
        'key' => 'romashka', 'shelter' => 'dom-lap', 'name' => 'Ромашка', 'species' => 'dog', 'sex' => 'female', 'age' => 48,
        'size' => 'medium', 'fur' => 'caramel', 'color' => 'палевая', 'status' => 'on_treatment',
        'traits' => ['good_with_kids', 'good_with_cats', 'house_trained'], 'health' => ['vaccinated', 'sterilized'],
        'guardianship' => true, 'waited' => 95,
        'character' => 'Добрая и послушная, ходит за людьми хвостиком. Ладит с кошками и детьми.',
        'story' => 'Ромашку сбила машина у остановки на Абая. Ей нужна операция на лапе — на неё идёт сбор.',
        'health_notes' => 'Перелом задней лапы, готовится к операции в клинике «Вет-Мед».',
    ],
    [
        'key' => 'tosha', 'shelter' => 'dom-lap', 'name' => 'Тоша', 'species' => 'dog', 'sex' => 'male', 'age' => 30, 'approx' => true,
        'size' => 'medium', 'fur' => 'graphite', 'color' => 'чёрный с подпалом', 'status' => 'looking_for_home',
        'traits' => ['good_with_dogs', 'leash_trained'], 'health' => $all, 'guardianship' => false, 'waited' => 160,
        'character' => 'Весёлый и любопытный, обожает новые места и других собак. Отлично ходит на поводке.',
        'story' => 'Тошу подбросили к воротам приюта щенком. Вырос общительным и очень любит прогулки.',
    ],
    [
        'key' => 'naida', 'shelter' => 'dom-lap', 'name' => 'Найда', 'species' => 'dog', 'sex' => 'female', 'age' => 60, 'approx' => true,
        'size' => 'large', 'fur' => 'caramel', 'color' => 'рыжая', 'status' => 'looking_for_home',
        'traits' => ['good_with_kids', 'leash_trained'], 'health' => $all, 'guardianship' => true, 'waited' => 540,
        'character' => 'Спокойная и преданная, хороший охранник. Подойдёт для частного дома с вольером.',
        'story' => 'Найда жила на стройке и охраняла её, пока стройку не закрыли. В приюте уже полтора года.',
    ],
    [
        'key' => 'bim', 'shelter' => 'dom-lap', 'name' => 'Бим', 'species' => 'dog', 'sex' => 'male', 'age' => 14,
        'size' => 'small', 'fur' => 'caramel', 'color' => 'белый с рыжими пятнами', 'status' => 'looking_for_home',
        'traits' => ['good_with_kids', 'good_with_cats', 'good_with_dogs'], 'health' => ['vaccinated', 'chipped'],
        'guardianship' => false, 'waited' => 40,
        'character' => 'Маленький непоседа: играет со всеми, любит игрушки-пищалки. Учится ходить на поводке.',
        'story' => null,
    ],
    [
        'key' => 'jessie', 'shelter' => 'dom-lap', 'name' => 'Джесси', 'species' => 'dog', 'sex' => 'female', 'age' => 108, 'approx' => true,
        'size' => 'medium', 'fur' => 'graphite', 'color' => 'чёрная с сединой', 'status' => 'looking_for_home',
        'traits' => ['good_with_kids', 'good_with_cats', 'house_trained', 'leash_trained'], 'health' => $all,
        'guardianship' => true, 'waited' => 700,
        'character' => 'Мудрая и неторопливая, любит лежать рядом с человеком. Идеальная компаньонка для спокойной семьи.',
        'story' => 'Джесси отдали в приют хозяева, которые уехали из страны. Она ждёт новый дом дольше всех.',
    ],
    [
        'key' => 'syoma', 'shelter' => 'dom-lap', 'name' => 'Сёма', 'species' => 'cat', 'sex' => 'male', 'age' => 36,
        'size' => 'small', 'fur' => 'smoke', 'color' => 'серый полосатый', 'status' => 'looking_for_home',
        'traits' => ['good_with_cats', 'house_trained'], 'health' => $all, 'guardianship' => false, 'waited' => 120,
        'character' => 'Независимый, но ласковый по вечерам. Приучен к лотку и когтеточке.',
        'story' => null,
    ],
    [
        'key' => 'luna', 'shelter' => 'dom-lap', 'name' => 'Луна', 'species' => 'cat', 'sex' => 'female', 'age' => 18,
        'size' => 'small', 'fur' => 'ginger', 'color' => 'рыжая', 'status' => 'looking_for_home',
        'traits' => ['good_with_kids', 'good_with_cats', 'house_trained'], 'health' => $all, 'guardianship' => false, 'waited' => 60,
        'character' => 'Игривая и разговорчивая, встречает людей у двери и любит сидеть на руках.',
        'story' => null,
    ],
    [
        'key' => 'altai', 'shelter' => 'dom-lap', 'name' => 'Алтай', 'species' => 'dog', 'sex' => 'male', 'age' => 40,
        'size' => 'large', 'fur' => 'graphite', 'color' => 'чёрный', 'status' => 'adopted', 'adopted_days_ago' => 20,
        'traits' => ['good_with_kids', 'leash_trained'], 'health' => $all, 'guardianship' => false, 'waited' => 300,
        'character' => 'Большой, добрый и спокойный пёс, любит детей.',
        'story' => 'Алтай нашёл дом в Талгаре — теперь у него большой двор и двое маленьких друзей.',
    ],
    [
        'key' => 'chernysh', 'shelter' => 'dom-lap', 'name' => 'Черныш', 'species' => 'dog', 'sex' => 'male', 'age' => 6, 'approx' => true,
        'size' => 'small', 'fur' => 'graphite', 'color' => 'чёрный', 'status' => 'draft',
        'traits' => [], 'health' => [], 'guardianship' => false, 'waited' => 3,
        'character' => 'Щенок, только поступил в приют. Анкета заполняется.',
        'story' => null,
    ],

    // ── «Мурлыка», Астана ─────────────────────────────────────────────
    [
        'key' => 'myata', 'shelter' => 'murlyka', 'name' => 'Мята', 'species' => 'cat', 'sex' => 'female', 'age' => 12,
        'size' => 'small', 'fur' => 'smoke', 'color' => 'дымчатая', 'status' => 'on_treatment',
        'traits' => ['good_with_kids', 'house_trained'], 'health' => ['vaccinated'], 'guardianship' => true, 'waited' => 45,
        'character' => 'Ласковая и тихая, мурлычет, как только берёшь на руки.',
        'story' => 'Мяту принесли из подъезда с простудой. Долечивается и скоро будет готова к переезду.',
        'health_notes' => 'Лечение ринотрахеита, курс заканчивается через две недели.',
    ],
    [
        'key' => 'ryzhik', 'shelter' => 'murlyka', 'name' => 'Рыжик', 'species' => 'cat', 'sex' => 'male', 'age' => 36,
        'size' => 'small', 'fur' => 'ginger', 'color' => 'рыжий', 'status' => 'looking_for_home',
        'traits' => ['good_with_kids', 'good_with_cats', 'house_trained'], 'health' => $all, 'guardianship' => false, 'waited' => 150,
        'character' => 'Игривый и общительный, приучен к лотку. Любит охотиться на бантики.',
        'story' => null,
    ],
    [
        'key' => 'persik', 'shelter' => 'murlyka', 'name' => 'Персик', 'species' => 'cat', 'sex' => 'male', 'age' => 7,
        'size' => 'small', 'fur' => 'ginger', 'color' => 'персиковый', 'status' => 'looking_for_home',
        'traits' => ['good_with_kids', 'good_with_cats', 'good_with_dogs'], 'health' => ['vaccinated'], 'guardianship' => false, 'waited' => 30,
        'character' => 'Любопытный котёнок, всё исследует и всех любит.',
        'story' => null,
    ],
    [
        'key' => 'iriska', 'shelter' => 'murlyka', 'name' => 'Ириска', 'species' => 'cat', 'sex' => 'female', 'age' => 24,
        'size' => 'small', 'fur' => 'ginger', 'color' => 'трёхцветная', 'status' => 'looking_for_home',
        'traits' => ['house_trained'], 'health' => $all, 'guardianship' => true, 'waited' => 210,
        'character' => 'Немного стеснительная, но очень нежная. Нужен спокойный дом без маленьких детей.',
        'story' => null,
    ],
    [
        'key' => 'dymka', 'shelter' => 'murlyka', 'name' => 'Дымка', 'species' => 'cat', 'sex' => 'female', 'age' => 60, 'approx' => true,
        'size' => 'small', 'fur' => 'smoke', 'color' => 'серая', 'status' => 'looking_for_home',
        'traits' => ['good_with_cats', 'house_trained'], 'health' => $all, 'guardianship' => true, 'waited' => 480,
        'character' => 'Спокойная взрослая кошка, любит подоконник и тёплый плед.',
        'story' => null,
    ],
    [
        'key' => 'pushok', 'shelter' => 'murlyka', 'name' => 'Пушок', 'species' => 'cat', 'sex' => 'male', 'age' => 108, 'approx' => true,
        'size' => 'small', 'fur' => 'smoke', 'color' => 'серый пушистый', 'status' => 'looking_for_home',
        'traits' => ['good_with_kids', 'good_with_cats', 'good_with_dogs', 'house_trained'], 'health' => $all,
        'guardianship' => true, 'waited' => 650,
        'character' => 'Пожилой добряк. Ладит со всеми, любит, когда его вычёсывают.',
        'story' => 'Пушок прожил 8 лет в семье, а потом хозяйка умерла. Ему очень нужен свой человек.',
    ],
    [
        'key' => 'mars', 'shelter' => 'murlyka', 'name' => 'Марс', 'species' => 'cat', 'sex' => 'male', 'age' => 20,
        'size' => 'small', 'fur' => 'ginger', 'color' => 'рыжий', 'status' => 'adopted', 'adopted_days_ago' => 45,
        'traits' => ['good_with_kids', 'house_trained'], 'health' => $all, 'guardianship' => false, 'waited' => 90,
        'character' => 'Энергичный и весёлый кот.',
        'story' => 'Марс переехал к семье в Астане и теперь охраняет их балкон.',
    ],
    [
        'key' => 'taiga', 'shelter' => 'murlyka', 'name' => 'Тайга', 'species' => 'dog', 'sex' => 'female', 'age' => 36,
        'size' => 'medium', 'fur' => 'caramel', 'color' => 'рыжая', 'status' => 'looking_for_home',
        'traits' => ['good_with_cats', 'good_with_kids', 'leash_trained'], 'health' => $all, 'guardianship' => false, 'waited' => 180,
        'character' => 'Единственная собака в нашем кошачьем приюте — и прекрасно ладит с кошками.',
        'story' => null,
    ],

    // ── «Тёплый угол», Шымкент ─────────────────────────────────────────
    [
        'key' => 'tuchka', 'shelter' => 'teply-ugol', 'name' => 'Тучка', 'species' => 'cat', 'sex' => 'female', 'age' => 60,
        'size' => 'small', 'fur' => 'smoke', 'color' => 'серая', 'status' => 'looking_for_home',
        'traits' => ['house_trained'], 'health' => $all, 'guardianship' => true, 'waited' => 260,
        'character' => 'Спокойная, стерилизована. Любит тишину и мягкие лежанки.',
        'story' => null,
    ],
    [
        'key' => 'lord', 'shelter' => 'teply-ugol', 'name' => 'Лорд', 'species' => 'dog', 'sex' => 'male', 'age' => 96,
        'size' => 'large', 'fur' => 'graphite', 'color' => 'чёрный', 'status' => 'looking_for_home',
        'traits' => ['good_with_cats', 'good_with_kids', 'leash_trained', 'house_trained'], 'health' => $all,
        'guardianship' => true, 'waited' => 820,
        'character' => 'Мудрый и уравновешенный, ладит с кошками. Неспешно гуляет и любит внимание.',
        'story' => 'Лорд живёт в приюте больше двух лет. Он уже немолод, но здоров и очень ждёт свою семью.',
    ],
    [
        'key' => 'sharik', 'shelter' => 'teply-ugol', 'name' => 'Шарик', 'species' => 'dog', 'sex' => 'male', 'age' => 24,
        'size' => 'medium', 'fur' => 'caramel', 'color' => 'рыжий', 'status' => 'looking_for_home',
        'traits' => ['good_with_dogs', 'good_with_kids', 'leash_trained'], 'health' => $all, 'guardianship' => false, 'waited' => 75,
        'character' => 'Жизнерадостный и игривый, любит бегать за мячом.',
        'story' => null,
    ],
    [
        'key' => 'buran', 'shelter' => 'teply-ugol', 'name' => 'Буран', 'species' => 'dog', 'sex' => 'male', 'age' => 48, 'approx' => true,
        'size' => 'large', 'fur' => 'graphite', 'color' => 'серый', 'status' => 'looking_for_home',
        'traits' => ['leash_trained'], 'health' => $all, 'guardianship' => true, 'waited' => 340,
        'character' => 'Сильный и выносливый, нужен опытный хозяин. С другими собаками — осторожно.',
        'story' => null,
    ],
    [
        'key' => 'laska', 'shelter' => 'teply-ugol', 'name' => 'Ласка', 'species' => 'dog', 'sex' => 'female', 'age' => 18,
        'size' => 'small', 'fur' => 'caramel', 'color' => 'палевая', 'status' => 'looking_for_home',
        'traits' => ['good_with_kids', 'good_with_dogs', 'good_with_cats'], 'health' => ['vaccinated', 'sterilized'],
        'guardianship' => false, 'waited' => 50,
        'character' => 'Маленькая и нежная, оправдывает своё имя. Подойдёт для квартиры.',
        'story' => null,
    ],
    [
        'key' => 'knopka', 'shelter' => 'teply-ugol', 'name' => 'Кнопка', 'species' => 'cat', 'sex' => 'female', 'age' => 10,
        'size' => 'small', 'fur' => 'ginger', 'color' => 'рыжая', 'status' => 'looking_for_home',
        'traits' => ['good_with_kids', 'good_with_cats'], 'health' => ['vaccinated'], 'guardianship' => false, 'waited' => 25,
        'character' => 'Шустрая и весёлая кошечка, любит играть с детьми.',
        'story' => null,
    ],

    // ── «Хвостики», Караганда ──────────────────────────────────────────
    [
        'key' => 'baron', 'shelter' => 'hvostiki', 'name' => 'Барон', 'species' => 'dog', 'sex' => 'male', 'age' => 36,
        'size' => 'medium', 'fur' => 'caramel', 'color' => 'рыжий', 'status' => 'looking_for_home',
        'traits' => ['good_with_kids', 'good_with_dogs', 'leash_trained', 'house_trained'], 'health' => $all,
        'guardianship' => true, 'waited' => 200,
        'character' => 'Дружелюбный, знает команды. Отлично подходит семье с детьми.',
        'story' => null,
    ],
    [
        'key' => 'vega', 'shelter' => 'hvostiki', 'name' => 'Вега', 'species' => 'dog', 'sex' => 'female', 'age' => 30,
        'size' => 'medium', 'fur' => 'graphite', 'color' => 'чёрная', 'status' => 'looking_for_home',
        'traits' => ['good_with_dogs', 'leash_trained'], 'health' => $all, 'guardianship' => false, 'waited' => 140,
        'character' => 'Энергичная и умная, любит учиться новому. Нужны активные хозяева.',
        'story' => null,
    ],
    [
        'key' => 'pirat', 'shelter' => 'hvostiki', 'name' => 'Пират', 'species' => 'dog', 'sex' => 'male', 'age' => 72, 'approx' => true,
        'size' => 'large', 'fur' => 'graphite', 'color' => 'чёрный с белым', 'status' => 'on_treatment',
        'traits' => ['good_with_kids', 'leash_trained'], 'health' => ['vaccinated', 'sterilized'], 'guardianship' => true, 'waited' => 380,
        'character' => 'Добродушный великан. Немного видит одним глазом, но это ему не мешает.',
        'story' => null,
        'health_notes' => 'Лечение глаза, капли два раза в день.',
    ],
    [
        'key' => 'sonya', 'shelter' => 'hvostiki', 'name' => 'Соня', 'species' => 'cat', 'sex' => 'female', 'age' => 48,
        'size' => 'small', 'fur' => 'smoke', 'color' => 'серая', 'status' => 'looking_for_home',
        'traits' => ['house_trained', 'good_with_cats'], 'health' => $all, 'guardianship' => false, 'waited' => 190,
        'character' => 'Соня полностью оправдывает имя: любит спать на солнце и тихие вечера.',
        'story' => null,
    ],
    [
        'key' => 'zhuzha', 'shelter' => 'hvostiki', 'name' => 'Жужа', 'species' => 'dog', 'sex' => 'female', 'age' => 120, 'approx' => true,
        'size' => 'small', 'fur' => 'caramel', 'color' => 'рыжая', 'status' => 'adopted', 'adopted_days_ago' => 70,
        'traits' => ['good_with_kids', 'house_trained'], 'health' => $all, 'guardianship' => false, 'waited' => 500,
        'character' => 'Спокойная пожилая собачка.',
        'story' => 'Жужа уехала к пенсионерке из Темиртау — теперь они вместе гуляют в парке каждое утро.',
    ],

    // ── «Котопёс», Актобе (приют на модерации — только черновики) ──────
    [
        'key' => 'keks', 'shelter' => 'kotopes', 'name' => 'Кекс', 'species' => 'cat', 'sex' => 'male', 'age' => 15,
        'size' => 'small', 'fur' => 'ginger', 'color' => 'рыжий', 'status' => 'draft',
        'traits' => ['house_trained'], 'health' => ['vaccinated'], 'guardianship' => false, 'waited' => 30,
        'character' => 'Добрый домашний кот, живёт на передержке у волонтёра.',
        'story' => null,
    ],
    [
        'key' => 'reks', 'shelter' => 'kotopes', 'name' => 'Рекс', 'species' => 'dog', 'sex' => 'male', 'age' => 28,
        'size' => 'medium', 'fur' => 'caramel', 'color' => 'рыжий', 'status' => 'draft',
        'traits' => ['leash_trained'], 'health' => ['vaccinated'], 'guardianship' => false, 'waited' => 14,
        'character' => 'Активный молодой пёс, любит бегать.',
        'story' => null,
    ],
];
