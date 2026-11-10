<?php

/*
| Платёжный слой пожертвований (этап 9). Реальных ключей в репозитории нет:
| по умолчанию — демо-шлюз `fake` (страница «банка» с кнопками «Оплатить» / «Отказ»).
*/

return [
    // Шлюз для новых пожертвований: fake | cloudpayments_kz
    'default' => env('PAYMENT_GATEWAY', 'fake'),

    // Сколько минут ждать оплату, прежде чем пометить пожертвование «Срок оплаты истёк».
    'pending_ttl_minutes' => (int) env('PAYMENT_PENDING_TTL', 60),

    // Пределы суммы пожертвования, в тенге (docs/product/data-requirements.md, 6).
    'min_amount' => 200,
    'max_amount' => 5_000_000,
    'presets' => [1_000, 2_000, 5_000, 10_000],

    'gateways' => [
        'fake' => [
            // Подпись демо-webhook; по умолчанию выводится из APP_KEY, задавать не нужно.
            'secret' => env('PAYMENT_FAKE_SECRET'),
        ],

        // Заготовка драйвера CloudPayments (Казахстан). Не проверялась на реальном API.
        'cloudpayments_kz' => [
            'public_id' => env('CLOUDPAYMENTS_PUBLIC_ID'),
            'api_secret' => env('CLOUDPAYMENTS_API_SECRET'),
            'api_url' => env('CLOUDPAYMENTS_API_URL', 'https://api.cloudpayments.ru'),
        ],
    ],
];
