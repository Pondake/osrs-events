<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventBlueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Blueprints from the host's side: reading them to start an event, and
 * writing one from an event that already exists.
 *
 * Separate from Admin\EventBlueprintController, which is the curator's view —
 * the global list, edited and retired. This is the half every host touches,
 * and it is not behind the admin gate.
 */
class EventBlueprintController extends Controller
{
    /**
     * The list the create form shows.
     *
     * Was an autocomplete on the title alone. It now carries the settings
     * each blueprint would fill in, because the form shows them: picking a
     * format you cannot see the shape of is picking a name, which is what
     * this already was.
     */
    public function suggestions(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermission('canCreateBoards'), 403);

        $search = $request->string('search')->toString();

        $blueprints = EventBlueprint::suggestable()
            ->visibleTo($request->user())
            ->when($search !== '', fn ($q) => $q->where('title', 'like', '%'.$search.'%'))
            ->with('creator:id,discord_username,nickname')
            // Yours first, then your clan's, then the set that ships with the
            // app. A gallery is read top-down, and the formats somebody saved
            // themselves are the ones they are looking for.
            //
            // The order matters more than it looks: this was 20 rows sorted
            // by title, which is fine for an autocomplete and wrong for a
            // gallery — a template called "Weekend format" simply never
            // appeared once the seeded set filled the first twenty.
            ->orderByRaw('CASE WHEN created_by = ? THEN 0 WHEN guild_id IS NOT NULL THEN 1 ELSE 2 END', [$request->user()->id])
            ->orderBy('title')
            ->limit(60)
            ->get(['id', 'title', 'type', 'metric', 'description', 'settings', 'layout', 'created_by', 'guild_id'])
            ->map(fn (EventBlueprint $blueprint) => [
                'id' => $blueprint->id,
                'title' => $blueprint->title,
                'type' => $blueprint->type,
                'metric' => $blueprint->metric,
                'description' => $blueprint->description,
                'settings' => $blueprint->applicableSettings(),
                // Whether it brings a board with it, and how much of one.
                // The layout itself is not sent: it is applied server-side
                // after the event exists, and shipping 81 rows to a picker
                // that only needs to say "includes a board" is 81 rows the
                // browser never reads.
                'layoutCount' => $blueprint->hasLayout() ? count($blueprint->layout) : 0,
                // Where this one came from, so a host can tell the set that
                // ships with the app from one of their clan's own.
                'source' => $blueprint->created_by === null ? 'global' : 'clan',
                'author' => $blueprint->creator?->nickname ?? $blueprint->creator?->discord_username,
            ]);

        return response()->json(['blueprints' => $blueprints]);
    }

    /**
     * Save this event as a reusable format.
     *
     * A copy, not a link: the settings are read once, here, and the event and
     * the template go their separate ways afterwards. Offered while editing
     * and again once an event has finished, which is when a host knows
     * whether the format was worth keeping.
     */
    public function storeFromEvent(Request $request, Event $event): JsonResponse
    {
        $this->assertCanEditEvent($request->user(), $event);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            // Which clan it is for, or none. Checked against the person's own
            // servers for the same reason a team's is (see TeamController):
            // it decides who else sees this, so it is a claim rather than a
            // label.
            'guild_id' => [
                'nullable',
                'string',
                Rule::exists('user_guilds', 'guild_id')->where('user_id', $request->user()->id),
            ],
        ]);

        $blueprint = EventBlueprint::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => $event->type,
            'metric' => $event->metric,
            'is_active' => true,
            'settings' => EventBlueprint::settingsFrom($event),
            // The board as well as its settings. Reusing a format without the
            // tiles is reusing the easy half — the evening a host spends is
            // deciding which task sits where, not picking a grid size.
            'layout' => EventBlueprint::layoutFrom($event),
            'created_by' => $request->user()->id,
            'guild_id' => $data['guild_id'] ?? null,
        ]);

        // The event it came from goes in the changes, not the scope —
        // that argument is a Team, and a blueprint belongs to no team.
        AuditLog::record('blueprint.created', $blueprint, ['from_event' => $event->title]);

        return response()->json([
            'blueprint' => $blueprint->only(['id', 'title', 'type', 'guild_id']),
        ], 201);
    }
}
