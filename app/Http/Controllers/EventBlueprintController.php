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
            ->limit(20)
            ->get(['id', 'title', 'type', 'metric', 'description', 'settings', 'created_by', 'guild_id'])
            ->map(fn (EventBlueprint $blueprint) => [
                'id' => $blueprint->id,
                'title' => $blueprint->title,
                'type' => $blueprint->type,
                'metric' => $blueprint->metric,
                'description' => $blueprint->description,
                'settings' => $blueprint->applicableSettings(),
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
