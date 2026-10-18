@extends('admin.layouts.admin')

@section('content')

<style>
.switch { position: relative; display: inline-block; width: 50px; height: 26px; }
.switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; border-radius: 26px; transition: .4s; }
.slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background-color: white; border-radius: 50%; transition: .4s; }
input:checked + .slider { background-color: #28a745; }
input:checked + .slider:before { transform: translateX(24px); }

.category-tabs { display: flex; gap: 0; margin-bottom: 16px; border-bottom: 2px solid #dee2e6; }
.category-tabs .tab-btn {
    padding: 8px 18px; border: 1px solid #dee2e6; border-bottom: none;
    background: #f8f9fa; cursor: pointer; font-size: 14px;
    border-radius: 6px 6px 0 0; margin-right: 4px; color: #495057;
}
.category-tabs .tab-btn.active { background: #fff; font-weight: 600; border-bottom: 2px solid #fff; color: #212529; }

.step-title {
    font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
    color: #6c757d; border-bottom: 1px solid #e9ecef; padding-bottom: 6px; margin-bottom: 14px;
}
</style>

<section class="content">
    <div class="container-fluid">
        <div class="row align-items-center mb-3">
            <div class="col-md-2">
                <button type="button" class="btn btn-secondary" id="newBtn">Add new</button>
            </div>
            <div class="col-md-4 offset-md-6">
                <label class="font-weight-bold">Filter by Client</label>
                <select class="form-control select2" id="filterClientCredential" style="width:100%;">
                    <option value="">All (Global + All Clients)</option>
                </select>
            </div>
        </div>
    </div>
</section>

<section class="content" id="addThisFormContainer" style="display:none">
    <div class="container-fluid">
        <div class="row justify-content-md-center">
            <div class="col-md-7">
                <div class="card card-secondary border-theme border-2">
                    <div class="card-header">
                        <h3 class="card-title" id="formTitle">Add new Account Head</h3>
                    </div>
                    <div class="card-body">
                        <div class="ermsg"></div>
                        <form id="createThisForm">
                            @csrf
                            <input type="hidden" id="codeid" name="codeid">

                            {{-- Step 1 — the Account Head is made first --}}
                            <div class="step-title">1 &middot; Account Head</div>

                            <div class="form-group">
                                <label>Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="code" name="code" placeholder="e.g. 210">
                                <small id="codeMsg"></small>
                            </div>

                            <div class="form-group">
                                <label>Account Head <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Transportation">
                            </div>

                            <div class="form-group">
                                <label>Description</label>
                                <input type="text" class="form-control" id="description" name="description">
                            </div>

                            <div class="form-group">
                                <label>Tax</label>
                                <select class="form-control" id="tax_rate_id" name="tax_rate_id">
                                    <option value="">Select tax</option>
                                    @foreach($taxRates as $tax)
                                        <option value="{{ $tax->id }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Client (optional — leave blank for Global)</label>
                                <select class="form-control select2" id="client_credential_id" name="client_credential_id" style="width:100%;">
                                    <option value="">Global (all clients)</option>
                                </select>
                            </div>

                            {{-- Step 2 — its Account Type is chosen/created under the head --}}
                            <div class="step-title mt-4">2 &middot; Account Type</div>

                            <p id="typeStepPending" class="text-muted small mb-0" style="display:none">
                                Save the Account Head first — then choose or create the Account Type that lives under it.
                            </p>

                            <div id="typeStepActive" style="display:none">
                                <div class="form-group">
                                    <label>Account Type <span class="text-danger">*</span></label>
                                    <select class="form-control" id="assign_type_id">
                                        <option value="">Select account type</option>
                                    </select>
                                    <small class="text-muted">Shared (global) types plus any type already made under this head.</small>
                                </div>
                                <button type="button" id="assignBtn" class="btn btn-sm btn-outline-primary">Assign Type &amp; Finish</button>
                                <button type="button" id="skipAssign" class="btn btn-sm btn-outline-secondary">Assign later</button>

                                <div class="mt-3">
                                    <button type="button" id="newTypeToggle" class="btn btn-sm btn-link p-0">&#43; Create a new Account Type under this head</button>
                                    <div id="newTypeForm" class="border rounded p-3 mt-2" style="display:none">
                                        <div class="form-group">
                                            <label>Type Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="new_type_name" placeholder="e.g. Bank Charges">
                                        </div>
                                        <div class="form-group">
                                            <label>Category <span class="text-danger">*</span></label>
                                            <select class="form-control" id="new_type_category">
                                                <option value="">Select category</option>
                                                <option value="asset">Asset</option>
                                                <option value="liability">Liability</option>
                                                <option value="equity">Equity</option>
                                                <option value="revenue">Revenue</option>
                                                <option value="expense">Expense</option>
                                            </select>
                                        </div>
                                        <div class="form-group mb-2">
                                            <label>Normal Balance <span class="text-danger">*</span></label>
                                            <select class="form-control" id="new_type_normal_balance">
                                                <option value="">Select normal balance</option>
                                                <option value="debit">Debit</option>
                                                <option value="credit">Credit</option>
                                            </select>
                                        </div>
                                        <small class="text-muted d-block mb-2">Its scope follows this head (Global head &rarr; global type, client head &rarr; that client).</small>
                                        <button type="button" id="createTypeBtn" class="btn btn-sm btn-success">Create Type</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer">
                        <button type="button" id="addBtn" class="btn btn-secondary" value="Create">Create</button>
                        <button type="button" id="FormCloseBtn" class="btn btn-default">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="content" id="contentContainer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-secondary border-theme border-2">
                    <div class="card-header">
                        <h3 class="card-title">All Account Heads</h3>
                    </div>
                    <div class="card-body">

                        <div class="category-tabs">
                            <button class="tab-btn active" data-category="">All Accounts</button>
                            <button class="tab-btn" data-category="asset">Assets</button>
                            <button class="tab-btn" data-category="liability">Liabilities</button>
                            <button class="tab-btn" data-category="equity">Equity</button>
                            <button class="tab-btn" data-category="expense">Expenses</button>
                            <button class="tab-btn" data-category="revenue">Revenue</button>
                        </div>

                        <table id="example1" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Account Type</th>
                                    <th>Code</th>
                                    <th>Account Head</th>
                                    <th>Client</th>
                                    <th>Tax</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('script')
<script>
$(function(){
    var url = "{{ url('/admin/account-heads') }}";
    var activeCategory = '';
    var currentHeadId = null;      // set after step 1 save (create) or on edit
    var currentHeadName = '';

    // Client Select2 with AJAX search (form)
    $('#client_credential_id').select2({
        placeholder: 'Global (all clients)',
        allowClear: true,
        ajax: {
            url: "{{ url('/admin/receipts/search-clients') }}",
            dataType: 'json',
            delay: 250,
            data: function(params) { return { q: params.term }; },
            processResults: function(data) { return { results: data }; },
            cache: true
        }
    });

    var table = $('#example1').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: url + '/datatable',
            data: function(d) {
                d.category = activeCategory;
                d.client_credential_id = $('#filterClientCredential').val();
            }
        },
        columns: [
            {data: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'account_type_name'},
            {data: 'code'},
            {data: 'name'},
            {data: 'client_name', orderable: false, searchable: false},
            {data: 'tax_rate_name', orderable: false, searchable: false},
            {data: 'status', orderable: false, searchable: false},
            {data: 'action', orderable: false, searchable: false},
        ]
    });

    $('.tab-btn').on('click', function(){
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        activeCategory = $(this).data('category');
        table.ajax.reload();
    });

    // Top filter Select2 (after table is created)
    $('#filterClientCredential').select2({
        placeholder: 'Select client...',
        allowClear: true,
        ajax: {
            url: "{{ url('/admin/receipts/search-clients') }}",
            dataType: 'json',
            delay: 250,
            data: function(params) { return { q: params.term }; },
            processResults: function(data) {
                data.unshift({ id: '', text: 'All (Global + All Clients)' });
                return { results: data };
            },
            cache: true
        }
    }).on('change', function() {
        table.ajax.reload();
    });

    let codeTimer;
    $('#code').on('input', function(){
        clearTimeout(codeTimer);
        let code = $(this).val().trim();
        let editId = $('#codeid').val();
        if(!code) { $('#codeMsg').text('').removeClass(); return; }
        codeTimer = setTimeout(function(){
            $.get(url+'/check-code', {code: code, id: editId, client_credential_id: $('#client_credential_id').val()}, function(d){
                if(d.available){
                    $('#codeMsg').text(code+' is available').removeClass().addClass('text-success');
                } else {
                    $('#codeMsg').text(code+' is not available').removeClass().addClass('text-danger');
                }
            });
        }, 400);
    });

    // Re-check code when client changes
    $('#client_credential_id').on('change', function(){
        let code = $('#code').val().trim();
        if(code) $('#code').trigger('input');
    });

    // ---- Step 2 helpers -------------------------------------------------

    function loadAssignableTypes(preselectId) {
        if(!currentHeadId) return;
        $('#assign_type_id').html('<option value="">Loading...</option>');
        $.get(url + '/assignable-types', {head_id: currentHeadId}, function(list){
            var html = '<option value="">Select account type</option>';
            $.each(list, function(i, t){
                var label = t.name + (t.category ? ' (' + t.category + ')' : '') + (t.under_head ? ' — under ' + t.under_head : '');
                html += '<option value="' + t.id + '">' + label + '</option>';
            });
            $('#assign_type_id').html(html);
            var pre = preselectId;
            if(!pre) {
                var sel = list.find(function(t){ return t.selected; });
                pre = sel ? sel.id : '';
            }
            if(pre) $('#assign_type_id').val(String(pre));
        });
    }

    function openTypeStep(preselectId) {
        $('#typeStepPending').hide();
        $('#typeStepActive').show();
        loadAssignableTypes(preselectId);
    }

    function closeForm(){
        $('#addThisFormContainer').hide(200);
        $('#newBtn').show();
        clearForm();
        table.ajax.reload(null, false);
    }

    // ---- Step 1: create / update the head -------------------------------

    $('#newBtn').click(function(){
        clearForm();
        $('#newBtn').hide();
        $('#addThisFormContainer').show(300);
        $('#typeStepPending').show();       // head doesn't exist yet
        $('#typeStepActive').hide();
    });

    $('#FormCloseBtn').click(function(){
        $('#addThisFormContainer').hide(200);
        $('#newBtn').show();
        clearForm();
    });

    $('#addBtn').click(function(){
        let isUpdate = $(this).val() == 'Update';
        let data = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            code:            $('#code').val(),
            name:            $('#name').val(),
            description:     $('#description').val(),
            tax_rate_id:     $('#tax_rate_id').val(),
            client_credential_id: $('#client_credential_id').val(),
        };
        if(isUpdate) {
            data.codeid = $('#codeid').val();
            // include the chosen type so Update can assign it in one go
            if($('#typeStepActive').is(':visible')) data.account_type_id = $('#assign_type_id').val();
        }

        $.post(isUpdate ? url+'/update' : url, data, function(d){
            if(d.status == 303){
                $('.ermsg').html(d.message);
            } else if(isUpdate) {
                toastr.success(d.message, 'Success!');
                closeForm();
            } else {
                // Step 1 done — open Step 2: assign the type under this head
                currentHeadId = d.id;
                currentHeadName = data.name;
                $('#codeid').val(d.id);
                toastr.success('Account head created — now choose its Account Type.', 'Success!');
                $('#formTitle').text('Account: ' + currentHeadName);
                $('#addBtn').hide();
                openTypeStep();
                table.ajax.reload(null, false);
            }
        });
    });

    // ---- Step 2: assign or create the type ------------------------------

    $('#assignBtn').click(function(){
        if(!currentHeadId) return;
        let typeId = $('#assign_type_id').val();
        if(!typeId){ toastr.warning('Select an Account Type first.'); return; }
        $.post(url + '/' + currentHeadId + '/assign-type', {
            _token: $('meta[name="csrf-token"]').attr('content'),
            account_type_id: typeId
        }, function(d){
            if(d.status == 303){
                $('.ermsg').html(d.message);
            } else {
                toastr.success(d.message, 'Success!');
                closeForm();
            }
        });
    });

    $('#skipAssign').click(function(){
        toastr.info('Head saved — you can assign its type later from this list.');
        closeForm();
    });

    $('#newTypeToggle').click(function(){
        $('#newTypeForm').slideToggle(150);
    });

    $('#createTypeBtn').click(function(){
        if(!currentHeadId) return;
        let name = $('#new_type_name').val().trim();
        let category = $('#new_type_category').val();
        let normalBalance = $('#new_type_normal_balance').val();
        if(!name || !category || !normalBalance){
            toastr.warning('Fill the type name, category and normal balance.');
            return;
        }
        $.post("{{ url('/admin/account-types') }}", {
            _token: $('meta[name="csrf-token"]').attr('content'),
            account_head_id: currentHeadId,
            name: name,
            category: category,
            normal_balance: normalBalance
        }, function(d){
            if(d.status == 303){
                $('.ermsg').html(d.message);
            } else {
                toastr.success(d.message, 'Success!');
                $('#newTypeForm').slideUp(150);
                $('#new_type_name').val('');
                $('#new_type_category').val('');
                $('#new_type_normal_balance').val('');
                // The server auto-assigns a new type to an untyped head.
                loadAssignableTypes(d.id);
                table.ajax.reload(null, false);
            }
        });
    });

    // ---- Edit / delete / toggle -----------------------------------------

    $('#contentContainer').on('click', '.EditBtn', function(){
        let id = $(this).attr('rid');
        $.get(url+'/'+id+'/edit', function(d){
            $('#codeid').val(d.id);
            $('#code').val(d.code);
            $('#name').val(d.name);
            $('#description').val(d.description);
            $('#tax_rate_id').val(d.tax_rate_id);
            if(d.client_credential_id) {
                $.get("{{ url('/admin/receipts/search-clients') }}", { q: '' }, function(data) {
                    var match = data.find(function(item) { return item.id == d.client_credential_id; });
                    if(match) {
                        var option = new Option(match.text, match.id, true, true);
                        $('#client_credential_id').append(option).trigger('change');
                    }
                });
            } else {
                $('#client_credential_id').val(null).trigger('change');
            }
            $('#codeMsg').text('');
            currentHeadId = d.id;
            currentHeadName = d.name;
            $('#addBtn').val('Update').text('Update').show();
            $('#formTitle').text('Edit Account: ' + d.name);
            openTypeStep(d.account_type_id);
            $('#addThisFormContainer').show(300);
            $('#newBtn').hide();
        });
    });

    $('#contentContainer').on('click', '.deleteBtn', function(){
        let id = $(this).attr('rid');
        if(!confirm('Are you sure?')) return;
        $.get(url+'/'+id+'/delete', function(d){
            if(d.success){
                toastr.success(d.message, 'Success!');
                table.ajax.reload(null, false);
            }
        });
    });

    $('#contentContainer').on('change', '.toggleStatus', function(){
        let id = $(this).data('id');
        $.get(url+'/'+id+'/toggle', function(){
            toastr.success('Status updated.', 'Success!');
            table.ajax.reload(null, false);
        });
    });

    function clearForm(){
        $('#createThisForm')[0].reset();
        $('#codeid').val('');
        $('#client_credential_id').val(null).trigger('change');
        $('#codeMsg').text('').removeClass();
        $('#addBtn').val('Create').text('Create').show();
        $('#formTitle').text('Add new Account Head');
        $('#assign_type_id').html('<option value="">Select account type</option>');
        $('#typeStepPending').hide();
        $('#typeStepActive').hide();
        $('#newTypeForm').hide();
        currentHeadId = null;
        currentHeadName = '';
        $('.ermsg').html('');
    }
});
</script>
@endsection
