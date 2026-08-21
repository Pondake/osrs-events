<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventBlueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Reusable event formats, managed the same way Admin\TaskController manages
 * tile tasks — same list/modal shape, same permission style.
 *
 * Gated on canCreateBoards rather than isAdmin: the people who run events
 * are the people who know which formats a clan actually reuses, and making
 * this admin-only means the list goes stale the moment nobody with the ADMIN
 * role is organising anything.
 */
class EventBlueprintController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeManage();

        $blueprints = EventBlueprint::when(
            $request->string('search')->isNotEmpty(),
            fn ($q) => $q->where('title', 'like', '%'.$request->string('search').'%'),
        )->orderBy('title')->get();

        return Inertia::render('Admin/Blueprints', [
            'blueprints' => $blueprints,
            'search' => $request->string('search')->toString(),
        ]);
    }

    /**
     * The autocomplete list for BoardSettingsModal's title field.
     *
     * Not behind the admin gate — every board creator uses this, and it is
     * the read side of the same data the admin page writes.
     */
    public function suggestions(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermission('canCreateBoards'), 403);

        $search = $request->string('search')->toString();

        $blueprints = EventBlueprint::suggestable()
            ->when($search !== '', fn ($q) => $q->where('title', 'like', '%'.$search.'%'))
            ->limit(20)
            ->get(['id', 'title', 'type', 'metric', 'description']);

        return response()->json(['blueprints' => $blueprints]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManage();

        $blueprint = EventBlueprint::create($this->validated($request));

        AuditLog::record('blueprint.created', $blueprint);

        return back()->with('board-save', trans('admin.blueprint_created'));
    }

    public function update(Request $request, EventBlueprint $blueprint): RedirectResponse
    {
        $this->authorizeManage();

        $blueprint->update($this->validated($request));

        AuditLog::record('blueprint.updated', $blueprint);

        return back()->with('board-save', trans('admin.blueprint_updated'));
    }

    public function destroy(EventBlueprint $blueprint): RedirectResponse
    {
        $this->authorizeManage();

        AuditLog::record('blueprint.deleted', $blueprint);

        $blueprint->delete();

        return back()->with('board-save', trans('admin.blueprint_deleted'));
    }

    /**
     * Type and metric are checked against the same lists the create-event
     * form validates against, so a blueprint can never prefill a value that
     * form would then reject — which would look like the blueprint broke it.
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(Event::availableTypes())],
            'metric' => ['nullable', Rule::in(Event::allMetrics())],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function authorizeManage(): void
    {
        abort_unless(Auth::user()->hasPermission('canCreateBoards'), 403);
    }
}
