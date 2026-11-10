<?php

namespace App\Payments;

use RuntimeException;

/** Webhook с неверной подписью или неполными данными — отвечаем 400 и ничего не меняем. */
class InvalidWebhook extends RuntimeException {}
