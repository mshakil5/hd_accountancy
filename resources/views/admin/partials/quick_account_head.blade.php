{{--
    Reusable "Quick Add Account Head" modal.

    Same fields as the Chart of Accounts form (Type -> Sub Type -> Code ->
    Account Head -> Normal Balance), always scoped to one client: there is no
    "Global" option here.

    Include from any page:
        @include('admin.partials.quick_account_head', [
            'quickHeadClientId'   => 444,                // client_credentials.id (required)
            'quickHeadClientName' => 'Jane Doe',         // optional, shown in the note
            'quickHeadTarget'     => '#account_head_id', // select refreshed + selected
        ])
    Then open it with:
        QuickAccountHead.open();                       // include defaults
        QuickAccountHead.open({ target: someSelect }); // per-row override
--}}
@php
    $quickHeadClientId = $quickHeadClientId ?? null;
    $quickHeadClientName = $quickHeadClientName ?? '';
    $quickHeadTarget = $quickHeadTarget ?? '#account_head_id';
@endphp

<div class="modal fade" id="quickHeadModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quick Add Account Head</h5>
                <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
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
                <button type="button" class="btn btn-success" id="quickHeadSaveBtn">Create &amp; Select</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    // The partial may be included before jQuery (content section) or after it
    // (script section), so wait for both jQuery and the document to be ready.
    function boot() {
        if (window.QuickAccountHead) return; // one modal per page

        var headUrl = "{{ url('/admin/account-heads') }}";
        var defaults = {
            clientId:   @json($quickHeadClientId),
            clientName: @json($quickHeadClientName),
            target:     @json($quickHeadTarget)
        };
        var codeTimer = null;
        var state = { target: defaults.target, onCreated: null };

        function optionHtml(heads) {
            var $sel = $('<select>').append(
                $('<option>', { value: '', text: 'Select Account Head' }).attr('data-type-id', '')
            );
            $.each(heads, function (i, h) {
                $sel.append(
                    $('<option>', { value: h.id, text: h.code + ' - ' + h.name })
                        .attr('data-type-id', h.account_type_id || '')
                );
            });
            return $sel.html();
        }

        function refreshTarget(headId, clientId) {
            var $el = $(state.target);
            if (!$el.length) return;
            $el.html('<option value="">Loading...</option>');
            $.get(headUrl + '/options', { client_credential_id: clientId || '' }, function (heads) {
                $el.html(optionHtml(heads));
                if (headId) $el.val(String(headId));
                $el.trigger('change');
            }).fail(function () {
                $el.html('<option value="">Unable to load Account Heads</option>').trigger('change');
            });
        }

        window.QuickAccountHead = {
            defaults: defaults,

            open: function (opts) {
                opts = opts || {};
                var clientId = (opts.clientId !== undefined && opts.clientId !== null)
                    ? opts.clientId : defaults.clientId;
                var clientName = (opts.clientName !== undefined && opts.clientName !== null)
                    ? opts.clientName : defaults.clientName;

                state.target = opts.target || defaults.target;
                state.onCreated = opts.onCreated || null;

                $('#quick_head_code').val('');
                $('#quick_head_name').val('');
                $('#quick_head_normal_balance').val('debit');
                $('#quick_head_type_id').val('');
                $('#quick_head_sub_type_id')
                    .html('<option value="">Select Account Type first</option>')
                    .prop('disabled', true);
                $('#quickHeadCodeMsg').text('').removeClass();
                $('.ermsg-quick').html('');
                $('#quick_head_client_id').val(clientId || '');

                var $note = $('#quickHeadScopeNote');
                if (clientId) {
                    $note.removeClass('alert-warning').addClass('alert-info').html(
                        'This head will be created for <strong>' +
                        $('<div>').text(clientName || ('Client #' + clientId)).html() +
                        '</strong> only.'
                    );
                } else {
                    $note.removeClass('alert-info').addClass('alert-warning').html(
                        'Select a client first — account heads are always created for a client.'
                    );
                }
                $('#quickHeadSaveBtn').prop('disabled', !clientId);

                $.get(headUrl + '/types', function (types) {
                    var html = '<option value="">Select Account Type</option>';
                    $.each(types, function (i, t) {
                        html += '<option value="' + t.id + '">' +
                            $('<div>').text(t.name).html() + '</option>';
                    });
                    $('#quick_head_type_id').html(html);
                    $('#quickHeadModal').modal('show');
                });
            }
        };

        $('#quick_head_type_id').on('change', function () {
            var typeId = $(this).val();
            var $sub = $('#quick_head_sub_type_id');
            if (!typeId) {
                $sub.html('<option value="">Select Account Type first</option>').prop('disabled', true);
                return;
            }
            $sub.html('<option value="">Loading...</option>').prop('disabled', true);
            $.get(headUrl + '/sub-types/' + typeId, function (data) {
                var html = '<option value="">Select Account Sub Type</option>';
                $.each(data, function (i, sub) {
                    html += '<option value="' + sub.id + '">' +
                        $('<div>').text(sub.name).html() + '</option>';
                });
                $sub.html(html).prop('disabled', false);
            });
        });

        $('#quick_head_code').on('input', function () {
            clearTimeout(codeTimer);
            var code = $(this).val().trim();
            if (!code) { $('#quickHeadCodeMsg').text('').removeClass(); return; }
            codeTimer = setTimeout(function () {
                $.get(headUrl + '/check-code', {
                    code: code,
                    client_credential_id: $('#quick_head_client_id').val()
                }, function (d) {
                    if (d.available) {
                        $('#quickHeadCodeMsg').text(code + ' is available').removeClass().addClass('text-success');
                    } else {
                        $('#quickHeadCodeMsg').text(code + ' is not available for this client').removeClass().addClass('text-danger');
                    }
                });
            }, 400);
        });

        $('#quickHeadSaveBtn').on('click', function () {
            var $btn = $(this);
            var clientId = $('#quick_head_client_id').val();
            var payload = {
                _token: $('meta[name="csrf-token"]').attr('content'),
                account_type_id:      $('#quick_head_type_id').val(),
                account_sub_type_id:  $('#quick_head_sub_type_id').val(),
                code:                 $('#quick_head_code').val(),
                name:                 $('#quick_head_name').val(),
                normal_balance:       $('#quick_head_normal_balance').val(),
                client_credential_id: clientId
            };

            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Creating...');
            $.post(headUrl, payload, function (d) {
                if (d.status == 303) {
                    $('.ermsg-quick').html(d.message);
                } else {
                    $('#quickHeadModal').modal('hide');
                    toastr.success(d.message, 'Success!');

                    var created = {
                        id: d.id,
                        code: payload.code,
                        name: payload.name,
                        account_type_id: payload.account_type_id,
                        account_sub_type_id: payload.account_sub_type_id
                    };

                    if (state.onCreated) {
                        state.onCreated(created);
                    } else {
                        refreshTarget(created.id, clientId);
                    }
                }
            }).fail(function (xhr) {
                var msg = 'Could not create head.';
                try { msg = $(xhr.responseText).text() || msg; } catch (e) {}
                toastr.error(msg);
            }).always(function () {
                $btn.prop('disabled', false).html('Create &amp; Select');
            });
        });
    }

    if (window.jQuery) {
        boot();
    } else {
        document.addEventListener('DOMContentLoaded', boot);
    }
})();
</script>
