<?php

namespace App\Models;

use App\Enums\AttachmentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Вложение отчёта: фото покупки или чек.
 *
 * @property AttachmentType $type
 */
#[Fillable(['campaign_report_id', 'path', 'type', 'sort'])]
class ReportAttachment extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AttachmentType::class,
            'sort' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<CampaignReport, $this>
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(CampaignReport::class, 'campaign_report_id');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
