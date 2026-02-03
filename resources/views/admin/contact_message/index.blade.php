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
            <div class="table-responsive">
              <table id="example1" class="table table-striped">
                <thead>
                <tr>
                  <th style="text-align: center">Sl</th>
                  <th style="text-align: center">Date</th>
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
                    <td style="text-align: center">{{ date('d-m-Y', strtotime($data->created_at)) }}</td>
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
                    <td style="text-align: center">
                        @php
                            $plainMessage = strip_tags($data->message);
                        @endphp

                        @if(strlen($plainMessage) > 50)
                            {{ \Illuminate\Support\Str::limit($plainMessage, 50) }}
                            <a href="#" class="read-more" data-message="{{ e($data->message) }}">Read More</a>
                        @else
                            {!! $data->message !!}
                        @endif
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

<!-- Global Modal -->
<div class="modal fade" id="readMoreModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="modalMessageContent"></div>
    </div>
  </div>
</div>


@endsection
@section('script')
<script>
    $(function () {
      $("#example1").DataTable({
        responsive: true
      });
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

  $(document).on('click', '.read-more', function(e) {
      e.preventDefault();
      let msg = $(this).data('message');
      $('#modalMessageContent').html(msg);
      $('#readMoreModal').modal('show');
  });

</script>
@endsection