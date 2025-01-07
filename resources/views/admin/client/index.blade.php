@extends('admin.layouts.admin')

@section('content')

<section class="section dashboard">
    <div class="row">
        <div class="col-lg-12 px-0 shadow-sm">
            <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
                <i class="bx bxs-user-plus fs-4 me-2"></i> All Clients
            </p>
            <div class="row px-3">
                <div class="col-lg-12 p-3 d-flex justify-content-end">

                    @if (in_array('7', json_decode(Auth::user()->role->permission)))

                    <a href="{{ route('createClient') }}" class="btn btn-sm bg-theme text-light btn-outline-dark">+ New Client</a>

                    @endif

                </div>
            </div>
            <div class="table-wrapper my-2 mx-auto" style="width: 95%;">
                <table class="table cell-border table-bordered table-striped" id="clientsTable">
                    <thead>
                        <tr>
                            <th scope="col">sl</th>
                            <th scope="col">Reference</th>
                            <th scope="col">Name</th>
                            <th scope="col">Manager</th>
                            <th scope="col">Phone</th>
                            <th scope="col">Email</th>
                            <th scope="col">Status</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- DataTables -->
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

    var canEditDelete = @json(in_array('8', json_decode(Auth::user()->role->permission)));

    var table = $('#clientsTable').DataTable({
        serverSide: true,
        processing: true,
        ajax: "{{ route('get.Clients') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'refid', name: 'refid'},
            {data: 'name', name: 'name'},
            {data: 'manager_first_name', name: 'manager_first_name'},
            {data: 'phone', name: 'phone'},
            {data: 'email', name: 'email'},
            {
                data: 'status',
                name: 'status',
                render: function(data, type, full, meta) {
                    var isActive = data == 1;
                    var statusClass = isActive ? 'btn btn-secondary' : 'btn btn-danger';
                    var statusText = isActive ? '<i class="fas fa-toggle-on"></i>' : '<i class="fas fa-toggle-off"></i>';
                    
                    var statusButton = '<button class="' + statusClass + '" onclick="changeStatus(' + full.id + ')" ' + (canEditDelete ? '' : 'disabled') + '>' + statusText + '</button>';
                    return statusButton;
                }
            },
            {
                data: 'id',
                name: 'details',
                render: function(data, type, full, meta) {
                    var editButtonHtml = canEditDelete ? '<a href="{{ url('admin/client/update-form') }}/' + data + '" class="btn btn-secondary"><i class="fas fa-edit"></i></a>' : '<button class="btn btn-secondary" disabled><i class="fas fa-edit"></i></button>';
                    var deleteButtonHtml = canEditDelete ? '<a href="#" class="btn btn-danger delete-client" data-client-id="' + data + '" style="margin-left: 10px;"><i class="fas fa-trash"></i></a>' : '<button class="btn btn-danger" disabled style="margin-left: 10px;"><i class="fas fa-trash"></i></button>';

                    return editButtonHtml + deleteButtonHtml;
                }
            }
        ]
    });
 });
</script>
<!-- Client Data table end -->

<!-- Delete Client start -->
<script>
    $(document).ready(function() {
        $(document).on('click', '.delete-client', function(e) {
            e.preventDefault();
            var clientId = $(this).data('client-id');

            if (confirm("Are you sure you want to delete this client?")) {
                $.ajax({
                    url: '/admin/delete-client/' + clientId, 
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') 
                    },
                    success: function(response) {
                        if (response.status === 200) {
                            toastr.success(response.message);
                            $('#clientsTable').DataTable().ajax.reload();
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        toastr.error('Failed to delete client.');
                    }
                });
            }
        });
    });
</script>
<!-- Delete Client end -->

<!-- Client status change start -->
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
                    $('#clientsTable').DataTable().ajax.reload(null, false);
                    toastr.success('Status changed successfully!');
                } else {
                    toastr.error('Failed to change status!');
                }
            }
        });
    }
</script>
<!-- Client status change end -->

@endsection