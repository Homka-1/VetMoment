<?php

namespace Tests\Feature\Security;

use App\Enums\Role;
use App\Http\Middleware\SecurityHeaders;
use App\Models\Pet;
use App\Models\Shelter;
use App\Models\User;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesActors;
use Tests\TestCase;

/**
 * Безопасность (этап 14): заголовки ответа, CSRF, экранирование пользовательского текста,
 * загрузка файлов, лимит webhook, команда создания администратора.
 */
class SecurityTest extends TestCase
{
    use CreatesActors, RefreshDatabase;

    public function test_security_headers_on_pages(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy')
            ->assertHeaderMissing('Strict-Transport-Security');

        // Ошибки тоже идут через web-группу
        $this->get('/pets/nobody')->assertNotFound()->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_csp_is_controlled_by_config(): void
    {
        config(['security.csp' => false]);
        $this->get('/')->assertHeaderMissing('Content-Security-Policy');

        config(['security.csp' => true]);
        $this->get('/')->assertHeader('Content-Security-Policy', SecurityHeaders::CSP);
    }

    public function test_hsts_only_over_https(): void
    {
        $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    public function test_csrf_token_is_required_except_payment_webhooks(): void
    {
        // В тестах Laravel CSRF отключён — включаем настоящую проверку.
        $this->app->instance(PreventRequestForgery::class, new class($this->app, $this->app->make(Encrypter::class)) extends PreventRequestForgery
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });

        $this->post('/login', ['identifier' => 'volunteer', 'password' => 'password'])->assertStatus(419);

        $volunteer = $this->volunteer();
        $this->actingAs($volunteer)->patch('/cabinet/profile', ['name' => 'Взлом'])->assertStatus(419);
        $this->assertNotSame('Взлом', $volunteer->fresh()->name);

        // Webhook провайдера проверяется подписью, а не токеном: без подписи — 4xx, но не 419.
        $status = $this->postJson('/payments/webhook/fake', [])->status();
        $this->assertNotSame(419, $status);
        $this->assertGreaterThanOrEqual(400, $status);
    }

    public function test_user_text_is_escaped(): void
    {
        $shelter = Shelter::factory()->create(['name' => 'Приют <b>жирный</b>']);
        $pet = Pet::factory()->for($shelter)->create([
            'name' => '<script>alert(1)</script>',
            'character' => 'Ласковый "<img src=x onerror=alert(2)>" и спокойный, любит гулять.',
        ]);

        $page = $this->get(route('pets.show', $pet))->assertOk();
        $page->assertDontSee('<script>alert(1)</script>', false);
        $page->assertDontSee('<img src=x onerror=alert(2)>', false);
        $page->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
        $page->assertDontSee('<b>жирный</b>', false);

        $this->get('/pets')->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_uploads_accept_only_real_images(): void
    {
        Storage::fake('public');
        $shelter = Shelter::factory()->create();
        $staff = $this->staffOf($shelter);

        $bad = [
            // PHP-скрипт с расширением и типом картинки: тип определяется по содержимому, а не по имени
            $this->upload('shell.jpg', '<?php system($_GET["c"]); ?>', 'image/jpeg'),
            $this->upload('shell.php', '<?php echo 1; ?>', 'application/x-php'),
            // SVG может содержать скрипт — не принимаем
            $this->upload('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', 'image/svg+xml'),
            $this->upload('page.png', '<html><body><script>alert(1)</script></body></html>', 'image/png'),
        ];

        foreach ($bad as $file) {
            $this->actingAs($staff)->patch('/shelter/profile', [
                'logo' => $file,
            ])->assertSessionHasErrors('logo');

            $this->actingAs($staff)->post('/shelter/pets', [
                'action' => 'draft',
                'photos_new' => [$file],
            ])->assertSessionHasErrors('photos_new.0');
        }

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    /**
     * Настоящий файл, тип которого PHP определяет по содержимому (finfo), а не «фейк» Laravel,
     * который берёт тип из расширения имени.
     */
    private function upload(string $name, string $content, string $clientMime): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, $clientMime, null, true);
    }

    public function test_payment_webhook_is_rate_limited(): void
    {
        RateLimiter::clear('webhooks');

        for ($i = 0; $i < 120; $i++) {
            $this->assertNotSame(429, $this->postJson('/payments/webhook/fake', [])->status());
        }

        $this->postJson('/payments/webhook/fake', [])->assertStatus(429);
    }

    public function test_create_admin_command(): void
    {
        putenv('ADMIN_PASSWORD=very-secret-1');

        try {
            $this->artisan('app:create-admin', [
                'login' => 'Boss',
                '--name' => 'Главный',
                '--phone' => '8 701 555 44 33',
                '--email' => 'Boss@Example.kz',
            ])->assertSuccessful();

            $admin = User::where('login', 'boss')->firstOrFail();
            $this->assertSame(Role::Admin, $admin->role);
            $this->assertSame('+77015554433', $admin->phone);
            $this->assertSame('boss@example.kz', $admin->email);

            $this->post('/login', ['identifier' => 'boss', 'password' => 'very-secret-1'])
                ->assertRedirect(route($admin->homeRoute()));

            // Повтор с тем же логином и неверным телефоном — ошибки, второй записи нет
            $this->artisan('app:create-admin', ['login' => 'boss', '--phone' => '123', '--email' => 'x@example.kz'])
                ->assertFailed();
            $this->assertSame(1, User::where('role', Role::Admin)->count());
        } finally {
            putenv('ADMIN_PASSWORD');
        }
    }
}
