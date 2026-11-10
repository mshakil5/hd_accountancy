{{--
    Shared amount block used by every place a receipt is created:
    Create tab, each Split line, and each "create for the remainder" line
    in Find & Match. Same fields, same order, same maths everywhere:
        Supplier · Invoice Date · Net Amount (auto) · VAT Percent · Gross Amount
    Net = Gross / (1 + VAT%/100); VAT = Gross - Net (hdVatUpdate in the page JS).
--}}
@php
    $grossReadonly = $grossReadonly ?? false;
    $grossClass    = $grossClass ?? '';
    $grossHint     = $grossHint ?? null;
    $grossValue    = $gross ?? '';
@endphp
<div class="hd-vat-box" data-gross="{{ $grossValue }}">
    <div class="hd-field">
        <label class="hd-field-lbl">Net Amount</label>
        <input class="form-control hd-fc hd-vat-net" value="{{ is_numeric($grossValue) && $grossValue !== '' ? number_format((float)$grossValue, 2) : '0.00' }}" readonly tabindex="-1">
    </div>
    <div class="hd-field">
        <label class="hd-field-lbl">VAT Percent</label>
        <input class="form-control hd-fc hd-vat-pct" type="number" name="{{ $vatName }}" min="0" max="100" step="0.01" value="0">
    </div>
    <div class="hd-field">
        <label class="hd-field-lbl">Gross Amount</label>
        @if($grossReadonly)
            <input class="form-control hd-fc" value="{{ $grossValue }}" readonly tabindex="-1">
        @else
            <input class="form-control hd-fc hd-vat-gross {{ $grossClass }}" type="number" name="{{ $grossName }}"
                   min="0" step="0.01" value="{{ $grossValue }}" placeholder="0.00">
        @endif
        <div class="hd-small hd-vat-amt-hint">VAT £<span class="hd-vat-amt">0.00</span>@if($grossHint) · {{ $grossHint }} @endif</div>
    </div>
</div>
