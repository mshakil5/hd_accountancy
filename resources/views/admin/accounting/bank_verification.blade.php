@extends('admin.layouts.admin')
@section('content')
<div class="container-fluid py-4">
<style>
.hd-card{background:#fff;border:1px solid #e5eaf1;border-radius:12px;padding:18px;margin-bottom:16px}
.hd-small{color:#64748b;font-size:12px}
.hd-scroll{overflow-x:auto;}
.hd-table{width:100%;font-size:13px;table-layout:auto}
.hd-table th,.hd-table td{border-bottom:1px solid #e7ebf1;padding:9px;vertical-align:top}
.hd-table th{background:#f1f5f9}
.hd-grid{display:grid;grid-template-columns:minmax(320px,1fr) minmax(420px,1.15fr);gap:16px;align-items:start}
@media(max-width:1200px){.hd-grid{grid-template-columns:1fr}}
.hd-pdf{width:100%;height:78vh;border:1px solid #dbe3ee;border-radius:10px;background:#f8fafc}
.hd-row-form{display:grid;grid-template-columns:110px 1fr 110px 1fr auto;gap:6px;align-items:end}
.hd-row-form input{min-width:0}
.hd-pill{display:inline-block;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600;white-space:nowrap;}
.hd-pill.blocking{background:#fee2e2;color:#b91c1c}
.hd-pill.warning{background:#fef3c7;color:#92400e}
.hd-pill.ok{background:#dcfce7;color:#15803d}
.hd-balances{display:flex;gap:22px;flex-wrap:wrap}
.hd-balances div{font-size:12px;color:#64748b}
.hd-balances strong{display:block;font-size:16px;color:#0f172a}
details summary{cursor:pointer;color:#1d4ed8;font-size:12px}
.hd-table td details{position:relative;display:inline-block;vertical-align:top;}
.hd-table td details form{position:absolute;left:0;top:100%;z-index:20;background:#fff;border:1px solid #e5eaf1;border-radius:8px;padding:8px;min-width:260px;box-shadow:0 4px 12px rgba(0,0,0,0.08);max-width:320px;overflow:hidden;}
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
    $backUrl = route('admin.bank-statements.index', array_filter([
        'client_credential_id' => $client->client_credential_id ?? null,
        'client_id' => $import->client_id,
    ]));
@endphp

<div class="hd-card">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <a href="{{ $backUrl }}" class="hd-small">&larr; Back to Bank Statements</a>
            <h4 class="mb-1">Statement verification</h4>
            <div class="hd-small">
                <strong>{{ $import->filename }}</strong>
                · {{ $bankName }} {{ $bankLast ? '••••'.e($bankLast) : '' }}
                · Client: {{ $clientName }}
                · Uploaded {{ \Illuminate\Support\Carbon::parse($import->created_at)->format('d M Y H:i') }}
                · {{ (int) $import->pages_total }} page(s), {{ (int) $import->pages_processed }} with text
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <form method="POST" action="{{ route('admin.bank-statements.rescan-ocr', $import->id) }}" onsubmit="if(!confirm('Re-run deep OCR scan on this entire PDF statement? This will scan all pages with OCR, re-extract transactions, and recalculate system balances.')){ return false; } const b=this.querySelector('button'); b.disabled=true; b.innerHTML='<span class=\'spinner-border spinner-border-sm mr-1\'></span> Scanning with OCR...'; return true;">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm" title="Force high-resolution OCR scan of all PDF pages">
                    <i class="bi bi-cpu mr-1"></i> Re-Scan with OCR
                </button>
            </form>
            <div class="text-end">
                <span class="badge bg-{{ $statusClass }}" style="font-size:13px;">{{ $statusLabel }}</span>
                @if($import->verified_at)
                    <div class="hd-small mt-1">Verified {{ \Illuminate\Support\Carbon::parse($import->verified_at)->format('d M Y H:i') }}</div>
                @endif
            </div>
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
            <div class="mt-2">
                <a href="{{ route('admin.bank-reconciliation.index', array_filter(['client_credential_id' => $client->client_credential_id ?? null, 'client_id' => $import->client_id])) }}"
                   class="btn btn-success btn-sm">
                    <i class="bi bi-bank mr-1"></i> Go to Bank Reconciliation &rarr;
                </a>
            </div>
        </div>
    @else
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3 pt-2 border-top">
            <div>
                @if($blocking > 0)
                    <span class="hd-small font-weight-bold" style="color:#b91c1c">
                        <i class="bi bi-exclamation-triangle-fill mr-1"></i> {{ $blocking }} blocking exception(s) must be resolved before verifying statement.
                    </span>
                @else
                    <span class="hd-small text-success font-weight-bold">
                        <i class="bi bi-check-circle-fill mr-1"></i> All checks passed! Ready to verify.
                    </span>
                @endif
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <form method="POST" action="{{ route('admin.bank-statements.rescan-ocr', $import->id) }}" onsubmit="if(!confirm('Re-run deep OCR scan on this entire PDF statement? This will scan all pages with OCR, re-extract transactions, and recalculate system balances.')){ return false; } const b=this.querySelector('button'); b.disabled=true; b.innerHTML='<span class=\'spinner-border spinner-border-sm mr-1\'></span> Scanning with OCR...'; return true;">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm" title="Force high-resolution OCR scan of all PDF pages"><i class="bi bi-cpu mr-1"></i> Re-Scan with OCR</button>
                </form>
                <form method="POST" action="{{ route('admin.bank-statements.verify-all-rows', $import->id) }}" onsubmit="return confirm('Verify all unverified rows for this statement? This marks them ready for reconciliation.')">
                    @csrf
                    <button type="submit" class="btn btn-outline-success btn-sm"><i class="bi bi-check-all mr-1"></i> Verify all rows</button>
                </form>
                <form method="POST" action="{{ route('admin.bank-statements.verify-import', $import->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm" @disabled($blocking > 0)><i class="bi bi-shield-check mr-1"></i> Verify statement &amp; unlock reconciliation</button>
                </form>
            </div>
        </div>
    @endif
</div>

<div class="hd-grid">
    <div class="hd-card" style="position:sticky;top:10px;">
        <h5>Original statement</h5>
        @if($import->storage_path)
            <iframe class="hd-pdf" src="{{ route('admin.bank-statements.statement-view', $import->id) }}#page=1"></iframe>
            <div class="mt-2"><a href="{{ route('admin.bank-statements.statement', $import->id) }}" class="hd-small">Download original PDF</a></div>
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
                                    <form method="POST" action="{{ route('admin.bank-statements.resolve-exception', $exception->id) }}">
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
                <thead>
                    <tr>
                        <th>Page</th>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>PDF Balance</th>
                        <th>System Balance</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($entries as $entry)
                    @php
                        $hasMismatch = ($entry->balance_after !== null && $entry->system_balance !== null && abs((float)$entry->balance_after - (float)$entry->system_balance) >= 0.01);
                        $hasMissingAmount = ($entry->amount === null || ($entry->amount == 0.0 && $entry->balance_after !== null));
                        $hasIssue = $hasMismatch || $hasMissingAmount;
                        $isVerified = !empty($entry->verified_at);
                    @endphp
                    <tr style="{{ $hasIssue ? 'background-color: #fff1f2; border-left: 4px solid #ef4444;' : 'background-color: #ffffff;' }}">
                        <td>{{ $entry->source_page ?? '—' }}@if($entry->source_position)/{{ $entry->source_position }}@endif</td>
                        <td style="white-space:nowrap;">{{ \Illuminate\Support\Carbon::parse($entry->transaction_date)->format('d/m/Y') }}</td>
                        <td>
                            <div>{{ $entry->description }}</div>
                            @if($hasMismatch)
                                <div class="hd-small text-danger font-weight-bold">
                                    <i class="bi bi-exclamation-triangle"></i> Balance gap: £{{ number_format(abs((float)$entry->balance_after - (float)$entry->system_balance), 2) }}
                                </div>
                            @elseif($hasMissingAmount)
                                <div class="hd-small text-danger font-weight-bold">
                                    <i class="bi bi-exclamation-circle"></i> Amount missing
                                </div>
                            @endif
                        </td>
                        <td style="white-space:nowrap; font-weight:600; color:{{ (float) $entry->amount < 0 ? '#b91c1c' : '#15803d' }}">
                            {{ number_format((float) $entry->amount, 2) }}
                        </td>
                        <td style="white-space:nowrap;">
                            {{ $entry->balance_after !== null ? number_format((float) $entry->balance_after, 2) : '—' }}
                        </td>
                        <td style="white-space:nowrap;">
                            @if($entry->system_balance !== null)
                                <span class="font-weight-bold" style="color:{{ $hasMismatch ? '#dc2626' : '#15803d' }};">
                                    {{ number_format((float) $entry->system_balance, 2) }}
                                </span>
                                @if($entry->balance_after !== null)
                                    @if($hasMismatch)
                                        <i class="bi bi-x-circle-fill text-danger ml-1" title="Mismatch with PDF balance"></i>
                                    @else
                                        <i class="bi bi-check-circle-fill text-success ml-1" title="100% Match"></i>
                                    @endif
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($hasMismatch)
                                <span class="hd-pill" style="background:#dc2626; color:#ffffff; font-weight:700;"><i class="bi bi-exclamation-triangle-fill mr-1"></i> Mismatch</span>
                            @elseif($hasMissingAmount)
                                <span class="hd-pill" style="background:#fee2e2; color:#b91c1c; border:1px solid #f87171; font-weight:700;"><i class="bi bi-exclamation-circle-fill mr-1"></i> Missing</span>
                            @elseif($isVerified)
                                <span class="hd-pill ok"><i class="bi bi-check2-all mr-1"></i> Verified</span>
                            @else
                                <span class="hd-pill" style="background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; font-weight:600;"><i class="bi bi-check mr-1"></i> Balanced</span>
                            @endif
                            </td>
                        <td style="white-space:nowrap;">
                            <div class="d-flex align-items-center gap-2">
                                <details>
                                    <summary class="text-primary hd-small font-weight-bold" style="cursor:pointer;">Edit</summary>
                                    <form method="POST" action="{{ route('admin.bank-statements.update-entry', $entry->id) }}" class="mt-1 p-2 border rounded bg-white shadow-sm" style="position:relative; z-index:10; min-width:220px; max-width:100%;">
                                        @csrf
                                        <label class="hd-small font-weight-bold mb-0">Date</label>
                                        <input type="date" name="transaction_date" required value="{{ \Illuminate\Support\Carbon::parse($entry->transaction_date)->format('Y-m-d') }}" class="form-control form-control-sm mb-1">
                                        <label class="hd-small font-weight-bold mb-0">Description</label>
                                        <input type="text" name="description" required maxlength="500" value="{{ $entry->description }}" class="form-control form-control-sm mb-1">
                                        <label class="hd-small font-weight-bold mb-0">Amount</label>
                                        <input type="number" step="0.01" name="amount" required value="{{ number_format((float) $entry->amount, 2, '.', '') }}" class="form-control form-control-sm mb-1">
                                        <label class="hd-small font-weight-bold mb-0">Reason</label>
                                        <input type="text" name="reason" required maxlength="1000" placeholder="Reason for correction" class="form-control form-control-sm mb-1">
                                        <button class="btn btn-primary btn-sm btn-block">Save</button>
                                    </form>
                                </details>
                                @unless($entry->verified_at)
                                    <form method="POST" action="{{ route('admin.bank-statements.verify-entry', $entry->id) }}">
                                        @csrf
                                        <button class="btn btn-outline-success btn-sm py-0 px-2" style="font-size:11px;" title="Mark row verified">Verify</button>
                                    </form>
                                @endunless
                                <details>
                                    <summary class="text-danger hd-small" style="cursor:pointer;">Delete</summary>
                                    <form method="POST" action="{{ route('admin.bank-statements.destroy-entry', $entry->id) }}" class="mt-1 p-2 border rounded bg-white shadow-sm" style="position:relative; z-index:10; min-width:200px; max-width:100%;" onsubmit="return confirm('Remove this row from the statement?')">
                                        @csrf
                                        <input type="text" name="reason" required maxlength="1000" placeholder="Why remove?" class="form-control form-control-sm mb-1">
                                        <button class="btn btn-outline-danger btn-sm btn-block">Confirm Delete</button>
                                    </form>
                                </details>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="hd-small text-center py-3">No rows extracted. Add a row manually if the PDF is readable.</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>

            <hr>
            <h6>Add a missing row</h6>
            <form method="POST" action="{{ route('admin.bank-statements.store-entry', $import->id) }}" class="hd-row-form">
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
