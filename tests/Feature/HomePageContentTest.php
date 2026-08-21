<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The home page is only PARTLY editable: its hero copy and one block region
 * come from a `pages` row, while the auth-dependent button, the admin
 * section and the feature grid stay in the component.
 *
 * These cover the seam — that the row is used when present, that the page
 * still works without it, and that the row does not leak out as a page of
 * its own.
 */
class HomePageContentTest extends TestCase
{
    use RefreshDatabase;

    private function homeRow(array $attributes = []): Page
    {
        return Page::create([
            'slug' => 'home',
            'title' => 'Edited headline',
            'subtitle' => 'Edited standfirst',
            'is_published' => true,
            'blocks' => [['type' => 'prose', 'props' => ['text' => 'An extra paragraph.']]],
            ...$attributes,
        ]);
    }

    #[Test]
    public function the_hero_copy_comes_from_the_page_row_when_one_exists(): void
    {
        $this->homeRow();

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Home')
                ->where('page.title', 'Edited headline')
                ->where('page.subtitle', 'Edited standfirst')
                ->has('page.blocks', 1));
    }

    /** A fresh install without the seeder is a plain home page, not a 500. */
    #[Test]
    public function the_page_still_renders_with_no_row_at_all(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Home')->where('page', null));
    }

    #[Test]
    public function an_unpublished_row_is_ignored_rather_than_blanking_the_page(): void
    {
        $this->homeRow(['is_published' => false]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('page', null));
    }

    /**
     * The row backs a component, so serving it through the /{page} catch-all
     * would put the same copy on a second URL with none of the parts the
     * component adds.
     */
    #[Test]
    public function the_row_is_not_also_served_as_its_own_page(): void
    {
        $this->homeRow();

        $this->get('/home')->assertNotFound();
    }

    /** Listed once, in the group that explains which half is code. */
    #[Test]
    public function the_cms_inventory_does_not_offer_it_as_a_fully_editable_page(): void
    {
        $this->homeRow();
        $admin = tap(User::factory()->create(['osrs_username' => 'Pondake']))
            ->assignRole(Role::findOrCreate('ADMIN', 'web'));

        $this->actingAs($admin)
            ->get('/admin/content')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('pages', 0)
                ->has('partialPages', 1)
                ->where('partialPages.0.slug', 'home'));
    }
}
