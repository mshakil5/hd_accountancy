@extends('admin.layouts.admin')

@section('content')

<!-- Main content -->

<section class="content" id="contentContainer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <!-- /.card -->

                <div class="card card-secondary">
                    <div class="card-header">
                        <div class="col d-flex justify-content-between">
                            <div>
                                <h3 class="card-title">All Attendence Records</h3>
                            </div>
                        </div>
                    </div>


                    <!-- /.card-header -->
                    <div class="card-body">
                        <table id="example1" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th style="text-align: center">Sl</th>
                                    <th style="text-align: center">Name</th>
                                    <th style="text-align: center">Login Time</th>
                                    <th style="text-align: center">Logout Time</th>
                                    <th style="text-align: center">Duration</th>
                                    <th style="text-align: center">Note</th>
                                    <th style="text-align: center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($previouslyLoggedStaff as $key => $staff)
                                <tr>
                                    <td style="text-align: center">{{ $key + 1 }}</td>
                                    <td style="text-align: center">{{ $staff->user->first_name }} {{ $staff->user->last_name }}</td>
                                    <td style="text-align: center">
                                        <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($staff->start_time)->format('d-m-Y H:i:s') }}" id="start_time_{{ $staff->id }}" disabled>
                                    </td>
                                    <td style="text-align: center">
                                        <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($staff->end_time)->format('d-m-Y H:i:s') }}" id="end_time_{{ $staff->id }}" disabled>
                                    </td>
                                    <td style="text-align: center">
                                        <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($staff->start_time)->diffInHours($staff->end_time) }} hours {{ \Carbon\Carbon::parse($staff->start_time)->diffInMinutes($staff->end_time) % 60 }} minutes" id="duration_{{ $staff->id }}" disabled>
                                    </td>
                                    <td style="text-align: center">
                                        <input type="text" class="form-control" value="{{ $staff->note }}" id="note_{{ $staff->id }}" disabled>
                                    </td>
                                    <td style="text-align: center">
                                        <a class="btn btn-link edit-btn" rid="{{ $staff->id }}"><i class="fa fa-edit" style="font-size: 20px;"></i></a>
                                        <div class="action-buttons">
                                          <button class="btn btn-success update-btn" style="display: none;" rid="{{ $staff->id }}">
                                              <i class="fas fa-check" style="font-size: 11px;"></i>
                                          </button>
                                          <button class="btn btn-secondary cancel-btn" style="display: none;" rid="{{ $staff->id }}">
                                              <i class="fas fa-times" style="font-size: 14px;"></i>
                                          </button>
                                      </div>
                                    </td>

                                </tr>
                                @endforeach

                            </tbody>
                        </table>
                        <div class="row">
                            <div class="col-lg-4 mx-auto text-center">
                                <a href="{{route('prevLogStaffs')}}" class="btn btn-primary rounded-2 border-theme bg-theme text-light">Back</a>
                            </div>
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
      $("#example1").DataTable({
      });
    });
</script>

<script>
  $(document).ready(function() {
      $('.table tbody tr').each(function() {
          var rowId = $(this).find('.edit-btn').attr('rid');
          $(this).find('input').each(function() {
              $(this).data('orig', $(this).val());
          });
      });

      $('.edit-btn').click(function() {
          var rowId = $(this).attr('rid');
          $('#start_time_' + rowId).prop('disabled', false);
          $('#end_time_' + rowId).prop('disabled', false);
          $('#duration_' + rowId).prop('disabled', false);
          $('#note_' + rowId).prop('disabled', false);

          $(this).hide();
          $('.update-btn[rid="' + rowId + '"]').show();
          $('.cancel-btn[rid="' + rowId + '"]').show();
      });

      $('.cancel-btn').click(function() {
          var rowId = $(this).attr('rid');
          $('#start_time_' + rowId).prop('disabled', true).val($('#start_time_' + rowId).data('orig'));
          $('#end_time_' + rowId).prop('disabled', true).val($('#end_time_' + rowId).data('orig'));
          $('#duration_' + rowId).prop('disabled', true).val($('#duration_' + rowId).data('orig'));
          $('#note_' + rowId).prop('disabled', true).val($('#note_' + rowId).data('orig'));

          $('.edit-btn[rid="' + rowId + '"]').show();
          $('.update-btn[rid="' + rowId + '"]').hide();
          $('.cancel-btn[rid="' + rowId + '"]').hide();
      });

      $('.update-btn').click(function() {
          var rowId = $(this).attr('rid');
          console.log(rowId);
          var startTime = $('#start_time_' + rowId).val();
          var endTime = $('#end_time_' + rowId).val();
          var note = $('#note_' + rowId).val();

          $.ajax({
              url: '/admin/prev-staffs/update/' + rowId,
              method: 'POST',
              data: {
                  _token: '{{ csrf_token() }}',
                  start_time: startTime,
                  end_time: endTime,
                  note: note
              },
              success: function(response) {
                  swal({
                    title: "Success!",
                    text: "Updated successfully",
                    icon: "success",
                    button: "OK",
                });

                setTimeout(function() {
                    location.reload();
                }, 2000);
              },
                error: function(xhr, status, error) {
                    var response = JSON.parse(xhr.responseText);
                    var errorMessage = response.message;
                    var errors = response.errors; 

                    swal({
                        title: "Error!",
                        text: errorMessage,
                        icon: "error",
                        footer: '<strong>Errors:</strong><br>' + JSON.stringify(errors),
                        button: "OK",
                    });
                }
          });
      });
  });

</script>

@endsection