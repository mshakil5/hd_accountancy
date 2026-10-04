@extends('admin.layouts.admin')

@section('content')
    <style>
        .report-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.07);
        }

        .filter-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 20px;
        }

        .bs-section-header {
            background: #0f2544;
            color: #fff;
            font-weight: 700;
            font-size: 12px;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 9px 16px;
        }

        .bs-row td {
            font-size: 12px;
            padding: 8px 16px;
            border-bottom: 1px solid #f0f4f8;
        }

        .bs-row:hover td {
            background: #f8fafc;
        }

        .bs-total td {
            font-weight: 700;
            font-size: 13px;
            padding: 10px 16px;
            background: #f0f4f8;
            border-top: 2px solid #dee2e6;
        }

        .bs-net-profit td {
            font-weight: 700;
            font-size: 12px;
            padding: 8px 16px;
            background: #f0fdf4;
            color: #15803d;
            border-bottom: 1px solid #f0f4f8;
        }

        .bs-net-loss td {
            font-weight: 700;
            font-size: 12px;
            padding: 8px 16px;
            background: #fff1f2;
            color: #b91c1c;
            border-bottom: 1px solid #f0f4f8;
        }

        .bs-grand td {
            font-weight: 800;
            font-size: 14px;
            padding: 12px 16px;
            background: #0f2544;
            color: #fff;
        }

        .col-amount {
            text-align: right;
            min-width: 130px;
        }

        .bs-divider td {
            border: none;
            padding: 0;
            background: transparent;
        }

        .balanced-badge {
            background: #dcfce7;
            color: #15803d;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .unbalanced-badge {
            background: #fee2e2;
            color: #b91c1c;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
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

        #reportOutput {
            display: none;
        }

        @media print {
            @page {
                size: A4;
                margin: 14mm;
            }

            body * {
                visibility: hidden;
            }

            #reportOutput,
            #reportOutput * {
                visibility: visible;
            }

            .filter-section,
            .no-print {
                display: none !important;
            }

            .main-header,
            .main-sidebar,
            .main-footer,
            .content-header {
                display: none !important;
            }

            body,
            .wrapper,
            .content-wrapper {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .report-card {
                box-shadow: none;
            }
        }
    </style>

    <section class="content py-4">
        <div class="container-fluid">

            <div class="d-flex align-items-center justify-content-between mb-3 no-print">
                <h4 class="mb-0"><i class="fa fa-columns mr-2"></i> Balance Sheet</h4>
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
                    <div class="col-md-3">
                        <label class="small font-weight-bold">As of Date</label>
                        <input type="date" class="form-control form-control-sm" id="asOf"
                            value="{{ now()->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="small font-weight-bold">Client</label>
                        <select class="form-control form-control-sm select2" id="clientSelect" style="width:100%">
                            <option value="">All Clients</option>
                            @foreach ($clients as $c)
                                <option value="{{ $c->id }}">{{ trim($c->first_name . ' ' . $c->last_name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small font-weight-bold">Business</label>
                        <select class="form-control form-control-sm" id="businessSelect">
                            <option value="">All Businesses</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary btn-sm btn-block mt-3" id="generateBtn"><i class="fa fa-refresh"></i>
                            Generate</button>
                    </div>
                </div>
            </div>

            <div id="reportOutput">
                <div class="card report-card">
                    <div class="card-body p-0">
                        <div class="text-center py-3 border-bottom d-flex align-items-center justify-content-between px-4">
                            <div>
                                <h5 class="mb-0 font-weight-bold">Balance Sheet</h5>
                                <p class="small text-muted mb-0" id="reportMeta"></p>
                            </div>
                            <div id="balanceBadge"></div>
                        </div>

                        <div class="row no-gutters">
                            {{-- LEFT: Assets --}}
                            <div class="col-md-6" style="border-right: 2px solid #dee2e6;">
                                <table class="table mb-0" id="assetsTable">
                                    <tbody></tbody>
                                </table>
                            </div>
                            {{-- RIGHT: Liabilities + Equity --}}
                            <div class="col-md-6">
                                <table class="table mb-0" id="liabEquityTable">
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
@endsection

@section('script')
    <script>
        $(function() {
            $('#clientSelect').select2({
                placeholder: 'Select Client',
                allowClear: true
            }).on('change', function() {
                var credId = $(this).val();
                $('#businessSelect').html('<option value="">All Businesses</option>');
                if (!credId) return;
                $.get("{{ url('/admin/accounting/get-businesses') }}", {
                    credential_id: credId
                }, function(data) {
                    $.each(data, function(i, b) {
                        $('#businessSelect').append('<option value="' + b.id + '">' + b
                            .text + '</option>');
                    });
                });
            });

            function fmt(n) {
                return '£' + parseFloat(n || 0).toFixed(2);
            }

            function xRows(rows) {
                var html = '';
                $.each(rows || [], function(i, row) {
                    var clickable = row.head_id || row.code ? ' data-head-id="' + (row.head_id || '') + '" data-code="' + (row.code || '') + '" title="Click for breakdown" style="cursor:pointer;"' : '';
                    html += '<tr class="bs-row"' + clickable + '><td>' + row.name +
                        '</td><td class="col-amount">' + fmt(row.balance) +
                        '</td></tr>';
                });
                return html;
            }

            function xSection(label) {
                return '<tr><td colspan="2" class="bs-section-header">EXCEL: ' + label + '</td></tr>';
            }

            function xTotal(label, val, cls) {
                return '<tr class="' + (cls || 'bs-total') + '"><td>' + label +
                    '</td><td class="col-amount">' + fmt(val) + '</td></tr>';
            }

            function bsExportUrl(format) {
                return "{{ url('/admin/accounting/balance-sheet/export') }}?format=" + format + '&' + $.param({
                    as_of: $('#asOf').val(),
                    client_credential_id: $('#clientSelect').val(),
                    client_id: $('#businessSelect').val()
                });
            }

            $('#exportCsvBtn').click(function() { window.location = bsExportUrl('csv'); });
            $('#exportXlsxBtn').click(function() { window.location = bsExportUrl('xlsx'); });

            $('#generateBtn').click(function() {
                $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
                $.get("{{ url('/admin/accounting/balance-sheet/data') }}", {
                    as_of: $('#asOf').val(),
                    client_credential_id: $('#clientSelect').val(),
                    client_id: $('#businessSelect').val(),
                }, function(d) {
                    // Assets table
                    var aBody = '';
                    aBody += '<tr><td colspan="2" class="bs-section-header">ASSETS</td></tr>';
                    $.each(d.assets, function(i, row) {
                        aBody += '<tr class="bs-row" data-head-id="' + (row.head_id || '') + '" data-code="' + (row.code || '') + '" title="Click for breakdown" style="cursor:pointer;"><td>' + row.name +
                            '</td><td class="col-amount">' + fmt(row.balance) +
                            '</td></tr>';
                    });
                    if (!d.assets.length) {
                        aBody +=
                            '<tr class="bs-row"><td colspan="2" class="text-muted" style="padding-left:28px;">No asset accounts</td></tr>';
                    }
                    aBody += '<tr class="bs-grand"><td>TOTAL ASSETS</td><td class="col-amount">' +
                        fmt(d.total_assets) + '</td></tr>';
                    if (d.excel) {
                        var st = d.excel.section_titles || {};
                        aBody += xSection(((st.fixed || 'FIXED ASSETS')).toUpperCase());
                        aBody += xRows(d.excel.fixed_heads);
                        aBody += xTotal('Total Fixed Assets (A)', d.excel.total_fixed_A);
                        aBody += xSection(((st.current || 'CURRENT ASSETS')).toUpperCase());
                        aBody += xRows(d.excel.current_heads);
                        aBody += xTotal('Total Current Assets (B)', d.excel.total_current_B_excel);
                    }
                    $('#assetsTable tbody').html(aBody);

                    // Liabilities + Equity table
                    var leBody = '';
                    leBody += '<tr><td colspan="2" class="bs-section-header">LIABILITIES</td></tr>';
                    $.each(d.liabilities, function(i, row) {
                        leBody += '<tr class="bs-row" data-head-id="' + (row.head_id || '') + '" data-code="' + (row.code || '') + '" title="Click for breakdown" style="cursor:pointer;"><td>' + row.name +
                            '</td><td class="col-amount">' + fmt(row.balance) +
                            '</td></tr>';
                    });
                    if (!d.liabilities.length) {
                        leBody +=
                            '<tr class="bs-row"><td colspan="2" class="text-muted" style="padding-left:28px;">No liability accounts</td></tr>';
                    }
                    leBody +=
                        '<tr class="bs-total"><td>Total Liabilities</td><td class="col-amount">' +
                        fmt(d.total_liabilities) + '</td></tr>';

                    leBody +=
                        '<tr><td colspan="2" class="bs-section-header" style="margin-top:8px;">EQUITY</td></tr>';
                    $.each(d.equity, function(i, row) {
                        leBody += '<tr class="bs-row" data-head-id="' + (row.head_id || '') + '" data-code="' + (row.code || '') + '" title="Click for breakdown" style="cursor:pointer;"><td>' + row.name +
                            '</td><td class="col-amount">' + fmt(row.balance) +
                            '</td></tr>';
                    });

                    // Net Profit / Loss as part of equity
                    var netClass = d.net_profit >= 0 ? 'bs-net-profit' : 'bs-net-loss';
                    var netLabel = d.net_profit >= 0 ? 'Retained Earnings (Net Profit)' :
                        'Retained Earnings (Net Loss)';
                    leBody += '<tr class="' + netClass + '"><td>' + netLabel +
                        '</td><td class="col-amount">' + fmt(d.net_profit) + '</td></tr>';
                    leBody += '<tr class="bs-total"><td>Total Equity</td><td class="col-amount">' +
                        fmt(d.total_equity) + '</td></tr>';

                    leBody +=
                        '<tr class="bs-grand"><td>TOTAL LIABILITIES + EQUITY</td><td class="col-amount">' +
                        fmt(d.total_liab_equity) + '</td></tr>';
                    if (d.excel) {
                        var st2 = d.excel.section_titles || {};
                        leBody += xSection(((st2.curr_liab || 'CURRENT LIABILITIES')).toUpperCase());
                        leBody += xRows(d.excel.current_liab_heads);
                        leBody += xTotal('Total Current Liabilities (C)', d.excel.total_current_C);
                        leBody += xTotal('Net Current Assets (B-C)', d.excel.net_current_BC);
                        leBody += xTotal('Total Assets less Current Liabilities (A+B-C)', d.excel.total_assets_less_current_excel);
                        leBody += xSection(((st2.noncurrent || 'NON-CURRENT LIABILITIES')).toUpperCase());
                        leBody += xRows(d.excel.noncurrent_heads);
                        leBody += xTotal('Total Non-Current Liabilities (D)', d.excel.total_noncurrent_D);
                        leBody += xTotal('Net Assets (A+B-C-D)', d.excel.net_assets_excel, 'bs-grand');
                        leBody += xSection(((st2.capital || 'CAPITAL AND RESERVES')).toUpperCase());
                        leBody += xRows(d.excel.capital_heads);
                        leBody += xTotal('Total Capital and Reserves', d.excel.total_capital_excel);
                        leBody += '<tr class="bs-row"><td colspan="2" class="small text-muted">Refs: A=Fixed, B=Current, C=Current Liab, B-C=Net Current, A+B-C=Assets less Current, D=Non-current, A+B-C-D=Net Assets. Rows follow the live chart of accounts; totals are straight sums.</td></tr>';
                    }
                    $('#liabEquityTable tbody').html(leBody);

                    // Balance check badge
                    $('#balanceBadge').html(
                        '<span class="balanced-badge"><i class="fa fa-info-circle"></i> Single Entry System</span>'
                    );

                    $('#reportMeta').text(d.business_name + '  |  As of ' + d.as_of + '  |  Click a row for breakdown');
                    $('#reportOutput').fadeIn();

                }).always(function() {
                    $('#generateBtn').prop('disabled', false).html(
                        '<i class="fa fa-refresh"></i> Generate');
                });
            });

            // Drill-down: click any head row → expandable breakdown of its transactions (as-of basis).
            $('#assetsTable, #liabEquityTable').on('click', 'tr.bs-row[data-head-id], tr.bs-row[data-code]', function() {
                var $row = $(this);
                if ($row.next().hasClass('breakdown-row')) {
                    $row.next().toggle();
                    return;
                }
                var headId = $row.data('head-id');
                var code = $row.data('code');
                if (!headId && !code) return;
                var $detail = $('<tr class="breakdown-row"><td colspan="2" style="background:#f8fafc; padding:8px 16px 12px 28px;"><span class="small text-muted"><i class="fa fa-spinner fa-spin"></i> Loading breakdown…</span></td></tr>');
                $row.after($detail);
                $.get("{{ url('/admin/accounting/head-transactions') }}", {
                    mode: 'bs',
                    head_id: headId || '',
                    code: code || '',
                    as_of: $('#asOf').val(),
                    client_credential_id: $('#clientSelect').val(),
                    client_id: $('#businessSelect').val()
                }, function(res) {
                    var html = '<div class="small font-weight-bold mb-1">Breakdown: ' + (res.heads.length ? res.heads.map(function(h) { return h.code + ' - ' + h.name; }).join(', ') : '') + ' (' + res.count + ' transactions, Total £' + parseFloat(res.total || 0).toFixed(2) + ')</div>';
                    if (!res.rows.length) {
                        html += '<div class="small text-muted">No transactions as of this date.</div>';
                    } else {
                        html += '<table class="table table-sm mb-0"><thead><tr><th>Date</th><th>Receipt</th><th>Business</th><th>Method</th><th>Side</th><th class="text-right">Amount</th></tr></thead><tbody>';
                        $.each(res.rows, function(i, r) {
                            var link = r.bill_url ? '<a href="' + r.bill_url + '" target="_blank">' + (r.receipt_number || ('#' + r.receipt_id)) + '</a>' : (r.receipt_number || ('#' + r.receipt_id));
                            html += '<tr><td>' + (r.date || '-') + '</td>' +
                                '<td>' + link + '</td>' +
                                '<td>' + (r.business || '-') + '</td>' +
                                '<td>' + (r.payment_method || '-') + '</td>' +
                                '<td>' + (r.type || '-') + '</td>' +
                                '<td class="text-right">£' + parseFloat(r.signed_amount ?? r.amount ?? 0).toFixed(2) + '</td></tr>';
                        });
                        html += '</tbody></table>';
                    }
                    $detail.find('td').html(html);
                }).fail(function() {
                    $detail.find('td').html('<span class="small text-danger">Failed to load breakdown.</span>');
                });
            });
        });
    </script>
@endsection