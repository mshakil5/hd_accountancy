@extends('admin.layouts.admin')
@section('content')
<div class="container-fluid py-4">
<style>
.hd-card{background:#fff;border:1px solid #e5eaf1;border-radius:12px;padding:18px;margin-bottom:16px}
.hd-small{color:#64748b;font-size:12px}
.hd-table{width:100%;font-size:13px}
.hd-table th,.hd-table td{border-bottom:1px solid #e7ebf1;padding:10px;vertical-align:middle}
.hd-table th{background:#f8fafc;font-weight:600;color:#334155}
.hd-scroll{max-height:550px;overflow-y:auto}
.hd-pill{display:inline-block;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600}
.select2-container .select2-selection--single{height:38px!important;border:1px solid #ced4da!important;border-radius:.25rem!important}
.select2-container--default .select2-selection--single .select2-selection__rendered{line-height:36px!important}
.select2-container--default .select2-selection--single .select2-selection__arrow{height:36px!important}
.hd-bank-overview{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px}
.hd-bank-overview-card{display:block;border:1px solid #d9e4ee;border-radius:12px;padding:16px;background:#fff;color:#233247;text-decoration:none!important;transition:border-color .15s,box-shadow .15s}
.hd-bank-overview-card:hover,.hd-bank-overview-card:focus{border-color:#0284c7;box-shadow:0 2px 9px rgba(2,132,199,.10);color:#233247}
.hd-bank-overview-card.is-selected{border:2px solid #0284c7;padding:15px}
.hd-stat-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:14px;display:flex;flex-direction:column;justify-content:center}
.hd-stat-card strong{font-size:22px;line-height:1.2;margin-top:4px}
</style>

@php
  $presetId = $credentialId ?? request('client_credential_id') ?? request('credential_id');
@endphp

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
  <div>
    <h3 class="mb-1"><i class="bi bi-file-earmark-spreadsheet mr-2 text-primary"></i>Bank Statements</h3>
    <p class="hd-small mb-0">Upload bank statement PDFs, review OCR-extracted transactions, check statement balances, and verify lines before transaction matching.</p>
  </div>
  @if($presetId)
  <div>
    <a href="{{ route('admin.bank-reconciliation.index', array_filter(['client_credential_id'=>$presetId, 'client_id'=>$businessId, 'bank_account_id'=>$bankAccountId])) }}" class="btn btn-outline-primary btn-sm">
      <i class="bi bi-bank mr-1"></i> Go to Bank Reconciliation &rarr;
    </a>
  </div>
  @endif
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if(isset($errors) && $errors->any())
  <div class="alert alert-danger">
    @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
  </div>
@endif

{{-- Client selector always visible --}}
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
      <div class="d-flex align-items-center" style="margin-top:24px; gap:18px; background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding:12px 20px; border-radius:12px; color:#fff;">
        <span class="font-weight-bold" style="font-size:18px;" id="ciName"></span>
        <span style="opacity:0.9; font-size:14px;"><i class="bi bi-envelope mr-1"></i><span id="ciEmail"></span></span>
        <span style="opacity:0.9; font-size:14px;"><i class="bi bi-telephone mr-1"></i><span id="ciPhone"></span></span>
        <a href="{{ route('admin.bank-statements.index') }}" class="btn btn-sm btn-light ml-2" style="color:#667eea; font-weight:600;">
          Change
        </a>
      </div>
    </div>
    @endif
  </div>
</div>

@if(!$presetId)
  <div class="hd-card"><div class="alert alert-info mb-0"><strong>Select a client to begin.</strong> Choose a client credential above to view or upload their bank statements.</div></div>
@else

  {{-- Business & Filter toolbar --}}
  <div class="hd-card">
    <form method="GET" action="{{ route('admin.bank-statements.index') }}" class="row align-items-end g-2">
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
            <option value="{{ $bank->id }}" {{ (int)$bankAccountId===(int)$bank->id?'selected':'' }}>
              {{ $bank->bank_name }}{{ $bank->account_last_four?' ••'.$bank->account_last_four:'' }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <label class="hd-small font-weight-bold">Verification Status</label>
        <select class="form-control select2" name="verification_status" style="width:100%">
          <option value="">All statuses</option>
          <option value="verified" {{ request('verification_status')==='verified'?'selected':'' }}>Verified</option>
          <option value="needs_accountant_review" {{ request('verification_status')==='needs_accountant_review'?'selected':'' }}>Needs Accountant Review</option>
          <option value="ready_for_reconciliation" {{ request('verification_status')==='ready_for_reconciliation'?'selected':'' }}>Ready For Reconciliation</option>
          <option value="pending" {{ request('verification_status')==='pending'?'selected':'' }}>Pending Checks</option>
          <option value="extraction_failed" {{ request('verification_status')==='extraction_failed'?'selected':'' }}>Extraction Failed</option>
        </select>
      </div>
      <div class="col-md-3">
        <button class="btn btn-primary btn-block"><i class="bi bi-funnel"></i> Apply Filter</button>
      </div>
    </form>
  </div>



  {{-- Metrics Counters --}}
  <div class="row g-2 mb-3">
    <div class="col-6 col-md-2">
      <div class="hd-stat-card">
        <div class="hd-small text-muted">Total Statements</div>
        <strong>{{ (int)($importStats['total'] ?? 0) }}</strong>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="hd-stat-card" style="border-left:4px solid #16a34a;">
        <div class="hd-small text-success">Verified</div>
        <strong class="text-success">{{ (int)($importStats['verified'] ?? 0) }}</strong>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="hd-stat-card" style="border-left:4px solid #f59e0b;">
        <div class="hd-small text-warning">Needs Review</div>
        <strong class="text-warning">{{ (int)($importStats['needs_review'] ?? 0) }}</strong>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="hd-stat-card" style="border-left:4px solid #0284c7;">
        <div class="hd-small text-info">Ready For Reconciliation</div>
        <strong class="text-info">{{ (int)($importStats['ready'] ?? 0) }}</strong>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="hd-stat-card" style="border-left:4px solid #ef4444;">
        <div class="hd-small text-danger">Failed / Pending</div>
        <strong class="text-danger">{{ (int)(($importStats['failed'] ?? 0) + ($importStats['pending'] ?? 0)) }}</strong>
      </div>
    </div>
  </div>

  {{-- Statements Table --}}
  <div class="hd-card">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
      <h5 class="mb-0">Uploaded Bank Statements</h5>
      @if($businessId)
      <div class="d-flex gap-2">
        <form method="POST" action="{{ route('admin.bank-statements.verify-all-rows-for-client') }}" onsubmit="return confirm('Verify all unverified rows across all statements for this business?')">
          @csrf
          <input type="hidden" name="client_id" value="{{ $businessId }}">
          <button class="btn btn-outline-success btn-sm"><i class="bi bi-check-all"></i> Verify all rows for client</button>
        </form>
      </div>
      @endif
    </div>

    <div class="hd-scroll">
      <table class="hd-table">
        <thead>
          <tr>
            <th>Uploaded</th>
            <th>File</th>
            <th>Bank Account</th>
            <th>Format</th>
            <th>Extracted Rows</th>
            <th>Balance Check</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($imports as $imp)
            @php
              $vs = $imp->verification_status ?? 'pending';
              $vc = match($vs) {
                'verified' => 'success',
                'ready_for_reconciliation' => 'info',
                'needs_accountant_review' => 'warning',
                'extraction_failed' => 'danger',
                default => 'secondary'
              };
              $bank = $bankAccounts->firstWhere('id', $imp->bank_account_id);
            @endphp
            <tr>
              <td>
                <div class="font-weight-bold">{{ \Carbon\Carbon::parse($imp->created_at)->format('d M Y') }}</div>
                <div class="hd-small text-muted">{{ \Carbon\Carbon::parse($imp->created_at)->format('H:i') }}</div>
              </td>
              <td>
                <div class="font-weight-bold text-truncate" style="max-width:220px;" title="{{ $imp->filename }}">
                  <i class="bi bi-file-earmark-pdf text-danger mr-1"></i>{{ $imp->filename }}
                </div>
                <div class="hd-small text-muted">{{ (int)$imp->pages_total }} page(s)</div>
              </td>
              <td>
                @if($bank)
                  <strong>{{ $bank->bank_name }}</strong>
                  @if($bank->account_last_four)<div class="hd-small text-muted">•••• {{ $bank->account_last_four }}</div>@endif
                @else
                  <span class="text-muted">Unassigned</span>
                @endif
              </td>
              <td><span class="badge bg-light text-dark border">{{ strtoupper($imp->file_type ?? 'PDF') }}</span></td>
              <td><strong>{{ (int)$imp->transaction_count }}</strong></td>
              <td>
                @if($imp->opening_balance !== null || $imp->closing_balance !== null)
                  <div class="hd-small">Opening: <strong>£{{ number_format((float)$imp->opening_balance, 2) }}</strong></div>
                  <div class="hd-small">Closing: <strong>£{{ number_format((float)$imp->closing_balance, 2) }}</strong></div>
                  @if($imp->balance_difference !== null)
                    <div class="hd-small" style="color:{{ (float)$imp->balance_difference != 0 ? '#b91c1c' : '#15803d' }}">
                      Diff: £{{ number_format((float)$imp->balance_difference, 2) }}
                    </div>
                  @endif
                @else
                  <span class="hd-small text-muted">—</span>
                @endif
              </td>
              <td>
                <span class="badge bg-{{ $vc }} px-2 py-1">
                  {{ ucwords(str_replace('_', ' ', $vs)) }}
                </span>
                @if($imp->verified_at)
                  <div class="hd-small text-success mt-1"><i class="bi bi-check2"></i> Verified</div>
                @endif
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <a href="{{ route('admin.bank-statements.verification', $imp->id) }}" class="btn btn-primary" title="Open PDF verification workspace">
                    <i class="bi bi-layout-split"></i> Verify Rows
                  </a>
                  @if($imp->storage_path)
                    <a href="{{ route('admin.bank-statements.statement', $imp->id) }}" class="btn btn-outline-secondary" title="Download original file">
                      <i class="bi bi-download"></i>
                    </a>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center text-muted py-4">
                No bank statements found for the selected client / business.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if(method_exists($imports, 'links'))
      <div class="mt-3">{{ $imports->links('vendor.pagination.bootstrap-5') }}</div>
    @endif
  </div>

@endif
</div>
@endsection

@section('script')
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
      dataType: 'json',
      delay: 250,
      data: function (params) { return { q: params.term }; },
      processResults: function (data) { return { results: data }; },
      cache: true
    }
  }).on('change', function () {
    var val = $(this).val();
    if (!val) return;
    if (presetApplied) { presetApplied = false; return; }
    window.location.href = "{{ route('admin.bank-statements.index') }}?client_credential_id=" + val;
  });

  if (presetCredentialId) {
    $.get("{{ url('/admin/receipts/search-clients') }}", { q: '' }, function (data) {
      var match = data.find(function (item) { return item.id == presetCredentialId; });
      if (match) {
        presetApplied = true;
        $('#filterClient').append(new Option(match.text, match.id, true, true)).trigger('change');
        $('#ciName').text(match.text);
        $('#ciEmail').text(match.email || 'N/A');
        $('#ciPhone').text(match.phone || 'N/A');
      }
    });

    $.get("{{ url('/admin/receipts/get-clients-by-credential') }}", { client_credential_id: presetCredentialId }, function (data) {
      var opts = '<option value="">All businesses</option>';
      $.each(data, function (i, c) {
        var sel = String(c.id) === String(presetBusiness) ? ' selected' : '';
        opts += '<option value="' + c.id + '"' + sel + '>' + c.name + '</option>';
      });
      $('#filterBusiness').html(opts).trigger('change.select2');
    });
  }
});
</script>
@endsection