@extends('admin.layouts.admin')
@section('content')
<div class="container-fluid py-4">
<style>
.hd-card{background:#fff;border:1px solid #e5eaf1;border-radius:12px;padding:18px;margin-bottom:16px}
.hd-small{color:#64748b;font-size:12px}
.hd-table{width:100%;font-size:13px}
.hd-table th,.hd-table td{border-bottom:1px solid #e7ebf1;padding:9px;vertical-align:top}
.hd-table th{background:#f1f5f9}
.hd-grid{display:grid;grid-template-columns:minmax(320px,1fr) minmax(420px,1.15fr);gap:16px;align-items:start}
@media(max-width:1200px){.hd-grid{grid-template-columns:1fr}}
.hd-pdf{width:100%;height:78vh;border:1px solid #dbe3ee;border-radius:10px;background:#f8fafc}
.hd-row-form{display:grid;grid-template-columns:110px 1fr 110px 1fr auto;gap:6px;align-items:end}
.hd-row-form input{min-width:0}
.hd-pill{display:inline-block;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600}
.hd-pill.blocking{background:#fee2e2;color:#b91c1c}
.hd-pill.warning{background:#fef3c7;color:#92400e}
.hd-pill.ok{background:#dcfce7;color:#15803d}
.hd-balances{display:flex;gap:22px;flex-wrap:wrap}
.hd-balances div{font-size:12px;color:#64748b}
.hd-balances strong{display:block;font-size:16px;color:#0f172a}
details summary{cursor:pointer;color:#1d4ed8;font-size:12px}
</style>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if($errors->any())
    <div class="alert alert-danger">
        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif

@php
    $status = $import->verification_status;
    $statusLabel = match ($status) {
        'ready_for_reconciliation' => 'Ready for reconciliation',
        'needs_accountant_review' => 'Needs accountant review',
        'extraction_failed' => 'Extraction failed',
        'verified' => 'Statement verified',
        'pending' => 'Pending checks',
        default => ucwords(str_replace('_', ' ', (string) $status)),
    };
    $statusClass = match ($status) {
        'verified' => 'success',
        'ready_for_reconciliation' => 'info',
        'needs_accountant_review' => 'warning',
        'extraction_failed' => 'danger',
        default => 'secondary',
    };
    $bankName = optional($bank)->bank_name ?: 'Unassigned bank';
    $bankLast = optional($bank)->account_last_four;
    $clientName = optional($client)->name ?: $import->client_id;
    $backUrl = route('admin.bank-reconciliation.index', array_filter([
        'client_credential_id' => $client->client_credential_id ?? null,
        'client_id' => $import->client_id,
    ]));
@endphp

<div class="hd-card">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <a href="{{ $backUrl }}" class="hd-small">&larr; Back to reconciliation</a>
            <h4 class="mb-1">Statement verification</h4>
            <div class="hd-small">
                <strong>{{ $import->filename }}</strong>
                · {{ $bankName }} {{ $bankLast ? '••••'.e($bankLast) : '' }}
                · Client: {{ $clientName }}
                · Uploaded {{ \Illuminate\Support\Carbon::parse($import->created_at)->format('d M Y H:i') }}
                · {{ (int) $import->pages_total }} page(s), {{ (int) $import->pages_processed }} with text
            </div>
        </div>
        <div class="text-end">
            <span class="badge bg-{{ $statusClass }}" style="font-size:13px;">{{ $statusLabel }}</span>
            @if($import->verified_at)
                <div class="hd-small mt-1">Verified {{ \Illuminate\Support\Carbon::parse($import->verified_at)->format('d M Y H:i') }}</div>
            @endif
        </div>
    </div>

    <div class="hd-balances mt-3">
        <div>Rows extracted<strong>{{ $entries->count() }}</strong></div>
        <div>Opening balance<strong>{{ $import->opening_balance !== null ? number_format((float) $import->opening_balance, 2) : '—' }}</strong></div>
        <div>Transactions total<strong>{{ number_format($entries->sum(fn ($e) => (float) $e->amount), 2) }}</strong></div>
        <div>Calculated closing<strong>{{ $import->calculated_closing_balance !== null ? number_format((float) $import->calculated_closing_balance, 2) : '—' }}</strong></div>
        <div>PDF closing<strong>{{ $import->closing_balance !== null ? number_format((float) $import->closing_balance, 2) : '—' }}</strong></div>
        <div>Difference<strong style="color:{{ (float) $import->balance_difference != 0.0 ? '#b91c1c' : '#15803d' }}">
            {{ $import->balance_difference !== null ? number_format((float) $import->balance_difference, 2) : '—' }}</strong></div>
        <div>Blocking issues<strong style="color:{{ $blocking ? '#b91c1c' : '#15803d' }}">{{ $blocking }}</strong></div>
    </div>

    @if($import->verification_status === 'verified')
        <div class="alert alert-success mt-3 mb-0">
            Verified. Reconciliation is unlocked for this statement.
            @if($import->verification_note)<div class="hd-small mt-1">Note: {{ $import->verification_note }}</div>@endif
        </div>
    @else
        <form method="POST" action="{{ route('admin.bank-reconciliation.verify-import', $import->id) }}" class="mt-3">
            @csrf
            <div class="row g-2 align-items-end">
                <div class="col-md-8">
                    <label class="hd-small">Verification note (optional)</label>
                    <input type="text" name="verification_note" maxlength="2000" class="form-control form-control-sm"
                           placeholder="e.g. Compared all 3 pages with the PDF; two rows corrected.">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-success btn-sm w-100" @disabled($blocking > 0)>
                        Verify statement &amp; unlock reconciliation
                    </button>
                </div>
            </div>
            @if($blocking > 0)
                <div class="hd-small mt-2" style="color:#b91c1c">
                    {{ $blocking }} blocking exception(s) must be corrected or resolved with a reason first.
                </div>
            @endif
        </form>
    @endif
</div>

<div class="hd-grid">
    <div class="hd-card" style="position:sticky;top:10px;">
        <h5>Original statement</h5>
        @if($import->storage_path)
            <iframe class="hd-pdf" src="{{ route('admin.bank-reconciliation.statement-view', $import->id) }}#page=1"></iframe>
            <div class="mt-2"><a href="{{ route('admin.bank-reconciliation.statement', $import->id) }}" class="hd-small">Download original PDF</a></div>
        @else
            <div class="alert alert-warning mb-0">The original file is not stored for this statement.</div>
        @endif
    </div>

    <div>
        <div class="hd-card">
            <h5>Statement exceptions</h5>
            @if($exceptions->isEmpty())
                <div class="hd-small">No exceptions recorded. Automated checks passed.</div>
            @else
                <div class="hd-scroll">
                <table class="hd-table">
                    <thead><tr><th>Severity</th><th>Issue</th><th>Page</th><th>Detail</th><th>Action</th></tr></thead>
                    <tbody>
                    @foreach($exceptions as $exception)
                        <tr>
                            <td><span class="hd-pill {{ $exception->severity === 'blocking' ? 'blocking' : 'warning' }}">{{ ucfirst($exception->severity) }}</span></td>
                            <td><strong>{{ str_replace('_', ' ', $exception->code) }}</strong>
                                <div class="hd-small">{{ $exception->message }}</div>
                            </td>
                            <td>{{ $exception->page_no ?? '—' }}</td>
                            <td class="hd-small" style="max-width:280px;white-space:pre-wrap;">{{ \Illuminate\Support\Str::limit((string) $exception->source_value, 220) }}</td>
                            <td>
                                @if($exception->resolved_at)
                                    <span class="hd-pill ok">Resolved</span>
                                    <div class="hd-small">{{ $exception->resolution }}</div>
                                @else
                                    <form method="POST" action="{{ route('admin.bank-reconciliation.resolve-exception', $exception->id) }}">
                                        @csrf
                                        <input type="text" name="resolution" required maxlength="1000" class="form-control form-control-sm mb-1" placeholder="How it was checked/why accepted">
                                        <button class="btn btn-outline-secondary btn-sm">Resolve with reason</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                </div>
            @endif
        </div>

        <div class="hd-card">
            <h5>Page coverage</h5>
            <div class="hd-scroll">
            <table class="hd-table">
                <thead><tr><th>Page</th><th>Extracted rows</th><th>Text length</th><th>Result</th></tr></thead>
                <tbody>
                @forelse($pages as $page)
                    <tr>
                        <td>{{ $page->page_no }}</td>
                        <td>{{ $page->extracted_rows }}</td>
                        <td>{{ $page->text_length }}</td>
                        <td>
                            <span class="hd-pill {{ $page->status === 'verified' ? 'ok' : ($page->status === 'review' ? 'blocking' : 'warning') }}">
                                {{ ucwords(str_replace('_', ' ', $page->status)) }}
                            </span>
                            @if($page->note)<div class="hd-small">{{ $page->note }}</div>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="hd-small">No page checks recorded yet.</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </div>

        <div class="hd-card">
            <h5>Extracted rows ({{ $entries->count() }})</h5>
            <div class="hd-small mb-2">Corrections are saved with the original extracted value, your corrected value, your user and the reason.</div>
            <div class="hd-scroll">
            <table class="hd-table">
                <thead><tr><th>Page</th><th>Date</th><th>Description</th><th>Amount</th><th>Balance</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($entries as $entry)
                    <tr>
                        <td>{{ $entry->source_page ?? '—' }}@if($entry->source_position)/{{ $entry->source_position }}@endif</td>
                        <td style="white-space:nowrap;">{{ \Illuminate\Support\Carbon::parse($entry->transaction_date)->format('d/m/Y') }}</td>
                        <td>{{ $entry->description }}</td>
                        <td style="white-space:nowrap;color:{{ (float) $entry->amount < 0 ? '#b91c1c' : '#15803d' }}">{{ number_format((float) $entry->amount, 2) }}</td>
                        <td style="white-space:nowrap;">{{ $entry->balance_after !== null ? number_format((float) $entry->balance_after, 2) : '—' }}</td>
                        <td>
                            @if($entry->verified_at)
                                <span class="hd-pill ok">Row verified</span>
                            @else
                                <span class="hd-pill warning">{{ str_replace('_', ' ', $entry->status) }}</span>
                            @endif
                            <div class="hd-small">{{ $entry->extraction_source }}</div>
                        </td>
                        <td style="min-width:230px;">
                            <details>
                                <summary>Correct this row</summary>
                                <form method="POST" action="{{ route('admin.bank-reconciliation.update-entry', $entry->id) }}" class="mt-1">
                                    @csrf
                                    <input type="date" name="transaction_date" required value="{{ \Illuminate\Support\Carbon::parse($entry->transaction_date)->format('Y-m-d') }}" class="form-control form-control-sm mb-1">
                                    <input type="text" name="description" required maxlength="500" value="{{ $entry->description }}" class="form-control form-control-sm mb-1">
                                    <input type="number" step="0.01" name="amount" required value="{{ number_format((float) $entry->amount, 2, '.', '') }}" class="form-control form-control-sm mb-1">
                                    <input type="text" name="reason" required maxlength="1000" placeholder="Reason for the correction" class="form-control form-control-sm mb-1">
                                    <button class="btn btn-primary btn-sm">Save correction</button>
                                </form>
                            </details>
                            <div class="d-flex gap-1 mt-1 flex-wrap">
                                @unless($entry->verified_at)
                                    <form method="POST" action="{{ route('admin.bank-reconciliation.verify-entry', $entry->id) }}">
                                        @csrf
                                        <button class="btn btn-outline-success btn-sm">Verify row</button>
                                    </form>
                                @endunless
                                <details>
                                    <summary>Remove</summary>
                                    <form method="POST" action="{{ route('admin.bank-reconciliation.destroy-entry', $entry->id) }}" class="mt-1" onsubmit="return confirm('Remove this row from the statement?')">
                                        @csrf
                                        <input type="text" name="reason" required maxlength="1000" placeholder="Why is it not a real transaction?" class="form-control form-control-sm mb-1">
                                        <button class="btn btn-outline-danger btn-sm">Remove row</button>
                                    </form>
                                </details>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="hd-small">No rows extracted. Add a row manually if the PDF is readable.</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>

            <hr>
            <h6>Add a missing row</h6>
            <form method="POST" action="{{ route('admin.bank-reconciliation.store-entry', $import->id) }}" class="hd-row-form">
                @csrf
                <div><label class="hd-small">Date</label><input type="date" name="transaction_date" required class="form-control form-control-sm"></div>
                <div><label class="hd-small">Description</label><input type="text" name="description" required maxlength="500" class="form-control form-control-sm"></div>
                <div><label class="hd-small">Amount</label><input type="number" step="0.01" name="amount" required class="form-control form-control-sm" placeholder="-100.00"></div>
                <div><label class="hd-small">Reason</label><input type="text" name="reason" maxlength="1000" class="form-control form-control-sm" placeholder="Missing from extraction"></div>
                <div><button class="btn btn-primary btn-sm">Add row</button></div>
            </form>
        </div>

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
                            <td style="white-space:nowrap;">{{ \Illuminate\Support\Carbon::parse($revision->created_at)->format('d/m H:i') }}</td>
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
    </div>
</div>
</div>
@endsection
