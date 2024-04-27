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
                {{-- <div class="col-lg-4 p-3">
                    <input type="search" name="" placeholder="Search..." class="form-control" id="">
                </div>
                <div class="col-lg-4 p-3">
                    <input type="search" name="" placeholder="Sort By" class="form-control" id="">
                </div> --}}
                <div class="col-lg-12 p-3 text-end">
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
                    var fullName = '';
                    if (full.first_name && full.last_name) {
                        fullName = full.first_name + ' ' + full.last_name;
                    } else if (full.first_name) {
                        fullName = full.first_name;
                    } else if (full.last_name) {
                        fullName = full.last_name;
                    }
                    return fullName;
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
                    var editButtonHtml = '<a href="{{ url('admin/staff/details') }}/' + data + '" class="btn btn-secondary"><i class="fa fa-edit"></i></a>';
                    var deleteButtonHtml = '<a href="#" class="btn btn-danger delete-staff" data-staff-id="' + data + '" style="margin-left: 10px;"><i class="fas fa-trash"></i></a>';

                    return editButtonHtml + deleteButtonHtml;
                }
            }
        ]
    });
  });
</script>


{{-- Delete staff start --}}
<script>
    $(document).ready(function() {
        $(document).on('click', '.delete-staff', function(e) {
            e.preventDefault();
            var staffId = $(this).data('staff-id');

            if (confirm("Are you sure you want to delete this staff member?")) {
                $.ajax({
                    url: '/admin/delete-staff/' + staffId, 
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') 
                    },
                    success: function(response) {
                        if (response.status === 200) {
                            swal({
                                title: "Success!",
                                text: "Staff deleted successfully",
                                icon: "success",
                                button: "OK",
                            });
                            $('#staffsTable').DataTable().ajax.reload();
                        } else {
                                Toastify({
                                    text: "Failed to delete."
                                }).showToast();
                            }
                    },
                    error: function(xhr, status, error) {
                    
                    }
                });
            }
        });
    });
</script>
{{-- Delete staff start --}}

<!-- Staff status change start -->
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
<!-- Staff status change end -->

@endsection