@extends('staff.layouts.staff')

@section('content')

<section class="section dashboard">
    <div class="row">
        <div class="col-lg-4 mb-3">
            <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100">
                <div class="card-body p-0">
                    <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                        Holiday Summary
                    </div>
                    <div class="d-flex gap-3 my-5">
                        <div class="text-center flex-fill">
                            <div class="fs-6 txt-theme fw-bold">Entitled</div>
                            <div class="text-center fs-2 txt-theme fw-bold">
                                   {{$entitled}}
                            </div>
                        </div>
                        <div class="text-center border-start border-3 ps-3 flex-fill">
                            <div class="fs-6 txt-theme fw-bold">Booked</div>
                            <div class="text-center fs-2 txt-theme fw-bold">
                                   {{$booked}}
                            </div>
                        </div>
                        <div class="text-center border-start border-3 ps-3 flex-fill">
                            <div class="fs-6 txt-theme fw-bold">Pending</div>
                            <div class="text-center fs-2 txt-theme fw-bold">
                                  {{$pending}}
                            </div>
                        </div>
                    </div>
                    {{-- <div class="row mt-3">
                        <div class="col-lg-6">
                            <a href="" class="p-2 border-theme text-center fs-6 d-block rounded-3 border-3 txt-theme fw-bold my-1">Clock in</a>
                        </div>
                        <div class="col-lg-6">
                            <a href="" class="p-2 border-theme text-center fs-6 d-block rounded-3 border-3 txt-theme fw-bold my-1">Take Break</a>
                        </div>
                    </div> --}}
                </div>
            </div>
        </div>



        <div class="col-lg-8 mb-3">
            <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100 position-relative">
                <div class="card-body px-0">
                    <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                        Request for holiday
                    </div>
                    <div class="bottom-0 mb-1">
                         <!-- Success and Error message -->
                        <div class="row my-4 px-3">
                            <div class="col-lg-12">
                                <div id="successMessage" class="alert alert-success" style="display: none;">
                                    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                                    <b></b>
                                </div>
                                <div id="errorMessage" class="alert alert-danger" style="display: none;">
                                    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                                    <b></b>
                                </div>
                            </div>
                        </div>
                        <!-- Success and Error message -->

                        <form id="myForm">
                            @csrf
                            <div class="row mt-3">
                                <!-- <div class="col-lg-12">
                                    <label for="holiday_type" class="fw-bold">Holiday Type</label>
                                    <select name="holiday_type" id="holiday_type" class="form-control p-2 border-theme text-center fs-6 d-block rounded-3 border-3 txt-theme fw-bold my-1">
                                        <option value="">Holiday Type</option>
                                        <option value="Test1">Test1</option>
                                        <option value="Test1">Test2</option>
                                        <option value="Test1">Test3</option>
                                    </select>
                                </div> -->
                                <div class="col-lg-6">
                                    <label for="start_date" class="fw-bold">Start Date</label>
                                    <input type="date" id="start_date" name="start_date" class="form-control p-2 border-theme text-center fs-6 d-block rounded-3 border-3 txt-theme fw-bold my-1">
                                </div>
                                <div class="col-lg-6">
                                    <label for="end_date" class="fw-bold">End Date</label>
                                    <input type="date" id="end_date" name="end_date" class="form-control p-2 border-theme text-center fs-6 d-block rounded-3 border-3 txt-theme fw-bold my-1">
                                </div>

                                <div class="col-lg-12">
                                    <label for="comment" class="fw-bold">Comment</label>
                                    <textarea id="comment" name="comment" class="rounded-3 border-2 border-theme form-control" placeholder="Leave a comment" rows="2"></textarea>
                                </div>
                                <div class="col-lg-3">
                                    <button id="saveButton" type="button" class="p-2 border-theme bg-theme text-center fs-6 d-block rounded-3 border-3 text-light fw-bold my-1">Send</button>
                                </div>
                                
                            </div>


                        </form>
                        

                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-3 d-none">
            <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100 position-relative">
                <div class="card-body px-0">
                    <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                        Your Notes
                    </div>
                    <div class="mh250">
                        <!-- Your notes content here -->
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8 mb-3">
            <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100 position-relative">
                <div class="card-body px-0">
                    <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                        Requested Holidays
                    </div>
                    <div class="table-wrapper my-2 table-responsive mx-1">
                        <table id="holidayRequestsTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Admin Note</th>
                                    <th>Total Days</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($holidayRequests as $request)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($request->start_date)->format('d F Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($request->end_date)->format('d F Y') }}</td>
                                    <td>{!! $request->admin_note !!}</td>
                                    <td class="text-center">{{ $request->total_day }}</td>
                                    <td>
                                        @if($request->status == 0)
                                            Processing
                                        @elseif($request->status == 1)
                                            Approved
                                        @elseif($request->status == 2)
                                            Declined
                                        @else
                                            Unknown
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('script')

<script>
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>



<!-- Holiday Start -->
<script>
    $(document).ready(function () {
        $('#saveButton').click(function (event) {
            event.preventDefault();
            var saveButton = $(this);
            saveButton.prop('disabled', true);

            var formData = new FormData($('#myForm')[0]);

            $.ajax({
                url: "{{URL::to('/staff/holiday-request')}}",
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function (response) {
                    toastr.success("Schedule created successfully", "Success");

                    window.setTimeout(function() {
                        location.reload();
                    }, 2000);
                },
                error: function (xhr, status, error) {
                    console.error(xhr.responseText);
                    var errorMessage = "";

                    if (xhr.responseJSON && xhr.responseJSON.status == 423) {
                        console.log(xhr.responseJSON.errors);
                        $('#errorMessage').html(xhr.responseJSON.errors);
                        $('#errorMessage').show();
                        $('#successMessage').hide();
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        $.each(xhr.responseJSON.errors, function (key, value) {
                            errorMessage += value.join(", ") + "<br>";
                        });
                        $('#errorMessage').html(errorMessage);
                    } else {
                        errorMessage = "An error occurred. Please try again later.";
                        $('#errorMessage').html(errorMessage);
                    }

                    $('#errorMessage').show();
                    $('#successMessage').hide();
                },
                complete: function() {
                    saveButton.prop('disabled', false);
                },
                cache: false
            });
            return false;
        });

        $('#clearButton').click(function (event) {
            event.preventDefault();
            $('#myForm')[0].reset();
        });
    });
</script>
<!-- Holiday End -->

<script>
    $(document).ready(function() {
        $('#holidayRequestsTable').DataTable();
    });

</script>

@endsection
