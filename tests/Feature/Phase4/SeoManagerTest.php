<?php

namespace Tests\Feature\Phase4;

use App\Models\Page;
use App\Models\Redirect;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeoManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        Cache::flush();
    }

    private function publishedPage(array $attrs = []): Page
    {
        return Page::factory()->published()->create($attrs);
    }

    // O1 — Sitemap returns 200 with XML content-type.
    public function test_o1_sitemap_returns_xml(): void
    {
        $this->get(route('sitemap'))
             ->assertOk()
             ->assertHeader('Content-Type', 'text/xml; charset=UTF-8');
    }

    // O2 — Sitemap includes published page URLs.
    public function test_o2_sitemap_includes_published_pages(): void
    {
        $page = $this->publishedPage(['slug' => 'about-us']);

        $response = $this->get(route('sitemap'));
        $response->assertOk();
        $this->assertStringContainsString('about-us', $response->getContent());
    }

    // O3 — Sitemap does NOT include draft pages.
    public function test_o3_sitemap_excludes_draft_pages(): void
    {
        Page::factory()->draft()->create(['slug' => 'hidden-draft']);

        $response = $this->get(route('sitemap'));
        $response->assertOk();
        $this->assertStringNotContainsString('hidden-draft', $response->getContent());
    }

    // O4 — Active 301 redirect redirects correctly.
    public function test_o4_active_301_redirect(): void
    {
        Redirect::create([
            'from_url'    => '/old-page',
            'to_url'      => '/new-page',
            'status_code' => 301,
            'is_active'   => true,
        ]);

        $this->get('/old-page')
             ->assertRedirect('/new-page')
             ->assertStatus(301);
    }

    // O5 — Active 302 redirect uses correct status code.
    public function test_o5_active_302_redirect(): void
    {
        Redirect::create([
            'from_url'    => '/temp-old',
            'to_url'      => '/temp-new',
            'status_code' => 302,
            'is_active'   => true,
        ]);

        $this->get('/temp-old')
             ->assertRedirect('/temp-new')
             ->assertStatus(302);
    }

    // O6 — Inactive redirect does NOT redirect (request passes through).
    public function test_o6_inactive_redirect_does_not_fire(): void
    {
        Redirect::create([
            'from_url'    => '/inactive-old',
            'to_url'      => '/inactive-new',
            'status_code' => 301,
            'is_active'   => false,
        ]);

        // Should NOT be a redirect (404 or 200, but not a redirect to /inactive-new)
        $response = $this->get('/inactive-old');
        $this->assertNotSame('/inactive-new', $response->headers->get('Location'));
    }

    // O7 — Redirect matching is exact path only (no partial match).
    public function test_o7_redirect_is_exact_match(): void
    {
        Redirect::create([
            'from_url'    => '/exact',
            'to_url'      => '/target',
            'status_code' => 301,
            'is_active'   => true,
        ]);

        // /exact-extra should NOT match /exact
        $response = $this->get('/exact-extra');
        $this->assertNotSame('/target', $response->headers->get('Location'));
    }

    // O8 — Admin can list redirects.
    public function test_o8_admin_can_list_redirects(): void
    {
        Redirect::create(['from_url' => '/listed', 'to_url' => '/dest', 'status_code' => 301, 'is_active' => true]);

        $this->actingAs($this->admin)
             ->get(route('admin.seo.redirects.index'))
             ->assertOk()
             ->assertSee('/listed');
    }

    // O9 — Admin can create a redirect.
    public function test_o9_admin_can_create_redirect(): void
    {
        $this->actingAs($this->admin)
             ->post(route('admin.seo.redirects.store'), [
                 'from_url'    => '/created-old',
                 'to_url'      => '/created-new',
                 'status_code' => 301,
                 'is_active'   => '1',
             ])
             ->assertRedirect(route('admin.seo.redirects.index'));

        $this->assertDatabaseHas('redirects', ['from_url' => '/created-old']);
    }

    // O10 — Admin can delete a redirect.
    public function test_o10_admin_can_delete_redirect(): void
    {
        $redirect = Redirect::create(['from_url' => '/del-old', 'to_url' => '/del-new', 'status_code' => 301, 'is_active' => true]);

        $this->actingAs($this->admin)
             ->delete(route('admin.seo.redirects.destroy', $redirect))
             ->assertRedirect(route('admin.seo.redirects.index'));

        $this->assertDatabaseMissing('redirects', ['id' => $redirect->id]);
    }

    // O11 — /robots.txt returns 200 with text/plain content-type.
    public function test_o11_robots_txt_returns_plain_text(): void
    {
        Storage::fake('local');

        $this->get(route('robots'))
             ->assertOk()
             ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    }

    // O12 — Custom robots.txt content is served after admin saves it.
    public function test_o12_custom_robots_content_is_served(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('seo/robots.txt', "User-agent: *\nDisallow: /private\n");

        $response = $this->get(route('robots'));
        $response->assertOk();
        $this->assertStringContainsString('Disallow: /private', $response->getContent());
    }

    // O13 — Default robots.txt content served when no custom file exists.
    public function test_o13_default_robots_served_when_no_file(): void
    {
        Storage::fake('local');

        $response = $this->get(route('robots'));
        $response->assertOk();
        $this->assertStringContainsString('Allow:', $response->getContent());
    }

    // O14 — Admin can save robots.txt via PUT.
    public function test_o14_admin_can_save_robots_txt(): void
    {
        Storage::fake('local');

        $this->actingAs($this->admin)
             ->put(route('admin.seo.robots.update'), [
                 'content' => "User-agent: *\nDisallow: /admin\n",
             ])
             ->assertRedirect(route('admin.seo.robots.edit'));

        Storage::disk('local')->assertExists('seo/robots.txt');
        $this->assertStringContainsString('Disallow: /admin', Storage::disk('local')->get('seo/robots.txt'));
    }

    // O15 — Page with seo_robots set renders the correct robots meta tag.
    public function test_o15_page_seo_robots_overrides_meta_tag(): void
    {
        $page = $this->publishedPage([
            'slug'       => 'noindex-page',
            'seo_robots' => 'noindex, follow',
        ]);

        $this->get(route('pages.show', $page->slug))
             ->assertOk()
             ->assertSee('noindex, follow', false);
    }
}
