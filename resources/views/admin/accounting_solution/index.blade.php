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
                  <div class="col-sm-4">
                      <div class="form-group">
                          <label>Short Title</label>
                          <input type="text" class="form-control" id="short_title" name="short_title" placeholder="Enter short title">
                      </div>
                  </div>
                  <div class="col-sm-4">
                      <div class="form-group">
                          <label>Footer Title</label>
                          <input type="text" class="form-control" id="footer_title" name="footer_title" placeholder="Enter footer title">
                      </div>
                  </div>
                  <div class="col-sm-4">
                      <div class="form-group">
                          <label>Meta Title</label>
                          <input type="text" class="form-control" id="meta_title" name="meta_title" placeholder="Enter meta title">
                      </div>
                  </div>
                  <div class="col-sm-6">
                      <div class="form-group">
                          <label>Long Title</label>
                          <textarea class="form-control summernote" id="long_title" name="long_title" placeholder="Enter long title"></textarea>
                      </div>
                  </div>
                  <div class="col-sm-6">
                      <div class="form-group">
                          <label>Review Description</label>
                          <textarea class="form-control summernote" id="short_description" name="short_description" placeholder="Enter review description"></textarea>
                      </div>
                  </div>
                  <div class="col-sm-6">
                      <div class="form-group">
                          <label>Header Description</label>
                          <textarea class="form-control summernote" id="header_description" name="header_description" placeholder="Enter header description"></textarea>
                      </div>
                  </div>
                  <div class="col-sm-6">
                      <div class="form-group">
                          <label>Meta Description</label>
                          <textarea class="form-control summernote" id="meta_description" name="meta_description" placeholder="Enter meta description"></textarea>
                      </div>
                  </div>
                  <div class="col-sm-12">
                      <div class="form-group">
                          <label>Long Description</label>
                          <textarea class="form-control summernote" id="long_description" name="long_description" placeholder="Enter long description"></textarea>
                      </div>
                  </div>
                  <div class="col-sm-4">
                      <div class="form-group">
                          <label>Image</label>
                          <input type="file" id="image" name="image" class="form-control" onchange="previewImage(event)" accept="image/*">
                      </div>
                      <img id="image_preview" src="#" alt="Meta Image Preview" class="pt-3" style="max-width: 150px; height: auto; display: none;"/>
                  </div>
                  <div class="col-sm-4">
                      <div class="form-group">
                          <label>Details Image</label>
                          <input type="file" id="details_image" name="details_image" class="form-control" onchange="previewDetailsImage(event)" accept="image/*">
                      </div>
                      <img id="details_image_preview" src="#" alt="Details Image Preview" class="pt-3" style="max-width: 150px; height: auto; display: none;"/>
                  </div>
                  <div class="col-sm-4">
                      <div class="form-group">
                          <label>Meta Image</label>
                          <input type="file" id="meta_image" name="meta_image" class="form-control" onchange="previewMetaImage(event)" accept="image/*">
                      </div>
                      <img id="meta_image_preview" src="#" alt="Meta Image Preview" class="pt-3" style="max-width: 150px; height: auto; display: none;"/>
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
                  <th style="text-align: center">Long Title</th>
                  <th style="text-align: center">Image</th>
                  <th style="text-align: center">Action</th>
                </tr>
                </thead>
                <tbody>
                  @foreach ($data as $key => $data)
                  <tr>
                    <td style="text-align: center">{{ $key + 1 }}</td>
                    <td style="text-align: center">{{$data->short_title}}</td>
                    <td style="text-align: center">{!!$data->long_title!!}</td>
                    <td style="text-align: center"><img src="{{ asset('/' . $data->image) }}" width="100" height="100"></td>
                    <td style="text-align: center">
                      <a class="btn btn-link" id="viewBtn" 
                          rid="{{$data->id}}"
                          data-long-description="{{ $data->long_description }}" 
                          data-details-image="{{ $data->details_image }}">
                          <i class="fas fa-eye" style="font-size: 20px;"></i>
                        </a>
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

<!-- Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalLabel">Business Value Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                  <div class="row py-2 col-lg-12 mx-auto py-3">
                      <div class="col-lg-6">
                          <p class="txt-primary" id="long-description1"></p>
                      </div>
                      <div class="col-lg-6 text-center">
                          <img src="" class="img-fluid" alt="" id="details-image">
                      </div>
                  </div>
            </div>
        </div>
    </div>
</div>

@endsection
@section('script')

<script>
    $(function () {
      $("#example1").DataTable();
    });
</script>

<script>
  $(document).ready(function() {
      $(document).on('click', '#viewBtn', function() {
          var longDescription = $(this).data('long-description');
          var detailsImage = $(this).data('details-image');
          
          $("#long-description1").html(longDescription);
          
          if (detailsImage) {
              $("#details-image").attr("src", "/" + detailsImage).show();
          } else {
              $("#details-image").attr("src", "").hide();
          }

          $('#viewModal').modal('show');
      });
  });
</script>

<script>
    function previewImage(event) {
        var output = document.getElementById('image_preview');
        output.src = URL.createObjectURL(event.target.files[0]);
        output.style.display = 'block';
    }

    function previewDetailsImage(event) {
      var output = document.getElementById('details_image_preview');
      output.src = URL.createObjectURL(event.target.files[0]);
      output.style.display = 'block';
    }

    function previewMetaImage(event) {
      var output = document.getElementById('meta_image_preview');
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
              form_data.append("footer_title", $("#footer_title").val());
              form_data.append("meta_title", $("#meta_title").val());
              form_data.append("long_title", $("#long_title").summernote('code'));
              form_data.append("short_description", $("#short_description").summernote('code'));
              form_data.append("header_description", $("#header_description").summernote('code'));
              form_data.append("meta_description", $("#meta_description").summernote('code'));
              form_data.append("long_description", $("#long_description").summernote('code'));
              form_data.append("image", $("#image")[0].files[0]);
              form_data.append("details_image", $("#details_image")[0].files[0]);
              form_data.append("meta_image", $("#meta_image")[0].files[0]);
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
                    //  console.error(xhr.responseText);
                }
            });
          }
          //create  end
          //Update
          if($(this).val() == 'Update'){
              var form_data = new FormData();
              form_data.append("short_title", $("#short_title").val());
              form_data.append("footer_title", $("#footer_title").val());
              form_data.append("meta_title", $("#meta_title").val());
              form_data.append("long_title", $("#long_title").summernote('code'));
              form_data.append("short_description", $("#short_description").summernote('code'));
              form_data.append("header_description", $("#header_description").summernote('code'));
              form_data.append("meta_description", $("#meta_description").summernote('code'));
              form_data.append("long_description", $("#long_description").summernote('code'));
              form_data.append("image", $("#image")[0].files[0]);
              form_data.append("details_image", $("#details_image")[0].files[0]);
              form_data.append("meta_image", $("#meta_image")[0].files[0]);
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
        $("#long_title").summernote('code', data.long_title);
        $("#short_description").summernote('code', data.short_description);
        $("#header_description").summernote('code', data.header_description);
        $("#long_description").summernote('code', data.long_description);
        $("#footer_title").val(data.footer_title);
        $("#meta_title").val(data.meta_title);
        $("#meta_description").summernote('code', data.meta_description);

        if (data.image) {
            $("#image_preview").attr("src", "/" + data.image).show();
        } else {
            $("#image_preview").attr("src", "").hide();
        }

        if (data.details_image) {
            $("#details_image_preview").attr("src", "/" + data.details_image).show();
        } else {
            $("#details_image_preview").attr("src", "").hide();
        }

        if (data.meta_image) {
            $("#meta_image_preview").attr("src", "/" + data.meta_image).show();
        } else {
            $("#meta_image_preview").attr("src", "").hide();
        }
        $("#codeid").val(data.id);
        $("#addBtn").val('Update');
        $("#addBtn").html('Update');
        $("#addThisFormContainer").show(300);
        $("#newBtn").hide(100);
      }
      function clearform() {
        $('#createThisForm')[0].reset();
        $("#addBtn").val('Create');
        $("#long_description").summernote('code', '');
        $('#image_preview').attr('src', '#').hide();
        $('#details_image_preview').attr('src', '#').hide();
        $('#meta_image_preview').attr('src', '#').hide();
        $('.summernote').each(function() {
            $(this).summernote('code', '');
        });
      }
  });
</script>
@endsection