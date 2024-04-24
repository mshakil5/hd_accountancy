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
            <h3 class="card-title">Add new service</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
            <div class="ermsg"></div>
            <form id="createThisForm">
                @csrf
                <input type="hidden" class="form-control" id="codeid" name="codeid">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group">
                        <label>Title</label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="Enter service title">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group">
                        <label>Image</label>
                        <input type="file" class="form-control" id="image" name="image" placeholder="Upload Image">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group">
                        <label>Meta Title</label>
                        <input type="text" class="form-control" id="meta_title" name="meta_title" placeholder="Enter meta tag" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group">
                        <label>Meta Description</label>
                            <textarea class="form-control my-2" id="meta_description" rows="2" name="meta_description" placeholder="Enter meta description"></textarea>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group">
                        <label>Tag</label>
                        <input type="text" class="form-control" id="tag" name="tag" placeholder="Enter tag">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group">
                            <label>Description</label>
                            <textarea class="form-control" id="description" name="description" rows="5" placeholder="Enter description"></textarea>
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
              <h3 class="card-title">All Web Services</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
              <table id="example1" class="table cell-border table-striped">
                <thead>
                <tr>
                  <th style="text-align: center">Sl</th>
                  <th style="text-align: center">Title</th>
                  <th style="text-align: center">Meta Title</th>
                  <th style="text-align: center">Meta Description</th>
                  <th style="text-align: center">Description</th>
                  <th style="text-align: center">Image</th>
                  <th style="text-align: center">Action</th>
                </tr>
                </thead>
                <tbody>
                  @foreach ($data as $key => $data)
                  <tr>
                    <td style="text-align: center">{{ $key + 1 }}</td>
                    <td style="text-align: center">{{$data->title}}</td>
                    <td style="text-align: center">{{$data->meta_title}}</td>
                    <td style="text-align: center">{{$data->meta_description}}</td>
                    <td style="text-align: center">{{$data->description}}</td>
                    <td style="text-align: center">
                        @if ($data->image)
                        <img src="{{asset('images/webservice/'.$data->image)}}" height="120px" width="220px" alt="">
                        @endif
                    </td>
                    <td style="text-align: center">
                      <a class="btn btn-link" id="EditBtn" rid="{{$data->id}}"><i class="fa fa-edit" style="font-size: 20px;"></i></a>
                      <a class="btn btn-link" id="deleteBtn" rid="{{$data->id}}"><i class="fas fa-trash" style="color: red; font-size: 20px;"></i></a>
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
      //header for csrf-token is must in laravel
      $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
      //
      var url = "{{URL::to('/admin/web-service')}}";
      var upurl = "{{URL::to('/admin/web-service-update')}}";
      // console.log(url);
      $("#addBtn").click(function(){
      //   alert("#addBtn");
          if($(this).val() == 'Create') {

                var file_data = $('#image').prop('files')[0];
                if(typeof file_data === 'undefined'){
                        file_data = 'null';
                }

              var form_data = new FormData();
              form_data.append("title", $("#title").val());
              form_data.append("description", $("#description").val());
              form_data.append("tag", $("#tag").val());
              form_data.append("meta_title", $("#meta_title").val());
              form_data.append("meta_description", $("#meta_description").val());

              form_data.append('image', file_data);

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

                var file_data = $('#image').prop('files')[0];
                    if(typeof file_data === 'undefined'){
                            file_data = 'null';
                    }

              var form_data = new FormData();

              form_data.append("title", $("#title").val());
              form_data.append("description", $("#description").val());
              form_data.append("tag", $("#tag").val());
              form_data.append("meta_title", $("#meta_title").val());
              form_data.append("meta_description", $("#meta_description").val());

              form_data.append('image', file_data);

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
          $("#title").val(data.title);
          $("#description").val(data.description);
          $("#tag").val(data.tag);
          $("#meta_title").val(data.meta_title);
          $("#meta_description").val(data.meta_description);
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