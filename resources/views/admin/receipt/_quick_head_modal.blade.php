{{-- Quick-add Account Head (Type -> Sub Type -> Head), scoped to this receipt's client --}}
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
                        <option value="">Select Account Type</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Account Sub Type <span class="text-danger">*</span></label>
                    <select class="form-control" id="quick_head_sub_type_id" disabled>
                        <option value="">Select Account Type first</option>
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
                    <label>Normal Balance <span class="text-danger">*</span></label>
                    <select class="form-control" id="quick_head_normal_balance">
                        <option value="debit">Debit</option>
                        <option value="credit">Credit</option>
                    </select>
                </div>

                <input type="hidden" id="quick_head_client_id">
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

    // Target head dropdown: an explicitly-set element (e.g. a bank-reconciliation row),
    // else fall back to the receipt page's single #account_head_id.
    function qaHeadSel() {
        return (window.quickAddTargetEl && document.contains(window.quickAddTargetEl))
            ? $(window.quickAddTargetEl) : $('#account_head_id');
    }
    window.qaHeadSel = qaHeadSel;

    function currentCredId() {
        var v = $('#quick_head_client_id').val();
        if (!v) v = $('#client_credential_id').val();
        @isset($credentialId)
            if (!v) v = '{{ $credentialId }}';
        @endisset
        return v || '';
    }

    window.openQuickHeadModal = function() {
        $('#quick_head_code').val('');
        $('#quick_head_name').val('');
        $('#quick_head_normal_balance').val('debit');
        $('#quick_head_sub_type_id').html('<option value="">Select Account Type first</option>').prop('disabled', true);
        $('#quickHeadCodeMsg').text('').removeClass();
        $('.ermsg-quick').html('');
        var credId = currentCredId();
        var credName = '';
        @isset($receipt)
            credName = "{{ isset($receipt) && $receipt->client && $receipt->client->credential ? trim(($receipt->client->credential->first_name ?? '').' '.($receipt->client->credential->last_name ?? '')) : '' }}";
        @endisset
        $('#quick_head_client_id').val(credId);
        $('#quickHeadScopeNote').html(
            credId
                ? "This head will be created for <strong>" + $('<div>').text(credName || ('Client #' + credId)).html() + "</strong> only."
                : "No client selected — head will be created as <strong>Global</strong>."
        );

        // Load the five fixed types.
        $.get(headUrl + '/types', function(types) {
            var options = '<option value="">Select Account Type</option>';
            $.each(types, function(i, t) {
                options += '<option value="' + t.id + '">' + t.name + '</option>';
            });
            $('#quick_head_type_id').html(options);
            $('#quickHeadModal').modal('show');
        });
    };

    $('#quick_head_type_id').on('change', function() {
        var typeId = $(this).val();
        var $sub = $('#quick_head_sub_type_id');
        if (!typeId) {
            $sub.html('<option value="">Select Account Type first</option>').prop('disabled', true);
            return;
        }
        $sub.html('<option value="">Loading...</option>').prop('disabled', true);
        $.get(headUrl + '/sub-types/' + typeId, function(data) {
            var options = '<option value="">Select Account Sub Type</option>';
            $.each(data, function(i, sub) {
                options += '<option value="' + sub.id + '">' + sub.name + '</option>';
            });
            $sub.html(options).prop('disabled', false);
        });
    });

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

    window.reloadHeadDropdown = function(selectId) {
        var headDrop = qaHeadSel();
        headDrop.html('<option value="">Loading...</option>').trigger('change');
        return $.get(baseReceiptUrl + '/get-account-heads', {
            client_credential_id: currentCredId()
        }, function(data) {
            var options = '<option value="">Select Account Head</option>';
            $.each(data, function(i, head) {
                var typeId = head.account_type_id || '';
                options += '<option value="' + head.id + '" data-type-id="' + typeId + '">' + head.code + ' - ' + head.name + '</option>';
            });
            headDrop.html(options);
            if (selectId) headDrop.val(String(selectId));
            headDrop.trigger('change');
            if ($.fn.select2) headDrop.trigger('change.select2');
        });
    };

    $('#quickHeadSaveBtn').on('click', function() {
        var $btn = $(this);
        var payload = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            account_type_id:     $('#quick_head_type_id').val(),
            account_sub_type_id: $('#quick_head_sub_type_id').val(),
            code:                $('#quick_head_code').val(),
            name:                $('#quick_head_name').val(),
            normal_balance:      $('#quick_head_normal_balance').val(),
            client_credential_id: currentCredId()
        };
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Creating...');
        $.post(headUrl, payload, function(d) {
            if (d.status == 303) {
                $('.ermsg-quick').html(d.message);
            } else {
                $('#quickHeadModal').modal('hide');
                toastr.success(d.message, 'Success!');
                reloadHeadDropdown(d.id);
            }
        }).fail(function(xhr) {
            var msg = 'Could not create head.';
            try { msg = $(xhr.responseText).text() || msg; } catch(e) {}
            toastr.error(msg);
        }).always(function() {
            $btn.prop('disabled', false).text('Create & Select');
        });
    });
})();
</script>
