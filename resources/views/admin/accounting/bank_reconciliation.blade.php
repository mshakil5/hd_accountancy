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
.hd-stat--filter{font:inherit;cursor:pointer;background:#fff;transition:border-color .15s,box-shadow .15s}
.hd-stat--filter:hover{border-color:#93c5fd}
.hd-stat--filter.active{border-color:#0284c7;box-shadow:0 0 0 2px #bae6fd}
.hd-stat--filter .hd-stat__label,.hd-stat--filter .hd-stat__val{display:block}
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
.hd-match-item:hover{border-color:#93c5fd;background:#f1f9ff}
.hd-match-item.selected{border-color:#0284c7;background:#eff8ff}
.hd-match-item input[type=radio]{accent-color:#0284c7}
.hd-match-meta{font-size:12px;color:#64748b}
.hd-match-amount{font-size:14px;font-weight:700;white-space:nowrap}
/* ── Split ── */
.hd-split-line{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;margin-bottom:8px}
.hd-split-meter{font-size:12px;font-weight:600;padding:4px 10px;border-radius:20px;background:#f1f5f9}
/* ── VAT block (gross in, net derived) — shared by Create / Split / Find & Match ── */
.hd-vat-box{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;align-items:start;background:#f8fbff;border:1px solid #e2e8f0;border-radius:8px;padding:10px;grid-column:1/-1}
@media(max-width:900px){.hd-vat-box{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:560px){.hd-vat-box{grid-template-columns:1fr}}
.hd-vat-box input[readonly]{background:#eef4f9;font-weight:700;color:#0f172a}
.hd-vat-net{font-variant-numeric:tabular-nums}
.hd-vat-amt-hint{margin-top:2px;font-variant-numeric:tabular-nums}
.hd-vat-amt-hint .hd-vat-amt{font-weight:700;color:#0f172a}
/* ── Find & match ── */
.hd-find-item{display:flex;align-items:center;gap:10px;padding:7px 10px;border:1px solid #e2e8f0;border-radius:7px;margin-bottom:5px;font-size:13px;background:#fff;cursor:pointer}
.hd-find-item:hover{background:#f1f9ff}
.hd-find-item.capped{opacity:.45;pointer-events:none}
.hd-find-apply{width:96px;height:32px;font-size:12.5px;margin-left:auto;padding:2px 6px}
.hd-find-counter{font-size:12px;font-weight:700;padding:5px 12px;border-radius:20px;background:#f1f5f9;margin-bottom:10px}
.hd-create-block{border-top:1px dashed #cbd5e1;margin-top:12px;padding-top:10px}
.hd-create-line{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;margin-bottom:8px}
.hd-create-meter{font-size:12px;font-weight:600;padding:4px 10px;border-radius:20px;background:#f1f5f9}
/* ── Receipt details popup ── */
.hd-receipt-eye{font-size:12px;color:#94a3b8;margin-left:6px}
.hd-receipt-modal{position:fixed;inset:0;background:rgba(15,23,42,.55);display:none;align-items:center;justify-content:center;padding:16px;z-index:1090}
.hd-receipt-modal.open{display:flex}
.hd-receipt-dialog{background:#fff;border-radius:12px;width:100%;max-width:560px;max-height:86vh;display:flex;flex-direction:column;box-shadow:0 24px 60px rgba(15,23,42,.35)}
.hd-receipt-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:14px 18px;border-bottom:1px solid #e5eaf1}
.hd-receipt-close{border:0;background:transparent;font-size:24px;line-height:1;color:#94a3b8;cursor:pointer;padding:0 4px}
.hd-receipt-close:hover{color:#334155}
.hd-receipt-body{padding:14px 18px;overflow-y:auto}
.hd-receipt-amount{font-size:24px;font-weight:800;color:#15803d}
.hd-receipt-sub{font-size:13px;color:#475569;font-weight:600;margin-bottom:10px}
.hd-receipt-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:6px 16px;font-size:12.5px}
.hd-receipt-grid .hd-details-row{margin-bottom:0}
.hd-receipt-grid .hd-details-row span:last-child{font-weight:600;text-align:right;overflow-wrap:anywhere}
.hd-receipt-desc{margin-top:10px;font-size:12.5px;color:#334155;background:#f8fafc;border:1px solid #e5eaf1;border-radius:8px;padding:8px 10px;white-space:pre-wrap}
.hd-receipt-files{margin-top:12px;font-size:12.5px}
.hd-receipt-file{display:inline-flex;align-items:center;gap:4px;margin:0 8px 6px 0;padding:4px 10px;border:1px solid #dbe4ee;border-radius:20px;color:#0369a1;text-decoration:none;background:#f8fafc}
.hd-receipt-file:hover{background:#eff8ff;text-decoration:none}
.hd-receipt-foot{display:flex;justify-content:flex-end;gap:8px;padding:12px 18px;border-top:1px solid #e5eaf1}
@media(max-width:520px){.hd-receipt-grid{grid-template-columns:1fr}}
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
  <form method="GET" action="{{ route('admin.bank-reconciliation.index') }}" class="row align-items-end g-2" id="hd-filter-form">
    <input type="hidden" name="client_credential_id" value="{{ $presetId }}">
    <input type="hidden" name="reconcile_status" value="{{ $reconcileFilter ?? 'unreconciled' }}">
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
          <option value="{{ $bank->id }}" data-client-id="{{ $bank->client_id }}" {{ (int)$bankAccountId===(int)$bank->id?'selected':'' }}>{{ $bank->bank_name }}{{ $bank->account_last_four?' ••'.$bank->account_last_four:'' }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2">
      <label class="hd-small font-weight-bold">From</label>
      <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}" />
    </div>
    <div class="col-md-2">
      <label class="hd-small font-weight-bold">To</label>
      <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}" />
    </div>
    <div class="col-md-2 d-flex align-items-end gap-2" style="padding-top:18px">
      <button type="submit" class="btn btn-primary">Filter</button>
      @if(request()->hasAny(['client_id','bank_account_id','reconcile_status','date_from','date_to']) && request('reconcile_status') !== 'unreconciled')
        <a href="{{ route('admin.bank-reconciliation.index', ['client_credential_id'=>$presetId]) }}" class="btn btn-outline-secondary btn-sm">Reset</a>
      @endif
    </div>
  </form>
</div>

<input type="hidden" id="client_credential_id" value="{{ $credentialId ?? $presetId }}">

{{-- ── Status cards: the count IS the filter (kept live by JS) ── --}}
<div class="hd-stat-bar" id="hd-status-cards">
  @foreach(['unreconciled' => ['Unreconciled', '#b91c1c', $unreconciledCount], 'reconciled' => ['Reconciled', '#15803d', $reconciledCount], 'all' => ['Total', '#0369a1', $totalCount]] as $val => $card)
  @php $isActive = ($reconcileFilter ?? 'unreconciled') === $val; @endphp
  <button type="button" class="hd-stat hd-stat--filter{{ $isActive ? ' active' : '' }}" data-hd-status="{{ $val }}" aria-pressed="{{ $isActive ? 'true' : 'false' }}">
    <span class="hd-stat__label">{{ $card[0] }}</span>
    <span class="hd-stat__val" style="color:{{ $card[1] }}" data-hd-count="{{ $val }}">{{ $card[2] }}</span>
  </button>
  @endforeach
</div>

{{-- ── Workspace: server-rendered fragments, swapped in place by JS ── --}}
<div class="hd-card hd-workspace" id="hd-reconciliation-workspace"
     data-rows-url="{{ route('admin.bank-reconciliation.rows') }}"
     data-reconcile-status="{{ $reconcileFilter ?? 'unreconciled' }}">
  <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
    <h5 class="mb-0">Bank Transactions <span class="badge badge-secondary" id="hd-rows-total" style="font-size:13px">{{ $entries->total() }}</span></h5>
    <span class="hd-small" id="hd-rows-range"></span>
  </div>
  <div id="hd-rows">
    @include('admin.accounting.partials.reconcile_rows')
  </div>
  <div class="mt-3" id="hd-pagination"></div>
</div>

@endif {{-- presetId --}}
</div>

{{-- Receipt details popup: opened by clicking a receipt row in Match / Find & Match --}}
<div class="hd-receipt-modal" id="hd-receipt-modal" role="dialog" aria-modal="true" aria-labelledby="hd-receipt-modal-title" hidden>
  <div class="hd-receipt-dialog">
    <div class="hd-receipt-head">
      <div>
        <div class="hd-small" style="font-weight:700;text-transform:uppercase;letter-spacing:.4px">Receipt details</div>
        <div id="hd-receipt-modal-title" style="font-size:16px;font-weight:800">Receipt</div>
      </div>
      <button type="button" class="hd-receipt-close" data-hd-receipt-close aria-label="Close">&times;</button>
    </div>
    <div class="hd-receipt-body" id="hd-receipt-modal-body"></div>
    <div class="hd-receipt-foot">
      <button type="button" class="btn btn-outline-secondary btn-sm" data-hd-receipt-close>Close</button>
      <button type="button" class="btn btn-primary btn-sm" id="hd-receipt-select" hidden>
        <i class="fa fa-check mr-1"></i> Select this receipt
      </button>
    </div>
  </div>
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

// ── VAT: gross + VAT % → net (the same rule everywhere a receipt is created) ──
function hdVatCalc(gross, pct){
  const gPence = Math.round((parseFloat(gross) || 0) * 100);
  const p = Math.min(100, Math.max(0, parseFloat(pct) || 0));
  const netPence = Math.round(gPence / (1 + p / 100));
  return { gross: gPence / 100, net: netPence / 100, vat: (gPence - netPence) / 100 };
}
function hdVatUpdate(box){
  if(!box) return;
  const grossEl = box.querySelector('.hd-vat-gross');
  const gross = grossEl ? grossEl.value : (box.dataset.gross || 0);
  const pctEl = box.querySelector('.hd-vat-pct');
  const r = hdVatCalc(gross, pctEl ? pctEl.value : 0);
  const netEl = box.querySelector('.hd-vat-net');
  const vatEl = box.querySelector('.hd-vat-amt');
  if(netEl) netEl.value = r.net.toFixed(2);
  if(vatEl){
    if(vatEl.tagName === 'INPUT') vatEl.value = r.vat.toFixed(2);
    else vatEl.textContent = r.vat.toFixed(2);
  }
}
document.addEventListener('input', function(e){
  if(!e.target.classList.contains('hd-vat-pct') && !e.target.classList.contains('hd-vat-gross')) return;
  hdVatUpdate(e.target.closest('.hd-vat-box'));
});
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.hd-vat-box').forEach(hdVatUpdate);
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
  tpl.querySelectorAll('.hd-vat-pct').forEach(el => { el.value = '0'; });
  tpl.querySelectorAll('.select2-container').forEach(c => c.remove());
  tpl.querySelectorAll('select').forEach(s => {
    s.classList.remove('select2-hidden-accessible');
    s.removeAttribute('data-select2-id'); s.removeAttribute('tabindex');
    s.removeAttribute('aria-hidden'); s.style.display = '';
  });
  const clone = wrap.appendChild(tpl);
  if(window.jQuery){ jQuery(clone).find('select.select2').select2({width:'100%', dropdownParent: jQuery(document.body)}); }
  hdVatUpdate(clone.querySelector('.hd-vat-box'));
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

// ── Find & Match: applied amounts + new receipts for the remainder ──
function hdFindUpdate(cb){
  const form = cb.closest('form'), list = form.querySelector('.hd-find-list');
  const target = Math.round(parseFloat(list.dataset.target || '0') * 100) / 100;

  // Ticking a receipt reveals its "Apply £" box and defaults it to what the
  // bank line still needs, never more than the receipt has left.
  list.querySelectorAll('.hd-find-item').forEach(item => {
    const row = item.querySelector('.hd-find-cb');
    const apply = item.querySelector('.hd-find-apply');
    if(!row || !apply) return;
    apply.hidden = !row.checked;
    // A disabled input is never submitted, so unticking cannot send a stale amount.
    apply.disabled = !row.checked;
    const left = parseFloat(item.dataset.remaining || '0');
    if(!row.checked) return;
    if(apply.value !== '' && !(parseFloat(apply.value) > left + 0.004)) return;
    let otherApplied = 0, otherCreated = 0;
    list.querySelectorAll('.hd-find-item').forEach(other => {
      const ocb = other.querySelector('.hd-find-cb');
      if(!ocb || ocb === row || !ocb.checked) return;
      const oap = other.querySelector('.hd-find-apply');
      const ov = oap && oap.value !== '' ? parseFloat(oap.value) : parseFloat(other.dataset.remaining || '0');
      if(!isNaN(ov)) otherApplied += ov;
    });
    form.querySelectorAll('.hd-create-gross').forEach(i => { const v = parseFloat(i.value); if(!isNaN(v)) otherCreated += v; });
    const outstanding = Math.max(0, Math.round((target - otherApplied - otherCreated) * 100) / 100);
    apply.value = Math.min(left, outstanding > 0 ? outstanding : left).toFixed(2);
  });

  hdFindTotals(form, true);
}

function hdFindTotals(form, autoFill){
  const list = form.querySelector('.hd-find-list');
  if(!list) return;
  const target = Math.round(parseFloat(list.dataset.target || '0') * 100) / 100;

  let applied = 0;
  list.querySelectorAll('.hd-find-item').forEach(item => {
    const row = item.querySelector('.hd-find-cb');
    if(!row || !row.checked) return;
    const apply = item.querySelector('.hd-find-apply');
    const left = parseFloat(item.dataset.remaining || '0');
    let v = apply && apply.value !== '' ? parseFloat(apply.value) : left;
    if(isNaN(v) || v < 0) v = 0;
    if(v > left + 0.004) v = left;
    applied += v;
  });
  let created = 0;
  form.querySelectorAll('.hd-create-gross').forEach(i => { const v = parseFloat(i.value); if(!isNaN(v) && v > 0) created += v; });
  applied = Math.round(applied * 100) / 100;
  created = Math.round(created * 100) / 100;
  let remaining = Math.round((target - applied - created) * 100) / 100;

  // Whatever is still uncovered is offered in the first empty create line.
  if(autoFill && remaining > 0.004){
    const empty = Array.from(form.querySelectorAll('.hd-create-gross')).find(i => i.value === '');
    if(empty){
      empty.value = remaining.toFixed(2);
      hdVatUpdate(empty.closest('.hd-vat-box'));
      created = Math.round((created + remaining) * 100) / 100;
      remaining = 0;
    }
  }

  const ok = Math.abs(remaining) < 0.005 && (applied + created) > 0;
  const panel = form.closest('.hd-tab-panel');
  const counter = panel ? panel.querySelector('.hd-find-counter') : null;
  if(counter){
    counter.textContent = 'Bank £'+target.toFixed(2)+' = Applied £'+applied.toFixed(2)+' + Creating £'+created.toFixed(2)+' · Remaining £'+Math.max(0, remaining).toFixed(2);
    counter.style.background = ok ? '#dcfce7' : (remaining < -0.004 ? '#fee2e2' : '#f1f5f9');
    counter.style.color = ok ? '#15803d' : (remaining < -0.004 ? '#b91c1c' : '#334155');
  }
  const meter = form.querySelector('.hd-create-meter');
  if(meter){
    meter.textContent = 'Creating £'+created.toFixed(2)+(Math.abs(remaining) < 0.005 ? ' ✓ balanced' : (remaining < 0 ? ' ✗ over by £'+Math.abs(remaining).toFixed(2) : ' · £'+remaining.toFixed(2)+' still to cover'));
    meter.style.background = Math.abs(remaining) < 0.005 ? '#dcfce7' : (remaining < 0 ? '#fee2e2' : '#f1f5f9');
    meter.style.color = Math.abs(remaining) < 0.005 ? '#15803d' : (remaining < 0 ? '#b91c1c' : '#334155');
  }
  const submitBtn = form.querySelector('.hd-find-submit');
  if(submitBtn) submitBtn.disabled = !ok;
  const rm = form.querySelector('button[onclick*="hdRemoveCreateLine"]');
  if(rm) rm.disabled = form.querySelectorAll('.hd-create-line').length <= 1;
}

function hdAddCreateLine(btn){
  const form = btn.closest('form'), wrap = form.querySelector('.hd-create-lines');
  const lines = wrap.querySelectorAll('.hd-create-line'), i = lines.length;
  const tpl = lines[0].cloneNode(true);
  // Re-number the cloned field names for the new line.
  tpl.querySelectorAll('[name]').forEach(el => { el.name = el.name.replace(/creates\[\d+\]/, 'creates['+i+']'); });
  // Fresh amounts, but keep the supplier / invoice-date defaults for speed.
  tpl.querySelectorAll('.hd-create-gross').forEach(el => { el.value = ''; });
  tpl.querySelectorAll('.hd-vat-pct').forEach(el => { el.value = '0'; });
  tpl.querySelectorAll('select').forEach(el => { el.value = ''; });
  tpl.querySelectorAll('.select2-container').forEach(c => c.remove());
  tpl.querySelectorAll('select').forEach(s => {
    s.classList.remove('select2-hidden-accessible');
    s.removeAttribute('data-select2-id'); s.removeAttribute('tabindex');
    s.removeAttribute('aria-hidden'); s.style.display = '';
  });
  const clone = wrap.appendChild(tpl);
  if(window.jQuery){ jQuery(clone).find('select.select2').select2({width:'100%', dropdownParent: jQuery(document.body)}); }
  hdVatUpdate(clone.querySelector('.hd-vat-box'));
  hdFindTotals(form, true);
}

function hdRemoveCreateLine(btn){
  const form = btn.closest('form'), wrap = form.querySelector('.hd-create-lines');
  const lines = wrap.querySelectorAll('.hd-create-line');
  if(lines.length <= 1) return;
  lines[lines.length - 1].remove();
  wrap.querySelectorAll('.hd-create-line').forEach((line, idx) => {
    line.querySelectorAll('[name]').forEach(el => { el.name = el.name.replace(/creates\[\d+\]/, 'creates['+idx+']'); });
  });
  hdFindTotals(form, false);
}

document.addEventListener('input', function(e){
  if(!e.target.classList.contains('hd-find-apply') && !e.target.classList.contains('hd-create-gross')) return;
  const form = e.target.closest('form');
  if(form && form.classList.contains('hd-find-form')) hdFindTotals(form, false);
});
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.hd-find-form').forEach(function(form){
    // Keep unticked rows out of the payload from the first paint.
    form.querySelectorAll('.hd-find-item').forEach(function(item){
      const row = item.querySelector('.hd-find-cb');
      const apply = item.querySelector('.hd-find-apply');
      if(row && apply) apply.disabled = !row.checked;
    });
    hdFindTotals(form, false);
  });
});

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

// ── Receipt details popup (view a receipt before selecting it) ──
(function(){
  const modal = document.getElementById('hd-receipt-modal');
  if(!modal) return;
  const bodyEl = document.getElementById('hd-receipt-modal-body');
  const titleEl = document.getElementById('hd-receipt-modal-title');
  const selectBtn = document.getElementById('hd-receipt-select');
  const baseUrl = "{{ url('/admin/bank-reconciliation/receipt') }}";
  let activeRow = null;
  let lastFocus = null;

  const esc = v => String(v == null ? '' : v).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const rowHtml = (label, value) => (value === null || value === undefined || value === '')
    ? '' : '<div class="hd-details-row"><span>'+esc(label)+'</span><span>'+esc(value)+'</span></div>';
  const money = v => (v === null || v === undefined || v === '') ? null : '£' + Number(v).toFixed(2);

  function open(row){
    activeRow = row;
    lastFocus = document.activeElement;
    modal.hidden = false;
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
    titleEl.textContent = 'Receipt #' + (row.dataset.hdReceipt || '');
    bodyEl.innerHTML = '<div class="hd-small" style="padding:12px 0">Loading receipt details…</div>';
    selectBtn.hidden = true;

    const url = baseUrl + '/' + encodeURIComponent(row.dataset.hdReceipt || '') +
                '?client_id=' + encodeURIComponent(row.dataset.hdClient || '');
    fetch(url, {headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}, credentials: 'same-origin'})
      .then(r => {
        if(!r.ok) throw new Error(r.status === 404 ? 'Receipt not found.' : 'Could not load receipt details.');
        return r.json();
      })
      .then(d => render(d))
      .catch(err => {
        bodyEl.innerHTML = '<div class="hd-small" style="padding:12px 0;color:#b91c1c">'+esc(err.message)+'</div>';
      });
  }

  function render(d){
    titleEl.textContent = 'Receipt #' + (d.receipt_number || d.id);
    const head = d.account_head ? (d.account_head.code + ' – ' + d.account_head.name) : null;
    const tax = Number(d.tax_amount || 0) + Number(d.vat_amount || 0);
    const method = d.payment_method ? d.payment_method.charAt(0).toUpperCase() + d.payment_method.slice(1) : null;
    const files = (d.files || []).length
      ? d.files.map(f => '<a class="hd-receipt-file" href="'+esc(f.url)+'" target="_blank" rel="noopener"><i class="fa fa-paperclip"></i> '+esc(f.name || 'File')+'</a>').join('')
      : '<div class="hd-small">No files attached.</div>';

    bodyEl.innerHTML =
      '<div class="hd-receipt-amount">'+esc(money(d.total_amount) || '—')+'</div>' +
      '<div class="hd-receipt-sub">'+esc(d.supplier || 'Unknown supplier')+'</div>' +
      '<div class="hd-receipt-grid">' +
        rowHtml('Invoice date', d.invoice_date) +
        rowHtml('Receipt date', d.receipt_date) +
        rowHtml('Invoice number', d.invoice_number) +
        rowHtml('Account head', head) +
        rowHtml('Payment method', method) +
        rowHtml('Paid', d.paid ? 'Yes' : 'No') +
        rowHtml('Net amount', money(d.net_amount)) +
        rowHtml('Tax / VAT', tax > 0 ? '£' + tax.toFixed(2) : null) +
        rowHtml('Used so far', money(d.used_amount)) +
        rowHtml('Remaining', money(d.remaining_amount)) +
        rowHtml('Status', d.status) +
        rowHtml('Uploaded', d.created_at) +
      '</div>' +
      (d.description ? '<div class="hd-receipt-desc">'+esc(d.description)+'</div>' : '') +
      '<div class="hd-receipt-files">' +
        '<div class="hd-small" style="font-weight:700;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px">Attachments</div>' + files +
      '</div>';

    const input = activeRow ? activeRow.querySelector('input[type=radio], input[type=checkbox]') : null;
    selectBtn.hidden = !input || input.checked;
  }

  function close(){
    modal.classList.remove('open');
    modal.hidden = true;
    document.body.style.overflow = '';
    activeRow = null;
    if(lastFocus && lastFocus.focus){ try { lastFocus.focus(); } catch(e){} }
  }

  document.addEventListener('click', function(e){
    const row = e.target.closest('.hd-match-item[data-hd-receipt], .hd-find-item[data-hd-receipt]');
    if(row){
      // Let the radio/checkbox keep its own select behaviour; anything else in
      // the row opens the details popup instead of selecting the receipt.
      if(e.target.closest('input')) return;
      e.preventDefault();
      open(row);
      return;
    }
    if(e.target.closest('[data-hd-receipt-close]')) { close(); return; }
    if(e.target === modal) close();
  });

  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape' && !modal.hidden) close();
  });

  selectBtn.addEventListener('click', function(){
    if(!activeRow) return;
    const input = activeRow.querySelector('input[type=radio], input[type=checkbox]');
    if(input){
      input.checked = true;
      input.dispatchEvent(new Event('change', {bubbles: true}));
    }
    close();
  });
})();

// ── Quick Add Head wiring ──
window.openQuickHeadFor = function(btn){
  var sel = hdHeadSelectFor(btn);
  QuickAccountHead.open({ target: sel });
};
function hdHeadSelectFor(btn){
  var scope = btn.closest('.hd-field') || btn.closest('label') || btn.closest('.hd-tab-panel');
  if(!scope) return null;
  var sel = scope.querySelector('select[name="account_head_id"]')
        || scope.querySelector('select[name^="splits["]')
        || scope.querySelector('select[name^="creates["]');
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
    $.get("{{ url('/admin/receipts/search-clients') }}", { q: '', selected_id: presetCredentialId }, function(data){
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
      // Dynamically load banks based on selected business
      function loadBanksForBusiness(bizId) {
        var credId = $('#client_credential_id').val();
        var url = '{{ route('admin.bank-reconciliation.banks') }}' + '?client_id=' + (bizId || '') + '&client_credential_id=' + credId;
        $.get(url, function(data){
          var currentBank = $('select[name="bank_account_id"]').val();
          var opts = '<option value="">All bank accounts</option>';
          $.each(data, function(i, b){
            if (String(b.bank_name).toLowerCase().trim() === 'unknown historical bank') return;
            var sel = String(b.id) === String(currentBank) ? ' selected' : '';
            opts += '<option value="' + b.id + '"' + sel + '>' + b.bank_name + (b.account_last_four ? ' ••' + b.account_last_four : '') + '</option>';
          });
          $('select[name="bank_account_id"]').html(opts).trigger('change.select2');
        });
      }
      $('#filterBusiness').on('change', function(){ loadBanksForBusiness($(this).val()); });
      loadBanksForBusiness($('#filterBusiness').val());
    });
  }
});
</script>

{{-- 2) Workspace: rows, counters and pager are server-rendered fragments that JS swaps in place --}}
<script>
// Re-attach the interactive bits after a fragment is injected.
function hdInitWorkspace(root){
  if(!root) return;
  root.querySelectorAll('.hd-vat-box').forEach(function(box){ hdVatUpdate(box); });
  root.querySelectorAll('.hd-split-form').forEach(function(f){ hdSplitTotals(f); });
  root.querySelectorAll('.hd-find-form').forEach(function(f){
    // Unticked rows must never send a stale "Apply".
    f.querySelectorAll('.hd-find-item').forEach(function(item){
      const cb = item.querySelector('.hd-find-cb'), ap = item.querySelector('.hd-find-apply');
      if(cb && ap){ ap.hidden = !cb.checked; ap.disabled = !cb.checked; }
    });
    hdFindTotals(f, false);
  });
  if(window.jQuery){
    jQuery(root).find('select.select2').each(function(){
      const $s = jQuery(this);
      if($s.data('select2')) return;
      $s.select2({width:'100%', dropdownParent: jQuery(document.body)});
    });
  }
}
window.hdInitWorkspace = hdInitWorkspace;
document.addEventListener('DOMContentLoaded', function(){ hdInitWorkspace(document.getElementById('hd-rows')); });

(function(){
  const ws = document.getElementById('hd-reconciliation-workspace');
  if(!ws) return;
  const rowsEl  = document.getElementById('hd-rows');
  const pagerEl = document.getElementById('hd-pagination');
  const totalEl = document.getElementById('hd-rows-total');
  const rangeEl = document.getElementById('hd-rows-range');
  const form    = document.getElementById('hd-filter-form');
  const rowsUrl = ws.dataset.rowsUrl;

  function statusCards(){ return Array.prototype.slice.call(document.querySelectorAll('[data-hd-status]')); }
  function activeStatus(){
    const on = document.querySelector('[data-hd-status].active');
    return on ? on.dataset.hdStatus : (ws.dataset.reconcileStatus || 'unreconciled');
  }
  function filterParams(){
    const p = new URLSearchParams();
    if(form){
      new FormData(form).forEach(function(v, k){ p.set(k, v == null ? '' : v); });
    }
    p.set('reconcile_status', activeStatus() === 'all' ? '' : activeStatus());
    return p;
  }
  function syncUrl(params, page){
    const p = new URLSearchParams(params);
    if(page > 1) p.set('page', page); else p.delete('page');
    const qs = p.toString();
    try { history.replaceState(null, '', location.pathname + (qs ? '?' + qs : '')); } catch(e){}
  }

  function paintCards(stats){
    if(!stats) return;
    document.querySelectorAll('[data-hd-count]').forEach(function(el){
      const v = stats[el.dataset.hdCount];
      if(v !== undefined && v !== null) el.textContent = v;
    });
    const active = activeStatus();
    statusCards().forEach(function(c){
      const on = c.dataset.hdStatus === active;
      c.classList.toggle('active', on);
      c.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
  }

  function paintPager(pg){
    if(!pagerEl) return;
    if(rangeEl) rangeEl.textContent = (pg && pg.total) ? ('Showing ' + pg.from + '–' + pg.to + ' of ' + pg.total) : '';
    pagerEl.innerHTML = '';
    if(!pg || pg.last_page <= 1) return;
    const ul = document.createElement('ul');
    ul.className = 'pagination mb-0';
    function add(label, target, opts){
      opts = opts || {};
      const li = document.createElement('li');
      li.className = 'page-item' + (opts.active ? ' active' : '') + (opts.disabled ? ' disabled' : '');
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'page-link';
      btn.innerHTML = label;
      if(!opts.disabled && !opts.active) btn.addEventListener('click', function(){ load(target); });
      li.appendChild(btn);
      ul.appendChild(li);
    }
    add('&laquo;', pg.page - 1, {disabled: pg.page <= 1});
    const start = Math.max(1, pg.page - 2), end = Math.min(pg.last_page, pg.page + 2);
    if(start > 1){ add('1', 1); if(start > 2) add('&hellip;', pg.page, {disabled: true}); }
    for(let i = start; i <= end; i++) add(String(i), i, {active: i === pg.page});
    if(end < pg.last_page){ if(end < pg.last_page - 1) add('&hellip;', pg.page, {disabled: true}); add(String(pg.last_page), pg.last_page); }
    add('&raquo;', pg.page + 1, {disabled: pg.page >= pg.last_page});
    pagerEl.appendChild(ul);
  }

  // Paint one server response: rows HTML + counters + pager.
  function paint(data){
    if(!rowsEl) return;
    if(typeof data.html === 'string') rowsEl.innerHTML = data.html;
    else if(!rowsEl.querySelector('.hd-reconcile-row') && !rowsEl.textContent.trim()) rowsEl.innerHTML = data.empty_html || '';
    if(totalEl && data.stats) totalEl.textContent = data.stats.total;
    paintCards(data.stats);
    paintPager(data.pagination);
    hdInitWorkspace(rowsEl);
  }
  window.hdWorkspacePaint = paint;

  function load(page){
    const params = filterParams();
    if(page > 1) params.set('page', page);
    rowsEl.style.opacity = '0.45';
    fetch(rowsUrl + '?' + params.toString(), {
      headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
      credentials: 'same-origin'
    })
      .then(function(r){ if(!r.ok) throw new Error('Could not load transactions.'); return r.json(); })
      .then(function(d){ paint(d); syncUrl(params, page || 1); })
      .catch(function(err){ if(window.toastr) toastr.error(err.message); })
      .then(function(){ rowsEl.style.opacity = ''; });
  }
  window.hdWorkspaceLoad = load;

  if(form) form.addEventListener('submit', function(e){ e.preventDefault(); load(1); });

  statusCards().forEach(function(c){
    c.addEventListener('click', function(){
      if(c.classList.contains('active')) return;
      statusCards().forEach(function(o){ o.classList.remove('active'); o.setAttribute('aria-pressed', 'false'); });
      c.classList.add('active'); c.setAttribute('aria-pressed', 'true');
      load(1);
    });
  });

  // Keep the address bar in step with what is on screen.
  syncUrl(filterParams(), parseInt(new URLSearchParams(location.search).get('page') || '1', 10) || 1);
})();
</script>

{{-- 3) Actions: the server returns the one row it changed, nothing else is reloaded --}}
<script>
jQuery(function($){
  $(document).on('submit', '.hd-action-form', function(e){
    e.preventDefault();
    var $form = $(this);
    $.ajax({
      url: $form.attr('action') + window.location.search,
      method: 'POST',
      data: $form.serialize(),
      dataType: 'json',
      headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
    }).done(function(resp){
      if(!resp || !resp.success){
        var bad = (resp && resp.message) ? resp.message : 'Failed';
        if(window.toastr) toastr.error(bad); else alert(bad);
        return;
      }
      if(window.toastr) toastr.success(resp.message || 'Saved');

      var id = resp.entry_id;
      if(resp.row){
        var $row = id ? $('#bank-entry-' + id) : $();
        if(resp.row.visible && resp.row.html){
          if($row.length) $row.replaceWith(resp.row.html);
          else $('#hd-rows').prepend(resp.row.html);
          $row = id ? $('#bank-entry-' + id) : $();
        } else if($row.length){
          $row.remove();
        }
        if(!$('#hd-rows .hd-reconcile-row').length){
          $('#hd-rows').html(resp.empty_html || '');
        }
        if(window.hdWorkspacePaint){
          window.hdWorkspacePaint({html: null, stats: resp.stats, pagination: resp.pagination});
        }
        hdInitWorkspace($row.length ? $row[0] : document.getElementById('hd-rows'));
      } else if(window.hdWorkspaceLoad){
        window.hdWorkspaceLoad(parseInt(new URLSearchParams(location.search).get('page') || '1', 10) || 1);
      }
    }).fail(function(xhr){
      var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Request failed';
      if(window.toastr) toastr.error(msg); else alert(msg);
    });
  });
});
</script>
@endsection
