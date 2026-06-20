<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageView;
use App\Models\PageViewDailyStat;
use Illuminate\Support\Facades\DB;

class AnalyticsDashboardController extends Controller
{
    public function index()
    {
        $days  = 30;
        $start = now()->subDays($days - 1)->startOfDay();
        $end   = now()->endOfDay();

        // Daily totals for chart (last 30 days from daily stats, fill gaps with 0).
        $statRows = PageViewDailyStat::query()
            ->select('stat_date', DB::raw('SUM(view_count) as views'), DB::raw('SUM(unique_visitors) as uniques'))
            ->whereBetween('stat_date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('stat_date')
            ->orderBy('stat_date')
            ->get()
            ->keyBy('stat_date');

        $chartLabels = [];
        $chartViews  = [];
        $chartUniques = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $chartLabels[]  = $date;
            $chartViews[]   = $statRows[$date]->views ?? 0;
            $chartUniques[] = $statRows[$date]->uniques ?? 0;
        }

        // Top 10 pages by total views (all time).
        $topPages = PageViewDailyStat::query()
            ->select('page_id', DB::raw('SUM(view_count) as total_views'), DB::raw('SUM(unique_visitors) as total_uniques'))
            ->groupBy('page_id')
            ->orderByDesc('total_views')
            ->limit(10)
            ->with('page:id,title,slug')
            ->get();

        // Summary totals for the period.
        $totalViews   = array_sum($chartViews);
        $totalUniques = array_sum($chartUniques);

        return view('backend.analytics.dashboard', compact(
            'chartLabels', 'chartViews', 'chartUniques',
            'topPages', 'totalViews', 'totalUniques', 'days',
        ));
    }

    public function exportCsv()
    {
        $stats = PageViewDailyStat::query()
            ->orderBy('stat_date')
            ->orderBy('page_id')
            ->with('page:id,title,slug')
            ->get();

        $filename = 'page-analytics-' . now()->format('Y-m-d') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($stats) {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, ['Date', 'Page Title', 'Page Slug', 'Views', 'Unique Visitors']);
            foreach ($stats as $row) {
                fputcsv($fh, [
                    $row->stat_date,
                    $row->page->title ?? '(deleted)',
                    $row->page->slug  ?? '',
                    $row->view_count,
                    $row->unique_visitors,
                ]);
            }
            fclose($fh);
        };

        return response()->stream($callback, 200, $headers);
    }
}
