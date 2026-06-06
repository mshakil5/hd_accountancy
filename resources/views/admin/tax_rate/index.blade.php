@extends('admin.layouts.admin')

@section('content')

<style>
.switch { position: relative; display: inline-block; width: 50px; height: 26px; }
.switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; border-radius: 26px; transition: .4s; }
.slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background-color: white; border-radius: 50%; transition: .4s; }
input:checked + .slider { background-color: #28a745; }
input:checked + .slider:before { transform: translateX(24px); }
</style>

<section class="content" id="newBtnSection">
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
                        <h3 class="card-title" id="formTitle">Add Tax Rate</h3>
                    </div>
                    <div class="card-body">
                        <div class="ermsg"></div>
                        <form id="createThisForm">
                            @csrf
                            <input type="hidden" id="codeid" name="codeid">
                            <div class="form-group">
                                <label>Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Standard VAT">
                            </div>
                            <div class="form-group">
                                <label>Rate (%) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" id="rate" name="rate" placeholder="e.g. 20.00">
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
                        <h3 class="card-title">All Tax Rates</h3>
                    </div>
                    <div class="card-body">
                        <table id="example1" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Rate (%)</th>
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

    var url = "{{ url('/admin/tax-rates') }}";

    var table = $('#example1').DataTable({
        processing: true,
        serverSide: true,
        ajax: url + '/datatable',
        columns: [
            {data: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'name'},
            {data: 'rate'},
            {data: 'status', orderable: false, searchable: false},
            {data: 'action', orderable: false, searchable: false},
        ]
    });

    // Show form
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

    // Create / Update
    $('#addBtn').click(function(){
        let isUpdate = $(this).val() == 'Update';
        let data = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            name: $('#name').val(),
            rate: $('#rate').val(),
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

    // Edit
    $('#contentContainer').on('click', '.EditBtn', function(){
        let id = $(this).attr('rid');
        $.get(url+'/'+id+'/edit', function(d){
            $('#codeid').val(d.id);
            $('#name').val(d.name);
            $('#rate').val(d.rate);
            $('#addBtn').val('Update').text('Update');
            $('#formTitle').text('Edit Tax Rate');
            $('#addThisFormContainer').show(300);
            $('#newBtn').hide();
        });
    });

    // Delete
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

    // Toggle status
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
        $('#addBtn').val('Create').text('Create');
        $('#formTitle').text('Add Tax Rate');
        $('.ermsg').html('');
    }

});
</script>
@endsection