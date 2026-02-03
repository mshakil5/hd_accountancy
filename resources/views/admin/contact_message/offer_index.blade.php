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
              <h3 class="card-title">Offer Contact Messages</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
            <div class="table-responsive">
              <table id="example1" class="table table-striped">
                <thead>
                <tr>
                  <th style="text-align: center">Date</th>
                  <th style="text-align: center">Name</th>
                  <th style="text-align: center">Company</th>
                  <th style="text-align: center">Email</th>
                  <th style="text-align: center">Phone</th>
                  <th style="text-align: center">Submission Type</th>
                  <th style="text-align: center">Message</th>
                  <th style="text-align: center">Action</th>
                </tr>
                </thead>
                <tbody>
                  @foreach ($data as $key => $item)
                  <tr>
                    <td style="text-align: center">{{ date('d-m-Y', strtotime($item->created_at)) }}</td>
                    <td style="text-align: center">{{$item->name}}</td>
                    <td style="text-align: center">{{$item->company ?? ''}}</td>
                    <td style="text-align: center">{{$item->email}}</td>
                    <td style="text-align: center">{{$item->phone}}</td>
                    <td style="text-align: center">
                        <span class="badge bg-{{ 
                            $item->submission_type == 'ltd_offer' 
                                ? 'success' 
                                : 'info' 
                        }}">
                            {{ ucfirst(str_replace('_', ' ', $item->submission_type)) }}
                        </span>
                    </td>
                    <td style="text-align: center">
                        @php
                            $plainMessage = strip_tags($item->message);
                        @endphp

                        @if(strlen($plainMessage) > 50)
                            {{ \Illuminate\Support\Str::limit($plainMessage, 50) }}
                            <a href="#" class="read-more" data-message="{{ e($item->message) }}">Read More</a>
                        @else
                            {!! $item->message !!}
                        @endif
                    </td>
                    <td style="text-align: center">
                      <a class="btn btn-link" id="deleteBtn" rid="{{$item->id}}">
                          <i class="fas fa-trash" style="color: red; font-size: 20px;"></i>
                      </a>
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
        <h5 class="modal-title">Full Message</h5>
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
        responsive: true,
        order: [[0, 'desc']]
      });
    });
</script>

<script>
  $(document).ready(function () {
      $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
      var url = "{{URL::to('/admin/contact-messages')}}";
      
      $("#contentContainer").on('click','#deleteBtn', function(){
          if(!confirm('Are you sure you want to delete this contact message?')) return;
          codeid = $(this).attr('rid');
          info_url = url + '/'+codeid;
          $.ajax({
              url:info_url,
              method: "GET",
              type: "DELETE",
              success: function(d){
                  if(d.status == 200) {
                      alert(d.message);
                  } else if(d.status == 404) {
                      alert(d.message);
                  } else if(d.status == 500) {
                      alert(d.message);
                  }
                  location.reload();
              },
              error:function(d){
                  console.log(d);
                  alert('Error deleting contact message.');
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