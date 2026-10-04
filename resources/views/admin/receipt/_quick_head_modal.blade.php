{{-- Quick-add Account Head (mirrors admin.account_head.index form, scoped to this receipt's client) --}}
<div class="modal fade" id="quickHeadModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quick Add Account Head</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="ermsg-quick"></div>
                <div class="alert alert-info py-2" style="font-size:12px;" id="quickHeadScopeNote"></div>
                <div class="form-group">
                    <label>Account Type <span class="text-danger">*</span></label>
                    <select class="form-control" id="quick_head_type_id">
                        <option value="">Select account type</option>
                        @foreach($accountTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="quick_head_code" placeholder="e.g. 210">
                    <small id="quickHeadCodeMsg"></small>
                </div>
                <div class="form-group">
                    <label>Account Head <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="quick_head_name" placeholder="e.g. Transportation">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" class="form-control" id="quick_head_description">
                </div>
                <div class="form-group">
                    <label>Tax</label>
                    <select class="form-control" id="quick_head_tax_rate_id">
                        <option value="">Select tax</option>
                        @foreach($taxRates ?? [] as $tax)
                            <option value="{{ $tax->id }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="quickHeadSaveBtn">Create & Select</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var headUrl = "{{ url('/admin/account-heads') }}";
    var baseReceiptUrl = "{{ url('/admin/receipts') }}";
    var codeTimer = null;

    function currentCredId() {
        var v = $('#client_credential_id').val();
        @isset($credentialId)
            if (!v) v = '{{ $credentialId }}';
        @endisset
        return v || '';
    }

    function currentCredName() {
        var txt = $('#client_credential_id option:selected').text();
        if (!txt || txt.indexOf('Search') !== -1 || txt.indexOf('First') !== -1) {
            txt = "{{ isset($receipt) && $receipt->client && $receipt->client->credential ? trim(($receipt->client->credential->first_name ?? '').' '.($receipt->client->credential->last_name ?? '')) : '' }}";
        }
        return (txt || '').trim();
    }

    window.openQuickHeadModal = function() {
        var typeId = $('#account_type_id').val();
        if (!typeId) {
            toastr.warning('Select an Account Type first — the new head will use it.');
        }
        $('#quick_head_type_id').val(typeId || '');
        $('#quick_head_code').val('');
        $('#quick_head_name').val('');
        $('#quick_head_description').val('');
        $('#quick_head_tax_rate_id').val('');
        $('#quickHeadCodeMsg').text('').removeClass();
        $('.ermsg-quick').html('');
        var credId = currentCredId();
        var credName = currentCredName();
        $('#quickHeadScopeNote').html(
            credId
                ? "This head will be created for <strong>" + $('<div>').text(credName || ('Client #' + credId)).html() + "</strong> only (per-client, free-form code). Same fields as global making."
                : "No client selected — head will be created as <strong>Global</strong> (must follow Excel chart code range, like global making)."
        );
        $('#quickHeadModal').modal('show');
    };

    $('#quick_head_code').on('input', function() {
        clearTimeout(codeTimer);
        var code = $(this).val().trim();
        if (!code) { $('#quickHeadCodeMsg').text('').removeClass(); return; }
        codeTimer = setTimeout(function() {
            $.get(headUrl + '/check-code', {
                code: code,
                client_credential_id: currentCredId()
            }, function(d) {
                if (d.available) {
                    $('#quickHeadCodeMsg').text(code + ' is available').removeClass().addClass('text-success');
                } else {
                    $('#quickHeadCodeMsg').text(code + ' is not available for this client scope').removeClass().addClass('text-danger');
                }
            });
        }, 400);
    });

    $('#quick_head_type_id').on('change', function() {
        var c = $('#quick_head_code').val().trim();
        if (c) $('#quick_head_code').trigger('input');
    });

    window.reloadHeadDropdown = function(selectId) {
        var typeId = $('#account_type_id').val() || $('#quick_head_type_id').val();
        if (!typeId) return $.Deferred().reject();
        var headDrop = $('#account_head_id');
        headDrop.html('<option value="">Loading...</option>').trigger('change');
        return $.get(baseReceiptUrl + '/get-account-heads', {
            account_type_id: typeId,
            client_credential_id: currentCredId()
        }, function(data) {
            var options = '<option value="">Select Account Head</option>';
            $.each(data, function(i, head) {
                var rate = head.tax_rate ? head.tax_rate.rate : 0;
                options += '<option value="' + head.id + '" data-rate="' + rate + '">' + head.code + ' - ' + head.name + '</option>';
            });
            headDrop.html(options).trigger('change');
            if (selectId) {
                headDrop.val(String(selectId)).trigger('change');
            }
        });
    };

    $('#quickHeadSaveBtn').on('click', function() {
        var $btn = $(this);
        var payload = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            account_type_id: $('#quick_head_type_id').val(),
            code: $('#quick_head_code').val(),
            name: $('#quick_head_name').val(),
            description: $('#quick_head_description').val(),
            tax_rate_id: $('#quick_head_tax_rate_id').val(),
            client_credential_id: currentCredId()
        };
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Creating...');
        $.post(headUrl, payload, function(d) {
            if (d.status == 303) {
                $('.ermsg-quick').html(d.message);
            } else {
                // Keep main type in sync with the created head's type
                $('#account_type_id').val(payload.account_type_id);
                $('#quickHeadModal').modal('hide');
                toastr.success(d.message, 'Success!');
                window.reloadHeadDropdown(d.id);
            }
        }).fail(function() {
            toastr.error('Could not create head.');
        }).always(function() {
            $btn.prop('disabled', false).text('Create & Select');
        });
    });
})();
</script>
