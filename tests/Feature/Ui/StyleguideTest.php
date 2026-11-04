<?php

namespace Tests\Feature\Ui;

use Tests\TestCase;

class StyleguideTest extends TestCase
{
    public function test_styleguide_renders_all_components_without_database(): void
    {
        $this->get('/styleguide')
            ->assertOk()
            ->assertSee('Витрина UI-кита')
            ->assertSee('Бублик')
            ->assertSee('Операция на лапе для Ромашки')
            ->assertSee("412\u{00A0}500\u{00A0}₸")
            ->assertSee('Проверенный приют')
            ->assertSee('Не хватает цифр: нужно 10 после +7')
            ->assertSee('aria-label="Страница 3"', escape: false)
            ->assertSee('id="sg-cancel"', escape: false);
    }

    public function test_styleguide_is_hidden_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->get('/styleguide')->assertNotFound();
    }
}
