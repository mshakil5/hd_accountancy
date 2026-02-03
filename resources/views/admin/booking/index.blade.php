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
                  <th style="text-align: center">Meet</th>
                  <th style="text-align: center">Time & Date</th>
                  <th style="text-align: center">Discussion</th>
                </tr>
                </thead>
                <tbody>
                  @foreach ($data as $key => $data)
                  <tr>
                    <td style="text-align: center">{{ $key + 1 }}</td>
                    <td style="text-align: center">{{ date('d-m-Y', strtotime($data->created_at)) }}</td>
                    <td style="text-align: center">{{$data->first_name}} {{$data->last_name}}</td>
                    <td style="text-align: center">{{$data->email}}, {{$data->phone}}</td>
                    <td style="text-align: center">{{$data->meet_type}}</td>
                    <td style="text-align: center">{{ date('g:i A', strtotime($data->time)) }}, {{ date('d-m-Y', strtotime($data->date)) }}</td>
                    <td style="text-align: center">{!!$data->discussion!!}</td>
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

@endsection