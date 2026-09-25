<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/** OSRS task templates used for tile autocomplete — ported from TasksService. */
class TaskController extends Controller
{
    /**
     * The gaps worth hunting for. A task without a wiki link has nothing to
     * link to from the tile card or the claim dialog, and after the seeder
     * the list runs into the hundreds — so the filter exists to find the
     * rows still needing a hand, not to browse.
     */
    public const FILTERS = ['with_wiki', 'without_wiki', 'without_icon'];

    public function index(Request $request): Response
    {
        abort_unless(Auth::user()->hasPermission('canCreateTiles'), 403);

        $search = $request->string('search')->toString();
        $filter = $request->string('filter')->toString();

        if (! in_array($filter, self::FILTERS, true)) {
            $filter = '';
        }

        $tasks = Task::query()
            ->when($search !== '', fn ($q) => $q->where('title', 'like', '%'.$search.'%'))
            // Both halves test null AND '': the column is a plain nullable
            // string with nothing stopping an empty one, and a row written
            // outside a request (seeder, wiki import) never passes through
            // ConvertEmptyStringsToNull. Checking only for null would file
            // those under "has a link" and hide exactly the rows the filter
            // is for.
            ->when($filter === 'with_wiki', fn ($q) => $q->whereNotNull('wiki_url')->where('wiki_url', '!=', ''))
            ->when($filter === 'without_wiki', fn ($q) => $q->where(
                fn ($w) => $w->whereNull('wiki_url')->orWhere('wiki_url', ''),
            ))
            ->when($filter === 'without_icon', fn ($q) => $q->where(
                fn ($w) => $w->whereNull('icon_url')->orWhere('icon_url', ''),
            ))
            ->orderBy('title')
            ->get();

        return Inertia::render('Admin/Tasks', [
            'tasks' => $tasks,
            'search' => $search,
            'filter' => $filter,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()->hasPermission('canCreateTiles'), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'icon_url' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        Task::create(['id' => (string) str()->uuid(), ...$data]);

        return back()->with('board-save', trans('admin.task_created'));
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        abort_unless(Auth::user()->hasPermission('canCreateTiles'), 403);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'icon_url' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        $task->update($data);

        return back()->with('board-save', trans('admin.task_updated'));
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        abort_unless(Auth::user()->hasPermission('canCreateTiles'), 403);

        // Optional, not required: a note explaining why is worth capturing
        // when someone bothers to write one, but forcing it on every delete
        // is exactly the friction the popover confirm already adds.
        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        AuditLog::record('task.deleted', $task, array_filter([
            'note' => $data['note'] ?? null,
        ]));

        // Soft delete (see the task's own SoftDeletes note) — every tile or
        // bingo square already using this task keeps its task_id pointing
        // here the whole time, which is what makes restore() below a
        // complete undo rather than a same-title task with none of its old
        // links back.
        $task->delete();

        // No board-save flash here on purpose — the frontend shows its own
        // toast with an Undo action once the delete actually lands, and a
        // second toast saying the same thing plainer would just be noise
        // stacked on top of it.
        return back();
    }

    /** Undo for the delete above — see TaskController::destroy(). */
    public function restore(string $task): RedirectResponse
    {
        abort_unless(Auth::user()->hasPermission('canCreateTiles'), 403);

        $model = Task::withTrashed()->findOrFail($task);
        $model->restore();

        AuditLog::record('task.restored', $model);

        return back()->with('board-save', trans('admin.task_restored'));
    }
}
