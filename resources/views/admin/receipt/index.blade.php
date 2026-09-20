@extends('admin.layouts.admin')

@section('content')
    @php $presetId = request('client_credential_id'); @endphp
    <style>
        .modern-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
        }

        .stat-card {
            border-radius: 12px;
            padding: 20px;
            color: #fff;
            cursor: pointer;
            transition: 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            opacity: 0.9;
        }

        .table thead {
            background: #f8f9fa;
            border-top: 2px solid #e9ecef;
        }

        .select2-container .select2-selection--single {
            height: 38px !important;
            border: 1px solid #ced4da !important;
            border-radius: .25rem !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 24px !important;
            padding-left: 0px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
        }

        .client-info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 18px 24px;
            color: #fff;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .client-info-card .client-name {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .client-info-card .client-detail {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 2px;
        }

        .client-info-card .client-detail i {
            width: 18px;
            margin-right: 6px;
        }

        .client-info-card .badge-status {
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 20px;
        }
    </style>

    <section class="content py-4">
        <div class="container-fluid">
            {{-- Client selector always visible --}}
            <div class="card modern-card mb-3">
                <div class="card-body py-3">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <label class="font-weight-bold">Select Client</label>
                            <select class="form-control select2" id="filterClient" style="width:100%">
                                <option value="">-- Choose a client --</option>
                            </select>
                        </div>
                        @if($presetId)
                        <div class="col-md-8">
                            <div class="d-flex align-items-center" style="margin-top:24px; gap:18px; background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding:12px 20px; border-radius:12px; color:#fff;">
                                <span class="font-weight-bold" style="font-size:18px;" id="ciName"></span>
                                <span style="opacity:0.9; font-size:14px;"><i class="fas fa-envelope mr-1"></i><span id="ciEmail"></span></span>
                                <span style="opacity:0.9; font-size:14px;"><i class="fas fa-phone mr-1"></i><span id="ciPhone"></span></span>
                                <a href="{{ url('/admin/receipts') }}" class="btn btn-sm btn-light ml-2" style="color:#667eea; font-weight:600;">
                                    <i class="fas fa-exchange-alt"></i> Change
                                </a>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($presetId)
                {{-- Stats --}}
                <div class="row">
                    @php $stats = ['pending'=>'bg-warning', 'to_review'=>'bg-info', 'ready'=>'bg-success', 'cancelled'=>'bg-danger', 'archived'=>'bg-secondary', 'total'=>'bg-dark']; @endphp
                    @foreach ($stats as $key => $bg)
                        <div class="col-md-2">
                            <div class="small-box stat-card {{ $bg }}" data-status="{{ $key != 'total' ? $key : '' }}">
                                <div class="inner">
                                    <h3 id="count_{{ $key }}">0</h3>
                                    <p class="mb-0 font-weight-bold">{{ ucfirst(str_replace('_', ' ', $key)) }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Table --}}
                <div class="card modern-card mt-3">
                    <div class="card-body">
                        {{-- Basic Filters --}}
                        <div class="row mb-3 align-items-end">
                            <div class="col-md-2">
                                <label>Status</label>
                                <select class="form-control" id="filterStatus">
                                    <option value="">All</option>
                                    <option value="pending">Pending</option>
                                    <option value="to_review">To Review</option>
                                    <option value="ready">Ready</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Payment</label>
                                <select class="form-control" id="filterPaid">
                                    <option value="">All</option>
                                    <option value="yes">Paid</option>
                                    <option value="no">Unpaid</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Method</label>
                                <select class="form-control" id="filterPaymentMethod">
                                    <option value="">All</option>
                                    <option value="cash">Cash</option>
                                    <option value="bank">Bank</option>
                                    <option value="card">Card</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                                <button class="btn btn-outline-secondary btn-block" type="button" id="toggleDeepSearch">
                                    <i class="fa fa-search"></i> Deep Search <i class="fa fa-chevron-down" id="deepSearchIcon"></i>
                                </button>
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                                <button class="btn btn-secondary btn-block" id="resetFilter">
                                    <i class="fa fa-refresh"></i> Reset
                                </button>
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                                <a href="{{ url('/admin/receipts/create') }}?client_credential_id={{ $presetId }}" class="btn btn-primary btn-block">
                                    <i class="fa fa-plus"></i> New
                                </a>
                            </div>
                        </div>

                        {{-- Deep Search - Collapsible --}}
                        <div id="deepSearchFilters" style="display:none;">
                            <div class="card card-body mb-3" style="background:#f8f9fa; border-radius:10px;">
                                <div class="row align-items-end">
                                    <div class="col-md-2">
                                        <label>Business</label>
                                        <select class="form-control" id="filterBusiness" style="width:100%">
                                            <option value="">All</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label>Supplier</label>
                                        <input type="text" class="form-control" id="filterSupplier" placeholder="Search supplier...">
                                    </div>
                                    <div class="col-md-2">
                                        <label>Inv No.</label>
                                        <input type="text" class="form-control" id="filterInvNo" placeholder="Invoice #">
                                    </div>
                                    <div class="col-md-2">
                                        <label>Notes</label>
                                        <input type="text" class="form-control" id="filterNotes" placeholder="Search notes...">
                                    </div>
                                    <div class="col-md-2">
                                        <label>Created By</label>
                                        <select class="form-control" id="filterCreatedBy">
                                            <option value="">All</option>
                                            @foreach($users as $u)
                                                <option value="{{ $u->id }}">{{ $u->first_name }} {{ $u->last_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label>Date Type</label>
                                        <select class="form-control" id="filterDateType">
                                            <option value="invoice">Invoice Date</option>
                                            <option value="created">Created Date</option>
                                            <option value="receipt">Receipt Date</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row align-items-end mt-2">
                                    <div class="col-md-2">
                                        <label>Date From</label>
                                        <input type="date" class="form-control" id="filterDateFrom">
                                    </div>
                                    <div class="col-md-2">
                                        <label>Date To</label>
                                        <input type="date" class="form-control" id="filterDateTo">
                                    </div>
                                    <div class="col-md-2">
                                        <label>Amount Min (£)</label>
                                        <input type="number" step="0.01" class="form-control" id="filterAmountMin" placeholder="Min">
                                    </div>
                                    <div class="col-md-2">
                                        <label>Amount Max (£)</label>
                                        <input type="number" step="0.01" class="form-control" id="filterAmountMax" placeholder="Max">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="example1" class="table table-striped table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Action</th>
                                        <th>Status</th>
                                        <th>Business</th>
                                        <th>Inv Date</th>
                                        <th>Inv No.</th>
                                        <th>Acc Type</th>
                                        <th>Acc Head</th>
                                        <th>Net</th>
                                        <th>VAT</th>
                                        <th>Tax</th>
                                        <th>Total</th>
                                        <th>Supplier</th>
                                        <th>Method</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                {{-- No client selected --}}
                <div class="text-center py-5">
                    <i class="fas fa-user-check fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Select a client to view their receipts</h5>
                </div>
            @endif
        </div>
    </section>
@endsection

@section('script')
    <script>
        $(function() {
            var presetCredentialId = '{{ $presetId }}';
            var presetApplied = false;

            $('#toggleDeepSearch').click(function() {
                $('#deepSearchFilters').slideToggle(200);
                $('#deepSearchIcon').toggleClass('fa-chevron-down fa-chevron-up');
            });

            $('#filterClient').select2({
                placeholder: "-- Choose a client --",
                allowClear: true,
                ajax: {
                    url: "{{ url('/admin/receipts/search-clients') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return { q: params.term };
                    },
                    processResults: function(data) {
                        return { results: data };
                    },
                    cache: true
                }
            }).on('change', function() {
                var val = $(this).val();
                if (!val) return;
                // Skip redirect if this change was triggered by the preset
                if (presetApplied) {
                    presetApplied = false;
                    return;
                }
                window.location.href = "{{ url('/admin/receipts') }}?client_credential_id=" + val;
            });

            if (presetCredentialId) {
                $.get("{{ url('/admin/receipts/search-clients') }}", { q: '' }, function(data) {
                    var match = data.find(function(item) { return item.id == presetCredentialId; });
                    if (match) {
                        presetApplied = true;
                        var option = new Option(match.text, match.id, true, true);
                        $('#filterClient').append(option).trigger('change');
                        $('#ciName').text(match.text);
                        $('#ciEmail').text(match.email || 'N/A');
                        $('#ciPhone').text(match.phone || 'N/A');
                    }
                });

                $.get("{{ url('/admin/receipts/counts') }}", { client_credential_id: presetCredentialId }, function(d) {
                    $('#count_pending').text(d.pending);
                    $('#count_to_review').text(d.to_review);
                    $('#count_ready').text(d.ready);
                    $('#count_cancelled').text(d.cancelled);
                    $('#count_archived').text(d.archived);
                    $('#count_total').text(d.total);
                });

                // Load businesses for filter
                $.get("{{ url('/admin/receipts/get-clients-by-credential') }}", { client_credential_id: presetCredentialId }, function(data) {
                    var opts = '<option value="">All</option>';
                    $.each(data, function(i, c) {
                        opts += '<option value="' + c.id + '">' + c.name + '</option>';
                    });
                    $('#filterBusiness').html(opts);
                });

                var table = $('#example1').DataTable({
                    processing: true,
                    serverSide: true,
                    scrollX: true,
                    ajax: {
                        url: "{{ url('/admin/receipts/datatable') }}",
                        data: function(d) {
                            d.client_credential_id = presetCredentialId;
                            d.status = $('#filterStatus').val();
                            d.paid = $('#filterPaid').val();
                            d.payment_method = $('#filterPaymentMethod').val();
                            d.business = $('#filterBusiness').val();
                            d.supplier = $('#filterSupplier').val();
                            d.invoice_number = $('#filterInvNo').val();
                            d.date_from = $('#filterDateFrom').val();
                            d.date_to = $('#filterDateTo').val();
                            d.date_type = $('#filterDateType').val();
                            d.amount_min = $('#filterAmountMin').val();
                            d.amount_max = $('#filterAmountMax').val();
                            d.notes = $('#filterNotes').val();
                            d.created_by = $('#filterCreatedBy').val();
                        }
                    },
                    createdRow: function(row, data) {
                        $(row).css('cursor', 'pointer').click(function(e) {
                            if ($(e.target).closest('td').index() === 1) return;
                            window.location.href = "{{ url('/admin/receipts') }}/" + data.id + "/bill";
                        });
                    },
                    columns: [
                        { data: 'DT_RowIndex', orderable: false, searchable: false },
                        { data: 'action', orderable: false, searchable: false },
                        { data: 'status_badge', orderable: false, searchable: false },
                        { data: 'business_name' },
                        { data: 'invoice_date' },
                        { data: 'invoice_number' },
                        { data: 'account_type' },
                        { data: 'account_head' },
                        { data: 'net_amount' },
                        { data: 'vat_amount' },
                        { data: 'tax_amount' },
                        { data: 'total_amount' },
                        { data: 'supplier' },
                        { data: 'payment_method' }
                    ]
                });

                $(document).on('click', '.stat-card', function() {
                    var status = $(this).data('status');
                    $('#filterStatus').val(status);
                    table.ajax.reload();
                });

                $('#filterStatus, #filterPaid, #filterPaymentMethod, #filterBusiness').on('change', function() {
                    table.ajax.reload();
                });

                var searchTimeout;
                $('#filterSupplier, #filterInvNo, #filterNotes, #filterAmountMin, #filterAmountMax, #filterDateFrom, #filterDateTo').on('keyup change', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(function() { table.ajax.reload(); }, 400);
                });

                $('#resetFilter').click(function() {
                    $('#filterStatus, #filterPaid, #filterPaymentMethod, #filterBusiness, #filterCreatedBy').val('');
                    $('#filterSupplier, #filterInvNo, #filterNotes, #filterAmountMin, #filterAmountMax, #filterDateFrom, #filterDateTo').val('');
                    $('#filterDateType').val('invoice');
                    table.ajax.reload();
                });
            }
        });
    </script>
@endsection
