<?php

namespace App\Http\Controllers\Cabinet;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * «Мои пожертвования» (/cabinet/donations): история, суммы, статусы.
 */
class DonationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $total = (int) $user->donations()->where('status', PaymentStatus::Succeeded)->sum('amount');

        return view('cabinet.donations', [
            'donations' => $user->donations()->with(['campaign', 'shelter'])->latest('created_at')->latest('id')->paginate(20),
            'total' => $total,
        ]);
    }
}
