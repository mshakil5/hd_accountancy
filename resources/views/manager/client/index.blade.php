@extends('manager.layouts.manager')

@section('content')

<section class="section dashboard">
    <div class="row">
        <div class="col-lg-12 px-0 border shadow-sm">
            <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
                <i class="bx bxs-user-plus fs-4 me-2"></i> Client list
                <select id="clientFilter" class="form-select ms-auto" aria-label="Filter Clients" style="max-width: 200px;">
                    <option value="all" selected>All Clients</option>
                    <option value="assigned">Assigned Clients</option>
                </select>
            </p>
            <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                <table class="table cell-border table-bordered table-striped" id="clientsTable">
                    <thead>
                        <tr>
                            <th scope="col">sl</th>
                            <th scope="col">Reference</th>
                            <th scope="col">Name</th>
                            <th scope="col">Manager</th>
                            <th scope="col">Phone</th>
                            <th scope="col">Email</th>
                            <th scope="col">Recent Update</th>
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

<!-- Client Data table start -->
<script>
 $(document).ready(function() {
    var table = $('#clientsTable').DataTable({
        serverSide: true,
        ajax: {
            url: "{{ route('get.Clients.manager') }}",
            data: function(d) {
                d.filter = $('#clientFilter').val();
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'refid', name: 'refid'},
            {data: 'name', name: 'name'},
            {
                data: 'manager.first_name', 
                name: 'manager.first_name',
                render: function(data, type, row) {
                    return data ? data : '';
                }
            },
            {data: 'phone', name: 'phone'},
            {data: 'email', name: 'email'},
            { 
                data: 'recent_update', 
                name: 'recent_update',
                render: function(data, type, row) {
                    return '<a href="' + '{{ route("client.Recent.update.manager", ":id") }}'.replace(":id", row.id) + '" class="btn btn-sm btn-primary">Recent Update</a>';
                }
            },
        ]
    });

    $('#clientFilter').on('change', function() {
        table.ajax.reload();
    });
 });
</script>
<!-- Client Data table end -->

@endsection