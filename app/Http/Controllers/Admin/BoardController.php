<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Board;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/** Ported from BoardsService::findAllAdmin() — every board, not just is_listed ones. */
class BoardController extends Controller
{
    public function index(): Response
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $boards = Board::with(['authors.user', 'boardTeams.team'])
            ->orderByDesc('start_date')
            ->get();

        return Inertia::render('Admin/Boards', ['boards' => $boards]);
    }
}
