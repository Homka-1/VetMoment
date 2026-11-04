<?php

namespace Tests\Feature;

use App\Rules\KazakhPhone;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidationLocalizationTest extends TestCase
{
    public function test_validation_messages_are_in_russian(): void
    {
        $validator = Validator::make(['login' => ''], ['login' => 'required']);

        $this->assertSame('Заполните поле «Логин».', $validator->errors()->first('login'));
    }

    public function test_kazakh_phone_rule_accepts_local_formats(): void
    {
        $validator = Validator::make(['phone' => '8 (701) 234-56-78'], ['phone' => [new KazakhPhone]]);

        $this->assertTrue($validator->passes());
    }

    public function test_kazakh_phone_rule_rejects_foreign_numbers_with_russian_message(): void
    {
        $validator = Validator::make(['phone' => '+998 90 123 45 67'], ['phone' => [new KazakhPhone]]);

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'Укажите казахстанский мобильный номер, например +7 701 234 56 78.',
            $validator->errors()->first('phone'),
        );
    }
}
