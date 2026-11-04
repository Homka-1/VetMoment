<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Избранное пользователя (/cabinet/favorites). Анкеты, снятые с публикации, не показываются.
 */
class FavoriteController extends Controller
{
    public function index(Request $request): View
    {
        $pets = $request->user()->favoritePets()
            ->visible()
            ->with(['cover', 'shelter'])
            ->orderByPivot('created_at', 'desc')
            ->paginate(12);

        return view('cabinet.favorites', ['pets' => $pets]);
    }
}
