<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Supporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The list behind /supporters. Admin-only: it publishes people's names.
 */
class SupporterController extends Controller
{
    public function index(): Response
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        return Inertia::render('Admin/Supporters', [
            'supporters' => Supporter::query()->ordered()->get()->map(fn (Supporter $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'roles' => $s->roles,
                'link' => $s->link,
                'sort_order' => $s->sort_order,
                'consented' => $s->consented_at !== null,
                'is_visible' => $s->is_visible,
            ]),
            'roles' => Supporter::ROLES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $supporter = Supporter::create($this->attributes($request));

        AuditLog::record('supporter.created', $supporter);

        return back()->with('board-save', trans('admin.supporter_created'));
    }

    public function update(Request $request, Supporter $supporter): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $supporter->update($this->attributes($request, $supporter));

        AuditLog::record('supporter.updated', $supporter);

        return back()->with('board-save', trans('admin.supporter_updated'));
    }

    public function destroy(Supporter $supporter): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        AuditLog::record('supporter.deleted', $supporter);

        $supporter->delete();

        return back()->with('board-save', trans('admin.supporter_deleted'));
    }

    /** @return array<string, mixed> */
    private function attributes(Request $request, ?Supporter $supporter = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::in(Supporter::ROLES)],
            'link' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:-9999', 'max:9999'],
            'consented' => ['required', 'boolean'],
            'is_visible' => ['required', 'boolean'],
        ]);

        return [
            'name' => $data['name'],
            'roles' => array_values(array_intersect(Supporter::ROLES, $data['roles'])),
            'link' => $data['link'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            // Keeps the original date when consent was already on record.
            'consented_at' => $data['consented'] ? ($supporter?->consented_at ?? now()) : null,
            'is_visible' => $data['is_visible'],
        ];
    }
}
