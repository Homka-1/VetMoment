<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $notifications = $user->notifications()->latest()->get();
        
        // Помечаем как прочитанные при открытии
        $user->unreadNotifications->markAsRead();

        return view('notifications.index', compact('notifications'));
    }
}
