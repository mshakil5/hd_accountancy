@extends('admin.layouts.admin')
@section('content')
<div class="container-fluid py-4">
<style>
.hd-card{background:#fff;border:1px solid #e5eaf1;border-radius:12px;padding:18px;margin-bottom:16px}
.hd-small{color:#64748b;font-size:12px}
.hd-scroll{overflow-x:auto;overflow-y:visible;}
.hd-table{width:100%;font-size:13px;table-layout:auto}
.hd-table th,.hd-table td{border-bottom:1px solid #e7ebf1;padding:9px;vertical-align:middle}
.hd-table th{background:#f1f5f9;font-weight:600}
.hd-grid{display:grid;grid-template-columns:minmax(320px,1fr) minmax(420px,1.25fr);gap:16px;align-items:start}
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
                    <thead><tr><th>Severity</th><th>Issue</th><th>Page</th><th>Detail</th></tr></thead>
                    <tbody>
                    @foreach($exceptions as $exception)
                        <tr>
                            <td><span class="hd-pill {{ $exception->severity === 'blocking' ? 'blocking' : 'warning' }}">{{ ucfirst($exception->severity) }}</span></td>
                            <td><strong>{{ str_replace('_', ' ', $exception->code) }}</strong>
                                <div class="hd-small">{{ $exception->message }}</div>
                            </td>
                            <td>{{ $exception->page_no ?? '—' }}</td>
                            <td class="hd-small" style="max-width:280px;white-space:pre-wrap;">{{ \Illuminate\Support\Str::limit((string) $exception->source_value, 220) }}</td>
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
                        <th style="width:50px; text-align:center;">Page</th>
                        <th style="width:85px;">Date</th>
                        <th>Description</th>
                        <th style="width:90px; text-align:right;">Amount</th>
                        <th style="width:95px; text-align:right;">PDF Bal</th>
                        <th style="width:95px; text-align:right;">System Bal</th>
                        <th style="width:90px; text-align:center;">Status</th>
                        <th style="width:130px; text-align:center;">Actions</th>
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
                        <td style="text-align:center; font-size:11px; color:#64748b; white-space:nowrap;">
                            {{ $entry->source_page ?? '—' }}@if($entry->source_position)/{{ $entry->source_position }}@endif
                        </td>
                        <td style="white-space:nowrap; font-size:12px;">
                            {{ \Illuminate\Support\Carbon::parse($entry->transaction_date)->format('d/m/Y') }}
                        </td>
                        <td style="max-width:240px; word-break:break-word;">
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
                        <td style="white-space:nowrap; text-align:right; font-weight:600; color:{{ (float) $entry->amount < 0 ? '#b91c1c' : '#15803d' }}">
                            {{ number_format((float) $entry->amount, 2) }}
                        </td>
                        <td style="white-space:nowrap; text-align:right; color:#475569;">
                            {{ $entry->balance_after !== null ? number_format((float) $entry->balance_after, 2) : '—' }}
                        </td>
                        <td style="white-space:nowrap; text-align:right;">
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
                        <td style="text-align:center; white-space:nowrap;">
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
                        <td style="white-space:nowrap; text-align:center;">
                            <div class="d-inline-flex align-items-center gap-1">
                                <button type="button" 
                                        class="btn btn-sm btn-outline-primary py-0 px-2 btn-edit-row" 
                                        style="font-size:11px;"
                                        data-id="{{ $entry->id }}"
                                        data-date="{{ \Illuminate\Support\Carbon::parse($entry->transaction_date)->format('Y-m-d') }}"
                                        data-description="{{ $entry->description }}"
                                        data-amount="{{ number_format((float) $entry->amount, 2, '.', '') }}"
                                        title="Edit this row">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                @unless($entry->verified_at)
                                    <form method="POST" action="{{ route('admin.bank-statements.verify-entry', $entry->id) }}" class="d-inline m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-success btn-sm py-0 px-2" style="font-size:11px;" title="Mark row verified">
                                            <i class="bi bi-check2"></i>
                                        </button>
                                    </form>
                                @endunless
                                <button type="button" 
                                        class="btn btn-sm btn-outline-danger py-0 px-2 btn-delete-row" 
                                        style="font-size:11px;"
                                        data-id="{{ $entry->id }}"
                                        data-date="{{ \Illuminate\Support\Carbon::parse($entry->transaction_date)->format('d/m/Y') }}"
                                        data-description="{{ $entry->description }}"
                                        data-amount="{{ number_format((float) $entry->amount, 2, '.', '') }}"
                                        title="Delete this row">
                                    <i class="bi bi-trash"></i>
                                </button>
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

{{-- Edit Row Modal --}}
<div class="modal fade" id="editRowModal" tabindex="-1" role="dialog" aria-labelledby="editRowModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="POST" id="editRowForm" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="editRowModalLabel"><i class="bi bi-pencil-square mr-1"></i> Edit Statement Row</h5>
                    <button type="button" class="btn-close close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="form-label font-weight-bold">Transaction Date</label>
                        <input type="date" name="transaction_date" id="modalEditDate" required class="form-control">
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label font-weight-bold">Description</label>
                        <input type="text" name="description" id="modalEditDescription" required maxlength="500" class="form-control">
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label font-weight-bold">Amount (£)</label>
                        <input type="number" step="0.01" name="amount" id="modalEditAmount" required class="form-control" placeholder="-50.00 or 50.00">
                        <small class="form-text text-muted">Use negative (-) for money out / expense, positive for money in.</small>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label font-weight-bold">Reason for correction <span class="text-danger">*</span></label>
                        <input type="text" name="reason" id="modalEditReason" required maxlength="1000" placeholder="e.g. Corrected typo in amount / description" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg mr-1"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete Row Modal --}}
<div class="modal fade" id="deleteRowModal" tabindex="-1" role="dialog" aria-labelledby="deleteRowModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="POST" id="deleteRowForm" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="deleteRowModalLabel"><i class="bi bi-trash mr-1"></i> Remove Statement Row</h5>
                    <button type="button" class="btn-close close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Are you sure you want to remove this transaction row from the statement?</p>
                    <div class="p-2 mb-3 bg-light rounded hd-small" id="deleteRowSummary"></div>
                    <div class="form-group mb-3">
                        <label class="form-label font-weight-bold">Reason for removal <span class="text-danger">*</span></label>
                        <input type="text" name="reason" id="modalDeleteReason" required maxlength="1000" placeholder="e.g. Duplicate row, header line, or non-transaction" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash mr-1"></i> Remove Row</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
$(document).ready(function() {
    $(document).on('click', '.btn-edit-row', function() {
        var id = $(this).data('id');
        var date = $(this).data('date');
        var desc = $(this).data('description');
        var amount = $(this).data('amount');
        var url = '{{ route("admin.bank-statements.update-entry", ":id") }}'.replace(':id', id);

        $('#editRowForm').attr('action', url);
        $('#modalEditDate').val(date);
        $('#modalEditDescription').val(desc);
        $('#modalEditAmount').val(amount);
        $('#modalEditReason').val('');
        
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            new bootstrap.Modal(document.getElementById('editRowModal')).show();
        } else {
            $('#editRowModal').modal('show');
        }
    });

    $(document).on('click', '.btn-delete-row', function() {
        var id = $(this).data('id');
        var desc = $(this).data('description');
        var amount = $(this).data('amount');
        var date = $(this).data('date');
        var url = '{{ route("admin.bank-statements.destroy-entry", ":id") }}'.replace(':id', id);

        $('#deleteRowForm').attr('action', url);
        $('#deleteRowSummary').html('<strong>' + $('<div>').text(desc).html() + '</strong><br>Date: ' + date + ' | Amount: £' + amount);
        $('#modalDeleteReason').val('');
        
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            new bootstrap.Modal(document.getElementById('deleteRowModal')).show();
        } else {
            $('#deleteRowModal').modal('show');
        }
    });
});
</script>
@endsection
