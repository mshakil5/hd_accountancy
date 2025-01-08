@extends('admin.layouts.admin')

@section('content')
<!-- Main content -->
<section class="content mt-3" id="newBtnSection">
    <div class="container-fluid">
      <div class="row">
        <div class="col-2">
            <button type="button" class="btn btn-secondary my-3" id="newBtn">Add new</button>
        </div>
      </div>
    </div>
</section>
<!-- /.content -->

<!-- Main content -->
<section class="content" id="addThisFormContainer">
  <div class="container-fluid">
    <div class="row justify-content-md-center">
      <!-- right column -->
      <div class="col-md-8">
        <!-- general form elements disabled -->
        <div class="card card-secondary">
          <div class="card-header">
            <h3 class="card-title">Add new admin</h3>
          </div>
          <!-- /.card-header -->
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
                    <label>Last Name</label>
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
 
          <!-- /.card-body -->
          <div class="card-footer">
            <button type="submit" id="addBtn" class="btn btn-secondary" value="Create">Create</button>
            <button type="submit" id="FormCloseBtn" class="btn btn-default">Cancel</button>
          </div>
          <!-- /.card-footer -->
          <!-- /.card-body -->
        </div>
      </div>
      <!--/.col (right) -->
    </div>
    <!-- /.row -->
  </div><!-- /.container-fluid -->
</section>
<!-- /.content -->

<!-- Main content -->
<section class="content" id="contentContainer">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <!-- /.card -->
          <div class="card card-secondary">
            <div class="card-header">
              <h3 class="card-title">All Admins</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
              <table id="example1" class="table cell-border table-striped">
                <thead>
                <tr>
                  <th>Sl</th>
                  <th>First Name</th>
                  <th>Last Name</th>
                  <th>Phone</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Action</th>
                </tr>
                </thead>
                <tbody>
                  @foreach ($data as $key => $data)
                  <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{$data->first_name}}</td>
                    <td>{{$data->last_name	}}</td>
                    <td>{{$data->phone}}</td>
                    <td>{{$data->email}}</td>
                    <td>{{$data->role ? $data->role->name : ''}}</td>
                    <td>
                      <a class="btn btn-link" id="EditBtn" rid="{{$data->id}}"><i class="fa fa-edit" style="font-size: 20px;"></i></a>
                      {{-- 
                      @if (Auth::user()->id != $data->id)
                        <a class="btn btn-link" id="deleteBtn" rid="{{$data->id}}"><i class="fas fa-trash" style="color: red; font-size: 20px;"></i></a>
                      @endif
                      --}}
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            <!-- /.card-body -->
          </div>
          <!-- /.card -->
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->
    </div>
    <!-- /.container-fluid -->
</section>
<!-- /.content -->

@endsection
@section('script')

<script>
    $(function () {
      $("#example1").DataTable();
    });

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
</script>

<script>
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
      //header for csrf-token is must in laravel
      $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
      var url = "{{URL::to('/admin/new-admin')}}";
      var upurl = "{{URL::to('/admin/new-admin-update')}}";
      // console.log(url);
      $("#addBtn").click(function(){
      //   alert("#addBtn");
          if($(this).val() == 'Create') {
              var form_data = new FormData();
              form_data.append("first_name", $("#first_name").val());
              form_data.append("email", $("#email").val());
              form_data.append("phone", $("#phone").val());
              form_data.append("last_name", $("#last_name").val());
              form_data.append("password", $("#password").val());
              form_data.append("confirm_password", $("#confirm_password").val());
              form_data.append("role_id", $("#role_id").val());
              $.ajax({
                url: url,
                method: "POST",
                contentType: false,
                processData: false,
                data:form_data,
                success: function (d) {
                    if (d.status == 303) {
                        // $(".ermsg").html(d.message);
                    }else if(d.status == 300){
                      // $(".ermsg").html(d.message);
                      toastr.success("Admin created successfully", "Success!");
                      window.setTimeout(function(){location.reload()},2000)
                      }
                },
                error: function (d) {
                  console.log(d);
                }
            });
          }
          //create  end
          //Update
          if($(this).val() == 'Update'){
              var form_data = new FormData();
              form_data.append("first_name", $("#first_name").val());
              form_data.append("email", $("#email").val());
              form_data.append("phone", $("#phone").val());
              form_data.append("last_name", $("#last_name").val());
              form_data.append("password", $("#password").val());
              form_data.append("confirm_password", $("#confirm_password").val());
              form_data.append("role_id", $("#role_id").val());
              form_data.append("codeid", $("#codeid").val());
              
              $.ajax({
                  url:upurl,
                  type: "POST",
                  dataType: 'json',
                  contentType: false,
                  processData: false,
                  data:form_data,
                  success: function(d){
                      // console.log(d);
                      if (d.status == 303) {
                        // $(".ermsg").html(d.message);
                      }else if(d.status == 300){
                        toastr.success("Admin updated successfully", "Success!");
                      window.setTimeout(function(){location.reload()},2000)
                      }
                  },
                  error:function(d){
                    console.log(d);
                  }
              });
          }
          //Update
      });
      //Edit
      $("#contentContainer").on('click','#EditBtn', function(){
          //alert("btn work");
          codeid = $(this).attr('rid');
          //console.log($codeid);
          info_url = url + '/'+codeid+'/edit';
          //console.log($info_url);
          $.get(info_url,{},function(d){
            populateForm(d);
          });
      });
      //Edit  end
      //Delete
      $("#contentContainer").on('click','#deleteBtn', function(){
            if(!confirm('Sure?')) return;
            codeid = $(this).attr('rid');
            info_url = url + '/'+codeid;
            $.ajax({
                url:info_url,
                method: "GET",
                type: "DELETE",
                data:{
                },
                success: function(d){
                    if(d.success) {
                      alert(d.message);
                      location.reload();
                    }
                },
                error:function(d){
                  console.log(d);
                }
            });
        });
      //Delete  
      function populateForm(data){
        $("#first_name").val(data.first_name);
        $("#last_name").val(data.last_name);
        $("#phone").val(data.phone);
        $("#email").val(data.email);
        $("#role_id").val(data.role_id);
        $("#codeid").val(data.id);
        $("#addBtn").val('Update');
        $("#addBtn").html('Update');
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