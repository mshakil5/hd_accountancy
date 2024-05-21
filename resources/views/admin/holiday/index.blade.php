@extends('admin.layouts.admin')

@section('content')

<!-- Main content -->
<section class="section dashboard">
    <div class="row">

        <!-- Modal start-->
            <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                <div class="modal-dialog mt-2" role="document">
                    <div class="modal-content">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100 position-relative">
                                        <div class="card-body px-0">
                                            <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                                               Note
                                            </div>
                                             <div class="form-group mt-4">
                                                <label for="holidayTypeId" class="txt-theme fw-bold">Holiday Type</label>
                                                <select class="form-control mt-2" id="holidayTypeId">
                                                    <option value="" selected disabled>Select holiday type</option>
                                                    @foreach($holidayTypes as $holidayType)
                                                        <option value="{{ $holidayType->id }}">{{ $holidayType->type }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group mt-4">
                                                <label for="" class="txt-theme fw-bold">Note</label>
                                                <textarea class="form-control" id="note" rows="7" name="" placeholder="Note..."></textarea>
                                            </div>
                                            <div class="text-center">
                                                <button type="button" class="mt-3 btn btn-primary bg-theme-light fs-4 border-theme border-2 fw-bold txt-theme" id="saveNote">Send</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <!-- Modal end-->

        <div class="col-lg-12 px-0 border shadow-sm">
            <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
                <i class="bx bxs-user-plus fs-4 me-2"></i> Holiday
            </p>
            <div class="row px-3">
                {{-- <div class="col-lg-4 p-3">
                    <input type="search" name="" placeholder="Search..." class="form-control" id="">
                </div>
                <div class="col-lg-4 p-3">
                    <input type="search" name="" placeholder="Sort By" class="form-control" id="">
                </div> --}}
                <div class="col-lg-12 p-3 text-end">
                    <a href="{{ route('createholiday') }}" class="btn btn-sm bg-theme text-light btn-outline-dark">+ Add New</a>
                </div>
            </div>
            <div class="table-wrapper my-4 mx-auto" style="width: 95%;">
                <table class="table cell-border table-bordered table-striped" id="thisTable">
                    <thead>
                        <tr>
                            <th scope="col">Sl</th>
                            <th scope="col">Staff Name</th>
                            <th scope="col">Start Date</th>
                            <th scope="col">End Date</th>
                            <th scope="col">Comment</th>
                            <th scope="col">Total Days</th>
                            <th scope="col">Change Status</th> 
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
            ajax: "{{ route('get.holiday') }}",
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                {data: 'staff_name', name: 'staff_name' },
                {
                    data: 'start_date',
                    name: 'start_date',
                    render: function(data, type, row) {
                        return moment(data).format('DD.MM.YY');
                    }
                },
                {
                    data: 'end_date',
                    name: 'end_date',
                    render: function(data, type, row) {
                        return moment(data).format('DD.MM.YY');
                    }
                },
                {data: 'comment', name: 'comment'},
                {data: 'total_day', name: 'total_day'},
                {
                    data: 'status', 
                    name: 'status',
                    render: function(data, type, row) {
                        var dropdown = `
                            <select class="form-select change-status" data-status-id="${row.DT_RowId}">
                                <option value="0" ${data === 0 ? 'selected' : ''}>Processing</option>
                                <option value="1" ${data === 1 ? 'selected' : ''}>Approved</option>
                                <option value="2" ${data === 2 ? 'selected' : ''}>Declined</option>
                            </select>`;
                        return dropdown;
                    }
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        var editUrl = "{{ url('/admin/edit-holiday') }}/" + row.id;
                        return `<a href="${editUrl}" class="btn btn-primary">
                                    <i class="fa fa-edit" style="font-size: 20px;"></i>
                                </a>`;
                    }
                }
            ]
        });

        $(document).on('click', '#saveNote', function() {
            var holidayTypeId = $('#holidayTypeId').val();
            var note = $('#note').val();
            var holidayId = window.selectedHolidayId;
            var status = window.selectedStatus;

            var data = {
                holiday_type_id: holidayTypeId,
                admin_note: note,
                status: status,
                holiday_id: holidayId,
                _token: '{{ csrf_token() }}'
            };

            console.log(data);
            $.ajax({
                url: "{{ route('store.holiday') }}",
                type: "POST",
                data: data,
                success: function(response) {
                    swal({
                        title: "Success!",
                        text: "Status chnaged successfully",
                        icon: "success",
                        button: "OK",
                    });
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                    $('#myModal').modal('hide');
                },
                error: function(xhr, status, error) {
                     console.error(xhr.responseText);
                }
            });
        });

        $(document).on('change', '.change-status', function() {
            var modal = $('#myModal');
            modal.modal('show');
            var selectedStatus = $(this).val();
            window.selectedStatus = selectedStatus;
            var selectedHolidayId = $(this).data('status-id');
            window.selectedHolidayId = selectedHolidayId;
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
                            swal({
                                title: "Success!",
                                text: "Data deleted successfully",
                                icon: "success",
                                button: "OK",
                            });
                            $('#thisTable').DataTable().ajax.reload();
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
{{-- Delete prorota start --}}



@endsection