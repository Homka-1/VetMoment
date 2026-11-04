<?php

namespace App\Providers;

use App\Models\AdoptionApplication;
use App\Models\DonationCampaign;
use App\Models\Pet;
use App\Models\Shelter;
use App\Models\WalkBooking;
use App\Models\WalkSlot;
use App\Policies\AdoptionApplicationPolicy;
use App\Policies\CampaignPolicy;
use App\Policies\PetPolicy;
use App\Policies\ShelterPolicy;
use App\Policies\WalkBookingPolicy;
use App\Policies\WalkSlotPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerRateLimiters();

        // Требования к паролю: не короче 8 символов (docs/product/data-requirements.md).
        Password::defaults(fn () => Password::min(8)->max(255));

        // Пагинация в стиле UI-кита (resources/views/pagination/vetmoment.blade.php).
        Paginator::defaultView('pagination.vetmoment');
        Paginator::defaultSimpleView('pagination.vetmoment');
    }

    private function registerPolicies(): void
    {
        // Явно, а не автопоиском: имя CampaignPolicy не совпадает с моделью DonationCampaign.
        Gate::policy(Pet::class, PetPolicy::class);
        Gate::policy(DonationCampaign::class, CampaignPolicy::class);
        Gate::policy(WalkSlot::class, WalkSlotPolicy::class);
        Gate::policy(WalkBooking::class, WalkBookingPolicy::class);
        Gate::policy(AdoptionApplication::class, AdoptionApplicationPolicy::class);
        Gate::policy(Shelter::class, ShelterPolicy::class);
    }

    /**
     * Ограничения частоты запросов (middleware `throttle:<имя>`).
     * Неудачные попытки входа дополнительно считает LoginRequest — по связке «логин + IP».
     */
    private function registerRateLimiters(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        RateLimiter::for('register', fn (Request $request) => Limit::perHour(10)->by($request->ip()));

        RateLimiter::for('password-reset', fn (Request $request) => [
            Limit::perMinute(3)->by('email:'.mb_strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perHour(20)->by('ip:'.$request->ip()),
        ]);

        // Обычные формы (заявки, бронирования, пожертвования): на пользователя или IP гостя.
        RateLimiter::for('forms', fn (Request $request) => Limit::perMinute(10)
            ->by($request->user()?->getAuthIdentifier() ?? $request->ip()));
    }
}
