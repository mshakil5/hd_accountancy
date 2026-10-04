@extends('admin.layouts.admin')

@section('content')
@php
    // Amount in words (GBP) — classic invoice line.
    $invWords = function ($number) {
        $number = round((float) $number, 2);
        $pounds = (int) floor($number);
        $pence = (int) round(($number - $pounds) * 100);
        $w = ['zero','one','two','three','four','five','six','seven','eight','nine','ten',
              'eleven','twelve','thirteen','fourteen','fifteen','sixteen','seventeen','eighteen','nineteen'];
        $t = ['', '', 'twenty','thirty','forty','fifty','sixty','seventy','eighty','ninety'];
        $two = function ($n) use ($w, $t) {
            if ($n < 20) return $w[$n];
            return $t[(int)($n / 10)] . ($n % 10 ? '-' . $w[$n % 10] : '');
        };
        $three = function ($n) use ($w, $two, &$three) {
            if ($n == 0) return $w[0];
            $s = '';
            if ($n >= 100) { $s .= $w[(int)($n / 100)] . ' hundred'; $n %= 100; if ($n) $s .= ' and '; }
            if ($n > 0) $s .= $two($n);
            return $s;
        };
        $big = function ($n) use ($w, $three, &$big) {
            if ($n < 1000) return $three($n);
            if ($n < 1000000) {
                $s = $big((int)($n / 1000)) . ' thousand';
                if ($n % 1000) $s .= ' ' . $three($n % 1000);
                return $s;
            }
            $s = $big((int)($n / 1000000)) . ' million';
            if ($n % 1000000) $s .= ' ' . $big($n % 1000000);
            return $s;
        };
        $out = ucfirst($big($pounds)) . ' pound' . ($pounds == 1 ? '' : 's');
        if ($pence > 0) $out .= ' and ' . $two($pence) . ' pence';
        return $out . ' only';
    };

    $detail = $receipt->detail;
    $client = $receipt->client;
    $clientName = $client ? trim(($client->name ?? '') . ' ' . ($client->last_name ?? '')) : '—';
    $invoiceDate = $detail?->invoice_date
        ? \Carbon\Carbon::parse($detail->invoice_date)->format('d M Y')
        : ($receipt->receipt_date ? $receipt->receipt_date->format('d M Y') : '—');
    $dueDate = $detail?->due_date
        ? \Carbon\Carbon::parse($detail->due_date)->format('d M Y')
        : '—';
    $cAddr = [];
    foreach (['address_line1','address_line2','address_line3'] as $f) { if (!empty($client?->$f)) $cAddr[] = $client->$f; }
    $town = trim(implode(' ', array_filter([$client?->city ?? null, $client?->town ?? null])));
    if ($town !== '') $cAddr[] = $town;
    if (!empty($client?->postcode)) $cAddr[] = $client->postcode;
    if (!empty($client?->country)) $cAddr[] = $client->country;
    if (empty($cAddr) && !empty($client?->trading_address)) $cAddr[] = $client->trading_address;
    $stamp = $receipt->status === 'cancelled' ? 'Cancelled' : (($detail?->paid ?? false) ? 'Paid' : 'Unpaid');
@endphp
<style>
.inv-topbar { max-width: 780px; margin: 24px auto 12px; display: flex; justify-content: space-between; align-items: center; }
.inv-sheet {
    max-width: 780px; margin: 0 auto 48px; background: #fff; color: #000;
    font-family: Arial, Helvetica, sans-serif; font-size: 13px; line-height: 1.5;
    border: 1px solid #000; padding: 36px 40px;
}
.inv-head { display: table; width: 100%; margin-bottom: 6px; }
.inv-head > div { display: table-cell; vertical-align: top; }
.inv-brand img { max-height: 56px; max-width: 240px; }
.inv-brand-name { font-size: 20px; font-weight: bold; }
.inv-brand-sub { font-size: 12px; }
.inv-doc { text-align: right; }
.inv-doc h1 { font-size: 30px; margin: 0; font-weight: bold; letter-spacing: 1px; }
.inv-doc .ref { font-size: 13px; margin-top: 2px; }
.inv-rule { border: 0; border-top: 2px solid #000; margin: 10px 0 14px; }
.inv-rule-thin { border: 0; border-top: 1px solid #000; margin: 14px 0; }
.inv-cols { display: table; width: 100%; }
.inv-cols > div { display: table-cell; width: 50%; vertical-align: top; }
.inv-h { font-size: 11px; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; }
.inv-meta { width: 100%; border-collapse: collapse; }
.inv-meta td { padding: 2px 0; font-size: 13px; vertical-align: top; }
.inv-meta td:first-child { font-weight: bold; width: 110px; }
.inv-items { width: 100%; border-collapse: collapse; margin-top: 4px; }
.inv-items th, .inv-items td { border: 1px solid #000; padding: 7px 8px; font-size: 13px; text-align: left; vertical-align: top; }
.inv-items th { font-weight: bold; background: #fff; }
.inv-items td.n, .inv-items th.n { text-align: right; white-space: nowrap; }
.inv-totals { width: 280px; margin-left: auto; border-collapse: collapse; margin-top: 10px; }
.inv-totals td { padding: 3px 0 3px 8px; font-size: 13px; }
.inv-totals td:last-child { text-align: right; white-space: nowrap; }
.inv-grand td { font-weight: bold; font-size: 15px; border-top: 3px double #000; padding-top: 6px; }
.inv-totalrow { display: flex; justify-content: space-between; align-items: flex-end; gap: 24px; margin-top: 10px; }
.inv-totalrow .inv-totals { margin-top: 0; }
.inv-words { font-size: 12px; font-style: italic; max-width: 55%; padding-bottom: 6px; margin-bottom: 14px; }
.inv-note-h { font-weight: bold; margin: 0 0 2px; font-size: 13px; }
.inv-stamp {
    display: inline-block; border: 2px solid #000; padding: 2px 14px;
    font-weight: bold; font-size: 14px; letter-spacing: 2px; text-transform: uppercase;
    transform: rotate(-4deg); margin-top: 10px;
}
.inv-sign { display: table; width: 100%; margin-top: 34px; }
.inv-sign > div { display: table-cell; width: 33.33%; font-size: 12px; }
.inv-sign .line { border-top: 1px solid #000; margin-right: 24px; padding-top: 2px; }
.inv-foot { margin-top: 18px; padding-top: 8px; border-top: 1px solid #000; font-size: 11px; text-align: center; }
@media print {
    @page { size: A4; margin: 12mm; }
    html, body { margin: 0 !important; padding: 0 !important; background: #fff !important; }
    body > * { visibility: hidden !important; }
    .inv-sheet, .inv-sheet * { visibility: visible !important; }
    .main-sidebar, .main-header, .main-footer, .content-header, .navbar,
    .sidebar, .no-print, .inv-topbar { display: none !important; }
    .inv-sheet { position: absolute; top: 0; left: 0; width: 100%; margin: 0 !important; border: 0; padding: 0; }
}
</style>

<div class="inv-topbar no-print">
    <a href="{{ route('admin.receipt.index', ['client_credential_id' => $receipt->client?->client_credential_id]) }}" class="btn btn-secondary btn-sm">
        <i class="fa fa-arrow-left"></i> Back
    </a>
    <div style="display:flex; gap:8px;">
        <a href="{{ route('admin.receipt.show', $receipt->id) }}" class="btn btn-default btn-sm">
            <i class="fa fa-edit"></i> Edit
        </a>
        <button onclick="window.print()" class="btn btn-sm btn-primary">
            <i class="fa fa-print"></i> Print / PDF
        </button>
    </div>
</div>

<div class="inv-sheet">

    <div class="inv-head">
        <div class="inv-brand">
            @if(file_exists(public_path('assets/img/logo.png')))
                <img src="{{ asset('assets/img/logo.png') }}" alt="HD Accountancy">
            @else
                <div class="inv-brand-name">HD Accountancy</div>
            @endif
            <div class="inv-brand-sub">Professional Accounting Services<br>United Kingdom</div>
        </div>
        <div class="inv-doc">
            <h1>INVOICE</h1>
            <div class="ref">{{ $receipt->receipt_number }}</div>
            <span class="inv-stamp">{{ $stamp }}</span>
        </div>
    </div>

    <hr class="inv-rule">

    <div class="inv-cols">
        <div>
            <div class="inv-h">Bill To</div>
            <strong>{{ $clientName }}</strong>
            @if($client?->business_name)<br>{{ $client->business_name }}@endif
            @if($client?->company_name)<br>{{ $client->company_name }}@endif
            @if($client?->company_name && $client?->company_number) (No. {{ $client->company_number }})@endif
            @foreach($cAddr as $line)<br>{{ $line }}@endforeach
            @if($client?->email)<br>{{ $client->email }}@endif
            @if($client?->phone)<br>Tel: {{ $client->phone }}@endif
        </div>
        <div>
            <table class="inv-meta">
                <tr><td>Invoice No.</td><td>{{ $detail?->invoice_number ?? '—' }}</td></tr>
                <tr><td>Invoice Date</td><td>{{ $invoiceDate }}</td></tr>
                <tr><td>Due Date</td><td>{{ $dueDate }}</td></tr>
                <tr><td>Status</td><td>{{ ucfirst(str_replace('_', ' ', $receipt->status)) }}</td></tr>
                <tr><td>Payment</td><td>@if($detail?->paid) Paid — {{ ucfirst($detail->payment_method ?? '—') }} @else Unpaid @endif</td></tr>
            </table>
        </div>
    </div>

    <hr class="inv-rule-thin">

    <table class="inv-items">
        <thead>
            <tr>
                <th style="width:28px;">#</th>
                <th>Description</th>
                <th style="width:24%;">Account Head</th>
                <th style="width:12%;">Tax</th>
                <th class="n" style="width:14%;">Amount (£)</th>
            </tr>
        </thead>
        <tbody>
            @if($detail)
            <tr>
                <td>1</td>
                <td>
                    {{ $detail->description ?: ($receipt->notes ?: 'Accounting Service') }}
                    @if($detail->invoice_number)<br><small>Ref: {{ $detail->invoice_number }}</small>@endif
                </td>
                <td>
                    @if($detail->accountHead)
                        {{ $detail->accountHead->name }}<br>
                        <small>{{ $detail->accountHead->code }}@if($detail->accountHead->accountType) · {{ $detail->accountHead->accountType->name }}@endif</small>
                    @else
                        —
                    @endif
                </td>
                <td>
                    @if($detail->accountHead?->taxRate)
                        {{ $detail->accountHead->taxRate->name }}<br><small>{{ $detail->accountHead->taxRate->rate }}%</small>
                    @else
                        —
                    @endif
                </td>
                <td class="n">{{ number_format($detail->net_amount ?? 0, 2) }}</td>
            </tr>
            @else
            <tr><td colspan="5" style="text-align:center; padding:28px 0;">No details added yet.</td></tr>
            @endif
        </tbody>
    </table>

    <div class="inv-totalrow">
        <div class="inv-words">Amount in words: {{ $invWords($detail?->total_amount ?? 0) }}</div>
        <table class="inv-totals">
            <tr><td>Subtotal</td><td>£{{ number_format($detail?->net_amount ?? 0, 2) }}</td></tr>
            @if(($detail?->tax_amount ?? 0) > 0)
            <tr><td>Tax</td><td>£{{ number_format($detail->tax_amount, 2) }}</td></tr>
            @endif
            @if(($detail?->vat_amount ?? 0) > 0)
            <tr><td>VAT</td><td>£{{ number_format($detail->vat_amount, 2) }}</td></tr>
            @endif
            <tr class="inv-grand"><td>Total</td><td>£{{ number_format($detail?->total_amount ?? 0, 2) }}</td></tr>
        </table>
    </div>

    @if($receipt->supplier)
        <hr class="inv-rule-thin">
        <p class="inv-note-h">Supplier</p>
        <div>{{ $receipt->supplier }}</div>
    @endif

    @if($receipt->notes)
        <hr class="inv-rule-thin">
        <p class="inv-note-h">Notes</p>
        <div style="white-space:pre-line;">{{ $receipt->notes }}</div>
    @endif

    <div class="inv-sign">
        <div><div class="line">Prepared by / Date</div></div>
        <div><div class="line">Checked by / Date</div></div>
        <div><div class="line">Received by / Date</div></div>
    </div>

    <div class="inv-foot">
        HD Accountancy &middot; Professional Accounting Services &middot; United Kingdom<br>
        Ref {{ $receipt->receipt_number }} &middot; Generated {{ now()->format('d M Y H:i') }}
    </div>

</div>
@endsection
