<?php

namespace Tests\Feature\Public;

use App\Http\Controllers\HomeController;
use App\Models\Pet;
use App\Models\Shelter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Этап 13: кэш главной — цифры и приюты из кэша, при этом в кэше нет объектов моделей
 * (cache.serializable_classes = false), а приют, снятый с публикации, не показывается.
 */
class HomeCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_cached_blocks_hold_only_plain_values_and_survive_serialization(): void
    {
        config(['cache.default' => 'file']);
        Cache::flush();

        $shelter = Shelter::factory()->create(['name' => 'Приют Кэшевый']);
        Pet::factory()->for($shelter)->count(2)->create();

        $this->get('/')->assertOk()->assertSee('Приют Кэшевый');
        $this->get('/')->assertOk()->assertSee('Приют Кэшевый');

        foreach (HomeController::CACHE_KEYS as $key) {
            $this->assertIsArray(Cache::get($key));
        }
        $this->assertSame([$shelter->id => 2], Cache::get('home.shelters'));

        $shelter->update(['status' => 'blocked']);
        $this->get('/')->assertOk()->assertDontSee('Приют Кэшевый');

        Cache::flush();
    }
}
