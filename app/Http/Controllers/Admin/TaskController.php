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

    public function destroy(Task $task): RedirectResponse
    {
        abort_unless(Auth::user()->hasPermission('canCreateTiles'), 403);

        AuditLog::record('task.deleted', $task);
        $task->delete();

        return back()->with('board-save', trans('admin.task_deleted'));
    }
}
