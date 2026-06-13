@extends('admin.layouts.admin')

@section('content')
    <style>
        .report-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.07);
        }

        .section-header {
            background: #0f2544;
            color: #fff;
            padding: 10px 16px;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .section-subhead {
            background: #f0f4f8;
            padding: 8px 16px;
            font-weight: 600;
            font-size: 12px;
            color: #374151;
            border-bottom: 1px solid #e4e9ee;
        }

        .report-row td {
            font-size: 12px;
            padding: 8px 16px;
            border-bottom: 1px solid #f0f4f8;
            cursor: pointer;
        }

        .report-row:hover td {
            background: #f8fafc;
        }

        .total-row td {
            font-weight: 700;
            font-size: 13px;
            padding: 10px 16px;
            background: #f8f9fa;
            border-top: 2px solid #dee2e6;
        }

        .grand-row td {
            font-weight: 800;
            font-size: 14px;
            padding: 12px 16px;
            background: #0f2544;
            color: #fff;
        }

        .profit-row td {
            font-weight: 800;
            font-size: 14px;
            padding: 12px 16px;
            background: #1a9e78;
            color: #fff;
        }

        .loss-row td {
            font-weight: 800;
            font-size: 14px;
            padding: 12px 16px;
            background: #dc3545;
            color: #fff;
        }

        .col-amount {
            text-align: right;
            min-width: 90px;
        }

        .filter-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 20px;
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
    </style>

    <section class="content py-4">
        <div class="container-fluid">

            <div class="d-flex align-items-center justify-content-between mb-3">
                <h4 class="mb-0"><i class="fa fa-chart-line mr-2"></i> Profit & Loss</h4>
                <div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="fa fa-print"></i>
                        Print</button>
                </div>
            </div>

            <div class="filter-section">
                <div class="row align-items-end">
                    <div class="col-md-2">
                        <label class="small font-weight-bold">From</label>
                        <input type="date" class="form-control form-control-sm" id="from"
                            value="{{ now()->startOfMonth()->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="small font-weight-bold">To</label>
                        <input type="date" class="form-control form-control-sm" id="to"
                            value="{{ now()->endOfMonth()->format('Y-m-d') }}">
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
                    <div class="col-md-2">
                        <label class="small font-weight-bold">Business</label>
                        <select class="form-control form-control-sm" id="businessSelect">
                            <option value="">All Businesses</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small font-weight-bold">Payment Method</label>
                        <select class="form-control form-control-sm" id="paymentMethod">
                            <option value="">All Methods</option>
                            <option value="cash">Cash</option>
                            <option value="bank">Bank</option>
                            <option value="card">Card</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-primary btn-sm btn-block mt-3" id="generateBtn"><i class="fa fa-refresh"></i>
                            Run</button>
                    </div>
                </div>
            </div>

            <div id="reportOutput">
                <div class="card report-card">
                    <div class="card-body p-0">
                        <div class="text-center py-3 border-bottom">
                            <h5 class="mb-0 font-weight-bold">Profit & Loss Statement</h5>
                            <p class="small text-muted mb-0" id="reportMeta"></p>
                        </div>
                        <table class="table mb-0" id="reportTable">
                            <thead>
                                <tr style="background:#f8f9fa;">
                                    <th style="width:40%; font-size:12px;">Account Head</th>
                                    <th class="col-amount" style="font-size:12px;">Cash</th>
                                    <th class="col-amount" style="font-size:12px;">Bank</th>
                                    <th class="col-amount" style="font-size:12px;">Card</th>
                                    <th class="col-amount" style="font-size:12px;">Total</th>
                                </tr>
                            </thead>
                            <tbody id="reportBody"></tbody>
                        </table>
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

            $('#generateBtn').click(function() {
                currentFilters = {
                    from: $('#from').val(),
                    to: $('#to').val(),
                    client_credential_id: $('#clientSelect').val(),
                    client_id: $('#businessSelect').val(),
                    payment_method: $('#paymentMethod').val(),
                };
                loadReport(currentFilters);
            });

            function fmt(n) {
                return '£' + parseFloat(n || 0).toFixed(2);
            }

            function buildSection(label, sections, colorClass) {
                var html = '';
                html += '<tr><td colspan="5" class="section-header p-0"><div class="section-header">' + label +
                    '</div></td></tr>';

                var sectionTotals = {
                    cash: 0,
                    bank: 0,
                    card: 0,
                    total: 0
                };

                $.each(sections, function(i, section) {
                    html += '<tr><td colspan="5" class="section-subhead">' + section.type_name +
                        '</td></tr>';
                    $.each(section.heads, function(j, head) {
                        html += '<tr class="report-row" data-head-id="' + head.head_id + '">' +
                            '<td style="padding-left:28px;">' + head.head_name + '</td>' +
                            '<td class="col-amount">' + fmt(head.cash) + '</td>' +
                            '<td class="col-amount">' + fmt(head.bank) + '</td>' +
                            '<td class="col-amount">' + fmt(head.card) + '</td>' +
                            '<td class="col-amount">' + fmt(head.total) + '</td>' +
                            '</tr>';
                    });
                    html += '<tr class="total-row">' +
                        '<td>Total ' + section.type_name + '</td>' +
                        '<td class="col-amount">' + fmt(section.type_total.cash) + '</td>' +
                        '<td class="col-amount">' + fmt(section.type_total.bank) + '</td>' +
                        '<td class="col-amount">' + fmt(section.type_total.card) + '</td>' +
                        '<td class="col-amount">' + fmt(section.type_total.total) + '</td>' +
                        '</tr>';

                    ['cash', 'bank', 'card', 'total'].forEach(function(m) {
                        sectionTotals[m] += section.type_total[m];
                    });
                });

                return {
                    html: html,
                    totals: sectionTotals
                };
            }

            function loadReport(filters) {
                $('#generateBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
                $.get("{{ url('/admin/accounting/profit-loss/data') }}", filters, function(d) {
                    var body = '';

                    // Income
                    var incomeResult = buildSection('TRADING INCOME', d.income, 'bg-success');
                    body += incomeResult.html;
                    body += '<tr class="grand-row"><td>Total Trading Income</td>' +
                        '<td class="col-amount">' + fmt(d.total_income.cash) + '</td>' +
                        '<td class="col-amount">' + fmt(d.total_income.bank) + '</td>' +
                        '<td class="col-amount">' + fmt(d.total_income.card) + '</td>' +
                        '<td class="col-amount">' + fmt(d.total_income.total) + '</td></tr>';

                    // Expenses
                    var expResult = buildSection('OPERATING EXPENSES', d.expenses, 'bg-danger');
                    body += expResult.html;
                    body += '<tr class="grand-row"><td>Total Expenses</td>' +
                        '<td class="col-amount">' + fmt(d.total_expense.cash) + '</td>' +
                        '<td class="col-amount">' + fmt(d.total_expense.bank) + '</td>' +
                        '<td class="col-amount">' + fmt(d.total_expense.card) + '</td>' +
                        '<td class="col-amount">' + fmt(d.total_expense.total) + '</td></tr>';

                    // Net Profit/Loss
                    var netClass = d.net_profit.total >= 0 ? 'profit-row' : 'loss-row';
                    var netLabel = d.net_profit.total >= 0 ? 'NET PROFIT' : 'NET LOSS';
                    body += '<tr class="' + netClass + '"><td>' + netLabel + '</td>' +
                        '<td class="col-amount">' + fmt(d.net_profit.cash) + '</td>' +
                        '<td class="col-amount">' + fmt(d.net_profit.bank) + '</td>' +
                        '<td class="col-amount">' + fmt(d.net_profit.card) + '</td>' +
                        '<td class="col-amount">' + fmt(d.net_profit.total) + '</td></tr>';

                    $('#reportBody').html(body);
                    $('#reportMeta').text(d.business_name + '  |  ' + d.from + ' – ' + d.to);
                    $('#reportOutput').fadeIn();
                }).always(function() {
                    $('#generateBtn').prop('disabled', false).html('<i class="fa fa-refresh"></i> Run');
                });
            }
        });
    </script>
@endsection