@extends('admin.layouts.admin')

@section('content')



<section class="content mt-3" id="newBtnSection">
    <div class="container-fluid">
      <div class="card-tools">
        <a href="{{ route('allPackage') }}" class="btn btn-sm bg-theme text-light btn-outline-dark">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
      <div class="row">
        <div class="col-2">
            <button type="button" class="btn btn-secondary my-3" id="newBtn">Add new</button>
        </div>
      </div>
    </div>
</section>

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
              <input type="hidden" class="form-control" id="package_id" name="package_id" value="{{ $id }}">


              <div class="row">
                <div class="col-sm-6">
                  <div class="form-group">
                    <label>Price Range</label>
                    <input type="text" class="form-control" id="price_range" name="price_range" placeholder="Enter price range"> 
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="form-group">
                    <label>Title</label>
                    <input type="text" class="form-control" id="title" name="title" placeholder="Enter title">
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
                    <label>Vat</label>
                    <input type="number" class="form-control" id="vat" name="vat" placeholder="Enter vat">
                  </div>
                </div>
                <div class="col-sm-12">
                  <div class="form-group">
                    <label>Note</label>
                    <input type="text" class="form-control" id="note" name="note" placeholder="Enter note">
                  </div>
                </div>
                <div class="col-sm-12">
                    <div class="form-group">
                        <label>Features</label>
                        <div class="feature-container">
                            @foreach($features as $feature)
                                <div class="form-check feature-item">
                                    <input class="form-check-input" type="checkbox" name="features[]" value="{{$feature->id}}" id="feature_{{$feature->id}}">
                                    <label class="form-check-label" for="feature_{{$feature->id}}">{{$feature->name}}</label>
                                </div>
                            @endforeach
                        </div>
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

<section class="content" id="contentContainer">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <!-- /.card -->
          <div class="card card-secondary">
            <div class="card-header">
              <h3 class="card-title">Turnovers of {{ $packageName }}</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
              <table id="example1" class="table cell-border table-striped">
                <thead>
                <tr>
                  <th style="text-align: center">Sl</th>
                  <th style="text-align: center">Price Range</th>
                  <th style="text-align: center">Price</th>
                  <th style="text-align: center">Action</th>
                </tr>
                </thead>
                <tbody>
                  @foreach ($turnovers as $key => $data)
                  <tr>
                    <td style="text-align: center">{{ $key + 1 }}</td>
                    <td style="text-align: center">{{$data->price_range}}</td>
                    <td style="text-align: center">{{$data->price}}</td>
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
      var url = "{{URL::to('/admin/package-turnover')}}";
      var upurl = "{{URL::to('/admin/package-turnover-update')}}";
      // console.log(url);
      $("#addBtn").click(function(){
      //   alert("#addBtn");
          if($(this).val() == 'Create') {
              var form_data = new FormData();
              form_data.append("price_range", $("#price_range").val());
              form_data.append("package_id", $("#package_id").val());
              form_data.append("title", $("#title").val());
              form_data.append("price", $("#price").val());
              form_data.append("note", $("#note").val());
              form_data.append("vat", $("#vat").val());
              var features = [];
                $('input[name="features[]"]:checked').each(function() {
                    features.push($(this).val());
                });
               form_data.append("features", features);
            //   for (let [key, value] of form_data.entries()) {
            //       console.log(key, value);
            //   }
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
              form_data.append("price_range", $("#price_range").val());
              form_data.append("package_id", $("#package_id").val());
              form_data.append("title", $("#title").val());
              form_data.append("price", $("#price").val());
              form_data.append("note", $("#note").val());
              form_data.append("vat", $("#vat").val());
              var features = [];
                $('input[name="features[]"]:checked').each(function() {
                    features.push($(this).val());
                });
              form_data.append("features", features);
              form_data.append("codeid", $("#codeid").val());

              for (let [key, value] of form_data.entries()) {
                  console.log(key, value);
              }
              
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
          // alert("btn work");
          codeid = $(this).attr('rid');
          // console.log(codeid);
          info_url = url + '/'+codeid+'/edit';
          console.log(info_url);
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
        $("#price_range").val(data.price_range);
        $("#title").val(data.title);
        $("#price").val(data.price);
        $("#vat").val(data.vat);
        $("#note").val(data.note);

        if (data.features && data.features !== '') {
            var featuresArray = JSON.parse(data.features);
            if (featuresArray !== null) {
                $('input[name="features[]"]').each(function(){
                    if(featuresArray.includes($(this).val())){
                        $(this).prop('checked', true);
                    }
                });
            }
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
      }
  });
</script>
@endsection