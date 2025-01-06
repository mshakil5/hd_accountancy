@extends('admin.layouts.admin')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}" />

<div class="row">

  <div class="col-md-7">
    <button type="button" onclick="history.back()" class="btn btn-primary mb-3">
      <i class="fa fa-arrow-left"></i> Back
    </button>

    <div class="card">
      <div class="card-header text-center">
        <h3 class="card-title">{{ $data->name }}</h3>
      </div>
      <div class="card-body ir-table">
        <form action="" method="post" enctype="multipart/form-data">
          {{ csrf_field() }}
          <div class="row">
            <div class="col-md-1">
              <table class="table table-hover">
              </table>
            </div>
            <div class="col-md-10">
              <table class="table table-hover">
                <tr>
                  <td><label class="control-label">Role Name <span class="text-danger">*</span></label></td>
                  <td>
                    <input name="name" id="name" type="text" class="form-control" maxlength="50px" value="{{ $data->name }}" required="required" />
                    <input name="id" id="id" type="hidden" class="form-control" maxlength="50px" value="{{ $data->id }}" required="required" />
                  </td>
                </tr>
              </table>

              <div class="row">
                <div class="col-md-6">
                  <table class="table table-hover">
                    @if(in_array(1, $permissions))
                    <tr>
                      <td><label class="control-label">Dashboard Content</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="1"
                            @foreach (json_decode($data->permission) as $permission) @if ($permission == 1) checked @endif @endforeach
                          >
                          <span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(2, $permissions))
                    <tr>
                      <td><label class="control-label">Switch To Web And Software</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="2"
                            @foreach (json_decode($data->permission) as $permission) @if ($permission == 2) checked @endif @endforeach><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(3, $permissions))
                    <tr>
                      <td><label class="control-label">Manage Admin</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="3" @foreach (json_decode($data->permission) as $permission) @if ($permission == 3) checked @endif @endforeach><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(4, $permissions))
                    <tr>
                      <td><label class="control-label">Manage Manager</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="4" @foreach (json_decode($data->permission) as $permission) @if ($permission == 4) checked @endif @endforeach><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(5, $permissions))
                    <tr>
                      <td><label class="control-label">Manage Staff</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="5" @foreach (json_decode($data->permission) as $permission) @if ($permission == 5) checked @endif @endforeach><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(6, $permissions))
                    <tr>
                      <td><label class="control-label">Manage Department</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="6" @foreach (json_decode($data->permission) as $permission) @if ($permission == 6) checked @endif @endforeach><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(7, $permissions))
                    <tr>
                      <td><label class="control-label">Client Entry</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="7" @foreach (json_decode($data->permission) as $permission) @if ($permission == 7) checked @endif @endforeach><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(8, $permissions))
                    <tr>
                      <td><label class="control-label">Client Manage</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="8" @foreach (json_decode($data->permission) as $permission) @if ($permission == 8) checked @endif @endforeach><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(9, $permissions))
                    <tr>
                      <td><label class="control-label">Client Type</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="9" @foreach (json_decode($data->permission) as $permission) @if ($permission == 9) checked @endif @endforeach><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                  </table>
                </div>

                <div class="col-md-6">
                  <table class="table table-hover">
                    @if(in_array(10, $permissions))
                    <tr>
                      <td><label class="control-label">Manage Admin Tasks</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="10" @foreach (json_decode($data->permission) as $permission) @if ($permission == 10) checked @endif @endforeach><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(11, $permissions))
                    <tr>
                      <td><label class="control-label">Manage One Time Tasks</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="11" @foreach (json_decode($data->permission) as $permission) @if ($permission == 11) checked @endif @endforeach><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(12, $permissions))
                    <tr>
                      <td><label class="control-label">Manage Services</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="12" @foreach (json_decode($data->permission) as $permission) @if ($permission == 12) checked @endif @endforeach><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(13, $permissions))
                    <tr>
                      <td><label class="control-label">Manage Attendence Records</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="13" @foreach (json_decode($data->permission) as $permission) @if ($permission == 13) checked @endif @endforeach><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(14, $permissions))
                    <tr>
                      <td><label class="control-label">Manage Holidays</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="14" @foreach (json_decode($data->permission) as $permission) @if ($permission == 14) checked @endif @endforeach><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(15, $permissions))
                    <tr>
                      <td><label class="control-label">Manage Holiday Types</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="15" @foreach (json_decode($data->permission) as $permission) @if ($permission == 15) checked @endif @endforeach><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(16, $permissions))
                    <tr>
                      <td><label class="control-label">Prorota</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="16" @foreach (json_decode($data->permission) as $permission) @if ($permission == 16) checked @endif @endforeach><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(17, $permissions))
                    <tr>
                      <td><label class="control-label">Manage Role & Permission</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="17" @foreach (json_decode($data->permission) as $permission) @if ($permission == 17) checked @endif @endforeach><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(18, $permissions))
                    <tr>
                      <td><label class="control-label">Update Frontend Contents</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="18" @foreach (json_decode($data->permission) as $permission) @if ($permission == 18) checked @endif @endforeach><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                  </table>
                </div>
              </div>

              <br>
              <button class="btn btn-success btn-md center-block" id="updateBtn" type="submit"><i class="fa fa-plus-circle"></i> Update </button>
            </div>
            <div class="col-md-1">
              <table class="table table-hover">
              </table>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection

@section('script')
<script>
    $(document).ready(function () {

        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

        var url = "{{URL::to('/admin/role-update')}}";

        $("body").delegate("#updateBtn","click",function(event){
            event.preventDefault();

            var name = $("#name").val();
            var id = $("#id").val();
            var permission = $("input:checkbox:checked[name='permission[]']")
                .map(function(){return $(this).val();}).get();

            $.ajax({
                url: url,
                method: "POST",
                data: {id,name,permission},

                success: function(d) {
                  if (d.status == 200) {
                      toastr.success(d.message);
                      window.setTimeout(function() {
                          window.location.href = "{{ route('admin.role') }}";
                      }, 1000);
                  } else if (d.status == 422) {
                      toastr.error(d.message);
                  }
              },
              error: function(d) {
                  toastr.error("An error occurred. Please try again.");
              }
            });
        });
    });  
</script>

@endsection