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
        <div class="col-md-6">
        <!-- general form elements disabled -->
        <div class="card card-secondary border-theme border-2">
            <div class="card-header">
            <h3 class="card-title">Add new contact info</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
                <div class="ermsg">
                </div>
                <form id="createThisForm">
                    @csrf
                    <input type="hidden" class="form-control" id="codeid" name="codeid">

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>
                                    Client ID
                                </label>
                                <select class="form-control" name="client_id" id="client_id">
                                    <option value="" selected disabled>
                                        Select client
                                    </option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="greeting">Greeting</label>                               
                                <select class="form-control" id="greeting" name="greeting">
                                    <option value="" selected disabled>
                                        Select greeting
                                    </option>
                                    <option value="Mr.">Mr.</option>
                                    <option value="Ms.">Ms.</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>
                                    First Name
                                </label>
                                <input type="text" class="form-control" id="first_name" name="first_name">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Job Title</label>
                                <input type="text" class="form-control" id="job_title" name="job_title">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6" >
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="number" class="form-control" id="phone" name="phone">
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

          <div class="card card-secondary border-theme border-2">
            <div class="card-header">
              <h3 class="card-title">All Data</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
              <table id="example1" class="table table-bordered table-striped">
                <thead>
                <tr>
                  <th style="text-align: center">Sl</th>
                  <th style="text-align: center">Client Name</th>
                  <th style="text-align: center">Contact Name</th>
                  <th style="text-align: center">Job Title</th>
                  <th style="text-align: center">Phone</th>
                  <th style="text-align: center">Email</th>
                  <th style="text-align: center">Action</th>
                </tr>
                </thead>
                <tbody>
                  @foreach ($data as $key => $data)
                  <tr>
                    <td style="text-align: center">{{ $key + 1 }}</td>
                    <td style="text-align: center">
                        @isset($data->client)
                            {{ $data->client->name }}
                        @endisset
                    </td>
                    <td style="text-align: center">{{$data->first_name}} {{$data->last_name}}</td>
                    <td style="text-align: center">{{$data->job_title}}</td>
                    <td style="text-align: center">{{$data->phone}}</td>
                    <td style="text-align: center">{{$data->email}}</td>
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
      var url = "{{URL::to('/admin/contact-info')}}";
      var upurl = "{{URL::to('/admin/contact-info-update')}}";
      // console.log(url);
      $("#addBtn").click(function(){
      //   alert("#addBtn");
          if($(this).val() == 'Create') {
              var form_data = new FormData();

                var clientId = $("#client_id").val();
                var greeting = $("#greeting").val();

                if (!clientId) {
                    var message = "<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please choose a client.</b></div>";
                    $('.ermsg').html(message);
                } else {
                    form_data.append("client_id", clientId);
                }

                if (!greeting) {
                    var message = "<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please choose a greeting.</b></div>";
                    $('.ermsg').html(message);
                } else {
                    form_data.append("greeting", greeting);
                }

              form_data.append("first_name", $("#first_name").val());
              form_data.append("last_name", $("#last_name").val());
              form_data.append("job_title", $("#job_title").val());
              form_data.append("email", $("#email").val());
              form_data.append("phone", $("#phone").val());
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
              var form_data = new FormData();
              form_data.append("client_id ", $("#client_id ").val());
              form_data.append("greeting", $("#greeting").val());
              form_data.append("first_name", $("#first_name").val());
              form_data.append("last_name", $("#last_name").val());
              form_data.append("job_title", $("#job_title").val());
              form_data.append("email", $("#email").val());
              form_data.append("phone", $("#phone").val());

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
          $("#client_id").val(data.client_id);
          $("#client_id").prop('disabled', true);
          $("#greeting").val(data.greeting);
          $("#first_name").val(data.first_name);
          $("#last_name").val(data.last_name);
          $("#job_title").val(data.job_title);
          $("#email").val(data.email);
          $("#phone").val(data.phone);

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