<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ModerationReasonRequest;
use App\Models\Shelter;
use App\Services\ShelterModeration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Действия модерации приютов. Экран очереди модерации — этап 11.
 */
class ShelterModerationController extends Controller
{
    public function __construct(private readonly ShelterModeration $moderation) {}

    public function approve(Shelter $shelter): RedirectResponse
    {
        Gate::authorize('approve', $shelter);
        $this->moderation->approve($shelter);

        return back()->with('status', "Приют «{$shelter->name}» одобрен и опубликован.");
    }

    public function reject(ModerationReasonRequest $request, Shelter $shelter): RedirectResponse
    {
        Gate::authorize('reject', $shelter);
        $this->moderation->reject($shelter, $request->validated('reason'));

        return back()->with('status', "Заявка приюта «{$shelter->name}» отклонена.");
    }

    public function block(ModerationReasonRequest $request, Shelter $shelter): RedirectResponse
    {
        Gate::authorize('block', $shelter);
        $this->moderation->block($shelter, $request->validated('reason'));

        return back()->with('status', "Приют «{$shelter->name}» заблокирован, его страницы скрыты.");
    }

    public function unblock(Shelter $shelter): RedirectResponse
    {
        Gate::authorize('unblock', $shelter);
        $this->moderation->unblock($shelter);

        return back()->with('status', "Приют «{$shelter->name}» разблокирован.");
    }
}
