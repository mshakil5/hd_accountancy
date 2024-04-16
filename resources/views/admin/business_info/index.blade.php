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
        <div class="card card-secondary">
            <div class="card-header">
            <h3 class="card-title">Add new business info</h3>
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
                                    Client Reference
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
                                <label>
                                    Nature Of Business
                                </label>
                                <input type="text" class="form-control" id="nature_of_business" name="nature_of_business">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Company Number</label>
                                <input type="number" class="form-control" id="company_number" name="company_number">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Year End Date</label>
                                <input type="date" class="form-control" id="year_end_date" name="year_end_date">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Due Date</label>
                                <input type="date" class="form-control" id="due_date" name="due_date">
                            </div>
                        </div>
                        <div class="col-sm-6" >
                            <div class="form-group">
                                <label>Confirmation Due Date</label>
                                <input type="date" class="form-control" id="confirmation_due_date" name="confirmation_due_date">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6" >
                            <div class="form-group">
                                <label>Aauthorization Code</label>
                                <input type="number" class="form-control" id="authorization_code" name="authorization_code">
                            </div>
                        </div>
                        <div class="col-sm-6" >
                            <div class="form-group">
                                <label>Company UTR</label>
                                <input type="number" class="form-control" id="company_utr" name="company_utr">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6" >
                            <div class="form-group">
                                <label>Company Status</label>
                                <select name="status" id="status" class="form-control">
                                <option value="
                                " selected disabled>Select company status</option>
                                <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Inactive</option>
                                <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Active</option>
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
              <h3 class="card-title">All Data</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
              <table id="example1" class="table table-bordered table-striped">
                <thead>
                <tr>
                  <th style="text-align: center">Sl</th>
                  <th style="text-align: center">Client Name</th>
                  <th style="text-align: center">Nature Of Business</th>
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
                    <td style="text-align: center">{{$data->nature_of_business}}
                    </td>
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
      var url = "{{URL::to('/admin/business-info')}}";
      var upurl = "{{URL::to('/admin/business-info-update')}}";
      // console.log(url);
      $("#addBtn").click(function(){
      //   alert("#addBtn");
          if($(this).val() == 'Create') {
              var form_data = new FormData();

              var clientId = $("#client_id").val();
                if (!clientId) {
                var message = "<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please choose a client.</b></div>";
                $('.ermsg').html(message);
                } else {
                    form_data.append("client_id", clientId);
                }

              form_data.append("nature_of_business", $("#nature_of_business").val());
              form_data.append("company_number", $("#company_number").val());
              form_data.append("year_end_date", $("#year_end_date").val());
              form_data.append("due_date", $("#due_date").val());
              form_data.append("confirmation_due_date", $("#confirmation_due_date").val());
              form_data.append("authorization_code", $("#authorization_code").val());
              form_data.append("company_utr", $("#company_utr").val());
              form_data.append("status", $("#status").val());

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
              form_data.append("nature_of_business", $("#nature_of_business").val());
              form_data.append("company_number", $("#company_number").val());
              form_data.append("year_end_date", $("#year_end_date").val());
              form_data.append("due_date", $("#due_date").val());
              form_data.append("confirmation_due_date", $("#confirmation_due_date").val());
              form_data.append("authorization_code", $("#authorization_code").val());
              form_data.append("company_utr", $("#company_utr").val());
              form_data.append("status", $("#status").val());

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
          $("#nature_of_business").val(data.nature_of_business);
          $("#company_number").val(data.company_number);
          $("#year_end_date").val(data.year_end_date);
          $("#due_date").val(data.due_date);
          $("#confirmation_due_date").val(data.confirmation_due_date);
          $("#authorization_code").val(data.authorization_code);
          $("#company_utr").val(data.company_utr);
          $("#status").val(data.status);
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