<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The CMS: lists editable pages and saves their block documents.
 *
 * Validation here checks that a document is *well formed* — known block
 * types, sane nesting, bounded size. It deliberately does NOT re-validate
 * every prop against the block vocabulary. That allowlist lives in
 * resources/js/Components/Cms/blocks.js and runs at render time, which it
 * has to do anyway since seeders and fixtures write to this table too;
 * duplicating it in PHP would give two schemas to keep in step while adding
 * no protection the renderer isn't already providing.
 */
class ContentController extends Controller
{
    public function index(): Response
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        return Inertia::render('Admin/Content', [
            'pages' => Page::query()
                // Partial pages are listed in their own group below, with a
                // note about which half is code. Without this they appeared
                // twice — once here as if wholly editable, which is the one
                // impression this inventory exists to prevent.
                ->whereNotIn('slug', Page::PARTIAL_SLUGS)
                ->orderBy('slug')
                // `blocks` is selected because blockCount below reads it.
                // Without it every page reported "0 blocks" — the column was
                // never loaded, so the count was counting null.
                ->get(['id', 'slug', 'title', 'subtitle', 'is_published', 'updated_at', 'blocks'])
                ->map(fn (Page $page) => [
                    'id' => $page->id,
                    'slug' => $page->slug,
                    'title' => $page->title,
                    'subtitle' => $page->subtitle,
                    'isPublished' => $page->is_published,
                    'updatedAt' => $page->updated_at,
                    'blockCount' => is_array($page->blocks ?? null) ? count($page->blocks) : 0,
                ]),
            // Pages where only PART of the content is editable. Listed
            // apart from the fully-editable ones so nobody opens the editor
            // expecting to find the whole page in it — what is missing from
            // the editor is missing because it is behaviour, not because it
            // was forgotten.
            'partialPages' => [
                [
                    'slug' => 'home',
                    'path' => '/',
                    'label' => 'Home',
                    'editable' => 'Headline, standfirst, and a content region below the preview.',
                    'logic' => 'The hero button changes with whether you are signed in, the admin shortcuts only appear for admins, and the feature and guide grids are structured lists rather than blocks.',
                ],
                [
                    'slug' => 'osrs-snakes-and-ladders',
                    'path' => '/osrs-snakes-and-ladders',
                    'label' => 'Snakes & Ladders',
                    'editable' => 'The FAQ. Each entry is also published as FAQPage structured data, so editing one updates what Google reads.',
                    'logic' => 'The five how-it-works steps and the board-size cards drive HowTo schema, and the page title, description and canonical URL are set in code.',
                ],
                [
                    'slug' => 'osrs-clan-events',
                    'path' => '/osrs-clan-events',
                    'label' => 'Clan Events',
                    'editable' => 'The FAQ, which is also published as FAQPage structured data.',
                    'logic' => 'The feature and access-mode cards, plus the page title, description and canonical URL.',
                ],
                [
                    'slug' => 'osrs-event-ideas',
                    'path' => '/osrs-event-ideas',
                    'label' => 'Event Ideas',
                    'editable' => 'Nothing yet — the row exists so copy can move here without another migration.',
                    'logic' => 'The eight event formats drive ItemList schema, so they are a code list rather than blocks.',
                ],
            ],
            // Pages that still live as hardcoded Vue components. Listed so
            // the inventory stays honest about what the CMS does not cover
            // yet, rather than implying every public page is editable.
            // Empty now that the landing pages have rows. Kept rather than
            // deleted: the next page someone hardcodes belongs here, and an
            // inventory that cannot express "not covered" stops being honest.
            'staticPages' => [],
        ]);
    }

    public function edit(Page $page): Response
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        return Inertia::render('Admin/ContentEdit', [
            'page' => [
                'id' => $page->id,
                'slug' => $page->slug,
                // Where this content is actually published. Not always
                // "/{slug}": a partial page's row backs a component mounted
                // somewhere else, and /home 404s by design. Sent from here
                // rather than built in the template, which cannot know.
                'publicPath' => $page->publicPath(),
                'title' => $page->title,
                'subtitle' => $page->subtitle,
                'seoTitle' => $page->seo_title,
                'seoDescription' => $page->seo_description,
                'isPublished' => $page->is_published,
                'blocks' => $page->blocks ?? [],
            ],
        ]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'is_published' => ['required', 'boolean'],
            'blocks' => ['present', 'array', 'max:'.Page::MAX_BLOCKS],
            'blocks.*.type' => ['required', 'string', Rule::in(Page::BLOCK_TYPES)],
            'blocks.*.props' => ['present', 'array'],
        ]);

        // The document to STORE is the raw input, not $data['blocks'].
        //
        // validate() returns only the keys it was given rules for. With
        // `blocks.*.type` and `blocks.*.props` named but not `blocks.*.blocks`,
        // every nested child array was stripped out of the validated result —
        // so saving any page silently emptied its sections. Caught by opening
        // /about after a save and finding its buttons gone.
        //
        // The rules above still do their job: they reject a malformed
        // document. They just don't decide what gets written.
        $blocks = $request->input('blocks', []);

        $this->assertNestingWithinLimit($blocks);

        $data['blocks'] = $blocks;

        $before = [
            'title' => $page->title,
            'blocks' => count($page->blocks ?? []),
            'is_published' => $page->is_published,
        ];

        $page->update($data);

        // Same diff-before-write shape as the site settings: the log should
        // say what changed, not restate the whole form on every save.
        $changes = [];
        if ($before['title'] !== $page->title) {
            $changes['name'] = ['from' => $before['title'], 'to' => $page->title];
        }
        if ($before['is_published'] !== $page->is_published) {
            $changes['published'] = ['from' => $before['is_published'], 'to' => $page->is_published];
        }
        if ($before['blocks'] !== count($page->blocks ?? [])) {
            $changes['blocks'] = ['from' => $before['blocks'], 'to' => count($page->blocks ?? [])];
        }

        AuditLog::record('page.updated', $page, $changes);

        return back()->with('board-save', trans('cms.saved'));
    }

    /**
     * Walks nested `blocks` arrays. Laravel's validation rules can express a
     * fixed depth but not a recursive one, and an unbounded document would
     * hit the renderer's own guard and silently lose its deepest content —
     * better to refuse the save and say so.
     *
     * @param  array<int, mixed>  $blocks
     */
    private function assertNestingWithinLimit(array $blocks, int $depth = 0): void
    {
        abort_if($depth > Page::MAX_DEPTH, 422, trans('cms.too_deep'));

        foreach ($blocks as $block) {
            if (is_array($block['blocks'] ?? null)) {
                $this->assertNestingWithinLimit($block['blocks'], $depth + 1);
            }
        }
    }
}
