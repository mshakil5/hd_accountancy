@extends('admin.layouts.admin')

@section('content')
<style>
.bill-topbar {
    max-width: 740px;
    margin: 24px auto 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.bill-page {
    max-width: 740px;
    margin: 0 auto 48px;
    background: #fff;
    box-shadow: 0 1px 8px rgba(0,0,0,0.1);
    font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Arial, sans-serif;
    color: #222;
    line-height: 1.5;
}

/* ── Header ── */
.bill-header {
    background: #1e293b;
    padding: 36px 48px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.bill-title {
    font-size: 28px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #fff;
}

.bill-ref {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 4px;
}

.bill-brand {
    text-align: right;
}

.bill-brand-name {
    font-size: 18px;
    font-weight: 700;
    color: #fff;
}

.bill-brand-sub {
    font-size: 11px;
    color: #94a3b8;
    line-height: 1.6;
    margin-top: 2px;
}

/* ── Accent ── */
.bill-accent {
    height: 3px;
    background: #1e293b;
}

/* ── Body ── */
.bill-body {
    padding: 32px 48px;
}

/* ── Status text ── */
.bill-status-text {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.bill-status-pending   { color: #b45309; }
.bill-status-to_review { color: #1d4ed8; }
.bill-status-ready     { color: #15803d; }
.bill-status-archived  { color: #6b7280; }
.bill-status-cancelled { color: #dc2626; }

/* ── Meta row ── */
.bill-meta {
    display: flex;
    justify-content: space-between;
    margin-bottom: 28px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e5e7eb;
}

.bill-meta-item {
    text-align: center;
    flex: 1;
}

.bill-meta-item:not(:last-child) {
    border-right: 1px solid #e5e7eb;
}

.bill-meta-key {
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: #6b7280;
    margin-bottom: 4px;
}

.bill-meta-val {
    font-size: 13px;
    font-weight: 600;
    color: #111827;
}

/* ── Addresses ── */
.bill-addresses {
    display: flex;
    justify-content: space-between;
    margin-bottom: 28px;
    padding-bottom: 24px;
    border-bottom: 1px solid #e5e7eb;
}

.bill-address-block {
    width: 48%;
}

.bill-address-label {
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: #6b7280;
    margin-bottom: 6px;
}

.bill-address-name {
    font-size: 15px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 2px;
}

.bill-address-detail {
    font-size: 12px;
    color: #4b5563;
    line-height: 1.7;
}

/* ── Table ── */
.bill-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 28px;
}

.bill-table thead th {
    padding: 10px 12px;
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #fff;
    background: #1e293b;
    text-align: left;
}

.bill-table thead th:first-child { border-radius: 4px 0 0 0; }
.bill-table thead th:last-child { text-align: right; border-radius: 0 4px 0 0; }

.bill-table tbody td {
    padding: 14px 12px;
    font-size: 13px;
    color: #1f2937;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: top;
}

.bill-table tbody td:last-child {
    text-align: right;
    font-weight: 600;
}

.bill-table tbody tr:nth-child(even) { background: #f9fafb; }

.bill-item-code {
    font-weight: 600;
    color: #111827;
}

.bill-item-sub {
    font-size: 11px;
    color: #6b7280;
    margin-top: 2px;
}

/* ── Totals ── */
.bill-totals {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 28px;
}

.bill-totals-table {
    width: 260px;
}

.bill-totals-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 12px;
    font-size: 12px;
    color: #4b5563;
}

.bill-totals-row:nth-child(odd) { background: #f9fafb; }

.bill-totals-row span:last-child {
    font-weight: 600;
    color: #111827;
}

.bill-totals-total {
    display: flex;
    justify-content: space-between;
    padding: 12px;
    font-size: 14px;
    font-weight: 700;
    background: #1e293b;
    color: #fff;
    border-radius: 0 0 4px 4px;
}

/* ── Supplier ── */
.bill-supplier {
    margin-bottom: 20px;
    padding: 12px 16px;
    background: #f8fafc;
    border-left: 3px solid #1e293b;
    border-radius: 0 4px 4px 0;
}

.bill-supplier-label {
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #6b7280;
    margin-bottom: 4px;
}

.bill-supplier-text {
    font-size: 13px;
    font-weight: 600;
    color: #111827;
}

/* ── Notes ── */
.bill-notes {
    margin-bottom: 24px;
    padding: 12px 16px;
    background: #f8fafc;
    border-left: 3px solid #6b7280;
    border-radius: 0 4px 4px 0;
}

.bill-notes-label {
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #6b7280;
    margin-bottom: 4px;
}

.bill-notes-text {
    font-size: 12px;
    color: #374151;
    line-height: 1.6;
    white-space: pre-line;
}

/* ── Footer ── */
.bill-footer {
    padding: 16px 48px;
    background: #f8fafc;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
    color: #374151;
}

.bill-footer-paid {
    color: #15803d;
    font-weight: 600;
}

.bill-footer-unpaid {
    color: #b45309;
    font-weight: 600;
}

/* ── Print ── */
@media print {
    @page { size: A4; margin: 10mm; }

    html, body {
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
        width: 100% !important;
    }

    body > * { visibility: hidden !important; }

    .bill-page,
    .bill-page * {
        visibility: visible !important;
    }

    /* Hide ALL AdminLTE chrome + topbar */
    .main-sidebar, .main-header, .main-footer,
    .content-header, .content-wrapper, .wrapper,
    .app-sidebar, .app-header, .app-footer,
    .main-sidebar-dark, .nav-sidebar, .sidebar,
    .navbar, .main-sidebar .brand-link,
    .no-print, .bill-topbar {
        display: none !important;
        visibility: hidden !important;
        margin: 0 !important;
        padding: 0 !important;
        height: 0 !important;
        overflow: hidden !important;
    }

    .bill-page {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        margin: 0 !important;
        padding: 0;
        box-shadow: none;
        page-break-inside: avoid;
    }

    .bill-header { padding: 24px 36px !important; }
    .bill-body { padding: 20px 36px !important; }
    .bill-footer { padding: 12px 36px !important; }
    .bill-meta { margin-bottom: 16px !important; padding-bottom: 14px !important; }
    .bill-addresses { margin-bottom: 16px !important; padding-bottom: 16px !important; }
    .bill-table tbody td { padding: 10px 12px !important; }
    .bill-totals { margin-bottom: 16px !important; }
    .bill-notes { margin-bottom: 12px !important; }
    .bill-supplier { margin-bottom: 12px !important; }

    .bill-header,
    .bill-accent,
    .bill-table thead th,
    .bill-table tbody tr:nth-child(even),
    .bill-totals-row:nth-child(odd),
    .bill-totals-total,
    .bill-notes,
    .bill-supplier,
    .bill-footer,
    .bill-status-text,
    .bill-footer-paid,
    .bill-footer-unpaid {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
}
</style>

<div class="bill-topbar no-print">
    <a href="{{ route('admin.receipt.index') }}" class="btn btn-secondary btn-sm">
        <i class="fa fa-arrow-left"></i> Back
    </a>
    <div style="display:flex; gap:8px;">
        <a href="{{ route('admin.receipt.show', $receipt->id) }}" class="btn btn-default btn-sm">
            <i class="fa fa-edit"></i> Edit
        </a>
        <button onclick="window.print()" class="btn btn-sm" style="background:#1e293b; color:#fff;">
            <i class="fa fa-print"></i> Print / PDF
        </button>
    </div>
</div>

<div class="bill-page">

    <div class="bill-header">
        <div>
            <div class="bill-title">Invoice</div>
            <div class="bill-ref">{{ $receipt->receipt_number }}</div>
        </div>
        <div class="bill-brand">
            <div class="bill-brand-name">HD Accountancy</div>
            <div class="bill-brand-sub">
                Professional Accounting Services<br>
                United Kingdom
            </div>
        </div>
    </div>

    <div class="bill-accent"></div>

    <div class="bill-body">

        @php
            $invoiceDate = $receipt->detail?->invoice_date
                ? \Carbon\Carbon::parse($receipt->detail->invoice_date)->format('d M Y')
                : ($receipt->receipt_date ? $receipt->receipt_date->format('d M Y') : '—');
            $dueDate = $receipt->detail?->due_date
                ? \Carbon\Carbon::parse($receipt->detail->due_date)->format('d M Y')
                : '—';
        @endphp
        <div class="bill-meta">
            <div class="bill-meta-item">
                <div class="bill-meta-key">Invoice No</div>
                <div class="bill-meta-val">{{ $receipt->detail?->invoice_number ?? '—' }}</div>
            </div>
            <div class="bill-meta-item">
                <div class="bill-meta-key">Date</div>
                <div class="bill-meta-val">{{ $invoiceDate }}</div>
            </div>
            <div class="bill-meta-item">
                <div class="bill-meta-key">Due Date</div>
                <div class="bill-meta-val">{{ $dueDate }}</div>
            </div>
            <div class="bill-meta-item">
                <div class="bill-meta-key">Status</div>
                <div class="bill-meta-val">
                    <span class="bill-status-text bill-status-{{ $receipt->status }}">
                        {{ str_replace('_', ' ', $receipt->status) }}
                    </span>
                </div>
            </div>
        </div>

        @php
            $client     = $receipt->client;
            $clientName = $client ? trim(($client->name ?? '') . ' ' . ($client->last_name ?? '')) : '—';
        @endphp
        <div class="bill-addresses">
            <div class="bill-address-block">
                <div class="bill-address-label">Bill To</div>
                <div class="bill-address-name">{{ $clientName }}</div>
                <div class="bill-address-detail">
                    @if($client?->business_name) {{ $client->business_name }}<br>@endif
                    @if($client?->city) {{ $client->city }}@endif
                    @if($client?->postcode) &middot; {{ $client->postcode }}@endif
                    @if($client?->country)<br>{{ $client->country }}@endif
                </div>
            </div>
            <div class="bill-address-block" style="text-align:right;">
                <div class="bill-address-label">From</div>
                <div class="bill-address-name">HD Accountancy</div>
                <div class="bill-address-detail">
                    Accounting Department<br>
                    United Kingdom
                </div>
            </div>
        </div>

        <table class="bill-table">
            <thead>
                <tr>
                    <th style="width:40%">Description</th>
                    <th style="width:30%">Account Head</th>
                    <th style="width:15%">Tax</th>
                    <th style="width:15%">Amount</th>
                </tr>
            </thead>
            <tbody>
                @if($receipt->detail)
                <tr>
                    <td>
                        {{ $receipt->detail->description ?: ($receipt->notes ?: 'Accounting Service') }}
                        @if($receipt->detail->invoice_number)
                            <div class="bill-item-sub">Ref: {{ $receipt->detail->invoice_number }}</div>
                        @endif
                    </td>
                    <td>
                        @if($receipt->detail->accountHead)
                            <div class="bill-item-code">{{ $receipt->detail->accountHead->name }}</div>
                            @if($receipt->detail->accountHead->accountType)
                                <div class="bill-item-sub">{{ $receipt->detail->accountHead->code }} &middot; {{ ucfirst($receipt->detail->accountHead->accountType->category) }}</div>
                            @endif
                        @else
                            <span style="color:#ccc;">—</span>
                        @endif
                    </td>
                    <td>
                        @if($receipt->detail->accountHead?->taxRate)
                            {{ $receipt->detail->accountHead->taxRate->name }}
                            <div class="bill-item-sub">{{ $receipt->detail->accountHead->taxRate->rate }}%</div>
                        @else
                            <span style="color:#ccc;">—</span>
                        @endif
                    </td>
                    <td>£{{ number_format($receipt->detail->net_amount ?? 0, 2) }}</td>
                </tr>
                @else
                <tr>
                    <td colspan="4" style="text-align:center; color:#9ca3af; padding:40px 0;">
                        No details added yet.
                    </td>
                </tr>
                @endif
            </tbody>
        </table>

        <div class="bill-totals">
            <div class="bill-totals-table">
                <div class="bill-totals-row">
                    <span>Subtotal</span>
                    <span>£{{ number_format($receipt->detail?->net_amount ?? 0, 2) }}</span>
                </div>
                @if(($receipt->detail?->tax_amount ?? 0) > 0)
                <div class="bill-totals-row">
                    <span>Tax</span>
                    <span>£{{ number_format($receipt->detail->tax_amount, 2) }}</span>
                </div>
                @endif
                @if(($receipt->detail?->vat_amount ?? 0) > 0)
                <div class="bill-totals-row">
                    <span>VAT</span>
                    <span>£{{ number_format($receipt->detail->vat_amount, 2) }}</span>
                </div>
                @endif
                <div class="bill-totals-total">
                    <span>Total</span>
                    <span>£{{ number_format($receipt->detail?->total_amount ?? 0, 2) }}</span>
                </div>
            </div>
        </div>

        @if($receipt->supplier)
            <div class="bill-supplier">
                <div class="bill-supplier-label">Supplier</div>
                <div class="bill-supplier-text">{{ $receipt->supplier }}</div>
            </div>
        @endif

        @if($receipt->notes)
            <div class="bill-notes">
                <div class="bill-notes-label">Notes</div>
                <div class="bill-notes-text">{{ $receipt->notes }}</div>
            </div>
        @endif

    </div>

    <div class="bill-footer">
        <div>
            Payment: @if($receipt->detail?->paid)
                {{ ucfirst($receipt->detail->payment_method ?? '—') }}
            @else
                <span class="bill-footer-unpaid">Not yet paid</span>
            @endif
        </div>
        <div>
            @if($receipt->status === 'cancelled')
                <span style="color:#dc2626; font-weight:600;">Cancelled</span>
            @elseif($receipt->detail?->paid)
                <span class="bill-footer-paid">Paid</span>
            @else
                <span class="bill-footer-unpaid">Unpaid</span>
            @endif
        </div>
    </div>

</div>
@endsection
