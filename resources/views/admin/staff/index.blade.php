@extends('admin.layouts.admin')

@section('content')

<!-- Main content -->
<section class="section dashboard">
    <div class="row">
        <div class="col-lg-12 px-0 border shadow-sm">
            <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
                <i class="bx bxs-user-plus fs-4 me-2"></i> All Staffs
            </p>
            <div class="row px-3">
                <div class="col-lg-4 p-3">
                    <input type="search" name="" placeholder="Search..." class="form-control" id="">
                </div>
                <div class="col-lg-4 p-3">
                    <input type="search" name="" placeholder="Sort By" class="form-control" id="">
                </div>
                <div class="col-lg-4 p-3 text-end">
                    <a href="{{ route('createStuff') }}" class="btn btn-sm bg-theme text-light btn-outline-dark">+ New Staff</a>
                </div>
            </div>
            <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                <table class="table cell-border table-bordered table-striped" id="staffsTable">
                    <thead>
                        <tr>
                            <th scope="col">Sl</th>
                            <th scope="col">Name</th>
                            <th scope="col">Phone</th>
                            <th scope="col">Email</th>
                            <th scope="col">Image</th>
                            <th scope="col">Status</th> 
                            <th scope="col">Details</th> 
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

<script>
  $(document).ready(function() {
    var table = $('#staffsTable').DataTable({
        serverSide: true,
        ajax: "{{ route('get.Stuffs') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {
                data: null,
                name: 'name',
                render: function(data, type, full, meta) {
                    return full.first_name + ' ' + full.last_name;
                }
            },
            {data: 'phone', name: 'phone'},
            {data: 'email', name: 'email'},
            {
                data: 'image',
                name: 'image',
                render: function(data, type, full, meta) {
                    if (data) {
                        var imageUrl = "{{ asset('images/staff') }}" + '/' + data;
                        return '<img src="' + imageUrl + '" height="120px" width="220px" alt="">';
                    } else {
                        return '';
                    }
                }
            },
            {
                data: 'status',
                name: 'status',
                render: function(data, type, full, meta) {
                    var statusClass = data ? 'btn btn-secondary' : 'btn btn-danger';
                    var statusText = data ? 'Active' : 'Inactive';
                    return '<button class="' + statusClass + '" onclick="changeStatus(' + full.id + ')">' + statusText + '</button>';
                }
            },
            {
                data: 'id',
                name: 'details',
                render: function(data, type, full, meta) {
                    return '<a href="{{ url('admin/staff/details') }}/' + data + '" class="btn btn-secondary"><i class="fas fa-eye"></i></a>';
                }
            }
        ]
    });
  });
</script>

<script>
    function changeStatus(userId) {
        $.ajax({
            url: "{{ route('staff.change.status') }}",
            method: "POST",
            data: {
                user_id: userId,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    $('#staffsTable').DataTable().ajax.reload();
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