<?php

namespace App\Models;

use Database\Factories\CampaignReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Отчёт о расходовании средств сбора (текст + фото/чеки).
 */
#[Fillable(['campaign_id', 'title', 'body', 'spent_amount', 'published_at'])]
class CampaignReport extends Model
{
    /** @use HasFactory<CampaignReportFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'spent_amount' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DonationCampaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(DonationCampaign::class, 'campaign_id');
    }

    /**
     * @return HasMany<ReportAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ReportAttachment::class)->orderBy('sort');
    }
}
