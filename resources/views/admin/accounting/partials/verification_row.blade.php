@php
    $amount = (float) $entry->amount;
    $hasMismatch = ($entry->balance_after !== null && $entry->system_balance !== null && abs((float) $entry->balance_after - (float) $entry->system_balance) >= 0.01);
    $hasMissingAmount = ($entry->amount === null || ((float) $entry->amount == 0.0 && $entry->balance_after !== null));
    $hasIssue = $hasMismatch || $hasMissingAmount;
    $isVerified = !empty($entry->verified_at);
    $isApproved = ($entry->reconciliation_status ?? '') === 'approved';
    $dateValue = $entry->transaction_date ? \Illuminate\Support\Carbon::parse($entry->transaction_date)->format('Y-m-d') : '';
    $dateLabel = $dateValue ? \Illuminate\Support\Carbon::parse($dateValue)->format('d/m/Y') : '—';
@endphp

<tr id="stmt-row-{{ $entry->id }}"
    class="hd-vrow{{ $hasIssue ? ' is-issue' : '' }}{{ $isVerified ? ' is-verified' : '' }}{{ $isApproved ? ' is-approved' : '' }}"
    data-id="{{ $entry->id }}"
    data-date="{{ $dateValue }}"
    data-description="{{ $entry->description }}"
    data-amount="{{ number_format($amount, 2, '.', '') }}"
    data-approved="{{ $isApproved ? '1' : '0' }}"
    data-edit-url="{{ route('admin.bank-statements.update-entry', $entry->id) }}"
    data-remove-url="{{ route('admin.bank-statements.destroy-entry', $entry->id) }}"
    data-verify-url="{{ route('admin.bank-statements.verify-entry', $entry->id) }}">
    <td style="text-align:center; font-size:11px; color:#64748b; white-space:nowrap;">
        {{ $entry->source_page ? $entry->source_page . ($entry->source_position ? '/' . $entry->source_position : '') : 'Manual' }}
    </td>
    <td style="white-space:nowrap; font-size:12px;">{{ $dateLabel }}</td>
    <td style="max-width:240px; word-break:break-word;">
        <div>{{ $entry->description }}</div>
        @if(!empty($entry->is_manually_added))
            <div class="hd-small" style="color:#0369a1"><i class="bi bi-pencil-square mr-1"></i> Added by accountant</div>
        @elseif(!empty($entry->is_manually_corrected))
            <div class="hd-small" style="color:#0369a1"><i class="bi bi-pencil mr-1"></i> Corrected</div>
        @endif
        @if($hasMismatch)
            <div class="hd-small text-danger font-weight-bold">
                <i class="bi bi-exclamation-triangle"></i> Balance gap: £{{ number_format(abs((float)$entry->balance_after - (float)$entry->system_balance), 2) }}
            </div>
        @elseif($hasMissingAmount)
            <div class="hd-small text-danger font-bold">
                <i class="bi bi-exclamation-circle"></i> Amount missing
            </div>
        @endif
    </td>
    <td style="white-space:nowrap; text-align:right; font-weight:600; color:{{ $amount < 0 ? '#b91c1c' : '#15803d' }}">
        {{ number_format($amount, 2) }}
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
        @if($isApproved)
            <div class="hd-pill ok mt-1">Reconciled</div>
        @endif
    </td>
    <td style="white-space:nowrap; text-align:center;">
        <div class="d-inline-flex align-items-center gap-1">
            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:11px;"
                    data-hd-row="edit" title="Edit this row" {{ $isApproved ? 'disabled' : '' }}>
                <i class="bi bi-pencil"></i> Edit
            </button>
            @unless($isVerified)
                <button type="button" class="btn btn-sm btn-outline-success py-0 px-2" style="font-size:11px;"
                        data-hd-row="verify" title="Mark row verified">
                    <i class="bi bi-check2"></i>
                </button>
            @endunless
            <button type="button" class="btn btn-sm btn-outline-success py-0 px-2" style="font-size:11px;"
                    data-hd-row="add" title="Add the missing transaction that belongs here">
                <i class="bi bi-plus-lg"></i> Add
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:11px;"
                    data-hd-row="delete" title="Delete this row" {{ $isApproved ? 'disabled' : '' }}>
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </td>
</tr>
