@extends('admin.layouts.admin')

@section('content')
<link href="{{ asset('assets/css/customize2.css') }}" rel="stylesheet">

<section class="section">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 px-0 border shadow-sm">
                <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
                    <i class='bx bxs-user-plus fs-4 me-2'></i> Holiday Report
                </p>

                <div class="px-3">

                    <div class="row mb-3 mt-3">
                        <div class="col-lg-4">
                            <label for="staff_id" class="form-label fw-bold">Employee</label>
                            <select class="form-select rounded-1 border-1 border-theme select2" id="staff_id" name="staff_id">
                                <option value="" selected disabled>Choose Employee</option>
                                @foreach($staffs as $staff)
                                <option value="{{ $staff->id }}">{{ $staff->first_name }} {{ $staff->last_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <label for="start_date" class="form-label fw-bold">Start Date</label>
                            <input type="date" class="form-control rounded-1 border-1 border-theme" id="start_date" name="start_date">
                        </div>
                        <div class="col-lg-2">
                            <label for="end_date" class="form-label fw-bold">End Date</label>
                            <input type="date" class="form-control rounded-1 border-1 border-theme" id="end_date" name="end_date">
                        </div>
                        <div class="col-lg-2">
                            <label for="status" class="form-label fw-bold">Status</label>
                            <select class="form-select rounded-1 border-1 border-theme" id="status" name="status">
                                <option value="" selected disabled>Choose Status</option>
                                <option value="1">Approved</option>
                                <option value="0">Processing</option>
                                <option value="2">Declined</option>
                            </select>
                        </div>
                        <div class="col-lg-2 align-self-end">
                            <label for="search-btn" class="form-label fw-bold mb-0">Action</label>
                            <div class="d-flex align-items-end">
                                <button type="button" class="btn btn-primary rounded-2 border-theme bg-theme text-light fw-bold me-2" id="search-btn" title="Search">
                                    <i class="fas fa-search"></i>
                                </button>
                                <button type="button" class="btn btn-outline-dark" id="clear-btn" title="Clear">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="holidayData">
                        <table id="holidayTable" class="display" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Staff Name</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Total Days</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data -->
                            </tbody>
                        </table>
                        <div class="row">
                            <div class="col-lg-4 mx-auto text-center mb-3">
                                <a href="{{ route('holiday') }}" class="btn btn-primary rounded-2 border-theme bg-theme text-light">Back</a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

@endsection

@section('script')

<script>
    $(document).ready(function() {
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        var holidayTable = $('#holidayTable').DataTable({
            paging: true,
            searching: false,
            ordering: true,
            info: true,
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ],
            columnDefs: [
                { targets: '_all', className: 'text-center' }
            ]
        });

        $('#search-btn').click(function() {
            var staff_id = $('#staff_id').val();
            var start_date = $('#start_date').val();
            var end_date = $('#end_date').val();
            var status = $('#status').val();

            if (!staff_id) {
                alert('Please choose an Employee.');
                return;
            }

            if (end_date && !start_date) {
                alert('Please choose a Start Date before selecting an End Date.');
                return;
            }

            var data = {
                staff_id: staff_id,
                start_date: start_date,
                end_date: end_date,
                status: status
            };

            $.ajax({
                url: "{{ route('getHolidayData') }}",
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                type: "POST",
                data: JSON.stringify(data),
                contentType: 'application/json',
                success: function(response) {
                    holidayTable.clear().draw();

                    if (response.length > 0) {
                        $.each(response, function(index, holiday) {
                            var startDateFormatted = moment(holiday.start_date).format('DD.MM.YYYY');
                            var endDateFormatted = moment(holiday.end_date).format('DD.MM.YYYY');
                            var statusText = holiday.status === 0 ? 'Processing' : (holiday.status === 1 ? 'Approved' : 'Declined');

                            holidayTable.row.add([
                                holiday.first_name + ' ' + holiday.last_name,
                                startDateFormatted,
                                endDateFormatted,
                                holiday.total_day,
                                statusText
                            ]).draw(false);
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching holiday data:', error);
                }
            });
        });

        $('#clear-btn').click(function() {
            $('#staff_id').val('').trigger('change');
            $('#start_date').val('');
            $('#end_date').val('');
            $('#status').val('');

             holidayTable.clear().draw();
        });

    });
</script>

@endsection
