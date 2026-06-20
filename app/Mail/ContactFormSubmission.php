<?php

namespace App\Mail;

use App\Models\FormDefinition;
use App\Models\FormSubmission;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContactFormSubmission extends Mailable
{
    public function __construct(
        public FormDefinition $form,
        public FormSubmission $submission,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Submission: ' . $this->form->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-form-submission',
        );
    }
}
