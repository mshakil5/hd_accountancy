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
 @foreach(['unmatched','needs_review','matched','categorized'] as $s)<option value="{{ $s }}" {{ request('status')===$s?'selected':'' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>@endforeach
 </select></div>
 <div class="col-md-3"><button class="btn btn-primary">Filter transactions</button></div>
</form>
</div>
<div class="row">
@foreach(['unmatched','needs_review','matched','categorized'] as $status)
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
<div class="hd-card">
<h5>Bank transactions & receipt comparison</h5>
<p class="hd-small">For PDF statements, verify every extracted amount and payment direction against the original before matching. Select a receipt and account head to approve a match. Only exact amount matches are accepted here.</p>
<div class="hd-scroll">
<table class="hd-table">
<thead><tr><th>Date</th><th>Bank description</th><th>Bank amount</th><th>Status</th><th>Account head / receipt comparison and actions</th></tr></thead><tbody>
@forelse($entries as $entry)
<tr>
<td>{{ $entry->transaction_date }}</td>
<td style="min-width:165px">{{ $entry->description }} @if($entry->reference)<div class="hd-small">{{ $entry->reference }}</div>@endif</td>
<td><strong>£{{ number_format(abs((float)$entry->amount),2) }}</strong><div class="hd-small">{{ $entry->amount<0?'Money out':'Money in' }}</div></td>
<td class="hd-status">{{ ucwords(str_replace('_',' ',$entry->status)) }} @if($entry->receipt_id)<div class="hd-small">Receipt #{{ $entry->receipt_id }}</div>@endif</td>
<td style="min-width:410px">
@if($entry->status==='needs_review')
<form class="hd-actions" method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}">
@csrf<input type="hidden" name="client_id" value="{{ $selected }}"><input type="hidden" name="action" value="verify">
<input type="date" class="form-control" name="transaction_date" value="{{ $entry->transaction_date }}" required>
<input type="number" step="0.01" class="form-control" name="amount" value="{{ $entry->amount }}" required>
<input type="text" class="form-control" name="description" value="{{ $entry->description }}" required>
<button class="btn btn-warning btn-sm" onclick="return confirm('Have you checked this transaction against the original PDF?')">Verify PDF row</button>
</form>
@elseif($entry->status==='matched')
<form method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}">
@csrf<input type="hidden" name="client_id" value="{{ $selected }}"><input type="hidden" name="action" value="unmatch">
<button class="btn btn-outline-secondary btn-sm" onclick="return confirm('Reverse this match?')">Undo match</button>
</form>
@else
@php
 $candidates=$receipts->filter(fn($r)=>abs((float)($r->detail->total_amount??0)-abs((float)$entry->amount))<0.01)->take(20);
@endphp
<form class="hd-actions mb-2" method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}">
@csrf<input type="hidden" name="client_id" value="{{ $selected }}"><input type="hidden" name="action" value="match">
<select class="form-control" name="receipt_id" required><option value="">Select matching receipt</option>
@foreach($candidates as $r)<option value="{{ $r->id }}">#{{ $r->receipt_number }} · {{ $r->supplier?:'Unknown supplier' }} · £{{ number_format((float)$r->detail->total_amount,2) }}</option>@endforeach
</select>
<select class="form-control" name="account_head_id"><option value="">Receipt account head</option>
@foreach($heads as $h)<option value="{{ $h->id }}">{{ $h->code }} — {{ $h->name }}</option>@endforeach</select>
<button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Confirm this receipt really matches this bank transaction?')">Confirm match</button>
</form>
<form class="hd-actions" method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}">
@csrf<input type="hidden" name="client_id" value="{{ $selected }}"><input type="hidden" name="action" value="categorize">
<select class="form-control" name="account_head_id" required><option value="">Account head (without receipt)</option>
@foreach($heads as $h)<option value="{{ $h->id }}" {{ $entry->account_head_id==$h->id?'selected':'' }}>{{ $h->code }} — {{ $h->name }}</option>@endforeach</select>
<input type="text" name="review_note" class="form-control" placeholder="Reason / missing receipt note">
<button class="btn btn-outline-primary btn-sm">Categorise only</button>
</form>
@endif
@if($entry->status!=='matched')
<form class="hd-actions mt-2" method="POST" action="{{ route('admin.bank-reconciliation.action',$entry->id) }}">
@csrf<input type="hidden" name="client_id" value="{{ $selected }}"><input type="hidden" name="action" value="request_receipt">
<input type="text" class="form-control" name="review_note" placeholder="Message to client">
<button class="btn btn-outline-warning btn-sm">Request missing receipt</button>
</form>
@endif
</td>
</tr>
@empty<tr><td colspan="5">No bank transactions for this client or filter.</td></tr>@endforelse
</tbody></table></div>
<div class="mt-3">{{ $entries->links() }}</div>
</div>
</div>
@endsection
