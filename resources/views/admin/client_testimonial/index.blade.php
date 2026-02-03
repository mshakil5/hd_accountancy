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
      <div class="col-md-12">
        <!-- general form elements disabled -->
        <div class="card card-secondary border-theme border-2">
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
                <div class="col-sm-6">
                  <div class="form-group">
                    <label>Title</label>
                    <input type="text" class="form-control" id="title" name="title" placeholder="Enter title">
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="form-group">
                    <label>Link</label>
                    <input type="url" class="form-control" id="link" name="link" placeholder="Enter link">
                  </div>
                </div>           
                <div class="col-sm-12">
                  <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-control" id="description" name="description" placeholder="Enter long description" rows="3"></textarea>
                    </div>
                </div>
                <div class="col-sm-12">
                    <div class="form-group">
                        <label>Video Thumbnail</label>
                        <input type="file" id="thumbnail" name="thumbnail" class="form-control" onchange="previewMetaImage(event)" accept="image/*">
                    </div>
                    <img id="image_preview" src="#" alt="Meta Image Preview" class="pt-3" style="max-width: 150px; height: auto; display: none;"/>
                </div>
                <div class="col-sm-12">
                    <div class="form-group">
                        <label>Video</label>
                        <input type="file" id="video" name="video" class="form-control" onchange="previewVideo(event)" accept="video/*">
                        <video id="video_preview" width="150" height="100" controls style="display: none;">
                            <source src="#" id="video_source">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                </div>
              </div>      
            </form>
          </div>
 
          <!-- /.card-body -->
          <div class="card-footer">
            <button type="submit" id="addBtn" class="btn btn-secondary" value="Create">Create</button>
            <button type="submit" id="FormCloseBtn" class="btn btn-default">Cancel</button>
              <div class="loader text-center" style="display: none;">
                  <div class="spinner-border text-primary" role="status">
                      <span class="visually-hidden">Loading...</span>
                  </div>
              </div>
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
              <table id="example1" class="table table-striped">
                <thead>
                <tr>
                  <th style="text-align: center">Sl</th>
                  <th style="text-align: center">Title</th>
                  <th style="text-align: center">Thumbnail</th>
                  <th style="text-align: center">Action</th>
                </tr>
                </thead>
                <tbody>
                  @foreach ($data as $key => $data)
                  <tr>
                    <td style="text-align: center">{{ $key + 1 }}</td>
                    <td style="text-align: center">{{ $data->title }}</td>
                    <td style="text-align: center"><img src="{{ $data->thumbnail }}" style="width: 100px; height: auto" /></td>
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

    function previewVideo(event) {
        var video = document.getElementById('video');
        var videoPreview = document.getElementById('video_preview');
        var videoSource = document.getElementById('video_source');

        videoPreview.style.display = "block";

        var file = video.files[0];
        var fileURL = URL.createObjectURL(file);
        videoSource.src = fileURL;
        videoPreview.load();
        videoPreview.play();
    }
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
      var url = "{{URL::to('/admin/client-testimonial')}}";
      var upurl = "{{URL::to('/admin/client-testimonial-update')}}";
      // console.log(url);
      $("#addBtn").click(function(){
      //   alert("#addBtn");
          if($(this).val() == 'Create') {
              var form_data = new FormData();
              form_data.append("title", $("#title").val());
              form_data.append("link", $("#link").val());
              form_data.append("description", $("#description").val());
              form_data.append("thumbnail", $("#thumbnail")[0].files[0]);
              form_data.append("video", $("#video")[0].files[0]);
              $.ajax({
                url: url,
                method: "POST",
                contentType: false,
                processData: false,
                data:form_data,
                beforeSend: function() {
                    document.querySelector('.loader').style.display = 'block';
                    document.getElementById('addBtn').disabled = true;
                },
                success: function (d) {
                    document.querySelector('.loader').style.display = 'none';
                    document.getElementById('addBtn').disabled = false;
                    if (d.status == 303) {
                        $(".ermsg").html(d.message);
                    }else if(d.status == 300){
                      $(".ermsg").html(d.message);
                      window.setTimeout(function(){location.reload()},2000)
                      }
                },
                error: function(xhr, status, error) {
                    document.querySelector('.loader').style.display = 'none';
                    document.getElementById('addBtn').disabled = false;
                     console.error(xhr.responseText);
                }
            });
          }
          //create  end
          //Update
          if($(this).val() == 'Update'){
              var form_data = new FormData();
              form_data.append("title", $("#title").val());
              form_data.append("link", $("#link").val());
              form_data.append("description", $("#description").val());
              form_data.append("thumbnail", $("#thumbnail")[0].files[0]);
              form_data.append("video", $("#video")[0].files[0]);
              form_data.append("codeid", $("#codeid").val());
              
              $.ajax({
                  url:upurl,
                  type: "POST",
                  dataType: 'json',
                  contentType: false,
                  processData: false,
                  data:form_data,
                  beforeSend: function() {
                      document.querySelector('.loader').style.display = 'block';
                      document.getElementById('addBtn').disabled = true;
                  },
                  success: function(d){
                    document.querySelector('.loader').style.display = 'none';
                    document.getElementById('addBtn').disabled = false;
                      console.log(d);
                      if (d.status == 303) {
                        $(".ermsg").html(d.message);
                      }else if(d.status == 300){
                        $(".ermsg").html(d.message);
                      window.setTimeout(function(){location.reload()},2000)
                      }
                  },
                  error: function(xhr, status, error) {
                    document.querySelector('.loader').style.display = 'none';
                    document.getElementById('addBtn').disabled = false;
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
                  $(".ermsg").html(d.message);
                  window.setTimeout(function(){location.reload()},2000)
                },
                error:function(d){
                  console.log(d);
                }
            });
        });
      //Delete  
      function populateForm(data){
        $("#link").val(data.link);
        $("#title").val(data.title);
        $("#description").val(data.description);
        if (data.thumbnail) {
            $("#image_preview").attr("src", data.thumbnail).show();
        } else {
            $("#image_preview").attr("src", "").hide();
        }
        if (data.video) {
            $("#video_preview").attr("src", data.video).show();
        } else {
            $("#video_preview").attr("src", "").hide();
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
        $('#image_preview').attr('src', '#').hide();
        $('#video_preview').hide();
      }
  });
</script>
@endsection