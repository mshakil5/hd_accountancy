@php
    $openExceptions = $exceptions->filter(fn ($exception) => empty($exception->resolved_at));
@endphp

<div class="hd-card">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">Statement exceptions</h5>
        <span class="hd-small">{{ $openExceptions->count() }} unresolved of {{ $exceptions->count() }}</span>
    </div>

    @if($exceptions->isEmpty())
        <div class="hd-small">No exceptions recorded. Automated checks passed.</div>
    @else
        @if($openExceptions->count() < $exceptions->count())
            <div class="hd-small mt-1 mb-2">
                Resolved entries stay listed as an audit record — they are not open issues and do not block verification.
            </div>
        @endif
        <div class="hd-scroll">
            <table class="hd-table">
                <thead><tr><th>Severity</th><th>Issue</th><th>Page</th><th>Detail</th><th style="width:120px;text-align:center;">Action</th></tr></thead>
                <tbody>
                @foreach($exceptions as $exception)
                    <tr class="hd-exception{{ !empty($exception->resolved_at) ? ' is-resolved' : '' }}" id="stmt-exception-{{ $exception->id }}"
                        data-id="{{ $exception->id }}">
                        <td>
                            <span class="hd-pill {{ $exception->severity === 'blocking' ? 'blocking' : 'warning' }}">{{ ucfirst($exception->severity) }}</span>
                        </td>
                        <td>
                            <strong>{{ str_replace('_', ' ', $exception->code) }}</strong>
                            <div class="hd-small">{{ $exception->message }}</div>
                        </td>
                        <td>{{ $exception->page_no ?? '—' }}</td>
                        <td class="hd-small" style="max-width:280px;white-space:pre-wrap;">{{ \Illuminate\Support\Str::limit((string) $exception->source_value, 220) }}</td>
                        <td style="text-align:center;">
                            @if(!empty($exception->resolved_at))
                                <span class="hd-pill ok"><i class="bi bi-check2 mr-1"></i> Resolved</span>
                                @if($exception->resolution)
                                    <div class="hd-small" style="max-width:180px;white-space:pre-wrap;">{{ $exception->resolution }}</div>
                                @endif
                            @else
                                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size:11px;"
                                        data-hd-exception="resolve"
                                        data-resolve-url="{{ route('admin.bank-statements.resolve-exception', $exception->id) }}">
                                    <i class="bi bi-check2 mr-1"></i> Resolve
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
