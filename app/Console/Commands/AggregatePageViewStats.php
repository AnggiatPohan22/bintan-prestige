<?php

namespace App\Console\Commands;

use App\Models\PageViewDailyStat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AggregatePageViewStats extends Command
{
    protected $signature   = 'analytics:aggregate-daily {--date= : Date to aggregate (Y-m-d). Defaults to yesterday.}';
    protected $description = 'Aggregate raw page_views into page_view_daily_stats for a given date.';

    public function handle(): int
    {
        $date = $this->option('date')
            ? \Carbon\Carbon::parse($this->option('date'))->toDateString()
            : now()->subDay()->toDateString();

        $count = $this->aggregate($date);

        $this->info("Aggregated {$count} page(s) for {$date}.");

        return self::SUCCESS;
    }

    public function aggregate(string $date): int
    {
        $rows = DB::table('page_views')
            ->select('page_id', DB::raw('COUNT(*) as view_count'), DB::raw('COUNT(DISTINCT visitor_hash) as unique_visitors'))
            ->where('viewed_date', $date)
            ->groupBy('page_id')
            ->get();

        foreach ($rows as $row) {
            PageViewDailyStat::updateOrCreate(
                ['page_id' => $row->page_id, 'stat_date' => $date],
                ['view_count' => $row->view_count, 'unique_visitors' => $row->unique_visitors],
            );
        }

        return $rows->count();
    }
}
