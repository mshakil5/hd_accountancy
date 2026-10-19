@extends('admin.layouts.admin')
@php $presetId = request('client_credential_id'); @endphp
@section('content')
<div class="container-fluid py-4">
<style>
.hd-card{background:white;border:1px solid #e5eaf1;border-radius:12px;padding:18px;margin-bottom:15px}
.hd-small{color:#64748b;font-size:12px}.hd-scroll{overflow-x:auto}
.hd-table{width:100%;font-size:13px}.hd-table th,.hd-table td{border-bottom:1px solid #e7ebf1;padding:10px;vertical-align:top}
.hd-table th{background:#f1f5f9}.hd-status{font-weight:600;color:#334155}
.hd-actions{display:flex;gap:5px;align-items:center;flex-wrap:wrap}
.hd-actions select,.hd-actions input{max-width:195px;min-height:34px}
.hd-actions button{min-height:34px}
.hd-reconcile-row{display:grid;grid-template-columns:minmax(240px,41%) minmax(0,59%);border:1px solid #dae4ef;border-radius:9px;margin:16px 0;overflow:hidden;background:#fff}
.hd-bank-side{background:#f2f7fa;padding:19px;border-right:1px solid #dae4ef}.hd-action-side{padding:13px 18px;min-width:0}
.hd-bank-description{font-size:17px;font-weight:600;margin:12px 0 5px;overflow-wrap:anywhere}.hd-pill{padding:3px 8px;background:#e2e8f0;border-radius:20px;font-size:11px}
.hd-amounts{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:19px 0}.hd-amounts>div{display:flex;flex-direction:column}.hd-amounts strong{font-size:18px}
.hd-row-tabs{display:flex;flex-wrap:wrap;gap:2px;border-bottom:1px solid #e2e8f0;margin-bottom:14px}.hd-tab{border:0;background:transparent;color:#334155;padding:9px 10px;cursor:pointer;font-size:13px}.hd-tab.active{color:#0369a1;border-bottom:3px solid #0284c7;font-weight:700}
.hd-tab-panel[hidden]{display:none!important}.hd-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;align-items:end}.hd-form-grid button{justify-self:start}.hd-form-grid label,.hd-inline-form label{font-size:12px;font-weight:600;width:100%}.hd-inline-form{display:flex;flex-wrap:wrap;gap:8px;align-items:end}.hd-inline-form label{max-width:180px}
.select2-container .select2-selection--single{height:38px!important;border:1px solid #ced4da!important;border-radius:.25rem!important}
.select2-container--default .select2-selection--single .select2-selection__rendered{line-height:36px!important}
.select2-container--default .select2-selection--single .select2-selection__arrow{height:36px!important}
 .hd-bank-overview{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px}
.hd-bank-overview-card{display:block;border:1px solid #d9e4ee;border-radius:12px;padding:16px;background:#fff;color:#233247;text-decoration:none!important;transition:border-color .15s,box-shadow .15s}
.hd-bank-overview-card:hover,.hd-bank-overview-card:focus{border-color:#0284c7;box-shadow:0 2px 9px rgba(2,132,199,.10);color:#233247}
.hd-bank-overview-card.is-selected{border:2px solid #0284c7;padding:15px}
.hd-bank-metrics{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;margin:14px 0}
.hd-bank-metrics strong{display:block;font-size:20px}.hd-bank-metrics small{font-size:11px;color:#64748b}
.hd-bank-progress{background:#e2e8f0;height:8px;border-radius:8px;overflow:hidden}.hd-bank-progress span{display:block;height:100%;background:#16a34a}
@media(max-width:850px){.hd-reconcile-row{grid-template-columns:1fr}.hd-bank-side{border-right:0;border-bottom:1px solid #dae4ef}.hd-form-grid{grid-template-columns:1fr}}
</style>
<div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
 <div><h3 class="mb-1">Bank Reconciliation</h3><p class="hd-small mb-0">Review bank transactions, match uploaded receipts, categorise exceptions and <strong>approve the reconciliation</strong>. Only an approved row posts to the ledger, so a receipt reaches the P&amp;L / balance sheet once its bank line is approved.</p></div>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="hd-card">
 <div class="row align-items-center">
  <div class="col-md-4">
   <label class="font-weight-bold">Select Client</label>
   <select class="form-control select2" id="bankFilterClient" style="width:100%"><option value="">-- Choose a client --</option></select>
  </div>
  @if($presetId)
  <div class="col-md-8">
   <div class="d-flex align-items-center" style="margin-top:24px;gap:18px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:12px 20px;border-radius:12px;color:#fff;">
    <span class="font-weight-bold" style="font-size:18px;" id="ciName"></span>
    <span style="opacity:.9;font-size:14px;"><i class="bi bi-envelope mr-1"></i><span id="ciEmail"></span></span>
    <span style="opacity:.9;font-size:14px;"><i class="bi bi-telephone mr-1"></i><span id="ciPhone"></span></span>
    <a href="{{ url('/admin/bank-reconciliation') }}" class="btn btn-sm btn-light ml-2" style="color:#667eea;font-weight:600;">Change</a>
   </div>
  </div>
  @endif
 </div>
</div>
@if(!$presetId)
<div class="hd-card"><div class="alert alert-info mb-0"><strong>Select a client to begin.</strong> Choose a client credential above, then pick the business whose bank statement you want to reconcile.</div></div>
@else
<div class="hd-card">
 <form method="GET" action="{{ route('admin.bank-reconciliation.index') }}" class="row align-items-end">
  <input type="hidden" name="client_credential_id" value="{{ $presetId }}">
  <div class="col-md-3"><label>Business</label><select class="form-control select2" name="client_id" id="bankFilterBusiness" style="width:100%"><option value="">All businesses</option></select></div>
  <div class="col-md-3"><label>Bank Account</label><select class="form-control select2" name="bank_account_id" style="width:100%">
<option value="">All bank accounts</option>
@foreach($bankAccounts as $bank)<option value="{{ $bank->id }}" {{ (int)$bankAccountId===(int)$bank->id?'selected':'' }}>{{ $bank->bank_name }} — {{ $bank->account_label }} {{ $bank->account_last_four?'••'.$bank->account_last_four:'' }}{{ !$bank->active?' (Legacy)':'' }}</option>@endforeach
</select></div>
<div class="col-md-3"><label>Status</label><select class="form-control select2" name="status" style="width:100%"><option value="">All</option>
  @foreach(['unmatched','needs_review','matched','split','categorized'] as $s)<option value="{{ $s }}" {{ request('status')===$s?'selected':'' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>@endforeach
  </select></div>
  <div class="col-md-3"><label>Approval</label><select class="form-control select2" name="approval" style="width:100%"><option value="">All</option>
  @foreach(['draft'=>'Draft','approved'=>'Approved','rejected'=>'Rejected'] as $val=>$label)<option value="{{ $val }}" {{ request('approval')===$val?'selected':'' }}>{{ $label }}</option>@endforeach
  </select></div>
  <div class="col-md-2"><button class="btn btn-primary">Filter transactions</button></div>
 </form>
</div>
@if($businessId)
<div class="hd-card" id="hd-bank-account-overview">
  <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
    <div><h4 class="mb-1">Bank accounts overview</h4><p class="hd-small mb-0">Choose an account to reconcile only its transactions. Figures are for the selected business and include all imported statement periods.</p></div>
    @if($bankAccountId)<a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.bank-reconciliation.index', ['client_credential_id'=>$credentialId,'client_id'=>$businessId]) }}#hd-bank-account-overview">All bank accounts</a>@endif
  </div>
  @if(($bankOverview??collect())->isEmpty())
    <div class="alert alert-info mb-0">No bank accounts registered yet. Use the Add account form below to register HSBC, Barclays, Halifax, or another bank.</div>
  @else
    @php
      $overallTotal=$bankOverview->sum('total');
      $overallApproved=$bankOverview->sum('approved');
      $overallUnmatched=$bankOverview->sum('unmatched');
      $overallReview=$bankOverview->sum('needs_review');
    @endphp
    <p class="hd-small mb-3"><strong>All banks:</strong> {{ number_format($overallTotal) }} imported transactions · {{ number_format($overallApproved) }} approved · {{ number_format($overallUnmatched) }} unmatched · {{ number_format($overallReview) }} need review</p>
    <div class="hd-bank-overview">
      @foreach($bankOverview as $overview)
        @php $b=$overview->bank; $progress=$overview->total ? round(100*$overview->approved/$overview->total) : 0; @endphp
        <a class="hd-bank-overview-card {{ (int)$bankAccountId===(int)$b->id ? 'is-selected' : '' }}" href="{{ route('admin.bank-reconciliation.index', ['client_credential_id'=>$credentialId,'client_id'=>$businessId,'bank_account_id'=>$b->id]) }}#hd-reconciliation-workspace" aria-label="Open reconciliation for {{ $b->bank_name }} {{ $b->account_label }}">
          <div class="d-flex justify-content-between align-items-start">
            <div><strong>{{ $b->bank_name }}</strong><div class="hd-small">{{ $b->account_label }} @if($b->account_last_four) · ending {{ $b->account_last_four }} @endif · {{ $b->currency }}</div></div>
            <span class="hd-pill">{{ $b->active ? 'Active' : 'Legacy / inactive' }}</span>
          </div>
          <div class="hd-bank-metrics">
            <div><small>Total transactions</small><strong>{{ number_format($overview->total) }}</strong></div>
            <div><small>Matched receipts</small><strong>{{ number_format($overview->matched) }}</strong></div>
            <div><small>Unmatched</small><strong>{{ number_format($overview->unmatched) }}</strong></div>
            <div><small>Approved</small><strong>{{ number_format($overview->approved) }}</strong></div>
            <div><small>Needs review</small><strong>{{ number_format($overview->needs_review) }}</strong></div>
            <div><small>Pending approval</small><strong>{{ number_format($overview->pending) }}</strong></div>
          </div>
          <div class="hd-bank-progress"><span style="width:{{ $progress }}%"></span></div>
          <div class="d-flex justify-content-between mt-2 hd-small"><span>{{ $progress }}% approved</span><span>Open reconciliation →</span></div>
          <div class="hd-small mt-2">{{ $overview->statement_count }} statement(s) uploaded @if($overview->last_upload) · latest {{ \Illuminate\Support\Carbon::parse($overview->last_upload)->format('d M Y') }}@endif</div>
        </a>
      @endforeach
    </div>
    <p class="hd-small mt-3 mb-0">Matched counts reflect linked receipts; approved counts reflect accountant sign-off. The figures are different stages and should not be added together. Bank balances are not shown until statement balance verification is supported.</p>
  @endif
</div>
@endif
@if($businessId)
<div class="hd-card"><h5>Bank accounts for this business</h5>
<p class="hd-small">Register each bank separately (e.g. HSBC, Barclays, Halifax). Select a bank in the filter above. Never upload statements for different accounts into one bank record.</p>
<form method="POST" action="{{ route('admin.bank-reconciliation.accounts.create') }}" class="row align-items-end">@csrf
<input type="hidden" name="client_id" value="{{ $businessId }}"><input type="hidden" name="client_credential_id" value="{{ $credentialId }}">
<div class="col-md-3"><label>Bank</label><input name="bank_name" class="form-control" maxlength="100" placeholder="HSBC" required></div>
<div class="col-md-3"><label>Account nickname</label><input name="account_label" class="form-control" maxlength="100" placeholder="Business Current" required></div>
<div class="col-md-2"><label>Last 4 digits</label><input name="account_last_four" class="form-control" maxlength="4" pattern="[0-9]{4}" placeholder="1234"></div>
<div class="col-md-2"><label>Currency</label><select class="form-control select2" name="currency"><option>GBP</option><option>EUR</option><option>USD</option></select></div>
<div class="col-md-2"><button class="btn btn-outline-primary">Add account</button></div>
</form></div>
@endif
<div class="row">
@foreach(['unmatched','needs_review','matched','split','categorized'] as $status)
<div class="col-6 col-md-2"><div class="hd-card"><div class="hd-small">{{ ucwords(str_replace('_',' ',$status)) }}</div><h3>{{ (int)($stats[$status]??0) }}</h3></div></div>
@endforeach
<div class="col-6 col-md-2"><div class="hd-card" style="border-color:#bbf7d0"><div class="hd-small">Approved</div><h3 style="color:#15803d">{{ (int)($approvalStats['approved']??0) }}</h3></div></div>
</div>
<div class="hd-card">
<h5>Imported statements</h5>
<div class="hd-scroll"><table class="hd-table"><thead><tr><th>Uploaded</th><th>File</th><th>Bank</th><th>Type</th><th>Processing</th><th>Imported rows</th><th></th></tr></thead><tbody>
@forelse($imports as $imp)
<tr><td>{{ $imp->created_at }}</td><td>{{ $imp->filename }}</td><td>{{ optional($bankAccounts->firstWhere('id',$imp->bank_account_id))->bank_name ?? 'Unassigned' }}</td><td>{{ strtoupper($imp->file_type??'csv') }}</td><td>{{ str_replace('_',' ', $imp->processing_status??'parsed') }}</td><td>{{ $imp->transaction_count }}</td>
<td>@if($imp->storage_path)<a href="{{ route('admin.bank-reconciliation.statement',$imp->id) }}">View original statement</a>@endif
<form method="POST" action="{{ route('admin.bank-reconciliation.approve-import',$imp->id) }}" class="mt-1">@csrf
 <input type="hidden" name="client_id" value="{{ $imp->client_id }}">
 <button class="btn btn-outline-success btn-sm" onclick="return confirm('Approve every reconciled transaction (matched, categorised or split) in this statement?')">Approve reconciled rows</button>
</form></td></tr>
@empty <tr><td colspan="7">No statements imported yet. Clients can upload from the mobile app.</td></tr>@endforelse
</tbody></table></div>
</div>
<div class="hd-card hd-workspace" id="hd-reconciliation-workspace">
<div class="d-flex justify-content-between align-items-center flex-wrap"><div><h4>Reconcile bank transactions</h4><p class="hd-small">Bank details on the left; accountant actions and uploaded receipts on the right. Reconcile each row, then <strong>approve</strong> it. Approval locks the row and posts it to the existing account heads, so the P&amp;L and balance sheet reflect it without duplication.</p></div><span class="hd-small">{{ $entries->total() }} bank transactions in this view</span></div>
@forelse($entries as $entry)
@php
// Exact-amount suggestions. A receipt already matched or split elsewhere is refused
// by the backend, so never offer it here (the Find tab labels it instead).
$exact = $receipts->filter(fn($r)=>abs((float)($r->detail->total_amount??0)-abs((float)$entry->amount))<0.01)
    ->filter(fn($r)=>!(isset($claimedReceiptIds[(int)$r->id])))
    ->take(20);
$locked = in_array($entry->status,['matched','split']);
$pending = $entry->status==='needs_review';
$reconStatus = $entry->reconciliation_status ?? 'draft';
$approved = $reconStatus==='approved';
$rejected = $reconStatus==='rejected';
@endphp
<div class="hd-reconcile-row" id="bank-entry-{{ $entry->id }}">
 <div class="hd-bank-side">
  <div class="d-flex justify-content-between"><span class="hd-small">{{ $entry->transaction_date }}</span><span>@if($approved)<span class="hd-pill" style="background:#dcfce7;color:#15803d">Approved</span>@if($entry->journal_posted_at)<span class="hd-pill" style="background:#dbeafe;color:#1d4ed8">Posted to ledger</span>@endif @elseif($rejected)<span class="hd-pill" style="background:#fee2e2;color:#b91c1c">Rejected</span>@endif <span class="hd-pill">{{ ucwords(str_replace('_',' ',$entry->status)) }}</span></span></div>
  <div class="hd-bank-description">{{ $entry->description }}</div><div class="hd-small">Bank: {{ optional($bankAccounts->firstWhere('id',$entry->bank_account_id))->bank_name ?? 'Unassigned' }}</div>
  @if($entry->reference)<div class="hd-small">Reference: {{ $entry->reference }}</div>@endif
  <div class="hd-amounts"><div><span class="hd-small">Spent</span><strong>{{ (float)$entry->amount<0?'£'.number_format(abs((float)$entry->amount),2):'—' }}</strong></div><div><span class="hd-small">Received</span><strong>{{ (float)$entry->amount>=0?'£'.number_format((float)$entry->amount,2):'—' }}</strong></div></div>
  <details><summary class="hd-small">More details</summary><div class="hd-small">Bank transaction #{{ $entry->id }} @if($entry->receipt_id) · Linked receipt #{{ $entry->receipt_id }}@endif</div></details>
  @php $entrySettlements=$settlements[$entry->id]??collect(); @endphp
  @if($entrySettlements->isNotEmpty())
  <div class="hd-small mt-2">Settlements:
   @foreach($entrySettlements as $st)
   <span class="hd-pill" style="background:{{ $st->status==='cleared'?'#dcfce7':'#fef9c3' }}">{{ ucwords(str_replace('_',' ',$st->settlement_type)) }} · £{{ number_format((float)$st->settled_amount,2) }} settled@if((float)$st->remaining_amount>0) · £{{ number_format((float)$st->remaining_amount,2) }} remaining@endif</span>
   @endforeach
  </div>
  @endif
 </div>
 <div class="hd-action-side">
  @if($approved)
    <div class="alert alert-success py-2">Reconciliation <strong>approved</strong>@if($entry->approved_at) on {{ $entry->approved_at }}@endif. @if($entry->journal_posted_at)Posted to the ledger and reflected in the P&amp;L / balance sheet. @else No ledger rows were needed for this entry. @endif This row is locked.</div>
    <form method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}">@csrf<input type="hidden" name="client_id" value="{{ $entry->client_id }}"><input type="hidden" name="action" value="reopen"><button class="btn btn-outline-secondary btn-sm" onclick="return confirm('Reopen this approved reconciliation for editing?')">Reopen for editing</button></form>
  @elseif($pending)
    <div class="alert alert-warning py-2">PDF row requires verification before it can be reconciled.</div>
    <form method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}" class="hd-inline-form">@csrf
     <input type="hidden" name="client_id" value="{{ $entry->client_id }}"><input type="hidden" name="action" value="verify">
     <label>Date<input class="form-control" type="date" name="transaction_date" value="{{ $entry->transaction_date }}" required></label>
     <label>Signed amount (£)<input class="form-control" type="number" step="0.01" name="amount" value="{{ $entry->amount }}" required></label>
     <label>Description<input class="form-control" name="description" value="{{ $entry->description }}" required></label>
     <button class="btn btn-warning btn-sm" onclick="return confirm('Checked this row against the original PDF?')">Verify</button>
    </form>
  @elseif($locked)
   <div class="alert alert-success py-2">{{ $entry->status==='split'?'Split saved':'Receipt matched' }}. Approving below locks the row and posts it to the ledger.</div>
   @if($entry->status==='split')<div class="hd-small">Allocation lines: {{ ($splits[$entry->id]??collect())->count() }}</div>@endif
   <div class="d-flex gap-2 flex-wrap mt-2">
    <form method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}">@csrf<input type="hidden" name="client_id" value="{{ $entry->client_id }}"><input type="hidden" name="action" value="unmatch"><button class="btn btn-outline-secondary btn-sm" onclick="return confirm('Undo reconciliation?')">Undo reconciliation</button></form>
    <form method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}">@csrf<input type="hidden" name="client_id" value="{{ $entry->client_id }}"><input type="hidden" name="action" value="approve"><button class="btn btn-success btn-sm" onclick="return confirm('Approve this reconciliation? The row becomes read-only.')">Approve reconciliation</button></form>
    <form method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}">@csrf<input type="hidden" name="client_id" value="{{ $entry->client_id }}"><input type="hidden" name="action" value="reject"><button class="btn btn-outline-danger btn-sm" onclick="return confirm('Reject this reconciliation and return it for review?')">Reject</button></form>
   </div>
  @else
  @if($rejected)<div class="alert alert-danger py-2">Returned for review. Correct it and approve again.</div>@endif
  @if($entry->status==='categorized')
   <div class="alert alert-info py-2">Categorised. Approve to sign this row off.</div>
   <div class="d-flex gap-2 flex-wrap mb-3">
    <form method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}">@csrf<input type="hidden" name="client_id" value="{{ $entry->client_id }}"><input type="hidden" name="action" value="approve"><button class="btn btn-success btn-sm" onclick="return confirm('Approve this reconciliation? The row becomes read-only.')">Approve reconciliation</button></form>
    <form method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}">@csrf<input type="hidden" name="client_id" value="{{ $entry->client_id }}"><input type="hidden" name="action" value="reject"><button class="btn btn-outline-danger btn-sm" onclick="return confirm('Reject this reconciliation and return it for review?')">Reject</button></form>
   </div>
  @endif
  <div class="hd-row-tabs" role="tablist" aria-label="Reconciliation actions for transaction {{ $entry->id }}">
   <button type="button" class="hd-tab active" data-hd-tab="match" aria-selected="true">Match</button>
   <button type="button" class="hd-tab" data-hd-tab="create" aria-selected="false">Create</button>
   <button type="button" class="hd-tab" data-hd-tab="split" aria-selected="false">Split</button>
   <button type="button" class="hd-tab" data-hd-tab="settle" aria-selected="false">Settle</button>
   <button type="button" class="hd-tab" data-hd-tab="discuss" aria-selected="false">Discuss</button>
   <button type="button" class="hd-tab" data-hd-tab="find" aria-selected="false">Find &amp; Match</button>
   <button type="button" class="hd-tab" data-hd-tab="transfer" aria-selected="false">Transfer</button>
  </div>
  <section class="hd-tab-panel" data-hd-panel="match">
   <p class="hd-small">Suggested uploaded receipts with exactly matching amounts. Confirm the supplier and evidence before matching.</p>
   <form method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}" class="hd-form-grid">@csrf
    <input type="hidden" name="client_id" value="{{ $entry->client_id }}"><input type="hidden" name="action" value="match">
    <label>Who / receipt<select class="form-control select2" name="receipt_id" required><option value="">{{ $exact->isEmpty()?'No exact receipt suggestions':'Select suggested receipt' }}</option>@foreach($exact as $r)<option value="{{ $r->id }}">#{{ $r->receipt_number }} · {{ $r->supplier?:'Unknown supplier' }} · £{{ number_format((float)$r->detail->total_amount,2) }}</option>@endforeach</select></label>
    <label>What / account head<select class="form-control select2" name="account_head_id"><option value="">Use receipt account head</option>@foreach($heads as $h)<option value="{{ $h->id }}">{{ $h->code }} — {{ $h->name }}</option>@endforeach</select>
     <button type="button" class="btn btn-sm btn-success mt-1" style="font-size:11px;border-radius:20px;padding:2px 10px;background:linear-gradient(45deg,#28a745,#20c997);border:none;" onclick="openQuickHeadFor(this)" title="Quickly create a missing account head"><i class="fa fa-plus-circle"></i> Quick Add Head</button>
    </label>
    <button class="btn btn-primary btn-sm" {{ $exact->isEmpty()?'disabled':'' }} onclick="return confirm('Approve exact receipt match?')">OK · Match</button>
   </form>
   @php $fuzzy=$fuzzyMatches[(int)$entry->id]??[]; @endphp
   @if(!empty($fuzzy))
   <p class="hd-small mt-3 mb-1">Close matches (amount within 10%, scored on amount, date, reference and supplier). Review before settling.</p>
   <div class="hd-scroll"><table class="hd-table"><thead><tr><th>Receipt</th><th>Supplier</th><th>Amount</th><th>Score</th><th></th></tr></thead><tbody>
    @foreach($fuzzy as $f)
    <tr><td>#{{ $f['receipt_number'] }}</td><td>{{ $f['supplier']?:'Unknown' }}</td><td>£{{ number_format($f['amount'],2) }}</td><td>{{ $f['score'] }}</td>
     <td><form method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}">@csrf
      <input type="hidden" name="client_id" value="{{ $entry->client_id }}"><input type="hidden" name="action" value="settle">
      <input type="hidden" name="receipt_id" value="{{ $f['id'] }}"><input type="hidden" name="settlement_type" value="partial">
      <button class="btn btn-outline-primary btn-sm" onclick="return confirm('Record this receipt as a partial settlement against the bank line?')">Settle</button>
     </form></td></tr>
    @endforeach
   </tbody></table></div>
   @endif
  </section>
  <section class="hd-tab-panel" data-hd-panel="create" hidden>
   <p class="hd-small">Categorise a bank movement without a linked receipt. Approving the row posts it to the chosen account head, so it appears in the P&amp;L / balance sheet.</p>
   <form method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}" class="hd-form-grid">@csrf
    <input type="hidden" name="client_id" value="{{ $entry->client_id }}"><input type="hidden" name="action" value="categorize">
    <label>What / account head
     <span>
      <button type="button" class="btn btn-sm btn-success" style="font-size:11px;border-radius:20px;padding:2px 10px;background:linear-gradient(45deg,#28a745,#20c997);border:none;" onclick="openQuickHeadFor(this)" title="Quickly create a missing account head"><i class="fa fa-plus-circle"></i> Quick Add Head</button>
      <button type="button" class="btn btn-sm btn-info" style="font-size:11px;border-radius:20px;padding:2px 10px;" onclick="openQuickTypeFor(this)" title="Quickly create a type under the selected head"><i class="fa fa-plus-circle"></i> Quick Add Type</button>
     </span>
     <select class="form-control select2" name="account_head_id" required><option value="">Choose account head</option>@foreach($heads as $h)<option value="{{ $h->id }}" {{ $entry->account_head_id==$h->id?'selected':'' }}>{{ $h->code }} — {{ $h->name }}</option>@endforeach</select></label>
    <label>Why / description<input class="form-control" type="text" name="review_note" placeholder="Reason for classification"></label>
    <button class="btn btn-primary btn-sm">Save classification</button>
   </form>
  </section>
  <section class="hd-tab-panel" data-hd-panel="split" hidden>
   <p class="hd-small">Allocate this bank movement across two or more heads or receipts. Lines must total £{{ number_format(abs((float)$entry->amount),2) }}. Approving posts each line to its account head, including any VAT recorded here.</p>
   <form method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}" class="hd-split-form" data-split-total="{{ number_format(abs((float)$entry->amount),2,'.','') }}">@csrf
    <input type="hidden" name="client_id" value="{{ $entry->client_id }}"><input type="hidden" name="action" value="split">
    <div class="hd-split-lines">
     @foreach([0,1] as $i)<div class="hd-form-grid mb-2 hd-split-line"><label>Line amount (£)<input class="form-control hd-split-amount" type="number" name="allocations[{{ $i }}][amount]" min="0.01" step="0.01" required></label>
      <label>Account head
       <span><button type="button" class="btn btn-sm btn-success" style="font-size:11px;border-radius:20px;padding:2px 10px;background:linear-gradient(45deg,#28a745,#20c997);border:none;" onclick="openQuickHeadFor(this)" title="Quickly create a missing account head"><i class="fa fa-plus-circle"></i> Quick Add Head</button></span>
       <select class="form-control select2" name="allocations[{{ $i }}][account_head_id]"><option value="">Choose account</option>@foreach($heads as $h)<option value="{{ $h->id }}">{{ $h->code }} — {{ $h->name }}</option>@endforeach</select></label>
      <label>Or existing receipt<select class="form-control select2" name="allocations[{{ $i }}][receipt_id]"><option value="">No receipt (head only)</option>@foreach($receipts as $r)<option value="{{ $r->id }}" {{ isset($claimedReceiptIds[(int)$r->id])?'disabled':'' }}>#{{ $r->receipt_number }} · {{ $r->supplier?:'Unknown supplier' }} · £{{ number_format((float)$r->detail->total_amount,2) }}{{ isset($claimedReceiptIds[(int)$r->id])?' · already used':'' }}</option>@endforeach</select></label>
      <label>VAT %<input class="form-control" type="number" name="allocations[{{ $i }}][tax_percent]" min="0" max="100" step="0.01" value="0"></label>
      <label>VAT amount (£)<input class="form-control" type="number" name="allocations[{{ $i }}][tax_amount]" min="0" step="0.01" value="0"></label>
      <label>Explanation<input class="form-control" name="allocations[{{ $i }}][memo]" placeholder="e.g. outstanding payment"></label></div>@endforeach
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap mt-1">
     <button type="button" class="btn btn-outline-secondary btn-sm" onclick="hdAddSplitLine(this)"><i class="fa fa-plus"></i> Add line</button>
     <button type="button" class="btn btn-outline-secondary btn-sm" onclick="hdRemoveSplitLine(this)" disabled><i class="fa fa-minus"></i> Remove line</button>
     <span class="hd-small hd-split-meter" role="status">Allocated £0.00 of £{{ number_format(abs((float)$entry->amount),2) }}</span>
    </div>
    <button class="btn btn-primary btn-sm mt-2" onclick="return hdConfirmSplit(this)">Save split</button>
   </form>
  </section>
  <section class="hd-tab-panel" data-hd-panel="discuss" hidden>
   <p class="hd-small">Add an internal accountant note or create a missing-receipt request. A request record does not guarantee a push notification is sent.</p>
   @php $entryNotes=$notes[$entry->id]??collect(); $entryRequests=$receiptRequests[$entry->id]??collect(); @endphp
   @if($entryNotes->isNotEmpty())
   <div class="hd-small mb-2"><strong>Notes on this row</strong>
    @foreach($entryNotes as $n)<div class="hd-pill" style="background:#f1f5f9;display:block;text-align:left;white-space:normal;line-height:1.4;margin-top:4px">{{ $n->body }} <span style="opacity:.6">· {{ $n->created_at }}</span></div>@endforeach
   </div>
   @endif
   @if($entryRequests->isNotEmpty())
   <div class="hd-small mb-2"><strong>Receipt requests</strong>
    @foreach($entryRequests as $rq)<div class="hd-pill" style="background:{{ $rq->status==='open'?'#fef9c3':'#dcfce7' }};display:block;text-align:left;white-space:normal;line-height:1.4;margin-top:4px">{{ $rq->message }} <span style="opacity:.6">· {{ ucfirst($rq->status) }} · {{ $rq->created_at }}</span></div>@endforeach
   </div>
   @endif
   <form method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}" class="mb-2">@csrf<input type="hidden" name="client_id" value="{{ $entry->client_id }}"><input type="hidden" name="action" value="add_note"><label>Internal note<input class="form-control" name="review_note" required maxlength="2000" placeholder="Clarification for accountant"></label><button class="btn btn-outline-primary btn-sm mt-2">Save note</button></form>
   <form method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}">@csrf<input type="hidden" name="client_id" value="{{ $entry->client_id }}"><input type="hidden" name="action" value="request_receipt"><label>Message to client<input class="form-control" name="review_note" placeholder="Please upload supporting receipt"></label><button class="btn btn-outline-warning btn-sm mt-2">Request receipt</button></form>
  </section>
  <section class="hd-tab-panel" data-hd-panel="find" hidden>
   <p class="hd-small">Search among the most recent 400 uploaded receipts. Only exact-amount receipts can be confirmed; partial/multiple matches are not yet supported.</p>
   <input type="search" class="form-control mb-2 hd-receipt-search" placeholder="Search receipt number or supplier" aria-label="Search receipts">
   <form method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}" class="hd-form-grid">@csrf<input type="hidden" name="client_id" value="{{ $entry->client_id }}"><input type="hidden" name="action" value="match">
    <label>Uploaded receipt<select class="form-control hd-receipt-select" name="receipt_id" required><option value="">Choose receipt</option>@foreach($receipts as $r)@php $claimed=isset($claimedReceiptIds[(int)$r->id]); $st=$settledTotals[$r->id]??null; @endphp<option value="{{ $r->id }}" data-search="{{ strtolower(($r->receipt_number??'').' '.($r->supplier??'')) }}" {{ $claimed?'disabled':'' }}>#{{ $r->receipt_number }} · {{ $r->supplier?:'Unknown supplier' }} · £{{ number_format((float)$r->detail->total_amount,2) }}{{ $claimed?' · already matched':($st?' · £'.number_format((float)$st->settled,2).' settled':'') }}</option>@endforeach</select></label>
    <label>Account head<select class="form-control select2" name="account_head_id"><option value="">Use receipt account head</option>@foreach($heads as $h)<option value="{{ $h->id }}">{{ $h->code }} — {{ $h->name }}</option>@endforeach</select>
     <button type="button" class="btn btn-sm btn-success mt-1" style="font-size:11px;border-radius:20px;padding:2px 10px;background:linear-gradient(45deg,#28a745,#20c997);border:none;" onclick="openQuickHeadFor(this)" title="Quickly create a missing account head"><i class="fa fa-plus-circle"></i> Quick Add Head</button>
    </label>
    <button class="btn btn-primary btn-sm" onclick="return confirm('Confirm this selected receipt matches the exact bank amount?')">Match selected receipt</button>
   </form>
  </section>
  <section class="hd-tab-panel" data-hd-panel="settle" hidden>
   <p class="hd-small">Record a partial or over/under settlement when a receipt does not exactly equal the bank amount. A settlement record tracks the outstanding remainder; the row stays in draft until approved.</p>
   <form method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}" class="hd-form-grid">@csrf
    <input type="hidden" name="client_id" value="{{ $entry->client_id }}"><input type="hidden" name="action" value="settle">
    <label>Receipt<select class="form-control select2" name="receipt_id"><option value="">No receipt (write-off / unlinked)</option>@foreach($receipts as $r)@php $st=$settledTotals[$r->id]??null; $claimed=isset($claimedReceiptIds[(int)$r->id]); @endphp<option value="{{ $r->id }}">#{{ $r->receipt_number }} · {{ $r->supplier?:'Unknown supplier' }} · £{{ number_format((float)$r->detail->total_amount,2) }}{{ $st?' · £'.number_format((float)$st->settled,2).' already settled':($claimed?' · matched elsewhere (still settleable)':'') }}</option>@endforeach</select></label>
    <label>Type<select class="form-control select2" name="settlement_type"><option value="partial">Partial</option><option value="over_payment">Over payment</option><option value="under_payment">Under payment</option></select></label>
    <label>Account head (required when no receipt)<select class="form-control select2" name="account_head_id"><option value="">Use receipt account head</option>@foreach($heads as $h)<option value="{{ $h->id }}">{{ $h->code }} — {{ $h->name }}</option>@endforeach</select>
     <button type="button" class="btn btn-sm btn-success mt-1" style="font-size:11px;border-radius:20px;padding:2px 10px;background:linear-gradient(45deg,#28a745,#20c997);border:none;" onclick="openQuickHeadFor(this)" title="Quickly create a missing account head"><i class="fa fa-plus-circle"></i> Quick Add Head</button>
    </label>
    <button class="btn btn-primary btn-sm" onclick="return confirm('Record this settlement?')">Save settlement</button>
   </form>
  </section>
  <section class="hd-tab-panel" data-hd-panel="transfer" hidden>
   <p class="hd-small">Link this movement to its counterparty in another of the client's bank accounts. Amounts must be equal and opposite. Transfers post net-zero to the P&amp;L.</p>
    @php
    // Only offer a counterparty the backend will actually accept: equal and opposite
    // amount, in a different bank account, still open and not already linked.
    $viable=$transferCandidates->filter(fn($c)=>(int)$c->id!==(int)$entry->id
        && abs(round((float)$c->amount*100)+round((float)$entry->amount*100))<1
        && ($c->bank_account_id??null)!==$entry->bank_account_id);
    @endphp
   <form method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}" class="hd-form-grid">@csrf
    <input type="hidden" name="client_id" value="{{ $entry->client_id }}"><input type="hidden" name="action" value="transfer">
    <label>Counterparty entry<select class="form-control select2" name="linked_entry_id" required><option value="">{{ $viable->isEmpty()?'No matching counterpart found':'Choose the other side' }}</option>@foreach($viable as $c)<option value="{{ $c->id }}">#{{ $c->id }} · {{ $c->transaction_date }} · {{ \Illuminate\Support\Str::limit($c->description,40) }} · £{{ number_format((float)$c->amount,2) }} · {{ optional($bankAccounts->firstWhere('id',$c->bank_account_id))->bank_name ?? 'Unassigned' }}</option>@endforeach</select></label>
    <label>Bank account head<select class="form-control select2" name="account_head_id"><option value="">Choose the bank account head</option>@foreach($heads as $h)<option value="{{ $h->id }}">{{ $h->code }} — {{ $h->name }}</option>@endforeach</select>
     <span><button type="button" class="btn btn-sm btn-success" style="font-size:11px;border-radius:20px;padding:2px 10px;background:linear-gradient(45deg,#28a745,#20c997);border:none;" onclick="openQuickHeadFor(this)" title="Quickly create a missing account head"><i class="fa fa-plus-circle"></i> Quick Add Head</button></span>
    </label>
    <button class="btn btn-primary btn-sm" {{ $viable->isEmpty()?'disabled':'' }} onclick="return confirm('Link these two entries as an internal transfer?')">Save transfer</button>
    @if($viable->isEmpty())<p class="hd-small text-muted mb-2">No open entry with an equal and opposite amount in a different bank account. Import both sides of the transfer first.</p>@endif
   </form>
  </section>
  @endif
 </div>
</div>
@empty
<div class="alert alert-info"><strong>No bank transactions to reconcile.</strong> @if($imports->contains(fn($imp)=>(int)$imp->transaction_count===0)) One or more statements have 0 imported rows. PDF extraction must be completed or a supported CSV imported before transaction matching will appear. @else Import a statement for the selected client to begin. @endif</div>
@endforelse
<div class="mt-3">{{ $entries->links() }}</div>
</div>
<script>
document.addEventListener('click',function(e){
 const tab=e.target.closest('[data-hd-tab]'); if(!tab)return;
 const row=tab.closest('.hd-action-side'); if(!row)return;
 row.querySelectorAll('[data-hd-tab]').forEach(el=>{el.classList.toggle('active',el===tab);el.setAttribute('aria-selected',el===tab?'true':'false')});
 row.querySelectorAll('[data-hd-panel]').forEach(el=>{el.hidden=el.dataset.hdPanel!==tab.dataset.hdTab});
 // Re-fit any select2 in the now-visible panel (they init at 0 width while hidden).
 if(window.jQuery){
  const panel=row.querySelector('[data-hd-panel="'+tab.dataset.hdTab+'"]');
  if(panel) panel.querySelectorAll('select.select2').forEach(function(s){
   const $s=jQuery(s);
   if($s.data('select2')){$s.select2('destroy');}
   $s.select2({width:'100%'});
  });
 }
});
document.addEventListener('input',function(e){
 if(!e.target.classList.contains('hd-receipt-search'))return;
 const select=e.target.closest('.hd-tab-panel').querySelector('.hd-receipt-select');
 const term=e.target.value.toLowerCase().trim();
 Array.from(select.options).forEach((option,index)=>{if(index===0)return;option.hidden=!!term && !(option.dataset.search||'').includes(term)});
 if(select.selectedOptions[0]?.hidden) select.value='';
});
// ---- Dynamic split lines: add/remove, live total, exact-balance guard ----
function hdSplitTotals(form){
 const total=parseFloat(form.dataset.splitTotal||'0');
 let sum=0;
 form.querySelectorAll('.hd-split-amount').forEach(i=>{const v=parseFloat(i.value);if(!isNaN(v))sum+=v;});
 const meter=form.querySelector('.hd-split-meter');
 if(meter){
  const ok=Math.abs(sum-total)<0.005&&sum>0;
  meter.textContent='Allocated £'+sum.toFixed(2)+' of £'+total.toFixed(2)+(Math.abs(sum-total)<0.005?' ✓ balanced':(sum>total?' ✗ over by £'+(sum-total).toFixed(2):' · £'+(total-sum).toFixed(2)+' left'));
  meter.style.color=ok?'#15803d':(sum>total?'#b91c1c':'');
 }
 const lines=form.querySelectorAll('.hd-split-line');
 const rm=form.querySelector('button[onclick*="hdRemoveSplitLine"]');
 if(rm) rm.disabled=lines.length<=2;
}
function hdAddSplitLine(btn){
 const form=btn.closest('form'),wrap=form.querySelector('.hd-split-lines'),lines=wrap.querySelectorAll('.hd-split-line');
 const i=lines.length,tpl=lines[0].cloneNode(true);
 // Clear values, then re-index every name to the new line number.
 tpl.querySelectorAll('input,select').forEach(el=>{
  if(el.tagName==='SELECT'){el.value='';}
  else if(el.type==='number'){el.value=el.classList.contains('hd-split-amount')?'':'0';}
  else el.value='';
  if(el.name)el.name=el.name.replace(/allocations\[\d+\]/,'allocations['+i+']');
 });
 tpl.querySelectorAll('.select2-container').forEach(c=>c.remove());
 tpl.querySelectorAll('select').forEach(s=>{s.removeAttribute('data-select2-id');s.style.display='';});
 const clone=wrap.appendChild(tpl);
 if(window.jQuery){const $s=jQuery(clone).find('select.select2');$s.select2({width:'100%'});}
 hdSplitTotals(form);
}
function hdRemoveSplitLine(btn){
 const form=btn.closest('form'),wrap=form.querySelector('.hd-split-lines'),lines=wrap.querySelectorAll('.hd-split-line');
 if(lines.length<=2)return;
 lines[lines.length-1].remove();
 // Re-index remaining lines so the array stays contiguous.
 wrap.querySelectorAll('.hd-split-line').forEach((line,idx)=>{
  line.querySelectorAll('[name]').forEach(el=>{el.name=el.name.replace(/allocations\[\d+\]/,'allocations['+idx+']')});
  const lbl=line.querySelector('label');if(lbl)lbl.innerHTML=lbl.innerHTML.replace(/Line \d+/,'Line '+(idx+1));
 });
 hdSplitTotals(form);
}
function hdConfirmSplit(form){
 const total=parseFloat(form.dataset.splitTotal||'0');let sum=0,n=0;
 form.querySelectorAll('.hd-split-amount').forEach(i=>{const v=parseFloat(i.value);if(!isNaN(v)&&v>0){sum+=v;n++;}});
 if(n<2){alert('A split needs at least two lines.');return false;}
 if(Math.abs(sum-total)>=0.005){alert('Lines total £'+sum.toFixed(2)+' but the bank amount is £'+total.toFixed(2)+'. Adjust the lines before saving.');return false;}
 return confirm('Save '+n+' allocation lines totalling £'+sum.toFixed(2)+'?');
}
document.addEventListener('input',function(e){if(e.target.classList.contains('hd-split-amount'))hdSplitTotals(e.target.closest('form'));});
document.addEventListener('DOMContentLoaded',function(){document.querySelectorAll('.hd-split-form').forEach(hdSplitTotals);});
</script>
<div class="hd-card"><h5>Receipts not yet linked to bank transactions (recent sample)</h5>
<p class="hd-small">These receipts may represent cash purchases, unpaid items, duplicates or bank movements outside the imported period. Review before creating or removing any accounting record.</p>
<div class="hd-scroll"><table class="hd-table"><thead><tr><th>Receipt</th><th>Supplier</th><th>Amount</th></tr></thead><tbody>
@forelse($unmatchedReceipts->take(80) as $r)<tr><td>#{{ $r->receipt_number }}</td><td>{{ $r->supplier }}</td><td>£{{ number_format((float)$r->detail->total_amount,2) }}</td></tr>
@empty<tr><td colspan="3">No unmatched receipts in the recent sample.</td></tr>@endforelse
</tbody></table></div></div>
@endif
</div>

{{-- Quick Add Head / Type modals (shared with the receipt page) --}}
@include('admin.receipt._quick_head_modal')
@include('admin.receipt._quick_type_modal')
<script>
// Bank-reconciliation wiring: each row/tab has its own account-head dropdown, so
// remember which one opened the modal and refresh only that one afterwards.
window.openQuickHeadFor = function(btn){
    var sel = btn.closest('label') ? btn.closest('label').querySelector('select[name="account_head_id"]') : null;
    if(!sel) sel = btn.closest('.hd-form-grid, .hd-tab-panel') ? btn.closest('.hd-form-grid, .hd-tab-panel').querySelector('select[name="account_head_id"]') : null;
    window.quickAddTargetEl = sel;
    window.openQuickHeadModal();
};
window.openQuickTypeFor = function(btn){
    var sel = btn.closest('label') ? btn.closest('label').querySelector('select[name="account_head_id"]') : null;
    if(!sel) sel = btn.closest('.hd-form-grid, .hd-tab-panel') ? btn.closest('.hd-form-grid, .hd-tab-panel').querySelector('select[name="account_head_id"]') : null;
    window.quickAddTargetEl = sel;
    window.openQuickTypeModal();
};
</script>
@endsection
@section('script')
<script>
$(function(){
 var presetCredentialId='{{ $presetId }}';
 var presetBusiness='{{ $businessId }}';
 var presetApplied=false;
 $('#bankFilterClient').select2({
  placeholder:"-- Choose a client --",allowClear:true,cache:true,
  ajax:{url:"{{ route('admin.bank-reconciliation.search-clients') }}",dataType:'json',delay:250,
   data:function(params){return {q:params.term};},
   processResults:function(data){return {results:data};},cache:true}
 }).on('change',function(){
  var val=$(this).val(); if(!val)return;
  if(presetApplied){presetApplied=false;return;}
  window.location.href="{{ route('admin.bank-reconciliation.index') }}?client_credential_id="+val;
 });
 if(presetCredentialId){
  $.get("{{ route('admin.bank-reconciliation.search-clients') }}",{q:''},function(data){
   var match=data.find(function(item){return item.id==presetCredentialId;});
   if(match){
    presetApplied=true;
    $('#bankFilterClient').append(new Option(match.text,match.id,true,true)).trigger('change');
    $('#ciName').text(match.text);
    $('#ciEmail').text(match.email||'N/A');
    $('#ciPhone').text(match.phone||'N/A');
   }
  });
  $.get("{{ route('admin.bank-reconciliation.businesses') }}",{client_credential_id:presetCredentialId},function(data){
   var opts='<option value="">All businesses</option>';
   $.each(data,function(i,c){opts+='<option value="'+c.id+'">'+c.name+'</option>';});
   $('#bankFilterBusiness').html(opts);
   if(presetBusiness)$('#bankFilterBusiness').val(presetBusiness);
  });
 }
});
</script>
@endsection
