@extends('admin.layouts.admin')

@section('content')
    <style>
        .file-viewer img {
            max-width: 100%;
            border-radius: 8px;
        }

        .file-viewer iframe {
            width: 100%;
            height: 600px;
            border-radius: 8px;
        }

        .archived-overlay {
            pointer-events: none;
            opacity: 0.65;
        }
    </style>

    @php $isArchived = $receipt->status === 'archived'; @endphp

    <section class="content">
        <div class="container-fluid">

            <div class="d-flex align-items-center justify-content-between my-3">
                <a href="{{ route('admin.receipt.index') }}" class="btn btn-default">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
                <div>
                    @if (!$isArchived && $receipt->status !== 'cancelled')
                        <button type="button" class="btn btn-danger btn-sm" id="cancelBtn">Cancel Receipt</button>
                    @endif
                </div>
            </div>

            @if ($isArchived)
                <div class="alert alert-secondary">
                    <i class="fa fa-lock"></i> This receipt is <strong>archived</strong> and cannot be edited.
                </div>
            @endif

            <div class="row">

                <div class="col-md-6">
                    <div class="card card-secondary border-theme border-2">
                        <div class="card-body file-viewer" id="fileViewer">
                            @forelse($receipt->files as $i => $file)
                                <div class="file-item" data-index="{{ $i }}"
                                    style="{{ $i > 0 ? 'display:none' : '' }}">
                                    @if ($file->file_type === 'pdf')
                                        <iframe src="{{ asset($file->file_path) }}"></iframe>
                                    @else
                                        <img src="{{ asset($file->file_path) }}">
                                    @endif
                                </div>
                            @empty
                                <p class="text-muted">No files uploaded.</p>
                            @endforelse
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <div>
                                <button class="btn btn-sm btn-default" id="prevFile">
                                    <i class="fa fa-chevron-left"></i> Previous
                                </button>
                                <span id="fileCount" class="mx-2">
                                    1 / {{ $receipt->files->count() }}
                                </span>
                                <button class="btn btn-sm btn-default" id="nextFile">
                                    Next <i class="fa fa-chevron-right"></i>
                                </button>
                            </div>
                            @if (!$isArchived)
                                <div>
                                    <label class="btn btn-sm btn-secondary mb-0">
                                        <i class="fa fa-upload"></i> Upload
                                        <input type="file" id="uploadFileInput" style="display:none"
                                            accept=".pdf,.jpg,.jpeg,.png">
                                    </label>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card card-secondary border-theme border-2 mt-2">
                        <div class="card-body p-2" id="fileList">
                            @foreach ($receipt->files as $i => $file)
                                <div class="d-flex align-items-center justify-content-between p-1 border-bottom file-list-item"
                                    data-index="{{ $i }}">
                                    <span style="cursor:pointer" class="fileThumb">
                                        <i class="fa fa-{{ $file->file_type === 'pdf' ? 'file-pdf-o' : 'image' }}"></i>
                                        {{ $file->file_name }}
                                    </span>
                                    @if (!$isArchived)
                                        <a class="btn btn-link text-danger deleteFileBtn" data-id="{{ $file->id }}">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card card-secondary border-theme border-2">
                        <div class="card-body mt-3 {{ $isArchived ? 'archived-overlay' : '' }}">
                            <div class="ermsg"></div>

                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control" id="status" name="status"
                                    {{ $isArchived || $receipt->status === 'cancelled' ? 'disabled' : '' }}>
                                    <option value="to_review" {{ $receipt->status == 'to_review' ? 'selected' : '' }}>To
                                        Review</option>
                                    <option value="ready" {{ $receipt->status == 'ready' ? 'selected' : '' }}>Ready
                                    </option>
                                    <option value="cancelled" {{ $receipt->status == 'cancelled' ? 'selected' : '' }}>
                                        Cancelled</option>
                                    @if ($receipt->status === 'ready' || $receipt->status === 'archived')
                                        <option value="archived" {{ $receipt->status == 'archived' ? 'selected' : '' }}>
                                            Archived</option>
                                    @endif
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Invoice Date</label>
                                <input type="date" class="form-control" id="invoice_date"
                                    value="{{ $receipt->detail?->invoice_date ?? ($receipt->receipt_date ? \Carbon\Carbon::parse($receipt->receipt_date)->format('Y-m-d') : '') }}"
                                    {{ $isArchived ? 'disabled' : '' }}>
                            </div>

                            <div class="form-group">
                                <label>Due Date</label>
                                <input type="date" class="form-control" id="due_date"
                                    value="{{ $receipt->detail?->due_date }}" {{ $isArchived ? 'disabled' : '' }}>
                            </div>

                            <div class="form-group">
                                <label>Invoice Number</label>
                                <input type="text" class="form-control" id="invoice_number"
                                    value="{{ $receipt->detail?->invoice_number }}" {{ $isArchived ? 'disabled' : '' }}>
                            </div>

                            <div class="form-group">
                                <label>Account Type</label>
                                <select class="form-control" id="filter_account_type_id" {{ $isArchived ? 'disabled' : '' }}>
                                    <option value="">Select account type</option>
                                    @foreach($accountTypes as $type)
                                        <option value="{{ $type->id }}"
                                            {{ $receipt->detail?->accountHead?->account_type_id == $type->id ? 'selected' : '' }}>
                                            {{ $type->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Account Head</label>
                                <select class="form-control" id="account_head_id" {{ $isArchived ? 'disabled' : '' }}>
                                    <option value="">Select account head</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Net Amount</label>
                                <input type="number" step="0.01" class="form-control" id="net_amount"
                                    value="{{ $receipt->detail?->net_amount }}" {{ $isArchived ? 'disabled' : '' }}>
                            </div>

                            <div class="form-group">
                                <label>VAT</label>
                                <input type="number" step="0.01" class="form-control" id="vat_amount"
                                    value="{{ $receipt->detail?->vat_amount }}" readonly>
                            </div>

                            <div class="form-group">
                                <label>Total Amount</label>
                                <input type="number" step="0.01" class="form-control" id="total_amount"
                                    value="{{ $receipt->detail?->total_amount }}" readonly>
                            </div>

                            <div class="form-group">
                                <label>Paid</label>
                                <select class="form-control" id="paid" {{ $isArchived ? 'disabled' : '' }}>
                                    <option value="no"
                                        {{ $receipt->detail?->paid == 0 || is_null($receipt->detail?->paid) ? 'selected' : '' }}>
                                        No</option>
                                    <option value="yes" {{ $receipt->detail?->paid == 1 ? 'selected' : '' }}>Yes
                                    </option>
                                </select>
                            </div>

                            <div class="form-group" id="paymentMethodGroup" style="display:none">
                                <label>Payment Method</label>
                                <select class="form-control" id="payment_method" {{ $isArchived ? 'disabled' : '' }}>
                                    <option value="">Select</option>
                                    <option value="cash"
                                        {{ $receipt->detail?->payment_method == 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="bank"
                                        {{ $receipt->detail?->payment_method == 'bank' ? 'selected' : '' }}>Bank</option>
                                    <option value="card"
                                        {{ $receipt->detail?->payment_method == 'card' ? 'selected' : '' }}>Card</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Description</label>
                                <input type="text" class="form-control" id="description"
                                    value="{{ $receipt->detail?->description }}" {{ $isArchived ? 'disabled' : '' }}>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            @if (!$isArchived)
                                <button class="btn btn-default" onclick="history.back()">Cancel</button>
                                <button class="btn btn-secondary" id="saveBtn">Save</button>
                            @endif
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
            var receiptId = {{ $receipt->id }};
            var baseUrl = "{{ url('/admin/receipts') }}";
            var isArchived = {{ $isArchived ? 'true' : 'false' }};
            var hasPaidTxn =
                {{ $receipt->transactions->whereNotNull('parent_id')->count() > 0 ? 'true' : 'false' }};

            function togglePayment() {
                $('#paid').val() === 'yes' ?
                    $('#paymentMethodGroup').show() :
                    $('#paymentMethodGroup').hide();
            }
            togglePayment();
            $('#paid').on('change', togglePayment);

            function loadHeads(typeId, selectedHeadId) {
                var $head = $('#account_head_id');
                $head.html('<option value="">Select account head</option>');
                if (!typeId) return;
                $.get("{{ url('/admin/account-heads') }}/by-type/" + typeId, function(d) {
                    $.each(d, function(i, h) {
                        var sel = (h.id == selectedHeadId) ? 'selected' : '';
                        $head.append('<option value="' + h.id + '" data-tax="' + h.tax_rate + '" ' + sel + '>' + h.name + '</option>');
                    });
                    calcAmounts();
                });
            }

            $('#filter_account_type_id').on('change', function() {
                loadHeads($(this).val(), null);
            });

            var preType = $('#filter_account_type_id').val();
            var preHead = {{ $receipt->detail?->account_head_id ?? 'null' }};
            if (preType) {
                loadHeads(preType, preHead);
            }

            function calcAmounts() {
                var net = parseFloat($('#net_amount').val()) || 0;
                var tax = parseFloat($('#account_head_id option:selected').data('tax')) || 0;
                var vat = parseFloat((net * tax / 100).toFixed(2));
                var total = parseFloat((net + vat).toFixed(2));
                $('#vat_amount').val(vat);
                $('#total_amount').val(total);
            }
            $('#net_amount, #account_head_id').on('change input', calcAmounts);

            var currentIndex = 0;
            var totalFiles = {{ $receipt->files->count() }};

            function showFile(index) {
                $('.file-item').hide();
                $('.file-item[data-index="' + index + '"]').show();
                $('#fileCount').text((index + 1) + ' / ' + totalFiles);
                currentIndex = index;
            }

            $('#prevFile').click(function() {
                if (currentIndex > 0) showFile(currentIndex - 1);
            });
            $('#nextFile').click(function() {
                if (currentIndex < totalFiles - 1) showFile(currentIndex + 1);
            });
            $('#fileList').on('click', '.fileThumb', function() {
                showFile($(this).closest('.file-list-item').data('index'));
            });

            $('#uploadFileInput').on('change', function() {
                var formData = new FormData();
                formData.append('file', this.files[0]);
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
                $.ajax({
                    url: baseUrl + '/' + receiptId + '/files',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(d) {
                        if (d.success) {
                            toastr.success(d.message, 'Success!');
                            setTimeout(() => location.reload(), 1000);
                        }
                    }
                });
            });

            $('#fileList').on('click', '.deleteFileBtn', function() {
                if (!confirm('Delete this file?')) return;
                var fileId = $(this).data('id');
                $.get(baseUrl + '/' + receiptId + '/files/' + fileId + '/delete', function(d) {
                    if (d.success) {
                        toastr.success(d.message, 'Success!');
                        setTimeout(() => location.reload(), 1000);
                    }
                });
            });

            $('#saveBtn').click(function() {
                if ($('#paid').val() === 'no' && hasPaidTxn) {
                    if (!confirm(
                            'Marking as unpaid will remove the existing payment transaction. Continue?'))
                        return;
                }

                $.post(baseUrl + '/' + receiptId + '/update', {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    status: $('#status').val(),
                    account_head_id: $('#account_head_id').val(),
                    invoice_date: $('#invoice_date').val(),
                    due_date: $('#due_date').val(),
                    invoice_number: $('#invoice_number').val(),
                    net_amount: $('#net_amount').val(),
                    paid: $('#paid').val(),
                    payment_method: $('#payment_method').val(),
                    description: $('#description').val(),
                }, function(d) {
                    if (d.status == 303) {
                        $('.ermsg').html(d.message);
                        $('html, body').animate({
                            scrollTop: 0
                        }, 300);
                    } else {
                        toastr.success(d.message, 'Success!');
                        $('.ermsg').html('');
                        setTimeout(() => location.reload(), 800);
                    }
                });
            });

            $('#cancelBtn').click(function() {
                if (!confirm('Cancel this receipt?')) return;
                $.get(baseUrl + '/' + receiptId + '/cancel', function(d) {
                    if (d.success) {
                        toastr.success(d.message, 'Success!');
                        setTimeout(() => window.location = "{{ route('admin.receipt.index') }}",
                            1000);
                    }
                });
            });
        });
    </script>
@endsection