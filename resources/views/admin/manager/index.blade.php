@extends('admin.layouts.admin')

@section('content')

<!-- Main content -->
<section class="content" id="newBtnSection">
    <div class="container-fluid">
      <div class="row">
        <div class="col-2">
            <button type="button" class="btn btn-secondary my-3" id="newBtn">Add new</button>
        </div>
      </div>
    </div>
</section>
<!-- /.content -->

<!-- Add/Edit Manager Form -->
<section class="content" id="addThisFormContainer">
  <div class="container-fluid">
    <div class="row justify-content-md-center">
      <div class="col-md-8">
        <div class="card card-secondary border-theme border-2">
          <div class="card-header">
            <h3 class="card-title">Add new manager</h3>
          </div>
          <div class="card-body">
            <div class="ermsg"></div>
            <form id="createThisForm">
              @csrf
              <input type="hidden" class="form-control" id="codeid" name="codeid">

              <div class="row">
                <div class="col-sm-6">
                  <div class="form-group">
                    <label>First Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="first_name" name="first_name" placeholder="Enter first name">
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="form-group">
                    <label>Last Name <span class="text-danger">*</span></label>
                    <input type="text" id="last_name" name="last_name" class="form-control" placeholder="Enter last name">
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-sm-6">
                  <div class="form-group">
                    <label>Phone</label>
                    <input type="number" id="phone" name="phone" class="form-control" placeholder="Enter phone">
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="form-group">
                    <label>Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter email">
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-sm-6">
                  <div class="form-group">
                    <label>Password</label>
                    <div class="input-group">
                      <input type="password" class="form-control" id="password" name="password" placeholder="Enter password">
                      <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#password">
                        <i class="fa fa-eye"></i>
                      </button>
                    </div>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="input-group">
                      <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Enter confirm password">
                      <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#confirm_password">
                        <i class="fa fa-eye"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-sm-6">
                  <div class="form-group">
                    <label>Role <span class="text-danger">*</span></label>
                      <select class="form-control" id="role_id" name="role_id">
                        <option value="">Select role</option>
                        @foreach ($roles as $role)
                          <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                      </select>
                  </div>
                </div>
              </div>
              
            </form>
          </div>

          <div class="card-footer">
            <button type="submit" id="addBtn" class="btn btn-secondary" value="Create">Create</button>
            <button type="submit" id="FormCloseBtn" class="btn btn-default">Cancel</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Manager Assignment Box -->
<div id="managerAssignBox" class="card card-secondary mt-3" style="display:none">
    <div class="card-header">
        <h5 class="mb-0">Assign Managers</h5>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label>Select Managers</label>
            <select id="managerSelect" class="form-control" multiple></select>
        </div>

        <div class="text-right mt-3">
            <button class="btn btn-secondary" id="saveManagers">
                <i class="fa fa-save"></i> Save
            </button>
            <button class="btn btn-default" id="closeManagerBox">
                Cancel
            </button>
        </div>
    </div>
</div>

<!-- Managers Table -->
<section class="content" id="contentContainer">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="card card-secondary border-theme border-2">
            <div class="card-header">
              <h3 class="card-title">All Managers</h3>
            </div>
            <div class="card-body">
              <table id="example1" class="table table-striped">
                <thead>
                <tr>
                  <th>Sl</th>
                  <th>First Name</th>
                  <th>Last Name</th>
                  <th>Phone</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Log</th>
                  <th>Action</th>
                </tr>
                </thead>
                <tbody>
                  @foreach ($data as $key => $data)
                  <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{$data->first_name}}</td>
                    <td>{{$data->last_name}}</td>
                    <td>{{$data->phone}}</td>
                    <td>{{$data->email}}</td>
                    <td>{{$data->role ? $data->role->name : ''}}</td>
                    <td>
                        <a href="{{ route('user.activities', $data->id) }}" class="btn btn-link">
                            <i class="fa fa-book" style="font-size: 20px;"></i>
                        </a>
                    </td>
                    <td>
                      <a class="btn btn-link" id="EditBtn" rid="{{$data->id}}">
                        <i class="fa fa-edit" style="font-size: 20px;"></i>
                      </a>
                      <a class="btn btn-link assignManagerBtn" data-id="{{ $data->id }}">
                        <i class="fa fa-users"></i>
                      </a>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
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
let currentUser = null;

// Assign Managers
$('.assignManagerBtn').click(function () {
    currentUser = $(this).data('id');
    $('#managerAssignBox').show();

    $.get('/admin/user-managers/' + currentUser, function (res) {
        let opt = '';
        res.managers.forEach(m => {
            opt += `<option value="${m.id}">${m.first_name} ${m.last_name} - ${m.type}</option>`;
        });
        $('#managerSelect').html(opt)
            .val(res.selected)
            .select2({ width:'100%' });
    });
});

// Save Managers
$('#saveManagers').click(function () {
    $.post('/admin/user-managers/' + currentUser, {
        manager_ids: $('#managerSelect').val(),
        _token: '{{ csrf_token() }}'
    }, () => toastr.success('Updated'));
});

// Close Manager Box
$('#closeManagerBox').click(function () {
    $('#managerAssignBox').hide();
    $('#managerSelect').val(null).trigger('change');
});

// Toggle password visibility
$(document).on('click', '.toggle-password', function () {
    const target = $(this).data('target');
    const input = $(target);
    const icon = $(this).find('i');
    if (input.attr('type') === 'password') {
        input.attr('type', 'text');
        icon.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
        input.attr('type', 'password');
        icon.removeClass('fa-eye-slash').addClass('fa-eye');
    }
});

// DataTables
$(function () {
    $("#example1").DataTable();
});

// Show/Hide Add Form
$(document).ready(function () {
    $("#addThisFormContainer").hide();
    $("#newBtn").click(function(){
        clearform();
        $("#newBtn").hide(100);
        $("#addThisFormContainer").show(300);
    });
    $("#FormCloseBtn").click(function(){
        $("#addThisFormContainer").hide(200);
        $("#newBtn").show(100);
        clearform();
    });

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    var url = "{{URL::to('/admin/manager')}}";
    var upurl = "{{URL::to('/admin/manager-update')}}";

    // Create / Update
    $("#addBtn").click(function(){
        let form_data = new FormData();
        form_data.append("first_name", $("#first_name").val());
        form_data.append("email", $("#email").val());
        form_data.append("phone", $("#phone").val());
        form_data.append("last_name", $("#last_name").val());
        form_data.append("password", $("#password").val());
        form_data.append("confirm_password", $("#confirm_password").val());
        form_data.append("role_id", $("#role_id").val());
        if($(this).val() == 'Update') form_data.append("codeid", $("#codeid").val());

        $.ajax({
            url: $(this).val() == 'Create' ? url : upurl,
            type: "POST",
            contentType: false,
            processData: false,
            data: form_data,
            success: function(d){
                if (d.status == 303) $(".ermsg").html(d.message);
                else if(d.status == 300){
                    toastr.success($(this).val() == 'Create' ? "Manager created successfully" : "Manager updated successfully", "Success!");
                    window.setTimeout(function(){location.reload()},2000)
                }
            },
            error:function(d){ console.log(d); }
        });
    });

    // Edit
    $("#contentContainer").on('click','#EditBtn', function(){
        let codeid = $(this).attr('rid');
        $.get(url + '/'+codeid+'/edit', function(d){ populateForm(d); });
    });

    function populateForm(data){
        $("#first_name").val(data.first_name);
        $("#last_name").val(data.last_name);
        $("#phone").val(data.phone);
        $("#email").val(data.email);
        $("#role_id").val(data.role_id);
        $("#codeid").val(data.id);
        $("#addBtn").val('Update').html('Update');
        $("#addThisFormContainer").show(300);
        $("#newBtn").hide(100);
    }
    function clearform(){
        $('#createThisForm')[0].reset();
        $("#addBtn").val('Create');
    }
});
</script>
@endsection
