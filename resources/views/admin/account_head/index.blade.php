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
            <div class="col-md-7">
                <div class="card card-secondary border-theme border-2">
                    <div class="card-header">
                        <h3 class="card-title" id="formTitle">Add new Account</h3>
                    </div>
                    <div class="card-body">
                        <div class="ermsg"></div>
                        <form id="createThisForm">
                            @csrf
                            <input type="hidden" id="codeid" name="codeid">

                            <div class="form-group">
                                <label>Account Type <span class="text-danger">*</span></label>
                                <select class="form-control" id="account_type_id" name="account_type_id">
                                    <option value="">Select account type</option>
                                    @foreach($accountTypes as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>

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

    var table = $('#example1').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: url + '/datatable',
            data: function(d) {
                d.category = activeCategory;
            }
        },
        columns: [
            {data: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'account_type_name'},
            {data: 'code'},
            {data: 'name'},
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

    let codeTimer;
    $('#code').on('input', function(){
        clearTimeout(codeTimer);
        let code = $(this).val().trim();
        let editId = $('#codeid').val();
        if(!code) { $('#codeMsg').text('').removeClass(); return; }
        codeTimer = setTimeout(function(){
            $.get(url+'/check-code', {code: code, id: editId}, function(d){
                if(d.available){
                    $('#codeMsg').text(code+' is available').removeClass().addClass('text-success');
                } else {
                    $('#codeMsg').text(code+' is not available').removeClass().addClass('text-danger');
                }
            });
        }, 400);
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
        let data = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            account_type_id: $('#account_type_id').val(),
            code:            $('#code').val(),
            name:            $('#name').val(),
            description:     $('#description').val(),
            tax_rate_id:     $('#tax_rate_id').val(),
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
            $('#account_type_id').val(d.account_type_id);
            $('#code').val(d.code);
            $('#name').val(d.name);
            $('#description').val(d.description);
            $('#tax_rate_id').val(d.tax_rate_id);
            $('#codeMsg').text('');
            $('#addBtn').val('Update').text('Update');
            $('#formTitle').text('Edit Account');
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
        $('#codeMsg').text('').removeClass();
        $('#addBtn').val('Create').text('Create');
        $('#formTitle').text('Add new Account');
        $('.ermsg').html('');
    }
});
</script>
@endsection