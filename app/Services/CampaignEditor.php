<?php

namespace App\Services;

use App\Enums\CampaignStatus;
use App\Models\DonationCampaign;
use App\Models\Shelter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Сборы в кабинете приюта: создание, правка, запуск, закрытие, архив.
 * Суммы сбора здесь не пересчитываются — только статус от изменившейся цели.
 */
class CampaignEditor
{
    public function __construct(private readonly PetPhotoStorage $images) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(Shelter $shelter, array $attributes, ?UploadedFile $cover, bool $publish): DonationCampaign
    {
        return $this->withCleanup(function () use ($shelter, $attributes, $cover, $publish): DonationCampaign {
            $campaign = $shelter->campaigns()->create($attributes + [
                'status' => $publish ? CampaignStatus::Active : CampaignStatus::Draft,
                'starts_at' => now(),
            ]);

            if ($cover) {
                $campaign->forceFill(['cover_path' => $this->storeCover($campaign, $cover)])->save();
            }

            return $campaign;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(DonationCampaign $campaign, array $attributes, ?UploadedFile $cover, bool $removeCover, bool $publish): DonationCampaign
    {
        $oldCover = $campaign->cover_path;

        $campaign = $this->withCleanup(function () use ($campaign, $attributes, $cover, $removeCover, $publish): DonationCampaign {
            $campaign = DonationCampaign::query()->lockForUpdate()->findOrFail($campaign->id);
            $campaign->fill($attributes);

            if ($publish && $campaign->status === CampaignStatus::Draft) {
                // Срок отсчитывается от запуска, а не от создания черновика.
                $campaign->forceFill(['status' => CampaignStatus::Active, 'starts_at' => now()]);
            }

            if ($cover) {
                $campaign->cover_path = $this->storeCover($campaign, $cover);
            } elseif ($removeCover) {
                $campaign->cover_path = null;
            }

            $this->syncGoalStatus($campaign);
            $campaign->save();

            return $campaign;
        });

        if ($oldCover && $oldCover !== $campaign->cover_path) {
            Storage::disk('public')->delete($oldCover);
        }

        return $campaign;
    }

    /** «Закрыть сбор»: деньги больше не принимаются, сбор остаётся на сайте с отчётами. */
    public function close(DonationCampaign $campaign): void
    {
        $campaign->forceFill(['status' => CampaignStatus::Closed])->save();
    }

    /** «Удалить» = архив: сбор пропадает с сайта, пожертвования и отчёты сохраняются. */
    public function archive(DonationCampaign $campaign): void
    {
        $campaign->forceFill(['status' => CampaignStatus::Archived])->save();
    }

    /** Цель изменили: достигнута → «Цель достигнута», подняли выше собранного → снова «Идёт сбор». */
    private function syncGoalStatus(DonationCampaign $campaign): void
    {
        if ($campaign->status === CampaignStatus::Active && $campaign->isGoalReached()) {
            $campaign->forceFill(['status' => CampaignStatus::Completed, 'completed_at' => now()]);
        } elseif ($campaign->status === CampaignStatus::Completed && ! $campaign->isGoalReached()) {
            $campaign->forceFill(['status' => CampaignStatus::Active, 'completed_at' => null]);
        }
    }

    private function storeCover(DonationCampaign $campaign, UploadedFile $file): string
    {
        $stored = $this->images->storeIn($file, "campaigns/{$campaign->id}");

        // Превью не нужно: обложка и так показывается в карточке, отдельный файл удаляем.
        if ($stored['thumb_path']) {
            Storage::disk('public')->delete($stored['thumb_path']);
        }

        return $stored['path'];
    }

    /**
     * Транзакция; если она откатилась — удалить уже записанные файлы.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function withCleanup(callable $callback): mixed
    {
        $before = Storage::disk('public')->allFiles('campaigns');

        try {
            return DB::transaction($callback);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete(array_diff(Storage::disk('public')->allFiles('campaigns'), $before));

            throw $e;
        }
    }
}
