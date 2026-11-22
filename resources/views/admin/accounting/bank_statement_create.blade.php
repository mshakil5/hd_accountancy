@extends('admin.layouts.admin')
@section('content')
<style>
    .detail-label { font-size: 12px; color: #888; margin-bottom: 3px; }
    .detail-field { margin-bottom: 14px; }
    .bank-box { border: 1px solid #e5eaf1; border-radius: 10px; padding: 14px; background: #f8fafc; }
    .bank-box.adding { border-color: #0284c7; background: #eff8ff; }
    .step-section { display: none; }
    .step-section.active { display: block; }
    .step-nav { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
    .step-btn { padding: 8px 20px; border-radius: 20px; border: 2px solid #dee2e6; background: #fff; color: #888; font-size: 13px; cursor: default; }
    .step-btn.active { border-color: #007bff; background: #007bff; color: #fff; }
    .step-btn.done { border-color: #28a745; background: #28a745; color: #fff; }
    .select2-container .select2-selection--single { height: 38px !important; border: 1px solid #ced4da !important; border-radius: .25rem !important; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 36px !important; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px !important; }
</style>

<section class="content">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between mt-3 mb-3">
            <a href="{{ route('admin.bank-statements.index', request('client_credential_id') ? ['client_credential_id' => request('client_credential_id')] : []) }}" class="btn btn-secondary btn-sm">
                <i class="fa fa-arrow-left"></i> Back
            </a>
            <h5 class="mb-0">Create Bank Statement</h5>
            <div></div>
        </div>

        <div class="ermsg mb-3"></div>

        <div class="step-nav">
            <div class="step-btn active" id="stepBtn1"><i class="fa fa-user"></i> 1. Select Client</div>
            <div class="step-btn" id="stepBtn2"><i class="fa fa-university"></i> 2. Bank Account</div>
            <div class="step-btn" id="stepBtn3"><i class="fa fa-upload"></i> 3. Upload Statements</div>
        </div>

        <form id="createStatementForm" enctype="multipart/form-data">
            @csrf

            {{-- STEP 1: Client --}}
            <div class="step-section active" id="step1">
                <div class="card card-outline card-primary">
                    <div class="card-header"><h3 class="card-title">Step 1: Select Client</h3></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 detail-field">
                                <label class="detail-label">Client Credential (Login User) <span class="text-danger">*</span></label>
                                <select class="form-control select2" id="client_credential_id" name="client_credential_id" style="width:100%;">
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

            {{-- STEP 2: Bank account --}}
            <div class="step-section" id="step2">
                <div class="card card-outline card-info">
                    <div class="card-header"><h3 class="card-title">Step 2: Bank Account</h3></div>
                    <div class="card-body">
                        <div class="detail-field">
                            <label class="detail-label">Bank Account <span class="text-danger">*</span></label>
                            <select class="form-control select2" id="bank_account_id" name="bank_account_id" style="width:100%;">
                                <option value="">Select bank account</option>
                            </select>
                            <small class="text-muted">Pick the account this statement belongs to, or add a new one below.</small>
                            <div id="bankHint" class="alert alert-secondary py-2 mt-2 mb-0" style="display:none;font-size:12px;"></div>
                        </div>

                        <div class="bank-box" id="newBankBox">
                            <div class="d-flex align-items-center justify-content-between">
                                <strong style="font-size:13px;"><i class="fa fa-plus-circle text-success"></i> Add a new bank account</strong>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="toggleNewBank" style="border-radius:20px;font-size:12px;">
                                    <i class="fa fa-chevron-down"></i> Show
                                </button>
                            </div>
                            <div id="newBankFields" style="display:none;margin-top:14px;">
                                <div class="row">
                                    <div class="col-md-5 detail-field">
                                        <label class="detail-label">Bank Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="new_bank_name" placeholder="e.g. Barclays">
                                    </div>
                                    <div class="col-md-3 detail-field">
                                        <label class="detail-label">Account Last 4 Digits</label>
                                        <input type="text" class="form-control" id="new_bank_last_four" maxlength="4" inputmode="numeric" placeholder="1234">
                                    </div>
                                    <div class="col-md-4 detail-field">
                                        <label class="detail-label">Sort Code</label>
                                        <input type="text" class="form-control" id="new_bank_sort_code" placeholder="20-00-00">
                                    </div>
                                </div>
                                <button type="button" class="btn btn-success btn-sm" id="addBankBtn">
                                    <i class="fa fa-save"></i> Add Bank Account
                                </button>
                            </div>
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <button type="button" class="btn btn-secondary" id="step2Back"><i class="fa fa-arrow-left"></i> Back</button>
                            <button type="button" class="btn btn-primary" id="step2Next">Next <i class="fa fa-arrow-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- STEP 3: Upload --}}
            <div class="step-section" id="step3">
                <div class="card card-outline card-success">
                    <div class="card-header"><h3 class="card-title">Step 3: Upload Statements</h3></div>
                    <div class="card-body">
                        <div class="detail-field">
                            <label class="detail-label">Statement PDFs <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="fileInput" name="files[]" accept=".pdf,application/pdf" multiple>
                            <small class="text-muted">Select one or more PDFs (up to 20, 50 MB each). Each PDF becomes a separate statement.</small>
                        </div>
                        <div id="selectedFile" class="alert alert-secondary py-2" style="display:none;"></div>

                        <div class="alert alert-info py-2" style="font-size:12px;">
                            <i class="bi bi-info-circle"></i> Statements are uploaded as pending. Nothing reaches the books until an accountant verifies the rows and approves the reconciliation.
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <button type="button" class="btn btn-secondary" id="step3Back"><i class="fa fa-arrow-left"></i> Back</button>
                            <button type="button" class="btn btn-success" id="submitBtn"><i class="fa fa-save"></i> Create Statements</button>
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
    const baseUrl = "{{ url('/admin/bank-statements') }}";

    function goToStep(step) {
        $('.step-section').removeClass('active');
        $('#step' + step).addClass('active');
        $('.step-btn').removeClass('active');
        for (let i = 1; i < step; i++) $('#stepBtn' + i).removeClass('active').addClass('done');
        $('#stepBtn' + step).addClass('active').removeClass('done');
    }

    var presetCredentialId = "{{ $presetCredentialId ?: '' }}";
    var presetBusinessId   = "{{ $presetBusinessId ?: '' }}";

    // ── Step 1: client credential + business ──
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
        $('#bank_account_id').html('<option value="">Select bank account</option>').trigger('change');
        if (!credId) {
            clientDrop.html('<option value="">First select client credential</option>').trigger('change');
            return;
        }
        $.get(baseUrl + '/businesses', { client_credential_id: credId }, function (data) {
            let opts = '<option value="">Select Business / Client</option>';
            $.each(data, function (i, c) {
                opts += `<option value="${c.id}" data-info="${c.info}">${c.name}</option>`;
            });
            clientDrop.html(opts);
            if (presetBusinessId && clientDrop.find('option[value="' + presetBusinessId + '"]').length) {
                clientDrop.val(String(presetBusinessId));
                presetBusinessId = '';
            }
            clientDrop.trigger('change');
        });
    });

    if (presetCredentialId) {
        $.get("{{ url('/admin/receipts/search-clients') }}", { q: '', selected_id: presetCredentialId }, function (data) {
            var match = data.find(function (item) { return item.id == presetCredentialId; });
            if (match) {
                $('#client_credential_id').append(new Option(match.text, match.id, true, true)).trigger('change');
            }
        });
    }

    $('#client_id').select2({ placeholder: 'Select Business / Client', allowClear: true })
        .on('change', function () {
            const info = $(this).find('option:selected').data('info');
            if (info) { $('#clientInfoText').text(info); $('#clientInfoBox').show(); }
            else { $('#clientInfoBox').hide(); }
            loadBanks();
        });

    $('#step1Next').click(function () {
        if (!$('#client_credential_id').val()) { toastr.error('Please select a Client Credential.'); return; }
        if (!$('#client_id').val()) { toastr.error('Please select a Business / Client.'); return; }
        goToStep(2);
    });

    $('#step2Back').click(() => goToStep(1));
    $('#step3Back').click(() => goToStep(2));

    // ── Step 2: bank accounts ──
    // Same list/semantics as the app's bank picker: active accounts only for
    // the chosen business, legacy buckets hidden, sort code + last 4 shown.
    var bankOptions = {};

    function bankLabel(b) {
        var parts = [b.bank_name];
        if (b.sort_code) parts.push(b.sort_code);
        if (b.account_last_four) parts.push('••' + b.account_last_four);
        return parts.join(' · ');
    }

    function loadBanks(selectId) {
        const drop = $('#bank_account_id');
        const clientId = $('#client_id').val();
        bankOptions = {};
        $('#bankHint').hide();
        drop.html('<option value="">Loading...</option>').trigger('change');
        if (!clientId) {
            drop.html('<option value="">Select bank account</option>').trigger('change');
            return;
        }
        $.get("{{ route('admin.bank-statements.accounts') }}", {
            client_id: clientId,
            client_credential_id: $('#client_credential_id').val()
        }, function (data) {
            let opts = '<option value="">Select bank account</option>';
            $.each(data, function (i, b) {
                bankOptions[b.id] = b;
                opts += `<option value="${b.id}">${bankLabel(b)}</option>`;
            });
            drop.html(opts);
            if (selectId) drop.val(String(selectId));
            drop.trigger('change');
            if (!data.length) {
                $('#bankHint').text('This business has no bank account yet. Add one below, then pick it.').show();
            }
        }).fail(function () {
            drop.html('<option value="">Unable to load bank accounts</option>').trigger('change');
            $('#bankHint').text('Could not load the bank accounts for this business. Try again.').show();
        });
    }

    $('#bank_account_id').on('change', function () {
        const bank = bankOptions[$(this).val()];
        if (!bank) { $('#bankHint').hide(); return; }
        $('#bankHint').html('<i class="fa fa-university"></i> Selected: <strong>' + bankLabel(bank) + '</strong>').show();
    });

    $('#toggleNewBank').click(function () {
        const fields = $('#newBankFields');
        const open = fields.is(':visible');
        fields.slideToggle(150);
        $('#newBankBox').toggleClass('adding', !open);
        $(this).html(open ? '<i class="fa fa-chevron-down"></i> Show' : '<i class="fa fa-chevron-up"></i> Hide');
    });

    $('#addBankBtn').click(function () {
        const bankName = ($('#new_bank_name').val() || '').trim();
        const lastFour = ($('#new_bank_last_four').val() || '').trim();
        const sortCode = ($('#new_bank_sort_code').val() || '').trim();

        if (!bankName) { toastr.error('Enter the bank name.'); return; }
        if (lastFour && !/^\d{4}$/.test(lastFour)) { toastr.error('Account last 4 digits must be exactly 4 numbers.'); return; }

        const btn = $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Adding...');
        $.ajax({
            url: "{{ route('admin.bank-statements.store-bank-account') }}",
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                client_credential_id: $('#client_credential_id').val(),
                client_id: $('#client_id').val(),
                bank_name: bankName,
                account_last_four: lastFour,
                sort_code: sortCode
            },
            success: function (d) {
                if (d.reused) {
                    toastr.info(d.message);
                } else {
                    toastr.success('Bank account added.');
                }
                $('#new_bank_name, #new_bank_last_four, #new_bank_sort_code').val('');
                loadBanks(d.id);
            },
            error: function (xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.errors)
                    ? Object.values(xhr.responseJSON.errors).flat().join(' ')
                    : 'Could not add the bank account.';
                toastr.error(msg);
            },
            complete: function () {
                btn.prop('disabled', false).html('<i class="fa fa-save"></i> Add Bank Account');
            }
        });
    });

    $('#step2Next').click(function () {
        if (!$('#bank_account_id').val()) { toastr.error('Please select or add a bank account.'); return; }
        goToStep(3);
    });

    // ── Step 3: files + submit ──
    function isPdf(f) {
        return f.type === 'application/pdf' || /\.pdf$/i.test(f.name);
    }

    $('#fileInput').on('change', function () {
        const files = Array.from(this.files || []);
        const box = $('#selectedFile').empty().hide();
        if (!files.length) return;

        const invalid = files.find(f => !isPdf(f));
        if (invalid) {
            toastr.error('Please choose PDF bank statements only. Invalid file: ' + invalid.name);
            $(this).val('');
            return;
        }
        if (files.length > 20) {
            toastr.error('You can upload up to 20 statements at a time.');
            $(this).val('');
            return;
        }

        const list = $('<ul class="mb-0 pl-3"></ul>');
        files.forEach(function (f) {
            list.append($('<li></li>').text(f.name + ' (' + (f.size / 1048576).toFixed(1) + ' MB)'));
        });
        box.append($('<strong></strong>').text('Selected statements (' + files.length + '): ')).append(list).show();
    });

    $('#submitBtn').click(function () {
        if (!$('#bank_account_id').val()) { toastr.error('Please select a bank account.'); goToStep(2); return; }
        const files = Array.from($('#fileInput')[0].files || []);
        if (!files.length) { toastr.error('Please choose at least one statement PDF.'); return; }

        const invalid = files.find(f => !isPdf(f));
        if (invalid) { toastr.error('Please choose PDF bank statements only. Invalid file: ' + invalid.name); return; }

        const formData = new FormData();
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        formData.append('client_credential_id', $('#client_credential_id').val());
        formData.append('client_id', $('#client_id').val());
        formData.append('bank_account_id', $('#bank_account_id').val());
        files.forEach(function (f) { formData.append('files[]', f); });

        const btn = $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Uploading...');

        $.ajax({
            url: "{{ route('admin.bank-statements.store') }}",
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 300000,
            success: function (d) {
                if (d.status == 303) {
                    $('.ermsg').html(d.message);
                    window.scrollTo(0, 0);
                    btn.prop('disabled', false).html('<i class="fa fa-save"></i> Create Statements');
                    return;
                }
                toastr.success(d.message);
                setTimeout(function () {
                    window.location.href = d.redirect || "{{ route('admin.bank-statements.index') }}";
                }, 1200);
            },
            error: function (xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.errors)
                    ? Object.values(xhr.responseJSON.errors).flat().join(' ')
                    : 'Something went wrong while uploading the statements.';
                toastr.error(msg);
                btn.prop('disabled', false).html('<i class="fa fa-save"></i> Create Statements');
            }
        });
    });
});
</script>
@endsection
