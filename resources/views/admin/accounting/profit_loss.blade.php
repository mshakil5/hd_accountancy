@extends('admin.layouts.admin')

@section('content')
    <style>
        .filter-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 20px;
        }

        /* Report body follows the exported P&L: plain, print-like. */
        .statement {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 34px 40px 26px;
            font-family: Arial, Helvetica, sans-serif;
            color: #1f2937;
        }

        .stmt-title {
            font-size: 21px;
            font-weight: 700;
            margin: 0;
        }

        .stmt-business {
            font-size: 14px;
            margin-top: 14px;
        }

        .stmt-period {
            font-size: 13px;
            color: #4b5563;
            margin-top: 2px;
        }

        .stmt-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 22px;
        }

        .stmt-table th {
            font-size: 13px;
            font-weight: 400;
            color: #374151;
            padding: 0 0 6px;
            border-bottom: 1px solid #9ca3af;
        }

        .stmt-table th.num,
        .stmt-table td.num {
            text-align: right;
            white-space: nowrap;
            width: 150px;
        }

        .stmt-table td {
            font-size: 13px;
            padding: 5px 0;
            vertical-align: top;
        }

        .stmt-table tr.sec td {
            font-weight: 700;
            padding-top: 18px;
        }

        .stmt-table tr.head td:first-child {
            padding-left: 18px;
        }

        .stmt-table tr.head {
            cursor: pointer;
        }

        .stmt-table tr.head:hover td {
            background: #f9fafb;
        }

        .stmt-table tr.tot td {
            font-weight: 700;
            border-top: 1px solid #d1d5db;
            padding-top: 7px;
        }

        .stmt-table tr.grand td {
            font-weight: 700;
            border-top: 1px solid #6b7280;
            border-bottom: 3px double #6b7280;
            padding: 7px 0;
        }

        .stmt-table tr.empty td {
            color: #9ca3af;
            font-style: italic;
            padding-left: 18px;
        }

        .stmt-foot {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #6b7280;
            display: flex;
            justify-content: space-between;
        }

        .stmt-note {
            margin-top: 12px;
            font-size: 12px;
            color: #6b7280;
        }

        .drill-wrap {
            background: #f9fafb;
            padding: 6px 10px 10px 18px;
            border-left: 3px solid #d1d5db;
        }

        .drill-wrap table {
            width: 100%;
            font-size: 12px;
        }

        .drill-wrap th {
            font-weight: 600;
            color: #4b5563;
            border-bottom: 1px solid #e5e7eb;
            padding: 4px 6px;
        }

        .drill-wrap td {
            padding: 4px 6px;
            border-bottom: 1px solid #f3f4f6;
        }

        #reportOutput {
            display: none;
        }

        .select2-container .select2-selection--single {
            height: 38px !important;
            border: 1px solid #ced4da !important;
            border-radius: .25rem !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 24px !important;
            padding-left: 0 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
        }

        @media print {

            .filter-section,
            .no-print {
                display: none !important;
            }

            .statement {
                border: none;
                padding: 0;
            }
        }
    </style>

    <section class="content py-4">
        <div class="container-fluid">

            <div class="d-flex align-items-center justify-content-between mb-3 no-print">
                <h4 class="mb-0"><i class="fa fa-chart-line mr-2"></i> Profit & Loss</h4>
                <div>
                    <button class="btn btn-sm btn-outline-success" id="exportCsvBtn"><i class="fa fa-file-csv"></i>
                        CSV</button>
                    <button class="btn btn-sm btn-outline-success" id="exportXlsxBtn"><i class="fa fa-file-excel"></i>
                        Excel</button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="fa fa-print"></i>
                        Print</button>
                </div>
            </div>

            <div class="filter-section no-print">
                <div class="row align-items-end">
                    <div class="col-md-2">
                        <label class="small font-weight-bold">Period</label>
                        <select class="form-control" id="periodSelect">
                            <option value="3m">Last 3 months</option>
                            <option value="6m">Last 6 months</option>
                            <option value="1y" selected>Last 1 year</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small font-weight-bold">Client</label>
                        <select class="form-control select2" id="clientSelect" style="width:100%">
                            <option value="">Select Client</option>
                            @foreach ($clients as $c)
                                <option value="{{ $c->id }}">{{ trim($c->first_name . ' ' . $c->last_name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small font-weight-bold">Business</label>
                        <select class="form-control" id="businessSelect">
                            <option value="">Select Business</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small font-weight-bold">Payment Method</label>
                        <select class="form-control" id="paymentMethod">
                            <option value="">Total</option>
                            <option value="cash">Cash</option>
                            <option value="bank">Bank</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small font-weight-bold">Amount</label>
                        <select class="form-control" id="basisSelect">
                            <option value="gross">Gross (with VAT)</option>
                            <option value="net">Net (without VAT)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary mt-3" id="generateBtn" style="width:50%; white-space:nowrap;"><i class="fa fa-search"></i>
                            Search</button>
                    </div>
                </div>
            </div>

            <div id="reportOutput">
                <div class="statement">
                    <h4 class="stmt-title">Profit and Loss</h4>
                    <div class="stmt-business" id="stmtBusiness"></div>
                    <div class="stmt-period" id="stmtPeriod"></div>

                    <table class="stmt-table">
                        <thead>
                            <tr id="stmtHeadRow">
                                <th class="stmt-account"></th>
                            </tr>
                        </thead>
                        <tbody id="reportBody"></tbody>
                    </table>

                    <div class="stmt-note" id="stmtNote"></div>

                    <div class="stmt-foot">
                        <span id="stmtFootLeft"></span>
                        <span id="stmtFootRight"></span>
                    </div>
                </div>
            </div>

        </div>
    </section>
@endsection

@section('script')
    <script>
        $(function() {
            var currentFilters = {};

            function periodRange() {
                var p = $('#periodSelect').val();
                var to = new Date();
                var from = new Date();
                if (p === '3m') {
                    from.setMonth(from.getMonth() - 3);
                } else if (p === '6m') {
                    from.setMonth(from.getMonth() - 6);
                } else {
                    from.setFullYear(from.getFullYear() - 1);
                }
                var iso = function(d) {
                    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d
                        .getDate()).padStart(2, '0');
                };
                return {
                    from: iso(from),
                    to: iso(to)
                };
            }

            $('#clientSelect').select2({
                placeholder: 'Select Client',
                allowClear: true
            }).on('change', function() {
                var credId = $(this).val();
                $('#businessSelect').html('<option value="">Select Business</option>');
                if (!credId) return;
                $.get("{{ url('/admin/accounting/get-businesses') }}", {
                    credential_id: credId
                }, function(data) {
                    $.each(data, function(i, b) {
                        $('#businessSelect').append('<option value="' + b.id + '">' + b.text +
                            '</option>');
                    });
                });
            });

            function filters() {
                var r = periodRange();
                return {
                    from: r.from,
                    to: r.to,
                    client_credential_id: $('#clientSelect').val(),
                    client_id: $('#businessSelect').val(),
                    payment_method: $('#paymentMethod').val(),
                    basis: $('#basisSelect').val(),
                };
            }

            $('#generateBtn').click(function() {
                if (!$('#clientSelect').val() || !$('#businessSelect').val()) {
                    toastr.warning('Select a client and a business first.');
                    return;
                }
                currentFilters = filters();
                loadReport(currentFilters);
            });

            function exportUrl(format) {
                return "{{ url('/admin/accounting/profit-loss/export') }}?format=" + format + '&' + $.param(
                    filters());
            }

            $('#exportCsvBtn').click(function() {
                window.location = exportUrl('csv');
            });
            $('#exportXlsxBtn').click(function() {
                window.location = exportUrl('xlsx');
            });

            // Print-style amounts: 1,234.56 / (1,234.56) / -
            function amt(n) {
                var v = parseFloat(n || 0);
                if (Math.abs(v) < 0.005) return '-';
                var s = Math.abs(v).toLocaleString('en-GB', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                return v < 0 ? '(' + s + ')' : s;
            }

            function cells(values, cls) {
                var out = '';
                $.each(values, function(i, v) {
                    out += '<td class="num' + (cls ? ' ' + cls : '') + '">' + amt(v) + '</td>';
                });
                return out;
            }

            function headRow(head) {
                var clickable = head.head_id ? ' data-head-id="' + head.head_id +
                    '" title="Click for the bank transactions behind this head"' : '';
                return '<tr class="head"' + clickable + '><td>' + head.name + '</td>' + cells(head.amounts) +
                    '</tr>';
            }

            function sectionRow(title, cols) {
                return '<tr class="sec"><td>' + title + '</td>' + '<td></td>'.repeat(cols) + '</tr>';
            }

            function receiptCard(r) {
                if (!r) return '<span class="text-muted">No receipt attached to this bank line.</span>';
                return '<strong>Receipt ' + (r.number || ('#' + r.id)) + '</strong>' +
                    ' &middot; ' + (r.supplier || '-') +
                    ' &middot; Invoice date ' + (r.invoice_date || '-') +
                    ' &middot; Status ' + (r.status || '-') +
                    '<br>Net £' + parseFloat(r.net || 0).toFixed(2) +
                    ' &middot; VAT £' + parseFloat(r.vat || 0).toFixed(2) +
                    ' &middot; Gross £' + parseFloat(r.gross || 0).toFixed(2) +
                    ' &middot; Applied £' + parseFloat(r.applied || 0).toFixed(2) +
                    ' &middot; <a href="' + r.url + '" target="_blank">Open receipt</a>';
            }

            function loadReport(f) {
                $('#generateBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
                $.get("{{ url('/admin/accounting/profit-loss/data') }}", f, function(d) {
                    var s = d.statement;
                    if (!s) {
                        $('#reportBody').html(
                            '<tr><td colspan="3" class="text-center text-muted py-4">Select a client and a business.</td></tr>'
                        );
                        $('#reportOutput').fadeIn();
                        return;
                    }

                    // Column headers: one per period (current + comparative).
                    var head = '<th class="stmt-account"></th>';
                    $.each(s.columns, function(i, label) {
                        var p = s.periods[i] || {};
                        head += '<th class="num" title="' + (p.from || '') + ' to ' + (p.to || '') +
                            '">' + label + '</th>';
                    });
                    $('#stmtHeadRow').html(head);

                    var body = '';
                    $.each(s.sections, function(i, section) {
                        body += sectionRow(section.title, s.columns.length);
                        if (!section.heads.length) {
                            body += '<tr class="empty"><td colspan="' + (s.columns.length + 1) +
                                '">No reconciled transactions.</td></tr>';
                        }
                        $.each(section.heads, function(j, h) {
                            body += headRow(h);
                        });
                        body += '<tr class="tot"><td>Total ' + section.title + '</td>' + cells(
                            section.totals) + '</tr>';
                        if (section.key === 'direct') {
                            body += '<tr class="grand"><td>Gross Profit</td>' + cells(s.gross_profit) +
                                '</tr>';
                        }
                    });

                    body += '<tr class="grand"><td>Operating Profit</td>' + cells(s.operating_profit) +
                        '</tr>';
                    body += '<tr class="tot"><td>Profit on Ordinary Activities Before Taxation</td>' + cells(
                        s.profit_before_tax) + '</tr>';
                    body += '<tr class="grand"><td>Profit after Taxation</td>' + cells(s.profit_after_tax) +
                        '</tr>';

                    $('#reportBody').html(body);
                    $('#stmtBusiness').text(d.business_name);
                    $('#stmtPeriod').text('For the period ' + d.from + ' to ' + d.to);
                    $('#stmtNote').text(
                        'Statement-based: only bank statement lines reconciled to a receipt. Amounts are ' +
                        (s.basis === 'net' ? 'net of VAT.' : 'gross, including VAT.'));
                    $('#stmtFootLeft').text('Profit and Loss  ' + d.business_name);
                    $('#stmtFootRight').text(@json(now()->format('d/m/Y')));
                    $('#reportOutput').fadeIn();
                }).always(function() {
                    $('#generateBtn').prop('disabled', false).html('<i class="fa fa-search"></i> Search');
                });
            }

            // Level 1: head row -> the bank transactions behind it.
            // Level 2: a bank transaction -> the receipt it was matched to.
            $('#reportBody').on('click', 'tr.head[data-head-id]', function() {
                var $row = $(this);
                if ($row.next().hasClass('breakdown-row')) {
                    $row.next().toggle();
                    return;
                }
                var headId = $row.data('head-id');
                var span = $row.children('td').length;
                var $detail = $('<tr class="breakdown-row"><td colspan="' + span +
                    '"><div class="drill-wrap"><span class="text-muted"><i class="fa fa-spinner fa-spin"></i> Loading transactions…</span></div></td></tr>'
                );
                $row.after($detail);
                $.get("{{ url('/admin/accounting/statement-drill') }}", $.extend({
                    head_id: headId
                }, currentFilters), function(res) {
                    var html = '<div class="font-weight-bold mb-1">' + (res.head.code ? res.head
                            .code + ' - ' : '') + res.head.name + ' — ' + res.count +
                        ' bank transaction(s), £' + parseFloat(res.total || 0).toFixed(2) + '</div>';
                    if (!res.transactions.length) {
                        html += '<div class="text-muted">No reconciled transactions in this period.</div>';
                    } else {
                        html +=
                            '<table><thead><tr><th>Date</th><th>Bank account</th><th>Description</th><th>Receipt</th><th class="text-right">Amount</th></tr></thead><tbody>';
                        $.each(res.transactions, function(i, t) {
                            html += '<tr class="drill-txn" data-txn="' + i +
                                '" style="cursor:pointer;">' +
                                '<td>' + (t.date || '-') + '</td>' +
                                '<td>' + (t.bank_account || '-') + '</td>' +
                                '<td>' + (t.description || '-') + '</td>' +
                                '<td>' + (t.receipt ? (t.receipt.number || ('#' + t.receipt.id)) : '-') +
                                '</td>' +
                                '<td class="text-right">£' + parseFloat(t.amount || 0).toFixed(2) +
                                '</td></tr>';
                            html += '<tr class="drill-receipt" data-txn="' + i +
                                '" style="display:none;"><td colspan="5" style="background:#fff;">' +
                                receiptCard(t.receipt) + '</td></tr>';
                        });
                        html += '</tbody></table>';
                    }
                    $detail.find('.drill-wrap').html(html);
                }).fail(function() {
                    $detail.find('.drill-wrap').html(
                        '<span class="text-danger">Failed to load transactions.</span>');
                });
            });

            $('#reportBody').on('click', 'tr.drill-txn', function() {
                var i = $(this).data('txn');
                $('tr.drill-receipt[data-txn="' + i + '"]').toggle();
            });
        });
    </script>
@endsection
