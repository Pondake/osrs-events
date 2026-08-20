<?php

namespace App\Http\Controllers\Settings\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only view of the audit log. There is deliberately no store, update or
 * destroy action here and no route for one: a log an admin can edit or clear
 * proves nothing about the admins it exists to hold accountable.
 *
 * Retention, if it's ever needed, belongs in a scheduled prune command with
 * its own explicit window — not in a "clear log" button.
 */
class AuditLogController extends Controller
{
    private const PER_PAGE = 50;

    public function index(Request $request): Response
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $logs = AuditLog::query()
            ->when(
                $request->string('action')->isNotEmpty(),
                fn ($q) => $q->where('action', $request->string('action')),
            )
            // Matches the stored labels, not a join on users: the rows worth
            // searching for hardest are the ones whose actor or target no
            // longer exists, and a join would drop exactly those.
            ->when(
                $request->string('search')->isNotEmpty(),
                fn ($q) => $q->where(function ($sub) use ($request) {
                    $term = '%'.$request->string('search').'%';
                    $sub->where('actor_label', 'like', $term)
                        ->orWhere('target_label', 'like', $term);
                }),
            )
            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Settings/Admin/Audit', [
            'logs' => $logs,
            'actions' => AuditLog::ACTIONS,
            'filters' => [
                'action' => $request->string('action')->toString(),
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }
}
