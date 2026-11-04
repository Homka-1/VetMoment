<?php

namespace App\Http\Controllers\Pets;

use App\Http\Controllers\Controller;
use App\Models\Pet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * «В избранное» на карточке и в каталоге. Гость попадает на вход и возвращается на страницу,
 * с которой нажал (redirect()->guest() для POST запоминает предыдущий URL).
 */
class FavoriteController extends Controller
{
    public function toggle(Request $request, Pet $pet): RedirectResponse
    {
        $user = $request->user();
        $exists = $user->favoritePets()->whereKey($pet->getKey())->exists();

        if ($exists) {
            $user->favoritePets()->detach($pet);

            return back()->with('status', "{$pet->name} — убрали из избранного");
        }

        Gate::authorize('favorite', $pet);
        $user->favoritePets()->syncWithoutDetaching([$pet->getKey()]);

        return back()->with('status', "{$pet->name} — в избранном");
    }
}
