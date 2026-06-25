@extends('layouts.admin')

@section('content')
<div class="admin-page">
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Analytics</h1>
            <p class="admin-page-subtitle">
                Page views for the last {{ $days }} days &mdash;
                <strong>{{ number_format($totalViews) }}</strong> views,
                <strong>{{ number_format($totalUniques) }}</strong> unique visitors.
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.analytics.export') }}"
               class="admin-btn admin-btn--secondary">
                <i class="fa-solid fa-file-csv mr-1" aria-hidden="true"></i>
                Export CSV
            </a>
        </div>
    </div>

    {{-- 30-day chart --}}
    <div class="admin-card mb-6">
        <div class="admin-card-header">
            <h2 class="admin-card-title">Daily Page Views — Last {{ $days }} Days</h2>
        </div>
        <div class="admin-card-body">
            <canvas id="analytics-chart" height="90"></canvas>
        </div>
    </div>

    {{-- Top 10 pages --}}
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title">Top 10 Pages (All Time)</h2>
        </div>

        @if($topPages->isEmpty())
            <div class="admin-card-body text-admin-secondary text-sm">
                No data yet. Page views are aggregated nightly.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Page</th>
                            <th class="text-right">Total Views</th>
                            <th class="text-right">Unique Visitors</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topPages as $i => $stat)
                            <tr>
                                <td class="text-admin-secondary">{{ $i + 1 }}</td>
                                <td>
                                    @if($stat->page)
                                        <a href="{{ route('admin.pages.edit', $stat->page) }}"
                                           class="text-blue-600 hover:underline">
                                            {{ $stat->page->title }}
                                        </a>
                                        <span class="text-xs text-admin-secondary ml-1">/{{ $stat->page->slug }}</span>
                                    @else
                                        <span class="text-admin-secondary">(deleted page)</span>
                                    @endif
                                </td>
                                <td class="text-right font-medium">{{ number_format($stat->total_views) }}</td>
                                <td class="text-right text-admin-secondary">{{ number_format($stat->total_uniques) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const labels   = @json($chartLabels);
    const views    = @json($chartViews);
    const uniques  = @json($chartUniques);

    const ctx = document.getElementById('analytics-chart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Page Views',
                    data: views,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                },
                {
                    label: 'Unique Visitors',
                    data: uniques,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16,185,129,0.08)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                },
            ],
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top' },
                tooltip: { mode: 'index' },
            },
            scales: {
                x: {
                    ticks: { maxTicksLimit: 10, maxRotation: 0 },
                },
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 },
                },
            },
        },
    });
})();
</script>
@endpush
@endsection
