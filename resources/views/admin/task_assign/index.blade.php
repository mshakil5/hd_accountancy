@extends('admin.layouts.admin')

@section('content')

<!-- Main content -->
<section class="content mt-3" id="newBtnSection">
    <div class="container-fluid">
      <div class="row">
        <div class="col-2">
            <button type="button" class="btn btn-secondary my-3" id="newBtn">Assign new</button>
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
            <h3 class="card-title">Assign new task</h3>
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
                            <label>
                                Date
                            </label>
                            <input type="date" class="form-control" id="date" name="date">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>Staff ID</label>
                            <select class="form-control" name="staff_id" id="staff_id">
                                <option value="" selected disabled>Select staff</option>
                                @foreach($staffs as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->first_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>                   
                </div>
                <div class="row">   
                    <div class="col-sm-6">
                        <div class="form-group">
                        <label>Client ID</label>
                            <select class="form-control" name="client_id" id="client_id">
                                <option value="" selected disabled>Select client</option>
                                @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>                
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>
                                Note
                            </label>
                            <input type="text" class="form-control" id="note" name="note">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group">
                            <label>Tasks</label><br>
                            @foreach($tasks as $task)
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="task_{{ $task->id }}" name="tasks[]" value="{{ $task->id }}">
                                    <label class="form-check-label" for="task_{{ $task->id }}">{{ $task->task }}</label>
                                </div>
                            @endforeach
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
              <h3 class="card-title">All Data</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
              <table id="example1" class="table table-bordered table-striped">
                <thead>
                <tr>
                  <th style="text-align: center">Sl</th>
                  <th style="text-align: center">Task</th>
                  <th style="text-align: center">Note</th>
                  <th style="text-align: center">Date</th>
                  <th style="text-align: center">Action</th>
                </tr>
                </thead>
                <tbody>
                  @foreach ($data as $key => $data)
                  <tr>
                    <td style="text-align: center">{{ $key + 1 }}</td>
                    <td style="text-align: center">{{$data->task}}</td>
                    <td style="text-align: center">{{$data->note}}</td>
                    <td style="text-align: center">{{$data->date}}</td>
                    <td style="text-align: center">
                      <a class="btn btn-link" id="EditBtn" rid="{{$data->id}}"><i class="fa fa-edit" style="font-size: 20px;"></i></a>
                      <a class="btn btn-link" id="deleteBtn" rid="{{$data->id}}"><i class="fa fa-trash-o" style="color: red; font-size: 20px;"></i></a>
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
      //
      var url = "{{URL::to('/admin/task-assign')}}";
      var upurl = "{{URL::to('/admin/task-assign-update')}}";
      // console.log(url);
      $("#addBtn").click(function(){
      //   alert("#addBtn");
          if($(this).val() == 'Create') {
              var form_data = new FormData();
              form_data.append("client_id", $("#client_id").val());
              form_data.append("staff_id", $("#staff_id").val());
              form_data.append("note", $("#note").val());
              form_data.append("date", $("#date").val());

            $("input[name='tasks[]']:checked").each(function() {
                form_data.append('tasks[]', $(this).val());
            });
            console.log(form_data);

              $.ajax({
                url: url,
                method: "POST",
                contentType: false,
                processData: false,
                data:form_data,
                success: function (d) {
                    if (d.status == 303) {
                        $(".ermsg").html(d.message);
                    }else if(d.status == 300){
                      $(".ermsg").html(d.message);
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
             var client = $("#client_id").val();
             console.log(client);
              var form_data = new FormData();
              form_data.append("client_id", $("#client_id").val());
              form_data.append("staff_id", $("#staff_id").val());
              form_data.append("note", $("#note").val());
              form_data.append("date", $("#date").val());

              var selectedTasks = [];
                $("input[name='tasks[]']:checked").each(function() {
                    selectedTasks.push($(this).val());
                });

                selectedTasks.forEach(function(taskId) {
                    form_data.append('tasks[]', taskId);
                });

              form_data.append("codeid", $("#codeid").val());
              
              $.ajax({
                  url:upurl,
                  type: "POST",
                  dataType: 'json',
                  contentType: false,
                  processData: false,
                  data:form_data,
                  success: function(d){
                      console.log(d);
                      if (d.status == 303) {
                          $(".ermsg").html(d.message);
                      }else if(d.status == 300){
                        $(".ermsg").html(d.message);
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
          $("#client_id ").val(data.client_id);
          $("#client_id").prop('disabled', true);
          $("#staff_id").val(data.staff_id );
          $("#note").val(data.note );
          $("#date").val(data.date );

        $("input[name='tasks[]']").prop('checked', false); 

        if (data.tasks && data.tasks.length > 0) {
            data.tasks.forEach(function(task) {
                $("#task_" + task.id).prop('checked', true); 
            });
        }

          
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