<?php

namespace App\Services;

use App\Enums\Role;
use App\Enums\ShelterMemberRole;
use App\Enums\ShelterStatus;
use App\Models\Shelter;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Регистрация приюта: заявка создаёт приют на модерации, автор становится владельцем
 * и получает роль shelter_staff сразу (кабинет работает в режиме черновика до одобрения).
 */
class ShelterRegistration
{
    public const LOGO_DIRECTORY = 'shelters/logos';

    /**
     * @param  array<string, mixed>  $data  провалидированные поля ShelterApplicationRequest
     */
    public function submit(User $user, array $data, ?UploadedFile $logo = null): Shelter
    {
        $shelter = DB::transaction(function () use ($user, $data, $logo): Shelter {
            $shelter = Shelter::create([
                ...$this->attributes($data),
                'status' => ShelterStatus::Pending,
                'logo_path' => $logo?->store(self::LOGO_DIRECTORY, 'public'),
            ]);

            $shelter->members()->attach($user, ['role' => ShelterMemberRole::Owner]);
            $user->forceFill(['role' => Role::ShelterStaff])->save();

            return $shelter;
        });

        $user->unsetRelation('shelters');

        return $shelter;
    }

    /**
     * Исправленная заявка после отказа: rejected → pending, причина отказа сбрасывается.
     *
     * @param  array<string, mixed>  $data
     */
    public function resubmit(Shelter $shelter, array $data, ?UploadedFile $logo = null): Shelter
    {
        $attributes = [
            ...$this->attributes($data),
            'status' => ShelterStatus::Pending,
            'moderation_comment' => null,
        ];

        if ($logo !== null) {
            $previous = $shelter->logo_path;
            $attributes['logo_path'] = $logo->store(self::LOGO_DIRECTORY, 'public');
        }

        $shelter->update($attributes);

        if (isset($previous)) {
            Storage::disk('public')->delete($previous);
        }

        return $shelter;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'name', 'city', 'address', 'phone', 'email', 'description', 'website', 'bin_iin',
        ]));
    }
}
