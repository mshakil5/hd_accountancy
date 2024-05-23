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

                <div class="row justify-content-center mt-3">
                    <div class="col-lg-10">
                        <div class="card">
                            <div class="card-body">
                                <div class="tab-content pt-2" id="myTabjustifiedContent">
                                    <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                                        <div class="mb-3">
                                            <label for="staff_id" class="form-label fw-bold">Employee</label>
                                            <select class="form-select rounded-3 border-3 border-theme select2" id="staff_id" name="staff_id">
                                                <option value="" selected disabled>Choose Employee</option>
                                                @foreach($staffs as $staff)
                                                <option value="{{ $staff->id }}">{{ $staff->first_name }} {{ $staff->last_name }}</option>
                                                @endforeach
                                            </select>
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
                                        </div>
                                        <div class="row">
                                                <div class="col-lg-4 mx-auto text-center">
                                                    <a href="{{route('holiday')}}" class="btn btn-sm btn-outline-dark">Back</a>
                                                </div>
                                            </div>
                                    </div>
                                </div>
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
        var holidayTable = $('#holidayTable').DataTable({
            "columnDefs": [
                { "className": "text-center", "targets": 3 }
            ]
        });

        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

        $('#staff_id').change(function() {
            var staff_id = $(this).val();
            if (staff_id) {
                $.ajax({
                    url: "{{ route('getHolidayData') }}",
                    type: "POST",
                    data: { staff_id: staff_id },
                    success: function(data) {
                        holidayTable.clear().draw();
                        if (data.length > 0) {
                            $.each(data, function(index, holiday) {
                                var startDateFormatted = moment(holiday.start_date).format('DD.MM.YYYY');
                                var endDateFormatted = moment(holiday.end_date).format('DD.MM.YYYY');
                                holidayTable.row.add([
                                    holiday.first_name + ' ' + holiday.last_name,
                                    startDateFormatted,
                                    endDateFormatted,
                                    holiday.total_day,
                                    holiday.status === 0 ? 'Processing' : holiday.status === 1 ? 'Approved' : 'Declined' 
                                ]).draw(false);
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log(error);
                    }
                });
            }
        });
    });
</script>

@endsection
