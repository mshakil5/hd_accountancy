@extends('admin.layouts.admin')

@section('content')
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
        
        /* Select2 AdminLTE Theme Compatibility Custom Height */
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
    </style>

    <section class="content py-4">
        <div class="container-fluid">
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

            <div class="card modern-card mt-3">
                <div class="card-body">
                    <div class="row mb-4 align-items-end justify-content-between">
                        <div class="col-md-2">
                            <label>Client</label>
                            <select class="form-control select2" id="filterClient">
                                <option value="">All Clients</option>
                            </select>
                        </div>
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
                        <div class="col-md-3">
                            <label>Method</label>
                            <select class="form-control" id="filterPaymentMethod">
                                <option value="">All</option>
                                <option value="cash">Cash</option>
                                <option value="bank">Bank</option>
                                <option value="card">Card</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-secondary btn-block" id="resetFilter">
                                <i class="fa fa-refresh"></i> Reset
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="example1" class="table table-striped table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Action</th>
                                    <th>Status</th>
                                    <th>Client</th>
                                    <th>Business</th>
                                    <th>Inv Date</th>
                                    <th>Inv No.</th>
                                    <th>Acc Type</th>
                                    <th>Acc Head</th>
                                    <th>Net</th>
                                    <th>VAT</th>
                                    <th>Tax</th>
                                    <th>Total</th>
                                    <th>Method</th>
                                </tr>
                            </thead>
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
            // Select2 Remote Data Integration (AJAX Search for Client Credentials)
            $('#filterClient').select2({
                placeholder: "Search Client Name...",
                allowClear: true,
                ajax: {
                    url: "{{ url('/admin/receipts/search-clients') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term // search term
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                }
            }).on('change', () => table.ajax.reload());

            $.get("{{ url('/admin/receipts/counts') }}", function(d) {
                $('#count_pending').text(d.pending);
                $('#count_to_review').text(d.to_review);
                $('#count_ready').text(d.ready);
                $('#count_cancelled').text(d.cancelled);
                $('#count_archived').text(d.archived);
                $('#count_total').text(d.total);
            });

            var table = $('#example1').DataTable({
                processing: true,
                serverSide: true,
                scrollX: true,
                ajax: {
                    url: "{{ url('/admin/receipts/datatable') }}",
                    data: function(d) {
                        d.client_credential_id = $('#filterClient').val(); // Send Client Credential ID to controller
                        d.status = $('#filterStatus').val();
                        d.paid = $('#filterPaid').val();
                        d.payment_method = $('#filterPaymentMethod').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', orderable: false }, 
                    { data: 'action', orderable: false }, 
                    { data: 'status_badge', orderable: false },
                    { data: 'client_name' },
                    { data: 'business_name' },
                    { data: 'invoice_date' }, 
                    { data: 'invoice_number' },
                    { data: 'account_type' }, 
                    { data: 'account_head' },
                    { data: 'net_amount' }, 
                    { data: 'vat_amount' },
                    { data: 'tax_amount' },
                    { data: 'total_amount' }, 
                    { data: 'payment_method' }
                ]
            });

            // Stat Cards Click Filtering
            $('.stat-card').click(function() {
                var status = $(this).data('status');
                $('#filterStatus').val(status);
                table.ajax.reload();
            });

            $('#filterStatus, #filterPaid, #filterPaymentMethod').on('change', () => table.ajax.reload());
            
            $('#resetFilter').click(function() {
                $('#filterClient').val(null).trigger('change');
                $('#filterStatus, #filterPaid, #filterPaymentMethod').val('');
                table.ajax.reload();
            });
        });
    </script>
@endsection