{{--
    Dashboard KPI tile.

    Usage: <x-admin.stat-card icon="cart" color="blue" label="Orders" value="248" trend="+8.2%" />
    Props:
        color  primary | amber | blue | rose
        trend  Change vs previous period; a leading "-" renders it as a decline.
        note   Plain line under the label, e.g. "12 orders".
--}}
@props([
    'icon',
    'label',
    'value',
    'color' => 'primary',
    'trend' => null,
    'trendLabel' => 'vs last week',
    'note' => null,
])

<div class="stat-card">
    <div class="stat-icon stat-icon-{{ $color }}">
        <x-icon :name="$icon" />
    </div>
    <div class="stat-body">
        <div class="stat-num">{{ $value }}</div>
        <div class="stat-label">{{ $label }}</div>
        @if ($note)
            <div class="stat-note">{{ $note }}</div>
        @endif
        @if ($trend)
            <div @class(['stat-trend', str_starts_with($trend, '-') ? 'down' : 'up'])>
                <x-icon name="trending-up" class="icon-sm" /> {{ $trend }} <span>{{ $trendLabel }}</span>
            </div>
        @endif
    </div>
</div>
