@extends('admin.layouts.admin')

@section('content')

<section class="section">
    <div class="container-fluid">
      
        <div class="row px-3">
          <div class="col-lg-12 pb-3 d-flex justify-content-end">
              <a href="{{ route('report.create') }}" class="btn btn-sm bg-theme text-light btn-outline-dark">+ New Report</a>
          </div>
        </div>

        <div class="row">
            <div class="col-lg-12 px-0 border shadow-sm">
                <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
                    <i class='bx bxs-user fs-4 me-2'></i> Report List
                </p>

                <div class="px-3 mt-3">
                      <table class="table table-striped table-bordered">
                          <thead>
                              <tr>
                                  <th>Report Name</th>
                                  <th>Report Base</th>
                                  <th>Base Name</th>
                                  <th>Created Date</th>
                                  <th>Created By</th>
                              </tr>
                          </thead>
                          <tbody>
                          </tbody>
                      </table>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('script')

<script>
    $(".table").DataTable();
</script>

@endsection