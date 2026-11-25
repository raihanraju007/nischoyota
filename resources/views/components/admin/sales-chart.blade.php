{{--
    Single-series bar chart (CSS only). Each bar shows a tooltip on hover;
    the same data is rendered as a visually hidden table for screen readers.

    Usage: <x-admin.sales-chart :days="[['label' => 'Mon', 'date' => '14 Sep', 'sales' => 1850000, 'orders' => 21], …]" />
    sales are paisa (F-028).
--}}
@props(['days'])

@php
    $toTaka = fn (int $paisa): int => intdiv($paisa, 100);
    $maxSales = max([0, ...array_map($toTaka, array_column($days, 'sales'))]);
    $axisMax = max(1000, (int) ceil($maxSales / 10000) * 10000);
    $ticks = array_map(fn (int $step): int => (int) ($axisMax / 4 * $step), [4, 3, 2, 1, 0]);
@endphp

<div @class(['bar-chart', 'bar-chart-dense' => count($days) > 14]) aria-hidden="true">
    <div class="bar-chart-axis">
        @foreach ($ticks as $tick)
            <span>{{ $tick >= 1000 ? ($tick / 1000).'k' : $tick }}</span>
        @endforeach
    </div>
    <div class="bar-chart-plot">
        @foreach ($days as $day)
            <div class="bar-col" style="--h: {{ round($toTaka($day['sales']) / $axisMax * 100, 2) }}%">
                <div class="bar"></div>
                <div class="bar-tip">
                    <strong><x-money :amount="$day['sales']" /></strong>
                    {{ $day['date'] }} · {{ trans_choice(':count order|:count orders', $day['orders']) }}
                </div>
            </div>
        @endforeach
    </div>
    <div class="bar-chart-labels">
        @foreach ($days as $day)
            <span>{{ $day['label'] }}</span>
        @endforeach
    </div>
</div>

<table class="visually-hidden">
    <caption>Sales per day</caption>
    <thead><tr><th>Date</th><th>Sales</th><th>Orders</th></tr></thead>
    <tbody>
        @foreach ($days as $day)
            <tr><td>{{ $day['date'] }}</td><td><x-money :amount="$day['sales']" /></td><td>{{ $day['orders'] }}</td></tr>
        @endforeach
    </tbody>
</table>
