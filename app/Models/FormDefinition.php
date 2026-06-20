<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormDefinition extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'fields',
        'settings',
    ];

    protected $casts = [
        'fields'   => 'array',
        'settings' => 'array',
    ];

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'form_id');
    }

    public function unreadCount(): int
    {
        return $this->submissions()->where('is_read', false)->count();
    }

    public function submitLabel(): string
    {
        return $this->settings['submit_label'] ?? 'Send Message';
    }

    public function successMessage(): string
    {
        return $this->settings['success_message'] ?? 'Thank you! We\'ll get back to you soon.';
    }

    public function notificationEmail(): string
    {
        return $this->settings['notification_email'] ?? config('mail.from.address', '');
    }
}
