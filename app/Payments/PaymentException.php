<?php

namespace App\Payments;

use RuntimeException;

/** Провайдер не смог создать платёж (сеть, ключи, отказ). Пользователь видит понятную ошибку. */
class PaymentException extends RuntimeException {}
