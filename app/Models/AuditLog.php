<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'auditable_label',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(
        string $action,
        Model $subject,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $label = null,
    ): void {
        if (! auth()->check()) {
            return;
        }

        static::create([
            'user_id'         => auth()->id(),
            'action'          => $action,
            'auditable_type'  => class_basename($subject),
            'auditable_id'    => $subject->getKey(),
            'auditable_label' => $label ?? self::resolveLabel($subject),
            'old_values'      => $oldValues,
            'new_values'      => $newValues,
            'ip_address'      => request()->ip(),
            'user_agent'      => substr(request()->userAgent() ?? '', 0, 500),
            'created_at'      => now(),
        ]);
    }

    private static function resolveLabel(Model $subject): ?string
    {
        foreach (['title', 'name', 'slug', 'email'] as $field) {
            if (isset($subject->{$field})) {
                return (string) $subject->{$field};
            }
        }

        return null;
    }
}
