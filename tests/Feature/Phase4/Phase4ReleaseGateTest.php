<?php

namespace Tests\Feature\Phase4;

use App\Http\Middleware\HandleRedirects;
use App\Mail\ContactFormSubmission as ContactFormSubmissionMail;
use App\Models\AuditLog;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Models\Page;
use App\Models\PageView;
use App\Models\PageViewDailyStat;
use App\Models\Plugin;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class Phase4ReleaseGateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    // Q1 — Sitemap is well-formed XML and contains the canonical public URL.
    public function test_q1_sitemap_is_valid_xml_with_expected_public_url(): void
    {
        $page = Page::factory()->published()->create(['slug' => 'release-gate-page']);

        $response = $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/xml; charset=UTF-8');

        $xml = simplexml_load_string($response->getContent());

        $this->assertNotFalse($xml);
        $this->assertStringContainsString(
            route('pages.show', $page->slug, false),
            $response->getContent(),
        );
    }

    // Q2 — Redirect handling remains registered in Laravel's global HTTP stack.
    public function test_q2_redirect_middleware_is_registered_globally(): void
    {
        $middleware = $this->app->make(Kernel::class)->getGlobalMiddleware();

        $this->assertContains(HandleRedirects::class, $middleware);
    }

    // Q3 — Admin activation produces exactly one complete lifecycle audit record.
    public function test_q3_plugin_activation_creates_exactly_one_audit_record(): void
    {
        $plugin = Plugin::factory()->inactive()->create(['name' => 'Release Plugin', 'slug' => 'release-plugin']);

        $this->actingAs($this->admin)
            ->patch(route('admin.plugins.activate', $plugin))
            ->assertRedirect(route('admin.plugins.index'));

        $this->assertLifecycleAudit($plugin->id, 'plugin.activated', 'Release Plugin');
    }

    // Q4 — Admin deactivation produces exactly one complete lifecycle audit record.
    public function test_q4_plugin_deactivation_creates_exactly_one_audit_record(): void
    {
        $plugin = Plugin::factory()->active()->create(['name' => 'Release Plugin', 'slug' => 'release-plugin']);

        $this->actingAs($this->admin)
            ->patch(route('admin.plugins.deactivate', $plugin))
            ->assertRedirect(route('admin.plugins.index'));

        $this->assertLifecycleAudit($plugin->id, 'plugin.deactivated', 'Release Plugin');
    }

    // Q5 — Admin uninstall produces exactly one complete lifecycle audit record.
    public function test_q5_plugin_uninstall_creates_exactly_one_audit_record(): void
    {
        $plugin = Plugin::factory()->inactive()->create(['name' => 'Release Plugin', 'slug' => 'release-plugin']);
        $pluginId = $plugin->id;

        $this->actingAs($this->admin)
            ->delete(route('admin.plugins.destroy', $plugin))
            ->assertRedirect(route('admin.plugins.index'));

        $this->assertLifecycleAudit($pluginId, 'plugin.uninstalled', 'Release Plugin');
    }

    // Q6 — Test transport receives one mail, valid input persists once, invalid input never persists.
    public function test_q6_contact_form_mail_and_persistence_are_atomic_for_valid_input(): void
    {
        Mail::fake();

        $form = $this->makeContactForm();

        $this->post(route('forms.submit', $form->slug), [
            'full_name' => 'Release Visitor',
            'email' => 'visitor@example.com',
        ])->assertRedirect();

        $this->assertSame(1, FormSubmission::where('form_id', $form->id)->count());
        Mail::assertSent(ContactFormSubmissionMail::class, 1);

        $this->from(route('home'))->post(route('forms.submit', $form->slug), [
            'full_name' => '',
            'email' => 'not-an-email',
        ])->assertRedirect(route('home'))->assertSessionHasErrors(['full_name', 'email']);

        $this->assertSame(1, FormSubmission::where('form_id', $form->id)->count());
        Mail::assertSent(ContactFormSubmissionMail::class, 1);
    }

    // Q7 — Aggregate command succeeds and dashboard exposes aligned 30-day chart arrays.
    public function test_q7_analytics_aggregation_and_chart_data_are_correct(): void
    {
        $page = Page::factory()->published()->create();
        $date = now()->toDateString();

        PageView::create(['page_id' => $page->id, 'visitor_hash' => hash('sha256', 'one'), 'viewed_date' => $date]);
        PageView::create(['page_id' => $page->id, 'visitor_hash' => hash('sha256', 'two'), 'viewed_date' => $date]);

        $this->assertSame(0, Artisan::call('analytics:aggregate-daily', ['--date' => $date]));
        $this->assertDatabaseHas('page_view_daily_stats', [
            'page_id' => $page->id,
            'stat_date' => $date,
            'view_count' => 2,
            'unique_visitors' => 2,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.analytics.index'))
            ->assertOk();

        $labels = $response->viewData('chartLabels');
        $views = $response->viewData('chartViews');
        $uniques = $response->viewData('chartUniques');

        $this->assertCount(30, $labels);
        $this->assertCount(30, $views);
        $this->assertCount(30, $uniques);
        $this->assertSame($date, $labels[29]);
        $this->assertSame(2, (int) $views[29]);
        $this->assertSame(2, (int) $uniques[29]);
        $this->assertSame(1, PageViewDailyStat::where('page_id', $page->id)->count());
    }

    private function assertLifecycleAudit(int $pluginId, string $action, string $label): void
    {
        $logs = AuditLog::query()
            ->where('action', $action)
            ->where('auditable_type', 'Plugin')
            ->where('auditable_id', $pluginId)
            ->get();

        $this->assertCount(1, $logs);
        $this->assertSame($this->admin->id, $logs->first()->user_id);
        $this->assertSame($label, $logs->first()->auditable_label);
        $this->assertNotNull($logs->first()->created_at);
    }

    private function makeContactForm(): FormDefinition
    {
        return FormDefinition::create([
            'name' => 'Release Contact',
            'slug' => 'release-contact',
            'fields' => [
                ['type' => 'text', 'name' => 'full_name', 'label' => 'Full Name', 'required' => true],
                ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => true],
            ],
            'settings' => [
                'success_message' => 'Thanks!',
                'notification_email' => 'admin@example.com',
            ],
        ]);
    }
}
