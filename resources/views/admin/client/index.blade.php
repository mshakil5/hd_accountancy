@extends('admin.layouts.admin')

@section('content')

<section class="section dashboard">
    <div class="row">
        <div class="col-lg-12 px-0 border shadow-sm">
            <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
                <i class="bx bxs-user-plus fs-4 me-2"></i> All Clients
            </p>
            <div class="row px-3">
                <div class="col-lg-4 p-3">
                    <input type="search" name="" placeholder="Search..." class="form-control" id="">
                </div>
                <div class="col-lg-4 p-3">
                    <input type="search" name="" placeholder="Sort By" class="form-control" id="">
                </div>
                <div class="col-lg-4 p-3 text-end">
                    <a href="{{ route('createClient') }}" class="btn btn-sm bg-theme text-light btn-outline-dark">+ New Client</a>
                </div>
            </div>
                <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                    <table class="table cell-border table-bordered table-striped" id="clientsTable">
                        <thead>
                            <tr>
                                <th scope="col">sl</th>
                                <th scope="col">Reference</th>
                                <th scope="col">Name</th>
                                <!-- <th scope="col">Type</th> -->
                                <th scope="col">Manager</th>
                                <th scope="col">Phone</th>
                                <th scope="col">Email</th>
                                <th scope="col">Status</th>
                                <!-- <th scope="col">Address</th> -->
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables  -->
                        </tbody>
                    </table>
                </div>
        </div>
    </div>
</section>

@endsection

@section('script')

<script>
  $(document).ready(function() {
    var table = $('#clientsTable').DataTable({
        serverSide: true,
        ajax: "{{ route('get.Clients') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'refid', name: 'refid'},
            {data: 'name', name: 'name'},
            {data: 'manager.first_name', name: 'manager.first_name'},
            {data: 'phone', name: 'phone'},
            {data: 'email', name: 'email'},
            {
                data: 'status',
                name: 'status',
                render: function(data, type, full, meta) {
                    var statusClass = data ? 'btn btn-secondary' : 'btn btn-danger';
                    var statusText = data ? 'Active' : 'Inactive';
                    return '<button class="' + statusClass + '" onclick="changeStatus(' + full.id + ')">' + statusText + '</button>';
                }
            },
        ]
    });
  });
</script>

<script>
    function changeStatus(clientId) {
        $.ajax({
            url: "{{ route('client.change.status') }}",
            method: "POST",
            data: {
                client_id: clientId,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    $('#clientsTable').DataTable().ajax.reload();
                    Toastify({
                        text: "Status changed successfully!"
                    }).showToast();
                } else {
                    Toastify({
                        text: "Failed to change status."
                    }).showToast();
                }
            }
        });
    }
</script>

@endsection