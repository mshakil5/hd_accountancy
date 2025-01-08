@extends('admin.layouts.admin')

@section('content')
<link href="{{ asset('assets/css/customize2.css')}}" rel="stylesheet">

<section class="section">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 px-0 border shadow-sm">
                <p class="p-2 bg-theme text-white px-3 mb-0 text-capitalize d-flex align-items-center">
                    <i class='bx bxs-user-plus fs-4 me-2'></i> New Holiday Entry
                </p>

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

                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <div class="tab-content pt-2" id="myTabjustifiedContent">
                                    <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                                        <form>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="staff_id" class="form-label fw-bold">Employee</label>
                                                    <select class="form-select rounded-2 border-1 select2" id="staff_id" name="staff_id">
                                                        <option value="" selected disabled>Choose Employee</option>
                                                        @foreach($staffs as $staff)
                                                        <option value="{{ $staff->id }}">{{ $staff->first_name }} {{ $staff->last_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="holiday_type" class="form-label fw-bold">Holiday Type</label>
                                                    <select class="form-select rounded-2 border-1 select2" id="holiday_type" name="holiday_type">
                                                        <option value="" selected disabled>Choose Holiday Type</option>
                                                        @foreach($holidayTypes as $holidayType)
                                                        <option value="{{ $holidayType->id }}">{{ $holidayType->type }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="start_date" class="form-label fw-bold">Start Date</label>
                                                    <input type="date" id="start_date" name="start_date" class="form-control border-theme text-center fs-6 rounded-2 border-1 txt-theme fw-bold" value="">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="end_date" class="form-label fw-bold">End Date</label>
                                                    <input type="date" id="end_date" name="end_date" class="form-control border-theme text-center fs-6 rounded-2 border-1 txt-theme fw-bold" value="">
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="admin_note" class="form-label fw-bold">Admin Note</label>
                                                <textarea id="admin_note" name="admin_note" class="form-control rounded-2 border-1 border-theme" placeholder="Leave a note" rows="2"></textarea>
                                            </div>
                                            
                                            <div class="mb-3 mt-3 d-flex justify-content-center align-items-center">
                                                <a href="{{route('holiday')}}" class="btn btn-sm btn-outline-dark">Cancel</a>
                                                <span class="me-3"></span>
                                                <button type="button" id="saveButton" class="btn btn-primary btn-sm border-theme bg-theme text-light fw-bold">Save</button>
                                            </div>
                                        </form>
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
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>

<script>
    $(document).ready(function() {
        $('#saveButton').click(function(event) {
            event.preventDefault();
            var saveButton = $(this);
            saveButton.prop('disabled', true);
            
            var data = {
                staff_id: $('#staff_id').val(),
                start_date: $('#start_date').val(),
                end_date: $('#end_date').val(),
                holiday_type: $('#holiday_type').val(),
                admin_note: $('#admin_note').val()
            };
            console.log(data);

            $.ajax({
                url: "{{url('/admin/holiday')}}",
                type: 'POST',
                data: JSON.stringify(data), 
                contentType: 'application/json',
                dataType: 'json',
                success: function (response) {
                    toastr.success("Holiday created successfully", "Success");
                    window.setTimeout(function(){
                        location.reload();
                    }, 2000);
                },
                error: function (xhr, status, error) {
                    toastr.error("An error occurred. Please try again later.", "Error");
                    console.error(xhr.responseText);
                },
                complete: function() {
                    saveButton.prop('disabled', false);
                }
            });
            return false;
        });
    });
</script>

@endsection