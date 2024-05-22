@extends('admin.layouts.admin')

@section('content')

<section class="section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-theme text-white py-3">
                        <h5 class="card-title mb-0">
                            <i class='fs-4 me-2'></i> Holiday Edit Of <strong>{{ $clientName }}</strong>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <input type="hidden" value="{{$staff_id}}" id="staffId">
                                <label for="holiday_type" class="form-label fw-bold">Holiday Type</label>
                                <select class="form-select rounded-3 border-3 border-theme" id="holidayTypeId" name="holidayTypeId">
                                    @foreach($holidayTypes as $holidayType)
                                        <option value="{{ $holidayType->id }}" {{ $holidayType->id == $holiday->holiday_type_id ? 'selected' : '' }}>
                                            {{ $holidayType->type }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label fw-bold">Status</label>
                                <select name="status" id="status" class="form-select rounded-3 border-3 border-theme">
                                    <option value="0" {{ $holiday->status == 0 ? 'selected' : '' }}>Processing</option>
                                    <option value="1" {{ $holiday->status == 1 ? 'selected' : '' }}>Approved</option>
                                    <option value="2" {{ $holiday->status == 2 ? 'selected' : '' }}>Declined</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="start_date" class="form-label fw-bold">Start Date</label>
                                <input type="date" id="start_date" name="start_date" class="form-control border-theme text-center fs-6 rounded-3 border-3 txt-theme fw-bold" value="{{ $holiday->start_date }}">
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" class="form-label fw-bold">End Date</label>
                                <input type="date" id="end_date" name="end_date" class="form-control border-theme text-center fs-6 rounded-3 border-3 txt-theme fw-bold" value="{{ $holiday->end_date }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="comment" class="form-label fw-bold">Comment</label>
                            <input type="text" id="comment" name="comment" class="form-control rounded-3 border-3 border-theme" placeholder="Leave a comment" value="{{ $holiday->comment }}">
                        </div>

                        <div class="mb-3">
                            <label for="comment" class="form-label fw-bold">Admin Note</label>
                            <input type="text" id="admin_note" name="admin_note" class="form-control rounded-3 border-3 border-theme" placeholder="Leave a note" value="{{ $holiday->admin_note }}">
                        </div>

                        <div class="mb-3 mt-3 d-flex justify-content-center align-items-center">
                            <a href="{{route('holiday')}}" class="btn btn-lg btn-outline-dark">Cancel</a>
                             <span class="me-3"></span>
                             @php
                                if ($holiday->status!= 1 && $holiday->status!= 2) {
                                    echo '<button id="saveButton" class="btn btn-primary btn-lg rounded-3 border-theme bg-theme text-light fw-bold">Update</button>';
                                }
                              @endphp
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
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $("#saveButton").click(function(e) {
            e.preventDefault(); 

            let holidayTypeId = $("#holidayTypeId").val();
            let startDate = $("#start_date").val();
            let endDate = $("#end_date").val();
            let comment = $("#comment").val();
            let status = $("#status").val();
            let staff_id = $("#staffId").val();
            let admin_note = $("#admin_note").val();

            let data = {
                holiday_type_id: holidayTypeId,
                start_date: startDate,
                end_date: endDate,
                comment: comment,
                status: status,
                staff_id: staff_id,
                admin_note: admin_note
            };
            console.log(data);

            $.ajax({
                url: "{{ url('/admin/holiday-update/' . $holiday->id) }}",
                type: "POST",
                data: data,
                dataType: "json",
                success: function(response) {
                    swal({
                        title: "Success!",
                        text: "Updated successfully",
                        icon: "success",
                        button: "OK",
                    });
                },
                error: function(xhr, status, error) {
                    console.log(xhr.responseText);
                }
            });
        });
    });
</script>


@endsection