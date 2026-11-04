<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\AttachmentType;
use App\Enums\BookingStatus;
use App\Enums\CampaignStatus;
use App\Enums\CancelledBy;
use App\Enums\PaymentStatus;
use App\Enums\PetStatus;
use App\Enums\ShelterMemberRole;
use App\Enums\ShelterStatus;
use App\Enums\WalkSlotStatus;
use App\Models\AdoptionApplication;
use App\Models\Donation;
use App\Models\DonationCampaign;
use App\Models\Pet;
use App\Models\Shelter;
use App\Models\User;
use App\Models\WalkBooking;
use App\Models\WalkSlot;
use App\Models\WalkTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Демо-данные как на макетах этапа 3: 4 одобренных приюта + 1 на модерации, ~30 питомцев,
 * сборы с пожертвованиями и отчётами, шаблоны и слоты выгула на неделю вперёд (и две недели истории).
 *
 * Даты считаются от текущего момента, поэтому данные «свежие» при любом запуске.
 * Сидер рассчитан на пустую базу (migrate:fresh --seed или app:seed-if-empty).
 * Демо-логины (пароль у всех — password) перечислены в README.
 */
class DemoSeeder extends Seeder
{
    public const PASSWORD = 'password';

    /** Каталог исходных демо-фото в репозитории и куда они копируются на диске public. */
    private const PHOTO_SOURCE = __DIR__.'/photos';

    private const PHOTO_TARGET = 'demo/pets';

    /** @var array<string, Shelter> */
    private array $shelters = [];

    /** @var array<string, Pet> */
    private array $pets = [];

    private User $volunteer;

    /** @var Collection<int, User> */
    private Collection $volunteers;

    /** Сколько активных будущих броней у пользователя (правило W6 — не больше 3). */
    private array $activeBookings = [];

    public function run(): void
    {
        fake()->seed(79);

        DB::transaction(function (): void {
            $this->seedUsers();
            $this->seedShelters();
            $this->seedPets();
            $this->seedCampaigns();
            $this->seedGeneralFund();
            $this->seedApplications();
            $this->seedFavorites();
            $this->seedWalks();
        });
    }

    private function seedUsers(): void
    {
        User::factory()->admin()->create([
            'login' => 'admin',
            'name' => 'Администратор VetMoment',
            'phone' => '+77010000001',
            'email' => 'admin@vetmoment.kz',
        ]);

        $this->volunteer = User::factory()->create([
            'login' => 'volunteer',
            'name' => 'Айгерим Нурланова',
            'phone' => '+77071234567',
            'email' => 'aigerim@mail.kz',
            'birth_year' => 1996,
        ]);

        $names = [
            'Арман Сейткали', 'Мадина Ахметова', 'Данияр Жаксыбеков', 'Екатерина Смирнова', 'Тимур Исаев',
            'Асель Муканова', 'Иван Петров', 'Жанна Ким', 'Нурсултан Абдрахманов', 'Анна Ли',
            'Ерасыл Тулегенов', 'Карина Оспанова', 'Максим Волков', 'Айжан Бекова', 'Руслан Ибраев', 'Дана Сапарова',
        ];

        $this->volunteers = collect($names)->map(fn (string $name, int $i) => User::factory()->create([
            'login' => 'volunteer'.($i + 2),
            'name' => $name,
            'birth_year' => fake()->numberBetween(1975, 2006),
        ]));

        // Один заблокированный — для экрана «Пользователи» в админке
        User::factory()->blocked()->create(['login' => 'blocked', 'name' => 'Заблокированный Пользователь']);
    }

    private function seedShelters(): void
    {
        foreach (require __DIR__.'/data/shelters.php' as $key => $data) {
            $status = ShelterStatus::from($data['status']);

            $shelter = Shelter::create([
                ...collect($data)->except(['owner', 'staff', 'status'])->all(),
                'status' => $status,
                'approved_at' => $status === ShelterStatus::Approved ? now()->subMonths(fake()->numberBetween(3, 18)) : null,
            ]);

            $owner = User::factory()->shelterStaff()->create($data['owner']);
            $shelter->members()->attach($owner, ['role' => ShelterMemberRole::Owner]);

            foreach ($data['staff'] as $staff) {
                $shelter->members()->attach(
                    User::factory()->shelterStaff()->create($staff),
                    ['role' => ShelterMemberRole::Staff],
                );
            }

            $this->shelters[$key] = $shelter;
        }
    }

    private function seedPets(): void
    {
        $disk = Storage::disk('public');
        $backgrounds = ['sky', 'meadow', 'tag', 'ink'];

        foreach (glob(self::PHOTO_SOURCE.'/*.jpg') ?: [] as $file) {
            $disk->put(self::PHOTO_TARGET.'/'.basename($file), (string) file_get_contents($file));
        }

        foreach (require __DIR__.'/data/pets.php' as $i => $data) {
            $status = PetStatus::from($data['status']);
            $arrivedAt = today()->subDays($data['waited']);

            $pet = Pet::create([
                'shelter_id' => $this->shelters[$data['shelter']]->id,
                'name' => $data['name'],
                'species' => $data['species'],
                'sex' => $data['sex'],
                'birth_date' => today()->subMonths($data['age'])->subDays(fake()->numberBetween(0, 25)),
                'birth_date_is_approx' => $data['approx'] ?? false,
                'size' => $data['size'],
                'breed' => 'метис',
                'color' => $data['color'],
                'character' => $data['character'],
                'story' => $data['story'],
                'health_notes' => $data['health_notes'] ?? null,
                ...array_fill_keys(array_keys(Pet::TRAITS), false),
                ...array_fill_keys($data['traits'], true),
                'vaccinated' => in_array('vaccinated', $data['health'], true),
                'sterilized' => in_array('sterilized', $data['health'], true),
                'chipped' => in_array('chipped', $data['health'], true),
                'status' => $status,
                'can_walk' => $data['species'] === 'dog' && $status !== PetStatus::Draft,
                'guardianship_available' => $data['guardianship'],
                'arrived_at' => $arrivedAt,
                'adopted_at' => isset($data['adopted_days_ago']) ? now()->subDays($data['adopted_days_ago']) : null,
            ]);

            $pet->forceFill(['created_at' => $arrivedAt, 'updated_at' => now()->subDays(fake()->numberBetween(0, 10))])->save();

            // 1–3 фото: один окрас на разных фонах, первое — обложка
            $count = $status === PetStatus::Draft ? 1 : fake()->numberBetween(2, 3);
            $start = $i % count($backgrounds);

            for ($n = 0; $n < $count; $n++) {
                $bg = $backgrounds[($start + $n) % count($backgrounds)];
                $pet->photos()->create([
                    'path' => self::PHOTO_TARGET."/{$data['species']}-{$data['fur']}-{$bg}.jpg",
                    'sort' => $n,
                    'is_cover' => $n === 0,
                ]);
            }

            $this->pets[$data['key']] = $pet;
        }
    }

    private function seedCampaigns(): void
    {
        foreach (require __DIR__.'/data/campaigns.php' as $data) {
            $status = CampaignStatus::from($data['status']);
            $startsAt = now()->subDays($data['started_days_ago'])->startOfHour();
            $endsAt = now()->addDays($data['days_left'])->endOfDay()->startOfMinute();

            $campaign = DonationCampaign::create([
                'shelter_id' => $this->shelters[$data['shelter']]->id,
                'pet_id' => $data['pet'] ? $this->pets[$data['pet']]->id : null,
                'title' => $data['title'],
                'short_description' => $data['short'],
                'description' => $data['description'],
                'goal_amount' => $data['goal'] * 100,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => $status,
                'completed_at' => $status === CampaignStatus::Completed ? $endsAt->copy()->subDays(3) : null,
            ]);
            $campaign->forceFill(['created_at' => $startsAt])->save();

            $paidUntil = $status === CampaignStatus::Active ? now() : $endsAt->copy()->subDays(3);
            $this->seedDonations($campaign->shelter_id, $campaign->id, $data['raised'], $data['donors'], $startsAt, $paidUntil);

            if ($status === CampaignStatus::Active) {
                // Незавершённые платежи не влияют на собранную сумму
                Donation::factory()->forCampaign($campaign)->status(PaymentStatus::Pending)->create(['provider_payment_id' => null]);
                Donation::factory()->forCampaign($campaign)->status(PaymentStatus::Failed)->create();
            }

            $campaign->recalculateTotals();

            if (isset($data['report'])) {
                $report = $campaign->reports()->create([
                    'title' => $data['report']['title'],
                    'body' => $data['report']['body'],
                    'spent_amount' => $data['report']['spent'] * 100,
                    'published_at' => now()->subDays($data['report']['published_days_ago']),
                ]);

                foreach (['dog-caramel-tag', 'cat-smoke-sky'] as $sort => $photo) {
                    $report->attachments()->create([
                        'path' => self::PHOTO_TARGET."/{$photo}.jpg",
                        'type' => AttachmentType::Photo,
                        'sort' => $sort,
                    ]);
                }
            }
        }
    }

    /** Пожертвования в общий фонд приютов (без сбора). */
    private function seedGeneralFund(): void
    {
        foreach ($this->shelters as $shelter) {
            if ($shelter->isApproved()) {
                $this->seedDonations($shelter->id, null, fake()->numberBetween(30, 120) * 1_000, fake()->numberBetween(8, 20), now()->subMonths(2), now());
            }
        }
    }

    /**
     * Успешные пожертвования на сумму $totalTenge от $count доноров, равномерно по времени.
     */
    private function seedDonations(int $shelterId, ?int $campaignId, int $totalTenge, int $count, Carbon $from, Carbon $until): void
    {
        $presets = [500, 1_000, 1_000, 2_000, 2_000, 3_000, 5_000, 5_000, 10_000, 20_000];
        $remaining = $totalTenge;
        $comments = [
            'Выздоравливай!', 'Держитесь, вы молодцы!', 'Спасибо за вашу работу', 'От нашей семьи', 'На корм',
            'Пусть найдёт дом поскорее', 'От коллег из офиса', null, null, null, null, null,
        ];
        $donors = $this->volunteers->concat([$this->volunteer]);

        for ($i = 0; $i < $count; $i++) {
            $left = $count - $i - 1;

            if ($left === 0) {
                $amount = $remaining;
            } else {
                $cap = min($remaining - 200 * $left, max(200, intdiv($remaining, $left + 1) * 2));
                $options = array_values(array_filter($presets, fn (int $p): bool => $p <= $cap));
                $amount = $options ? fake()->randomElement($options) : max(200, $cap);
            }

            $remaining -= $amount;
            $user = fake()->boolean(25) ? $donors->random() : null;
            $anonymous = fake()->boolean(25);
            $paidAt = Carbon::createFromTimestamp(fake()->numberBetween($from->getTimestamp(), $until->getTimestamp()));

            $donation = Donation::factory()->create([
                'campaign_id' => $campaignId,
                'shelter_id' => $shelterId,
                'user_id' => $user?->id,
                'donor_name' => $user?->name ?? fake()->firstName(),
                'donor_email' => $user?->email ?? fake()->safeEmail(),
                'is_anonymous' => $anonymous,
                'amount' => $amount * 100,
                'comment' => $anonymous ? null : fake()->randomElement($comments),
                'status' => PaymentStatus::Succeeded,
                'paid_at' => $paidAt,
            ]);
            $donation->forceFill(['created_at' => $paidAt, 'updated_at' => $paidAt])->save();
        }
    }

    private function seedApplications(): void
    {
        $apply = function (string $pet, User $user, ApplicationType $type, ApplicationStatus $status, ?string $comment = null): void {
            $application = AdoptionApplication::factory()->create([
                'pet_id' => $this->pets[$pet]->id,
                'user_id' => $user->id,
                'type' => $type,
                'contact_name' => $user->name,
                'contact_phone' => $user->phone,
                'city' => $this->pets[$pet]->shelter->city,
                'experience' => fake()->randomElement([
                    'Раньше была собака, прожила с нами 12 лет.',
                    'Животных не было, но мы долго готовились и читали про уход.',
                    'Сейчас дома живёт кошка, ей 4 года.',
                ]),
                'living_conditions' => $type === ApplicationType::Adoption
                    ? 'Живём вдвоём с мужем, дочке 9 лет. Своя квартира, сетки на окнах.'
                    : null,
                'message' => 'Очень хотим познакомиться!',
                'status' => $status,
                'shelter_comment' => $comment,
                'processed_at' => $status->isActive() && $status !== ApplicationStatus::Approved ? null : now()->subDays(2),
            ]);
            $application->forceFill(['created_at' => now()->subDays(fake()->numberBetween(1, 20))])->save();
        };

        $v = $this->volunteers;

        // Кабинет волонтёра
        $apply('bublik', $this->volunteer, ApplicationType::Adoption, ApplicationStatus::InReview);
        $apply('myata', $this->volunteer, ApplicationType::Guardianship, ApplicationStatus::New);
        $apply('dymka', $this->volunteer, ApplicationType::Adoption, ApplicationStatus::Withdrawn);

        // Кабинет приюта «Дом лап»
        $apply('bublik', $v[0], ApplicationType::Adoption, ApplicationStatus::New);
        $apply('naida', $v[1], ApplicationType::Adoption, ApplicationStatus::Rejected, 'Найде нужен частный дом с двором.');
        $apply('jessie', $v[2], ApplicationType::Guardianship, ApplicationStatus::New);
        $apply('graf', $v[3], ApplicationType::Adoption, ApplicationStatus::Approved, 'Ждём окончания лечения.');
        $apply('altai', $v[4], ApplicationType::Adoption, ApplicationStatus::Completed);

        // Другие приюты
        $apply('lord', $v[5], ApplicationType::Guardianship, ApplicationStatus::Approved);
        $apply('baron', $v[6], ApplicationType::Adoption, ApplicationStatus::New);
        $apply('ryzhik', $v[7], ApplicationType::Adoption, ApplicationStatus::InReview);
        $apply('mars', $v[8], ApplicationType::Adoption, ApplicationStatus::Completed);
        $apply('zhuzha', $v[9], ApplicationType::Adoption, ApplicationStatus::Completed);
    }

    private function seedFavorites(): void
    {
        $this->volunteer->favoritePets()->attach([$this->pets['myata']->id, $this->pets['bublik']->id, $this->pets['lord']->id]);

        $visible = collect($this->pets)->filter(fn (Pet $pet) => in_array($pet->status, PetStatus::catalog(), true));

        foreach ($this->volunteers as $user) {
            $user->favoritePets()->attach($visible->random(fake()->numberBetween(0, 4))->pluck('id'));
        }
    }

    private function seedWalks(): void
    {
        $tz = config('app.display_timezone');

        // [приют, дни недели (1 = Пн), начало, минут, мест, место сбора]
        $templates = [
            ['dom-lap', [6, 7], '10:00', 60, 4, 'У ворот приюта, ул. Жандосова, 186'],
            ['dom-lap', [2, 4], '17:00', 90, 3, 'У ворот приюта, ул. Жандосова, 186'],
            ['teply-ugol', [1, 2, 3, 4, 5, 6, 7], '10:00', 60, 3, 'Вход в парк у приюта'],
            ['hvostiki', [3, 6], '11:00', 60, 5, 'Парковка у приюта'],
            ['murlyka', [7], '12:00', 60, 2, 'Проходная, ул. Сыганак, 14'],
        ];

        $today = Carbon::today($tz);

        foreach ($templates as [$shelterKey, $days, $time, $minutes, $capacity, $meetingPoint]) {
            $shelter = $this->shelters[$shelterKey];
            $dogs = collect($this->pets)->filter(fn (Pet $pet) => $pet->shelter_id === $shelter->id && $pet->isWalkable());

            $template = WalkTemplate::create([
                'shelter_id' => $shelter->id,
                'weekdays' => WalkTemplate::maskFromDays($days),
                'start_time' => $time,
                'duration_minutes' => $minutes,
                'capacity' => $capacity,
                'meeting_point' => $meetingPoint,
                'valid_from' => $today->copy()->subWeeks(2),
            ]);
            $template->pets()->attach($dogs->pluck('id'));

            // Две недели истории и неделя вперёд
            for ($date = $today->copy()->subDays(14); $date->lte($today->copy()->addDays(7)); $date->addDay()) {
                if (! $template->hasWeekday($date->dayOfWeekIso)) {
                    continue;
                }

                [$h, $m] = array_map('intval', explode(':', $time));
                $startsAt = $date->copy()->setTime($h, $m)->utc();

                $slot = WalkSlot::create([
                    'shelter_id' => $shelter->id,
                    'template_id' => $template->id,
                    'starts_at' => $startsAt,
                    'ends_at' => $startsAt->copy()->addMinutes($minutes),
                    'capacity' => $capacity,
                    'meeting_point' => $meetingPoint,
                    'status' => WalkSlotStatus::Open,
                ]);

                $slotDogs = $dogs->count() > 1 ? $dogs->random(min($dogs->count(), fake()->numberBetween(2, 4))) : $dogs;
                $slot->pets()->attach($slotDogs->pluck('id'));
            }
        }

        $this->seedBookings();
    }

    private function seedBookings(): void
    {
        $slots = WalkSlot::with('pets')->orderBy('starts_at')->get();
        $upcoming = $slots->filter(fn (WalkSlot $slot) => $slot->starts_at->isFuture())->values();
        $past = $slots->filter(fn (WalkSlot $slot) => $slot->starts_at->isPast());
        $domLap = $this->shelters['dom-lap']->id;

        // Волонтёр: подтверждённая запись с Бубликом в «Доме лап» и ожидающая — в «Хвостиках»
        $bublikSlot = $upcoming->first(fn (WalkSlot $s) => $s->shelter_id === $domLap && $s->pets->contains($this->pets['bublik']))
            ?? $upcoming->first(fn (WalkSlot $s) => $s->shelter_id === $domLap);
        if (! $bublikSlot->pets->contains($this->pets['bublik'])) {
            $bublikSlot->pets()->attach($this->pets['bublik']);
            $bublikSlot->load('pets');
        }
        $this->book($bublikSlot, $this->volunteer, BookingStatus::Confirmed, $this->pets['bublik']);

        $hvostikiSlot = $upcoming->first(fn (WalkSlot $s) => $s->shelter_id === $this->shelters['hvostiki']->id);
        $this->book($hvostikiSlot, $this->volunteer, BookingStatus::Pending);

        // История волонтёра: состоявшиеся прогулки
        foreach ($past->where('shelter_id', $domLap)->take(3) as $slot) {
            $this->book($slot, $this->volunteer, BookingStatus::Completed);
        }

        // Остальные записи. Один ближайший слот «Дома лап» заполняем целиком — состояние «Мест нет».
        $fullSlot = $upcoming->first(fn (WalkSlot $s) => $s->shelter_id === $domLap && $s->id !== $bublikSlot->id);

        foreach ($slots as $slot) {
            $target = $slot->is($fullSlot) ? $slot->capacity : fake()->numberBetween(0, $slot->capacity - 1);

            foreach ($this->volunteers->shuffle() as $user) {
                if ($slot->bookings()->active()->count() + $slot->bookings()->whereIn('status', [BookingStatus::Completed, BookingStatus::NoShow])->count() >= $target) {
                    break;
                }

                $status = $slot->starts_at->isFuture()
                    ? fake()->randomElement([BookingStatus::Confirmed, BookingStatus::Confirmed, BookingStatus::Pending])
                    : fake()->randomElement([BookingStatus::Completed, BookingStatus::Completed, BookingStatus::Completed, BookingStatus::NoShow]);

                $this->book($slot, $user, $status);
            }

            // Немного отменённых записей — для истории и статистики
            if (fake()->boolean(20)) {
                $this->book($slot, $this->volunteers->random(), BookingStatus::Cancelled);
            }
        }

        // Один отменённый приютом слот на следующей неделе
        $cancelled = $upcoming->last(fn (WalkSlot $s) => $s->shelter_id === $this->shelters['teply-ugol']->id);
        $cancelled?->update(['status' => WalkSlotStatus::Cancelled]);
        $cancelled?->bookings()->active()->update([
            'status' => BookingStatus::Cancelled,
            'cancelled_by' => CancelledBy::Shelter,
            'cancelled_at' => now(),
            'shelter_comment' => 'Приют закрыт на карантин, приносим извинения.',
        ]);

        WalkSlot::query()->each(fn (WalkSlot $slot) => $slot->recalculateBookedCount());
    }

    /**
     * Создаёт запись, соблюдая правила W4–W6: пользователь и питомец — не более одной активной записи в слоте,
     * у пользователя не больше 3 активных будущих записей.
     */
    private function book(WalkSlot $slot, User $user, BookingStatus $status, ?Pet $pet = null): void
    {
        $taken = $slot->bookings()->where('user_id', $user->id)->exists();
        $countsAsActive = $status->isActive() && $slot->starts_at->isFuture();

        if ($taken || ($countsAsActive && ($this->activeBookings[$user->id] ?? 0) >= 3)) {
            return;
        }

        if ($pet === null && $status !== BookingStatus::Cancelled) {
            $busy = $slot->bookings()->whereNotIn('status', [BookingStatus::Cancelled, BookingStatus::Rejected])->pluck('pet_id');
            $pet = $slot->pets->whereNotIn('id', $busy)->shuffle()->first();
        }

        $bookedAt = $slot->starts_at->copy()->subDays(fake()->numberBetween(1, 5));

        $booking = WalkBooking::factory()->status($status)->create([
            'walk_slot_id' => $slot->id,
            'user_id' => $user->id,
            'pet_id' => $pet?->id,
            'comment' => fake()->boolean(20) ? 'Беру с собой поводок и воду.' : null,
            'confirmed_at' => $status === BookingStatus::Pending || $status === BookingStatus::Cancelled ? null : $bookedAt->copy()->addHours(3),
            'cancelled_at' => $status === BookingStatus::Cancelled ? $bookedAt->copy()->addDay() : null,
        ]);
        $booking->forceFill(['created_at' => $bookedAt])->save();

        if ($countsAsActive) {
            $this->activeBookings[$user->id] = ($this->activeBookings[$user->id] ?? 0) + 1;
        }
    }
}
