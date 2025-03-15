@extends('admin.layouts.admin')

@section('content')

<!-- Main content -->
<section class="content mt-3" id="contentContainer">
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
              <table id="example1" class="table cell-border table-striped">
                <thead>
                <tr>
                  <th style="text-align: center">Sl</th>
                  <th style="text-align: center">Name</th>
                  <th style="text-align: center">Business Name</th>
                  <th style="text-align: center">Email</th>
                  <th style="text-align: center">Phone</th>
                  <th style="text-align: center">Yearly Turnover</th>
                  <th style="text-align: center">Interested Service</th>
                  <th style="text-align: center">Message</th>
                  <th style="text-align: center">Action</th>
                </tr>
                </thead>
                <tbody>
                  @foreach ($data as $key => $data)
                  <tr>
                    <td style="text-align: center">{{ $key + 1 }}</td>
                    <td style="text-align: center">{{$data->name}}</td>
                    <td style="text-align: center">{{$data->business_name}}</td>
                    <td style="text-align: center">{{$data->email}}</td>
                    <td style="text-align: center">{{$data->phone}}</td>
                    <td style="text-align: center">{{$data->yearly_turnover}}</td>
                    <td style="text-align: center">
                      @php
                        $services = json_decode($data->interested_service);
                        $serviceList = implode(', ', $services);
                        echo $serviceList;
                    @endphp</td>
                    <td style="text-align: center">{!!$data->message!!}</td>
                    <td style="text-align: center">
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
      $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
      var url = "{{URL::to('/admin/contact-messages')}}";
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