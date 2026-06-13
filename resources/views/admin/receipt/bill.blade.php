@extends('admin.layouts.admin')

@section('content')
<style>
/* ── Screen layout container ── */
.bill-topbar {
    max-width: 680px;
    margin: 24px auto 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.bill-page {
    max-width: 680px;
    margin: 0 auto 48px;
    background: #fff;
    box-shadow: 0 2px 32px rgba(0,0,0,0.13);
    font-family: 'Segoe UI', Arial, sans-serif;
}

/* ── Dark header & Teal strip ── */
.bill-header-row {
    background: #0f2544;
    padding: 28px 36px 22px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.bill-label-invoice {
    font-size: 28px;
    font-weight: 800;
    color: #fff;
    letter-spacing: 3px;
    line-height: 1;
}

.bill-receipt-no {
    font-size: 11px;
    color: #7fa3cc;
    margin-top: 5px;
}

.bill-company-block {
    text-align: right;
}

.bill-company-name {
    font-size: 17px;
    font-weight: 700;
    color: #fff;
}

.bill-company-sub {
    font-size: 11px;
    color: #7fa3cc;
    line-height: 1.8;
    margin-top: 3px;
}

.bill-accent {
    height: 4px;
    background: linear-gradient(90deg, #1a9e78, #26c9a0);
}

/* ── Main body wrapper ── */
.bill-inner {
    padding: 28px 36px;
}

/* ── Status stamp ── */
.bill-status-stamp-row {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 20px;
}

/* ── Client / Processed Box ── */
.bill-parties {
    display: flex;
    border: 1px solid #e4e9ee;
    margin-bottom: 0;
}

.bill-party {
    flex: 1;
    padding: 14px 18px;
}

.bill-party:first-child {
    border-right: 1px solid #e4e9ee;
}

.bill-party-label {
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: #1a9e78;
    margin-bottom: 6px;
}

.bill-party-name {
    font-size: 14px;
    font-weight: 700;
    color: #0f2544;
    margin-bottom: 2px;
}

.bill-party-sub {
    font-size: 11px;
    color: #6b7a8d;
    line-height: 1.7;
}

/* ── Meta items grid ── */
.bill-meta-row {
    display: flex;
    border: 1px solid #e4e9ee;
    border-top: none;
    margin-bottom: 24px;
}

.bill-meta-pill {
    flex: 1;
    padding: 10px 18px;
    border-right: 1px solid #e4e9ee;
}

.bill-meta-pill:last-child {
    border-right: none;
}

.bill-meta-pill-label {
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: #9aa5b4;
    margin-bottom: 3px;
}

.bill-meta-pill-value {
    font-size: 12px;
    font-weight: 600;
    color: #0f2544;
}

/* Dynamic status styles mapping to original HTML format inside the metadata row */
.status-pill-pending   { color: #b45309; font-weight: 700; }
.status-pill-to_review { color: #0369a1; font-weight: 700; }
.status-pill-ready     { color: #15803d; font-weight: 700; }
.status-pill-archived  { color: #374151; font-weight: 700; }
.status-pill-cancelled { color: #b91c1c; font-weight: 700; }

/* Global raw stamp box (Top right) */
.stamp-box {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    padding: 5px 14px;
    border-radius: 3px;
    border: 2px solid #9ca3af;
}
.stamp-pending   { border-color: #fef3c7; color: #b45309; }
.stamp-to_review { border-color: #e0f2fe; color: #0369a1; }
.stamp-ready     { border-color: #dcfce7; color: #15803d; }
.stamp-archived  { border-color: #9ca3af; color: #374151; }
.stamp-cancelled { border-color: #fee2e2; color: #b91c1c; }

/* ── Line items table ── */
.bill-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 0;
}

.bill-table thead tr {
    background: #0f2544;
}

.bill-table thead th {
    padding: 10px 12px;
    color: #b0c4d8;
    font-size: 9px;
    font-weight: 600;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    text-align: left;
}

.bill-table thead th:last-child { text-align: right; }

.bill-table tbody td {
    padding: 14px 12px;
    border-bottom: 1px solid #f0f4f8;
    color: #374151;
    font-size: 12px;
    vertical-align: top;
}

.bill-table tbody td:last-child {
    text-align: right;
}

.head-code {
    font-weight: 700;
    color: #0f2544;
    font-size: 12px;
}

.head-category {
    font-size: 10px;
    color: #9aa5b4;
    margin-top: 2px;
}

.tax-name { font-size: 12px; color: #374151; }
.tax-pct  { font-size: 10px; color: #9aa5b4; margin-top: 2px; }

.amount-val {
    font-size: 14px;
    font-weight: 700;
    color: #0f2544;
}

/* ── Totals block ── */
.bill-totals-wrap {
    border-top: 2px solid #0f2544;
    display: flex;
    justify-content: flex-end;
}

.bill-totals-box {
    width: 240px;
}

.totals-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 12px;
    font-size: 12px;
    color: #6b7a8d;
    border-bottom: 1px solid #f0f4f8;
}

.totals-row span:last-child { font-weight: 600; }

.totals-row-final {
    display: flex;
    justify-content: space-between;
    padding: 11px 12px;
    font-size: 13px;
    font-weight: 700;
    background: #0f2544;
    color: #fff;
}
.totals-row-final span:last-child { color: #1a9e78; }

/* ── Footer ── */
.bill-footer-row {
    padding: 16px 36px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid #e4e9ee;
    margin-top: 20px;
}

.bill-payment-label {
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: #9aa5b4;
    margin-bottom: 3px;
}
.bill-payment-value {
    font-size: 13px;
    font-weight: 600;
    color: #0f2544;
}

.pay-status-badge {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.8px;
    padding: 7px 20px;
    border-radius: 3px;
    text-transform: uppercase;
}
.pay-status-paid      { background: #dcfce7; color: #15803d; }
.pay-status-unpaid    { background: #fef9c3; color: #78350f; }
.pay-status-cancelled { background: #fee2e2; color: #b91c1c; }

/* ── Print styles ── */
@media print {
    @page {
        size: A4;
        margin: 14mm;
    }

    body * { visibility: hidden; }
    .bill-page, .bill-page * { visibility: visible; }

    .no-print,
    .main-header,
    .main-sidebar,
    .main-footer,
    .content-header,
    .bill-topbar { display: none !important; }

    body, .wrapper, .content-wrapper {
        background: #fff !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .bill-page {
        max-width: 100%;
        margin: 0;
        box-shadow: none;
        page-break-inside: avoid;
    }

    .bill-header-row { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .bill-accent { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .bill-table thead tr { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .totals-row-final { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .pay-status-badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

    .bill-table tbody tr { page-break-inside: avoid; }
    .bill-totals-wrap { page-break-inside: avoid; }
    .bill-footer-row  { page-break-inside: avoid; }
}
</style>

{{-- Top action bar (hidden in print) --}}
<div class="bill-topbar no-print">
    <a href="{{ route('admin.receipt.index') }}" class="btn btn-secondary btn-sm">
        <i class="fa fa-arrow-left"></i> Back to Inbox
    </a>
    <div style="display:flex; gap:8px;">
        <a href="{{ route('admin.receipt.show', $receipt->id) }}" class="btn btn-default btn-sm">
            <i class="fa fa-edit"></i> Edit Receipt
        </a>
        <button onclick="window.print()" class="btn btn-sm" style="background:#0f2544; color:#fff;">
            <i class="fa fa-print"></i> Print / PDF
        </button>
    </div>
</div>

<div class="bill-page">
    {{-- Header --}}
    <div class="bill-header-row">
        <div>
            <div class="bill-label-invoice">INVOICE</div>
            <div class="bill-receipt-no">{{ $receipt->receipt_number }}</div>
        </div>
        <div class="bill-company-block">
            <div class="bill-company-name">HD Accountancy</div>
            <div class="bill-company-sub">
                Professional Accounting Services<br>
                United Kingdom
            </div>
        </div>
    </div>

    {{-- Teal Strip --}}
    <div class="bill-accent"></div>

    <div class="bill-inner">
        {{-- Status Stamp Row --}}
        <div class="bill-status-stamp-row">
            <div class="stamp-box stamp-{{ $receipt->status }}">
                {{ str_replace('_', ' ', $receipt->status) }}
            </div>
        </div>

        {{-- From / To --}}
        @php
            $client     = $receipt->client;
            $clientName = $client ? trim(($client->name ?? '') . ' ' . ($client->last_name ?? '')) : '—';
        @endphp
        <div class="bill-parties">
            <div class="bill-party">
                <div class="bill-party-label">From (Client)</div>
                <div class="bill-party-name">{{ $clientName }}</div>
                <div class="bill-party-sub">
                    @if($client?->business_name) {{ $client->business_name }}<br>@endif
                    @if($client?->city) {{ $client->city }}@if($client?->postcode), {{ $client->postcode }}@endif<br>@endif
                    @if($client?->country) {{ $client->country }}@endif
                </div>
            </div>
            <div class="bill-party">
                <div class="bill-party-label">Processed By</div>
                <div class="bill-party-name">HD Accountancy</div>
                <div class="bill-party-sub">
                    Accounting Department<br>
                    United Kingdom
                </div>
            </div>
        </div>

        {{-- Meta row with identical styling --}}
        @php
            $statusClass = 'status-pill-' . $receipt->status;
            $statusLabel = ucfirst(str_replace('_', ' ', $receipt->status));
            $invoiceDate = $receipt->detail?->invoice_date
                ? \Carbon\Carbon::parse($receipt->detail->invoice_date)->format('d M Y')
                : ($receipt->receipt_date ? $receipt->receipt_date->format('d M Y') : '—');
            $dueDate = $receipt->detail?->due_date
                ? \Carbon\Carbon::parse($receipt->detail->due_date)->format('d M Y')
                : '—';
        @endphp
        <div class="bill-meta-row">
            <div class="bill-meta-pill">
                <div class="bill-meta-pill-label">Invoice No.</div>
                <div class="bill-meta-pill-value">{{ $receipt->detail?->invoice_number ?? '—' }}</div>
            </div>
            <div class="bill-meta-pill">
                <div class="bill-meta-pill-label">Receipt Ref.</div>
                <div class="bill-meta-pill-value">{{ $receipt->receipt_number }}</div>
            </div>
            <div class="bill-meta-pill">
                <div class="bill-meta-pill-label">Invoice Date</div>
                <div class="bill-meta-pill-value">{{ $invoiceDate }}</div>
            </div>
            <div class="bill-meta-pill">
                <div class="bill-meta-pill-label">Due Date</div>
                <div class="bill-meta-pill-value">{{ $dueDate }}</div>
            </div>
        </div>

        {{-- Line items Table --}}
        <table class="bill-table">
            <thead>
                <tr>
                    <th style="width:35%">Description</th>
                    <th style="width:28%">Account Head</th>
                    <th style="width:18%">Tax Rate</th>
                    <th style="width:19%">Amount</th>
                </tr>
            </thead>
            <tbody>
                @if($receipt->detail)
                <tr>
                    <td>{{ $receipt->detail->description ?: ($receipt->notes ?: 'Accounting Service') }}</td>
                    <td>
                        @if($receipt->detail->accountHead)
                            <div class="head-code">{{ $receipt->detail->accountHead->code }} — {{ $receipt->detail->accountHead->name }}</div>
                            @if($receipt->detail->accountHead->accountType)
                                <div class="head-category">{{ ucfirst($receipt->detail->accountHead->accountType->category) }}</div>
                            @endif
                        @else
                            <span style="color:#9aa5b4;">—</span>
                        @endif
                    </td>
                    <td>
                        @if($receipt->detail->accountHead?->taxRate)
                            <div class="tax-name">{{ $receipt->detail->accountHead->taxRate->name }}</div>
                            <div class="tax-pct">{{ $receipt->detail->accountHead->taxRate->rate }}%</div>
                        @else
                            <span style="color:#9aa5b4;">—</span>
                        @endif
                    </td>
                    <td>
                        <div class="amount-val">£{{ number_format($receipt->detail->net_amount ?? 0, 2) }}</div>
                    </td>
                </tr>
                @else
                <tr>
                    <td colspan="4" style="text-align:center; color:#9aa5b4; padding:32px 0;">
                        No details added yet — admin has not processed this receipt.
                    </td>
                </tr>
                @endif
            </tbody>
        </table>

        {{-- Totals Box --}}
        <div class="bill-totals-wrap">
            <div class="bill-totals-box">
                <div class="totals-row">
                    <span>Net Amount</span>
                    <span>£{{ number_format($receipt->detail?->net_amount ?? 0, 2) }}</span>
                </div>
                <div class="totals-row">
                    <span>Tax Amount</span>
                    <span>£{{ number_format($receipt->detail?->tax_amount ?? 0, 2) }}</span>
                </div>
                <div class="totals-row">
                    <span>VAT Amount</span>
                    <span>£{{ number_format($receipt->detail?->vat_amount ?? 0, 2) }}</span>
                </div>
                <div class="totals-row-final">
                    <span>Total Due</span>
                    <span>£{{ number_format($receipt->detail?->total_amount ?? 0, 2) }}</span>
                </div>
            </div>
        </div>

    </div>{{-- /.bill-inner --}}

    {{-- Footer Row --}}
    <div class="bill-footer-row">
        <div class="bill-payment-block">
            <div class="bill-payment-label">Payment Method</div>
            <div class="bill-payment-value">
                @if($receipt->detail?->paid)
                    {{ ucfirst($receipt->detail->payment_method ?? '—') }}
                @else
                    Not yet paid
                @endif
            </div>
        </div>

        <div>
            @if($receipt->status === 'cancelled')
                <span class="pay-status-badge pay-status-cancelled">Cancelled</span>
            @elseif($receipt->detail?->paid)
                <span class="pay-status-badge pay-status-paid">Paid</span>
            @else
                <span class="pay-status-badge pay-status-unpaid">Awaiting Payment</span>
            @endif
        </div>
    </div>

</div>
@endsection