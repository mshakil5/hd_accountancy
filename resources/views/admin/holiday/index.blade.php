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
                                                <label for="holidayTypeId" class="txt-theme fw-bold">Holiday Type:</label>
                                                <select class="form-control mt-2" id="holidayTypeId">
                                                    <option value="" selected disabled>Select holiday type</option>
                                                    @foreach($holidayTypes as $holidayType)
                                                        <option value="{{ $holidayType->id }}">{{ $holidayType->type }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group mt-4">
                                                <label for="" class="txt-theme fw-bold">Admin Note:</label>
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
                <div class="col-lg-12 p-3 text-end">
                    <a href="{{ route('holidayReport') }}" class="btn btn-sm bg-theme text-light btn-outline-dark"> Holiday Report</a>
                    
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
                            <th scope="col">Status</th> 
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
        var originalStatus = {};

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
                        return moment(data).format('DD.MM.YYYY');
                    }
                },
                {
                    data: 'end_date',
                    name: 'end_date',
                    render: function(data, type, row) {
                        return moment(data).format('DD.MM.YYYY');
                    }
                },
                {data: 'comment', name: 'comment'},
                {data: 'total_day', name: 'total_day'},
                {
                    data: 'status', 
                    name: 'status',
                    render: function(data, type, row) {
                        var isDisabled = data === 1 || data === 2;
                        var disabledAttr = isDisabled ? 'disabled' : '';
                        var dropdown = `
                            <select class="form-select change-status" ${disabledAttr} data-status-id="${row.DT_RowId}" data-staff-id="${row.staff_id}" data-start-date="${row.start_date}" data-end-date="${row.end_date}" data-holiday-request-id="${row.id}">
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
                        var logUrl = "{{ url('/admin/holiday-log') }}/" + row.id;
                        return `<a href="${logUrl}" class="btn btn-primary">
                                    <i class="fa fa-file-alt" style="font-size: 18px;"></i>
                                </a>`;
                    }
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        var isDisabled = row.status === 1 || row.status === 2;
                        var editUrl = "{{ url('/admin/edit-holiday') }}/" + row.id;
                        
                        if (isDisabled) {
                            return `<button class="btn btn-primary" disabled>
                                        <i class="fa fa-edit" style="font-size: 20px;" id="edit-btn"></i>
                                    </button>`;
                        } else {
                            return `<a href="${editUrl}" class="btn btn-primary">
                                        <i class="fa fa-edit" style="font-size: 20px;" id="edit-btn"></i>
                                    </a>`;
                        }
                    }
                }
            ]
        });

        function fetchOriginalStatus() {
            $('#thisTable tbody').find('tr').each(function(index, row) {
                var rowData = table.row(row).data();
                originalStatus[rowData.DT_RowId] = rowData.status;
            });
        }

        table.on('draw', function() {
            fetchOriginalStatus();
        });

        $(document).on('click', '#saveNote', function() {
            var holidayTypeId = $('#holidayTypeId').val();
            var note = $('#note').val();
            var holidayId = window.selectedHolidayId;
            var status = window.selectedStatus;
            var staffId = window.selectedStaffId;
            var startDate = window.selectedStartDate;
            var endDate = window.selectedEndDate;

            var data = {
                holiday_type_id: holidayTypeId,
                admin_note: note,
                status: status,
                holiday_id: holidayId,
                staff_id: staffId,
                start_date: startDate,
                end_date: endDate,
                _token: '{{ csrf_token() }}'
            };

            $.ajax({
                url: "{{ route('store.holiday') }}",
                type: "POST",
                data: data,
                success: function(response) {
                    toastr.success("Status changed successfully", "Success");
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
            var selectedStatus = $(this).val();
            var selectedHolidayId = $(this).data('status-id');
            var selectedStaffId = $(this).data('staff-id');
            var selectedStartDate = $(this).data('start-date');
            var selectedEndDate = $(this).data('end-date');
            var holidayRequestId = $(this).data('holiday-request-id');

            $.ajax({
                url: "{{ route('get.holiday.type') }}",
                type: "POST",
                data: {
                    id: holidayRequestId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#holidayTypeId').val(response.holiday_type_id);
                    $('#note').val(response.admin_note);

                    window.selectedStatus = selectedStatus;
                    window.selectedHolidayId = selectedHolidayId;
                    window.selectedStaffId = selectedStaffId;
                    window.selectedStartDate = selectedStartDate;
                    window.selectedEndDate = selectedEndDate;

                    $('#myModal').modal('show');
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching holiday type:', error);
                }
            });
        });

        $('#myModal').on('hidden.bs.modal', function () {
            window.selectedStatus = null;
            window.selectedHolidayId = null;
            window.selectedStaffId = null;
            window.selectedStartDate = null;
            window.selectedEndDate = null;

            $('.change-status').each(function() {
                var rowId = $(this).data('status-id');
                $(this).val(originalStatus[rowId]);
            });
        });

        $('#myModal').on('show.bs.modal', function () {
            var holidayRequestId = window.selectedHolidayId;
            
            $.ajax({
                url: "{{ route('get.holiday.type') }}",
                type: "POST",
                data: {
                    id: holidayRequestId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#holidayTypeId').val(response.holiday_type_id);
                    $('#note').val(response.admin_note);
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching holiday type:', error);
                }
            });
        });

        fetchOriginalStatus();
    });
</script>

@endsection