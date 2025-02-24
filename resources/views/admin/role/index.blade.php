@extends('admin.layouts.admin')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}" />

<div class="row">

  <div class="col-md-5">
    <div class="card">
      <div class="card-header text-center">
        <h3 class="card-title">Roles</h3>
      </div>
      <div class="card-body ir-table">
        <table class="table table-hover table-responsive" width="100%" id="supplierTBL">
          <thead>
            <tr>
              <th>Name</th>
              <th><i class=""></i> Action</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($roles as $data)
            <tr>
              <td>{{ $data->name }}</td>
              <td>
                <a href="{{ route('admin.roleedit', $data->id)}}" class="btn btn-success btn-sm"><i class='fa fa-pencil'></i> Edit</a>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-md-7">
    <div class="card">
      <div class="card-header text-center">
        <h3 class="card-title">Create Role</h3>
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
                    <input name="name" id="name" type="text" class="form-control" maxlength="50px" required="required" />
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
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="1"><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(2, $permissions))
                    <tr>
                      <td><label class="control-label">Switch To Web And Software</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="2"><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(3, $permissions))
                    <tr>
                      <td><label class="control-label">Manage Admin</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="3"><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(4, $permissions))
                    <tr>
                      <td><label class="control-label">Manage Manager</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="4"><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(5, $permissions))
                    <tr>
                      <td><label class="control-label">Manage Staff</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="5"><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(6, $permissions))
                    <tr>
                      <td><label class="control-label">Manage Department</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="6"><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(7, $permissions))
                    <tr>
                      <td><label class="control-label">Client Entry</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="7"><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(8, $permissions))
                    <tr>
                      <td><label class="control-label">Client Manage</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="8"><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(9, $permissions))
                    <tr>
                      <td><label class="control-label">Client Type</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="9"><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(10, $permissions))
                    <tr>
                      <td><label class="control-label">Manage Admin Tasks</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="10"><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                  </table>
                </div>
                <div class="col-md-6">
                  <table class="table table-hover">
                    @if(in_array(11, $permissions))
                    <tr>
                      <td><label class="control-label">Manage One Time Tasks</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="11"><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(12, $permissions))
                    <tr>
                      <td><label class="control-label">Manage Services</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="12"><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(13, $permissions))
                    <tr>
                      <td><label class="control-label">Manage Attendence Records</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="13"><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(14, $permissions))
                    <tr>
                      <td><label class="control-label">Manage Holidays</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="14"><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(15, $permissions))
                    <tr>
                      <td><label class="control-label">Manage Holiday Types</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="15"><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(16, $permissions))
                    <tr>
                      <td><label class="control-label">Prorota</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="16"><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(17, $permissions))
                    <tr>
                      <td><label class="control-label">Manage Role & Permission</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="17"><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(19, $permissions))
                    <tr>
                      <td><label class="control-label">Manage Recycle Bin</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="19"><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                    @if(in_array(18, $permissions))
                    <tr>
                      <td><label class="control-label">Update Frontend Contents</label></td>
                      <td>
                        <label style="margin-top: -9px" class="switch"><input name="permission[]" type="checkbox" value="18"><span class="slider round"></span></label>
                      </td>
                    </tr>
                    @endif
                  </table>
                </div>
              </div>

              <br>
              <button class="btn btn-success btn-md center-block" id="submitBtn" type="submit"><i class="fa fa-plus-circle"></i> Submit </button>
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
  $(document).ready(function() {

    $.ajaxSetup({
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
    });

    var url = "{{URL::to('/admin/role')}}";
    $("body").delegate("#submitBtn", "click", function(event) {
      event.preventDefault();

      var name = $("#name").val();
      var permission = $("input:checkbox:checked[name='permission[]']")
        .map(function() {
          return $(this).val();
        }).get();

      $.ajax({
        url: url,
        method: "POST",
        data: {
          name,
          permission
        },
          success: function(d) {
            if (d.status == 200) {
                toastr.success(d.message);
                window.setTimeout(function() {
                    location.reload();
                }, 2000);
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