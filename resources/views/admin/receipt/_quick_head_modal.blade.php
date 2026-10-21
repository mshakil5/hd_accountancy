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
                    <label>VAT Rate</label>
                    <select class="form-control" id="quick_head_tax_rate_id">
                        <option value="">Select VAT rate</option>
                        @foreach($taxRates ?? [] as $tax)
                            <option value="{{ $tax->id }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                        @endforeach
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
        $('#quick_head_description').val('');
        $('#quick_head_tax_rate_id').val('');
        $('#quick_head_type_id').val('');
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
                ? "This head will be created for <strong>" + $('<div>').text(credName || ('Client #' + credId)).html() + "</strong> only (per-client, free-form code)."
                : "No client selected — head will be created as <strong>Global</strong>."
        );
        $('#quickHeadModal').modal('show');
        // VAT select inside the modal needs its own init — dropdown attaches to body
        // so it is not clipped by the modal and stays clickable.
        setTimeout(function(){
            var $vat = $('#quick_head_tax_rate_id');
            if ($vat.length && $.fn.select2) {
                if ($vat.data('select2')) $vat.select2('destroy');
                $vat.select2({width:'100%', dropdownParent: $(document.body)});
            }
        }, 150);
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

    window.reloadHeadDropdown = function(selectId) {
        var headDrop = qaHeadSel();
        headDrop.html('<option value="">Loading...</option>').trigger('change');
        return $.get(baseReceiptUrl + '/get-account-heads', {
            client_credential_id: currentCredId()
        }, function(data) {
            var options = '<option value="">Select Account Head</option>';
            $.each(data, function(i, head) {
                var rate = head.tax_rate ? head.tax_rate.rate : 0;
                var typeId = head.account_type_id || '';
                options += '<option value="' + head.id + '" data-rate="' + rate + '" data-type-id="' + typeId + '">' + head.code + ' - ' + head.name + '</option>';
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
                $('#quickHeadModal').modal('hide');
                toastr.success(d.message, 'Success!');
                // Head-first: head created without a type — now create its type
                var newHeadId = d.id;
                var newHeadName = $('#quick_head_code').val() + ' - ' + $('#quick_head_name').val();
                $('#quick_type_head_id').val(newHeadId);
                $('#quick_type_head_name').val(newHeadName);
                $('#quick_type_name').val('');
                $('#quick_type_category').val('');
                $('#quick_type_normal_balance').val('');
                $('.ermsg-quick-type').html('');
                $('#quickTypeModal').modal('show');
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