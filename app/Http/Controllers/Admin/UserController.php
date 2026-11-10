<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserModeration;
use App\Support\Plural;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Пользователи: список с поиском и фильтром по роли, блокировка и разблокировка.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $role = Role::tryFrom((string) $request->query('role'));
        $blocked = $request->boolean('blocked');
        $search = trim((string) $request->query('q'));

        $users = User::query()
            ->when($role, fn ($q) => $q->where('role', $role))
            ->when($blocked, fn ($q) => $q->where('is_blocked', true))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('login', 'like', '%'.mb_strtolower($search).'%')
                ->orWhere('email', 'like', '%'.mb_strtolower($search).'%')
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('name', 'like', '%'.mb_convert_case($search, MB_CASE_TITLE).'%')))
            ->with('shelters')
            ->latest()->latest('id')
            ->paginate(20)->withQueryString();

        return view('admin.users', [
            'users' => $users,
            'role' => $role,
            'blocked' => $blocked,
            'search' => $search,
            'blockedCount' => User::query()->where('is_blocked', true)->count(),
        ]);
    }

    public function block(User $user, UserModeration $moderation): RedirectResponse
    {
        Gate::authorize('block', $user);
        $cancelled = $moderation->block($user);

        $message = "Пользователь «{$user->login}» заблокирован";

        if ($cancelled > 0) {
            $message .= '; отменено '.Plural::format($cancelled, ['запись', 'записи', 'записей']).' на выгул';
        }

        return back()->with('status', $message);
    }

    public function unblock(User $user, UserModeration $moderation): RedirectResponse
    {
        Gate::authorize('unblock', $user);
        $moderation->unblock($user);

        return back()->with('status', "Пользователь «{$user->login}» разблокирован");
    }
}
