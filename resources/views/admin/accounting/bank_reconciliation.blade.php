@extends('admin.layouts.admin')
@php $presetId = $credentialId ?? request('client_credential_id') ?? request('credential_id'); @endphp
@section('content')
<div class="container-fluid py-4">
<style>
/* ── Base ── */
.hd-card{background:#fff;border:1px solid #e5eaf1;border-radius:12px;padding:18px;margin-bottom:15px}
.hd-small{color:#64748b;font-size:12px}
.hd-scroll{overflow-x:auto}
/* ── Stats bar ── */
.hd-stat-bar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px}
.hd-stat{background:#fff;border:1px solid #e5eaf1;border-radius:10px;padding:12px 20px;min-width:130px;text-align:center}
.hd-stat__label{font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.hd-stat__val{font-size:26px;font-weight:800;line-height:1.1;margin-top:2px}
.hd-filter-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px}
.hd-ftab{border:1px solid #dbe4ee;background:#f8fafc;border-radius:20px;padding:5px 16px;font-size:13px;cursor:pointer;text-decoration:none;color:#334155;font-weight:600}
.hd-ftab.active{background:#0284c7;border-color:#0284c7;color:#fff}
/* ── Reconcile row ── */
.hd-reconcile-row{display:grid;grid-template-columns:minmax(210px,38%) minmax(0,62%);border:1px solid #dae4ef;border-radius:10px;margin:14px 0;overflow:hidden;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.hd-bank-side{background:#f4f8fb;padding:18px;border-right:1px solid #dae4ef}
.hd-action-side{padding:14px 18px;min-width:0}
/* ── Bank side ── */
.hd-entry-date{font-size:12px;color:#64748b;font-weight:600}
.hd-entry-desc{font-size:15px;font-weight:700;margin:6px 0 4px;overflow-wrap:anywhere;line-height:1.3}
.hd-entry-bank{font-size:12px;color:#64748b;margin-bottom:10px}
.hd-credit-label{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;margin-bottom:4px}
.hd-credit{background:#dcfce7;color:#15803d}.hd-debit{background:#fee2e2;color:#b91c1c}
.hd-amount-big{font-size:22px;font-weight:800;display:block;margin-bottom:8px}
.hd-pill{padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;background:#e2e8f0;color:#334155;display:inline-block}
.hd-details-toggle{font-size:12px;color:#0369a1;cursor:pointer;background:none;border:none;padding:0;margin-top:8px;display:flex;align-items:center;gap:3px}
.hd-details-panel{font-size:12px;color:#334155;background:#eef4fb;border-radius:6px;padding:10px;margin-top:8px;display:none}
.hd-details-panel.open{display:block}
.hd-details-row{display:flex;justify-content:space-between;margin-bottom:3px;gap:8px}
.hd-details-row span:first-child{color:#64748b;white-space:nowrap}
/* ── Tabs ── */
.hd-row-tabs{display:flex;flex-wrap:wrap;gap:2px;border-bottom:1px solid #e2e8f0;margin-bottom:14px}
.hd-tab{border:0;background:transparent;color:#64748b;padding:8px 11px;cursor:pointer;font-size:12.5px;font-weight:600}
.hd-tab.active{color:#0369a1;border-bottom:3px solid #0284c7}
.hd-tab-panel[hidden]{display:none!important}
/* ── Forms ── */
.hd-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;align-items:end}
@media(max-width:640px){.hd-form-grid{grid-template-columns:1fr}}
.hd-field{display:flex;flex-direction:column;gap:3px}
.hd-field label,.hd-field-lbl{font-size:11.5px;font-weight:700;color:#334155;margin-bottom:2px}
.hd-fc{width:100%}
.select2-container .select2-selection--single{height:38px!important;border:1px solid #ced4da!important;border-radius:.25rem!important}
.select2-container--default .select2-selection--single .select2-selection__rendered{line-height:36px!important}
.select2-container--default .select2-selection--single .select2-selection__arrow{height:36px!important}
/* ── Match list ── */
.hd-match-item{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:8px 10px;border:1px solid #dae4ef;border-radius:8px;margin-bottom:6px;background:#f8fafc;cursor:pointer}
.hd-match-item.selected{border-color:#0284c7;background:#eff8ff}
.hd-match-item input[type=radio]{accent-color:#0284c7}
.hd-match-meta{font-size:12px;color:#64748b}
.hd-match-amount{font-size:14px;font-weight:700;white-space:nowrap}
/* ── Split ── */
.hd-split-line{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;margin-bottom:8px}
.hd-split-meter{font-size:12px;font-weight:600;padding:4px 10px;border-radius:20px;background:#f1f5f9}
/* ── Find & match ── */
.hd-find-item{display:flex;align-items:center;gap:10px;padding:7px 10px;border:1px solid #e2e8f0;border-radius:7px;margin-bottom:5px;font-size:13px;background:#fff;cursor:pointer}
.hd-find-item:hover{background:#f1f9ff}
.hd-find-item.capped{opacity:.45;pointer-events:none}
.hd-find-counter{font-size:12px;font-weight:700;padding:5px 12px;border-radius:20px;background:#f1f5f9;margin-bottom:10px}
/* ── Reconciled banner ── */
.hd-reconciled-banner{background:linear-gradient(135deg,#dcfce7,#bbf7d0);border:1px solid #86efac;border-radius:8px;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px}
/* ── Notes ── */
.hd-note-pill{background:#f1f5f9;border-radius:6px;padding:7px 10px;font-size:12px;margin-bottom:6px;border-left:3px solid #cbd5e1}
/* ── Quick add btn ── */
.hd-qadd-btn{font-size:11px;border-radius:20px;padding:2px 10px;background:linear-gradient(45deg,#28a745,#20c997);border:none;color:#fff;cursor:pointer;margin-left:6px}
/* ── Responsive ── */
@media(max-width:860px){.hd-reconcile-row{grid-template-columns:1fr}.hd-bank-side{border-right:0;border-bottom:1px solid #dae4ef}}
/* ── Table ── */
.hd-table{width:100%;font-size:13px;border-collapse:collapse}
.hd-table th,.hd-table td{border-bottom:1px solid #e7ebf1;padding:9px 10px;vertical-align:top}
.hd-table th{background:#f1f5f9;font-size:12px;text-transform:uppercase;letter-spacing:.4px;color:#475569}
</style>

{{-- ── Page header ── --}}
<div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
  <div>
    <h3 class="mb-1">Bank Reconciliation</h3>
    <p class="hd-small mb-0">Match bank transactions to receipts. Each match posts immediately to the ledger.</p>
  </div>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(isset($errors) && $errors->any())
<div class="alert alert-danger">
  @foreach($errors->all() as $error)
  <div>{{ $error }}</div>
  @endforeach
</div>
@endif

{{-- ── Client picker ── --}}
<div class="hd-card mb-3">
  <div class="row align-items-center">
    <div class="col-md-4">
      <label class="font-weight-bold">Select Client</label>
      <select class="form-control select2" id="filterClient" style="width:100%">
        <option value="">-- Choose a client --</option>
      </select>
    </div>
    @if($presetId)
    <div class="col-md-8">
      <div class="d-flex align-items-center" style="margin-top:24px;gap:18px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:12px 20px;border-radius:12px;color:#fff;">
        <span class="font-weight-bold" style="font-size:18px;" id="ciName"></span>
        <span style="opacity:.9;font-size:14px;"><i class="bi bi-envelope mr-1"></i><span id="ciEmail"></span></span>
        <span style="opacity:.9;font-size:14px;"><i class="bi bi-telephone mr-1"></i><span id="ciPhone"></span></span>
        <a href="{{ route('admin.bank-reconciliation.index') }}" class="btn btn-sm btn-light ml-2" style="color:#667eea;font-weight:600;">Change</a>
      </div>
    </div>
    @endif
  </div>
</div>

@if(!$presetId)
<div class="hd-card"><div class="alert alert-info mb-0"><strong>Select a client to begin.</strong></div></div>
@else

{{-- ── Filter form ── --}}
<div class="hd-card">
  <form method="GET" action="{{ route('admin.bank-reconciliation.index') }}" class="row align-items-end g-2">
    <input type="hidden" name="client_credential_id" value="{{ $presetId }}">
    <div class="col-md-3">
      <label class="hd-small font-weight-bold">Business</label>
      <select class="form-control select2" name="client_id" id="filterBusiness" style="width:100%">
        <option value="">All businesses</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="hd-small font-weight-bold">Bank Account</label>
      <select class="form-control select2" name="bank_account_id" style="width:100%">
        <option value="">All bank accounts</option>
        @foreach($bankAccounts as $bank)
          @continue(strtolower(trim($bank->bank_name)) === 'unknown historical bank')
          <option value="{{ $bank->id }}" {{ (int)$bankAccountId===(int)$bank->id?'selected':'' }}>{{ $bank->bank_name }}{{ $bank->account_last_four?' ••'.$bank->account_last_four:'' }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-4 d-flex align-items-end gap-2" style="padding-top:18px">
      <button class="btn btn-primary">Filter</button>
      @if(request()->hasAny(['client_id','bank_account_id','reconcile_status']) && request('reconcile_status') !== 'unreconciled')
        <a href="{{ route('admin.bank-reconciliation.index', ['client_credential_id'=>$presetId]) }}" class="btn btn-outline-secondary btn-sm">Reset</a>
      @endif
    </div>
  </form>
</div>

<input type="hidden" id="client_credential_id" value="{{ $credentialId ?? $presetId }}">

{{-- ── Stats bar ── --}}
<div class="hd-stat-bar">
  <div class="hd-stat">
    <div class="hd-stat__label">Unreconciled</div>
    <div class="hd-stat__val" style="color:#b91c1c">{{ $unreconciledCount }}</div>
  </div>
  <div class="hd-stat">
    <div class="hd-stat__label">Reconciled</div>
    <div class="hd-stat__val" style="color:#15803d">{{ $reconciledCount }}</div>
  </div>
  <div class="hd-stat">
    <div class="hd-stat__label">Total</div>
    <div class="hd-stat__val" style="color:#0369a1">{{ $totalCount }}</div>
  </div>
</div>

{{-- ── Reconcile-status filter tabs ── --}}
<div class="hd-filter-tabs">
  @foreach(['unreconciled'=>'Unreconciled',''=>'All','reconciled'=>'Reconciled'] as $val=>$lbl)
  <a href="{{ request()->fullUrlWithQuery(['reconcile_status'=>$val]) }}"
     class="hd-ftab {{ ($reconcileFilter ?? 'unreconciled') === $val ? 'active' : '' }}">{{ $lbl }}</a>
  @endforeach
</div>

{{-- ── Workspace ── --}}
<div class="hd-card hd-workspace" id="hd-reconciliation-workspace">
  <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
    <h5 class="mb-0">Bank Transactions <span class="badge badge-secondary" style="font-size:13px">{{ $entries->total() }}</span></h5>
  </div>

  @forelse($entries as $entry)
  @php
    $absAmt     = abs((float)$entry->amount);
    $isCredit   = (float)$entry->amount >= 0;
    $approved   = ($entry->reconciliation_status ?? '') === 'approved';
    $dateFormatted = \Carbon\Carbon::parse($entry->transaction_date)->format('d-m-Y');
    $bankName   = optional($bankAccounts->firstWhere('id', $entry->bank_account_id))->bank_name ?? 'Unassigned';
    $importInfo = $importMap[$entry->bank_import_id] ?? null;

    // Exact-amount matches from the eligible receipts collection
    $exact = $fuzzyMatches[(int)$entry->id] ?? collect();
    // Auto-select if only one match
    $autoMatch = $exact->count() === 1 ? $exact->first() : null;
  @endphp

  <div class="hd-reconcile-row" id="bank-entry-{{ $entry->id }}">
    {{-- ── LEFT: bank transaction details ── --}}
    <div class="hd-bank-side">
      <div class="hd-entry-date">{{ $dateFormatted }}</div>
      <div class="hd-entry-desc">{{ $entry->description }}</div>
      <div class="hd-entry-bank"><i class="bi bi-bank mr-1"></i>{{ $bankName }}</div>

      <span class="hd-credit-label {{ $isCredit ? 'hd-credit' : 'hd-debit' }}">
        @if($isCredit)
          <i class="fa fa-arrow-down mr-1"></i>Credit
        @else
          <i class="fa fa-arrow-up mr-1"></i>Debit
        @endif
      </span>
      <span class="hd-amount-big" style="color:{{ $isCredit ? '#15803d' : '#b91c1c' }}">
        {{ $isCredit ? '+' : '-' }}£{{ number_format($absAmt, 2) }}
      </span>

      @if($approved)
        <span class="hd-pill" style="background:#dcfce7;color:#15803d">✓ Reconciled</span>
      @else
        <span class="hd-pill" style="background:#fee2e2;color:#b91c1c">Unreconciled</span>
      @endif

      {{-- View Details toggle --}}
      <button type="button" class="hd-details-toggle" onclick="hdToggleDetails(this)">
        <i class="fa fa-chevron-right fa-xs"></i> View Details
      </button>
      <div class="hd-details-panel">
        @if($entry->reference)
        <div class="hd-details-row"><span>Reference</span><span>{{ $entry->reference }}</span></div>
        @endif
        <div class="hd-details-row"><span>Transaction ID</span><span>#{{ $entry->id }}</span></div>
        @if($entry->balance_after !== null)
        <div class="hd-details-row"><span>PDF Balance</span><span>£{{ number_format((float)$entry->balance_after, 2) }}</span></div>
        @endif
        @if($entry->system_balance !== null)
        <div class="hd-details-row"><span>System Balance</span><span>£{{ number_format((float)$entry->system_balance, 2) }}</span></div>
        @endif
        @if($importInfo)
        <div class="hd-details-row"><span>Statement</span><span>{{ $importInfo->original_filename ?? '#'.$importInfo->id }}</span></div>
        @endif
        @if($entry->source_page || $entry->source_position)
        <div class="hd-details-row"><span>Page / Row</span><span>{{ $entry->source_page }}/{{ $entry->source_position }}</span></div>
        @endif
        @if($entry->extraction_source)
        <div class="hd-details-row"><span>Extraction</span><span>{{ ucfirst($entry->extraction_source) }}</span></div>
        @endif
        @if($entry->approved_at)
        <div class="hd-details-row"><span>Reconciled at</span><span>{{ \Carbon\Carbon::parse($entry->approved_at)->format('d-m-Y H:i') }}</span></div>
        @endif
      </div>
    </div>

    {{-- ── RIGHT: action area ── --}}
    <div class="hd-action-side">
      @if($approved)
        {{-- Reconciled state --}}
        <div class="hd-reconciled-banner mb-3">
          <div>
            <strong style="color:#15803d">✓ Reconciled</strong>
            @if($entry->approved_at)
              <span class="hd-small ml-2">on {{ \Carbon\Carbon::parse($entry->approved_at)->format('d-m-Y') }}</span>
            @endif
            @if($entry->receipt_id)
              <span class="hd-small ml-2">· Receipt #{{ $entry->receipt_id }}</span>
            @endif
            @if($entry->journal_posted_at)
              <span class="hd-pill ml-2" style="background:#dbeafe;color:#1d4ed8">Posted to ledger</span>
            @endif
          </div>
          <form method="POST" action="{{ route('admin.bank-reconciliation.action', $entry->id) }}" class="hd-action-form">
            @csrf
            <input type="hidden" name="client_id" value="{{ $entry->client_id }}">
            <input type="hidden" name="action" value="unmatch">
            <button class="btn btn-outline-danger btn-sm" onclick="return confirm('Undo this reconciliation?')">Undo</button>
          </form>
        </div>

        {{-- Show split allocations if any --}}
        @php $entrySplits = $splits[$entry->id] ?? collect(); @endphp
        @if($entrySplits->isNotEmpty())
        <div class="hd-small font-weight-bold mb-1">Split allocations ({{ $entrySplits->count() }}):</div>
        <div class="hd-scroll mb-2">
          <table class="hd-table" style="background:#f8fafc;border-radius:6px">
            <thead><tr><th>Amount</th><th>Receipt</th><th>Memo</th></tr></thead>
            <tbody>
              @foreach($entrySplits as $sp)
              <tr>
                <td><strong>£{{ number_format((float)$sp->amount, 2) }}</strong></td>
                <td>{{ $sp->receipt_id ? '#'.$sp->receipt_id : '—' }}</td>
                <td>{{ $sp->memo ?: '—' }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @endif

      @else
        {{-- ── TABS ── --}}
        <div class="hd-row-tabs" role="tablist">
          <button type="button" class="hd-tab active" data-hd-tab="match">Match</button>
          <button type="button" class="hd-tab" data-hd-tab="create">Create</button>
          <button type="button" class="hd-tab" data-hd-tab="split">Split</button>
          <button type="button" class="hd-tab" data-hd-tab="find">Find &amp; Match</button>
          <button type="button" class="hd-tab" data-hd-tab="transfer">Transfer</button>
          <button type="button" class="hd-tab" data-hd-tab="discuss">Discuss</button>
        </div>

        {{-- ── TAB: Match ── --}}
        <section class="hd-tab-panel" data-hd-panel="match">
          @if($exact->isEmpty())
            <div class="alert alert-light py-2 mb-2" style="border:1px solid #e2e8f0;font-size:13px">
              <i class="fa fa-info-circle mr-1 text-muted"></i> No receipts with an exact matching amount (£{{ number_format($absAmt,2) }}) found. Use <strong>Find &amp; Match</strong> to search manually, or <strong>Create</strong> a new receipt.
            </div>
          @else
          <p class="hd-small mb-2">
            Receipts with exactly matching amount.
            @if($autoMatch)
              <strong>Auto-selected</strong> (only 1 match).
            @endif
          </p>
          <form method="POST" action="{{ route('admin.bank-reconciliation.action', $entry->id) }}" class="hd-action-form" id="match-form-{{ $entry->id }}">
            @csrf
            <input type="hidden" name="client_id" value="{{ $entry->client_id }}">
            <input type="hidden" name="action" value="match">

            @if($autoMatch)
              <input type="hidden" name="receipt_id" value="{{ $autoMatch->id }}">
              <div class="hd-match-item selected mb-2">
                <div>
                  <div style="font-size:13px;font-weight:700">{{ $autoMatch->supplier ?: 'Unknown Supplier' }}</div>
                  <div class="hd-match-meta">
                    #{{ $autoMatch->receipt_number }}
                    @if($autoMatch->detail && $autoMatch->detail->accountHead)
                      · {{ $autoMatch->detail->accountHead->code }} – {{ $autoMatch->detail->accountHead->name }}
                    @endif
                    @if($autoMatch->detail && $autoMatch->detail->invoice_date)
                      · {{ \Carbon\Carbon::parse($autoMatch->detail->invoice_date)->format('d-m-Y') }}
                    @endif
                  </div>
                </div>
                <div class="hd-match-amount" style="color:#15803d">£{{ number_format((float)$autoMatch->detail->total_amount, 2) }}</div>
              </div>
            @else
              @foreach($exact->take(10) as $r)
              <label class="hd-match-item" style="cursor:pointer;display:flex">
                <input type="radio" name="receipt_id" value="{{ $r->id }}" style="margin-right:8px;margin-top:2px" {{ $loop->first ? 'checked' : '' }}>
                <div style="flex:1;min-width:0">
                  <div style="font-size:13px;font-weight:700">{{ $r->supplier ?: 'Unknown Supplier' }}</div>
                  <div class="hd-match-meta">
                    #{{ $r->receipt_number }}
                    @if($r->detail && $r->detail->accountHead)
                      · {{ $r->detail->accountHead->code }} – {{ $r->detail->accountHead->name }}
                    @endif
                    @if($r->detail && $r->detail->invoice_date)
                      · {{ \Carbon\Carbon::parse($r->detail->invoice_date)->format('d-m-Y') }}
                    @endif
                  </div>
                </div>
                <div class="hd-match-amount" style="color:#15803d">£{{ number_format((float)$r->detail->total_amount, 2) }}</div>
              </label>
              @endforeach
            @endif

            <button class="btn btn-primary btn-sm mt-2" onclick="return confirm('Match this receipt to the bank transaction?')">
              <i class="fa fa-link mr-1"></i> Match &amp; Reconcile
            </button>
          </form>
          @endif
        </section>

        {{-- ── TAB: Create ── --}}
        <section class="hd-tab-panel" data-hd-panel="create" hidden>
          <p class="hd-small mb-2">Create a new receipt from this bank transaction and reconcile immediately.</p>
          <form method="POST" action="{{ route('admin.bank-reconciliation.action', $entry->id) }}" class="hd-action-form">
            @csrf
            <input type="hidden" name="client_id" value="{{ $entry->client_id }}">
            <input type="hidden" name="action" value="create">
            <div class="hd-form-grid">
              <div class="hd-field">
                <label class="hd-field-lbl">Supplier</label>
                <input class="form-control hd-fc" name="supplier" value="{{ $entry->description }}" placeholder="Supplier name" required>
              </div>
              <div class="hd-field">
                <label class="hd-field-lbl">Invoice Date</label>
                <input class="form-control hd-fc" type="date" name="invoice_date" value="{{ $entry->transaction_date }}" required>
              </div>
              <div class="hd-field" style="grid-column:1/-1">
                <label class="hd-field-lbl">
                  Account Head
                  <button type="button" class="hd-qadd-btn" onclick="openQuickHeadFor(this)"><i class="fa fa-plus-circle"></i> Quick Add</button>
                </label>
                <select class="form-control select2 hd-fc" name="account_head_id" required>
                  <option value="">Choose account head</option>
                  @foreach($heads as $h)
                  <option value="{{ $h->id }}">{{ $h->code }} — {{ $h->name }}</option>
                  @endforeach
                </select>
              </div>
              <div style="grid-column:1/-1">
                <button class="btn btn-success btn-sm" onclick="return confirm('Create receipt and reconcile for £{{ number_format($absAmt,2) }}?')">
                  <i class="fa fa-plus mr-1"></i> Create &amp; Reconcile
                </button>
              </div>
            </div>
          </form>
        </section>

        {{-- ── TAB: Split ── --}}
        <section class="hd-tab-panel" data-hd-panel="split" hidden>
          <p class="hd-small mb-2">Split this £{{ number_format($absAmt, 2) }} transaction across multiple receipts. All lines must total exactly £{{ number_format($absAmt, 2) }}.</p>
          <form method="POST" action="{{ route('admin.bank-reconciliation.action', $entry->id) }}"
                class="hd-split-form hd-action-form"
                data-split-total="{{ number_format($absAmt, 2, '.', '') }}">
            @csrf
            <input type="hidden" name="client_id" value="{{ $entry->client_id }}">
            <input type="hidden" name="action" value="split">
            <div class="hd-split-lines">
              @foreach([0,1] as $i)
              <div class="hd-split-line hd-form-grid">
                <div class="hd-field">
                  <label class="hd-field-lbl">Amount (£)</label>
                  <input class="form-control hd-fc hd-split-amount" type="number" name="splits[{{ $i }}][amount]" min="0.01" step="0.01" required>
                </div>
                <div class="hd-field">
                  <label class="hd-field-lbl">Supplier</label>
                  <input class="form-control hd-fc" name="splits[{{ $i }}][supplier]" placeholder="Supplier name">
                </div>
                <div class="hd-field">
                  <label class="hd-field-lbl">Invoice Date</label>
                  <input class="form-control hd-fc" type="date" name="splits[{{ $i }}][invoice_date]" value="{{ $entry->transaction_date }}">
                </div>
                <div class="hd-field">
                  <label class="hd-field-lbl">
                    Account Head
                    <button type="button" class="hd-qadd-btn" onclick="openQuickHeadFor(this)"><i class="fa fa-plus-circle"></i> Quick Add</button>
                  </label>
                  <select class="form-control select2 hd-fc" name="splits[{{ $i }}][account_head_id]" required>
                    <option value="">Choose head</option>
                    @foreach($heads as $h)
                    <option value="{{ $h->id }}">{{ $h->code }} — {{ $h->name }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="hd-field" style="grid-column:1/-1">
                  <label class="hd-field-lbl">Memo (optional)</label>
                  <input class="form-control hd-fc" name="splits[{{ $i }}][memo]" placeholder="Note about this line">
                </div>
              </div>
              @endforeach
            </div>
            <div class="d-flex gap-2 align-items-center flex-wrap mt-2 mb-2">
              <button type="button" class="btn btn-outline-secondary btn-sm" onclick="hdAddSplitLine(this)"><i class="fa fa-plus"></i> Add line</button>
              <button type="button" class="btn btn-outline-secondary btn-sm" onclick="hdRemoveSplitLine(this)" disabled><i class="fa fa-minus"></i> Remove line</button>
              <span class="hd-split-meter" role="status">Allocated £0.00 of £{{ number_format($absAmt, 2) }}</span>
            </div>
            <button class="btn btn-primary btn-sm" onclick="return hdConfirmSplit(this.closest('form'))">
              <i class="fa fa-scissors mr-1"></i> Save Split &amp; Reconcile
            </button>
          </form>
        </section>

        {{-- ── TAB: Find & Match ── --}}
        <section class="hd-tab-panel" data-hd-panel="find" hidden>
          <p class="hd-small mb-2">Search and select receipts to match. Selected total cannot exceed £{{ number_format($absAmt, 2) }}.</p>
          <div class="hd-find-counter" id="find-counter-{{ $entry->id }}">
            Selected: £0.00 / Target: £{{ number_format($absAmt, 2) }} · Remaining: £{{ number_format($absAmt, 2) }}
          </div>
          <input type="search" class="form-control mb-2 hd-find-search" placeholder="Search supplier or receipt #" aria-label="Search">
          <form method="POST" action="{{ route('admin.bank-reconciliation.action', $entry->id) }}" class="hd-action-form" id="find-form-{{ $entry->id }}">
            @csrf
            <input type="hidden" name="client_id" value="{{ $entry->client_id }}">
            <input type="hidden" name="action" value="find_match">
            <div class="hd-find-list" data-target="{{ number_format($absAmt, 2, '.', '') }}">
              @forelse($receipts as $r)
              @php $rAmt = (float)($r->detail->total_amount ?? 0); @endphp
              <label class="hd-find-item"
                     data-search="{{ strtolower(($r->receipt_number ?? '').' '.($r->supplier ?? '')) }}"
                     data-amount="{{ number_format($rAmt, 2, '.', '') }}">
                <input type="checkbox" name="receipt_ids[]" value="{{ $r->id }}"
                       class="hd-find-cb"
                       data-amount="{{ $rAmt }}"
                       onchange="hdFindUpdate(this)">
                <div style="flex:1;min-width:0">
                  <span style="font-weight:700;font-size:13px">#{{ $r->receipt_number }} — {{ $r->supplier ?: 'Unknown' }}</span>
                  @if($r->detail && $r->detail->invoice_date)
                    <span class="hd-small ml-1">· {{ \Carbon\Carbon::parse($r->detail->invoice_date)->format('d-m-Y') }}</span>
                  @endif
                  @if($r->detail && $r->detail->accountHead)
                    <span class="hd-small ml-1">· {{ $r->detail->accountHead->code }}</span>
                  @endif
                </div>
                <span style="font-weight:700;color:#15803d;white-space:nowrap">£{{ number_format($rAmt, 2) }}</span>
              </label>
              @empty
              <div class="hd-small text-muted">No eligible receipts found.</div>
              @endforelse
            </div>
            <button class="btn btn-primary btn-sm mt-2 hd-find-submit" disabled onclick="return confirm('Match selected receipts to this bank transaction?')">
              <i class="fa fa-check mr-1"></i> Match Selected
            </button>
          </form>
        </section>

        {{-- ── TAB: Transfer ── --}}
        <section class="hd-tab-panel" data-hd-panel="transfer" hidden>
          <p class="hd-small mb-2">Mark this movement as an internal transfer to another bank account.</p>
          <form method="POST" action="{{ route('admin.bank-reconciliation.action', $entry->id) }}" class="hd-action-form">
            @csrf
            <input type="hidden" name="client_id" value="{{ $entry->client_id }}">
            <input type="hidden" name="action" value="transfer">
            <div class="hd-field mb-3">
              <label class="hd-field-lbl">Destination Bank Account</label>
              <select class="form-control select2 hd-fc" name="destination_bank_account_id" required>
                <option value="">Choose destination</option>
                @foreach($bankAccounts as $bank)
                  @continue((int)$bank->id === (int)$entry->bank_account_id)
                  @continue(strtolower(trim($bank->bank_name)) === 'unknown historical bank')
                  <option value="{{ $bank->id }}">{{ $bank->bank_name }}{{ $bank->account_last_four?' ••'.$bank->account_last_four:'' }}</option>
                @endforeach
              </select>
            </div>
            <button class="btn btn-primary btn-sm" onclick="return confirm('Record this as an internal bank transfer?')">
              <i class="fa fa-exchange mr-1"></i> Save Transfer
            </button>
          </form>
        </section>

        {{-- ── TAB: Discuss ── --}}
        <section class="hd-tab-panel" data-hd-panel="discuss" hidden>
          @php $entryNotes = $notes[$entry->id] ?? collect(); @endphp
          @if($entryNotes->isNotEmpty())
          <div class="mb-2">
            <div class="hd-small font-weight-bold mb-1">Internal Notes</div>
            @foreach($entryNotes as $n)
            <div class="hd-note-pill">{{ $n->body }} <span style="opacity:.6;font-size:11px">· {{ \Carbon\Carbon::parse($n->created_at)->format('d-m-Y H:i') }}</span></div>
            @endforeach
          </div>
          @endif
          <form method="POST" action="{{ route('admin.bank-reconciliation.action', $entry->id) }}" class="hd-action-form">
            @csrf
            <input type="hidden" name="client_id" value="{{ $entry->client_id }}">
            <input type="hidden" name="action" value="add_note">
            <div class="hd-field mb-2">
              <label class="hd-field-lbl">Add Internal Note</label>
              <textarea class="form-control hd-fc" name="review_note" rows="2" maxlength="2000" placeholder="Accountant note…" required></textarea>
            </div>
            <button class="btn btn-outline-primary btn-sm"><i class="fa fa-comment mr-1"></i> Save Note</button>
          </form>
        </section>
      @endif
    </div>
  </div>
  @empty
  <div class="alert alert-info"><strong>No bank transactions found.</strong> Import a statement for this client and business to begin reconciliation.</div>
  @endforelse

  <div class="mt-3">{{ $entries->links('vendor.pagination.bootstrap-5') }}</div>
</div>

@endif {{-- presetId --}}
</div>

{{-- Quick Add Account Head --}}
@include('admin.partials.quick_account_head', [
    'quickHeadClientId'   => $credentialId ?? $presetId,
    'quickHeadClientName' => '',
    'quickHeadTarget'     => '#account_head_id',
])

<script>
// ── Details toggle ──
function hdToggleDetails(btn){
  const panel = btn.parentElement.querySelector('.hd-details-panel');
  if(!panel) return;
  const open = panel.classList.toggle('open');
  btn.innerHTML = (open ? '<i class="fa fa-chevron-down fa-xs"></i>' : '<i class="fa fa-chevron-right fa-xs"></i>') + ' View Details';
}

// ── Tab switching ──
document.addEventListener('click', function(e){
  const tab = e.target.closest('[data-hd-tab]'); if(!tab) return;
  const row = tab.closest('.hd-action-side'); if(!row) return;
  row.querySelectorAll('[data-hd-tab]').forEach(el => {
    el.classList.toggle('active', el === tab);
    el.setAttribute('aria-selected', el === tab ? 'true' : 'false');
  });
  row.querySelectorAll('[data-hd-panel]').forEach(el => { el.hidden = el.dataset.hdPanel !== tab.dataset.hdTab; });
  // Re-init select2 in revealed panel
  if(window.jQuery){
    const panel = row.querySelector('[data-hd-panel="'+tab.dataset.hdTab+'"]');
    if(panel) panel.querySelectorAll('select.select2').forEach(function(s){
      const $s = jQuery(s);
      if($s.data('select2')) $s.select2('destroy');
      $s.select2({width:'100%', dropdownParent: jQuery(document.body)});
    });
  }
});

// ── Split: live totals + auto-fill last line ──
function hdSplitTotals(form, changedInput){
  const total = parseFloat(form.dataset.splitTotal || '0');
  const allAmounts = Array.from(form.querySelectorAll('.hd-split-amount'));
  const lastInput = allAmounts[allAmounts.length - 1];

  // Auto-fill the last line with remaining if the changed input is NOT the last
  if(changedInput && changedInput !== lastInput){
    let sumOthers = 0;
    allAmounts.forEach(i => { if(i !== lastInput){ const v = parseFloat(i.value); if(!isNaN(v) && v > 0) sumOthers += v; } });
    const remaining = Math.round((total - sumOthers) * 100) / 100;
    if(remaining > 0){
      lastInput.value = remaining.toFixed(2);
    } else {
      lastInput.value = '';
    }
  }

  let sum = 0;
  allAmounts.forEach(i => { const v = parseFloat(i.value); if(!isNaN(v)) sum += v; });
  const meter = form.querySelector('.hd-split-meter');
  if(meter){
    const ok = Math.abs(sum - total) < 0.005 && sum > 0;
    const remaining = total - sum;
    meter.textContent = 'Allocated £'+sum.toFixed(2)+' of £'+total.toFixed(2)+(ok ? ' ✓ balanced' : (sum > total ? ' ✗ over by £'+(sum-total).toFixed(2) : ' · £'+remaining.toFixed(2)+' left'));
    meter.style.color = ok ? '#15803d' : (sum > total ? '#b91c1c' : '#334155');
    meter.style.background = ok ? '#dcfce7' : (sum > total ? '#fee2e2' : '#f1f5f9');
  }
  const lines = form.querySelectorAll('.hd-split-line');
  const rm = form.querySelector('button[onclick*="hdRemoveSplitLine"]');
  if(rm) rm.disabled = lines.length <= 2;
}
function hdAddSplitLine(btn){
  const form = btn.closest('form'), wrap = form.querySelector('.hd-split-lines');
  const lines = wrap.querySelectorAll('.hd-split-line'), i = lines.length;
  const tpl = lines[0].cloneNode(true);
  tpl.querySelectorAll('input,select,textarea').forEach(el => {
    if(el.tagName === 'SELECT') el.value = '';
    else if(el.type === 'number') el.value = '';
    else el.value = '';
    if(el.name) el.name = el.name.replace(/splits\[\d+\]/, 'splits['+i+']');
  });
  tpl.querySelectorAll('.select2-container').forEach(c => c.remove());
  tpl.querySelectorAll('select').forEach(s => {
    s.classList.remove('select2-hidden-accessible');
    s.removeAttribute('data-select2-id'); s.removeAttribute('tabindex');
    s.removeAttribute('aria-hidden'); s.style.display = '';
  });
  const clone = wrap.appendChild(tpl);
  if(window.jQuery){ jQuery(clone).find('select.select2').select2({width:'100%', dropdownParent: jQuery(document.body)}); }
  hdSplitTotals(form);
}
function hdRemoveSplitLine(btn){
  const form = btn.closest('form'), wrap = form.querySelector('.hd-split-lines');
  const lines = wrap.querySelectorAll('.hd-split-line');
  if(lines.length <= 2) return;
  lines[lines.length-1].remove();
  wrap.querySelectorAll('.hd-split-line').forEach((line,idx) => {
    line.querySelectorAll('[name]').forEach(el => { el.name = el.name.replace(/splits\[\d+\]/, 'splits['+idx+']'); });
  });
  hdSplitTotals(form);
}
function hdConfirmSplit(form){
  const total = parseFloat(form.dataset.splitTotal||'0'); let sum=0, n=0;
  form.querySelectorAll('.hd-split-amount').forEach(i => { const v=parseFloat(i.value); if(!isNaN(v)&&v>0){sum+=v;n++;} });
  if(n < 2){ alert('A split needs at least two lines.'); return false; }
  if(Math.abs(sum-total) >= 0.005){ alert('Lines total £'+sum.toFixed(2)+' but bank amount is £'+total.toFixed(2)+'. Adjust lines before saving.'); return false; }
  return confirm('Save '+n+' lines totalling £'+sum.toFixed(2)+' and reconcile?');
}
document.addEventListener('input', function(e){
  if(e.target.classList.contains('hd-split-amount')) hdSplitTotals(e.target.closest('form'), e.target);
});
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.hd-split-form').forEach(hdSplitTotals);
});

// ── Find & Match: checkbox handler ──
function hdFindUpdate(cb){
  const list = cb.closest('.hd-find-list');
  const target = parseFloat(list.dataset.target || '0');
  const panel = cb.closest('.hd-tab-panel');
  const counter = panel ? panel.querySelector('.hd-find-counter') : null;
  const submitBtn = cb.closest('form').querySelector('.hd-find-submit');

  let selected = 0;
  list.querySelectorAll('.hd-find-cb').forEach(c => {
    if(c.checked) selected += parseFloat(c.dataset.amount || '0');
  });
  selected = Math.round(selected * 100) / 100;
  const remaining = Math.round((target - selected) * 100) / 100;

  if(counter){
    counter.textContent = 'Selected: £'+selected.toFixed(2)+' / Target: £'+target.toFixed(2)+' · Remaining: £'+Math.max(0,remaining).toFixed(2);
    counter.style.background = Math.abs(remaining) < 0.005 ? '#dcfce7' : (selected > target ? '#fee2e2' : '#f1f5f9');
    counter.style.color = Math.abs(remaining) < 0.005 ? '#15803d' : (selected > target ? '#b91c1c' : '#334155');
  }

  // Disable unchecked items if adding them would exceed target
  list.querySelectorAll('.hd-find-item').forEach(item => {
    const itemCb = item.querySelector('.hd-find-cb');
    if(!itemCb || itemCb.checked) return;
    const itemAmt = parseFloat(itemCb.dataset.amount || '0');
    item.classList.toggle('capped', selected + itemAmt > target + 0.005);
  });

  if(submitBtn){
    submitBtn.disabled = !(Math.abs(selected - target) < 0.005 && selected > 0);
  }

  // If checked would exceed, undo
  if(selected > target + 0.005){
    cb.checked = false;
    hdFindUpdate(cb);
  }
}

// ── Find & Match: search filter ──
document.addEventListener('input', function(e){
  if(!e.target.classList.contains('hd-find-search')) return;
  const term = e.target.value.toLowerCase().trim();
  const list = e.target.closest('.hd-tab-panel').querySelector('.hd-find-list');
  if(!list) return;
  list.querySelectorAll('.hd-find-item').forEach(item => {
    const search = (item.dataset.search || '').toLowerCase();
    item.style.display = (!term || search.includes(term)) ? '' : 'none';
  });
});

// ── Quick Add Head wiring ──
window.openQuickHeadFor = function(btn){
  var sel = hdHeadSelectFor(btn);
  QuickAccountHead.open({ target: sel });
};
function hdHeadSelectFor(btn){
  var scope = btn.closest('.hd-field') || btn.closest('label') || btn.closest('.hd-tab-panel');
  if(!scope) return null;
  var sel = scope.querySelector('select[name="account_head_id"]') || scope.querySelector('select[name^="splits["]');
  return sel;
}
</script>

@endsection

@section('script')
{{-- 1) Client / business dropdowns --}}
<script>
jQuery(function ($) {
  var presetCredentialId = "{{ $presetId }}";
  var presetBusiness = "{{ $businessId ?? request('client_id') ?? '' }}";
  var presetApplied = false;

  $('#filterClient').select2({
    placeholder: "-- Choose a client --",
    allowClear: true,
    ajax: {
      url: "{{ url('/admin/receipts/search-clients') }}",
      dataType: 'json', delay: 250,
      data: function(params){ return { q: params.term }; },
      processResults: function(data){ return { results: data }; },
      cache: true
    }
  }).on('change', function(){
    var val = $(this).val(); if(!val) return;
    if(presetApplied){ presetApplied = false; return; }
    window.location.href = "{{ route('admin.bank-reconciliation.index') }}?client_credential_id=" + val;
  });

  if(presetCredentialId){
    $.get("{{ url('/admin/receipts/search-clients') }}", { q: '' }, function(data){
      var match = data.find(function(item){ return item.id == presetCredentialId; });
      if(match){
        presetApplied = true;
        $('#filterClient').append(new Option(match.text, match.id, true, true)).trigger('change');
        $('#ciName').text(match.text);
        $('#ciEmail').text(match.email || 'N/A');
        $('#ciPhone').text(match.phone || 'N/A');
      }
    });

    $.get("{{ url('/admin/receipts/get-clients-by-credential') }}", { client_credential_id: presetCredentialId }, function(data){
      var opts = '<option value="">All businesses</option>';
      $.each(data, function(i, c){
        var sel = String(c.id) === String(presetBusiness) ? ' selected' : '';
        opts += '<option value="' + c.id + '"' + sel + '>' + c.name + '</option>';
      });
      $('#filterBusiness').html(opts).trigger('change.select2');
    });
  }
});
</script>

{{-- 2) AJAX action handler --}}
<script>
jQuery(function($){
  $(document).on('submit', '.hd-action-form', function(e){
    var $form = $(this);
    e.preventDefault();
    $.post($form.attr('action'), $form.serialize(), function(resp){
      if(resp && resp.success){
        toastr.success(resp.message || 'Saved');
        var scrollY = window.scrollY;
        $.get(window.location.href, function(html){
          var $parsed = $('<div>').html(html);
          var $newWs = $parsed.find('#hd-reconciliation-workspace');
          if($newWs.length){
            $('#hd-reconciliation-workspace').replaceWith($newWs);
            window.scrollTo(0, scrollY);
            $newWs.find('select.select2:not(.select2-hidden-accessible)').select2({width:'100%'});
            $newWs.find('.hd-split-form').each(function(){ hdSplitTotals(this); });
          }
        });
      } else {
        toastr.error(resp && resp.message ? resp.message : 'Failed');
      }
    }).fail(function(xhr){
      var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Request failed';
      toastr.error(msg);
    });
  });
});
</script>
@endsection