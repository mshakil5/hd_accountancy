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
            <h3 class="card-title">Add new image</h3>
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
                          <label>Image</label>
                          <input type="file" id="image" name="image" class="form-control" onchange="previewMetaImage(event)" accept="image/*">
                      </div>
                      <img id="image_preview" src="#" alt="Meta Image Preview" class="pt-3" style="max-width: 150px; height: auto; display: none;"/>
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
              <h3 class="card-title">All Images</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
              <table id="example1" class="table cell-border table-striped">
                <thead>
                <tr>
                  <th style="text-align: center">Sl</th>
                  <th style="text-align: center">Image</th>
                  <th style="text-align: center">Action</th>
                </tr>
                </thead>
                <tbody>
                  @foreach ($data as $key => $data)
                  <tr>
                    <td style="text-align: center">{{ $key + 1 }}</td>
                    <td style="text-align: center">
                        @if ($data->image)
                        <img src="{{asset('images/we_work_with_images/'.$data->image)}}" width="100" height="100" alt="">
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
      var url = "{{URL::to('/admin/we-work-image')}}";
      var upurl = "{{URL::to('/admin/we-work-image-update')}}";
      // console.log(url);
      $("#addBtn").click(function(){
      //   alert("#addBtn");
          if($(this).val() == 'Create') {

                var file_data = $('#image').prop('files')[0];
                if(typeof file_data === 'undefined'){
                        file_data = 'null';
                }

              var form_data = new FormData();
              form_data.append('image', file_data);

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
                error: function (d) {
                    document.querySelector('.loader')('loader').style.display = 'none';
                    document.getElementById('addBtn').disabled = false;
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

              form_data.append('image', file_data);

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

                      // console.log(d);
                      if (d.status == 303) {
                          $(".ermsg").html(d.message);
                      }else if(d.status == 300){
                        $(".ermsg").html(d.message);
                          window.setTimeout(function(){location.reload()},2000)
                      }
                  },
                  error:function(d){

                      document.querySelector('.loader').style.display = 'none';
                      document.getElementById('addBtn').disabled = false;

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
          if (data.image) {
            $("#image_preview").attr("src", "{{ asset('images/we_work_with_images/') }}" + "/" + data.image).show();
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
          $('#image_preview').attr('src', '#').hide();
      }
  });
</script>
@endsection