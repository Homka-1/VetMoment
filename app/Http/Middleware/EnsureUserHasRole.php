<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Пускает только пользователей с одной из перечисленных ролей: `role:shelter_staff,admin`.
 * Неавторизованных отправляет на вход (как `auth`), чужие роли получают 403.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        $allowed = array_map(fn (string $role) => Role::from($role), $roles);

        abort_unless($user->hasRole(...$allowed), 403, 'Раздел недоступен для вашей роли.');

        return $next($request);
    }
}
