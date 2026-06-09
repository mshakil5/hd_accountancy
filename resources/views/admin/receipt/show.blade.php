@extends('admin.layouts.admin')

@section('content')
    <style>
        .file-viewer img { max-width: 100%; border-radius: 8px; }
        .file-viewer iframe { width: 100%; height: 580px; border-radius: 8px; }
        .detail-label { font-size: 12px; color: #888; margin-bottom: 3px; }
        .detail-field { margin-bottom: 14px; }
        .receipt-header { background: #f8f9fa; border-radius: 10px; padding: 14px 18px; margin-bottom: 16px; }
        .notes-box { background: #fffdf0; border-left: 4px solid #ffc107; padding: 12px; border-radius: 4px; margin-top: 15px; }
    </style>

    <section class="content">
        <div class="container-fluid">
            <div class="receipt-header d-flex align-items-center justify-content-between mt-3">
                <a href="{{ route('admin.receipt.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
                <div class="d-flex align-items-center gap-2">
                    <strong>{{ $receipt->receipt_number }}</strong>
                    <span class="badge badge-info">{{ ucfirst($receipt->status) }}</span>
                </div>
                <div>
                    @if ($prev) <a href="{{ route('admin.receipt.show', $prev) }}" class="btn btn-default btn-sm"><i class="fa fa-chevron-left"></i> Prev</a> @endif
                    @if ($next) <a href="{{ route('admin.receipt.show', $next) }}" class="btn btn-default btn-sm">Next <i class="fa fa-chevron-right"></i></a> @endif
                </div>
            </div>

            <div class="ermsg"></div>

            <div class="row">
                <div class="col-md-7">
                    <div class="card card-outline card-primary">
                        <div class="card-header"><h3 class="card-title">Receipt Media Viewer</h3></div>
                        <div class="card-body">
                            <div class="file-viewer text-center">
                                @if($receipt->files->count() > 0)
                                    @php $firstFile = $receipt->files->first(); @endphp
                                    @if($firstFile->file_type == 'image')
                                        <img src="{{ asset($firstFile->file_path) }}" alt="Receipt">
                                    @elseif($firstFile->file_type == 'pdf')
                                        <iframe src="{{ asset($firstFile->file_path) }}"></iframe>
                                    @endif
                                @else
                                    <p class="text-muted py-5">No files attached to this receipt.</p>
                                @endif
                            </div>

                            @if($receipt->notes)
                                <div class="notes-box">
                                    <strong><i class="fa fa-pencil"></i> Client Notes:</strong>
                                    <p class="mb-0 text-dark" style="white-space: pre-line;">{{ $receipt->notes }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="card card-outline card-success">
                        <div class="card-header"><h3 class="card-title">Audit & Process Details</h3></div>
                        <div class="card-body">
                            <form id="receiptForm">
                                <div class="detail-field">
                                    <label class="detail-label">Status Workflow</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="pending" {{ $receipt->status == 'pending' ? 'selected':'' }}>Pending</option>
                                        <option value="to_review" {{ $receipt->status == 'to_review' ? 'selected':'' }}>To Review</option>
                                        <option value="ready" {{ $receipt->status == 'ready' ? 'selected':'' }}>Ready</option>
                                        <option value="archived" {{ $receipt->status == 'archived' ? 'selected':'' }}>Archived</option>
                                    </select>
                                </div>

                                <div class="detail-field">
                                    <label class="detail-label">Account Type</label>
                                    <select class="form-control" id="account_type_id" name="account_type_id">
                                        <option value="">Select Account Type</option>
                                        @foreach($accountTypes as $type)
                                            <option value="{{ $type->id }}" {{ ($receipt->detail?->accountHead?->account_type_id == $type->id) ? 'selected' : '' }}>
                                                {{ $type->name }} ({{ ucfirst($type->category) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="detail-field">
                                    <label class="detail-label">Account Head</label>
                                    <select class="form-control select2" id="account_head_id" name="account_head_id" style="width:100%;">
                                        <option value="">First Select Account Type</option>
                                        @foreach($heads as $h)
                                            <option value="{{ $h->id }}" data-rate="{{ $h->taxRate?->rate ?? 0 }}" {{ $receipt->detail?->account_head_id == $h->id ? 'selected':'' }}>
                                                {{ $h->code }} - {{ $h->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 detail-field">
                                        <label class="detail-label">Invoice Date</label>
                                        <input type="date" class="form-control" id="invoice_date" value="{{ $receipt->detail?->invoice_date ?? ($receipt->receipt_date ? $receipt->receipt_date->format('Y-m-d') : '') }}">
                                    </div>
                                    <div class="col-md-6 detail-field">
                                        <label class="detail-label">Due Date</label>
                                        <input type="date" class="form-control" id="due_date" value="{{ $receipt->detail?->due_date }}">
                                    </div>
                                </div>

                                <div class="detail-field">
                                    <label class="detail-label">Invoice / Reference Number</label>
                                    <input type="text" class="form-control" id="invoice_number" value="{{ $receipt->detail?->invoice_number }}">
                                </div>

                                <div class="row">
                                    <div class="col-md-6 detail-field">
                                        <label class="detail-label">Net Amount (£)</label>
                                        <input type="number" step="0.01" class="form-control" id="net_amount" value="{{ $receipt->detail?->net_amount }}">
                                    </div>
                                    <div class="col-md-3 detail-field">
                                        <label class="detail-label">Tax Rate (%)</label>
                                        <input type="text" class="form-control" id="tax_percent" readonly value="{{ $receipt->detail?->accountHead?->taxRate?->rate ?? 0 }}">
                                    </div>
                                    <div class="col-md-3 detail-field">
                                        <label class="detail-label">Tax Amt (£)</label>
                                        <input type="text" class="form-control" id="tax_amount" readonly value="{{ $receipt->detail?->tax_amount ?? 0 }}">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 detail-field">
                                        <label class="detail-label">VAT Amount (£) [Manual Entry]</label>
                                        <input type="number" step="0.01" class="form-control" id="vat_amount" value="{{ $receipt->detail?->vat_amount ?? 0 }}">
                                    </div>
                                    <div class="col-md-6 detail-field">
                                        <label class="detail-label">Total Amount (£) [Net+Tax+VAT]</label>
                                        <input type="text" class="form-control" id="total_amount" readonly value="{{ $receipt->detail?->total_amount }}">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 detail-field">
                                        <label class="detail-label">Is Paid?</label>
                                        <select class="form-control" id="paid">
                                            <option value="no" {{ $receipt->detail?->paid == 0 ? 'selected':'' }}>No</option>
                                            <option value="yes" {{ $receipt->detail?->paid == 1 ? 'selected':'' }}>Yes</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 detail-field" id="method_box" style="{{ $receipt->detail?->paid == 1 ? '':'display:none;' }}">
                                        <label class="detail-label">Payment Method</label>
                                        <select class="form-control" id="payment_method">
                                            <option value="cash" {{ $receipt->detail?->payment_method == 'cash' ? 'selected':'' }}>Cash</option>
                                            <option value="bank" {{ $receipt->detail?->payment_method == 'bank' ? 'selected':'' }}>Bank</option>
                                            <option value="card" {{ $receipt->detail?->payment_method == 'card' ? 'selected':'' }}>Card</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="detail-field">
                                    <label class="detail-label">Internal Audit Description</label>
                                    <textarea class="form-control" id="description" rows="2">{{ $receipt->detail?->description }}</textarea>
                                </div>

                                <div class="mt-4">
                                    <button type="button" class="btn btn-success" id="saveBtn"><i class="fa fa-save"></i> Save Details</button>
                                    <button type="button" class="btn btn-danger float-right" id="cancelBtn"><i class="fa fa-ban"></i> Cancel Receipt</button>
                                </div>
                            </form>
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
            const receiptId = "{{ $receipt->id }}";
            const baseUrl = "{{ url('/admin/receipts') }}";
            const currentStatus = "{{ $receipt->status }}";

            // Status Lock Security
            if (currentStatus === 'archived' || currentStatus === 'cancelled') {
                $('#receiptForm input, #receiptForm select, #receiptForm textarea').prop('disabled', true);
                $('#saveBtn, #cancelBtn').prop('disabled', true).addClass('disabled');
                $('.ermsg').html(`<div class='alert alert-warning'><i class='fa fa-lock'></i> This receipt is locked under <strong>${currentStatus.toUpperCase()}</strong> status. No updates allowed.</div>`);
            }

            // Type-Based Dynamic Account Head Loading
            $('#account_type_id').change(function() {
                let typeId = $(this).val();
                let headDropdown = $('#account_head_id');
                
                headDropdown.html('<option value="">Loading Heads...</option>').trigger('change');

                if (!typeId) {
                    headDropdown.html('<option value="">First Select Account Type</option>').trigger('change');
                    $('#tax_percent').val(0);
                    calculateAmounts();
                    return;
                }

                $.get(baseUrl + '/get-account-heads', { account_type_id: typeId }, function(data) {
                    let options = '<option value="">Select Account Head</option>';
                    $.each(data, function(index, head) {
                        let taxRate = head.tax_rate ? head.tax_rate.rate : 0;
                        options += `<option value="${head.id}" data-rate="${taxRate}">${head.code} - ${head.name}</option>`;
                    });
                    headDropdown.html(options).trigger('change');
                });
            });

            // Account Head চেঞ্জ হলে ট্যাক্স রেট ইনপুট বক্সে এসাইন হবে
            $('#account_head_id').change(function() {
                let selectedOption = $(this).find('option:selected');
                let rate = parseFloat(selectedOption.data('rate')) || 0;
                $('#tax_percent').val(rate);
                calculateAmounts();
            });

            // 🧮 লাইভ ফিন্যান্সিয়াল ম্যাথ লজিক (Net + Tax + VAT = Total)
            function calculateAmounts() {
                let net = parseFloat($('#net_amount').val()) || 0;
                let rate = parseFloat($('#tax_percent').val()) || 0;
                let vat = parseFloat($('#vat_amount').val()) || 0;
                
                // ১. ট্যাক্স হিসাব
                let taxAmount = net * (rate / 100);
                $('#tax_amount').val(taxAmount.toFixed(2));
                
                // ২. গ্র্যান্ড টোটাল হিসাব (Net + Tax + VAT)
                let total = net + taxAmount + vat;
                $('#total_amount').val('£' + total.toFixed(2));
            }

            // ইভেন্ট লিসেনার ট্রিগার
            $('#net_amount, #vat_amount').on('input keyup change', calculateAmounts);

            $('#paid').change(function() {
                if ($(this).val() === 'yes') $('#method_box').fadeIn();
                else $('#method_box').fadeOut();
            });

            // Save Trigger
            $('#saveBtn').click(function() {
                $.post(baseUrl + '/' + receiptId + '/update', {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    status: $('#status').val(),
                    account_head_id: $('#account_head_id').val(),
                    invoice_date: $('#invoice_date').val(),
                    due_date: $('#due_date').val(),
                    invoice_number: $('#invoice_number').val(),
                    net_amount: $('#net_amount').val(),
                    tax_percent: $('#tax_percent').val(),
                    tax_amount: $('#tax_amount').val(),
                    vat_amount: $('#vat_amount').val(),
                    paid: $('#paid').val(),
                    payment_method: $('#payment_method').val(),
                    description: $('#description').val(),
                }, function(d) {
                    if (d.status == 303) {
                        $('.ermsg').html(d.message);
                        window.scrollTo(0, 0);
                    } else {
                        toastr.success(d.message);
                        $('.ermsg').html('');
                        setTimeout(() => window.location.reload(), 1000);
                    }
                });
            });

            // Cancel Trigger
            $('#cancelBtn').click(function() {
                if (!confirm('Are you sure you want to cancel? This clears all accounting logs.')) return;
                $.get(baseUrl + '/' + receiptId + '/cancel', function(d) {
                    if (d.success) {
                        toastr.success(d.message);
                        setTimeout(() => window.location = "{{ route('admin.receipt.index') }}", 1000);
                    } else {
                        toastr.error(d.message);
                    }
                });
            });
        });
    </script>
@endsection