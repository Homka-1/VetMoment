<?php

namespace App\Http\Requests\Shelter;

use App\Models\DonationCampaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * Отчёт о расходах сбора: заголовок, текст (строки «— покупка — 8 000 ₸» показываются таблицей),
 * сколько потрачено, до 10 вложений — фото покупок и чеков.
 */
class CampaignReportRequest extends FormRequest
{
    public const MAX_FILES = 10;

    public function authorize(): bool
    {
        $campaign = $this->route('campaign');

        return $campaign instanceof DonationCampaign && $this->user()->can('manageReports', $campaign);
    }

    protected function prepareForValidation(): void
    {
        $spent = preg_replace('/[\s\x{00A0}₸]/u', '', (string) $this->input('spent'));

        $this->merge([
            'title' => trim((string) $this->input('title')),
            'spent' => $spent === '' ? null : $spent,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $image = ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'];

        return [
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'min:20', 'max:5000'],
            'spent' => ['nullable', 'integer', 'min:0', 'max:'.CampaignRequest::GOAL_MAX],
            'photos' => ['array', 'max:'.self::MAX_FILES],
            'photos.*' => $image,
            'receipts' => ['array', 'max:'.self::MAX_FILES],
            'receipts.*' => $image,
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                if (count($this->photos()) + count($this->receipts()) > self::MAX_FILES) {
                    $validator->errors()->add('photos', 'Не больше '.self::MAX_FILES.' файлов в одном отчёте.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'заголовок',
            'body' => 'текст отчёта',
            'spent' => 'потрачено',
            'photos.*' => 'фото',
            'receipts.*' => 'чек',
        ];
    }

    /**
     * @return list<UploadedFile>
     */
    public function photos(): array
    {
        return array_values(array_filter((array) $this->file('photos', []), fn ($f) => $f instanceof UploadedFile));
    }

    /**
     * @return list<UploadedFile>
     */
    public function receipts(): array
    {
        return array_values(array_filter((array) $this->file('receipts', []), fn ($f) => $f instanceof UploadedFile));
    }
}
