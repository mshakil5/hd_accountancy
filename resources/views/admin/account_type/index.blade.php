@extends('admin.layouts.admin')

@section('content')

<style>
.switch { position: relative; display: inline-block; width: 50px; height: 26px; }
.switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; border-radius: 26px; transition: .4s; }
.slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background-color: white; border-radius: 50%; transition: .4s; }
input:checked + .slider { background-color: #28a745; }
input:checked + .slider:before { transform: translateX(24px); }

.step-title {
    font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
    color: #6c757d; border-bottom: 1px solid #e9ecef; padding-bottom: 6px; margin-bottom: 14px;
}
</style>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-2">
                <button type="button" class="btn btn-secondary my-3" id="newBtn">Add new</button>
            </div>
        </div>
    </div>
</section>

<section class="content" id="addThisFormContainer" style="display:none">
    <div class="container-fluid">
        <div class="row justify-content-md-center">
            <div class="col-md-6">
                <div class="card card-secondary border-theme border-2">
                    <div class="card-header">
                        <h3 class="card-title" id="formTitle">Add Account Type</h3>
                    </div>
                    <div class="card-body">
                        <div class="ermsg"></div>
                        <form id="createThisForm">
                            @csrf
                            <input type="hidden" id="codeid" name="codeid">

                            {{-- Head-first: pick the Account Head before the type details --}}
                            <div class="step-title">1 &middot; Under which Account Head?</div>
                            <div class="form-group">
                                <label>Account Head <span class="text-danger">*</span></label>
                                <select class="form-control select2" id="account_head_id" name="account_head_id" style="width:100%;">
                                    <option value="">Select account head</option>
                                    @foreach($heads as $h)
                                        @php
                                            $clientName = $h->clientCredential
                                                ? trim($h->clientCredential->first_name . ' ' . $h->clientCredential->last_name)
                                                : null;
                                        @endphp
                                        <option value="{{ $h->id }}"
                                            data-scope="{{ $clientName ? 'Client: ' . $clientName : 'Global (all clients)' }}">
                                            {{ $h->code }} &mdash; {{ $h->name }} ({{ $clientName ? $clientName : 'Global' }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted d-block mt-1">
                                    <span id="headScopeNote">The type's scope follows this head: a global head gets a global type, a client head gets that client's type.</span>
                                </small>
                            </div>

                            <div class="step-title mt-4">2 &middot; Account Type</div>

                            <div class="form-group">
                                <label>Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Overhead">
                            </div>
                            <div class="form-group">
                                <label>Category <span class="text-danger">*</span></label>
                                <select class="form-control" id="category" name="category">
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
                                <select class="form-control" id="normal_balance" name="normal_balance">
                                    <option value="">Select normal balance</option>
                                    <option value="debit">Debit</option>
                                    <option value="credit">Credit</option>
                                </select>
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
                        <h3 class="card-title">All Account Types</h3>
                    </div>
                    <div class="card-body">
                        <table id="example1" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Account Head</th>
                                    <th>Name</th>
                                    <th>Client</th>
                                    <th>Category</th>
                                    <th>Normal Balance</th>
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
    var url = "{{ url('/admin/account-types') }}";

    $('#account_head_id').select2({
        placeholder: 'Select account head',
        allowClear: true,
        width: '100%'
    }).on('change', function(){
        var opt = $(this).find(':selected');
        var scope = opt.data('scope');
        $('#headScopeNote').text(scope
            ? 'Scope: ' + scope + ' (the type follows the head).'
            : "The type's scope follows this head: a global head gets a global type, a client head gets that client's type.");
    });

    var table = $('#example1').DataTable({
        processing: true,
        serverSide: true,
        ajax: url + '/datatable',
        columns: [
            {data: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'account_head_name', orderable: false, searchable: false},
            {data: 'name'},
            {data: 'client_name', orderable: false, searchable: false},
            {data: 'category'},
            {data: 'normal_balance'},
            {data: 'status', orderable: false, searchable: false},
            {data: 'action', orderable: false, searchable: false},
        ]
    });

    $('#newBtn').click(function(){
        clearForm();
        $('#newBtn').hide();
        $('#addThisFormContainer').show(300);
    });

    $('#FormCloseBtn').click(function(){
        $('#addThisFormContainer').hide(200);
        $('#newBtn').show();
        clearForm();
    });

    $('#addBtn').click(function(){
        let isUpdate = $(this).val() == 'Update';
        let headId = $('#account_head_id').val();
        if(!isUpdate && !headId){
            $('.ermsg').html("<div class='alert alert-warning'>Select the Account Head this type belongs to.</div>");
            return;
        }
        let data = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            account_head_id: headId,
            name: $('#name').val(),
            category: $('#category').val(),
            normal_balance: $('#normal_balance').val(),
        };
        if(isUpdate) data.codeid = $('#codeid').val();

        $.post(isUpdate ? url+'/update' : url, data, function(d){
            if(d.status == 303){
                $('.ermsg').html(d.message);
            } else {
                toastr.success(d.message, 'Success!');
                $('#addThisFormContainer').hide(200);
                $('#newBtn').show();
                clearForm();
                table.ajax.reload(null, false);
            }
        });
    });

    $('#contentContainer').on('click', '.EditBtn', function(){
        let id = $(this).attr('rid');
        $.get(url+'/'+id+'/edit', function(d){
            $('#codeid').val(d.id);
            $('#account_head_id').val(d.account_head_id ? String(d.account_head_id) : '').trigger('change');
            $('#name').val(d.name);
            $('#category').val(d.category);
            $('#normal_balance').val(d.normal_balance);
            $('#addBtn').val('Update').text('Update');
            $('#formTitle').text('Edit Account Type');
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
        $('#account_head_id').val('').trigger('change');
        $('#headScopeNote').text("The type's scope follows this head: a global head gets a global type, a client head gets that client's type.");
        $('#addBtn').val('Create').text('Create');
        $('#formTitle').text('Add Account Type');
        $('.ermsg').html('');
    }
});
</script>
@endsection
