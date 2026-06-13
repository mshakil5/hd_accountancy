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

        .tb-table thead th {
            background: #0f2544;
            color: #fff;
            font-size: 12px;
            padding: 10px 14px;
        }

        .tb-table tbody td {
            font-size: 12px;
            padding: 8px 14px;
            border-bottom: 1px solid #f0f4f8;
        }

        .tb-table tbody tr:hover td {
            background: #f8fafc;
        }

        .tb-total td {
            font-weight: 800;
            font-size: 13px;
            padding: 11px 14px;
            background: #0f2544;
            color: #fff;
            border-top: 3px solid #1a9e78;
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

        .col-amount {
            text-align: right;
            min-width: 110px;
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
    </style>

    <section class="content py-4">
        <div class="container-fluid">

            <div class="d-flex align-items-center justify-content-between mb-3">
                <h4 class="mb-0"><i class="fa fa-balance-scale mr-2"></i> Trial Balance</h4>
                <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="fa fa-print"></i>
                    Print</button>
            </div>

            <div class="filter-section">
                <div class="row align-items-end">
                    <div class="col-md-2">
                        <label class="small font-weight-bold">From</label>
                        <input type="date" class="form-control form-control-sm" id="from"
                            value="{{ now()->startOfYear()->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="small font-weight-bold">To</label>
                        <input type="date" class="form-control form-control-sm" id="to"
                            value="{{ now()->endOfYear()->format('Y-m-d') }}">
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
                                <h5 class="mb-0 font-weight-bold">Trial Balance</h5>
                                <p class="small text-muted mb-0" id="reportMeta"></p>
                            </div>
                            <div id="balanceBadge"></div>
                        </div>
                        <table class="table tb-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width:8%">Code</th>
                                    <th style="width:32%">Account Head</th>
                                    <th style="width:15%">Category</th>
                                    <th style="width:15%">Normal Balance</th>
                                    <th class="col-amount">Debit (£)</th>
                                    <th class="col-amount">Credit (£)</th>
                                </tr>
                            </thead>
                            <tbody id="reportBody"></tbody>
                            <tfoot>
                                <tr class="tb-total" id="totalRow" style="display:none">
                                    <td colspan="4">TOTAL</td>
                                    <td class="col-amount" id="totalDebit"></td>
                                    <td class="col-amount" id="totalCredit"></td>
                                </tr>
                            </tfoot>
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
                $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
                $.get("{{ url('/admin/accounting/trial-balance/data') }}", {
                    from: $('#from').val(),
                    to: $('#to').val(),
                    client_credential_id: $('#clientSelect').val(),
                    client_id: $('#businessSelect').val(),
                }, function(d) {
                    var rows = '';
                    $.each(d.rows, function(i, r) {
                        rows += '<tr>' +
                            '<td><strong>' + r.code + '</strong></td>' +
                            '<td>' + r.name + '</td>' +
                            '<td><span class="badge badge-secondary">' + r.category +
                            '</span></td>' +
                            '<td>' + r.normal_balance + '</td>' +
                            '<td class="col-amount">' + (r.debit > 0 ? '£' + r.debit
                                .toFixed(2) : '—') + '</td>' +
                            '<td class="col-amount">' + (r.credit > 0 ? '£' + r.credit
                                .toFixed(2) : '—') + '</td>' +
                            '</tr>';
                    });
                    if (!rows) rows =
                        '<tr><td colspan="6" class="text-center text-muted py-4">No transactions found for this period.</td></tr>';

                    $('#reportBody').html(rows);
                    $('#totalDebit').text('£' + parseFloat(d.total_debit).toFixed(2));
                    $('#totalCredit').text('£' + parseFloat(d.total_credit).toFixed(2));
                    $('#totalRow').show();
                    $('#reportMeta').text(d.business_name + '  |  ' + d.from + ' – ' + d.to);

                    $('#balanceBadge').html(
                        '<span class="balanced-badge"><i class="fa fa-info-circle"></i> Single Entry System</span>'
                    );

                    $('#reportOutput').fadeIn();
                }).always(function() {
                    $('#generateBtn').prop('disabled', false).html(
                        '<i class="fa fa-refresh"></i> Generate');
                });
            });
        });
    </script>
@endsection