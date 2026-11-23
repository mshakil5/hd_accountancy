@extends('admin.layouts.admin')
@php
    // Both verification entry points render this page; the fragment endpoint is
    // requested from the same route family so links stay inside the workspace
    // the accountant came from.
    $verificationRoute = (string) optional(request()->route())->getName();
    $rowsUrl = \Illuminate\Support\Str::startsWith($verificationRoute, 'admin.bank-reconciliation')
        ? route('admin.bank-reconciliation.verification-rows', $import->id)
        : route('admin.bank-statements.verification-rows', $import->id);
@endphp
@section('content')
<div class="container-fluid py-4">
<style>
.hd-card{background:#fff;border:1px solid #e5eaf1;border-radius:12px;padding:18px;margin-bottom:16px}
.hd-small{color:#64748b;font-size:12px}
.hd-scroll{overflow-x:auto;overflow-y:visible}
.hd-table{width:100%;font-size:13px;table-layout:auto}
.hd-table th,.hd-table td{border-bottom:1px solid #e7ebf1;padding:9px;vertical-align:middle}
.hd-table th{background:#f1f5f9;font-weight:600}
.hd-grid{display:grid;grid-template-columns:minmax(300px,.8fr) minmax(0,1.9fr);gap:16px;align-items:start}
@media(max-width:1200px){.hd-grid{grid-template-columns:1fr}}
.hd-pdf{width:100%;height:78vh;border:1px solid #dbe3ee;border-radius:10px;background:#f8fafc}
.hd-pill{display:inline-block;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600;white-space:nowrap;}
.hd-pill.blocking{background:#fee2e2;color:#b91c1c}
.hd-pill.warning{background:#fef3c7;color:#92400e}
.hd-pill.ok{background:#dcfce7;color:#15803d}
/* ── Live counters (same cards as Bank Reconciliation) ── */
.hd-stat-bar{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}
.hd-stat{background:#f8fafc;border:1px solid #e5eaf1;border-radius:10px;padding:9px 16px;min-width:120px;text-align:center}
.hd-stat__label{display:block;font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.hd-stat__val{display:block;font-size:20px;font-weight:800;line-height:1.15;margin-top:2px;font-variant-numeric:tabular-nums}
/* ── Verification rows ── */
.hd-verify-table td:first-child{border-left:4px solid transparent}
.hd-verify-table tr.is-issue td{background:#fff1f2}
.hd-verify-table tr.is-issue td:first-child{border-left-color:#ef4444}
.hd-verify-table tr.is-editing{opacity:.4}
.hd-verify-table tbody tr:hover td{background:#f8fbff}
.hd-inline-form-tr > td{padding:6px 4px!important;background:#f8fbff!important;border-bottom:1px solid #dbe4ee!important}
.hd-inline-form-box{background:#f8fbff;border:1px solid #dbe4ee;border-radius:8px;padding:10px}
.hd-inline-grid{display:grid;grid-template-columns:130px minmax(180px,1fr) 130px minmax(160px,1fr);gap:8px;align-items:end}
@media(max-width:900px){.hd-inline-grid{grid-template-columns:1fr 1fr}}
@media(max-width:560px){.hd-inline-grid{grid-template-columns:1fr}}
.hd-inline-grid label{display:block;font-size:11px;font-weight:700;color:#334155;margin-bottom:2px}
.hd-exception.is-resolved{opacity:.65}
</style>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if($errors->any())
    <div class="alert alert-danger">
        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif

{{-- ── Header: fixed chrome + live status / counters / banner ── --}}
<div class="hd-card">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <a href="{{ $backUrl }}" class="hd-small">&larr; Back to Bank Statements</a>
            <h4 class="mb-1">Statement verification</h4>
            <div class="hd-small">
                <strong>{{ $import->filename }}</strong>
                · {{ $bankName }} {{ $bankLast ? '••••'.e($bankLast) : '' }}
                · Client: {{ $clientName }}
                · Uploaded {{ \Illuminate\Support\Carbon::parse($import->created_at)->format('d/m/Y H:i') }}
                · {{ (int) $import->pages_total }} page(s), {{ (int) $import->pages_processed }} with text
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <form method="POST" action="{{ route('admin.bank-statements.refresh', $import->id) }}" onsubmit="const b=this.querySelector('button'); b.disabled=true; b.innerHTML='<span class=\'spinner-border spinner-border-sm mr-1\'></span> Checking...'; return true;">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm" title="Re-read the PDF and recalculate the checks and balances. Does NOT delete or re-insert rows (safe to run anytime).">
                    <i class="bi bi-arrow-repeat mr-1"></i> Recalculate checks
                </button>
            </form>
            <form method="POST" action="{{ route('admin.bank-statements.rescan-ocr', $import->id) }}" onsubmit="if(!confirm('Re-extract this statement with OCR? All non-approved rows will be replaced with the OCR result and system balances recalculated. Manual corrections to unapproved rows will be lost.')){ return false; } const b=this.querySelector('button'); b.disabled=true; b.innerHTML='<span class=\'spinner-border spinner-border-sm mr-1\'></span> Extracting with OCR...'; return true;">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm" title="Re-read the PDF and re-extract rows with OCR, replace extracted rows, and recalculate balances.">
                    <i class="bi bi-cpu mr-1"></i> Re-extract with OCR
                </button>
            </form>
            <div id="hd-ver-status">
                @include('admin.accounting.partials.verification_header_status')
            </div>
        </div>
    </div>

    <div id="hd-ver-stats">
        @include('admin.accounting.partials.verification_stats')
    </div>

    <div id="hd-ver-banner">
        @include('admin.accounting.partials.verification_banner')
    </div>
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

    <div id="hd-ver-workspace" data-rows-url="{{ $rowsUrl }}">
        {{-- 1. Rows first: each one carries its own "add the missing row" action. --}}
        <div class="hd-card">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <h5 class="mb-0">Extracted rows (<span data-hd-rows-count>{{ $entries->count() }}</span>)</h5>
                <span class="hd-small">Checked against the PDF · corrections keep their audit trail</span>
            </div>
            <div id="hd-ver-rows" data-add-url="{{ route('admin.bank-statements.store-entry', $import->id) }}">
                @include('admin.accounting.partials.verification_rows')
            </div>
        </div>

        {{-- 2. Then the checks the accountant resolves against those rows. --}}
        <div id="hd-ver-exceptions">
            @include('admin.accounting.partials.verification_exceptions')
        </div>

        <div id="hd-ver-pages">
            @include('admin.accounting.partials.verification_pages')
        </div>

        <div id="hd-ver-revisions">
            @include('admin.accounting.partials.verification_revisions')
        </div>
    </div>
</div>
</div>
@endsection

@section('script')
<script>
(function(){
  const ws = document.getElementById('hd-ver-workspace');
  if(!ws) return;

  const csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  const rowsBox = document.getElementById('hd-ver-rows');
  const statsBox = document.getElementById('hd-ver-stats');
  const statusBox = document.getElementById('hd-ver-status');
  const bannerBox = document.getElementById('hd-ver-banner');
  const exceptionsBox = document.getElementById('hd-ver-exceptions');
  const pagesBox = document.getElementById('hd-ver-pages');
  const revisionsBox = document.getElementById('hd-ver-revisions');
  const rowsUrl = ws.dataset.rowsUrl;

  const esc = v => String(v == null ? '' : v).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const money = v => (v === null || v === undefined || v === '' ? '' : '£' + Number(v).toFixed(2));

  function toast(kind, message){
    if(window.toastr){ toastr[kind](message); return; }
    if(kind === 'error'){ alert(message); }
  }

  function firstError(xhr){
    const body = xhr && xhr.responseJSON;
    if(body && body.errors){
      const key = Object.keys(body.errors)[0];
      if(key) return body.errors[key][0];
    }
    if(body && body.message) return body.message;
    if(xhr && xhr.status === 419) return 'Session expired — reload the page and try again.';
    if(xhr && xhr.status === 409) return 'This row is already reconciled and cannot be changed here.';
    return 'The change could not be saved.';
  }

  /** Replaces every panel the server re-rendered, keeping scroll positions. */
  function paint(data){
    if(!data) return;
    const y = window.scrollY;
    const scroller = rowsBox ? rowsBox.querySelector('.hd-scroll') : null;
    const left = scroller ? scroller.scrollLeft : 0;

    if(typeof data.rows_html === 'string' && rowsBox) rowsBox.innerHTML = data.rows_html;
    if(typeof data.stats_html === 'string' && statsBox) statsBox.innerHTML = data.stats_html;
    if(typeof data.status_html === 'string' && statusBox) statusBox.innerHTML = data.status_html;
    if(typeof data.banner_html === 'string' && bannerBox) bannerBox.innerHTML = data.banner_html;
    if(typeof data.exceptions_html === 'string' && exceptionsBox) exceptionsBox.innerHTML = data.exceptions_html;
    if(typeof data.pages_html === 'string' && pagesBox) pagesBox.innerHTML = data.pages_html;
    if(typeof data.revisions_html === 'string' && revisionsBox) revisionsBox.innerHTML = data.revisions_html;

    if(data.stats && data.stats.rows !== undefined){
      document.querySelectorAll('[data-hd-rows-count]').forEach(function(el){ el.textContent = data.stats.rows; });
    }

    const next = rowsBox ? rowsBox.querySelector('.hd-scroll') : null;
    if(next) next.scrollLeft = left;
    window.scrollTo(0, y);
  }

  function send(url, form){
    const init = {
      method: 'POST',
      credentials: 'same-origin',
      headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
    };
    if(form) init.body = new FormData(form);
    return fetch(url, init).then(function(response){
      return response.json().catch(() => ({})).then(function(body){
        if(!response.ok) throw {xhr: {status: response.status, responseJSON: body}};
        return body;
      });
    });
  }

  function submitForm(form, url, confirmText){
    if(confirmText && !window.confirm(confirmText)) return Promise.resolve(null);
    const button = form ? form.querySelector('[type=submit]') : null;
    const label = button ? button.innerHTML : '';
    if(button){ button.disabled = true; button.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> Saving…'; }

    return send(url, form).then(function(data){
      if(data.message) toast('success', data.message);
      paint(data);
      return data;
    }).catch(function(error){
      toast('error', firstError(error.xhr));
      if(button){ button.disabled = false; button.innerHTML = label; }
      return null;
    });
  }

  // ── Inline editor (add / edit / remove) — one at a time, in place ──
  function closeEditors(){
    if(!rowsBox) return;
    rowsBox.querySelectorAll('.hd-inline-form-tr').forEach(function(tr){
      const target = tr.dataset.forRow ? document.getElementById('stmt-row-' + tr.dataset.forRow) : null;
      if(target) target.classList.remove('is-editing');
      tr.remove();
    });
  }

  function openEditor(row, mode){
    if(!rowsBox) return;
    closeEditors();
    const isAdd = mode === 'add';
    const isDelete = mode === 'delete';
    const date = row ? (row.dataset.date || '') : '';
    const description = row ? (row.dataset.description || '') : '';
    const amount = row ? (row.dataset.amount || '') : '';
    const balance = row ? (row.dataset.balance || '') : '';
    const addUrl = rowsBox.dataset.addUrl;

    const fields = isDelete ? '' :
      '<div class="hd-inline-grid">' +
        '<div><label>Date <span class="text-danger">*</span></label>' +
          '<input type="date" name="transaction_date" value="' + esc(date) + '" required class="form-control form-control-sm"></div>' +
        '<div><label>Description <span class="text-danger">*</span></label>' +
          '<input type="text" name="description" value="' + esc(isAdd ? '' : description) + '" required maxlength="500" class="form-control form-control-sm" placeholder="e.g. Card payment to supplier"></div>' +
        '<div><label>Amount <span class="text-danger">*</span></label>' +
          '<input type="number" step="0.01" name="amount" value="' + esc(isAdd ? '' : amount) + '" required class="form-control form-control-sm" placeholder="-100.00 or 100.00"></div>' +
        '<div><label>PDF Balance <span class="text-muted">(optional)</span></label>' +
          '<input type="number" step="0.01" name="balance_after" value="' + esc(isAdd ? '' : balance) + '" class="form-control form-control-sm" placeholder="Leave blank to remove"></div>' +
        '<div><label>Reason <span class="text-danger">*</span></label>' +
          '<input type="text" name="reason" value="' + esc(isAdd ? 'Missing from extraction' : '') + '" required maxlength="1000" class="form-control form-control-sm" placeholder="' + (isAdd ? 'Missing from extraction' : 'Corrected typo in amount / description') + '"></div>' +
      '</div>';

    const summary = isDelete
      ? '<div class="hd-small mb-2"><strong>' + esc(description) + '</strong> · ' + esc(date) + ' · ' + money(amount) + '</div>'
      : '';

    const tr = document.createElement('tr');
    tr.className = 'hd-inline-form-tr';
    tr.dataset.forRow = row ? row.dataset.id : '';
    const cell = document.createElement('td');
    cell.colSpan = 8;
    cell.innerHTML = '<div class="hd-inline-form-box"><form>' + summary + fields +
      '<div class="d-flex gap-2 mt-2">' +
        '<button type="submit" class="btn btn-sm ' + (isDelete ? 'btn-danger' : (isAdd ? 'btn-success' : 'btn-primary')) + '">' +
          (isDelete ? 'Remove row' : (isAdd ? 'Add row' : 'Save changes')) +
        '</button>' +
        '<button type="button" class="btn btn-sm btn-outline-secondary" data-hd-cancel>Cancel</button>' +
      '</div></form></div>';
    tr.appendChild(cell);

    if(isAdd){
      if(row && row.parentNode){ row.after(tr); }
      else {
        const tbody = rowsBox.querySelector('tbody');
        const first = tbody ? tbody.querySelector('tr') : null;
        if(first) first.before(tr); else if(tbody) tbody.appendChild(tr);
      }
    } else if(row){
      row.after(tr);
      row.classList.add('is-editing');
    }

    const form = tr.querySelector('form');
    const focus = form.querySelector('input:not([type=date])') || form.querySelector('input');
    if(focus) focus.focus();
    form.addEventListener('submit', function(e){
      e.preventDefault();
      const url = isAdd ? addUrl : (isDelete ? row.dataset.removeUrl : row.dataset.editUrl);
      submitForm(form, url, isDelete ? 'Remove this row from the statement?' : null);
    });
  }

  // ── One delegated handler for every control inside the workspace ──
  document.addEventListener('click', function(e){
    const target = e.target;

    const rowBtn = target.closest('[data-hd-row]');
    if(rowBtn && rowsBox && rowsBox.contains(rowBtn)){
      const row = rowBtn.closest('tr');
      const action = rowBtn.dataset.hdRow;
      if(action === 'verify'){
        if(!window.confirm('Mark this row verified against the PDF?')) return;
        const button = rowBtn;
        button.disabled = true;
        send(row.dataset.verifyUrl).then(function(data){
          toast('success', data.message || 'Row verified.');
          paint(data);
        }).catch(function(error){
          toast('error', firstError(error.xhr));
          button.disabled = false;
        });
        return;
      }
      openEditor(row, action === 'add' ? 'add' : action);
      return;
    }

    const addTop = target.closest('[data-hd-add]');
    if(addTop && rowsBox && rowsBox.contains(addTop)){ openEditor(null, 'add'); return; }

    if(target.closest('[data-hd-cancel]')){ closeEditors(); return; }

    const resolveBtn = target.closest('[data-hd-exception="resolve"]');
    if(resolveBtn && exceptionsBox && exceptionsBox.contains(resolveBtn)){ openResolver(resolveBtn); return; }
  });

  // ── Resolve a blocking exception with a reason (no reload) ──
  function openResolver(button){
    exceptionsBox.querySelectorAll('.hd-inline-resolve').forEach(function(tr){ tr.remove(); });
    const row = button.closest('tr');
    if(!row) return;
    const url = button.dataset.resolveUrl || '';
    if(!url) return;

    const tr = document.createElement('tr');
    tr.className = 'hd-inline-resolve';
    tr.innerHTML = '<td colspan="5"><form class="p-2 rounded" style="background:#f8fbff;border:1px solid #dbe4ee">' +
      '<label class="hd-small font-weight-bold mb-1">Why is this exception resolved?</label>' +
      '<input type="text" name="resolution" required maxlength="1000" class="form-control form-control-sm" placeholder="e.g. Checked page 2 against the PDF — row extracted correctly">' +
      '<div class="d-flex gap-2 mt-2"><button type="submit" class="btn btn-sm btn-success">Resolve exception</button>' +
      '<button type="button" class="btn btn-sm btn-outline-secondary" data-hd-cancel>Cancel</button></div></form></td>';
    row.after(tr);

    const form = tr.querySelector('form');
    form.querySelector('input').focus();
    form.addEventListener('submit', function(e){ e.preventDefault(); submitForm(form, url); });
    tr.querySelector('[data-hd-cancel]').addEventListener('click', function(){ tr.remove(); });
  }

  // ── Any form marked data-hd-ajax posts in place (e.g. Verify all rows) ──
  document.addEventListener('submit', function(e){
    const form = e.target.closest('form[data-hd-ajax]');
    if(!form) return;
    e.preventDefault();
    submitForm(form, form.getAttribute('action'), form.dataset.hdConfirm || '');
  });

  // Exposed for the tests / console: repaint from the server's fragment endpoint.
  window.hdVerificationReload = function(){
    return fetch(rowsUrl, {headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}, credentials: 'same-origin'})
      .then(function(response){ if(!response.ok) throw new Error('Could not load the statement rows.'); return response.json(); })
      .then(paint);
  };
})();
</script>
@endsection
