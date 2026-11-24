@if($lastReconciled)
@php
  $amount = (float) $lastReconciled->amount;
  $bankLabel = trim(($lastReconciled->bank_name ?? '') . (!empty($lastReconciled->account_last_four) ? ' ••' . $lastReconciled->account_last_four : ''));
  $shownBalance = $lastReconciled->balance_after ?? $lastReconciled->system_balance;
  $approvedAt = $lastReconciled->approved_at ? \Carbon\Carbon::parse($lastReconciled->approved_at) : null;
@endphp
<div class="hd-last-recon">
  <div class="hd-last-recon__main">
    <div class="hd-last-recon__label">Last reconciled row</div>
    <div class="hd-last-recon__title">
      <span class="hd-last-recon__date">{{ \Carbon\Carbon::parse($lastReconciled->transaction_date)->format('d/m/Y') }}</span>
      <span class="hd-last-recon__desc">{{ $lastReconciled->description }}</span>
    </div>
    @if($bankLabel !== '')
      <div class="hd-last-recon__meta">{{ $bankLabel }}</div>
    @endif
  </div>
  <div class="hd-last-recon__cells">
    @if($shownBalance !== null)
      <div class="hd-last-recon__cell">
        <div class="hd-last-recon__label">Balance after</div>
        <div class="hd-last-recon__val">£{{ number_format((float) $shownBalance, 2) }}</div>
        @if($lastReconciled->balance_after === null)
          <div class="hd-last-recon__sub">calculated</div>
        @endif
      </div>
    @endif
    <div class="hd-last-recon__cell">
      <div class="hd-last-recon__label">Amount</div>
      <div class="hd-last-recon__val {{ $amount < 0 ? 'hd-last-recon__val--neg' : 'hd-last-recon__val--pos' }}">{{ $amount < 0 ? '−' : '+' }}£{{ number_format(abs($amount), 2) }}</div>
    </div>
    @if($approvedAt)
      <div class="hd-last-recon__cell">
        <div class="hd-last-recon__label">Approved</div>
        <div class="hd-last-recon__val" style="font-size:13px">{{ $approvedAt->format('d/m/Y H:i') }}</div>
        @if(!empty($lastReconciled->approved_by_name))
          <div class="hd-last-recon__sub">by {{ $lastReconciled->approved_by_name }}</div>
        @endif
      </div>
    @endif
  </div>
</div>
@endif
