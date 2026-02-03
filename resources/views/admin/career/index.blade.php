@extends('admin.layouts.admin')

@section('content')

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
            <div class="table-responsive">
              <table id="example1" class="table table-striped">
                <thead>
                <tr>
                  <th style="text-align: center">Sl</th>
                  <th style="text-align: center">Date</th>
                  <th style="text-align: center">Name</th>
                  <th style="text-align: center">Contact</th>
                  <th style="text-align: center">LinkedIn</th>
                  <th style="text-align: center">CV</th>
                  <th style="text-align: center">Action</th>
                </tr>
                </thead>
                <tbody>
                  @foreach ($data as $key => $data)
                  <tr>
                    <td style="text-align: center">{{ $key + 1 }}</td>
                    <td style="text-align: center">{{ date('d-m-Y', strtotime($data->created_at)) }}</td>
                    <td style="text-align: center">{{$data->name}}</td>
                    <td style="text-align: center">{{$data->email}}, {{$data->phone}}</td>
                    <td style="text-align: center">{{$data->linkedin_profile}}</td>
                    <td style="text-align: center">
                        <a href="{{ asset('images/Cv/' . $data->cv) }}" target="_blank">
                            <i class="fas fa-file-pdf" style="font-size: 24px;"></i>
                        </a>
                    </td>
                    <td style="text-align: center">
                      <a class="btn btn-link" id="deleteBtn" rid="{{$data->id}}"><i class="fas fa-trash" style="color: red; font-size: 20px;"></i></a>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
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
      $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
      var url = "{{URL::to('/admin/career-list')}}";
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
                  if(d.status == 300) {
                      alert(d.message);
                  } else if(d.status == 404) {
                      alert(d.message);
                  } else if(d.status == 303) {
                      alert(d.message);
                  }
                  location.reload();
              },
              error:function(d){
                  console.log(d);
              }
          });
      });
  });
</script>

@endsection