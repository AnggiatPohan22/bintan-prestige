<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $type
 * @property string $disk
 * @property string $path
 * @property int|null $size_bytes
 * @property string|null $checksum_sha256
 * @property string $status
 * @property string|null $purpose
 * @property array<string,mixed>|null $meta
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Backup extends Model
{
    use HasFactory;

    public const TYPE_DB    = 'db';
    public const TYPE_MEDIA = 'media';

    public const STATUS_OK      = 'ok';
    public const STATUS_FAILED  = 'failed';
    public const STATUS_PRUNED  = 'pruned';
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'type',
        'disk',
        'path',
        'size_bytes',
        'checksum_sha256',
        'status',
        'purpose',
        'meta',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'meta'       => 'array',
    ];
}
