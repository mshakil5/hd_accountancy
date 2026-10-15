@extends('admin.layouts.admin')
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
@media(max-width:850px){.hd-reconcile-row{grid-template-columns:1fr}.hd-bank-side{border-right:0;border-bottom:1px solid #dae4ef}.hd-form-grid{grid-template-columns:1fr}}
</style>
<div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
 <div><h3 class="mb-1">Bank Reconciliation</h3><p class="hd-small mb-0">Review bank transactions, match uploaded receipts and categorise exceptions. Financial journals are not created automatically.</p></div>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="hd-card">
<form method="GET" action="{{ route('admin.bank-reconciliation.index') }}" class="row align-items-end">
 <div class="col-md-6"><label>Business / client record</label>
 <select class="form-control" name="client_id">
 @foreach($clients as $client)<option value="{{ $client->id }}" {{ $selected==$client->id?'selected':'' }}>Business #{{ $client->id }} — Account #{{ $client->client_credential_id }}</option>@endforeach
 </select></div>
 <div class="col-md-3"><label>Status</label><select class="form-control" name="status"><option value="">All</option>
 @foreach(['unmatched','needs_review','matched','split','categorized'] as $s)<option value="{{ $s }}" {{ request('status')===$s?'selected':'' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>@endforeach
 </select></div>
 <div class="col-md-3"><button class="btn btn-primary">Filter transactions</button></div>
</form>
</div>
<div class="row">
@foreach(['unmatched','needs_review','matched','split','categorized'] as $status)
<div class="col-md-3 col-sm-6"><div class="hd-card"><div class="hd-small">{{ ucwords(str_replace('_',' ',$status)) }}</div><h3>{{ (int)($stats[$status]??0) }}</h3></div></div>
@endforeach
</div>
<div class="hd-card">
<h5>Imported statements</h5>
<div class="hd-scroll"><table class="hd-table"><thead><tr><th>Uploaded</th><th>File</th><th>Type</th><th>Processing</th><th>Imported rows</th><th></th></tr></thead><tbody>
@forelse($imports as $imp)
<tr><td>{{ $imp->created_at }}</td><td>{{ $imp->filename }}</td><td>{{ strtoupper($imp->file_type??'csv') }}</td><td>{{ str_replace('_',' ', $imp->processing_status??'parsed') }}</td><td>{{ $imp->transaction_count }}</td>
<td>@if($imp->storage_path)<a href="{{ route('admin.bank-reconciliation.statement',$imp->id) }}">View original statement</a>@endif</td></tr>
@empty <tr><td colspan="6">No statements imported yet. Clients can upload from the mobile app.</td></tr>@endforelse
</tbody></table></div>
</div>
<div class="hd-card hd-workspace">
<div class="d-flex justify-content-between align-items-center flex-wrap"><div><h4>Reconcile bank transactions</h4><p class="hd-small">Bank details on the left; accountant actions and uploaded receipts on the right. Matching here does not yet post to financial reports.</p></div><span class="hd-small">{{ $entries->total() }} bank transactions in this view</span></div>
@forelse($entries as $entry)
@php
$exact = $receipts->filter(fn($r)=>abs((float)($r->detail->total_amount??0)-abs((float)$entry->amount))<0.01)->take(20);
$locked = in_array($entry->status,['matched','split']);
$pending = $entry->status==='needs_review';
@endphp
<div class="hd-reconcile-row" id="bank-entry-{{ $entry->id }}">
 <div class="hd-bank-side">
  <div class="d-flex justify-content-between"><span class="hd-small">{{ $entry->transaction_date }}</span><span class="hd-pill">{{ ucwords(str_replace('_',' ',$entry->status)) }}</span></div>
  <div class="hd-bank-description">{{ $entry->description }}</div>
  @if($entry->reference)<div class="hd-small">Reference: {{ $entry->reference }}</div>@endif
  <div class="hd-amounts"><div><span class="hd-small">Spent</span><strong>{{ (float)$entry->amount<0?'£'.number_format(abs((float)$entry->amount),2):'—' }}</strong></div><div><span class="hd-small">Received</span><strong>{{ (float)$entry->amount>=0?'£'.number_format((float)$entry->amount,2):'—' }}</strong></div></div>
  <details><summary class="hd-small">More details</summary><div class="hd-small">Bank transaction #{{ $entry->id }} @if($entry->receipt_id) · Linked receipt #{{ $entry->receipt_id }}@endif</div></details>
 </div>
 <div class="hd-action-side">
  @if($pending)
    <div class="alert alert-warning py-2">PDF row requires verification before it can be reconciled.</div>
    <form method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}" class="hd-inline-form">@csrf
     <input type="hidden" name="client_id" value="{{ $selected }}"><input type="hidden" name="action" value="verify">
     <label>Date<input class="form-control" type="date" name="transaction_date" value="{{ $entry->transaction_date }}" required></label>
     <label>Signed amount (£)<input class="form-control" type="number" step="0.01" name="amount" value="{{ $entry->amount }}" required></label>
     <label>Description<input class="form-control" name="description" value="{{ $entry->description }}" required></label>
     <button class="btn btn-warning btn-sm" onclick="return confirm('Checked this row against the original PDF?')">Verify</button>
    </form>
  @elseif($locked)
   <div class="alert alert-success py-2">{{ $entry->status==='split'?'Split saved':'Receipt matched' }}. No financial posting triggered.</div>
   @if($entry->status==='split')<div class="hd-small">Allocation lines: {{ ($splits[$entry->id]??collect())->count() }}</div>@endif
   <form method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}">@csrf<input type="hidden" name="client_id" value="{{ $selected }}"><input type="hidden" name="action" value="unmatch"><button class="btn btn-outline-secondary btn-sm" onclick="return confirm('Undo reconciliation?')">Undo reconciliation</button></form>
  @else
  <div class="hd-row-tabs" role="tablist" aria-label="Reconciliation actions for transaction {{ $entry->id }}">
   <button type="button" class="hd-tab active" data-hd-tab="match" aria-selected="true">Match</button>
   <button type="button" class="hd-tab" data-hd-tab="create" aria-selected="false">Create</button>
   <button type="button" class="hd-tab" data-hd-tab="split" aria-selected="false">Split</button>
   <button type="button" class="hd-tab" data-hd-tab="discuss" aria-selected="false">Discuss</button>
   <button type="button" class="hd-tab" data-hd-tab="find" aria-selected="false">Find &amp; Match</button>
   <button type="button" class="hd-tab" data-hd-tab="transfer" aria-selected="false">Transfer</button>
  </div>
  <section class="hd-tab-panel" data-hd-panel="match">
   <p class="hd-small">Suggested uploaded receipts with exactly matching amounts. Confirm the supplier and evidence before matching.</p>
   <form method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}" class="hd-form-grid">@csrf
    <input type="hidden" name="client_id" value="{{ $selected }}"><input type="hidden" name="action" value="match">
    <label>Who / receipt<select class="form-control" name="receipt_id" required><option value="">{{ $exact->isEmpty()?'No exact receipt suggestions':'Select suggested receipt' }}</option>@foreach($exact as $r)<option value="{{ $r->id }}">#{{ $r->receipt_number }} · {{ $r->supplier?:'Unknown supplier' }} · £{{ number_format((float)$r->detail->total_amount,2) }}</option>@endforeach</select></label>
    <label>What / account head<select class="form-control" name="account_head_id"><option value="">Use receipt account head</option>@foreach($heads as $h)<option value="{{ $h->id }}">{{ $h->code }} — {{ $h->name }}</option>@endforeach</select></label>
    <button class="btn btn-primary btn-sm" {{ $exact->isEmpty()?'disabled':'' }} onclick="return confirm('Approve exact receipt match?')">OK · Match</button>
   </form>
  </section>
  <section class="hd-tab-panel" data-hd-panel="create" hidden>
   <p class="hd-small">Categorise a bank movement without a linked receipt. This is a reconciliation classification, not a new P&amp;L entry.</p>
   <form method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}" class="hd-form-grid">@csrf
    <input type="hidden" name="client_id" value="{{ $selected }}"><input type="hidden" name="action" value="categorize">
    <label>What / account head<select class="form-control" name="account_head_id" required><option value="">Choose account head</option>@foreach($heads as $h)<option value="{{ $h->id }}" {{ $entry->account_head_id==$h->id?'selected':'' }}>{{ $h->code }} — {{ $h->name }}</option>@endforeach</select></label>
    <label>Why / description<input class="form-control" type="text" name="review_note" placeholder="Reason for classification"></label>
    <button class="btn btn-primary btn-sm">Save classification</button>
   </form>
  </section>
  <section class="hd-tab-panel" data-hd-panel="split" hidden>
   <p class="hd-small">Allocate one bank payment to two account heads; both allocations must total £{{ number_format(abs((float)$entry->amount),2) }}. No automatic financial posting.</p>
   <form method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}">@csrf
    <input type="hidden" name="client_id" value="{{ $selected }}"><input type="hidden" name="action" value="split">
    @foreach([0,1] as $i)<div class="hd-form-grid mb-2"><label>Line {{ $i+1 }} amount (£)<input class="form-control" type="number" name="allocations[{{ $i }}][amount]" min="0.01" step="0.01" required></label>
     <label>Account head<select class="form-control" name="allocations[{{ $i }}][account_head_id]" required><option value="">Choose account</option>@foreach($heads as $h)<option value="{{ $h->id }}">{{ $h->code }} — {{ $h->name }}</option>@endforeach</select></label>
     <label>Explanation<input class="form-control" name="allocations[{{ $i }}][memo]" placeholder="e.g. outstanding payment"></label></div>@endforeach
    <button class="btn btn-primary btn-sm" onclick="return confirm('Confirm split totals and save allocations?')">Save split</button>
   </form>
  </section>
  <section class="hd-tab-panel" data-hd-panel="discuss" hidden>
   <p class="hd-small">Add an internal accountant note or create a missing-receipt request. A request record does not guarantee a push notification is sent.</p>
   <form method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}" class="mb-2">@csrf<input type="hidden" name="client_id" value="{{ $selected }}"><input type="hidden" name="action" value="add_note"><label>Internal note<input class="form-control" name="review_note" required maxlength="2000" placeholder="Clarification for accountant"></label><button class="btn btn-outline-primary btn-sm mt-2">Save note</button></form>
   <form method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}">@csrf<input type="hidden" name="client_id" value="{{ $selected }}"><input type="hidden" name="action" value="request_receipt"><label>Message to client<input class="form-control" name="review_note" placeholder="Please upload supporting receipt"></label><button class="btn btn-outline-warning btn-sm mt-2">Request receipt</button></form>
  </section>
  <section class="hd-tab-panel" data-hd-panel="find" hidden>
   <p class="hd-small">Search among the most recent 400 uploaded receipts. Only exact-amount receipts can be confirmed; partial/multiple matches are not yet supported.</p>
   <input type="search" class="form-control mb-2 hd-receipt-search" placeholder="Search receipt number or supplier" aria-label="Search receipts">
   <form method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}" class="hd-form-grid">@csrf<input type="hidden" name="client_id" value="{{ $selected }}"><input type="hidden" name="action" value="match">
    <label>Uploaded receipt<select class="form-control hd-receipt-select" name="receipt_id" required><option value="">Choose receipt</option>@foreach($receipts as $r)<option value="{{ $r->id }}" data-search="{{ strtolower(($r->receipt_number??'').' '.($r->supplier??'')) }}">#{{ $r->receipt_number }} · {{ $r->supplier?:'Unknown supplier' }} · £{{ number_format((float)$r->detail->total_amount,2) }}</option>@endforeach</select></label>
    <label>Account head<select class="form-control" name="account_head_id"><option value="">Use receipt account head</option>@foreach($heads as $h)<option value="{{ $h->id }}">{{ $h->code }} — {{ $h->name }}</option>@endforeach</select></label>
    <button class="btn btn-primary btn-sm" onclick="return confirm('Confirm this selected receipt matches the exact bank amount?')">Match selected receipt</button>
   </form>
  </section>
  <section class="hd-tab-panel" data-hd-panel="transfer" hidden><div class="alert alert-info mb-0">Internal bank transfer reconciliation is not yet implemented. Do not categorise a transfer as income or an expense. Leave it unmatched for review.</div></section>
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
});
document.addEventListener('input',function(e){
 if(!e.target.classList.contains('hd-receipt-search'))return;
 const select=e.target.closest('.hd-tab-panel').querySelector('.hd-receipt-select');
 const term=e.target.value.toLowerCase().trim();
 Array.from(select.options).forEach((option,index)=>{if(index===0)return;option.hidden=!!term && !(option.dataset.search||'').includes(term)});
 if(select.selectedOptions[0]?.hidden) select.value='';
});
</script>
<div class="hd-card"><h5>Receipts not yet linked to bank transactions (recent sample)</h5>
<p class="hd-small">These receipts may represent cash purchases, unpaid items, duplicates or bank movements outside the imported period. Review before creating or removing any accounting record.</p>
<div class="hd-scroll"><table class="hd-table"><thead><tr><th>Receipt</th><th>Supplier</th><th>Amount</th></tr></thead><tbody>
@forelse($unmatchedReceipts->take(80) as $r)<tr><td>#{{ $r->receipt_number }}</td><td>{{ $r->supplier }}</td><td>£{{ number_format((float)$r->detail->total_amount,2) }}</td></tr>
@empty<tr><td colspan="3">No unmatched receipts in the recent sample.</td></tr>@endforelse
</tbody></table></div></div>
</div>
@endsection
