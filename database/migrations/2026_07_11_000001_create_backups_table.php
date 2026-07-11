<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 8 (A2) — bookkeeping for automated backups.
 *
 * One row per successful or failed backup artifact. Powers the retention prune,
 * the admin dashboard (B3), and the restore engine (B4). Media backups
 * (type='media', B1) will land in the same table so the admin surface stays a
 * single grid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table) {
            $table->id();

            // 'db' | 'media' — narrow set on purpose; new backup kinds must
            // extend the enum consciously.
            $table->string('type', 16);

            // Laravel filesystem disk name (from config/filesystems.php). Kept
            // as a string so B2's off-machine target is a matter of writing
            // to a different disk without a schema change.
            $table->string('disk', 64);

            // Relative path under the disk root, e.g. 'db/20260711-030000.sql.gz'.
            $table->string('path', 500);

            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->char('checksum_sha256', 64)->nullable();

            // 'ok' | 'failed' | 'pruned' — retention leaves the row so history
            // stays complete, marking the disk file as gone.
            $table->string('status', 16);

            // Human-readable label (e.g. "Auto pre-migrate snapshot" or
            // "Scheduled daily 03:00").
            $table->string('purpose', 255)->nullable();

            // Free-form details: mysqldump options snapshot, media file counts,
            // hostname, source app version, etc.
            $table->json('meta')->nullable();

            $table->timestamps();

            // Dashboard + prune ordering.
            $table->index(['type', 'status', 'created_at'], 'backups_type_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
