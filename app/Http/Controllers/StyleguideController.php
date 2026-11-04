<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Enums\BookingStatus;
use App\Enums\CampaignStatus;
use App\Enums\PaymentStatus;
use App\Enums\PetStatus;
use App\Enums\Sex;
use App\Enums\ShelterStatus;
use App\Enums\Species;
use App\Models\DonationCampaign;
use App\Models\Pet;
use App\Models\Shelter;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\View;

/**
 * Витрина UI-кита: все Blade-компоненты на одной странице для сверки с design/ui-kit.svg.
 * Доступна только в окружениях local и testing — в режиме запуска (production) отдаёт 404.
 * Данные — несохранённые модели в памяти, база не нужна.
 */
class StyleguideController extends Controller
{
    public function __invoke(): View
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        $domLap = new Shelter(['name' => 'Дом лап', 'city' => 'Алматы', 'status' => ShelterStatus::Approved]);
        $murlyka = new Shelter(['name' => 'Мурлыка', 'city' => 'Астана', 'status' => ShelterStatus::Approved]);

        $pets = [
            $this->pet('Бублик', Species::Dog, Sex::Male, 26, PetStatus::LookingForHome, $domLap, ['good_with_kids', 'leash_trained'], true),
            $this->pet('Мята', Species::Cat, Sex::Female, 14, PetStatus::OnTreatment, $murlyka, ['good_with_kids', 'house_trained'], false),
            $this->pet('Граф', Species::Dog, Sex::Male, 74, PetStatus::Reserved, $domLap, ['good_with_dogs', 'leash_trained'], true),
        ];

        $campaigns = [
            $this->campaign('Операция на лапе для Ромашки', $domLap, 600_000_00, 412_500_00, 84, 12, CampaignStatus::Active),
            $this->campaign('Корм на зиму для 40 кошек', $murlyka, 250_000_00, 250_000_00, 131, null, CampaignStatus::Completed),
        ];

        $statuses = [
            'Питомец' => PetStatus::cases(),
            'Сбор' => CampaignStatus::cases(),
            'Запись на выгул' => BookingStatus::cases(),
            'Заявка на питомца' => ApplicationStatus::cases(),
            'Приют' => ShelterStatus::cases(),
            'Платёж' => PaymentStatus::cases(),
        ];

        $base = Carbon::now(config('app.display_timezone'))->startOfWeek()->setTime(10, 0);
        $days = collect(range(0, 6))->map(fn (int $i) => [
            'date' => $base->copy()->addDays($i),
            'available' => $i !== 4,
        ])->all();

        // Пример ошибки валидации для поля телефона.
        view()->share('errors', (new ViewErrorBag)->put('default', new MessageBag([
            'sg_phone' => 'Не хватает цифр: нужно 10 после +7',
        ])));

        preg_match_all('/<symbol id="i-([a-z0-9-]+)"/', (string) file_get_contents(resource_path('svg/icons.svg')), $m);
        $icons = $m[1];

        $paginator = new LengthAwarePaginator([], 90, 10, 2, ['path' => url('/styleguide')]);

        return view('styleguide', compact('pets', 'campaigns', 'statuses', 'days', 'base', 'paginator', 'domLap', 'murlyka', 'icons'));
    }

    /**
     * @param  list<string>  $traits
     */
    private function pet(string $name, Species $species, Sex $sex, int $months, PetStatus $status, Shelter $shelter, array $traits, bool $canWalk): Pet
    {
        $pet = new Pet([
            'name' => $name,
            'species' => $species,
            'sex' => $sex,
            'birth_date' => now()->subMonths($months),
            'status' => $status,
            'can_walk' => $canWalk,
            ...array_fill_keys($traits, true),
        ]);
        $pet->setRelation('shelter', $shelter);

        return $pet;
    }

    private function campaign(string $title, Shelter $shelter, int $goal, int $raised, int $donors, ?int $daysLeft, CampaignStatus $status): DonationCampaign
    {
        $campaign = new DonationCampaign([
            'title' => $title,
            'goal_amount' => $goal,
            'raised_amount' => $raised,
            'donations_count' => $donors,
            'starts_at' => now()->subMonth(),
            'ends_at' => $daysLeft ? now()->addDays($daysLeft)->subMinute() : now()->subDay(),
            'status' => $status,
        ]);
        $campaign->setRelation('shelter', $shelter);

        return $campaign;
    }
}
