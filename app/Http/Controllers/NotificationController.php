<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Уведомления пользователя (/notifications): список, переход по уведомлению с отметкой «прочитано»,
 * «Прочитать все». Записи создают классы App\Notifications\* (database-канал).
 */
class NotificationController extends Controller
{
    public const TABS = [
        'all' => 'Все',
        'unread' => 'Непрочитанные',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();
        $tab = array_key_exists((string) $request->query('tab'), self::TABS) ? (string) $request->query('tab') : 'all';

        $notifications = ($tab === 'unread' ? $user->unreadNotifications() : $user->notifications())
            ->paginate(20)
            ->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'tab' => $tab,
            'counts' => [
                'all' => $user->notifications()->count(),
                'unread' => $user->unreadNotificationsCount(),
            ],
        ]);
    }

    /** Открыть уведомление: отметить прочитанным и перейти туда, куда оно ведёт (только внутри сайта). */
    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        $url = (string) ($notification->data['url'] ?? '');

        return str_starts_with($url, url('/'))
            ? redirect()->to($url)
            : redirect()->route('notifications.index');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return redirect()->route('notifications.index')->with('status', 'Все уведомления прочитаны');
    }
}
