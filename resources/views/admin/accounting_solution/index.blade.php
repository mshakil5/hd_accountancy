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
      <div class="col-md-10">
        <!-- general form elements disabled -->
        <div class="card card-secondary">
          <div class="card-header">
            <h3 class="card-title">Add new</h3>
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
                    <label>Short Title</label>
                    <input type="text" class="form-control" id="short_title" name="short_title" placeholder="Enter short title">
                  </div>
                </div>
                <div class="col-sm-12">
                  <div class="form-group">
                        <label>Long Description</label>
                        <textarea class="form-control summernote" id="long_description" name="long_description" placeholder="Enter long description"></textarea>
                    </div>
                </div>
                <div class="col-sm-12">
                    <div class="form-group">
                        <label>Image</label>
                        <input type="file" id="image" name="image" class="form-control" onchange="previewMetaImage(event)" accept="image/*">
                    </div>
                    <img id="image_preview" src="#" alt="Meta Image Preview" class="pt-3" style="max-width: 150px; height: auto; display: none;"/>
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
              <table id="example1" class="table cell-border table-striped">
                <thead>
                <tr>
                  <th style="text-align: center">Sl</th>
                  <th style="text-align: center">Short Title</th>
                  <th style="text-align: center">Image</th>
                  <th style="text-align: center">Action</th>
                </tr>
                </thead>
                <tbody>
                  @foreach ($data as $key => $data)
                  <tr>
                    <td style="text-align: center">{{ $key + 1 }}</td>
                    <td style="text-align: center">{{$data->short_title}}</td>
                    <td style="text-align: center"><img src="{{ asset('/' . $data->image) }}" width="100" height="100"></td>
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
    function previewMetaImage(event) {
        var output = document.getElementById('image_preview');
        output.src = URL.createObjectURL(event.target.files[0]);
        output.style.display = 'block';
    }

    $(document).ready(function() {
        $('.summernote').summernote({
            height: 200, 
        });
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
      var url = "{{URL::to('/admin/accounting-solution')}}";
      var upurl = "{{URL::to('/admin/accounting-solution-update')}}";
      // console.log(url);
      $("#addBtn").click(function(){
      //   alert("#addBtn");
          if($(this).val() == 'Create') {
              var form_data = new FormData();
              form_data.append("short_title", $("#short_title").val());
              form_data.append("long_description", $("#long_description").summernote('code'));
              form_data.append("image", $("#image")[0].files[0]);
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
                      swal({
                            title: "Success!",
                            text: "Created successfully",
                            icon: "success",
                            button: "OK",
                        });
                      window.setTimeout(function(){location.reload()},2000)
                      }
                },
                error: function(xhr, status, error) {
                     console.error(xhr.responseText);
                }
            });
          }
          //create  end
          //Update
          if($(this).val() == 'Update'){
              var form_data = new FormData();
              form_data.append("short_title", $("#short_title").val());
              form_data.append("long_description", $("#long_description").summernote('code'));
              form_data.append("image", $("#image")[0].files[0]);
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
                        swal({
                            title: "Success!",
                            text: "Updated successfully",
                            icon: "success",
                            button: "OK",
                        });
                      window.setTimeout(function(){location.reload()},2000)
                      }
                  },
                  error: function(xhr, status, error) {
                     console.error(xhr.responseText);
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
                    if (d.status === 300) {
                      $(".ermsg").html(d.message);
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
        $("#short_title").val(data.short_title);
        $("#long_description").summernote('code', data.long_description);
        if (data.image) {
            $("#image_preview").attr("src", "/" + data.image).show();
        } else {
            $("#image_preview").attr("src", "").hide();
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
        $("#long_description").summernote('code', '');
        $('#image_preview').attr('src', '#').hide();
      }
  });
</script>
@endsection