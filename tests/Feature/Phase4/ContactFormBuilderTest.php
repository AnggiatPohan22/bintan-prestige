<?php

namespace Tests\Feature\Phase4;

use App\Mail\ContactFormSubmission as ContactFormSubmissionMail;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactFormBuilderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    private function makeForm(array $overrides = []): FormDefinition
    {
        return FormDefinition::create(array_merge([
            'name'     => 'Contact Us',
            'slug'     => 'contact-us',
            'fields'   => [
                ['type' => 'text',  'name' => 'full_name', 'label' => 'Full Name', 'required' => true],
                ['type' => 'email', 'name' => 'email',     'label' => 'Email',     'required' => true],
                ['type' => 'textarea', 'name' => 'message', 'label' => 'Message',  'required' => false],
            ],
            'settings' => [
                'submit_label'      => 'Send',
                'success_message'   => 'Thanks!',
                'notification_email'=> 'admin@example.com',
            ],
        ], $overrides));
    }

    // M1 — Admin can view form definitions list.
    public function test_m1_admin_can_view_form_definitions_index(): void
    {
        $this->makeForm();

        $this->actingAs($this->admin)
            ->get(route('admin.forms.index'))
            ->assertOk()
            ->assertSee('Contact Us');
    }

    // M2 — Admin can create a form definition.
    public function test_m2_admin_can_create_form_definition(): void
    {
        $fields = json_encode([
            ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'required' => true],
            ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => true],
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.forms.store'), [
                'name'   => 'Booking Inquiry',
                'slug'   => 'booking-inquiry',
                'fields' => $fields,
            ])
            ->assertRedirect(route('admin.forms.index'));

        $this->assertDatabaseHas('form_definitions', ['slug' => 'booking-inquiry']);
    }

    // M3 — Admin can update a form definition.
    public function test_m3_admin_can_update_form_definition(): void
    {
        $form = $this->makeForm();

        $fields = json_encode([
            ['type' => 'text', 'name' => 'full_name', 'label' => 'Full Name', 'required' => true],
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.forms.update', $form), [
                'name'   => 'Updated Form',
                'slug'   => 'updated-form',
                'fields' => $fields,
            ])
            ->assertRedirect(route('admin.forms.edit', $form));

        $this->assertSame('Updated Form', $form->fresh()->name);
    }

    // M4 — Admin can delete a form definition; cascade removes submissions.
    public function test_m4_admin_can_delete_form_definition_and_cascades_submissions(): void
    {
        $form = $this->makeForm();
        FormSubmission::create([
            'form_id'    => $form->id,
            'data'       => ['full_name' => 'Alice'],
            'is_read'    => false,
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.forms.destroy', $form))
            ->assertRedirect(route('admin.forms.index'));

        $this->assertDatabaseMissing('form_definitions', ['id' => $form->id]);
        $this->assertDatabaseMissing('form_submissions', ['form_id' => $form->id]);
    }

    // M5 — Visitor can submit a contact form and submission is stored.
    public function test_m5_visitor_can_submit_contact_form(): void
    {
        Mail::fake();
        $form = $this->makeForm();

        $this->post(route('forms.submit', $form->slug), [
            'full_name' => 'Alice',
            'email'     => 'alice@example.com',
            'message'   => 'Hello!',
        ])->assertRedirect();

        $this->assertDatabaseHas('form_submissions', ['form_id' => $form->id]);
    }

    // M6 — Honeypot-filled submission is silently dropped (not stored).
    public function test_m6_honeypot_submission_is_dropped(): void
    {
        $form = $this->makeForm();

        $this->post(route('forms.submit', $form->slug), [
            'full_name' => 'Bot',
            'email'     => 'bot@evil.com',
            '_hp'       => 'i-am-a-bot',
        ])->assertRedirect();

        $this->assertDatabaseMissing('form_submissions', ['form_id' => $form->id]);
    }

    // M7 — Submission is linked to the correct FormDefinition.
    public function test_m7_submission_is_linked_to_correct_form(): void
    {
        Mail::fake();
        $form = $this->makeForm();

        $this->post(route('forms.submit', $form->slug), [
            'full_name' => 'Bob',
            'email'     => 'bob@example.com',
        ]);

        $submission = FormSubmission::where('form_id', $form->id)->first();
        $this->assertNotNull($submission);
        $this->assertSame($form->id, $submission->form_id);
        $this->assertSame('Bob', $submission->data['full_name']);
    }

    // M8 — Admin can view submissions inbox for a form.
    public function test_m8_admin_can_view_submissions_inbox(): void
    {
        $form = $this->makeForm();
        FormSubmission::create([
            'form_id'    => $form->id,
            'data'       => ['full_name' => 'Carol', 'email' => 'carol@example.com'],
            'is_read'    => false,
            'ip_address' => '10.0.0.1',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.forms.submissions.index', $form))
            ->assertOk()
            ->assertSee('Carol');
    }

    // M9 — Admin can mark a submission as read.
    public function test_m9_admin_can_mark_submission_as_read(): void
    {
        $form = $this->makeForm();
        $submission = FormSubmission::create([
            'form_id' => $form->id, 'data' => ['full_name' => 'Dave'],
            'is_read' => false, 'ip_address' => null,
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.form-submissions.read', $submission));

        $this->assertTrue($submission->fresh()->is_read);
    }

    // M10 — Admin can delete a submission.
    public function test_m10_admin_can_delete_submission(): void
    {
        $form = $this->makeForm();
        $submission = FormSubmission::create([
            'form_id' => $form->id, 'data' => ['full_name' => 'Eve'],
            'is_read' => false, 'ip_address' => null,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.form-submissions.destroy', $submission))
            ->assertRedirect(route('admin.forms.submissions.index', $form));

        $this->assertDatabaseMissing('form_submissions', ['id' => $submission->id]);
    }

    // M11 — contact_form block type is accepted by the block store endpoint.
    public function test_m11_contact_form_block_type_is_valid(): void
    {
        $page = Page::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.page-blocks.store', $page), [
                'block_type' => 'contact_form',
                'label'      => 'Contact Section',
            ])
            ->assertRedirect(route('admin.pages.edit', $page));

        $this->assertDatabaseHas('page_blocks', [
            'page_id'    => $page->id,
            'block_type' => 'contact_form',
        ]);
    }

    // M12 — Submitting a form sends a ContactFormSubmission mail notification.
    public function test_m12_submission_sends_email_notification(): void
    {
        Mail::fake();

        $form = $this->makeForm([
            'settings' => ['notification_email' => 'admin@example.com'],
        ]);

        $this->post(route('forms.submit', $form->slug), [
            'full_name' => 'Frank',
            'email'     => 'frank@example.com',
        ]);

        Mail::assertSent(ContactFormSubmissionMail::class, function ($mail) {
            return $mail->hasTo('admin@example.com');
        });
    }
}
