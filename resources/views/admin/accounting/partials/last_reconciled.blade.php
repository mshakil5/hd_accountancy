@if($lastReconciled)
<div class="hd-reconciled-banner mb-3">
  <div>
    <div class="hd-small font-weight-bold" style="color:#166534;text-transform:uppercase;letter-spacing:.03em;">Last reconciled row</div>
    <div class="font-weight-bold">
      {{ \Carbon\Carbon::parse($lastReconciled->transaction_date)->format('d/m/Y') }}
      · {{ $lastReconciled->description }}
    </div>
    @if($lastReconciled->balance_after !== null)
      <div class="hd-small text-muted">Balance after: £{{ number_format((float) $lastReconciled->balance_after, 2) }}</div>
    @endif
  </div>
  <div class="text-right">
    <div class="font-weight-bold" style="color:#15803d;font-size:18px;">
      {{ (float) $lastReconciled->amount < 0 ? '−' : '+' }}£{{ number_format(abs((float) $lastReconciled->amount), 2) }}
    </div>
    @if($lastReconciled->approved_at)
      <div class="hd-small text-muted">
        Approved {{ \Carbon\Carbon::parse($lastReconciled->approved_at)->format('d/m/Y H:i') }}@if(!empty($lastReconciled->approved_by_name))
          by {{ $lastReconciled->approved_by_name }}@endif
      </div>
    @endif
  </div>
</div>
@endif
