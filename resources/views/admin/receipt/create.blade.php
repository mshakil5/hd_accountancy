@extends('admin.layouts.admin')

@section('content')
    <style>
        .detail-label { font-size: 12px; color: #888; margin-bottom: 3px; }
        .detail-field { margin-bottom: 14px; }
        .step-header { background: #f8f9fa; border-radius: 10px; padding: 12px 18px; margin-bottom: 20px; font-weight: 600; font-size: 14px; color: #495057; border-left: 4px solid #007bff; }
        .file-preview-bar { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
        .file-preview-item { position: relative; width: 70px; height: 70px; border: 2px solid #dee2e6; border-radius: 6px; overflow: hidden; }
        .file-preview-item img { width: 100%; height: 100%; object-fit: cover; }
        .file-preview-item .pdf-icon { display: flex; align-items: center; justify-content: center; height: 100%; background: #f8f9fa; font-size: 24px; color: #dc3545; }
        .file-preview-item .remove-file { position: absolute; top: 1px; right: 1px; background: rgba(220,53,69,0.85); color: #fff; border: none; border-radius: 50%; width: 18px; height: 18px; font-size: 10px; line-height: 18px; text-align: center; cursor: pointer; padding: 0; }
        .step-section { display: none; }
        .step-section.active { display: block; }
        .step-nav { display: flex; gap: 10px; margin-bottom: 20px; }
        .step-btn { padding: 8px 20px; border-radius: 20px; border: 2px solid #dee2e6; background: #fff; color: #888; font-size: 13px; cursor: default; }
        .step-btn.active { border-color: #007bff; background: #007bff; color: #fff; }
        .step-btn.done { border-color: #28a745; background: #28a745; color: #fff; }
    </style>

    <section class="content">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between mt-3 mb-3">
                <a href="{{ route('admin.receipt.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
                <h5 class="mb-0">Create New Receipt</h5>
                <div></div>
            </div>

            <div class="ermsg mb-3"></div>

            {{-- Step Nav --}}
            <div class="step-nav">
                <div class="step-btn active" id="stepBtn1"><i class="fa fa-user"></i> 1. Select Client</div>
                <div class="step-btn" id="stepBtn2"><i class="fa fa-upload"></i> 2. Upload Files</div>
                <div class="step-btn" id="stepBtn3"><i class="fa fa-file-text"></i> 3. Receipt Details</div>
            </div>

            <form id="createReceiptForm" enctype="multipart/form-data">
                @csrf

                {{-- STEP 1: Client Selection --}}
                <div class="step-section active" id="step1">
                    <div class="card card-outline card-primary">
                        <div class="card-header"><h3 class="card-title">Step 1: Select Client</h3></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 detail-field">
                                    <label class="detail-label">Client Credential (Login User) <span class="text-danger">*</span></label>
                                    <select class="form-control select2-client-cred" id="client_credential_id" name="client_credential_id" style="width:100%;">
                                        <option value="">Search client name...</option>
                                    </select>
                                </div>
                                <div class="col-md-6 detail-field">
                                    <label class="detail-label">Business / Client <span class="text-danger">*</span></label>
                                    <select class="form-control select2" id="client_id" name="client_id" style="width:100%;">
                                        <option value="">First select client credential</option>
                                    </select>
                                </div>
                            </div>
                            <div class="client-info-box" id="clientInfoBox" style="display:none;">
                                <div class="alert alert-info mb-0" id="clientInfoText"></div>
                            </div>
                            <div class="mt-3">
                                <button type="button" class="btn btn-primary" id="step1Next">Next <i class="fa fa-arrow-right"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- STEP 2: File Upload --}}
                <div class="step-section" id="step2">
                    <div class="card card-outline card-info">
                        <div class="card-header"><h3 class="card-title">Step 2: Upload Receipt Files</h3></div>
                        <div class="card-body">
                            <div class="detail-field">
                                <label class="detail-label">Receipt Date</label>
                                <input type="date" class="form-control" id="receipt_date" name="receipt_date" value="{{ now()->format('Y-m-d') }}" style="max-width:220px;">
                            </div>
                            <div class="detail-field">
                                <label class="detail-label">Client Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Optional notes..."></textarea>
                            </div>
                            <div class="detail-field">
                                <label class="detail-label">Files (JPG, PNG, PDF) <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="fileInput" name="files[]" multiple accept=".jpg,.jpeg,.png,.pdf">
                                <small class="text-muted">You can select multiple files.</small>
                            </div>
                            <div class="file-preview-bar" id="filePreviewBar"></div>
                            <div class="mt-3 d-flex gap-2">
                                <button type="button" class="btn btn-secondary" id="step2Back"><i class="fa fa-arrow-left"></i> Back</button>
                                <button type="button" class="btn btn-primary" id="step2Next">Next <i class="fa fa-arrow-right"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- STEP 3: Receipt Details --}}
                <div class="step-section" id="step3">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-outline card-success">
                                <div class="card-header"><h3 class="card-title">Step 3: Audit & Process Details</h3></div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 detail-field">
                                            <label class="detail-label">Status Workflow</label>
                                            <select class="form-control" id="status" name="status">
                                                <option value="pending">Pending</option>
                                                <option value="to_review">To Review</option>
                                                <option value="ready">Ready</option>
                                                <option value="archived">Archived</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 detail-field">
                                            <label class="detail-label">Account Type</label>
                                            <select class="form-control" id="account_type_id" name="account_type_id">
                                                <option value="">Select Account Type</option>
                                                @foreach($accountTypes as $type)
                                                    <option value="{{ $type->id }}">{{ $type->name }} ({{ ucfirst($type->category) }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6 detail-field">
                                            <label class="detail-label">Account Head</label>
                                            <select class="form-control select2" id="account_head_id" name="account_head_id" style="width:100%;">
                                                <option value="">First Select Account Type</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-3 detail-field">
                                            <label class="detail-label">Invoice Date</label>
                                            <input type="date" class="form-control" id="invoice_date" name="invoice_date" value="{{ now()->format('Y-m-d') }}">
                                        </div>
                                        <div class="col-md-3 detail-field">
                                            <label class="detail-label">Due Date</label>
                                            <input type="date" class="form-control" id="due_date" name="due_date">
                                        </div>
                                        <div class="col-md-6 detail-field">
                                            <label class="detail-label">Invoice / Reference Number</label>
                                            <input type="text" class="form-control" id="invoice_number" name="invoice_number" placeholder="INV-0001">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-3 detail-field">
                                            <label class="detail-label">Net Amount (£)</label>
                                            <input type="number" step="0.01" class="form-control" id="net_amount" name="net_amount" placeholder="0.00">
                                        </div>
                                        <div class="col-md-2 detail-field">
                                            <label class="detail-label">Tax Rate (%)</label>
                                            <input type="text" class="form-control" id="tax_percent" name="tax_percent" readonly value="0">
                                        </div>
                                        <div class="col-md-2 detail-field">
                                            <label class="detail-label">Tax Amt (£)</label>
                                            <input type="text" class="form-control" id="tax_amount" name="tax_amount" readonly value="0">
                                        </div>
                                        <div class="col-md-2 detail-field">
                                            <label class="detail-label">VAT Amount (£)</label>
                                            <input type="number" step="0.01" class="form-control" id="vat_amount" name="vat_amount" value="0">
                                        </div>
                                        <div class="col-md-3 detail-field">
                                            <label class="detail-label">Total Amount (£)</label>
                                            <input type="text" class="form-control" id="total_amount" readonly value="£0.00">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4 detail-field">
                                            <label class="detail-label">Supplier</label>
                                            <input type="text" class="form-control" id="supplier" name="supplier" placeholder="Supplier name...">
                                        </div>
                                        <div class="col-md-4 detail-field">
                                            <label class="detail-label">Is Paid?</label>
                                            <select class="form-control" id="paid" name="paid">
                                                <option value="no">No</option>
                                                <option value="yes">Yes</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 detail-field" id="method_box" style="display:none;">
                                            <label class="detail-label">Payment Method</label>
                                            <select class="form-control" id="payment_method" name="payment_method">
                                                <option value="cash">Cash</option>
                                                <option value="bank">Bank</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mt-3 d-flex gap-2">
                                        <button type="button" class="btn btn-secondary" id="step3Back"><i class="fa fa-arrow-left"></i> Back</button>
                                        <button type="button" class="btn btn-success" id="submitBtn"><i class="fa fa-save"></i> Create Receipt</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </section>
@endsection

@section('script')
<script>
$(function () {
    const baseUrl = "{{ url('/admin/receipts') }}";

    // ── Step Navigation ──
    function goToStep(step) {
        $('.step-section').removeClass('active');
        $('#step' + step).addClass('active');
        $('.step-btn').removeClass('active');
        for (let i = 1; i < step; i++) $('#stepBtn' + i).removeClass('active').addClass('done');
        $('#stepBtn' + step).addClass('active').removeClass('done');
    }

    // ── Select2: Client Credential ──
    $('#client_credential_id').select2({
        placeholder: 'Search client name...',
        allowClear: true,
        ajax: {
            url: "{{ url('/admin/receipts/search-clients') }}",
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term }),
            processResults: data => ({ results: data }),
            cache: true
        }
    }).on('change', function () {
        const credId = $(this).val();
        const clientDrop = $('#client_id');
        clientDrop.html('<option value="">Loading...</option>').trigger('change');
        $('#clientInfoBox').hide();
        if (!credId) {
            clientDrop.html('<option value="">First select client credential</option>').trigger('change');
            return;
        }
        $.get(baseUrl + '/get-clients-by-credential', { client_credential_id: credId }, function (data) {
            let opts = '<option value="">Select Business / Client</option>';
            $.each(data, function (i, c) {
                opts += `<option value="${c.id}" data-info="${c.info}">${c.name}</option>`;
            });
            clientDrop.html(opts).trigger('change');
        });
    });

    $('#client_id').select2({ placeholder: 'Select Business / Client', allowClear: true })
        .on('change', function () {
            const selected = $(this).find('option:selected');
            const info = selected.data('info');
            if (info) {
                $('#clientInfoText').text(info);
                $('#clientInfoBox').show();
            } else {
                $('#clientInfoBox').hide();
            }
        });

    $('#step1Next').click(function () {
        if (!$('#client_credential_id').val()) { toastr.error('Please select a Client Credential.'); return; }
        if (!$('#client_id').val()) { toastr.error('Please select a Business / Client.'); return; }
        goToStep(2);
    });

    $('#step2Back').click(() => goToStep(1));
    $('#step3Back').click(() => goToStep(2));

    // ── File Preview ──
    let selectedFiles = [];

    $('#fileInput').on('change', function () {
        const newFiles = Array.from(this.files);
        newFiles.forEach(f => selectedFiles.push(f));
        renderPreviews();
    });

    function renderPreviews() {
        const bar = $('#filePreviewBar');
        bar.empty();
        selectedFiles.forEach((f, i) => {
            const isPdf = f.type === 'application/pdf';
            const html = `
                <div class="file-preview-item" data-index="${i}">
                    ${isPdf
                        ? `<div class="pdf-icon"><i class="fa fa-file-pdf-o"></i></div>`
                        : `<img src="${URL.createObjectURL(f)}" alt="">`
                    }
                    <button type="button" class="remove-file" data-index="${i}">&times;</button>
                </div>`;
            bar.append(html);
        });
    }

    $(document).on('click', '.remove-file', function () {
        const idx = parseInt($(this).data('index'));
        selectedFiles.splice(idx, 1);
        renderPreviews();
    });

    $('#step2Next').click(function () {
        if (selectedFiles.length === 0) { toastr.error('Please upload at least one file.'); return; }
        goToStep(3);
    });

    // ── Account Type → Head ──
    $('#account_type_id').change(function () {
        const typeId = $(this).val();
        const headDrop = $('#account_head_id');
        headDrop.html('<option value="">Loading...</option>').trigger('change');
        if (!typeId) {
            headDrop.html('<option value="">First Select Account Type</option>').trigger('change');
            $('#tax_percent').val(0);
            calculateAmounts();
            return;
        }
        $.get(baseUrl + '/get-account-heads', { account_type_id: typeId }, function (data) {
            let opts = '<option value="">Select Account Head</option>';
            $.each(data, function (i, h) {
                const rate = h.tax_rate ? h.tax_rate.rate : 0;
                opts += `<option value="${h.id}" data-rate="${rate}">${h.code} - ${h.name}</option>`;
            });
            headDrop.html(opts).trigger('change');
        });
    });

    $('#account_head_id').change(function () {
        const rate = parseFloat($(this).find('option:selected').data('rate')) || 0;
        $('#tax_percent').val(rate);
        calculateAmounts();
    });

    function calculateAmounts() {
        const net  = parseFloat($('#net_amount').val()) || 0;
        const rate = parseFloat($('#tax_percent').val()) || 0;
        const vat  = parseFloat($('#vat_amount').val()) || 0;
        const tax  = net * (rate / 100);
        $('#tax_amount').val(tax.toFixed(2));
        $('#total_amount').val('£' + (net + tax + vat).toFixed(2));
    }

    $('#net_amount, #vat_amount').on('input keyup change', calculateAmounts);

    $('#paid').change(function () {
        $(this).val() === 'yes' ? $('#method_box').fadeIn() : $('#method_box').fadeOut();
    });

    // ── Submit ──
    $('#submitBtn').click(function () {
        const status = $('#status').val();

        if (['ready', 'archived'].includes(status)) {
            let errs = [];
            if (!$('#account_type_id').val()) errs.push('Account Type is required.');
            if (!$('#account_head_id').val()) errs.push('Account Head is required.');
            if (!$('#invoice_date').val())    errs.push('Invoice Date is required.');
            if (!$('#net_amount').val())      errs.push('Net Amount is required.');
            if (errs.length) {
                $('.ermsg').html(`<div class='alert alert-danger'><ul class='mb-0'><li>${errs.join('</li><li>')}</li></ul></div>`);
                window.scrollTo(0, 0);
                return;
            }
        }

        if (selectedFiles.length === 0) {
            toastr.error('Please upload at least one file.');
            goToStep(2);
            return;
        }

        const formData = new FormData();
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        formData.append('client_credential_id', $('#client_credential_id').val());
        formData.append('client_id',            $('#client_id').val());
        formData.append('receipt_date',         $('#receipt_date').val());
        formData.append('notes',                $('#notes').val());
        formData.append('supplier',             $('#supplier').val());
        formData.append('status',               $('#status').val());
        formData.append('account_type_id',      $('#account_type_id').val());
        formData.append('account_head_id',      $('#account_head_id').val());
        formData.append('invoice_date',         $('#invoice_date').val());
        formData.append('due_date',             $('#due_date').val());
        formData.append('invoice_number',       $('#invoice_number').val());
        formData.append('net_amount',           $('#net_amount').val());
        formData.append('tax_percent',          $('#tax_percent').val());
        formData.append('tax_amount',           $('#tax_amount').val());
        formData.append('vat_amount',           $('#vat_amount').val());
        formData.append('paid',                 $('#paid').val());
        formData.append('payment_method',       $('#payment_method').val());
        formData.append('description',          $('#description').val());

        selectedFiles.forEach(f => formData.append('files[]', f));

        $('#submitBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Creating...');

        $.ajax({
            url: "{{ route('admin.receipt.store') }}",
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (d) {
                if (d.status == 303) {
                    $('.ermsg').html(d.message);
                    window.scrollTo(0, 0);
                    $('#submitBtn').prop('disabled', false).html('<i class="fa fa-save"></i> Create Receipt');
                } else {
                    toastr.success(d.message);
                    setTimeout(() => window.location.href = "{{ url('/admin/receipts') }}/" + d.id, 1000);
                }
            },
            error: function () {
                toastr.error('Something went wrong.');
                $('#submitBtn').prop('disabled', false).html('<i class="fa fa-save"></i> Create Receipt');
            }
        });
    });
});
</script>
@endsection