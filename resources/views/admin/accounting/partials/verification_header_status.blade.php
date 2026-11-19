<div class="text-end">
    <span class="badge bg-{{ $statusClass }}" style="font-size:13px;">{{ $statusLabel }}</span>
    @if($import->verified_at)
        <div class="hd-small mt-1">Verified {{ \Illuminate\Support\Carbon::parse($import->verified_at)->format('d/m/Y H:i') }}</div>
    @endif
</div>
