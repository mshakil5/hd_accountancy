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
      <div class="col-md-8">
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
                <div class="col-sm-6">
                  <div class="form-group">
                    <label>Name</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter name">
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="form-group">
                    <label>Price</label>
                    <input type="number" class="form-control" id="price" name="price" placeholder="Enter price"> 
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="form-group">
                    <label>Short Title</label>
                    <input type="text" class="form-control" id="short_title" name="short_title" placeholder="Enter short title">
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="form-group">
                    <label>Long Title</label>
                    <input type="text" class="form-control" id="long_title" name="long_title" placeholder="Enter long title">
                  </div>
                </div>
                <div class="col-sm-12">
                  <div class="form-group">
                    <label>Short Description</label>
                    <input type="text" class="form-control" id="short_description" name="short_description" placeholder="Enter short description">
                  </div>
                </div>
                <div class="col-sm-12">
                  <div class="form-group">
                        <label>Long Description</label>
                        <textarea class="form-control summernote" id="long_description" name="long_description" placeholder="Enter long description"></textarea>
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
                  <th style="text-align: center">Name</th>
                  <th style="text-align: center">Price</th>
                  <th style="text-align: center">Turnovers</th>
                  <th style="text-align: center">Action</th>
                </tr>
                </thead>
                <tbody>
                  @foreach ($data as $key => $data)
                  <tr>
                    <td style="text-align: center">{{ $key + 1 }}</td>
                    <td style="text-align: center">{{$data->name}}</td>
                    <td style="text-align: center">{{ number_format($data->price, 2) }}</td>
                    <td style="text-align: center">
                        <a href="{{ route('package-turnover', ['id' => $data->id]) }}" class="btn btn-sm bg-theme text-light btn-outline-dark">Manage Turnovers</a>
                    </td>
                    <td style="text-align: center">                  
                        <a class="btn btn-link" id="viewBtn" rid="{{$data->id}}"><i class="fas fa-eye" style="font-size: 20px;"></i></a>
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

<!-- Modal for viewing data -->
<div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalLabel">View Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-6">
                        <label>Name:</label>
                        <p id="view_name"></p>
                    </div>
                    <div class="col-sm-6">
                        <label>Price:</label>
                        <p id="view_price"></p>
                    </div>
                    <div class="col-sm-6">
                        <label>Short Title:</label>
                        <p id="view_short_title"></p>
                    </div>
                    <div class="col-sm-6">
                        <label>Long Title:</label>
                        <p id="view_long_title"></p>
                    </div>
                    <div class="col-sm-12">
                        <label>Short Description:</label>
                        <p id="view_short_description"></p>
                    </div>
                    <div class="col-sm-12">
                        <label>Long Description:</label>
                        <p id="view_long_description"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
  .feature-container {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
  }

  .feature-item {
      width: 45%;
      margin: 10px;
  }
</style>

@endsection
@section('script')

<script>
    $(function () {
      $("#example1").DataTable();
    });
</script>

<script>
    $(document).ready(function() {
        $('.summernote').summernote({
            height: 200, 
        });
    });
</script>

<script>
  $(document).ready(function () {

        $("#contentContainer").on('click', '#turnOverBtn', function(){
            var packageId = $(this).attr('rid');
            $("#package_id").val(packageId);
            var package_url = "{{ URL::to('/admin/package-turnover') }}";
            
            $.get(package_url, { package_id: packageId }, function(data){
                var turnOverList = '';
                
                if (data.turnovers && Array.isArray(data.turnovers)) {
                    data.turnovers.forEach(function(turnOver){
                        turnOverList += '<div class="turnover-item mb-2">' +
                            '<p><strong>Price Range:</strong> ' + turnOver.price_range + '</p>' +
                            '</div>';
                    });
                } else {
                    turnOverList = '<p>No turnovers found.</p>';
                }
                
                $('#turnOverList').html(turnOverList);
                $('#turnOverModal').modal('show');
            }).fail(function() {
                swal({
                    title: "Error!",
                    text: "Failed to load turnovers.",
                    icon: "error",
                    button: "OK",
                });
            });
        });

        $("#addTurnOverForm").submit(function(e){
            e.preventDefault();

            // Get form data
            var formData = new FormData(this);
            var packageId = $("#package_id").val();
            var package_url = "{{ route('package.turnover.store') }}";
            var priceRange = $("#price_range").val().trim();

            // Check if price range is empty
            if (!priceRange) {
                swal({
                    title: "Error!",
                    text: "Please enter a price range.",
                    icon: "error",
                    button: "OK",
                });
                return; // Stop form submission
            }

            // Append package_id to formData
            formData.append('package_id', packageId);

            // Perform AJAX request
            $.ajax({
                url: package_url,
                type: "POST",
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    if (response.status === 'success') {
                        $('#turnOverModal').modal('hide');
                        $('#addTurnOverForm')[0].reset();
                        swal({
                            title: "Success!",
                            text: "Turnover added successfully",
                            icon: "success",
                            button: "OK",
                        });
                        // Optionally reload the list of turnovers
                    } else {
                        swal({
                            title: "Error!",
                            text: response.message || "An error occurred while adding the turnover.",
                            icon: "error",
                            button: "OK",
                        });
                        console.error('Error:', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    swal({
                        title: "Error!",
                        text: "An error occurred while processing your request.",
                        icon: "error",
                        button: "OK",
                    });
                    console.error(xhr.responseText);
                }
            });
        });

        $("#contentContainer").on('click', '#viewBtn', function(){
            var codeid = $(this).attr('rid');
            var info_url = "{{URL::to('/admin/package')}}" + '/' + codeid + '/edit'; 

            $.get(info_url, {}, function(data){
              // console.log(data);
                $('#view_name').text(data.name);
                $('#view_price').text(data.price);
                $('#view_short_title').text(data.short_title);
                $('#view_long_title').text(data.long_title);
                $('#view_short_description').text(data.short_description);
                $('#view_long_description').html(decodeURIComponent(data.long_description));

                // $('#view_features').empty();
                // if (data.features && data.features !== '') {
                //     var featuresArray = data.features.replace(/\"/g, '').split(',');
                //     if (featuresArray !== null) {
                //         featuresArray.forEach(function(feature){
                //             $('#view_features').append('<li>' + feature.trim() + '</li>');
                //         });
                //     }
                // }

                $('#viewModal').modal('show');
            });
        });

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
      var url = "{{URL::to('/admin/package')}}";
      var upurl = "{{URL::to('/admin/package-update')}}";
      // console.log(url);
      $("#addBtn").click(function(){
      //   alert("#addBtn");
          if($(this).val() == 'Create') {
              var form_data = new FormData();
              form_data.append("name", $("#name").val());
              form_data.append("price", $("#price").val());
              form_data.append("short_title", $("#short_title").val());
              form_data.append("long_title", $("#long_title").val());
              form_data.append("short_description", $("#short_description").val());
              form_data.append("long_description", $('#long_description').summernote('code'));

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
              form_data.append("name", $("#name").val());
              form_data.append("price", $("#price").val());
              form_data.append("short_title", $("#short_title").val());
              form_data.append("long_title", $("#long_title").val());
              form_data.append("short_description", $("#short_description").val());
              form_data.append("long_description", $('#long_description').summernote('code'));
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
                      document.querySelector('.loader').style.display = 'none'; 
                      document.getElementById('addBtn').disabled = false;
                    //  console.error(xhr.responseText);
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
        // console.log(data);
        $("#name").val(data.name);
        $("#short_title").val(data.short_title);
        $("#price").val(data.price);
        $("#long_title").val(data.long_title);
        $("#short_description").val(data.short_description);
        $("#long_description").summernote('code', data.long_description);

        // if (data.features && data.features !== '') {
        //     var featuresArray = JSON.parse(data.features);
        //     if (featuresArray !== null) {
        //         $('input[name="features[]"]').each(function(){
        //             if(featuresArray.includes($(this).val())){
        //                 $(this).prop('checked', true);
        //             }
        //         });
        //     }
        // }

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
      }
  });
</script>
@endsection