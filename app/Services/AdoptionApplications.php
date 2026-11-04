<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\PetStatus;
use App\Models\AdoptionApplication;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Заявки «Хочу забрать» / «Взять под опеку» (docs/product/user-flows.md, 5):
 * подача, отзыв, обработка приютом и закрытие остальных заявок, когда питомец нашёл дом.
 * Права («кто») проверяют политики, здесь — бизнес-правила («можно ли сейчас»).
 */
class AdoptionApplications
{
    /** Допустимые переходы при обработке приютом. */
    public const TRANSITIONS = [
        'new' => [ApplicationStatus::InReview, ApplicationStatus::Rejected],
        'in_review' => [ApplicationStatus::Approved, ApplicationStatus::Rejected],
        'approved' => [ApplicationStatus::Completed, ApplicationStatus::Rejected],
    ];

    public const PET_ADOPTED_COMMENT = 'Питомец нашёл дом.';

    /**
     * @param  array<string, mixed>  $data  проверенные поля AdoptionApplicationRequest
     */
    public function submit(User $user, Pet $pet, array $data): AdoptionApplication
    {
        $type = ApplicationType::from($data['type']);

        return DB::transaction(function () use ($user, $pet, $data, $type): AdoptionApplication {
            // Блокировка питомца: две одновременные отправки формы не создадут две активные заявки.
            Pet::query()->whereKey($pet->getKey())->lockForUpdate()->first();

            if ($type === ApplicationType::Guardianship && ! $pet->guardianship_available) {
                throw ValidationException::withMessages(['type' => "{$pet->name} сейчас нельзя взять под опеку."]);
            }

            if ($this->activeFor($user, $pet, $type) !== null) {
                throw ValidationException::withMessages([
                    'type' => 'Вы уже отправили такую заявку — приют её рассматривает.',
                ]);
            }

            return $pet->applications()->create([
                'user_id' => $user->getKey(),
                'type' => $type,
                'contact_name' => $data['contact_name'],
                'contact_phone' => $data['contact_phone'],
                'city' => $data['city'],
                'experience' => $data['experience'] ?? null,
                'living_conditions' => $type === ApplicationType::Adoption ? ($data['living_conditions'] ?? null) : null,
                'message' => $data['message'] ?? null,
                'status' => ApplicationStatus::New,
            ]);
        });
    }

    public function activeFor(User $user, Pet $pet, ApplicationType $type): ?AdoptionApplication
    {
        return $pet->applications()->active()
            ->where('user_id', $user->getKey())
            ->where('type', $type)
            ->first();
    }

    public function withdraw(AdoptionApplication $application): void
    {
        $application->update(['status' => ApplicationStatus::Withdrawn, 'processed_at' => now()]);
    }

    /** @return list<ApplicationStatus> */
    public function nextStatuses(AdoptionApplication $application): array
    {
        return self::TRANSITIONS[$application->status->value] ?? [];
    }

    /**
     * Решение приюта. «Завершена» = питомец передан: питомец → «Нашёл дом», остальные заявки закрываются.
     */
    public function process(AdoptionApplication $application, ApplicationStatus $to, ?string $comment = null): void
    {
        if (! in_array($to, $this->nextStatuses($application), true)) {
            throw ValidationException::withMessages([
                'status' => "Заявку в статусе «{$application->status->label()}» нельзя перевести в «{$to->label()}».",
            ]);
        }

        DB::transaction(function () use ($application, $to, $comment): void {
            $application->update([
                'status' => $to,
                'shelter_comment' => $comment ?? $application->shelter_comment,
                'processed_at' => now(),
            ]);

            if ($to === ApplicationStatus::Completed && $application->type === ApplicationType::Adoption) {
                $pet = $application->pet;
                $pet->update(['status' => PetStatus::Adopted, 'adopted_at' => now()]);
                $this->closeForAdoptedPet($pet, except: $application);
            }
        });
    }

    /**
     * Питомец нашёл дом: одобренные заявки на усыновление → «Завершена», остальные активные → «Отклонена».
     */
    public function closeForAdoptedPet(Pet $pet, ?AdoptionApplication $except = null): void
    {
        $open = $pet->applications()->active()
            ->when($except, fn ($q) => $q->whereKeyNot($except->getKey()))
            ->get();

        $completed = $except !== null;

        foreach ($open as $application) {
            // Если приют сразу сменил статус питомца, одна одобренная заявка на усыновление считается состоявшейся.
            if (! $completed && $application->status === ApplicationStatus::Approved && $application->type === ApplicationType::Adoption) {
                $application->update(['status' => ApplicationStatus::Completed, 'processed_at' => now()]);
                $completed = true;

                continue;
            }

            $application->update([
                'status' => ApplicationStatus::Rejected,
                'shelter_comment' => self::PET_ADOPTED_COMMENT,
                'processed_at' => now(),
            ]);
        }
    }
}
