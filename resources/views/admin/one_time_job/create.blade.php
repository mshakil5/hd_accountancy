@extends('admin.layouts.admin')

@section('content')

<!-- Main content -->
<section class="content mt-3" id="newBtnSection">
    <div class="container-fluid">
      <div class="row">
        <div class="col-2">
            <button type="button" class="btn btn-secondary my-3" id="newBtn">Create new</button>
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
            <div class="col-md-6">
            <!-- general form elements disabled -->
                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">Add new client type</h3>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="ermsg">                      
                        </div>
                        <form id="createThisForm">                        
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                    <label>Task <span class="tex-danger">*</span></label>
                                    <input type="text" class="form-control" id="task" name="task">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                  <div class="form-group">
                                      <label for="manager_id">Assign To <span class="tex-danger">*</span></label>
                                      <select class="form-control select2" id="manager_id" name="manager_id">
                                          <option value="">Select a manager or staff</option>
                                          @foreach ($managerAndStaffs as $staff)
                                              <option value="{{ $staff->id }}">{{ $staff->first_name }} {{ $staff->last_name }}</option>
                                          @endforeach
                                      </select>
                                  </div>
                              </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                    <label>Deadline</label>
                                    <input type="date" class="form-control" id="legal_deadline" name="legal_deadline">
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
              <h3 class="card-title">One Time Jobs</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
              <table id="example1" class="table cell-border table-striped">
                <thead>
                <tr>
                  <th>Sl</th>
                  <th>Assigned Date</th>
                  <th>Task</th>
                  <th>Assigned To</th>
                  <th>Deadline</th>
                  <th>Status</th>
                </tr>
                </thead>
                <tbody>
                  @foreach ($data as $key => $data)
                  <tr class="
                      @if ($data->status == 2)
                          table-success
                      @elseif($data->status == 1)
                          table-warning
                      @elseif($data->status == 0)
                          table-info
                      @endif
                  ">
                    <td>{{ $key + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($data->created_at)->format('d-m-Y') }}</td>
                    <td>{{ $data->service->name }}</td>
                    <td>{{ $data->manager->first_name }} {{ $data->manager->last_name }}</td>
                    <td>{{ \Carbon\Carbon::parse($data->legal_deadline)->format('d-m-Y') }} </td>
                    <td>
                      @if ($data->status == 2)
                        <span>Completed</span>
                      @elseif($data->status == 1)
                        <span>Not Started Yet</span>
                      @elseif($data->status == 0)
                        <span>In Progress</span>
                      @endif
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
      $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
      var url = "{{URL::to('/admin/one-time-job')}}";
      $("#addBtn").click(function(){
          if($(this).val() == 'Create') {
              var form_data = new FormData();
              form_data.append("task", $("#task").val());
              form_data.append("manager_id", $("#manager_id").val());
              form_data.append("legal_deadline", $("#legal_deadline").val());
              $.ajax({
                url: url,
                method: "POST",
                contentType: false,
                processData: false,
                data:form_data,
                success: function (d) {
                    swal({
                          title: "Success!",
                          text: "Task assigned successfully",
                          icon: "success",
                          button: "OK",
                      });
                    window.setTimeout(function(){location.reload()},1000);
                },
                error: function (d) {
                    console.log(d);
                }
            });
          }
      });

      function clearform(){
          $('#createThisForm')[0].reset();
          $("#addBtn").val('Create');
      }
  });
</script>
@endsection