@extends('manager.layouts.manager')

@section('content')

<section class="section dashboard">
        <div class="row">
        <div class="col-lg-4 mb-3">
            <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100 position-relative">
                <div class="card-body px-0">
                    <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                        Your Notes
                    </div>
                    <div class="mh250">
                        <!-- Your notes content here -->
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8 mb-3">
            <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100 position-relative">
                <div class="card-body px-0">
                    <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                        Your Assigned Tasks
                    </div>
                <!-- Works assigned to a user and specified staff -->
                        <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                        <table id="serviceManagerTable" class="table cell-border table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th scope="col">Client Name</th>
                                    <th scope="col">Service Name</th>
                                    <th scope="col">Deadline</th>
                                    <th scope="col">Frequency</th>
                                </tr>
                            </thead>
                        </table>
                        </div>
                <!-- Works assigned to a user and specified staff -->
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('script')

<script>
  $(document).ready(function() {

    $('#serviceManagerTable').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: '/manager/get-all-services',
        type: 'GET',
        //     success: function(data) {
        //     console.log(data);
        // },
        dataSrc: 'data',
        error: function(xhr, error, thrown) {
          console.error('DataTables error:', error, thrown);
        }
      },
      columns: [
        { data: 'clientname', name: 'clientname' },
        { data: 'servicename', name: 'servicename' },
        { 
          data: 'service_deadline', 
          name: 'service_deadline',
          render: function(data, type, row) {
            return moment(data).format('DD.MM.YY');
          }
        },
        { data: 'service_frequency', name: 'service_frequency' },
      ]
    });
  });
</script>

@endsection