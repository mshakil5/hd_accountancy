{{-- Quick-add Account Type under the selected Account Head (client-scoped via head) --}}
<div class="modal fade" id="quickTypeModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quick Add Account Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="ermsg-quick-type"></div>
                <div class="alert alert-info py-2" style="font-size:12px;">
                    Created under the selected Account Head — scope follows the head (client-specific if the head is per-client).
                </div>
                <div class="form-group">
                    <label>Under Account Head</label>
                    <input type="text" class="form-control" id="quick_type_head_name" readonly>
                    <input type="hidden" id="quick_type_head_id">
                </div>
                <div class="form-group">
                    <label>Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="quick_type_name" placeholder="e.g. Office Expenses">
                </div>
                <div class="form-group">
                    <label>Category <span class="text-danger">*</span></label>
                    <select class="form-control" id="quick_type_category">
                        <option value="">Select category</option>
                        <option value="asset">Asset</option>
                        <option value="liability">Liability</option>
                        <option value="equity">Equity</option>
                        <option value="revenue">Revenue</option>
                        <option value="expense">Expense</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Normal Balance <span class="text-danger">*</span></label>
                    <select class="form-control" id="quick_type_normal_balance">
                        <option value="">Select</option>
                        <option value="debit">Debit</option>
                        <option value="credit">Credit</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="quickTypeSaveBtn">Create & Select</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var typeUrl = "{{ url('/admin/account-types') }}";

    window.openQuickTypeModal = function() {
        var headSel = (window.qaHeadSel ? window.qaHeadSel() : $('#account_head_id'));
        var headId = headSel.val();
        if (!headId) {
            toastr.warning('Please select an Account Head first.');
            return;
        }
        var headText = headSel.find('option:selected').text();
        $('#quick_type_head_id').val(headId);
        $('#quick_type_head_name').val(headText);
        $('#quick_type_name').val('');
        $('#quick_type_category').val('');
        $('#quick_type_normal_balance').val('');
        $('.ermsg-quick-type').html('');
        $('#quickTypeModal').modal('show');
    };

    $('#quickTypeSaveBtn').on('click', function() {
        var $btn = $(this);
        var payload = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            account_head_id: $('#quick_type_head_id').val(),
            name: $('#quick_type_name').val(),
            category: $('#quick_type_category').val(),
            normal_balance: $('#quick_type_normal_balance').val(),
        };
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Creating...');
        $.post(typeUrl, payload, function(d) {
            if (d.status == 303) {
                $('.ermsg-quick-type').html(d.message);
            } else {
                $('#quickTypeModal').modal('hide');
                toastr.success(d.message, 'Success!');
                // append new type to dropdown and select it (scoped to the target row if any)
                var opt = new Option($('#quick_type_name').val() + ' (' + $('#quick_type_category').val().charAt(0).toUpperCase() + $('#quick_type_category').val().slice(1) + ')', d.id, true, true);
                var typeSel = (window.quickAddTargetEl && window.quickAddTargetEl.closest) ? $(window.quickAddTargetEl.closest('.hd-form-grid, .hd-tab-panel, label')).find('select[name="account_type_id"]') : $('select[name="account_type_id"]');
                if (typeSel.length) {
                    typeSel.append(opt).val(String(d.id)).trigger('change');
                    if ($.fn.select2) typeSel.trigger('change.select2');
                }
                // update selected head's data-type-id by reloading heads
                var createdHeadId = $('#quick_type_head_id').val();
                if (typeof window.reloadHeadDropdown === 'function') {
                    window.reloadHeadDropdown(createdHeadId);
                } else {
                    // fallback: select the head in dropdown
                    var headDrop = (typeof window.qaHeadSel === 'function') ? window.qaHeadSel() : (window.quickAddTargetEl ? $(window.quickAddTargetEl) : $('#account_head_id'));
                    headDrop.val(String(createdHeadId)).trigger('change');
                }
            }
        }).fail(function() {
            toastr.error('Could not create type.');
        }).always(function() {
            $btn.prop('disabled', false).text('Create & Select');
        });
    });
})();
</script>