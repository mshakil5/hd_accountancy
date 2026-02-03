@extends('admin.layouts.admin')

@section('content')

<!-- Main content -->
<section class="section dashboard">
    <div class="row">
        <div class="col-lg-12 px-0 border shadow-sm">
            <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
                <i class="bx bxs-user-plus fs-4 me-2"></i> Prorota
            </p>
            <div class="row px-3">
                {{-- <div class="col-lg-4 p-3">
                    <input type="search" name="" placeholder="Search..." class="form-control" id="">
                </div>
                <div class="col-lg-4 p-3">
                    <input type="search" name="" placeholder="Sort By" class="form-control" id="">
                </div> --}}
                <div class="col-lg-12 p-3 text-end">
                    <a href="{{ route('prorota.create') }}" class="btn btn-sm bg-theme text-light btn-outline-dark">+ Add New</a>
                </div>
            </div>
            <div class="table-wrapper my-2 table-responsive mx-1">
                <table class="table cell-border table-bordered table-striped" id="thisTable">
                    <thead>
                        <tr>
                            <th scope="col">Sl</th>
                            <th scope="col">Staff Name</th>
                            <!-- <th scope="col">Schedule Type</th> -->
                            <th scope="col">Details</th>
                            <th scope="col">Log</th>
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
    var table = $('#thisTable').DataTable({
        serverSide: true,
        ajax: "{{ route('get.prorota') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'staff_name', name: 'staff_name' },
            // {data: 'schedule_type', name: 'schedule_type'},
            {
                data: 'id',
                name: 'details',
                render: function(data, type, full, meta) {
                    var viewButtonHtml = '<a href="{{ url('admin/prorota/details') }}/' + data + '" class="btn btn-success"><i class="fa fa-eye"></i></a>';
                    return viewButtonHtml;
                }
            },
            {
                data: 'id',
                name: 'log',
                render: function(data, type, full, meta) {
                    return '<a href="{{ url('admin/prorota-log') }}/' + data + '" class="btn btn-primary"><i class="fa fa-file-alt"></i></a>';
                }
            },
            {
                data: 'id',
                name: 'details',
                render: function(data, type, full, meta) {
                    var editButtonHtml = '<a href="{{ url('admin/prorota/edit') }}/' + data + '" class="btn btn-secondary"><i class="fa fa-edit"></i></a>';
                    var deleteButtonHtml = '<a href="#" class="btn btn-danger delete-prorota" data-prorota-id="' + data + '" style="margin-left: 10px;"><i class="fas fa-trash"></i></a>';
                    return editButtonHtml + deleteButtonHtml;
                }
            }
        ]
    });
  });
</script>


{{-- Delete prorota start --}}
<script>
    $(document).ready(function() {
        $(document).on('click', '.delete-prorota', function(e) {
            e.preventDefault();
            var prorotaId = $(this).data('prorota-id');

            if (confirm("Are you sure you want to delete this data?")) {
                $.ajax({
                    url: '/admin/delete-prorota/' + prorotaId, 
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') 
                    },
                    success: function(response) {
                        if (response.status === 200) {
                            toastr.success("Deleted successfully", 'Success');
                            $('#thisTable').DataTable().ajax.reload();
                        } else {
                        toastr.error("Failed to delete.", "Error!");
                        }
                    },
                    error: function(xhr, status, error) {
                    
                    }
                });
            }
        });
    });
</script>
{{-- Delete prorota start --}}



@endsection