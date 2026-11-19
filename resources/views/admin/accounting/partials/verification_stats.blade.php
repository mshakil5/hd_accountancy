@php
    $difference = $stats['difference'];
    $cards = [
        ['rows', 'Rows extracted', $stats['rows'], '#0f172a'],
        ['opening', 'Opening balance', $stats['opening'] !== null ? number_format($stats['opening'], 2) : '—', '#0f172a'],
        ['total', 'Transactions total', number_format($stats['total'], 2), $stats['total'] < 0 ? '#b91c1c' : '#15803d'],
        ['calculated', 'Calculated closing', $stats['calculated'] !== null ? number_format($stats['calculated'], 2) : '—', '#0f172a'],
        ['closing', 'PDF closing', $stats['closing'] !== null ? number_format($stats['closing'], 2) : '—', '#0f172a'],
        ['difference', 'Difference', $difference !== null ? number_format($difference, 2) : '—', $difference !== null && abs($difference) >= 0.01 ? '#b91c1c' : '#15803d'],
        ['blocking', 'Blocking issues', $stats['blocking'], $stats['blocking'] > 0 ? '#b91c1c' : '#15803d'],
    ];
@endphp

<div class="hd-stat-bar">
    @foreach($cards as [$key, $label, $value, $color])
        <div class="hd-stat" data-hd-stat="{{ $key }}">
            <span class="hd-stat__label">{{ $label }}</span>
            <span class="hd-stat__val" style="color:{{ $color }}">{{ $value }}</span>
        </div>
    @endforeach
</div>
