<?php

namespace App\Http\Controllers\Shelter;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\Shelter;
use App\Support\LocalTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Пожертвования приюта (/shelter/donations): фильтры (сбор / общий фонд, статус, период), итоги, экспорт CSV.
 * Приют видит email и статус платежа; имя анонимного жертвователя тоже видно, с пометкой.
 */
class DonationController extends Controller
{
    public function index(Request $request): View
    {
        $shelter = $request->user()->shelter();
        Gate::authorize('manage', $shelter);

        $filters = $this->filters($request);
        $query = $this->query($shelter, $filters);

        $totals = (clone $query)->toBase()->reorder()
            ->where('status', PaymentStatus::Succeeded)
            ->selectRaw('COALESCE(SUM(amount), 0) AS total, COUNT(*) AS cnt')->first();

        return view('shelter.donations.index', [
            'shelter' => $shelter,
            'donations' => $query->with('campaign')->paginate(30)->withQueryString(),
            'filters' => $filters,
            'total' => (int) $totals->total,
            'paidCount' => (int) $totals->cnt,
            'campaigns' => $shelter->campaigns()->latest('starts_at')->pluck('title', 'id')->all(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $shelter = $request->user()->shelter();
        Gate::authorize('manage', $shelter);

        $query = $this->query($shelter, $this->filters($request))->with('campaign');
        $name = 'donations-'.$shelter->slug.'-'.LocalTime::of(now())->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            // BOM и «;» — чтобы Excel с русской локалью открыл файл без мастера импорта.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Дата (Алматы)', 'Сумма, ₸', 'Статус', 'Назначение', 'Имя', 'Email', 'Анонимно', 'Комментарий', 'ID платежа'], ';');

            foreach ($query->lazy(500) as $donation) {
                fputcsv($out, [
                    LocalTime::of($donation->paid_at ?? $donation->created_at)->format('d.m.Y H:i'),
                    number_format($donation->amount / 100, $donation->amount % 100 ? 2 : 0, ',', ''),
                    $donation->status->label(),
                    $donation->campaign?->title ?? 'Общий фонд',
                    self::cell($donation->donor_name),
                    self::cell($donation->donor_email),
                    $donation->is_anonymous ? 'да' : 'нет',
                    self::cell($donation->comment),
                    $donation->uuid,
                ], ';');
            }

            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Текст от жертвователя не должен стать формулой в Excel («=HYPERLINK(…)»). */
    private static function cell(?string $value): ?string
    {
        return $value !== null && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }

    /**
     * @return array{campaign: ?string, status: ?string, from: ?string, to: ?string}
     */
    private function filters(Request $request): array
    {
        $date = fn (string $key) => is_string($request->query($key)) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $request->query($key))
            ? $request->query($key) : null;
        $campaign = (string) $request->query('campaign');

        return [
            // id сбора или «fund» — общий фонд приюта
            'campaign' => $campaign === 'fund' || ctype_digit($campaign) ? $campaign : null,
            'status' => PaymentStatus::tryFrom((string) $request->query('status'))?->value,
            'from' => $date('from'),
            'to' => $date('to'),
        ];
    }

    /**
     * @param  array{campaign: ?string, status: ?string, from: ?string, to: ?string}  $filters
     * @return HasMany<Donation, Shelter>
     */
    private function query(Shelter $shelter, array $filters): HasMany
    {
        $tz = config('app.display_timezone');

        return $shelter->donations()
            ->when($filters['campaign'] === 'fund', fn (Builder $q) => $q->whereNull('campaign_id'))
            ->when($filters['campaign'] && $filters['campaign'] !== 'fund', fn (Builder $q) => $q->where('campaign_id', (int) $filters['campaign']))
            ->when($filters['status'], fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filters['from'], fn (Builder $q, string $from) => $q->where('created_at', '>=', Carbon::parse($from, $tz)->startOfDay()->utc()))
            ->when($filters['to'], fn (Builder $q, string $to) => $q->where('created_at', '<=', Carbon::parse($to, $tz)->endOfDay()->utc()))
            ->latest('created_at')->latest('id');
    }
}
