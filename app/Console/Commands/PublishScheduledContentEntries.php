<?php

namespace App\Console\Commands;

use App\Models\ContentEntry;
use Illuminate\Console\Command;

class PublishScheduledContentEntries extends Command
{
    protected $signature = 'content-entries:publish-scheduled';

    protected $description = 'Publish content entries whose scheduled published_at time has arrived';

    public function handle(): int
    {
        // published_at is the publish timestamp itself, so it is kept (unlike
        // pages, which use a separate publish_at column that is nulled).
        $count = ContentEntry::where('status', 'scheduled')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->update(['status' => 'published']);

        $this->info("Published {$count} scheduled content entry(ies).");

        return self::SUCCESS;
    }
}
