<?php

namespace App\Support;

use App\Models\Donation;
use Illuminate\Http\Request;

/**
 * Начальные значения формы пожертвования: профиль вошедшего пользователя,
 * а при «Попробовать снова» (?retry={uuid}) — данные неудавшегося платежа, если он этого посетителя.
 */
final class DonationPrefill
{
    public const SESSION_KEY = 'donations.mine';

    /**
     * @return array{amount: ?int, donor_name: ?string, donor_email: ?string, is_anonymous: bool, comment: ?string}
     */
    public static function for(Request $request): array
    {
        $user = $request->user();
        $prefill = [
            'amount' => null,
            'donor_name' => $user?->name,
            'donor_email' => $user?->email,
            'is_anonymous' => false,
            'comment' => null,
        ];

        $retry = $request->query('retry');
        $donation = is_string($retry) && self::owns($request, $retry)
            ? Donation::query()->where('uuid', $retry)->first()
            : null;

        if ($donation) {
            $prefill = [
                'amount' => intdiv($donation->amount, 100),
                'donor_name' => $donation->donor_name,
                'donor_email' => $donation->donor_email,
                'is_anonymous' => $donation->is_anonymous,
                'comment' => $donation->comment,
            ];
        } elseif (is_numeric($request->query('amount'))) {
            $prefill['amount'] = (int) $request->query('amount');
        }

        return $prefill;
    }

    /** Пожертвование создано в этой сессии (гость) или принадлежит вошедшему пользователю. */
    public static function owns(Request $request, string $uuid, ?Donation $donation = null): bool
    {
        if (in_array($uuid, (array) $request->session()->get(self::SESSION_KEY, []), true)) {
            return true;
        }

        $userId = $request->user()?->getKey();

        if ($userId === null) {
            return false;
        }

        return $donation
            ? $donation->user_id === $userId
            : Donation::query()->where('uuid', $uuid)->where('user_id', $userId)->exists();
    }

    public static function remember(Request $request, Donation $donation): void
    {
        $mine = array_slice(array_unique([$donation->uuid, ...(array) $request->session()->get(self::SESSION_KEY, [])]), 0, 20);
        $request->session()->put(self::SESSION_KEY, $mine);
    }
}
