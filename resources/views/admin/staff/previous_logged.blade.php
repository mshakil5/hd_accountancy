@extends('admin.layouts.admin')

@section('content')

<!-- Main content -->
<!-- <div class="col-md-10 mt-2">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Previously logged-in staffs (within the last 12 hours)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Login Time</th>
                            <th>Logout Time</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($previouslyLoggedStaff as $staff)
                        <tr>
                            <td>{{ $staff->user->first_name }} {{ $staff->user->last_name }}</td>
                            <td>{{ \Carbon\Carbon::parse($staff->start_time)->format('H:i . d/m/Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($staff->end_time)->format('H:i . d/m/Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($staff->start_time)->diffInHours($staff->end_time) }} hours {{ \Carbon\Carbon::parse($staff->start_time)->diffInMinutes($staff->end_time) % 60 }} minutes</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div> -->

<section class="content" id="contentContainer">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <!-- /.card -->

          <div class="card card-secondary">
            <div class="card-header">
              <h3 class="card-title">Previously logged-in staffs (within the last 12 hours)</h3>
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
                </tr>
                </thead>
                <tbody>
                  @foreach ($previouslyLoggedStaff as $key => $staff)
                  <tr>
                    <td style="text-align: center">{{ $key + 1 }}</td>
                    <td style="text-align: center">{{ $staff->user->first_name }} {{ $staff->user->last_name }}</td>
                    <td style="text-align: center">{{ \Carbon\Carbon::parse($staff->start_time)->format('H:i . d/m/Y') }}</td>
                    <td style="text-align: center">{{ \Carbon\Carbon::parse($staff->end_time)->format('H:i . d/m/Y') }}</td>
                    <td style="text-align: center">{{ \Carbon\Carbon::parse($staff->start_time)->diffInHours($staff->end_time) }} hours {{ \Carbon\Carbon::parse($staff->start_time)->diffInMinutes($staff->end_time) % 60 }} minutes</td>

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
      $("#example1").DataTable({
      });
    });
</script>

@endsection