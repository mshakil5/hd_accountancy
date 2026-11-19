<div class="hd-card">
    <h5>Correction audit trail</h5>
    @if($revisions->isEmpty())
        <div class="hd-small">No corrections recorded yet.</div>
    @else
        <div class="hd-scroll">
            <table class="hd-table">
                <thead><tr><th>When</th><th>Row</th><th>Action</th><th>Original → Corrected</th><th>Reason</th></tr></thead>
                <tbody>
                @foreach($revisions as $revision)
                    <tr>
                        <td style="white-space:nowrap;">{{ \Illuminate\Support\Carbon::parse($revision->created_at)->format('d/m/Y H:i') }}</td>
                        <td>{{ $revision->bank_entry_id ?? 'removed' }}</td>
                        <td>{{ $revision->action }}</td>
                        <td class="hd-small" style="max-width:320px;white-space:pre-wrap;">{{ \Illuminate\Support\Str::limit((string) $revision->original_values, 140) }} → {{ \Illuminate\Support\Str::limit((string) $revision->corrected_values, 140) }}</td>
                        <td class="hd-small">{{ $revision->reason }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
