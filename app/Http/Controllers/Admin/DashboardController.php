<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ShelterStatus;
use App\Http\Controllers\Controller;
use App\Models\Shelter;
use App\Services\PlatformStats;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Статистика администратора (макет 11-stats): итоги месяца и прогулки по неделям.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, PlatformStats $stats): View
    {
        $tz = config('app.display_timezone', 'Asia/Almaty');
        $current = CarbonImmutable::now($tz)->startOfMonth();

        // Месяцы для выбора: текущий и 11 предыдущих.
        $months = collect(range(0, 11))->map(fn (int $i) => $current->subMonthsNoOverflow($i));

        $requested = (string) $request->query('month');
        $month = $months->first(fn (CarbonImmutable $m) => $m->format('Y-m') === $requested) ?? $current;

        $previous = $stats->month($month->subMonthNoOverflow());
        $data = $stats->month($month);

        return view('admin.dashboard', [
            'month' => $month,
            'months' => $months->map(fn (CarbonImmutable $m) => [
                'value' => $m->format('Y-m'),
                'label' => mb_convert_case($m->locale('ru')->translatedFormat('F Y'), MB_CASE_TITLE),
            ])->all(),
            'data' => $data,
            'adoptedDelta' => $data['adopted'] - $previous['adopted'],
            'pendingShelters' => Shelter::query()->where('status', ShelterStatus::Pending)->count(),
            'overall' => $stats->overall(),
        ]);
    }
}
