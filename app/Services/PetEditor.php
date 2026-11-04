<?php

namespace App\Services;

use App\Enums\PetStatus;
use App\Models\Pet;
use App\Models\PetPhoto;
use App\Models\Shelter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Создание и редактирование анкеты в кабинете приюта: поля, фото (загрузка, удаление, порядок, обложка)
 * и последствия смены статуса. Проверки ввода и правило «публикация — только с фото и после модерации» —
 * в App\Http\Requests\Shelter\PetRequest.
 */
class PetEditor
{
    public function __construct(
        private readonly PetPhotoStorage $storage,
        private readonly AdoptionApplications $applications,
    ) {}

    /**
     * @param  array<string, mixed>  $data  PetRequest::petAttributes()
     * @param  array{new: list<UploadedFile>, existing: array<int|string, array{sort?: mixed, delete?: mixed}>, cover: string|null}  $photos
     */
    public function create(Shelter $shelter, array $data, array $photos): Pet
    {
        return $this->save(new Pet(['shelter_id' => $shelter->getKey()]), $data, $photos);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array{new: list<UploadedFile>, existing: array<int|string, array{sort?: mixed, delete?: mixed}>, cover: string|null}  $photos
     */
    public function update(Pet $pet, array $data, array $photos): Pet
    {
        return $this->save($pet, $data, $photos);
    }

    /** «Удалить анкету» = перевести в архив: история заявок и выгулов сохраняется. */
    public function archive(Pet $pet): void
    {
        $pet->update(['status' => PetStatus::Archived]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array{new: list<UploadedFile>, existing: array<int|string, array{sort?: mixed, delete?: mixed}>, cover: string|null}  $photos
     */
    private function save(Pet $pet, array $data, array $photos): Pet
    {
        $wasAdopted = $pet->exists && $pet->status === PetStatus::Adopted;
        $status = $data['status'] instanceof PetStatus ? $data['status'] : PetStatus::from($data['status']);

        $data['adopted_at'] = match (true) {
            $status !== PetStatus::Adopted => null,
            $wasAdopted => $pet->adopted_at,
            default => now(),
        };

        $stored = [];

        try {
            DB::transaction(function () use ($pet, $data, $photos, $status, $wasAdopted, &$stored): void {
                $pet->fill($data)->save();

                $this->syncPhotos($pet, $photos, $stored);

                if ($status === PetStatus::Adopted && ! $wasAdopted) {
                    $this->applications->closeForAdoptedPet($pet);
                }
            });
        } catch (\Throwable $e) {
            // Транзакция откатилась — новые файлы на диске больше никому не принадлежат.
            foreach ($stored as $photo) {
                $photo->deleteFiles();
            }

            throw $e;
        }

        return $pet->refresh();
    }

    /**
     * @param  array{new: list<UploadedFile>, existing: array<int|string, array{sort?: mixed, delete?: mixed}>, cover: string|null}  $photos
     * @param  list<PetPhoto>  $stored
     */
    private function syncPhotos(Pet $pet, array $photos, array &$stored): void
    {
        $existing = $pet->photos()->get()->keyBy('id');
        $cover = $photos['cover'] ?? null;
        $coverId = null;

        foreach ($photos['existing'] as $id => $input) {
            $photo = $existing->get((int) $id);
            if (! $photo) {
                continue;
            }

            if (! empty($input['delete'])) {
                $photo->delete();
                $photo->deleteFiles();
                $existing->forget($photo->id);

                continue;
            }

            if (isset($input['sort']) && is_numeric($input['sort'])) {
                $photo->sort = max(0, min(65535, (int) $input['sort']));
            }
        }

        $nextSort = (int) ($existing->max('sort') ?? -1) + 1;

        foreach (array_values($photos['new']) as $index => $file) {
            $paths = $this->storage->store($file, $pet->getKey());
            $photo = $pet->photos()->make($paths + ['sort' => $nextSort++, 'is_cover' => false]);
            $photo->save();
            $stored[] = $photo;
            $existing->put($photo->id, $photo);

            if ($cover === "new:{$index}") {
                $coverId = $photo->id;
            }
        }

        if ($cover !== null && str_starts_with($cover, 'existing:')) {
            $id = (int) substr($cover, 9);
            $coverId = $existing->has($id) ? $id : null;
        }

        // Ровно одна обложка: выбранная, иначе прежняя, иначе первое фото по порядку.
        $coverId ??= $existing->firstWhere('is_cover', true)?->id;
        $coverId ??= $existing->sortBy([['sort', 'asc'], ['id', 'asc']])->first()?->id;

        foreach ($existing as $photo) {
            $photo->is_cover = $photo->id === $coverId;
            if ($photo->isDirty()) {
                $photo->save();
            }
        }

        $pet->unsetRelation('photos')->unsetRelation('cover');
    }
}
