@extends('admin.layouts.admin')

@section('content')

<style>
  .switch {
      position: relative;
      display: inline-block;
      width: 50px;
      height: 25px;
  }

  .switch input {
      opacity: 0;
      width: 0;
      height: 0;
  }

  .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      transition: .4s;
      border-radius: 25px;
  }

  .slider:before {
      position: absolute;
      content: "";
      height: 18px;
      width: 18px;
      left: 4px;
      bottom: 3.5px;
      background-color: white;
      transition: .4s;
      border-radius: 50%;
  }

  input:checked + .slider {
      background-color: #233969;
  }

  input:checked + .slider:before {
      transform: translateX(24px);
  }

  .perm-group {
      border: 1px solid #e3e6ea;
      border-radius: 10px;
      padding: 10px 14px 6px;
      margin-bottom: 14px;
      background: #fff;
  }

  .perm-group > legend {
      float: left;
      width: auto;
      padding: 2px 12px;
      margin-bottom: 0;
      font-size: 13px;
      font-weight: 700;
      color: #233969;
      background: #eef2f7;
      border: 1px solid #e3e6ea;
      border-radius: 20px;
  }

  .perm-group .group-toggle {
      font-size: 11px;
      font-weight: 400;
      color: #6c757d;
      margin-left: 8px;
  }

  .perm-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 7px 2px;
      border-bottom: 1px dashed #f0f0f0;
  }

  .perm-row:last-child {
      border-bottom: none;
  }

  .perm-cols {
      column-count: 2;
      column-gap: 16px;
  }

  .perm-cols .perm-group {
      break-inside: avoid;
  }

  @media (max-width: 767px) {
      .perm-cols {
          column-count: 1;
      }
  }
</style>

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
        <h3 class="card-title">Create New Role</h3>
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
                  <td><label class="control-label pt-2">Role Name <span class="text-danger">*</span></label></td>
                  <td>
                    <input name="name" id="name" type="text" class="form-control" maxlength="50px" required="required" />
                  </td>
                </tr>
              </table>

              @php
                // Module-grouped permissions: one module = one legend.
                // A creator only sees toggles they hold themselves (no escalation).
                $myPerms = array_map('intval', (array) ($permissions ?? []));
                $modules = [
                  'Dashboard' => [1 => 'Dashboard Content'],
                  'Access' => [2 => 'Switch To Web And Software'],
                  'Team' => [3 => 'Manage Admin', 4 => 'Manage Manager', 5 => 'Manage Staff', 6 => 'Manage Department'],
                  'Clients' => [7 => 'Client Entry', 8 => 'Client Manage', 9 => 'Client Type', 28 => 'Client Credentials'],
                  'Tasks' => [10 => 'Manage Admin Tasks', 11 => 'Manage One Time Tasks'],
                  'Services' => [12 => 'Manage Services'],
                  'Attendance & Leave' => [13 => 'Attendance Records', 14 => 'Holidays', 15 => 'Holiday Types'],
                  'Prorota' => [16 => 'Prorota'],
                  'Accounting' => [21 => 'Receipts', 22 => 'Tax Rates', 23 => 'Account Types', 24 => 'Chart of Accounts', 25 => 'Profit & Loss', 26 => 'Trial Balance', 27 => 'Balance Sheet'],
                  'Reports' => [20 => 'Reports'],
                  'System' => [17 => 'Roles & Permissions', 19 => 'Recycle Bin'],
                  'Website' => [18 => 'Frontend Contents'],
                ];
              @endphp

              <div class="perm-cols">
              @foreach ($modules as $group => $items)
                @php $visible = array_filter(array_keys($items), fn($id) => in_array($id, $myPerms)); @endphp
                @if(count($visible))
                  <fieldset class="perm-group" data-group="{{ Str::slug($group) }}">
                    <legend>{{ $group }}<label class="group-toggle"><input type="checkbox" class="group-check"> all</label></legend>
                    @foreach ($items as $id => $label)
                      @if(in_array($id, $myPerms))
                        <div class="perm-row">
                          <label class="control-label mb-0">{{ $label }}</label>
                          <label style="margin-top: -9px" class="switch mb-0"><input name="permission[]" type="checkbox" value="{{ $id }}"><span class="slider round"></span></label>
                        </div>
                      @endif
                    @endforeach
                  </fieldset>
                @endif
              @endforeach
              </div>
            </div>
            <div class="col-md-1">
              <table class="table table-hover">
              </table>
            </div>
          </div>
        </form>

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
    // Per-module "select all" toggles (only affects visible checkboxes in that fieldset)
    $("body").delegate(".group-check", "change", function() {
      $(this).closest("fieldset").find("input[name='permission[]']").prop("checked", $(this).is(":checked"));
    });
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
            } else {
                toastr.error((d && d.message) ? d.message : "Request rejected.");
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