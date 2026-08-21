<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Task;
use App\Services\OsrsWikiService as Wiki;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The OSRS Wiki side of the tile/square task picker.
 *
 * Scoped to an event rather than standing on its own, and gated on
 * canEditEvent — the same rule TileController and BingoController use. The
 * alternative, gating on the global canCreateTiles permission, would have
 * meant a board's own owner could not fill in their own board unless they
 * also held the EDITOR role, which is exactly the class of bug the team
 * permissions had.
 *
 * Two endpoints, because picking a wiki page does two different things:
 * searching is a read, and choosing a result writes a Task row.
 */
class WikiController extends Controller
{
    public function search(Request $request, Event $event, Wiki $wiki): JsonResponse
    {
        abort_unless($request->user()->canEditEvent($event), 403);

        return response()->json([
            'results' => $wiki->search($request->string('search')->toString()),
        ]);
    }

    /**
     * Turn a chosen wiki page into a Task, or return the one it already
     * became.
     *
     * Storing the page ON the tile would have meant adding an icon_url and a
     * source_url to bingo_squares AND to tiles, and leaving the two editors
     * with a second kind of "what this asks for" that nothing else in the
     * app understands. Creating a Task instead reuses the one concept both
     * editors already link to — and the task library grows as people use it,
     * so the second person to want "Zulrah" finds it in the local search
     * without the wiki being touched at all.
     */
    public function importTask(Request $request, Event $event, Wiki $wiki): JsonResponse
    {
        abort_unless($request->user()->canEditEvent($event), 403);

        $data = $request->validate([
            'page_id' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
        ]);

        // Re-searched rather than trusted. Everything above arrives from the
        // browser, and taking an icon_url straight off the request would let
        // anyone who can edit a board point a task's icon at an arbitrary
        // host. Matching the submitted page id against a live result means
        // the icon can only ever be one the wiki itself served.
        $page = collect($wiki->search($data['title']))->firstWhere('page_id', $data['page_id']);

        if ($page === null) {
            return response()->json(['message' => trans('tile_editor.wiki_import_failed')], 422);
        }

        // updateOrCreate, not firstOrCreate: the task table is a cache of the
        // wiki now rather than a library people curate, so a page that has
        // been renamed or re-illustrated since it was last imported should
        // correct itself the next time somebody picks it — see
        // Task::wikiCacheIsStale() for the other half, which is what makes
        // an existing row worth re-reading at all.
        $task = Task::updateOrCreate(
            ['wiki_page_id' => $page['page_id']],
            [
                'title' => $page['title'],
                'icon_url' => $page['icon_url'],
                'wiki_url' => $page['url'],
                'wiki_synced_at' => now(),
            ],
        );

        return response()->json(['task' => $task->only(['id', 'title', 'icon_url', 'description', 'wiki_url'])]);
    }
}
