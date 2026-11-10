<?php

namespace App\Services;

use App\Enums\AttachmentType;
use App\Models\CampaignReport;
use App\Models\DonationCampaign;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Отчёты о расходах сбора: публикуются сразу (published_at = сейчас), вложения — фото и чеки.
 */
class CampaignReports
{
    public function __construct(private readonly PetPhotoStorage $images) {}

    /**
     * @param  array{title: string, body: string, spent_amount: ?int}  $attributes
     * @param  list<UploadedFile>  $photos
     * @param  list<UploadedFile>  $receipts
     */
    public function publish(DonationCampaign $campaign, array $attributes, array $photos, array $receipts): CampaignReport
    {
        $stored = [];

        try {
            return DB::transaction(function () use ($campaign, $attributes, $photos, $receipts, &$stored): CampaignReport {
                $report = $campaign->reports()->create($attributes + ['published_at' => now()]);
                $sort = 0;

                foreach ([[AttachmentType::Photo, $photos], [AttachmentType::Receipt, $receipts]] as [$type, $files]) {
                    foreach ($files as $file) {
                        $saved = $this->images->storeIn($file, "campaigns/{$campaign->id}/reports/{$report->id}");
                        $stored[] = $saved['path'];
                        if ($saved['thumb_path']) {
                            Storage::disk('public')->delete($saved['thumb_path']);
                        }

                        $report->attachments()->create(['path' => $saved['path'], 'type' => $type, 'sort' => $sort++]);
                    }
                }

                return $report;
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($stored);

            throw $e;
        }
    }

    public function delete(CampaignReport $report): void
    {
        $paths = $report->attachments()->pluck('path')->all();

        $report->delete();

        // Демо-вложения сидера лежат в общей папке фото — их не трогаем.
        Storage::disk('public')->delete(array_filter($paths, fn (string $p) => str_starts_with($p, 'campaigns/')));
    }
}
