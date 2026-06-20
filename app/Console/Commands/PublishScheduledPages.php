<?php

namespace App\Console\Commands;

use App\Models\Page;
use Illuminate\Console\Command;

class PublishScheduledPages extends Command
{
    protected $signature = 'pages:publish-scheduled';

    protected $description = 'Publish pages whose scheduled publish_at time has arrived';

    public function handle(): int
    {
        $count = Page::where('status', 'scheduled')
            ->where('publish_at', '<=', now())
            ->update(['status' => 'published', 'publish_at' => null]);

        $this->info("Published {$count} scheduled page(s).");

        return self::SUCCESS;
    }
}
