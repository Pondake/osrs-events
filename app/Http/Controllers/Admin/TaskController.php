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
    public function index(Request $request): Response
    {
        abort_unless(Auth::user()->hasPermission('canCreateTiles'), 403);

        $tasks = Task::when(
            $request->string('search')->isNotEmpty(),
            fn ($q) => $q->where('title', 'like', '%'.$request->string('search').'%'),
        )->orderBy('title')->get();

        return Inertia::render('Admin/Tasks', ['tasks' => $tasks, 'search' => $request->string('search')->toString()]);
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

        return back();
    }
}
