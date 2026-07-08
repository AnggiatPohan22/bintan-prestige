<?php

namespace Tests\Feature\Phase6;

use App\Models\FormDefinition;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 6 — A1 carry-over debt clearing.
 *
 * TD-04: widget text content must be sanitized on render (was raw {!! !!}).
 * TD-05: the contact_form block must not query FormDefinition in Blade —
 *        resolution happens in App\Support\PageRenderData.
 */
class A1DebtClearingTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // TD-04 — widget text sanitization
    // -------------------------------------------------------------------------

    public function test_td04_widget_text_content_is_sanitized_on_render(): void
    {
        $widget = new Widget([
            'widget_type' => 'text',
            'data' => [
                'heading' => 'Safe Heading',
                'content' => '<p>Keep <strong>this</strong>.</p><script>alert(1)</script>',
            ],
        ]);

        $html = (string) $this->view('frontend.widgets.text', ['widget' => $widget]);

        $this->assertStringContainsString('Keep <strong>this</strong>.', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
    }

    public function test_td04_widget_text_blade_does_not_emit_raw_unsanitized_content(): void
    {
        $contents = file_get_contents(resource_path('views/frontend/widgets/text.blade.php'));

        $this->assertStringNotContainsString('{!! $content !!}', $contents);
        $this->assertStringContainsString('InlineContentSanitizer::richtext', $contents);
    }

    // -------------------------------------------------------------------------
    // TD-05 — contact_form block query moved out of Blade
    // -------------------------------------------------------------------------

    public function test_td05_contact_form_block_renders_resolved_form_definition(): void
    {
        $form = FormDefinition::create([
            'name' => 'Trip Inquiry',
            'slug' => 'trip-inquiry',
            'fields' => [
                ['type' => 'text', 'name' => 'full_name', 'label' => 'Full Name', 'required' => true],
                ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => true],
            ],
            'settings' => ['submit_label' => 'Send Inquiry'],
        ]);

        $page = Page::create([
            'title' => 'Contact Page',
            'slug' => 'contact-page',
            'status' => 'published',
        ]);
        PageBlock::create([
            'page_id' => $page->id,
            'block_type' => 'contact_form',
            'label' => 'Contact form',
            'data' => ['form_definition_id' => $form->id, 'title' => 'Talk to us'],
            'sort_order' => 0,
            'is_visible' => true,
        ]);

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertSee('Talk to us')
            ->assertSee('action="'.route('forms.submit', $form->slug).'"', false)
            ->assertSee('Send Inquiry')
            ->assertSee('name="full_name"', false);
    }

    public function test_td05_contact_form_resolution_does_not_run_per_block_queries(): void
    {
        $form = FormDefinition::create([
            'name' => 'Trip Inquiry',
            'slug' => 'trip-inquiry',
            'fields' => [['type' => 'text', 'name' => 'full_name', 'label' => 'Full Name', 'required' => true]],
            'settings' => ['submit_label' => 'Send'],
        ]);

        $page = Page::create([
            'title' => 'Contact Page',
            'slug' => 'contact-page',
            'status' => 'published',
        ]);
        foreach (range(0, 2) as $sortOrder) {
            PageBlock::create([
                'page_id' => $page->id,
                'block_type' => 'contact_form',
                'label' => 'Contact form '.$sortOrder,
                'data' => ['form_definition_id' => $form->id],
                'sort_order' => $sortOrder,
                'is_visible' => true,
            ]);
        }

        $formQueries = 0;
        DB::listen(function ($query) use (&$formQueries): void {
            if (str_contains(strtolower($query->sql), 'from "form_definitions"')) {
                $formQueries++;
            }
        });

        $this->get(route('pages.show', $page->slug))->assertOk();

        // Three contact_form blocks share one resolution query, not one per block.
        $this->assertSame(1, $formQueries);
    }

    public function test_td05_contact_form_blade_does_not_reference_form_definition_model(): void
    {
        $contents = file_get_contents(resource_path('views/frontend/blocks/contact-form.blade.php'));

        $this->assertStringNotContainsString('FormDefinition::', $contents);
        $this->assertStringNotContainsString('App\\Models\\', $contents);
        $this->assertStringNotContainsString('::find(', $contents);
    }
}
